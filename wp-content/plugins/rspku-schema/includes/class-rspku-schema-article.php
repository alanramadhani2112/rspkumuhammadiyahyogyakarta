<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Single article schema — Article for regular posts, ScholarlyArticle for
 * journals (CPT `jurnal`). Includes author, publisher (Hospital), and
 * image. For medical health education posts tagged with
 * `medical_scholarly`, the type is upgraded to MedicalScholarlyArticle.
 */
final class RSPKU_Schema_Article {

    /**
     * @return array<string,mixed>|null
     */
    public static function build(int $postId): ?array {
        $post = get_post($postId);
        if (!$post instanceof \WP_Post || $post->post_status !== 'publish') {
            return null;
        }

        $url = (string) get_permalink($post);
        $type = self::resolve_type($post);
        $author = self::author_node((int) $post->post_author);

        $node = RSPKU_Schema_Helpers::compact_node([
            '@type' => $type,
            '@id' => $url . '#article',
            'headline' => (string) get_the_title($post),
            'description' => self::excerpt($post),
            'url' => $url,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
            'datePublished' => get_the_date(DATE_ATOM, $post) ?: null,
            'dateModified' => get_the_modified_date(DATE_ATOM, $post) ?: null,
            'inLanguage' => str_replace('_', '-', (string) get_bloginfo('language')),
            'image' => RSPKU_Schema_Helpers::image_from_post($postId),
            'author' => $author,
            'publisher' => RSPKU_Schema_Helpers::organization_id_ref(),
            'articleSection' => self::primary_category($postId),
            'keywords' => self::keywords($postId),
        ]);

        return $node;
    }

    private static function resolve_type(\WP_Post $post): string {
        if ($post->post_type === 'jurnal') {
            return 'ScholarlyArticle';
        }

        if (has_tag('medical-scholarly', $post) || has_tag('medical_scholarly', $post)) {
            return 'MedicalScholarlyArticle';
        }

        return 'Article';
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function author_node(int $authorId): ?array {
        if ($authorId <= 0) {
            return null;
        }

        $user = get_userdata($authorId);
        if (!$user) {
            return null;
        }

        return RSPKU_Schema_Helpers::compact_node([
            '@type' => 'Person',
            'name' => (string) $user->display_name,
            'url' => (string) get_author_posts_url($authorId),
        ]);
    }

    private static function excerpt(\WP_Post $post): string {
        if (has_excerpt($post)) {
            return trim(wp_strip_all_tags((string) $post->post_excerpt));
        }

        $words = wp_trim_words(wp_strip_all_tags(strip_shortcodes((string) $post->post_content)), 40, '...');
        return trim((string) $words);
    }

    private static function primary_category(int $postId): ?string {
        $terms = get_the_category($postId);
        if (!is_array($terms) || $terms === []) {
            return null;
        }

        $first = $terms[0];
        return $first instanceof \WP_Term ? $first->name : null;
    }

    /**
     * @return array<int,string>|null
     */
    private static function keywords(int $postId): ?array {
        $terms = get_the_tags($postId);
        if (!is_array($terms) || $terms === []) {
            return null;
        }

        $keywords = [];
        foreach ($terms as $term) {
            if ($term instanceof \WP_Term && $term->name !== '') {
                $keywords[] = $term->name;
            }
        }

        return $keywords === [] ? null : $keywords;
    }
}
