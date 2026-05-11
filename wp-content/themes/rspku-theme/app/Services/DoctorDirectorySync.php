<?php

declare(strict_types=1);

namespace Rspku\Services;

use Rspku\Repositories\DoctorScheduleRepository;
use WP_Post;

final class DoctorDirectorySync
{
    private const HASH_OPTION = 'rspku_doctor_directory_source_hash';
    private const SYNC_VERSION = '2026-05-08-6';

    public static function register(): void
    {
        add_action('init', [self::class, 'maybeSync'], 140);
    }

    public static function maybeSync(): void
    {
        if (!class_exists(\TablePress::class)) {
            return;
        }

        $repository = new DoctorScheduleRepository();
        $records = $repository->records();
        $hash = md5(self::SYNC_VERSION . '|' . $repository->sourceHash());

        if ($records === [] || $hash === '') {
            return;
        }

        $storedHash = (string) get_option(self::HASH_OPTION, '');
        if ($storedHash === $hash) {
            return;
        }

        self::sync($records, $repository);
        update_option(self::HASH_OPTION, $hash, false);
    }

    /**
     * @param array<int,array<string,mixed>> $records
     */
    private static function sync(array $records, DoctorScheduleRepository $repository): void
    {
        $doctorPosts = self::doctorPosts();
        $existingDoctors = self::existingDoctors($doctorPosts);
        $activePostIds = [];

        foreach ($records as $record) {
            $name = (string) ($record['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $existing = self::matchExistingDoctor($record, $existingDoctors, $doctorPosts);
            $postId = self::upsertDoctor($record, $existing instanceof WP_Post ? (int) $existing->ID : 0);
            if ($postId <= 0) {
                continue;
            }

            $activePostIds[$postId] = true;

            self::syncDoctorMeta($postId, $record);
            self::syncDoctorTerms($postId, $record);
        }

        foreach ($doctorPosts as $post) {
            if (isset($activePostIds[(int) $post->ID])) {
                continue;
            }

            if ((string) get_post_meta((int) $post->ID, '_rspku_profile_source', true) === 'team-rspku') {
                continue;
            }

            wp_trash_post($post->ID);
        }

        self::pruneSpecializationTerms($repository);
    }

    /**
     * @return array<int,WP_Post>
     */
    private static function doctorPosts(): array
    {
        return get_posts([
            'post_type' => 'dokter',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 300,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
    }

    /**
     * @param array<int,WP_Post> $posts
     * @return array<string,WP_Post>
     */
    private static function existingDoctors(array $posts): array
    {
        $lookup = [];

        foreach ($posts as $post) {
            $names = array_filter([
                get_the_title($post),
                (string) get_post_meta($post->ID, '_rspku_schedule_source_name', true),
                (string) get_post_meta($post->ID, 'nama_dokter', true),
            ]);

            foreach ($names as $name) {
                $normalized = self::normalizeKey($name);
                if ($normalized === '') {
                    continue;
                }

                $lookup[$normalized] = isset($lookup[$normalized])
                    ? self::preferredPost($lookup[$normalized], $post)
                    : $post;
            }
        }

        return $lookup;
    }

    /**
     * @param array<string,mixed> $record
     */
    private static function upsertDoctor(array $record, int $existingId = 0): int
    {
        $name = (string) ($record['name'] ?? '');
        $slug = sanitize_title($name);
        $profile = (string) ($record['profile'] ?? '');
        $summary = (string) ($record['summary'] ?? '');
        $authorId = get_current_user_id() ?: 1;

        $payload = [
            'post_type' => 'dokter',
            'post_status' => 'publish',
            'post_title' => $name,
            'post_excerpt' => $summary,
            'post_content' => $profile,
            'post_author' => $authorId,
        ];

        if ($existingId > 0) {
            $payload['ID'] = $existingId;
            $payload['post_name'] = (string) get_post_field('post_name', $existingId);
            $result = wp_update_post($payload, true);

            return is_wp_error($result) ? 0 : (int) $result;
        }

        $payload['post_name'] = $slug;
        $result = wp_insert_post($payload, true);

        return is_wp_error($result) ? 0 : (int) $result;
    }

    /**
     * @param array<string,mixed> $record
     */
    private static function syncDoctorMeta(int $postId, array $record): void
    {
        $name = (string) ($record['name'] ?? '');
        $category = (string) ($record['specialization_category'] ?? '');
        $consultationType = (string) ($record['consultation_type'] ?? '');
        $schedule = is_array($record['schedule'] ?? null) ? $record['schedule'] : [];
        $summary = (string) ($record['summary'] ?? '');
        $profile = (string) ($record['profile'] ?? '');
        $hasCuratedProfile = (string) get_post_meta($postId, '_rspku_profile_source', true) === 'team-rspku';
        $serviceIds = self::relatedIds($record, 'related_services');
        $polyclinicIds = self::relatedIds($record, 'related_polyclinics');

        update_post_meta($postId, '_rspku_synced_from_schedule', '1');
        update_post_meta($postId, '_rspku_schedule_source_name', $name);
        update_post_meta($postId, 'nama_dokter', $name);
        if (!$hasCuratedProfile) {
            update_post_meta($postId, '_rspku_doctor_biography', $profile);
            update_post_meta($postId, 'profil_dokter', $profile);
        }
        update_post_meta($postId, '_rspku_doctor_schedule', $schedule);
        update_post_meta($postId, 'jadwal_praktek', $schedule);
        update_post_meta($postId, '_rspku_consultation_type', $consultationType);
        update_post_meta($postId, '_rspku_schedule_summary', $summary);
        update_post_meta($postId, '_rspku_schedule_category', $category);
        update_post_meta($postId, '_rspku_specialization_name', (string) ($record['specialization'] ?? ''));
        update_post_meta($postId, '_rspku_specialization_slug', (string) ($record['specialization_slug'] ?? ''));
        update_post_meta($postId, '_rspku_related_services', $serviceIds);
        update_post_meta($postId, '_rspku_related_polyclinics', $polyclinicIds);
        update_post_meta($postId, 'pilih_poliklinik_dokter', $polyclinicIds[0] ?? '');

        self::replaceIndexedMeta($postId, '_rspku_related_service', $serviceIds);
        self::replaceIndexedMeta($postId, '_rspku_related_polyclinic', $polyclinicIds);
        self::replaceIndexedMeta($postId, '_rspku_schedule_day', array_column($schedule, 'day'));
    }

    /**
     * @param array<string,mixed> $record
     */
    private static function syncDoctorTerms(int $postId, array $record): void
    {
        $specialization = (string) ($record['specialization'] ?? '');
        $category = (string) ($record['specialization_category'] ?? '');

        if ($specialization === '') {
            return;
        }

        $parentId = 0;
        if ($category !== '' && $category !== 'Lainnya') {
            $parentId = self::ensureTerm($category, 'spesialisasi-dokter');
        }

        $termId = self::ensureTerm($specialization, 'spesialisasi-dokter', $parentId);
        if ($termId > 0) {
            $termIds = array_filter([$parentId, $termId]);
            wp_set_object_terms($postId, $termIds, 'spesialisasi-dokter', false);
        }
    }

    private static function ensureTerm(string $name, string $taxonomy, int $parentId = 0): int
    {
        $slug = sanitize_title($name);
        $term = term_exists($slug, $taxonomy);

        if (!$term) {
            $created = wp_insert_term($name, $taxonomy, [
                'slug' => $slug,
                'parent' => $parentId,
            ]);

            if (is_wp_error($created)) {
                return 0;
            }

            return (int) ($created['term_id'] ?? 0);
        }

        $termId = (int) (is_array($term) ? ($term['term_id'] ?? 0) : $term);
        if ($termId <= 0) {
            return 0;
        }

        $termObject = get_term($termId, $taxonomy);
        if ($termObject && !is_wp_error($termObject) && (int) $termObject->parent !== $parentId) {
            wp_update_term($termId, $taxonomy, ['parent' => $parentId]);
        }

        return $termId;
    }

    /**
     * @param array<string,mixed> $record
     * @return array<int,int>
     */
    private static function relatedIds(array $record, string $key): array
    {
        $items = is_array($record[$key] ?? null) ? $record[$key] : [];
        $ids = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = absint($item['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int,mixed> $values
     */
    private static function replaceIndexedMeta(int $postId, string $metaKey, array $values): void
    {
        delete_post_meta($postId, $metaKey);

        foreach (array_values(array_unique(array_filter(array_map('strval', $values)))) as $value) {
            add_post_meta($postId, $metaKey, $value, false);
        }
    }

    private static function pruneSpecializationTerms(DoctorScheduleRepository $repository): void
    {
        $validSlugs = [];

        foreach ($repository->specializations() as $item) {
            $slug = sanitize_title((string) ($item['name'] ?? ''));
            if ($slug !== '') {
                $validSlugs[$slug] = true;
            }

            $category = (string) ($item['category'] ?? '');
            if ($category !== '' && $category !== 'Lainnya') {
                $validSlugs[sanitize_title($category)] = true;
            }
        }

        $terms = get_terms([
            'taxonomy' => 'spesialisasi-dokter',
            'hide_empty' => false,
        ]);

        if (!is_array($terms)) {
            return;
        }

        foreach ($terms as $term) {
            $slug = (string) ($term->slug ?? '');
            if ($slug === '' || isset($validSlugs[$slug])) {
                continue;
            }

            if ((int) ($term->count ?? 0) > 0) {
                continue;
            }

            wp_delete_term((int) $term->term_id, 'spesialisasi-dokter');
        }
    }

    private static function normalizeKey(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES, get_bloginfo('charset'));
        $value = strtolower(trim($value));
        $value = str_replace(['&', '/', '(', ')', '.', ',', '-', "'", '’', '‘'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @param array<string,mixed> $record
     * @param array<string,WP_Post> $existingDoctors
     * @param array<int,WP_Post> $doctorPosts
     */
    private static function matchExistingDoctor(array $record, array $existingDoctors, array $doctorPosts): ?WP_Post
    {
        $name = (string) ($record['name'] ?? '');
        $normalizedName = self::normalizeKey($name);

        if ($normalizedName !== '' && isset($existingDoctors[$normalizedName])) {
            return $existingDoctors[$normalizedName];
        }

        $looseKey = self::looseKey($name);
        if ($looseKey === '') {
            return null;
        }

        $candidates = [];

        foreach ($doctorPosts as $post) {
            foreach (self::postNames($post) as $postName) {
                if (self::looseKey($postName) === $looseKey) {
                    $candidates[] = $post;
                    break;
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            static fn (WP_Post $left, WP_Post $right): int => self::candidateScore($right) <=> self::candidateScore($left) ?: ((int) $left->ID <=> (int) $right->ID)
        );

        return $candidates[0];
    }

    /**
     * @return array<int,string>
     */
    private static function postNames(WP_Post $post): array
    {
        return array_values(
            array_filter([
                get_the_title($post),
                (string) get_post_meta($post->ID, '_rspku_schedule_source_name', true),
                (string) get_post_meta($post->ID, 'nama_dokter', true),
            ])
        );
    }

    private static function looseKey(string $value): string
    {
        $value = self::normalizeKey($value);
        $value = trim((string) preg_replace('/,.*/u', '', $value));
        $value = preg_replace('/\b(prof|drg|dr|h|hj|kh|ir)\b/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return is_string($value) ? trim($value) : '';
    }

    private static function preferredPost(WP_Post $left, WP_Post $right): WP_Post
    {
        $leftScore = self::candidateScore($left);
        $rightScore = self::candidateScore($right);

        if ($leftScore === $rightScore) {
            return (int) $left->ID <= (int) $right->ID ? $left : $right;
        }

        return $leftScore > $rightScore ? $left : $right;
    }

    private static function candidateScore(WP_Post $post): int
    {
        $score = 0;

        if (get_post_thumbnail_id($post->ID)) {
            $score += 20;
        }

        if ((string) get_post_meta($post->ID, '_rspku_synced_from_schedule', true) === '1') {
            $score += 5;
        }

        foreach (['_rspku_social_instagram', '_rspku_social_facebook', '_rspku_social_linkedin', '_rspku_social_whatsapp', '_rspku_appointment_url'] as $metaKey) {
            if ((string) get_post_meta($post->ID, $metaKey, true) !== '') {
                $score += 4;
            }
        }

        if (trim((string) get_post_meta($post->ID, '_rspku_doctor_biography', true)) !== '') {
            $score += 6;
        }

        if (trim((string) $post->post_content) !== '') {
            $score += 3;
        }

        return $score;
    }

    private static function isGenericDirectoryLabel(string $name): bool
    {
        return in_array(
            self::normalizeKey($name),
            [
                'fisioterapi',
                'okupasi terapis',
                'terapis wicara',
            ],
            true
        );
    }
}
