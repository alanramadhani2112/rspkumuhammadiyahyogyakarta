<?php

declare(strict_types=1);

namespace Rspku\Controllers;

use Rspku\Helpers\View;
use Rspku\Repositories\ContentRepository;
use Rspku\Repositories\DoctorRepository;
use Rspku\Repositories\DoctorScheduleRepository;
use Rspku\Repositories\ReviewRepository;
use Timber\Timber;
use WP_Post_Type;
use WP_Query;
use WP_Term;

final class TemplateController
{
    public static function render(): void
    {
        $context = self::context();
        $templates = self::templates($context);

        View::render($templates, $context);
    }

    /**
     * @return array<string,mixed>
     */
    private static function context(): array
    {
        $context = class_exists(Timber::class) ? Timber::context() : [];
        $contentRepository = new ContentRepository();
        $doctorRepository = new DoctorRepository();
        $doctorScheduleRepository = new DoctorScheduleRepository();
        $reviewRepository = new ReviewRepository();

        if (is_front_page()) {
            // Dynamic pickers: use admin-selected IDs if available,
            // otherwise fall back to auto-populated content.
            $featuredServiceIds = self::settingArray('home_featured_services');
            $featuredDoctorIds = self::settingArray('home_featured_doctors');
            $featuredReviews = self::settingArray('home_featured_reviews');

            $services = $featuredServiceIds !== []
                ? self::postsByIds($featuredServiceIds, 'layanan', fn (\WP_Post $p) => $contentRepository->normalizeServicePublic($p))
                : $contentRepository->featuredServices(8);

            $doctors = $featuredDoctorIds !== []
                ? self::postsByIds($featuredDoctorIds, 'dokter', fn (\WP_Post $p) => $doctorRepository->normalize($p))
                : $doctorRepository->list(['per_page' => 6]);

            $reviews = $featuredReviews !== []
                ? $featuredReviews
                : $reviewRepository->homeReviews(12);

            $context['home'] = [
                'doctors' => $doctors,
                'services' => $services,
                'polyclinics' => $contentRepository->polyclinics(8),
                'articles' => $contentRepository->latestArticles(6),
                'journals' => $contentRepository->latestJournals(4),
                'rooms' => $contentRepository->latestRooms(4),
                'reviews' => $reviews,
                'review_summary' => $reviewRepository->summary(),
                'schedule_summary' => $doctorScheduleRepository->summary(),
            ];
            $context['doctor_search'] = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rspku_doctor_search'),
                'filter_options' => $doctorRepository->filterOptions(),
            ];
        }

        if (is_post_type_archive('dokter')) {
            $filters = self::doctorFilters();
            $query = $doctorRepository->query($filters);

            $context['doctor_archive'] = [
                'doctors' => array_map(fn ($post): array => $doctorRepository->normalize($post), $query->posts),
                'query' => $query,
                'filters' => $filters,
                'filter_options' => $doctorRepository->filterOptions(),
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rspku_doctor_search'),
                'pagination' => self::pagination($query),
                'pagination_meta' => self::paginationMeta($query, 'dokter'),
            ];
        }

        if (is_post_type_archive('poliklinik')) {
            $allPolyclinics = $contentRepository->allPolyclinics();
            $archiveItems = $contentRepository->polyclinicItems($GLOBALS['wp_query']->posts ?? []);

            $context['polyclinic_archive'] = [
                'items' => $archiveItems,
                'lead' => self::firstWithImage($allPolyclinics) ?? ($allPolyclinics[0] ?? null),
                'groups' => $contentRepository->polyclinicGroups($allPolyclinics),
                'total' => count($allPolyclinics),
            ];
        }

        if (is_singular('dokter')) {
            $context['doctor'] = $doctorRepository->find((int) get_queried_object_id());
        }

        if (is_singular('poliklinik')) {
            $polyclinicId = (int) get_queried_object_id();
            $context['polyclinic'] = $contentRepository->findPolyclinic($polyclinicId);
            $context['polyclinic_navigation'] = $contentRepository->polyclinicGroups();
            $context['polyclinic_doctors'] = $doctorRepository->forPolyclinic($polyclinicId, 4);
        }

        if (is_singular('layanan')) {
            $serviceId = (int) get_queried_object_id();
            $context['service_single'] = $contentRepository->findService($serviceId);
            $context['service_related'] = $contentRepository->relatedServices($serviceId, 3);
            $context['service_doctors'] = $doctorRepository->forService($serviceId, 4);
        }

        if (is_singular('rawat-inap')) {
            $roomId = (int) get_queried_object_id();
            $context['room_single'] = $contentRepository->findRoom($roomId);
            $context['room_related'] = $contentRepository->relatedRooms($roomId, 3);
        }

        if (is_singular('manajemen-rs')) {
            $context['management_single'] = $contentRepository->findManagement((int) get_queried_object_id());
        }

        if (is_singular()) {
            $context['post'] = class_exists(Timber::class) ? Timber::get_post() : get_post();
        }

        if (is_singular('post')) {
            $context['article_single'] = self::articleSingleContext((int) get_queried_object_id(), $contentRepository);
        }

        if (is_page()) {
            $slug = (string) get_post_field('post_name', (int) get_queried_object_id());

            if ($slug === 'jadwal-dokter') {
                $context['schedule_page'] = [
                    'rows' => $doctorScheduleRepository->records(),
                    'day_headers' => $doctorScheduleRepository->dayHeaders(),
                    'specialization_groups' => $doctorScheduleRepository->specializationGroups(),
                    'summary' => $doctorScheduleRepository->summary(),
                ];
            }

            if ($slug === 'berita-artikel') {
                $articlePage = $contentRepository->paginatedArticles(self::pageNumber(), 9);
                $articles = is_array($articlePage['items'] ?? null) ? $articlePage['items'] : [];

                $context['article_landing'] = [
                    'items' => $articles,
                    'featured' => self::firstWithImage($articles) ?? ($articles[0] ?? null),
                    'title' => __('Berita & Artikel', 'rspku-theme'),
                    'description' => __('Beragam berita, edukasi kesehatan, dan informasi terbaru RS PKU Muhammadiyah Yogyakarta dalam satu halaman yang lebih mudah dipindai.', 'rspku-theme'),
                    'search_action' => home_url('/'),
                    'total' => (int) ($articlePage['total'] ?? self::publishCount('post')),
                    'total_pages' => (int) ($articlePage['total_pages'] ?? 1),
                    'current_page' => (int) ($articlePage['current_page'] ?? 1),
                    'per_page' => (int) ($articlePage['per_page'] ?? 9),
                    'pagination' => $articlePage['query'] instanceof WP_Query
                        ? self::paginationForQuery($articlePage['query'], get_permalink((int) get_queried_object_id()))
                        : '',
                ];
            }

            if ($slug === 'e-jurnal') {
                $journals = $contentRepository->latestJournals(9);

                $context['journal_landing'] = [
                    'items' => $journals,
                    'featured' => $journals[0] ?? null,
                    'title' => __('E-Jurnal', 'rspku-theme'),
                    'description' => __('Publikasi kesehatan, pembelajaran klinis, dan inovasi layanan medis RS PKU Muhammadiyah Yogyakarta yang disusun lebih jelas dan rapi.', 'rspku-theme'),
                    'total' => self::publishCount('jurnal'),
                    'documents' => count(
                        array_filter(
                            $journals,
                            static fn (array $journal): bool => isset($journal['file']['url']) && (string) $journal['file']['url'] !== ''
                        )
                    ),
                ];
            }

            if ($slug === 'fasilitas-rawat-inap') {
                $roomPage = $contentRepository->paginatedRooms(self::pageNumber(), 9);
                $rooms = is_array($roomPage['items'] ?? null) ? $roomPage['items'] : [];

                $context['room_landing'] = [
                    'items' => $rooms,
                    'featured' => self::firstWithImage($rooms) ?? ($rooms[0] ?? null),
                    'title' => __('Fasilitas Rawat Inap', 'rspku-theme'),
                    'description' => __('Pilihan kamar dan fasilitas perawatan yang disusun lebih proporsional agar keluarga pasien dapat membandingkan opsi layanan dengan cepat.', 'rspku-theme'),
                    'total' => (int) ($roomPage['total'] ?? self::publishCount('rawat-inap')),
                    'total_pages' => (int) ($roomPage['total_pages'] ?? 1),
                    'current_page' => (int) ($roomPage['current_page'] ?? 1),
                    'per_page' => (int) ($roomPage['per_page'] ?? 9),
                    'pagination' => $roomPage['query'] instanceof WP_Query
                        ? self::paginationForQuery($roomPage['query'], get_permalink((int) get_queried_object_id()))
                        : '',
                ];
            }

            if ($slug === 'kontak') {
                $context['contact_page'] = self::contactPageContext();
            }

            if ($slug === 'sejarah-kami') {
                $context['history_page'] = self::historyPageContext();
            }
        }

        if (is_post_type_archive('jurnal')) {
            $allJournals = $contentRepository->allJournals();
            $archiveItems = $contentRepository->journalItems($GLOBALS['wp_query']->posts ?? []);

            $context['journal_archive'] = [
                'items' => $archiveItems,
                'featured' => $archiveItems[0] ?? ($allJournals[0] ?? null),
                'total' => count($allJournals),
                'documents' => count(
                    array_filter(
                        $allJournals,
                        static fn (array $journal): bool => isset($journal['file']['url']) && (string) $journal['file']['url'] !== ''
                    )
                ),
            ];
        }

        if (is_singular('jurnal')) {
            $context['journal'] = $contentRepository->findJournal((int) get_queried_object_id());
        }

        if (is_archive() && !is_post_type_archive('dokter')) {
            $context['posts'] = class_exists(Timber::class) ? Timber::get_posts() : [];
            $context['archive_title'] = get_the_archive_title();
            $context['archive_description'] = get_the_archive_description();
            $context['pagination'] = self::pagination($GLOBALS['wp_query']);
            $context['pagination_meta'] = self::paginationMeta($GLOBALS['wp_query'], self::archiveItemLabel());

            if (self::isArticleArchive()) {
                $context['article_archive'] = self::articleArchiveContext();
            }

            if (is_post_type_archive('layanan') || is_tax('kategori-layanan')) {
                $context['service_archive'] = self::serviceArchiveContext(
                    $contentRepository->serviceItems($GLOBALS['wp_query']->posts ?? [])
                );
            }

            if (is_post_type_archive('manajemen-rs')) {
                $context['posts'] = $contentRepository->managementItems($GLOBALS['wp_query']->posts ?? []);
                $context['management_archive'] = [
                    'title' => __('Manajemen RS', 'rspku-theme'),
                    'description' => __('Profil pimpinan dan jajaran manajemen RS PKU Muhammadiyah Yogyakarta yang disusun lebih rapi agar struktur kepemimpinan lebih mudah dipahami.', 'rspku-theme'),
                    'sections' => $contentRepository->managementSections($context['posts']),
                ];
            }
        }

        if (is_search()) {
            $context['posts'] = class_exists(Timber::class) ? Timber::get_posts() : [];
            $context['search_query'] = get_search_query();
            $context['pagination'] = self::pagination($GLOBALS['wp_query']);
            $context['pagination_meta'] = self::paginationMeta($GLOBALS['wp_query'], __('hasil', 'rspku-theme'));
            $context['search_view'] = self::searchViewContext();
        }

        return $context;
    }

    /**
     * @param array<string,mixed> $context
     * @return array<int,string>
     */
    private static function templates(array $context): array
    {
        if (is_front_page()) {
            return ['pages/front-page.twig', 'pages/page.twig'];
        }

        if (is_singular('dokter')) {
            return ['pages/single-doctor.twig', 'pages/single.twig'];
        }

        if (is_post_type_archive('dokter')) {
            return ['pages/archive-doctor.twig', 'pages/archive.twig'];
        }

        if (is_page()) {
            $slug = (string) get_post_field('post_name', (int) get_queried_object_id());

            return [
                'pages/page-' . $slug . '.twig',
                'pages/page.twig',
                'pages/single.twig',
            ];
        }

        if (is_singular()) {
            $postType = get_post_type();
            return [
                'pages/single-' . (string) $postType . '.twig',
                'pages/single.twig',
            ];
        }

        if (is_tax('kategori-layanan')) {
            return ['pages/archive-layanan.twig', 'pages/archive.twig'];
        }

        if (is_search()) {
            return ['pages/search.twig', 'pages/archive.twig'];
        }

        if (is_archive()) {
            $postType = get_post_type();
            if (is_string($postType)) {
                return [
                    'pages/archive-' . $postType . '.twig',
                    'pages/archive.twig',
                ];
            }

            return ['pages/archive.twig'];
        }

        if (is_404()) {
            return ['pages/404.twig'];
        }

        return ['pages/page.twig', 'pages/single.twig'];
    }

    /**
     * @return array<string,mixed>
     */
    private static function doctorFilters(): array
    {
        return [
            'q' => sanitize_text_field(wp_unslash($_GET['q'] ?? '')),
            'specialization' => sanitize_title(wp_unslash($_GET['specialization'] ?? '')),
            'day' => sanitize_key(wp_unslash($_GET['day'] ?? '')),
            'service' => absint($_GET['service'] ?? 0),
            'page' => max(1, (int) get_query_var('paged')),
            'per_page' => 12,
        ];
    }

    private static function pagination(WP_Query $query): string
    {
        return self::paginationForQuery($query);
    }

    private static function paginationForQuery(WP_Query $query, string $baseUrl = ''): string
    {
        $args = [
            'total' => max(1, (int) $query->max_num_pages),
            'current' => self::pageNumber(),
            'type' => 'list',
            'prev_text' => __('Sebelumnya', 'rspku-theme'),
            'next_text' => __('Berikutnya', 'rspku-theme'),
        ];

        if ($baseUrl !== '') {
            $args['base'] = trailingslashit($baseUrl) . '%_%';
            $args['format'] = 'page/%#%/';
        }

        $links = paginate_links($args);

        return is_string($links) ? $links : '';
    }

    private static function pageNumber(): int
    {
        return max(
            1,
            (int) get_query_var('paged'),
            (int) get_query_var('page'),
            absint($_GET['paged'] ?? 0)
        );
    }

    /**
     * @return array<string,mixed>
     */
    private static function paginationMeta(WP_Query $query, string $itemLabel = 'item'): array
    {
        $perPage = (int) $query->get('posts_per_page');
        if ($perPage <= 0) {
            $perPage = (int) get_option('posts_per_page');
        }

        return [
            'total' => (int) $query->found_posts,
            'per_page' => max(1, $perPage),
            'current_page' => self::pageNumber(),
            'total_pages' => max(1, (int) $query->max_num_pages),
            'item_label' => $itemLabel,
        ];
    }

    private static function archiveItemLabel(): string
    {
        if (self::isArticleArchive()) {
            return __('artikel', 'rspku-theme');
        }

        if (is_post_type_archive('jurnal')) {
            return __('jurnal', 'rspku-theme');
        }

        if (is_post_type_archive('poliklinik')) {
            return __('poliklinik', 'rspku-theme');
        }

        if (is_post_type_archive('layanan') || is_tax('kategori-layanan')) {
            return __('layanan', 'rspku-theme');
        }

        if (is_post_type_archive('manajemen-rs')) {
            return __('profil', 'rspku-theme');
        }

        return __('item', 'rspku-theme');
    }

    private static function isArticleArchive(): bool
    {
        if (is_category() || is_tag() || is_date() || is_author()) {
            return true;
        }

        return get_post_type() === 'post';
    }

    /**
     * @return array<string,mixed>
     */
    private static function articleArchiveContext(): array
    {
        $description = trim(wp_strip_all_tags((string) get_the_archive_description()));
        $queriedObject = get_queried_object();
        $categorySlug = '';

        if ($queriedObject instanceof WP_Term && $queriedObject->taxonomy === 'category') {
            $categorySlug = $queriedObject->slug;
        }

        if ($description === '') {
            $description = self::defaultArchiveDescription($queriedObject);
        }

        return [
            'eyebrow' => __('Berita dan artikel', 'rspku-theme'),
            'title' => get_the_archive_title(),
            'description' => $description,
            'search_action' => home_url('/'),
            'search_query' => get_search_query(),
            'search_category_slug' => $categorySlug,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function searchViewContext(): array
    {
        $postTypeQuery = get_query_var('post_type');
        $postType = is_array($postTypeQuery)
            ? sanitize_key((string) ($postTypeQuery[0] ?? ''))
            : sanitize_key((string) $postTypeQuery);
        $categorySlug = sanitize_title((string) get_query_var('category_name'));
        $isArticleSearch = $postType === 'post' || $categorySlug !== '';

        return [
            'eyebrow' => $isArticleSearch ? __('Berita dan artikel', 'rspku-theme') : __('Pencarian', 'rspku-theme'),
            /* translators: %s: user's search query */
            'title' => sprintf(__('Hasil pencarian untuk "%s"', 'rspku-theme'), get_search_query()),
            'description' => $isArticleSearch
                ? __('Temukan berita, edukasi kesehatan, dan informasi terbaru RS PKU Muhammadiyah Yogyakarta dengan lebih cepat.', 'rspku-theme')
                : __('Gunakan kata kunci yang lebih spesifik untuk menemukan konten yang Anda butuhkan.', 'rspku-theme'),
            'search_action' => home_url('/'),
            'post_type' => $postType,
            'category_slug' => $categorySlug,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function articleSingleContext(int $postId, ContentRepository $contentRepository): array
    {
        $terms = get_the_category($postId);
        $categories = array_map(
            static fn (WP_Term $term): array => [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'url' => is_wp_error(get_term_link($term)) ? '' : (string) get_term_link($term),
            ],
            $terms
        );

        $categoryIds = array_map(static fn (WP_Term $term): int => (int) $term->term_id, $terms);

        $post = get_post($postId);
        $rendered = $post instanceof \WP_Post ? apply_filters('the_content', $post->post_content) : '';
        $toc = is_string($rendered) && $rendered !== ''
            ? \Rspku\Helpers\TocGenerator::fromHtml($rendered)
            : ['html' => '', 'items' => []];

        $contentWithAnchors = (string) ($toc['html'] ?? '');
        if ($contentWithAnchors === '') {
            $contentWithAnchors = is_string($rendered) ? $rendered : '';
        }

        $tocItems = is_array($toc['items'] ?? null) ? $toc['items'] : [];
        $tocFlat = \Rspku\Helpers\TocGenerator::flatten($tocItems);

        return [
            'categories' => $categories,
            'primary_category' => $categories[0] ?? null,
            'popular_articles' => $contentRepository->popularArticles(5, $postId),
            'related_articles' => $contentRepository->relatedArticles($postId, 3, $categoryIds),
            'content_with_anchors' => $contentWithAnchors,
            'toc' => [
                'items' => $tocItems,
                'flat' => $tocFlat,
                'has_toc' => count($tocFlat) >= 2,
            ],
            'cta' => $post instanceof \WP_Post
                ? \Rspku\Services\ArticleCtaMapper::build($post)
                : null,
            'reading_time' => $post instanceof \WP_Post
                ? \Rspku\Helpers\ReadingTime::calculate($post->post_content)
                : 0,
        ];
    }

    private static function defaultArchiveDescription(object|null $queriedObject): string
    {
        if ($queriedObject instanceof WP_Term) {
            return sprintf(
                /* translators: %s: topic name */
                __('Kumpulan berita, edukasi kesehatan, dan informasi RS PKU Muhammadiyah Yogyakarta dalam topik %s.', 'rspku-theme'),
                $queriedObject->name
            );
        }

        if ($queriedObject instanceof WP_Post_Type) {
            return sprintf(
                /* translators: %s: content type plural label */
                __('Jelajahi berbagai konten %s yang tersusun rapi dan lebih mudah dipindai.', 'rspku-theme'),
                $queriedObject->labels->name
            );
        }

        return __('Beragam berita, edukasi kesehatan, dan informasi terbaru RS PKU Muhammadiyah Yogyakarta.', 'rspku-theme');
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<string,mixed>
     */
    private static function serviceArchiveContext(array $items): array
    {
        $queriedObject = get_queried_object();
        $description = trim(wp_strip_all_tags((string) get_the_archive_description()));
        $query = $GLOBALS['wp_query'];
        $total = $queriedObject instanceof WP_Term
            ? (int) $queriedObject->count
            : self::publishCount('layanan');

        if ($description === '') {
            $description = $queriedObject instanceof WP_Term
                ? sprintf(
                    /* translators: %s: service category name (lowercase) */
                    __('Pilihan layanan %s RS PKU Muhammadiyah Yogyakarta yang disusun agar pasien dan keluarga lebih cepat menemukan kebutuhan perawatan yang tepat.', 'rspku-theme'),
                    strtolower($queriedObject->name)
                )
                : __('Kumpulan layanan medis RS PKU Muhammadiyah Yogyakarta yang disusun lebih rapi dan mudah dipahami.', 'rspku-theme');
        }

        return [
            'title' => $queriedObject instanceof WP_Term ? $queriedObject->name : __('Layanan Medis', 'rspku-theme'),
            'description' => $description,
            'eyebrow' => self::serviceEyebrow($queriedObject),
            'items' => $items,
            'featured' => self::firstWithImage($items) ?? ($items[0] ?? null),
            'total' => $total,
            'current_page' => self::pageNumber(),
            'total_pages' => max(1, (int) $query->max_num_pages),
            'per_page' => max(1, (int) $query->get('posts_per_page')),
            'breadcrumb_parent' => [
                'title' => __('Semua layanan medis', 'rspku-theme'),
                'url' => home_url('/layanan/'),
            ],
        ];
    }

    private static function serviceEyebrow(object|null $queriedObject): string
    {
        if (!$queriedObject instanceof WP_Term) {
            return __('Layanan medis', 'rspku-theme');
        }

        return match ($queriedObject->slug) {
            'layanan-unggulan' => __('Layanan unggulan', 'rspku-theme'),
            'layanan-penunjang' => __('Layanan penunjang', 'rspku-theme'),
            default => __('Layanan medis', 'rspku-theme'),
        };
    }

    /**
     * @return array<string,mixed>
     */
    private static function contactPageContext(): array
    {
        // Read from plugin settings if available
        $s = function_exists('rspku_setting') ? fn ($k) => rspku_setting($k) : fn ($k) => null;

        $serviceHoursRaw = $s('service_hours') ?: [];
        $serviceHours = [];
        foreach ($serviceHoursRaw as $row) {
            if (is_array($row)) {
                $serviceHours[] = [
                    'label' => (string) ($row['label'] ?? ''),
                    'time' => (string) ($row['time'] ?? ''),
                    'highlight' => !empty($row['highlight']),
                ];
            }
        }

        $socials = [];
        if ($s('social_instagram')) {
            $socials[] = ['name' => 'Instagram', 'handle' => $s('social_instagram_handle') ?: '@rspkujogja', 'url' => $s('social_instagram'), 'icon' => 'instagram'];
        }
        if ($s('social_facebook')) {
            $socials[] = ['name' => 'Facebook', 'handle' => $s('social_facebook_handle') ?: 'Facebook Page', 'url' => $s('social_facebook'), 'icon' => 'facebook'];
        }
        if ($s('social_youtube')) {
            $socials[] = ['name' => 'YouTube', 'handle' => $s('social_youtube_handle') ?: 'YouTube Channel', 'url' => $s('social_youtube'), 'icon' => 'circle-play'];
        }

        $phoneIgd = $s('phone_igd') ?: '0274 512653';
        $phoneIgdLink = $s('phone_igd_link') ?: '+62274512653';
        $phoneMain = $s('phone_main') ?: '+62 8886412345';
        $phoneMainLink = $s('phone_main_link') ?: '+628886412345';
        $whatsapp = $s('whatsapp') ?: '0274 566129';
        $whatsappLink = $s('whatsapp_link') ?: '+62274566129';
        $email = $s('email') ?: 'info@rspkujogja.com';

        return [
            'eyebrow' => __('Informasi Kontak', 'rspku-theme'),
            'title' => __('Kami Siap Membantu Anda', 'rspku-theme'),
            'description' => __('Tim RS PKU Muhammadiyah Yogyakarta hadir untuk membantu kebutuhan informasi layanan, pendaftaran, dan kontak penting rumah sakit.', 'rspku-theme'),
            'map_embed_url' => $s('google_maps_embed_url') ?: 'https://maps.google.com/maps?q=Jl.%20KH.%20Ahmad%20Dahlan%20No.20%2C%20Yogyakarta&t=m&z=14&output=embed',
            'map_link' => $s('google_maps_link') ?: 'https://maps.app.goo.gl/RSPKUJogja',
            'emergency' => [
                'icon' => 'siren',
                'label' => __('Pusat Panggilan', 'rspku-theme'),
                'title' => __('Call Center 1', 'rspku-theme'),
                'description' => __('Hubungi pusat panggilan RS PKU Muhammadiyah Yogyakarta untuk informasi layanan umum.', 'rspku-theme'),
                'phone' => $phoneIgdLink,
                'phone_display' => $phoneIgd,
            ],
            'quick_contacts' => array_filter([
                [
                    'icon' => 'phone',
                    'title' => __('Call Center 1', 'rspku-theme'),
                    'value' => $phoneIgd,
                    'url' => 'tel:' . $phoneIgdLink,
                    'description' => __('Pusat panggilan', 'rspku-theme'),
                ],
                [
                    'icon' => 'phone',
                    'title' => __('Call Center 2', 'rspku-theme'),
                    'value' => $phoneMain,
                    'url' => 'tel:' . $phoneMainLink,
                    'description' => __('Pusat panggilan', 'rspku-theme'),
                ],
                $whatsapp ? [
                    'icon' => 'phone',
                    'title' => __('Call Center 3', 'rspku-theme'),
                    'value' => $whatsapp,
                    'url' => 'tel:' . $whatsappLink,
                    'description' => __('Pusat panggilan', 'rspku-theme'),
                ] : null,
                [
                    'icon' => 'mail',
                    'title' => __('Email', 'rspku-theme'),
                    'value' => $email,
                    'url' => 'mailto:' . $email,
                    'description' => __('Pertanyaan umum & layanan', 'rspku-theme'),
                ],
            ]),
            'address' => [
                'street' => $s('address_street') ?: 'Jl. KH. Ahmad Dahlan No.20',
                'district' => $s('address_district') ?: 'Ngupasan, Kec. Gondomanan',
                'city' => $s('address_city') ?: 'Kota Yogyakarta',
                'province' => $s('address_province') ?: 'Daerah Istimewa Yogyakarta 55122',
            ],
            'service_hours' => $serviceHours,
            'socials' => $socials,
            'departments' => [
                ['icon' => 'stethoscope', 'name' => __('Customer Service', 'rspku-theme'), 'phone' => $phoneMain, 'ext' => 'Ext. 100'],
                ['icon' => 'heart', 'name' => __('Humas & Layanan Pelanggan', 'rspku-theme'), 'phone' => $phoneMain, 'ext' => 'Ext. 200'],
                ['icon' => 'briefcase-medical', 'name' => __('Farmasi', 'rspku-theme'), 'phone' => $phoneMain, 'ext' => 'Ext. 300'],
                ['icon' => 'user-round', 'name' => __('Rekam Medis', 'rspku-theme'), 'phone' => $phoneMain, 'ext' => 'Ext. 400'],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function historyPageContext(): array
    {
        return [
            'eyebrow' => __('Perjalanan Kami', 'rspku-theme'),
            'title' => __('Sejarah RS PKU Muhammadiyah Yogyakarta', 'rspku-theme'),
            'description' => __('Perjalanan lebih dari 100 tahun pelayanan kesehatan umat yang berakar dari gerakan sosial, berkembang menjadi rumah sakit islami modern, dan tetap berpijak pada nilai dakwah serta kemanusiaan.', 'rspku-theme'),
            'gallery' => self::historyGalleryContext(),
            'stats' => [
                ['value' => '100+', 'label' => __('Tahun Melayani', 'rspku-theme')],
                ['value' => '75+', 'label' => __('Dokter Spesialis', 'rspku-theme')],
                ['value' => '31+', 'label' => __('Spesialisasi', 'rspku-theme')],
                ['value' => '24/7', 'label' => __('IGD Siaga', 'rspku-theme')],
            ],
            'milestones' => [
                [
                    'year' => '1923',
                    'label' => __('Berdiri', 'rspku-theme'),
                    'title' => __('Berawal dari PKO', 'rspku-theme'),
                    'body' => __('Layanan kesehatan dimulai dari klinik sederhana di Jagang Notoprajan No. 72 Yogyakarta untuk membantu masyarakat yang kesulitan mengakses pelayanan medis. Didirikan sebagai wujud gerakan sosial Muhammadiyah.', 'rspku-theme'),
                    'icon' => 'heart-pulse',
                ],
                [
                    'year' => '1928',
                    'label' => __('Pindah ke Ngabean', 'rspku-theme'),
                    'title' => __('Transformasi menjadi PKU', 'rspku-theme'),
                    'body' => __('PKO (Pertolongan Kesengsaraan Oemoem) berkembang menjadi PKU (Pembina Kesejahteraan Umat) dan berpindah ke Ngabean untuk memperluas jangkauan pelayanan.', 'rspku-theme'),
                    'icon' => 'building-2',
                ],
                [
                    'year' => '1936',
                    'label' => __('Lokasi Permanen', 'rspku-theme'),
                    'title' => __('Menempati Jl. KH Ahmad Dahlan', 'rspku-theme'),
                    'body' => __('Menempati lokasi permanen di Jalan K.H. Ahmad Dahlan No. 20 Yogyakarta, yang kini menjadi lokasi utama rumah sakit dan saksi perjalanan pelayanan kesehatan umat.', 'rspku-theme'),
                    'icon' => 'map-pin',
                ],
                [
                    'year' => '1970',
                    'label' => __('Naik Status', 'rspku-theme'),
                    'title' => __('Menjadi Rumah Sakit', 'rspku-theme'),
                    'body' => __('Status rumah sakit menandai fase baru: pelayanan yang lebih terstruktur, profesional, dan semakin berorientasi pada mutu serta keselamatan pasien.', 'rspku-theme'),
                    'icon' => 'hospital',
                ],
                [
                    'year' => '1998',
                    'label' => __('Penguatan', 'rspku-theme'),
                    'title' => __('Modernisasi Tata Kelola', 'rspku-theme'),
                    'body' => __('Memasuki era baru dengan penguatan tata kelola, standar layanan, pendidikan klinis, dan integrasi teknologi kesehatan modern.', 'rspku-theme'),
                    'icon' => 'shield-check',
                ],
                [
                    'year' => '2024',
                    'label' => __('PKU Reborn', 'rspku-theme'),
                    'title' => __('Era Digital & Inovasi', 'rspku-theme'),
                    'body' => __('Transformasi digital dan inovasi berkelanjutan dalam pelayanan, pendidikan kedokteran, penelitian, dan dakwah kesehatan untuk generasi mendatang.', 'rspku-theme'),
                    'icon' => 'sparkles',
                ],
            ],
            'principles' => [
                [
                    'title' => __('Falsafah Pelayanan', 'rspku-theme'),
                    'icon' => 'heart',
                    'body' => __('Pelayanan kesehatan dipandang sebagai bagian dari dakwah Islam amar ma\'ruf nahi munkar, dengan keselamatan pasien, mutu layanan, dan nilai kemanusiaan sebagai landasan utama.', 'rspku-theme'),
                ],
                [
                    'title' => __('Visi', 'rspku-theme'),
                    'icon' => 'eye',
                    'body' => __('Menjadi rumah sakit yang Islami dan unggul dalam pelayanan, pendidikan, penelitian, dan dakwah di bidang kesehatan.', 'rspku-theme'),
                ],
                [
                    'title' => __('Misi', 'rspku-theme'),
                    'icon' => 'target',
                    'items' => [
                        __('Menyelenggarakan pelayanan kesehatan berbasis standar terkini dan bukti ilmiah.', 'rspku-theme'),
                        __('Mengembangkan sumber daya insani melalui pendidikan, pelatihan, penelitian, dan pemanfaatan teknologi kesehatan.', 'rspku-theme'),
                        __('Melaksanakan dakwah Islam dalam setiap aspek pelayanan dan membangun sinergi untuk masyarakat yang sehat dan sejahtera.', 'rspku-theme'),
                    ],
                ],
            ],
            'values' => [
                ['letter' => 'A', 'name' => __('Amanah', 'rspku-theme'), 'desc' => __('Jujur, bertanggung jawab, dan dapat dipercaya dalam setiap pelayanan.', 'rspku-theme')],
                ['letter' => 'L', 'name' => __('Lengkap', 'rspku-theme'), 'desc' => __('Menghadirkan layanan kesehatan secara komprehensif dan terintegrasi.', 'rspku-theme')],
                ['letter' => 'M', 'name' => __('Mutu', 'rspku-theme'), 'desc' => __('Menjunjung standar pelayanan terkini serta nilai syariah Islamiyah.', 'rspku-theme')],
                ['letter' => 'A', 'name' => __('Antusias', 'rspku-theme'), 'desc' => __('Melayani dengan cepat, tepat, dan sepenuh hati tanpa mengenal lelah.', 'rspku-theme')],
                ['letter' => 'U', 'name' => __('Universal', 'rspku-theme'), 'desc' => __('Terbuka dan ramah untuk seluruh lapisan masyarakat tanpa terkecuali.', 'rspku-theme')],
                ['letter' => 'N', 'name' => __('Nyaman', 'rspku-theme'), 'desc' => __('Menciptakan pengalaman layanan yang tenang dan menenteramkan.', 'rspku-theme')],
            ],
        ];
    }

    private static function historyGalleryContext(): array
    {
        if (!function_exists('rspku_setting')) {
            return [];
        }

        $gallery = [];
        $slots = [
            'history_hero',
            'history_pioneers',
            'history_child_service',
            'history_first_stone',
            'history_modernization',
        ];

        foreach ($slots as $slot) {
            $imageId = absint(rspku_setting($slot . '_image_id', 0));
            $year = trim((string) rspku_setting($slot . '_year', ''));
            $title = trim((string) rspku_setting($slot . '_title', ''));
            $caption = trim((string) rspku_setting($slot . '_caption', ''));
            $alt = trim((string) rspku_setting($slot . '_alt', ''));

            if ($imageId < 1 || !wp_attachment_is_image($imageId) || $year === '' || $title === '' || $caption === '' || $alt === '') {
                continue;
            }

            $gallery[] = [
                'key' => $slot,
                'image_id' => $imageId,
                'image_url' => wp_get_attachment_image_url($imageId, 'rspku-hero') ?: '',
                'year' => $year,
                'title' => $title,
                'caption' => $caption,
                'alt' => $alt,
            ];
        }

        return $gallery;
    }

    private static function publishCount(string $postType): int
    {
        $counts = wp_count_posts($postType);

        return $counts && isset($counts->publish) ? (int) $counts->publish : 0;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<string,mixed>|null
     */
    private static function firstWithImage(array $items): ?array
    {
        foreach ($items as $item) {
            if (isset($item['image']['url']) && (string) $item['image']['url'] !== '') {
                return $item;
            }
        }

        return null;
    }

    /**
     * Read a setting key that should be an array (post IDs or review objects).
     * Returns empty array if the setting is not set or not an array.
     *
     * @return array<int,mixed>
     */
    private static function settingArray(string $key): array
    {
        if (!function_exists('rspku_setting')) {
            return [];
        }

        $value = rspku_setting($key, []);

        return is_array($value) ? $value : [];
    }

    /**
     * Load posts by an ordered list of IDs and normalize each one.
     * Preserves the admin-defined order (not WP default ordering).
     *
     * @param array<int,int> $ids
     * @return array<int,array<string,mixed>>
     */
    private static function postsByIds(array $ids, string $postType, callable $normalizer): array
    {
        $ids = array_values(array_filter(array_map('absint', $ids)));
        if ($ids === []) {
            return [];
        }

        $posts = get_posts([
            'post_type' => $postType,
            'post_status' => 'publish',
            'post__in' => $ids,
            'orderby' => 'post__in',
            'posts_per_page' => count($ids),
            'no_found_rows' => true,
        ]);

        return array_map($normalizer, $posts);
    }
}
