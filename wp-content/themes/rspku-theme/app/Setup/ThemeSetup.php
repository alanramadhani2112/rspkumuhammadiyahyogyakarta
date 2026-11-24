<?php

declare(strict_types=1);

namespace Rspku\Setup;

final class ThemeSetup
{
    private const NOINDEX = 'noindex, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    /**
     * Pages kept out of the index and sitemap: WP default sample page, one-line stubs without content yet,
     * and /cari-dokter/ (301 to /jadwal-dokter/ in legacyTarget, so it must not be listed as a sitemap URL).
     */
    private const NOINDEX_PAGES = ['sample-page', 'pusat-informasi', 'e-career', 'cari-dokter'];

    public static function register(): void
    {
        add_action('after_setup_theme', [self::class, 'configureTheme']);
        self::registerHooks();
    }

    /**
     * Register non-configureTheme hooks. Kept separate from {@see register()}
     * so callers that already invoke {@see configureTheme()} directly
     * (e.g. the Theme bootstrap) can still wire up template_redirect and
     * other runtime hooks without re-running theme configuration twice.
     */
    public static function registerHooks(): void
    {
        add_action('pre_get_posts', [self::class, 'configureSearchPostTypes']);
        add_action('template_redirect', [self::class, 'legacyRedirects']);
        add_filter('wpseo_title', [self::class, 'yoastTitle']);
        add_filter('wpseo_metadesc', [self::class, 'yoastMetaDescription']);
        add_filter('wpseo_robots', [self::class, 'yoastRobots']);
        add_filter('wpseo_sitemap_exclude_taxonomy', [self::class, 'excludeTagSitemap'], 10, 2);
        add_filter('wpseo_exclude_from_sitemap_by_post_ids', [self::class, 'excludeSamplePageFromSitemap']);
        add_filter('the_content', [self::class, 'cleanLegacyPageContent'], 20);
        add_filter('wpseo_exclude_from_sitemap_by_term_ids', [self::class, 'excludeThinCategories']);
        add_filter('wpseo_sitemap_exclude_author', [self::class, 'excludeAuthorsFromSitemap']);
        add_filter('wpseo_canonical', [self::class, 'blogPageCanonical']);
        add_filter('rest_endpoints', [self::class, 'hideUsersRoutes']);
    }

    /** Categories with fewer posts than this are thin listings: noindex and out of the sitemap. */
    private const THIN_CATEGORY_MAX_POSTS = 4;

    private static function isThinCategory(object $term): bool
    {
        return (int) ($term->count ?? 0) <= self::THIN_CATEGORY_MAX_POSTS;
    }

    /**
     * @param array<int,int> $ids
     * @return array<int,int>
     */
    public static function excludeThinCategories(array $ids): array
    {
        $terms = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);
        foreach (is_array($terms) ? $terms : [] as $term) {
            if ($term instanceof \WP_Term && self::isThinCategory($term)) {
                $ids[] = (int) $term->term_id;
            }
        }

        return $ids;
    }

    /**
     * Author archives only list "Admin"-type accounts: drop them from the sitemap.
     *
     * @param array<int,mixed> $users
     * @return array<int,mixed>
     */
    public static function excludeAuthorsFromSitemap(array $users): array
    {
        return [];
    }

    /** /berita-artikel/page/N/ is its own listing page, not a copy of page 1. */
    public static function blogPageCanonical(string $canonical): string
    {
        $paged = max((int) get_query_var('paged'), (int) get_query_var('page'));
        if ($paged > 1 && is_page('berita-artikel')) {
            return trailingslashit((string) get_permalink()) . 'page/' . $paged . '/';
        }

        return $canonical;
    }

    /**
     * Anonymous visitors can list usernames through /wp-json/wp/v2/users; logged-in users keep it.
     *
     * @param array<string,mixed> $endpoints
     * @return array<string,mixed>
     */
    public static function hideUsersRoutes(array $endpoints): array
    {
        if (is_user_logged_in()) {
            return $endpoints;
        }

        unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);

        return $endpoints;
    }

    /**
     * Legacy page content carries its own <h1> (duplicating the template H1) and a literal
     * [yoast_breadcrumb] shortcode. Demote the duplicate heading and drop the stray tag.
     */
    public static function cleanLegacyPageContent(string $content): string
    {
        if ((!is_page() && !is_singular('post')) || !is_main_query()) {
            return $content;
        }

        $content = str_replace('[yoast_breadcrumb]', '', $content);
        // Articles always get their H1 from the template, so every content H1 is a duplicate.
        // Pages: only a content H1 repeating the page title (some pages rely on their own H1).
        $title = self::plain((string) get_the_title());
        $allDuplicates = is_singular('post');

        return (string) preg_replace_callback(
            '#<h1(\s[^>]*)?>(.*?)</h1>#is',
            static fn (array $m): string => $allDuplicates || self::plain($m[2]) === $title ? '<h2' . $m[1] . '>' . $m[2] . '</h2>' : $m[0],
            $content
        );
    }

    private static function plain(string $html): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES))));
    }

    /** Tag archives are thin (most list a single article): keep them out of the sitemap. */
    public static function excludeTagSitemap(bool $exclude, string $taxonomy): bool
    {
        return $exclude || $taxonomy === 'post_tag';
    }

    /**
     * @param array<int,int> $ids
     * @return array<int,int>
     */
    public static function excludeSamplePageFromSitemap(array $ids): array
    {
        foreach (self::NOINDEX_PAGES as $slug) {
            $page = get_page_by_path($slug);
            if ($page instanceof \WP_Post) {
                $ids[] = (int) $page->ID;
            }
        }

        return $ids;
    }

    public static function configureSearchPostTypes(\WP_Query $query): void
    {
        if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
            return;
        }

        $query->set('post_type', [
            'post',
            'page',
            'dokter',
            'poliklinik',
            'layanan',
            'jurnal',
            'manajemen-rs',
            'rawat-inap',
        ]);
    }

    /**
     * Redirect old URLs to new ones for backward compatibility
     */
    public static function legacyRedirects(): void
    {
        $uri = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field((string) wp_unslash($_SERVER['REQUEST_URI']))
            : '';

        if ($uri === '') {
            return;
        }

        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return;
        }

        $path = trim($path, '/');

        // Redirect /e-journal/ -> /e-jurnal/
        if (preg_match('#^e-journal(/.*)?$#', $path, $m)) {
            $suffix = $m[1] ?? '';
            wp_safe_redirect(home_url('/e-jurnal' . $suffix), 301);
            exit;
        }

        $target = self::legacyTarget($path);
        if ($target !== null) {
            wp_safe_redirect(home_url($target), 301);
            exit;
        }
    }

    /**
     * Old URL structure -> current URL. Returns null when there is no confident target.
     */
    private static function legacyTarget(string $path): ?string
    {
        /** @var array<string,string> $map reviewed old path => new path */
        static $map = null;
        $map ??= require __DIR__ . '/legacy-redirect-map.php';
        if (isset($map[$path])) {
            return $map[$path];
        }

        // /cari-dokter/ is an empty legacy page (raw shortcode); the working doctor filter lives on /jadwal-dokter/.
        if ($path === 'caridokter' || $path === 'dokterku' || $path === 'cari-dokter') {
            return '/jadwal-dokter/';
        }

        // /doctors/x -> /dokter/x, /departments/x -> /poliklinik/x, only when the same slug exists now.
        if (preg_match('#^(doctors|departments)/([^/]+)$#', $path, $m) === 1) {
            $type = $m[1] === 'doctors' ? 'dokter' : 'poliklinik';
            if (get_page_by_path($m[2], OBJECT, $type) instanceof \WP_Post) {
                return '/' . $type . '/' . $m[2] . '/';
            }
        }

        return null;
    }

    public static function configureTheme(): void
    {
        load_theme_textdomain('rspku-theme', RSPKU_THEME_PATH . '/languages');

        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support('custom-logo', [
            'height' => 96,
            'width' => 96,
            'flex-height' => true,
            'flex-width' => true,
        ]);
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
            'navigation-widgets',
        ]);
        add_theme_support('editor-styles');
        add_theme_support('responsive-embeds');
        add_theme_support('align-wide');
        add_theme_support('wp-block-styles');

        add_image_size('rspku-hero', 1600, 900, true);
        add_image_size('rspku-card', 960, 720, true);
        add_image_size('rspku-doctor', 720, 900, true);
        add_image_size('rspku-square', 800, 800, true);

        register_nav_menus([
            'primary' => __('Primary Menu', 'rspku-theme'),
            'footer' => __('Footer Menu', 'rspku-theme'),
            'utility' => __('Utility Menu', 'rspku-theme'),
        ]);

        global $content_width;
        $content_width = $content_width ?? 1200;
    }

    /**
     * Reviewed per-post title/meta from seo-overrides.php. An editor's own Yoast value always wins.
     */
    private static function seoOverride(string $field): string
    {
        if (!is_singular()) {
            return '';
        }

        $post = get_queried_object();
        if (!$post instanceof \WP_Post) {
            return '';
        }

        /** @var array<string,array{title?:string,desc?:string}> $map key: post slug, or "type:slug" for non-posts */
        static $map = null;
        $map ??= require __DIR__ . '/seo-overrides.php';
        $key = $post->post_type === 'post' ? $post->post_name : $post->post_type . ':' . $post->post_name;

        return $map[$key][$field] ?? '';
    }

    private const TITLE_SUFFIX = ' | RS PKU Muhammadiyah Yogyakarta';
    private const ARCHIVE_TITLES = [
        'poliklinik' => 'Daftar Poliklinik',
        'dokter' => 'Daftar Dokter',
        'layanan' => 'Semua Layanan',
        'manajemen-rs' => 'Manajemen RS',
    ];

    /** "Label [- Halaman N] | brand", with the short brand when the long one passes ~60 chars. */
    private static function archiveTitle(string $label): string
    {
        $paged = (int) get_query_var('paged');
        $base = $label . ($paged > 1 ? ' - Halaman ' . $paged : '');

        return $base . (mb_strlen($base . self::TITLE_SUFFIX) > 60 ? ' | RS PKU Jogja' : self::TITLE_SUFFIX);
    }

    public static function yoastTitle(string $title): string
    {
        // An editor's own Yoast title always wins.
        // A bare "%%title%%" is Yoast's no-brand placeholder, not an editorial choice.
        $own = is_singular() ? trim((string) get_post_meta((int) get_queried_object_id(), '_yoast_wpseo_title', true)) : '';
        $hasOwnTitle = $own !== '' && $own !== '%%title%%';

        if (is_front_page()) {
            return $hasOwnTitle ? $title : 'RS PKU Muhammadiyah Yogyakarta';
        }

        if (is_post_type_archive(array_keys(self::ARCHIVE_TITLES))) {
            $label = self::ARCHIVE_TITLES[(string) get_query_var('post_type')] ?? null;

            return $label === null ? $title : self::archiveTitle($label);
        }

        $term = get_queried_object();
        if ($term instanceof \WP_Term) {
            if (is_category()) {
                return self::archiveTitle('Kategori ' . $term->name);
            }
            if (is_tax('spesialisasi-dokter')) {
                $prefix = preg_match('/^(dokter|psikolog)/i', $term->name) === 1 ? '' : 'Dokter ';

                return self::archiveTitle($prefix . $term->name);
            }
            if (is_tax('kategori-layanan')) {
                return self::archiveTitle($term->name);
            }
        }

        $override = self::seoOverride('title');
        if ($override !== '' && !$hasOwnTitle) {
            return $override;
        }

        // Long clinic names push the default title past ~60 chars: use the short brand suffix.
        if (!$hasOwnTitle && is_singular('poliklinik') && mb_strlen($title) > 60) {
            return (string) preg_replace('/\s*[|\-–]\s*RS PKU Muhammadiyah Yogyakarta$/u', ' | RS PKU Jogja', $title);
        }

        return $title;
    }

    public static function yoastMetaDescription(string $description): string
    {
        if (trim($description) !== '') {
            return $description;
        }

        $override = self::seoOverride('desc');
        if ($override !== '') {
            return $override;
        }

        if (is_front_page()) {
            return 'RS PKU Muhammadiyah Yogyakarta menghadirkan layanan kesehatan, dokter spesialis, rawat inap, IGD, dan pendaftaran online untuk pasien dan keluarga.';
        }

        if (is_post_type_archive('dokter')) {
            return 'Temukan dokter RS PKU Muhammadiyah Yogyakarta berdasarkan nama, spesialisasi, dan jadwal praktik terbaru.';
        }

        if (is_post_type_archive('poliklinik')) {
            return 'Lihat daftar poliklinik RS PKU Muhammadiyah Yogyakarta untuk layanan rawat jalan dan konsultasi dokter spesialis.';
        }

        if (is_post_type_archive('layanan') || is_tax('kategori-layanan')) {
            return 'Jelajahi layanan medis, layanan unggulan, dan layanan penunjang di RS PKU Muhammadiyah Yogyakarta.';
        }

        if (is_post_type_archive('rawat-inap')) {
            return 'Informasi fasilitas rawat inap RS PKU Muhammadiyah Yogyakarta, pilihan kamar, dan layanan perawatan pasien.';
        }

        if (is_post_type_archive('manajemen-rs')) {
            return 'Profil manajemen RS PKU Muhammadiyah Yogyakarta dan struktur pimpinan rumah sakit.';
        }

        if (is_singular('dokter')) {
            $name = (string) get_the_title();
            $specialties = self::termNames('spesialisasi-dokter');
            $suffix = $specialties !== '' ? ' spesialis ' . $specialties : '';
            return trim($name . $suffix . ' di RS PKU Muhammadiyah Yogyakarta. Lihat profil dan jadwal praktik dokter.');
        }

        if (is_singular('poliklinik')) {
            // "jadwal dokter" only when the page really lists doctors (same lookup the template uses).
            $hasDoctors = (new \Rspku\Repositories\DoctorRepository())->forPolyclinic((int) get_the_ID(), 1) !== [];

            return (string) get_the_title() . ' RS PKU Muhammadiyah Yogyakarta: '
                . ($hasDoctors ? 'jadwal dokter spesialis, layanan, dan daftar online.' : 'informasi layanan dan pendaftaran online.');
        }

        if (is_singular('layanan')) {
            $category = self::termNames('kategori-layanan');
            $suffix = $category !== '' ? ' kategori ' . $category : '';
            return 'Informasi layanan ' . (string) get_the_title() . $suffix . ' di RS PKU Muhammadiyah Yogyakarta.';
        }

        if (is_singular('rawat-inap')) {
            return 'Informasi fasilitas ' . (string) get_the_title() . ' untuk rawat inap di RS PKU Muhammadiyah Yogyakarta.';
        }

        if (is_singular('jurnal')) {
            return 'E-Jurnal RS PKU Muhammadiyah Yogyakarta: ' . (string) get_the_title() . '.';
        }

        return $description;
    }

    public static function yoastRobots(string $robots): string
    {
        if (is_search() || is_tag() || is_author() || is_page(self::NOINDEX_PAGES)) {
            return self::NOINDEX;
        }

        $term = get_queried_object();
        if (is_category() && $term instanceof \WP_Term && self::isThinCategory($term)) {
            return self::NOINDEX;
        }

        if (is_post_type_archive('dokter') && self::hasQueryFilters(['q', 'specialization', 'day', 'service'])) {
            return self::NOINDEX;
        }

        return $robots;
    }

    /**
     * @param array<int,string> $keys
     */
    private static function hasQueryFilters(array $keys): bool
    {
        foreach ($keys as $key) {
            if (isset($_GET[$key]) && trim((string) wp_unslash($_GET[$key])) !== '') {
                return true;
            }
        }

        return false;
    }

    private static function termNames(string $taxonomy): string
    {
        $terms = get_the_terms(get_the_ID(), $taxonomy);
        if (!is_array($terms) || $terms === []) {
            return '';
        }

        $names = [];
        foreach ($terms as $term) {
            if ($term instanceof \WP_Term && $term->name !== '') {
                $names[] = $term->name;
            }
        }

        return implode(', ', array_slice($names, 0, 3));
    }
}
