<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the taxonomies tied to RS PKU post types and keeps the old
 * /kategori-layanan/ rewrite rules alive so existing backlinks do not
 * 404. Moved from the theme in M6.
 */
final class RSPKU_CPT_Taxonomies
{
    public static function register(): void
    {
        add_action('init', [self::class, 'registerTaxonomies'], 100);
        add_action('init', [self::class, 'registerLegacyRewriteRules'], 101);
        add_action('template_redirect', [self::class, 'redirectLegacyServiceCategory'], 1);
    }

    public static function registerTaxonomies(): void
    {
        register_taxonomy('spesialisasi-dokter', ['dokter'], [
            'labels' => self::labels('Spesialisasi Dokter', 'Spesialisasi Dokter'),
            'public' => true,
            'show_in_rest' => true,
            'hierarchical' => true,
            'rewrite' => ['slug' => 'spesialisasi-dokter', 'with_front' => false],
        ]);

        register_taxonomy('kategori-layanan', ['layanan'], [
            'labels' => self::labels('Kategori Layanan', 'Kategori Layanan'),
            'public' => true,
            'show_in_rest' => true,
            'hierarchical' => true,
            'rewrite' => ['slug' => 'layanan-medis', 'with_front' => false],
        ]);

        register_taxonomy('jenis-konsultasi', ['dokter'], [
            'labels' => self::labels('Jenis Konsultasi', 'Jenis Konsultasi'),
            'public' => true,
            'show_in_rest' => true,
            'hierarchical' => false,
            'rewrite' => ['slug' => 'jenis-konsultasi', 'with_front' => false],
        ]);
    }

    public static function registerLegacyRewriteRules(): void
    {
        add_rewrite_rule(
            '^kategori-layanan/([^/]+)/page/([0-9]+)/?$',
            'index.php?kategori-layanan=$matches[1]&paged=$matches[2]',
            'top'
        );
        add_rewrite_rule(
            '^kategori-layanan/([^/]+)/?$',
            'index.php?kategori-layanan=$matches[1]',
            'top'
        );
    }

    public static function redirectLegacyServiceCategory(): void
    {
        if (!is_tax('kategori-layanan')) {
            return;
        }

        $path = trim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        if (!str_starts_with($path, 'kategori-layanan/')) {
            return;
        }

        $term = get_queried_object();
        if (!$term instanceof \WP_Term) {
            return;
        }

        $target = get_term_link($term);
        if (is_wp_error($target)) {
            return;
        }

        wp_safe_redirect((string) $target, 301);
        exit;
    }

    /**
     * @return array<string,string>
     */
    private static function labels(string $singular, string $plural): array
    {
        return [
            'name' => $plural,
            'singular_name' => $singular,
            'search_items' => sprintf(__('Cari %s', 'rspku-theme'), $plural),
            'all_items' => sprintf(__('Semua %s', 'rspku-theme'), $plural),
            'edit_item' => sprintf(__('Ubah %s', 'rspku-theme'), $singular),
            'update_item' => sprintf(__('Perbarui %s', 'rspku-theme'), $singular),
            'add_new_item' => sprintf(__('Tambah %s Baru', 'rspku-theme'), $singular),
            'new_item_name' => sprintf(__('Nama %s Baru', 'rspku-theme'), $singular),
            'menu_name' => $plural,
        ];
    }
}
