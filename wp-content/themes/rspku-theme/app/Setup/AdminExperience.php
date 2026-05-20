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

        // Author credentials field on user profile (spec R2.4).
        add_action('show_user_profile', [self::class, 'renderAuthorCredentialsField']);
        add_action('edit_user_profile', [self::class, 'renderAuthorCredentialsField']);
        add_action('personal_options_update', [self::class, 'saveAuthorCredentialsField']);
        add_action('edit_user_profile_update', [self::class, 'saveAuthorCredentialsField']);
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

    /**
     * Render the author credentials input on edit-user screens.
     * Fulfils spec R2.4 — lets editors capture "Sp.A., M.Kes." strings
     * that appear on single post author cards.
     */
    public static function renderAuthorCredentialsField(\WP_User $user): void
    {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }

        $value = (string) get_user_meta($user->ID, '_rspku_author_credentials', true);
        ?>
        <h2><?php esc_html_e('Informasi RSPKU', 'rspku-theme'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="rspku_author_credentials"><?php esc_html_e('Kredensial (opsional)', 'rspku-theme'); ?></label></th>
                <td>
                    <input type="text"
                           name="rspku_author_credentials"
                           id="rspku_author_credentials"
                           value="<?php echo esc_attr($value); ?>"
                           class="regular-text"
                           maxlength="160">
                    <p class="description">
                        <?php esc_html_e('Contoh: Sp.A., M.Kes. — ditampilkan di bawah nama penulis artikel.', 'rspku-theme'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function saveAuthorCredentialsField(int $userId): void
    {
        if (!current_user_can('edit_user', $userId)) {
            return;
        }

        $raw = isset($_POST['rspku_author_credentials'])
            ? sanitize_text_field(wp_unslash((string) $_POST['rspku_author_credentials']))
            : '';

        // Cap length defensively even though the input has maxlength.
        $raw = mb_substr($raw, 0, 160);

        update_user_meta($userId, '_rspku_author_credentials', $raw);
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
            $group = self::findAcfFieldGroupByTitle($title);
            if ($group instanceof \WP_Post) {
                $ids[] = 'acf-' . $group->post_name;
            }
        }

        return $ids;
    }

    /**
     * Lookup an ACF field group post by exact title.
     *
     * Replacement for the deprecated WordPress 6.2+ `get_page_by_title()`.
     * Uses WP_Query's `title` argument which performs an exact match on
     * `post_title` and is available since WP 4.4.
     */
    private static function findAcfFieldGroupByTitle(string $title): ?\WP_Post
    {
        $query = new \WP_Query([
            'post_type' => 'acf-field-group',
            'title' => $title,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'ignore_sticky_posts' => true,
        ]);

        $post = $query->posts[0] ?? null;

        return $post instanceof \WP_Post ? $post : null;
    }
}
