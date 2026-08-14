<?php

declare(strict_types=1);

namespace Rspku\Repositories;

use WP_Post;
use WP_Query;

final class DoctorRepository
{
    private const CACHE_GROUP = 'rspku_theme';
    private const CACHE_TTL = 6 * HOUR_IN_SECONDS;

    /**
     * Per-request memo so repeated normalize() calls on the same post
     * inside a single page render (archive + sidebar + schedule) don't
     * refetch meta/terms over and over.
     *
     * @var array<int,array<string,mixed>>
     */
    private static array $normalizeMemo = [];

    private const DAYS = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
        'sunday' => 'Minggu',
    ];

    /**
     * @param array<string,mixed> $filters
     */
    public function query(array $filters = []): WP_Query
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 12), 1), 50);
        $page = max((int) ($filters['page'] ?? 1), 1);

        $args = [
            'post_type' => 'dokter',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => false,
        ];

        $search = sanitize_text_field((string) ($filters['q'] ?? $filters['search'] ?? ''));
        if ($search !== '') {
            $args['s'] = $search;
        }

        $taxQuery = [];
        $specialization = sanitize_title((string) ($filters['specialization'] ?? ''));
        if ($specialization !== '') {
            $taxQuery[] = [
                'taxonomy' => 'spesialisasi-dokter',
                'field' => 'slug',
                'terms' => $specialization,
            ];
        }

        if ($taxQuery !== []) {
            $args['tax_query'] = count($taxQuery) > 1
                ? array_merge(['relation' => 'AND'], $taxQuery)
                : $taxQuery;
        }

        $metaQuery = [
            [
                'key' => '_rspku_synced_from_schedule',
                'value' => '1',
                'compare' => '=',
            ],
        ];
        $day = sanitize_key((string) ($filters['day'] ?? ''));
        if ($day !== '' && isset(self::DAYS[$day])) {
            $metaQuery[] = [
                'key' => '_rspku_schedule_day',
                'value' => $day,
                'compare' => '=',
            ];
        }

        $serviceId = absint($filters['service'] ?? 0);
        if ($serviceId > 0) {
            $metaQuery[] = [
                'key' => '_rspku_related_service',
                'value' => (string) $serviceId,
                'compare' => '=',
            ];
        }

        $args['meta_query'] = array_merge(['relation' => 'AND'], $metaQuery);

        return new WP_Query($args);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function list(array $filters = []): array
    {
        $query = $this->query($filters);

        return array_map(fn (WP_Post $post): array => $this->normalize($post), $query->posts);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function featured(int $limit = 4): array
    {
        return $this->list([
            'per_page' => $limit,
            'page' => 1,
        ]);
    }

    /**
     * Get doctors related to a polyclinic via explicit relation meta.
     *
     * @return array<int,array<string,mixed>>
     */
    public function forPolyclinic(int $polyclinicId, int $limit = 4): array
    {
        if ($polyclinicId <= 0) {
            return [];
        }

        $doctorIds = [];

        $direct = new WP_Query([
            'post_type' => 'dokter',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $limit),
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_rspku_synced_from_schedule',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => 'pilih_poliklinik_dokter',
                        'value' => (string) $polyclinicId,
                        'compare' => '=',
                    ],
                    [
                        'key' => 'pilih_poliklinik_dokter',
                        'value' => '"' . $polyclinicId . '"',
                        'compare' => 'LIKE',
                    ],
                    [
                        'key' => '_rspku_related_polyclinic',
                        'value' => (string) $polyclinicId,
                        'compare' => '=',
                    ],
                ],
            ],
        ]);

        foreach ($direct->posts as $id) {
            $doctorIds[(int) $id] = true;
        }

        if ($doctorIds === []) {
            return [];
        }

        $query = new WP_Query([
            'post_type' => 'dokter',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $limit),
            'post__in' => array_keys($doctorIds),
            'orderby' => 'post__in',
            'no_found_rows' => true,
        ]);

        return array_map(fn (WP_Post $post): array => $this->normalize($post), $query->posts);
    }

    /**
     * Get doctors related to a service via:
     * 1. _rspku_related_service meta (direct relation)
     * 2. Spesialisasi that matches service title
     *
     * @return array<int,array<string,mixed>>
     */
    public function forService(int $serviceId, int $limit = 4): array
    {
        if ($serviceId <= 0) {
            return [];
        }

        $doctorIds = [];

        // Strategy 1: Direct meta relation
        $direct = new WP_Query([
            'post_type' => 'dokter',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $limit),
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_rspku_synced_from_schedule',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => '_rspku_related_service',
                    'value' => (string) $serviceId,
                    'compare' => '=',
                ],
            ],
        ]);

        foreach ($direct->posts as $id) {
            $doctorIds[(int) $id] = true;
        }

        // Strategy 2: Match by specialization keyword from service title
        if (count($doctorIds) < $limit) {
            $servicePost = get_post($serviceId);
            if ($servicePost) {
                $serviceName = $servicePost->post_title;
                $matches = $this->findBySpecializationKeyword($serviceName, $limit - count($doctorIds), array_keys($doctorIds));
                foreach ($matches as $id) {
                    $doctorIds[$id] = true;
                }
            }
        }

        if ($doctorIds === []) {
            return [];
        }

        $query = new WP_Query([
            'post_type' => 'dokter',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $limit),
            'post__in' => array_keys($doctorIds),
            'orderby' => 'post__in',
            'no_found_rows' => true,
        ]);

        return array_map(fn (WP_Post $post): array => $this->normalize($post), $query->posts);
    }

    /**
     * Find doctors whose spesialisasi taxonomy matches a keyword from the given name.
     *
     * @param array<int,int> $excludeIds
     * @return array<int,int>
     */
    private function findBySpecializationKeyword(string $name, int $limit, array $excludeIds = []): array
    {
        if ($limit <= 0 || trim($name) === '') {
            return [];
        }

        // Extract meaningful keywords (min 4 chars, skip common words)
        $stopWords = ['klinik', 'poliklinik', 'layanan', 'medis', 'dan', 'atau', 'untuk', 'rumah', 'sakit'];
        $words = preg_split('/\s+/', strtolower(trim($name)));
        $keywords = array_filter($words, fn ($w) => strlen($w) >= 4 && !in_array($w, $stopWords, true));

        if ($keywords === []) {
            return [];
        }

        // Find matching taxonomy terms
        $matchedTermIds = [];
        foreach ($keywords as $keyword) {
            $terms = get_terms([
                'taxonomy' => 'spesialisasi-dokter',
                'hide_empty' => false,
                'search' => $keyword,
                'number' => 10,
            ]);
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $matchedTermIds[$term->term_id] = true;
                }
            }
        }

        if ($matchedTermIds === []) {
            return [];
        }

        $args = [
            'post_type' => 'dokter',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
            'tax_query' => [
                [
                    'taxonomy' => 'spesialisasi-dokter',
                    'field' => 'term_id',
                    'terms' => array_keys($matchedTermIds),
                ],
            ],
        ];

        if ($excludeIds !== []) {
            $args['post__not_in'] = $excludeIds;
        }

        $query = new WP_Query($args);

        return array_map('intval', $query->posts);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $postId): ?array
    {
        $post = get_post($postId);
        if (!$post instanceof WP_Post || $post->post_type !== 'dokter') {
            return null;
        }

        return $this->normalize($post);
    }

    /**
     * @return array<string,mixed>
     */
    public function filterOptions(): array
    {
        $scheduleRepository = new DoctorScheduleRepository();
        $scheduleSpecializations = $scheduleRepository->specializations();

        return [
            'days' => self::DAYS,
            'specializations' => $scheduleSpecializations !== []
                ? array_map(
                    static fn (array $item): array => [
                        'id' => 0,
                        'name' => (string) ($item['name'] ?? ''),
                        'slug' => (string) ($item['slug'] ?? ''),
                    ],
                    $scheduleSpecializations
                )
                : $this->terms('spesialisasi-dokter'),
            'services' => $this->posts('layanan'),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function normalize(WP_Post $post): array
    {
        $postId = (int) $post->ID;

        if (isset(self::$normalizeMemo[$postId])) {
            return self::$normalizeMemo[$postId];
        }

        $cacheKey = self::cacheKey($postId);
        $cached = wp_cache_get($cacheKey, self::CACHE_GROUP);
        if (is_array($cached)) {
            self::$normalizeMemo[$postId] = $cached;
            return $cached;
        }

        $transientKey = 'rspku_doctor_' . $postId;
        $persistent = get_transient($transientKey);
        if (is_array($persistent)) {
            wp_cache_set($cacheKey, $persistent, self::CACHE_GROUP, self::CACHE_TTL);
            self::$normalizeMemo[$postId] = $persistent;
            return $persistent;
        }

        $data = $this->buildNormalized($post);

        wp_cache_set($cacheKey, $data, self::CACHE_GROUP, self::CACHE_TTL);
        set_transient($transientKey, $data, self::CACHE_TTL);
        self::$normalizeMemo[$postId] = $data;

        return $data;
    }

    /**
     * Public so other modules (e.g. the directory sync) can bust the
     * cache for a specific doctor after mutating its meta.
     */
    public static function flushCache(int $postId): void
    {
        if ($postId <= 0) {
            return;
        }

        wp_cache_delete(self::cacheKey($postId), self::CACHE_GROUP);
        delete_transient('rspku_doctor_' . $postId);
        unset(self::$normalizeMemo[$postId]);
    }

    private static function cacheKey(int $postId): string
    {
        return 'doctor_' . $postId;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildNormalized(WP_Post $post): array
    {
        $postId = (int) $post->ID;
        $title = get_the_title($post);
        $name = $this->field($postId, '_rspku_doctor_name', 'nama_dokter');
        $profile = $this->field($postId, '_rspku_doctor_biography', 'profil_dokter');
        $education = $this->field($postId, '_rspku_education', 'pendidikan_dokter');
        $experience = $this->field($postId, '_rspku_experience', 'pengalaman_dokter');
        $training = $this->field($postId, '_rspku_training', 'pelatihan_dokter');
        $photo = $this->doctorPhoto($postId);
        $schedule = $this->schedule($postId);
        $specializations = wp_get_post_terms($postId, 'spesialisasi-dokter', ['fields' => 'all']);
        $primarySpecialization = $this->primarySpecialization($postId, is_array($specializations) ? $specializations : []);

        return [
            'id' => $postId,
            'title' => html_entity_decode($title, ENT_QUOTES, get_bloginfo('charset')),
            'name' => $name !== '' ? $name : html_entity_decode($title, ENT_QUOTES, get_bloginfo('charset')),
            'url' => get_permalink($post),
            'excerpt' => has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags($profile ?: $post->post_content), 24),
            'profile' => $profile ?: apply_filters('the_content', $post->post_content),
            'photo' => $photo,
            'degree' => $this->field($postId, '_rspku_degree'),
            'primary_specialization' => $primarySpecialization,
            'specializations' => is_array($specializations) ? array_map(static fn ($term): array => [
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ], $specializations) : [],
            'specialization_category' => $this->field($postId, '_rspku_schedule_category'),
            'sub_specialization' => $this->field($postId, '_rspku_sub_specialization'),
            'license' => $this->field($postId, '_rspku_license'),
            'education' => $education,
            'education_items' => $this->credentialItems($education),
            'experience' => $experience,
            'experience_items' => $this->credentialItems($experience),
            'training' => $training,
            'training_items' => $this->credentialItems($training),
            'appointment_url' => $this->field($postId, '_rspku_appointment_url'),
            'consultation_type' => $this->field($postId, '_rspku_consultation_type'),
            'schedule' => $schedule,
            'services' => $this->relatedServices($postId),
            'social_links' => $this->socialLinks($postId),
        ];
    }

    private function field(int $postId, string $modernKey, string $legacyKey = ''): string
    {
        $value = get_post_meta($postId, $modernKey, true);

        if (($value === '' || $value === null) && $legacyKey !== '') {
            $value = function_exists('get_field') ? get_field($legacyKey, $postId) : get_post_meta($postId, $legacyKey, true);
        }

        if (is_array($value) || is_object($value)) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * @return array<int,array{period:string,description:string}>
     */
    private function credentialItems(string $value): array
    {
        $value = $this->normalizeCredentialText($value);
        if ($value === '') {
            return [];
        }

        $lines = preg_split('/\R+/u', $value) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $period = '';
            $description = $line;

            if (preg_match('/^((?:[A-Za-z]+\s+)?\d{1,2}\s+[A-Za-z]+\s+\d{4}|\d{4}(?:\s*-\s*(?:\d{4}|sekarang|present))?)\s*-\s*(.+)$/iu', $line, $matches)) {
                $period = trim((string) $matches[1]);
                $description = trim((string) $matches[2]);
            }

            $items[] = [
                'period' => $period,
                'description' => $description,
            ];
        }

        return $items;
    }

    private function normalizeCredentialText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES, get_bloginfo('charset'));
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = str_replace(['?', '–', '—'], '-', $value);
        $value = preg_replace('/[\x{2013}\x{2014}\x{2212}]/u', '-', $value) ?: $value;
        $value = preg_replace('/(?<!^)(?<!\n)(?<=[\p{L}\)])(?=\d{4}\s*-\s*[\p{L}])/u', "\n", $value) ?: $value;
        $value = preg_replace('/(?<=\d{4})\s*-\s*(?=(?:sekarang|present)\b)/iu', ' - ', $value) ?: $value;
        $value = preg_replace('/(?<=\d{4})\s*-\s*(?=[^\n-])/u', ' - ', $value) ?: $value;
        $value = preg_replace('/\s+-\s+/u', ' - ', $value) ?: $value;
        $value = preg_replace('/[ \t]+\n/u', "\n", $value) ?: $value;
        $value = preg_replace('/\n{3,}/u', "\n\n", $value) ?: $value;

        return trim($value);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function doctorPhoto(int $postId): ?array
    {
        $thumbnailId = get_post_thumbnail_id($postId);
        if ($thumbnailId) {
            return $this->imageFromAttachment((int) $thumbnailId, 'rspku-doctor');
        }

        $legacy = function_exists('get_field') ? get_field('foto_dokter', $postId) : get_post_meta($postId, 'foto_dokter', true);
        return $this->imageFromValue($legacy);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function schedule(int $postId): array
    {
        $schedule = get_post_meta($postId, '_rspku_doctor_schedule', true);

        if (!is_array($schedule) || $schedule === []) {
            $schedule = function_exists('get_field') ? get_field('jadwal_praktek', $postId) : get_post_meta($postId, 'jadwal_praktek', true);
        }

        if (!is_array($schedule)) {
            return [];
        }

        $rows = [];
        foreach ($schedule as $row) {
            if (!is_array($row)) {
                continue;
            }

            $day = $this->normalizeDayKey((string) ($row['day'] ?? $row['hari'] ?? $row['hari_praktek'] ?? ''));
            $rows[] = [
                'day' => $day,
                'day_label' => self::DAYS[$day] ?? ($row['hari'] ?? $row['day_label'] ?? ''),
                'start_time' => (string) ($row['start_time'] ?? $row['jam_mulai'] ?? $row['mulai'] ?? ''),
                'end_time' => (string) ($row['end_time'] ?? $row['jam_selesai'] ?? $row['selesai'] ?? ''),
                'room' => (string) ($row['room'] ?? $row['ruangan'] ?? ''),
                'consultation_type' => (string) ($row['consultation_type'] ?? $row['jenis_konsultasi'] ?? ''),
            ];
        }

        return $rows;
    }

    private function normalizeDayKey(string $day): string
    {
        $day = sanitize_key($day);

        return match ($day) {
            'senin' => 'monday',
            'selasa' => 'tuesday',
            'rabu' => 'wednesday',
            'kamis' => 'thursday',
            'jumat' => 'friday',
            'sabtu' => 'saturday',
            'minggu' => 'sunday',
            default => $day,
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function relatedServices(int $postId): array
    {
        $serviceIds = [];
        $groupedIds = get_post_meta($postId, '_rspku_related_services', true);
        if (is_array($groupedIds)) {
            $serviceIds = array_merge($serviceIds, array_map('absint', $groupedIds));
        }

        $serviceIds = array_merge($serviceIds, array_map('absint', get_post_meta($postId, '_rspku_related_service', false)));
        $serviceIds = array_values(array_unique(array_filter($serviceIds)));

        $services = [];
        foreach ($serviceIds as $serviceId) {
            $post = get_post($serviceId);
            if (!$post instanceof WP_Post || $post->post_type !== 'layanan') {
                continue;
            }

            $services[] = [
                'id' => $serviceId,
                'title' => get_the_title($serviceId),
                'url' => get_permalink($serviceId),
            ];
        }

        return $services;
    }

    /**
     * @param array<int,mixed> $terms
     * @return array<string,mixed>|null
     */
    private function primarySpecialization(int $postId, array $terms): ?array
    {
        $sourceSlug = $this->field($postId, '_rspku_specialization_slug');
        $sourceName = $this->field($postId, '_rspku_specialization_name');

        foreach ($terms as $term) {
            if (!is_object($term)) {
                continue;
            }

            $slug = (string) ($term->slug ?? '');
            $name = (string) ($term->name ?? '');
            if (($sourceSlug !== '' && $slug === $sourceSlug) || ($sourceName !== '' && $name === $sourceName)) {
                return [
                    'id' => (int) ($term->term_id ?? 0),
                    'name' => $name,
                    'slug' => $slug,
                ];
            }
        }

        foreach ($terms as $term) {
            if (!is_object($term)) {
                continue;
            }

            if ((int) ($term->parent ?? 0) > 0) {
                return [
                    'id' => (int) ($term->term_id ?? 0),
                    'name' => (string) ($term->name ?? ''),
                    'slug' => (string) ($term->slug ?? ''),
                ];
            }
        }

        if ($sourceName !== '') {
            return [
                'id' => 0,
                'name' => $sourceName,
                'slug' => $sourceSlug !== '' ? $sourceSlug : sanitize_title($sourceName),
            ];
        }

        $term = $terms[0] ?? null;
        if (!is_object($term)) {
            return null;
        }

        return [
            'id' => (int) ($term->term_id ?? 0),
            'name' => (string) ($term->name ?? ''),
            'slug' => (string) ($term->slug ?? ''),
        ];
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function socialLinks(int $postId): array
    {
        $items = [
            'instagram' => $this->field($postId, '_rspku_social_instagram'),
            'facebook' => $this->field($postId, '_rspku_social_facebook'),
            'linkedin' => $this->field($postId, '_rspku_social_linkedin'),
            'whatsapp' => $this->field($postId, '_rspku_social_whatsapp'),
        ];

        $links = [];
        foreach ($items as $platform => $url) {
            if ($url === '') {
                continue;
            }

            $links[] = [
                'platform' => $platform,
                'url' => $url,
            ];
        }

        return $links;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function terms(string $taxonomy): array
    {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (!is_array($terms)) {
            return [];
        }

        return array_map(static fn ($term): array => [
            'id' => (int) $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        ], $terms);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function posts(string $postType): array
    {
        $posts = get_posts([
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        return array_map(static fn (WP_Post $post): array => [
            'id' => (int) $post->ID,
            'title' => get_the_title($post),
            'url' => get_permalink($post),
        ], $posts);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function imageFromValue(mixed $value): ?array
    {
        if (is_numeric($value)) {
            return $this->imageFromAttachment((int) $value, 'rspku-doctor');
        }

        if (is_array($value)) {
            $id = absint($value['ID'] ?? $value['id'] ?? 0);
            if ($id > 0) {
                return $this->imageFromAttachment($id, 'rspku-doctor');
            }

            if (!empty($value['url'])) {
                return [
                    'id' => null,
                    'url' => esc_url_raw((string) $value['url']),
                    'alt' => sanitize_text_field((string) ($value['alt'] ?? '')),
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function imageFromAttachment(int $attachmentId, string $size): ?array
    {
        $image = wp_get_attachment_image_src($attachmentId, $size);
        $fallback = wp_get_attachment_image_src($attachmentId, 'full');
        $src = $image ?: $fallback;

        if (!$src) {
            return null;
        }

        return [
            'id' => $attachmentId,
            'url' => $src[0],
            'width' => (int) $src[1],
            'height' => (int) $src[2],
            'alt' => (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
        ];
    }
}
