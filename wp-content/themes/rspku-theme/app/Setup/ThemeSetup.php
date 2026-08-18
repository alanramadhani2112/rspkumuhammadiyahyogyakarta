<?php

declare(strict_types=1);

namespace Rspku\Setup;

final class ThemeSetup
{
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
        add_action('template_redirect', [self::class, 'legacyRedirects']);
        add_filter('wpseo_metadesc', [self::class, 'yoastMetaDescription']);
        add_filter('wpseo_robots', [self::class, 'yoastRobots']);
    }

    /**
     * Redirect old URLs to new ones for backward compatibility
     */
    public static function legacyRedirects(): void
    {
        if (is_singular('manajemen-rs')) {
            wp_safe_redirect(get_post_type_archive_link('manajemen-rs') ?: home_url('/manajemen-rs/'), 301);
            exit;
        }

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

    public static function yoastMetaDescription(string $description): string
    {
        if (trim($description) !== '') {
            return $description;
        }

        if (is_front_page()) {
            return 'RS PKU Muhammadiyah Yogyakarta menghadirkan layanan kesehatan, dokter spesialis, fasilitas rawat inap, IGD, dan pendaftaran online untuk pasien dan keluarga.';
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
            return 'Informasi ' . (string) get_the_title() . ' RS PKU Muhammadiyah Yogyakarta, layanan rawat jalan, dan dokter terkait.';
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
        if (is_search()) {
            return 'noindex, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        }

        if (is_post_type_archive('dokter') && self::hasQueryFilters(['q', 'specialization', 'day', 'service'])) {
            return 'noindex, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
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
