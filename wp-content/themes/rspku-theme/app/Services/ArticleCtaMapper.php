<?php

declare(strict_types=1);

namespace Rspku\Services;

use WP_Post;
use WP_Query;

/**
 * Contextual CTA resolver for single-post pages.
 *
 * Looks at the article's tags and categories and, when a match is found,
 * directs the "Butuh konsultasi?" CTA to the most relevant polyclinic or
 * service page instead of the generic /dokter/ + /poliklinik/ pair.
 *
 * Matching strategy (in order):
 *
 *   1. Article tag slug matches a polyclinic slug exactly
 *   2. Article tag name shares meaningful tokens with a polyclinic title
 *   3. Article category slug/name matches a polyclinic (same logic)
 *   4. None matched → return the generic CTA
 *
 * Results are cached per post for 12 hours since this is mostly static
 * data that only changes when editors rename taxonomies or polyclinics.
 *
 * Fulfils spec R7 (End-of-Article Konsultasi CTA), acceptance R7.3.
 */
final class ArticleCtaMapper
{
    private const CACHE_TTL = 12 * HOUR_IN_SECONDS;

    /**
     * Words to drop when fuzzy-matching tag/category against polyclinic
     * title. They carry no semantic weight and cause false positives.
     *
     * @var array<int,string>
     */
    private const STOP_WORDS = [
        'klinik',
        'poliklinik',
        'rumah',
        'sakit',
        'penyakit',
        'layanan',
        'medis',
        'dan',
        'atau',
        'untuk',
        'spesialis',
        'umum',
    ];

    /**
     * Build the CTA payload for the given article.
     *
     * @return array{
     *     heading: string,
     *     description: string,
     *     primary: array{label:string,url:string,icon:string},
     *     secondary: array{label:string,url:string,icon:string}
     * }
     */
    public static function build(WP_Post $post): array
    {
        if ($post->post_type !== 'post') {
            return self::genericPayload();
        }

        $cacheKey = 'rspku_article_cta_' . (int) $post->ID;
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $match = self::resolveContextualMatch((int) $post->ID);
        $payload = $match !== null
            ? self::contextualPayload($match)
            : self::genericPayload();

        set_transient($cacheKey, $payload, self::CACHE_TTL);

        return $payload;
    }

    /**
     * Clear the cached CTA for a given post. Called from save_post so
     * editors who re-tag an article see the refreshed CTA immediately.
     */
    public static function flushCache(int $postId): void
    {
        if ($postId <= 0) {
            return;
        }

        delete_transient('rspku_article_cta_' . $postId);
    }

    /**
     * Try to find a polyclinic that matches one of the article's
     * tags or categories. Returns the matched post or null.
     */
    private static function resolveContextualMatch(int $postId): ?WP_Post
    {
        $tags = wp_get_post_terms($postId, 'post_tag', ['fields' => 'all']);
        $categories = wp_get_post_terms($postId, 'category', ['fields' => 'all']);

        $terms = [];
        if (is_array($tags)) {
            $terms = array_merge($terms, $tags);
        }
        if (is_array($categories)) {
            $terms = array_merge($terms, $categories);
        }

        if ($terms === []) {
            return null;
        }

        // Step 1: exact slug match against any polyclinic
        foreach ($terms as $term) {
            if (!$term instanceof \WP_Term) {
                continue;
            }

            $slug = (string) $term->slug;
            if ($slug === '') {
                continue;
            }

            $poly = get_page_by_path($slug, OBJECT, 'poliklinik');
            if ($poly instanceof WP_Post && $poly->post_status === 'publish') {
                return $poly;
            }
        }

        // Step 2: fuzzy token match against polyclinic titles
        $candidates = self::polyclinicCandidates();
        if ($candidates === []) {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0;

        foreach ($terms as $term) {
            if (!$term instanceof \WP_Term) {
                continue;
            }

            $termTokens = self::tokenise((string) $term->name);
            if ($termTokens === []) {
                continue;
            }

            foreach ($candidates as $candidate) {
                $candidateTokens = self::tokenise($candidate->post_title);
                $overlap = count(array_intersect($termTokens, $candidateTokens));

                if ($overlap > $bestScore) {
                    $bestScore = $overlap;
                    $bestMatch = $candidate;
                }
            }
        }

        // Require at least one matching non-stopword token.
        return $bestScore >= 1 ? $bestMatch : null;
    }

    /**
     * @return array<int,WP_Post>
     */
    private static function polyclinicCandidates(): array
    {
        $cached = wp_cache_get('rspku_polyclinic_candidates', 'rspku_theme');
        if (is_array($cached)) {
            return $cached;
        }

        $query = new WP_Query([
            'post_type' => 'poliklinik',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'no_found_rows' => true,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        wp_cache_set('rspku_polyclinic_candidates', $query->posts, 'rspku_theme', HOUR_IN_SECONDS);

        return $query->posts;
    }

    /**
     * @return array<int,string>
     */
    private static function tokenise(string $value): array
    {
        $lower = strtolower(trim($value));
        $parts = preg_split('/[^a-z0-9]+/u', $lower, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts)) {
            return [];
        }

        return array_values(array_filter(
            $parts,
            static fn (string $token): bool => strlen($token) >= 4 && !in_array($token, self::STOP_WORDS, true)
        ));
    }

    /**
     * @return array{
     *     heading: string,
     *     description: string,
     *     primary: array{label:string,url:string,icon:string},
     *     secondary: array{label:string,url:string,icon:string}
     * }
     */
    private static function contextualPayload(WP_Post $polyclinic): array
    {
        $title = html_entity_decode(get_the_title($polyclinic), ENT_QUOTES, get_bloginfo('charset'));

        return [
            'heading' => __('Butuh konsultasi lebih lanjut?', 'rspku-theme'),
            'description' => sprintf(
                /* translators: %s: polyclinic name */
                __('Tim %s siap membantu kebutuhan konsultasi dan pemeriksaan Anda.', 'rspku-theme'),
                $title
            ),
            'primary' => [
                'label' => sprintf(
                    /* translators: %s: polyclinic name */
                    __('Lihat %s', 'rspku-theme'),
                    $title
                ),
                'url' => (string) get_permalink($polyclinic),
                'icon' => 'stethoscope',
            ],
            'secondary' => [
                'label' => __('Cari dokter', 'rspku-theme'),
                'url' => home_url('/dokter/'),
                'icon' => 'search',
            ],
        ];
    }

    /**
     * @return array{
     *     heading: string,
     *     description: string,
     *     primary: array{label:string,url:string,icon:string},
     *     secondary: array{label:string,url:string,icon:string}
     * }
     */
    private static function genericPayload(): array
    {
        return [
            'heading' => __('Konsultasikan dengan dokter kami', 'rspku-theme'),
            'description' => __('Temukan dokter spesialis sesuai kebutuhan Anda atau jelajahi poliklinik yang tersedia.', 'rspku-theme'),
            'primary' => [
                'label' => __('Cari dokter', 'rspku-theme'),
                'url' => home_url('/dokter/'),
                'icon' => 'search',
            ],
            'secondary' => [
                'label' => __('Jadwal poliklinik', 'rspku-theme'),
                'url' => home_url('/poliklinik/'),
                'icon' => 'stethoscope',
            ],
        ];
    }
}
