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
            $context['home'] = [
                'doctors' => $doctorRepository->list(['per_page' => 6]),
                'services' => $contentRepository->featuredServices(8),
                'polyclinics' => $contentRepository->polyclinics(8),
                'articles' => $contentRepository->latestArticles(6),
                'journals' => $contentRepository->latestJournals(4),
                'rooms' => $contentRepository->latestRooms(4),
                'reviews' => $reviewRepository->homeReviews(12),
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

            if ($context['polyclinic_doctors'] === []) {
                $context['polyclinic_doctors'] = $doctorRepository->featured(4);
            }
        }

        if (is_singular('layanan')) {
            $serviceId = (int) get_queried_object_id();
            $context['service_single'] = $contentRepository->findService($serviceId);
            $context['service_related'] = $contentRepository->relatedServices($serviceId, 3);
            $context['service_doctors'] = $doctorRepository->forService($serviceId, 4);

            if ($context['service_doctors'] === []) {
                $context['service_doctors'] = $doctorRepository->featured(4);
            }
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
                    'title' => 'Berita & Artikel',
                    'description' => 'Beragam berita, edukasi kesehatan, dan informasi terbaru RS PKU Muhammadiyah Yogyakarta dalam satu halaman yang lebih mudah dipindai.',
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
                    'title' => 'E-Jurnal',
                    'description' => 'Publikasi kesehatan, pembelajaran klinis, dan inovasi layanan medis RS PKU Muhammadiyah Yogyakarta yang disusun lebih jelas dan rapi.',
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
                    'title' => 'Fasilitas Rawat Inap',
                    'description' => 'Pilihan kamar dan fasilitas perawatan yang disusun lebih proporsional agar keluarga pasien dapat membandingkan opsi layanan dengan cepat.',
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
                $context['management_archive'] = [
                    'title' => 'Manajemen RS',
                    'description' => 'Profil pimpinan dan jajaran manajemen RS PKU Muhammadiyah Yogyakarta yang disusun lebih rapi agar struktur kepemimpinan lebih mudah dipahami.',
                ];
            }
        }

        if (is_search()) {
            $context['posts'] = class_exists(Timber::class) ? Timber::get_posts() : [];
            $context['search_query'] = get_search_query();
            $context['pagination'] = self::pagination($GLOBALS['wp_query']);
            $context['pagination_meta'] = self::paginationMeta($GLOBALS['wp_query'], 'hasil');
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
            return 'artikel';
        }

        if (is_post_type_archive('jurnal')) {
            return 'jurnal';
        }

        if (is_post_type_archive('poliklinik')) {
            return 'poliklinik';
        }

        if (is_post_type_archive('layanan') || is_tax('kategori-layanan')) {
            return 'layanan';
        }

        if (is_post_type_archive('manajemen-rs')) {
            return 'profil';
        }

        return 'item';
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
            'eyebrow' => 'Berita dan artikel',
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
            'eyebrow' => $isArticleSearch ? 'Berita dan artikel' : 'Pencarian',
            'title' => sprintf('Hasil pencarian untuk "%s"', get_search_query()),
            'description' => $isArticleSearch
                ? 'Temukan berita, edukasi kesehatan, dan informasi terbaru RS PKU Muhammadiyah Yogyakarta dengan lebih cepat.'
                : 'Gunakan kata kunci yang lebih spesifik untuk menemukan konten yang Anda butuhkan.',
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

        return [
            'categories' => $categories,
            'primary_category' => $categories[0] ?? null,
            'popular_articles' => $contentRepository->popularArticles(5, $postId),
            'related_articles' => $contentRepository->relatedArticles($postId, 3, $categoryIds),
        ];
    }

    private static function defaultArchiveDescription(object|null $queriedObject): string
    {
        if ($queriedObject instanceof WP_Term) {
            return sprintf(
                'Kumpulan berita, edukasi kesehatan, dan informasi RS PKU Muhammadiyah Yogyakarta dalam topik %s.',
                $queriedObject->name
            );
        }

        if ($queriedObject instanceof WP_Post_Type) {
            return sprintf(
                'Jelajahi berbagai konten %s yang tersusun rapi dan lebih mudah dipindai.',
                $queriedObject->labels->name
            );
        }

        return 'Beragam berita, edukasi kesehatan, dan informasi terbaru RS PKU Muhammadiyah Yogyakarta.';
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
                    'Pilihan layanan %s RS PKU Muhammadiyah Yogyakarta yang disusun agar pasien dan keluarga lebih cepat menemukan kebutuhan perawatan yang tepat.',
                    strtolower($queriedObject->name)
                )
                : 'Kumpulan layanan medis RS PKU Muhammadiyah Yogyakarta yang disusun lebih rapi dan mudah dipahami.';
        }

        return [
            'title' => $queriedObject instanceof WP_Term ? $queriedObject->name : 'Layanan Medis',
            'description' => $description,
            'eyebrow' => self::serviceEyebrow($queriedObject),
            'items' => $items,
            'featured' => self::firstWithImage($items) ?? ($items[0] ?? null),
            'total' => $total,
            'current_page' => self::pageNumber(),
            'total_pages' => max(1, (int) $query->max_num_pages),
            'per_page' => max(1, (int) $query->get('posts_per_page')),
            'breadcrumb_parent' => [
                'title' => 'Semua layanan medis',
                'url' => home_url('/layanan/'),
            ],
        ];
    }

    private static function serviceEyebrow(object|null $queriedObject): string
    {
        if (!$queriedObject instanceof WP_Term) {
            return 'Layanan medis';
        }

        return match ($queriedObject->slug) {
            'layanan-unggulan' => 'Layanan unggulan',
            'layanan-penunjang' => 'Layanan penunjang',
            default => 'Layanan medis',
        };
    }

    /**
     * @return array<string,mixed>
     */
    private static function contactPageContext(): array
    {
        return [
            'eyebrow' => 'Informasi Kontak',
            'title' => 'Kami Siap Membantu Anda',
            'description' => 'Tim RS PKU Muhammadiyah Yogyakarta hadir untuk membantu kebutuhan informasi layanan, pendaftaran, dan kontak penting rumah sakit.',
            'map_embed_url' => 'https://maps.google.com/maps?q=Jl.%20KH.%20Ahmad%20Dahlan%20No.20%2C%20Ngupasan%2C%20Kec.%20Gondomanan%2C%20Kota%20Yogyakarta%2C%20Daerah%20Istimewa%20Yogyakarta%2055122&t=m&z=14&output=embed&iwloc=near',
            'cards' => [
                [
                    'icon' => 'map-pin',
                    'title' => 'Alamat',
                    'body' => 'Jl. KH. Ahmad Dahlan No.20, Ngupasan, Kec. Gondomanan, Kota Yogyakarta, Daerah Istimewa Yogyakarta 55122',
                ],
                [
                    'icon' => 'phone',
                    'title' => 'Pusat Panggilan',
                    'lines' => ['+62 274 512653', '+62 8886412345', '+62 274 566129'],
                ],
                [
                    'icon' => 'hospital',
                    'title' => 'IGD',
                    'lines' => ['0274 512653 - 118'],
                ],
                [
                    'icon' => 'briefcase',
                    'title' => 'Email',
                    'lines' => ['info@rspkudev.test'],
                ],
            ],
            'service_hours' => [
                'IGD: 24 Jam',
                'Rawat Jalan: 07.00 - 20.00 WIB',
                'Administrasi: 08.00 - 16.00 WIB',
                'Pendaftaran Online: 24 Jam',
            ],
            'socials' => [
                'Facebook: RS PKU Muhammadiyah Yogyakarta',
                'Instagram: @rspkujogja',
                'YouTube: RS PKU Yogyakarta',
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function historyPageContext(): array
    {
        return [
            'eyebrow' => 'Perjalanan Kami',
            'title' => 'Sejarah RS PKU Muhammadiyah Yogyakarta',
            'description' => 'Perjalanan panjang pelayanan kesehatan umat yang berakar dari gerakan sosial, berkembang menjadi rumah sakit islami modern, dan tetap berpijak pada nilai dakwah serta kemanusiaan.',
            'milestones' => [
                [
                    'label' => '1923',
                    'title' => 'Berawal dari PKO',
                    'body' => 'Layanan kesehatan ini dimulai dari klinik sederhana di Jagang Notoprajan No. 72 Yogyakarta untuk membantu masyarakat yang kesulitan mengakses pelayanan medis.',
                ],
                [
                    'label' => '1928-1936',
                    'title' => 'Bertumbuh di pusat kota',
                    'body' => 'PKO berkembang menjadi PKU, berpindah ke Ngabean, lalu menempati lokasi permanen di Jalan K.H. Ahmad Dahlan No. 20 Yogyakarta.',
                ],
                [
                    'label' => '1970-an',
                    'title' => 'Menjadi rumah sakit',
                    'body' => 'Status rumah sakit menandai fase baru: pelayanan yang lebih terstruktur, profesional, dan semakin berorientasi pada mutu.',
                ],
                [
                    'label' => '1998-sekarang',
                    'title' => 'Penguatan tata kelola dan PKU Reborn',
                    'body' => 'Rumah sakit terus memperkuat standar layanan, pendidikan, teknologi kesehatan, dan dakwah melalui pembaruan berkelanjutan.',
                ],
            ],
            'principles' => [
                [
                    'title' => 'Falsafah Pelayanan',
                    'body' => 'Pelayanan kesehatan dipandang sebagai bagian dari dakwah Islam amar ma\'ruf nahi munkar, dengan keselamatan pasien, mutu layanan, dan nilai kemanusiaan sebagai landasan utama.',
                ],
                [
                    'title' => 'Visi',
                    'body' => 'Menjadi rumah sakit yang Islami dan unggul dalam pelayanan, pendidikan, penelitian, dan dakwah di bidang kesehatan.',
                ],
                [
                    'title' => 'Misi',
                    'items' => [
                        'Menyelenggarakan pelayanan kesehatan berbasis standar terkini dan bukti ilmiah.',
                        'Mengembangkan sumber daya insani melalui pendidikan, pelatihan, penelitian, dan pemanfaatan teknologi kesehatan.',
                        'Melaksanakan dakwah Islam dalam setiap aspek pelayanan dan membangun sinergi untuk masyarakat yang sehat dan sejahtera.',
                    ],
                ],
            ],
            'values' => [
                'Amanah: jujur, bertanggung jawab, dan dapat dipercaya.',
                'Lengkap: menghadirkan layanan kesehatan secara komprehensif.',
                'Mutu: menjunjung standar pelayanan terkini serta nilai syariah Islamiyah.',
                'Antusias: melayani dengan cepat, tepat, dan sepenuh hati.',
                'Universal: terbuka untuk seluruh lapisan masyarakat.',
                'Nyaman: menciptakan pengalaman layanan yang tenang dan menenteramkan.',
            ],
        ];
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
}
