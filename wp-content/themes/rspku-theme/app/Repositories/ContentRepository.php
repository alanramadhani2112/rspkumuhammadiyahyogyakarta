<?php

declare(strict_types=1);

namespace Rspku\Repositories;

use WP_Post;
use WP_Query;
use WP_Term;

final class ContentRepository
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function latestArticles(int $limit = 6): array
    {
        return $this->query('post', $limit, 'date', 'DESC', fn (WP_Post $post): array => $this->normalizeArticle($post));
    }

    /**
     * @return array<string,mixed>
     */
    public function paginatedArticles(int $page = 1, int $perPage = 9): array
    {
        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $perPage),
            'paged' => max(1, $page),
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => false,
        ]);

        return [
            'items' => array_map(fn (WP_Post $post): array => $this->normalizeArticle($post), $query->posts),
            'query' => $query,
            'total' => (int) $query->found_posts,
            'total_pages' => max(1, (int) $query->max_num_pages),
            'current_page' => max(1, $page),
            'per_page' => max(1, $perPage),
        ];
    }

    /**
     * Score-based related articles scorer (per spec R8.2).
     *
     * Ranking:
     * - +100 pts: article shares at least one category with the source
     * - +50 pts: article shares at least one tag with the source
     * - +10 pts: recency fallback (so we never return empty)
     *
     * Results are cached per source post for six hours. The transient
     * is invalidated automatically by the theme's cache-busting hooks
     * (save_post, deleted_post) and by {@see self::flushRelatedCache()}.
     *
     * @param array<int,int> $categoryIds
     * @return array<int,array<string,mixed>>
     */
    public function relatedArticles(int $excludeId, int $limit = 3, array $categoryIds = []): array
    {
        if ($excludeId <= 0) {
            return $this->queryRelatedArticles($excludeId, $limit, []);
        }

        $cacheKey = 'rspku_related_' . $excludeId . '_' . $limit;
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $ranked = $this->scoreRelatedArticles($excludeId, $limit, $categoryIds);

        // Persist for six hours. On a post update the save_post hook in
        // Theme::registerCacheInvalidation() flushes the source post's
        // cache; tag/category edits on related posts do not invalidate
        // here (too hot-path). Worst case is 6h of staleness, accepted.
        set_transient($cacheKey, $ranked, 6 * HOUR_IN_SECONDS);

        return $ranked;
    }

    /**
     * Walk the candidate pool once, score by category/tag overlap, and
     * return the top `$limit` by combined score (ties broken by date).
     *
     * @param array<int,int> $categoryIds
     * @return array<int,array<string,mixed>>
     */
    private function scoreRelatedArticles(int $excludeId, int $limit, array $categoryIds): array
    {
        $categoryIds = array_values(array_filter(array_map('absint', $categoryIds)));

        if ($categoryIds === []) {
            $termIds = wp_get_post_terms($excludeId, 'category', ['fields' => 'ids']);
            if (is_array($termIds)) {
                $categoryIds = array_values(array_filter(array_map('absint', $termIds)));
            }
        }

        $tagIds = wp_get_post_terms($excludeId, 'post_tag', ['fields' => 'ids']);
        $tagIds = is_array($tagIds) ? array_values(array_filter(array_map('absint', $tagIds))) : [];

        // Pull a wider pool than $limit so scoring has something to rank.
        // 3× limit is a sensible trade-off between query weight and
        // ranking quality; worst case we materialise 9 candidates.
        $poolSize = max($limit * 3, 10);

        $taxQuery = [];
        if ($categoryIds !== []) {
            $taxQuery[] = [
                'taxonomy' => 'category',
                'field' => 'term_id',
                'terms' => $categoryIds,
                'operator' => 'IN',
            ];
        }
        if ($tagIds !== []) {
            $taxQuery[] = [
                'taxonomy' => 'post_tag',
                'field' => 'term_id',
                'terms' => $tagIds,
                'operator' => 'IN',
            ];
        }

        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $poolSize,
            'post__not_in' => [$excludeId],
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ];

        if ($taxQuery !== []) {
            $args['tax_query'] = count($taxQuery) > 1
                ? array_merge(['relation' => 'OR'], $taxQuery)
                : $taxQuery;
        }

        $candidates = get_posts($args);

        // Always include the latest fallback so we never return nothing.
        if (count($candidates) < $limit) {
            $fallback = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $limit * 2,
                'post__not_in' => array_merge(
                    [$excludeId],
                    array_map(static fn (WP_Post $p): int => (int) $p->ID, $candidates)
                ),
                'orderby' => 'date',
                'order' => 'DESC',
                'no_found_rows' => true,
            ]);
            $candidates = array_merge($candidates, $fallback);
        }

        if ($candidates === []) {
            return [];
        }

        $scored = [];
        foreach ($candidates as $post) {
            $score = 10; // Recency baseline so everything ranks.

            if ($categoryIds !== []) {
                $postCats = wp_get_post_terms((int) $post->ID, 'category', ['fields' => 'ids']);
                if (is_array($postCats) && array_intersect($categoryIds, array_map('absint', $postCats))) {
                    $score += 100;
                }
            }

            if ($tagIds !== []) {
                $postTags = wp_get_post_terms((int) $post->ID, 'post_tag', ['fields' => 'ids']);
                if (is_array($postTags) && array_intersect($tagIds, array_map('absint', $postTags))) {
                    $score += 50;
                }
            }

            $scored[] = [
                'score' => $score,
                'timestamp' => (int) strtotime((string) $post->post_date_gmt),
                'post' => $post,
            ];
        }

        usort(
            $scored,
            static fn (array $a, array $b): int => ($b['score'] <=> $a['score']) ?: ($b['timestamp'] <=> $a['timestamp'])
        );

        $top = array_slice($scored, 0, max(1, $limit));

        return array_map(
            fn (array $entry): array => $this->normalizeArticle($entry['post']),
            $top
        );
    }

    /**
     * Public cache-bust hook for individual related-articles caches.
     * Called from the theme's save_post/deleted_post handlers.
     */
    public static function flushRelatedCache(int $postId): void
    {
        if ($postId <= 0) {
            return;
        }

        // Delete the whole size matrix we might have written.
        foreach ([3, 4, 5, 6, 9] as $limit) {
            delete_transient('rspku_related_' . $postId . '_' . $limit);
        }
    }

    /**
     * @param array<int,int> $categoryIds
     * @return array<int,array<string,mixed>>
     */
    private function queryRelatedArticles(int $excludeId, int $limit, array $categoryIds): array
    {
        $queryArgs = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $limit),
            'post__not_in' => [$excludeId],
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ];

        $categoryIds = array_values(array_filter(array_map('absint', $categoryIds)));
        if ($categoryIds !== []) {
            $queryArgs['category__in'] = $categoryIds;
        }

        $query = new WP_Query($queryArgs);

        return array_map(fn (WP_Post $post): array => $this->normalizeArticle($post), $query->posts);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function featuredServices(int $limit = 8): array
    {
        return $this->query('layanan', $limit, 'title', 'ASC', fn (WP_Post $post): array => $this->normalizeService($post));
    }

    /**
     * @param array<int,WP_Post> $posts
     * @return array<int,array<string,mixed>>
     */
    public function serviceItems(array $posts): array
    {
        return array_values(array_map(fn (WP_Post $post): array => $this->normalizeService($post), $posts));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function allPolyclinics(): array
    {
        return $this->polyclinics(200);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function polyclinics(int $limit = 12): array
    {
        return $this->query('poliklinik', $limit, 'title', 'ASC', fn (WP_Post $post): array => $this->normalizePolyclinic($post));
    }

    /**
     * @param array<int,WP_Post> $posts
     * @return array<int,array<string,mixed>>
     */
    public function polyclinicItems(array $posts): array
    {
        return array_values(array_map(fn (WP_Post $post): array => $this->normalizePolyclinic($post), $posts));
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findService(int $postId): ?array
    {
        $post = $this->postOfType($postId, 'layanan');
        if (!$post) {
            return null;
        }

        $item = $this->normalizeService($post);
        $terms = $this->terms($postId, 'kategori-layanan');
        $primaryCategory = $terms[0] ?? null;

        $item['content'] = apply_filters('the_content', $this->field($postId, 'detail_layanan', $post->post_content));
        $item['views'] = $this->views($postId);
        $item['categories'] = $this->termPayloads($terms);
        $item['primary_category'] = $primaryCategory ? $this->termPayload($primaryCategory) : null;

        return $item;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function relatedServices(int $excludeId, int $limit = 3): array
    {
        $termIds = wp_get_post_terms($excludeId, 'kategori-layanan', ['fields' => 'ids']);
        $queryArgs = [
            'post_type' => 'layanan',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $limit),
            'post__not_in' => [$excludeId],
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ];

        if (is_array($termIds) && $termIds !== []) {
            $queryArgs['tax_query'] = [[
                'taxonomy' => 'kategori-layanan',
                'field' => 'term_id',
                'terms' => array_map('absint', $termIds),
            ]];
        }

        $query = new WP_Query($queryArgs);

        return array_map(fn (WP_Post $post): array => $this->normalizeService($post), $query->posts);
    }

    /**
     * @param array<int,array<string,mixed>>|null $items
     * @return array<int,array<string,mixed>>
     */
    public function polyclinicGroups(?array $items = null): array
    {
        $items = $items ?? $this->allPolyclinics();
        $groups = [];

        foreach ($items as $item) {
            $title = (string) ($item['group'] ?? 'Layanan lainnya');
            if (!isset($groups[$title])) {
                $groups[$title] = [
                    'title' => $title,
                    'items' => [],
                    'order' => $this->polyclinicGroupOrder($title),
                ];
            }

            $groups[$title]['items'][] = $item;
        }

        uasort(
            $groups,
            static fn (array $left, array $right): int => ($left['order'] <=> $right['order'])
                ?: strcmp((string) $left['title'], (string) $right['title'])
        );

        return array_map(
            static function (array $group): array {
                unset($group['order']);
                return $group;
            },
            array_values($groups)
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findPolyclinic(int $postId): ?array
    {
        $post = $this->postOfType($postId, 'poliklinik');
        if (!$post) {
            return null;
        }

        $item = $this->normalizePolyclinic($post);
        $content = $this->field($postId, 'detail_poli', $post->post_content);

        $item['content'] = $content !== ''
            ? apply_filters('the_content', $content)
            : '';

        return $item;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function allJournals(): array
    {
        return $this->latestJournals(200);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function latestJournals(int $limit = 6): array
    {
        return $this->query('jurnal', $limit, 'date', 'DESC', fn (WP_Post $post): array => $this->normalizeJournal($post));
    }

    /**
     * @param array<int,WP_Post> $posts
     * @return array<int,array<string,mixed>>
     */
    public function journalItems(array $posts): array
    {
        return array_values(array_map(fn (WP_Post $post): array => $this->normalizeJournal($post), $posts));
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findJournal(int $postId): ?array
    {
        $post = $this->postOfType($postId, 'jurnal');
        if (!$post) {
            return null;
        }

        $item = $this->normalizeJournal($post);
        $content = $this->field($postId, 'deskripsi_jurnal', $post->post_content);

        $item['content'] = $content !== ''
            ? apply_filters('the_content', $content)
            : '';

        return $item;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function latestRooms(int $limit = 6): array
    {
        return $this->query('rawat-inap', $limit, 'title', 'ASC', fn (WP_Post $post): array => $this->normalizeRoom($post));
    }

    /**
     * @return array<string,mixed>
     */
    public function paginatedRooms(int $page = 1, int $perPage = 9): array
    {
        $query = new WP_Query([
            'post_type' => 'rawat-inap',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $perPage),
            'paged' => max(1, $page),
            'orderby' => [
                'menu_order' => 'ASC',
                'title' => 'ASC',
            ],
            'order' => 'ASC',
            'no_found_rows' => false,
        ]);

        return [
            'items' => array_map(fn (WP_Post $post): array => $this->normalizeRoom($post), $query->posts),
            'query' => $query,
            'total' => (int) $query->found_posts,
            'total_pages' => max(1, (int) $query->max_num_pages),
            'current_page' => max(1, $page),
            'per_page' => max(1, $perPage),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findRoom(int $postId): ?array
    {
        $post = $this->postOfType($postId, 'rawat-inap');
        if (!$post) {
            return null;
        }

        $item = $this->normalizeRoom($post);
        $item['content'] = apply_filters('the_content', $post->post_content);
        $item['category'] = $this->field($postId, 'kategori_kamar');
        $item['bed_count'] = $this->field($postId, 'jumlah_tempat_tidur');
        $item['size'] = $this->field($postId, 'luas_kamar_m²');
        $item['features'] = $this->arrayField($postId, 'fasilitas_kamar');
        $item['included'] = $this->arrayField($postId, 'sudah_termasuk');
        $item['gallery'] = $this->gallery($postId);
        $item['views'] = $this->views($postId);

        return $item;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function relatedRooms(int $excludeId, int $limit = 3): array
    {
        $query = new WP_Query([
            'post_type' => 'rawat-inap',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $limit),
            'post__not_in' => [$excludeId],
            'orderby' => [
                'menu_order' => 'ASC',
                'title' => 'ASC',
            ],
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        return array_map(fn (WP_Post $post): array => $this->normalizeRoom($post), $query->posts);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findManagement(int $postId): ?array
    {
        $post = $this->postOfType($postId, 'manajemen-rs');
        if (!$post) {
            return null;
        }

        $item = $this->normalizeManagement($post);
        $item['content'] = apply_filters('the_content', $post->post_content);

        return $item;
    }

    /**
     * @param array<int,WP_Post> $posts
     * @return array<int,array<string,mixed>>
     */
    public function managementItems(array $posts): array
    {
        return array_values(array_map(fn (WP_Post $post): array => $this->normalizeManagement($post), $posts));
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<int,array{title:string,items:array<int,array<string,mixed>>}>
     */
    public function managementSections(array $items): array
    {
        $sections = [
            'Badan Pembina Harian RS PKU Muhammadiyah Yogyakarta' => [],
            'Direksi RS PKU Muhammadiyah Yogyakarta Periode 2026-2030' => [],
        ];

        foreach ($items as $index => $item) {
            $position = strtolower((string) ($item['position'] ?? ''));
            $section = str_contains($position, 'badan pembina harian')
                ? 'Badan Pembina Harian RS PKU Muhammadiyah Yogyakarta'
                : 'Direksi RS PKU Muhammadiyah Yogyakarta Periode 2026-2030';

            $sections[$section][] = ['index' => $index, 'item' => $item];
        }

        $rankedSort = function (array $ranks): callable {
            return function (array $left, array $right) use ($ranks): int {
                $leftPosition = strtolower((string) ($left['item']['position'] ?? ''));
                $rightPosition = strtolower((string) ($right['item']['position'] ?? ''));
                $leftRank = count($ranks);
                $rightRank = count($ranks);

                foreach ($ranks as $rank => $needle) {
                    if (str_contains($leftPosition, $needle)) {
                        $leftRank = $rank;
                    }

                    if (str_contains($rightPosition, $needle)) {
                        $rightRank = $rank;
                    }
                }

                return [$leftRank, $left['index']] <=> [$rightRank, $right['index']];
            };
        };

        usort($sections['Badan Pembina Harian RS PKU Muhammadiyah Yogyakarta'], $rankedSort(['ketua', 'sekretaris', 'anggota']));
        usort($sections['Direksi RS PKU Muhammadiyah Yogyakarta Periode 2026-2030'], $rankedSort(['direktur utama']));

        return array_values(array_map(
            fn (string $title, array $sectionItems): array => [
                'title' => $title,
                'items' => array_column($sectionItems, 'item'),
            ],
            array_keys($sections),
            $sections
        ));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function popularArticles(int $limit = 5, int $excludeId = 0): array
    {
        $queryArgs = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $limit),
            'meta_key' => 'views',
            'orderby' => ['meta_value_num' => 'DESC', 'date' => 'DESC'],
            'order' => 'DESC',
            'no_found_rows' => true,
        ];

        if ($excludeId > 0) {
            $queryArgs['post__not_in'] = [$excludeId];
        }

        $query = new WP_Query($queryArgs);

        return array_map(fn (WP_Post $post): array => $this->normalizeArticle($post), $query->posts);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function query(string $postType, int $limit, string $orderby, string $order, callable $normalizer): array
    {
        $query = new WP_Query([
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => max(1, $limit),
            'orderby' => $orderby,
            'order' => $order,
            'no_found_rows' => true,
        ]);

        return array_map(fn (WP_Post $post): array => $normalizer($post), $query->posts);
    }

    /**
     * @return array<string,mixed>
     */
    private function normalizeArticle(WP_Post $post): array
    {
        $excerpt = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags($post->post_content), 24, '');
        
        return [
            'id' => (int) $post->ID,
            'title' => get_the_title($post),
            'url' => get_permalink($post),
            'excerpt' => $excerpt,
            'image' => $this->thumbnail($post->ID, 'rspku-card'),
            'date' => get_the_date('j M Y', $post),
            'views' => (int) get_post_meta((int) $post->ID, 'views', true),
        ];
    }

    /**
     * Public normalizer for service posts. Used by TemplateController
     * when resolving admin-picked featured services by ID.
     *
     * @return array<string,mixed>
     */
    public function normalizeServicePublic(WP_Post $post): array
    {
        return $this->normalizeService($post);
    }

    /**
     * @return array<string,mixed>
     */
    private function normalizeService(WP_Post $post): array
    {
        $postId = (int) $post->ID;
        $title = html_entity_decode(get_the_title($post), ENT_QUOTES, get_bloginfo('charset'));

        return [
            'id' => $postId,
            'title' => $this->field($postId, 'nama_layanan', $title),
            'url' => get_permalink($post),
            'excerpt' => $this->field($postId, 'deskripsi_singkat_layanan', wp_trim_words(wp_strip_all_tags($post->post_content), 24)),
            'image' => $this->image($postId, 'gambar_layanan', 'rspku-card'),
            'views' => (int) get_post_meta($postId, 'views', true),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function normalizePolyclinic(WP_Post $post): array
    {
        $postId = (int) $post->ID;
        $title = html_entity_decode(get_the_title($post), ENT_QUOTES, get_bloginfo('charset'));
        $resolvedTitle = $this->field($postId, 'nama_poli', $title);

        return [
            'id' => $postId,
            'slug' => $post->post_name,
            'title' => $resolvedTitle,
            'url' => get_permalink($post),
            'excerpt' => wp_trim_words(
                wp_strip_all_tags($this->field($postId, 'deskripsi_singkat', $post->post_content)),
                24
            ),
            'image' => $this->image($postId, 'gambar_poli', 'rspku-card'),
            'group' => $this->polyclinicGroup($resolvedTitle),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function normalizeJournal(WP_Post $post): array
    {
        $postId = (int) $post->ID;
        $title = html_entity_decode(get_the_title($post), ENT_QUOTES, get_bloginfo('charset'));

        return [
            'id' => $postId,
            'slug' => $post->post_name,
            'title' => $this->field($postId, 'judul_jurnal', $title),
            'url' => get_permalink($post),
            'excerpt' => wp_trim_words(
                wp_strip_all_tags($this->field($postId, 'deskripsi_jurnal', $post->post_content)),
                24
            ),
            'image' => $this->thumbnail($postId, 'rspku-card'),
            'file' => $this->document($postId, 'file_dokumen'),
            'date' => get_the_date('j M Y', $post),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function normalizeRoom(WP_Post $post): array
    {
        $postId = (int) $post->ID;

        return [
            'id' => $postId,
            'title' => $this->field($postId, 'nama_kamar', get_the_title($post)),
            'url' => get_permalink($post),
            'excerpt' => $this->field($postId, 'deskripsi', wp_trim_words(wp_strip_all_tags($post->post_content), 24)),
            'image' => $this->galleryImage($postId),
            'rate' => $this->field($postId, 'tarif_per_hari_rp', ''),
            'views' => (int) get_post_meta($postId, 'views', true),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function normalizeManagement(WP_Post $post): array
    {
        $postId = (int) $post->ID;
        $title = get_the_title($post);
        $photo = $this->image($postId, 'foto_profile', 'rspku-card');

        return [
            'id' => $postId,
            'title' => $title,
            'name' => $this->field($postId, 'nama', $title),
            'position' => $this->field($postId, 'jabatan'),
            'url' => get_permalink($post),
            'excerpt' => has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags($post->post_content), 24),
            'image' => $photo,
            'photo' => $photo,
            'views' => (int) get_post_meta($postId, 'views', true),
        ];
    }

    private function postOfType(int $postId, string $postType): ?WP_Post
    {
        $post = get_post($postId);

        if (!$post instanceof WP_Post || $post->post_type !== $postType) {
            return null;
        }

        return $post;
    }

    private function views(int $postId): int
    {
        return (int) get_post_meta($postId, 'views', true);
    }

    /**
     * @return array<int,WP_Term>
     */
    private function terms(int $postId, string $taxonomy): array
    {
        $terms = wp_get_post_terms($postId, $taxonomy, ['fields' => 'all']);

        return is_array($terms) ? $terms : [];
    }

    /**
     * @param array<int,WP_Term> $terms
     * @return array<int,array<string,mixed>>
     */
    private function termPayloads(array $terms): array
    {
        return array_map(fn (WP_Term $term): array => $this->termPayload($term), $terms);
    }

    /**
     * @return array<string,mixed>
     */
    private function termPayload(WP_Term $term): array
    {
        $url = get_term_link($term);

        return [
            'id' => (int) $term->term_id,
            'name' => (string) $term->name,
            'slug' => (string) $term->slug,
            'url' => is_wp_error($url) ? '' : (string) $url,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function thumbnail(int $postId, string $size): ?array
    {
        $thumbnailId = get_post_thumbnail_id($postId);
        if (!$thumbnailId) {
            return null;
        }

        $image = wp_get_attachment_image_src($thumbnailId, $size) ?: wp_get_attachment_image_src($thumbnailId, 'full');
        if (!$image) {
            return null;
        }

        return [
            'id' => (int) $thumbnailId,
            'url' => $image[0],
            'width' => (int) $image[1],
            'height' => (int) $image[2],
            'alt' => (string) get_post_meta($thumbnailId, '_wp_attachment_image_alt', true),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function image(int $postId, string $key, string $size): ?array
    {
        $value = function_exists('get_field') ? get_field($key, $postId) : get_post_meta($postId, $key, true);

        if (is_numeric($value)) {
            return $this->imageFromAttachment((int) $value, $size);
        }

        if (is_array($value)) {
            $id = absint($value['ID'] ?? $value['id'] ?? 0);
            if ($id > 0) {
                return $this->imageFromAttachment($id, $size);
            }

            if (!empty($value['url'])) {
                return [
                    'id' => null,
                    'url' => esc_url_raw((string) $value['url']),
                    'alt' => sanitize_text_field((string) ($value['alt'] ?? '')),
                ];
            }
        }

        return $this->thumbnail($postId, $size);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function imageFromAttachment(int $attachmentId, string $size): ?array
    {
        $image = wp_get_attachment_image_src($attachmentId, $size) ?: wp_get_attachment_image_src($attachmentId, 'full');
        if (!$image) {
            return null;
        }

        return [
            'id' => $attachmentId,
            'url' => $image[0],
            'width' => (int) $image[1],
            'height' => (int) $image[2],
            'alt' => (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function document(int $postId, string $key): ?array
    {
        $value = function_exists('get_field') ? get_field($key, $postId) : get_post_meta($postId, $key, true);

        if (is_numeric($value)) {
            return $this->documentFromAttachment((int) $value);
        }

        if (is_array($value)) {
            $id = absint($value['ID'] ?? $value['id'] ?? 0);
            if ($id > 0) {
                return $this->documentFromAttachment($id);
            }

            $url = esc_url_raw((string) ($value['url'] ?? ''));
            if ($url !== '') {
                return [
                    'id' => null,
                    'url' => $url,
                    'label' => sanitize_text_field((string) ($value['title'] ?? $value['filename'] ?? __('Dokumen jurnal', 'rspku-theme'))),
                    'mime' => sanitize_text_field((string) ($value['mime_type'] ?? '')),
                ];
            }
        }

        if (is_string($value)) {
            $url = trim($value);
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                return [
                    'id' => null,
                    'url' => esc_url_raw($url),
                    'label' => __('Dokumen jurnal', 'rspku-theme'),
                    'mime' => '',
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function documentFromAttachment(int $attachmentId): ?array
    {
        $url = wp_get_attachment_url($attachmentId);
        if (!$url) {
            return null;
        }

        return [
            'id' => $attachmentId,
            'url' => esc_url_raw($url),
            'label' => get_the_title($attachmentId) ?: __('Dokumen jurnal', 'rspku-theme'),
            'mime' => (string) get_post_mime_type($attachmentId),
        ];
    }

    private function galleryImage(int $postId): ?array
    {
        $gallery = function_exists('get_field') ? get_field('foto_kamar', $postId) : get_post_meta($postId, 'foto_kamar', true);

        if (is_array($gallery) && $gallery !== []) {
            $first = $gallery[0];
            if (is_numeric($first)) {
                return $this->imageFromAttachment((int) $first, 'rspku-card');
            }

            if (is_array($first) && isset($first['ID'])) {
                return $this->imageFromAttachment((int) $first['ID'], 'rspku-card');
            }
        }

        return $this->thumbnail($postId, 'rspku-card');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function gallery(int $postId): array
    {
        $gallery = function_exists('get_field') ? get_field('foto_kamar', $postId) : get_post_meta($postId, 'foto_kamar', true);
        if (!is_array($gallery)) {
            return [];
        }

        $images = [];
        foreach ($gallery as $item) {
            if (is_numeric($item)) {
                $image = $this->imageFromAttachment((int) $item, 'rspku-hero');
            } elseif (is_array($item) && isset($item['ID'])) {
                $image = $this->imageFromAttachment((int) $item['ID'], 'rspku-hero');
            } else {
                $image = null;
            }

            if ($image !== null) {
                $images[] = $image;
            }
        }

        return $images;
    }

    /**
     * @return array<int,string>
     */
    private function arrayField(int $postId, string $key): array
    {
        $value = function_exists('get_field') ? get_field($key, $postId) : get_post_meta($postId, $key, true);
        if (!is_array($value)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    static fn ($item): string => is_string($item) ? trim($item) : '',
                    $value
                )
            )
        );
    }

    private function field(int $postId, string $key, string $fallback = ''): string
    {
        $value = function_exists('get_field') ? get_field($key, $postId) : get_post_meta($postId, $key, true);

        if (is_array($value) || is_object($value)) {
            return $fallback;
        }

        $value = trim((string) $value);
        return $value !== '' ? $value : $fallback;
    }

    private function polyclinicGroup(string $title): string
    {
        $title = trim($title);

        if ($title === 'Anestesi' || str_starts_with($title, 'Umum /')) {
            return 'Layanan umum';
        }

        if (
            str_starts_with($title, 'Klinik Anak')
            || str_starts_with($title, 'Klinik Jantung Anak')
            || str_starts_with($title, 'Terapi Tumbuh Kembang Anak')
            || str_starts_with($title, 'Terapi Wicara')
        ) {
            return 'Klinik Anak';
        }

        if (str_starts_with($title, 'Klinik Bedah')) {
            return 'Klinik Bedah';
        }

        if (str_starts_with($title, 'Klinik Gigi')) {
            return 'Klinik Gigi';
        }

        if ($title === 'Rehabilitasi Medis') {
            return 'Rehabilitasi & Terapi';
        }

        if (
            in_array(
                $title,
                [
                    'Klinik Home Care',
                    'Klinik Patologi',
                    'Klinik Patologi Anatomi',
                    'Klinik Psikologi Klinis',
                    'Klinik USG dan Radiologi',
                ],
                true
            )
        ) {
            return 'Klinik Penunjang';
        }

        return 'Klinik Penyakit Khusus';
    }

    private function polyclinicGroupOrder(string $title): int
    {
        return match ($title) {
            'Layanan umum' => 10,
            'Klinik Anak' => 20,
            'Klinik Bedah' => 30,
            'Klinik Gigi' => 40,
            'Klinik Penyakit Khusus' => 50,
            'Klinik Penunjang' => 60,
            'Rehabilitasi & Terapi' => 70,
            default => 999,
        };
    }
}
