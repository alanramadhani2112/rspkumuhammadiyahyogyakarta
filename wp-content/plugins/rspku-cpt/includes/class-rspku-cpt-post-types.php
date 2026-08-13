<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the custom post types used across RS PKU (dokter, poliklinik,
 * layanan, jurnal, manajemen-rs, rawat-inap). Moved from the
 * theme in M6 so content remains accessible even if the active theme is
 * swapped out.
 */
final class RSPKU_CPT_PostTypes
{
    public static function register(): void
    {
        add_action('init', [self::class, 'registerPostTypes'], 99);
    }

    public static function registerPostTypes(): void
    {
        self::doctor();
        self::polyclinic();
        self::service();
        self::journal();
        self::management();
        self::room();
    }

    private static function doctor(): void
    {
        register_post_type('dokter', [
            'labels' => self::labels('Dokter', 'Dokter', 'Dokter'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => 'dokter',
            'rewrite' => ['slug' => 'dokter', 'with_front' => false],
            'menu_icon' => 'dashicons-id-alt',
            'supports' => ['title', 'thumbnail', 'revisions', 'author'],
        ]);
    }

    private static function polyclinic(): void
    {
        register_post_type('poliklinik', [
            'labels' => self::labels('Poliklinik', 'Poliklinik', 'Poliklinik'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => 'poliklinik',
            'rewrite' => ['slug' => 'poliklinik', 'with_front' => false],
            'menu_icon' => 'dashicons-heart',
            'supports' => ['title', 'thumbnail', 'revisions'],
        ]);
    }

    private static function service(): void
    {
        register_post_type('layanan', [
            'labels' => self::labels('Layanan', 'Layanan', 'Layanan'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => 'layanan',
            'rewrite' => ['slug' => 'layanan', 'with_front' => false],
            'menu_icon' => 'dashicons-clipboard',
            'supports' => ['title', 'thumbnail', 'revisions'],
        ]);
    }

    private static function journal(): void
    {
        register_post_type('jurnal', [
            'labels' => self::labels('E-Jurnal', 'E-Jurnal', 'Jurnal'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => ['slug' => 'e-jurnal', 'with_front' => false],
            'menu_icon' => 'dashicons-media-document',
            'supports' => ['title', 'thumbnail', 'revisions'],
        ]);
    }

    private static function management(): void
    {
        register_post_type('manajemen-rs', [
            'labels' => self::labels('Manajemen RS', 'Manajemen RS', 'Manajemen RS'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => 'manajemen-rs',
            'rewrite' => ['slug' => 'manajemen-rs', 'with_front' => false],
            'menu_icon' => 'dashicons-groups',
            'supports' => ['title', 'thumbnail', 'revisions', 'page-attributes'],
        ]);
    }

    private static function room(): void
    {
        register_post_type('rawat-inap', [
            'labels' => self::labels('Rawat Inap', 'Rawat Inap', 'Rawat Inap'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => 'rawat-inap',
            'rewrite' => ['slug' => 'rawat-inap', 'with_front' => false],
            'menu_icon' => 'dashicons-building',
            'supports' => ['title', 'thumbnail', 'revisions'],
        ]);
    }

    /**
     * @return array<string,string>
     */
    private static function labels(string $singular, string $plural, string $menu): array
    {
        return [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $menu,
            'add_new' => sprintf(__('Tambah %s', 'rspku-theme'), $singular),
            'add_new_item' => sprintf(__('Tambah %s Baru', 'rspku-theme'), $singular),
            'edit_item' => sprintf(__('Ubah %s', 'rspku-theme'), $singular),
            'new_item' => sprintf(__('%s Baru', 'rspku-theme'), $singular),
            'view_item' => sprintf(__('Lihat %s', 'rspku-theme'), $singular),
            'search_items' => sprintf(__('Cari %s', 'rspku-theme'), $plural),
        ];
    }
}
