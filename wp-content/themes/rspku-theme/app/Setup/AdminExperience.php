<?php

declare(strict_types=1);

namespace Rspku\Setup;

final class AdminExperience
{
    /**
     * @var array<int,string>
     */
    private const STRUCTURED_POST_TYPES = [
        'dokter',
        'layanan',
        'poliklinik',
        'jurnal',
        'rawat-inap',
        'manajemen-rs',
    ];

    public static function register(): void
    {
        add_filter('use_block_editor_for_post_type', [self::class, 'useBlockEditor'], 10, 2);
        add_filter('admin_body_class', [self::class, 'adminBodyClass']);
        add_filter('enter_title_here', [self::class, 'titlePlaceholder'], 10, 2);
        add_filter('wpseo_metabox_prio', [self::class, 'yoastMetaboxPriority']);
        add_action('admin_menu', [self::class, 'removeLowValueMetaBoxes'], 99);
        add_action('add_meta_boxes', [self::class, 'removeConflictingDoctorMetaBoxes'], 99);
    }

    public static function useBlockEditor(bool $useBlockEditor, string $postType): bool
    {
        if (in_array($postType, self::STRUCTURED_POST_TYPES, true)) {
            return false;
        }

        return $useBlockEditor;
    }

    public static function adminBodyClass(string $classes): string
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !in_array((string) $screen->post_type, self::STRUCTURED_POST_TYPES, true)) {
            return $classes;
        }

        $classes .= ' rspku-admin-screen rspku-admin-structured rspku-admin-' . sanitize_html_class((string) $screen->post_type);

        return trim($classes);
    }

    public static function titlePlaceholder(string $placeholder, \WP_Post $post): string
    {
        return match ($post->post_type) {
            'dokter' => __('Nama dokter', 'rspku-theme'),
            'layanan' => __('Nama layanan medis', 'rspku-theme'),
            'poliklinik' => __('Nama poliklinik', 'rspku-theme'),
            'jurnal' => __('Judul jurnal', 'rspku-theme'),
            'rawat-inap' => __('Nama kamar rawat inap', 'rspku-theme'),
            'manajemen-rs' => __('Nama pimpinan / manajemen', 'rspku-theme'),
            default => $placeholder,
        };
    }

    public static function yoastMetaboxPriority(): string
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && in_array((string) $screen->post_type, self::STRUCTURED_POST_TYPES, true)) {
            return 'low';
        }

        return 'high';
    }

    public static function removeLowValueMetaBoxes(): void
    {
        foreach (self::STRUCTURED_POST_TYPES as $postType) {
            remove_meta_box('postexcerpt', $postType, 'normal');
            remove_meta_box('commentstatusdiv', $postType, 'normal');
            remove_meta_box('commentsdiv', $postType, 'normal');
            remove_meta_box('authordiv', $postType, 'normal');
            remove_meta_box('trackbacksdiv', $postType, 'normal');
        }
    }

    public static function removeConflictingDoctorMetaBoxes(): void
    {
        $postId = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
        $post = $postId > 0 ? get_post($postId) : null;
        if (!$post instanceof \WP_Post || $post->post_type !== 'dokter') {
            return;
        }

        if ((string) get_post_meta($postId, '_rspku_synced_from_schedule', true) !== '1') {
            return;
        }

        foreach (self::doctorLegacyAcfBoxIds() as $boxId) {
            remove_meta_box($boxId, 'dokter', 'normal');
            remove_meta_box($boxId, 'dokter', 'advanced');
            remove_meta_box($boxId, 'dokter', 'side');
        }
    }

    /**
     * @return array<int,string>
     */
    private static function doctorLegacyAcfBoxIds(): array
    {
        $titles = [
            'Dokter',
            'Jadwal Dokter',
            'Pelatihan Dokter',
            'Pendidikan Dokter',
            'Pengalaman Dokter',
            'Poliklinik Dokter',
        ];

        $ids = [];
        foreach ($titles as $title) {
            $group = get_page_by_title($title, OBJECT, 'acf-field-group');
            if ($group instanceof \WP_Post) {
                $ids[] = 'acf-' . $group->post_name;
            }
        }

        return $ids;
    }
}
