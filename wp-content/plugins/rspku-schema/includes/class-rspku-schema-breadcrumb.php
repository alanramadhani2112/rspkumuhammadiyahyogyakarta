<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * BreadcrumbList schema builder. Uses WordPress's native query context to
 * reconstruct a sensible breadcrumb trail without depending on third-
 * party plugins. Returns null on the front page or when the trail would
 * only have a single entry.
 */
final class RSPKU_Schema_Breadcrumb {

    /**
     * @return array<string,mixed>|null
     */
    public static function build(): ?array {
        if (is_front_page() || is_admin() || is_feed()) {
            return null;
        }

        $items = self::trail();
        if (count($items) < 2) {
            return null;
        }

        $listItems = [];
        foreach ($items as $index => $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => (string) $item['name'],
                'item' => (string) $item['url'],
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => (string) home_url(add_query_arg([], (string) ($_SERVER['REQUEST_URI'] ?? '/'))) . '#breadcrumb',
            'itemListElement' => $listItems,
        ];
    }

    /**
     * @return array<int,array{name:string,url:string}>
     */
    private static function trail(): array {
        $home = home_url('/');
        $trail = [['name' => 'Beranda', 'url' => $home]];

        if (is_singular()) {
            $post = get_post();
            if (!$post instanceof \WP_Post) {
                return $trail;
            }

            // Post type archive (e.g. Dokter, Poliklinik) when one exists.
            $pto = get_post_type_object($post->post_type);
            if ($pto && $pto->has_archive) {
                $archiveLink = get_post_type_archive_link($post->post_type);
                if (is_string($archiveLink)) {
                    $trail[] = [
                        'name' => (string) ($pto->labels->name ?? $post->post_type),
                        'url' => $archiveLink,
                    ];
                }
            } elseif ($post->post_type === 'post') {
                $blogPageId = (int) get_option('page_for_posts');
                if ($blogPageId > 0) {
                    $trail[] = [
                        'name' => (string) get_the_title($blogPageId),
                        'url' => (string) get_permalink($blogPageId),
                    ];
                }
            }

            $trail[] = [
                'name' => (string) get_the_title($post),
                'url' => (string) get_permalink($post),
            ];
        } elseif (is_post_type_archive()) {
            $pto = get_queried_object();
            if ($pto instanceof \WP_Post_Type) {
                $archiveLink = get_post_type_archive_link($pto->name);
                $trail[] = [
                    'name' => (string) ($pto->labels->name ?? $pto->name),
                    'url' => is_string($archiveLink) ? $archiveLink : $home,
                ];
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $termLink = get_term_link($term);
                $trail[] = [
                    'name' => (string) $term->name,
                    'url' => is_wp_error($termLink) ? $home : (string) $termLink,
                ];
            }
        } elseif (is_search()) {
            $trail[] = [
                'name' => sprintf('Pencarian: %s', get_search_query()),
                'url' => (string) home_url('/?s=' . rawurlencode(get_search_query())),
            ];
        } elseif (is_page()) {
            $pageId = (int) get_queried_object_id();
            if ($pageId > 0) {
                $trail[] = [
                    'name' => (string) get_the_title($pageId),
                    'url' => (string) get_permalink($pageId),
                ];
            }
        }

        return $trail;
    }
}
