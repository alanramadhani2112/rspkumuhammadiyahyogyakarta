<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin page for RS PKU Settings — tabbed interface.
 */
final class RSPKU_Settings_Admin
{
    private const MENU_SLUG = 'rspku-settings';
    private const CAPABILITY = 'manage_options';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'registerMenu']);
        add_action('admin_init', [self::class, 'registerSettings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('admin_post_rspku_settings_save', [self::class, 'handleSave']);
        add_action('admin_post_rspku_settings_export', [self::class, 'handleExport']);
        add_action('admin_post_rspku_settings_import', [self::class, 'handleImport']);
        add_action('wp_ajax_rspku_search_posts', [self::class, 'ajaxSearchPosts']);
    }

    public static function registerMenu(): void
    {
        add_menu_page(
            'RS PKU Settings',
            'RS PKU Settings',
            self::CAPABILITY,
            self::MENU_SLUG,
            [self::class, 'renderPage'],
            self::menuIcon(),
            3
        );
    }

    /**
     * Return a data-URI SVG for the admin menu icon.
     *
     * `dashicons-hospital` does not exist in the bundled WordPress
     * dashicons set, so the previous menu rendered without a glyph.
     * Using an inline SVG guarantees visibility and keeps us on-brand
     * with the Lucide heart-pulse icon used throughout the theme.
     */
    private static function menuIcon(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 14h-4.5a2.5 2.5 0 0 0 0 5H20"/><path d="M12 21V10"/><path d="M2 21V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/><path d="M2 10h20"/><path d="M8 14v2"/><path d="M16 14v2"/></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public static function registerSettings(): void
    {
        register_setting(
            'rspku_settings_group',
            RSPKU_SETTINGS_OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize'],
                'default' => RSPKU_Settings_Defaults::all(),
            ]
        );

        // After WP saves the option and redirects back, ensure the user
        // lands on the same tab they were editing (not always "Umum").
        add_filter('wp_redirect', [self::class, 'preserveTabOnRedirect']);
    }

    /**
     * Append `&tab=<active_tab>` to the options.php redirect URL so the
     * user returns to the tab they just saved, not the first tab.
     */
    public static function preserveTabOnRedirect(string $location): string
    {
        // Only intercept the options.php → settings-updated redirect.
        if (!str_contains($location, 'page=' . self::MENU_SLUG) || !str_contains($location, 'settings-updated')) {
            return $location;
        }

        $activeTab = isset($_POST['active_tab']) ? sanitize_key((string) $_POST['active_tab']) : '';
        if ($activeTab === '' || str_contains($location, '&tab=')) {
            return $location;
        }

        return add_query_arg('tab', $activeTab, $location);
    }

    /**
     * Handle the settings form submission directly via admin-post.php.
     *
     * This bypasses WordPress's options.php whitelist mechanism which
     * was silently rejecting our save (register_setting timing issue).
     * The approach is the same used by ACF, WooCommerce, and other
     * major plugins that manage their own option arrays.
     */
    public static function handleSave(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die('Unauthorized', 'Forbidden', ['response' => 403]);
        }

        check_admin_referer('rspku_settings_save', '_rspku_nonce');

        $activeTab = isset($_POST['active_tab']) ? sanitize_key((string) $_POST['active_tab']) : '';

        // Extract only the rspku_settings array from POST.
        $input = isset($_POST[RSPKU_SETTINGS_OPTION_KEY]) && is_array($_POST[RSPKU_SETTINGS_OPTION_KEY])
            ? wp_unslash($_POST[RSPKU_SETTINGS_OPTION_KEY])
            : [];

        // Run through our sanitizer (which merges with stored values).
        $clean = self::sanitize($input);

        // Save to database.
        update_option(RSPKU_SETTINGS_OPTION_KEY, $clean);

        // Flush caches so frontend reflects changes immediately.
        if (class_exists('RSPKU_Settings_API')) {
            // Trigger the same flush that fires on option update.
            do_action('rspku/settings/flushed');
        }

        // Redirect back to the same tab with success notice.
        $redirect = add_query_arg(
            [
                'page' => self::MENU_SLUG,
                'tab' => $activeTab,
                'settings-updated' => 'true',
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public static function enqueueAssets(string $hook): void
    {
        if ($hook !== 'toplevel_page_' . self::MENU_SLUG) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'rspku-settings-admin',
            RSPKU_SETTINGS_URL . 'assets/admin.css',
            [],
            RSPKU_SETTINGS_VERSION
        );

        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');

        wp_enqueue_script(
            'rspku-settings-admin',
            RSPKU_SETTINGS_URL . 'assets/admin.js',
            ['jquery', 'wp-color-picker'],
            RSPKU_SETTINGS_VERSION,
            true
        );

        // Localize nonce for AJAX post picker.
        wp_add_inline_script('rspku-settings-admin', 'var rspkuSettingsNonce = "' . wp_create_nonce('rspku_search_posts') . '";', 'before');

        // Also output as a data attribute on body for fallback access.
        add_action('admin_footer', static function (): void {
            echo '<script>if(typeof rspkuSettingsNonce==="undefined"){var rspkuSettingsNonce="' . wp_create_nonce('rspku_search_posts') . '";}</script>' . "\n";
        });
    }

    /**
     * Sanitize the submitted option payload.
     *
     * The admin form only submits values for fields that belong to the
     * currently active tab (plus a few hidden pass-throughs). Treating
     * "key missing from input" as "reset to default" would wipe every
     * other tab's saved value on every submit — which is exactly the
     * "perubahan tidak tersimpan" bug we're fixing.
     *
     * Strategy:
     *   1. Load the currently stored option (so values from other tabs
     *      survive untouched).
     *   2. Walk `$defaults` so new fields picked up by Registry updates
     *      get a default when they have never been persisted.
     *   3. For each key, prefer the submitted value, fall back to the
     *      stored value, fall back to the default.
     *   4. Sanitize the winning value by type.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function sanitize(array $input): array
    {
        $defaults = RSPKU_Settings_Defaults::all();
        $stored = get_option(RSPKU_SETTINGS_OPTION_KEY, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        $clean = [];

        foreach ($defaults as $key => $default) {
            $submitted = array_key_exists($key, $input);
            $hasStored = array_key_exists($key, $stored);

            // Toggles are special: unchecked checkboxes are absent from
            // $_POST entirely, so we interpret "submitted form but missing
            // toggle key" as explicit false. We know the form was the
            // source of truth when active_tab is set in the payload.
            if (is_bool($default)) {
                $activeTab = isset($_POST['active_tab']) ? sanitize_key((string) $_POST['active_tab']) : '';
                $tabContainsToggle = $activeTab !== '' && self::tabContainsField($activeTab, $key);

                if ($submitted) {
                    $clean[$key] = !empty($input[$key]);
                } elseif ($tabContainsToggle) {
                    // Active tab owns this toggle but the box was unchecked.
                    $clean[$key] = false;
                } elseif ($hasStored) {
                    $clean[$key] = (bool) $stored[$key];
                } else {
                    $clean[$key] = (bool) $default;
                }
                continue;
            }

            // Non-bool: field must be submitted OR have an existing value
            // to be retained. Unsubmitted + no stored = default.
            if (!$submitted) {
                // Special case: array fields (checkbox pickers, repeaters)
                // that belong to the active tab but are absent from POST
                // means "user unchecked everything" → save empty array.
                if (is_array($default)) {
                    $activeTab = isset($_POST['active_tab']) ? sanitize_key((string) $_POST['active_tab']) : '';
                    if ($activeTab !== '' && self::tabContainsField($activeTab, $key)) {
                        $clean[$key] = [];
                        continue;
                    }
                }

                $clean[$key] = $hasStored ? $stored[$key] : $default;
                continue;
            }

            $value = $input[$key];

            if ($key === 'service_hours' && is_array($value)) {
                $clean[$key] = [];
                foreach ($value as $row) {
                    if (!is_array($row) || empty($row['label'])) {
                        continue;
                    }
                    $clean[$key][] = [
                        'label' => sanitize_text_field((string) $row['label']),
                        'time' => sanitize_text_field((string) ($row['time'] ?? '')),
                        'highlight' => !empty($row['highlight']),
                    ];
                }
            } elseif ($key === 'footer_quick_links' && is_array($value)) {
                $clean[$key] = [];
                foreach ($value as $row) {
                    if (!is_array($row) || empty($row['label'])) {
                        continue;
                    }
                    $clean[$key][] = [
                        'label' => sanitize_text_field((string) $row['label']),
                        'url' => esc_url_raw((string) ($row['url'] ?? '')),
                    ];
                }
            } elseif ($key === 'home_featured_reviews' && is_array($value)) {
                $clean[$key] = [];
                foreach ($value as $row) {
                    if (!is_array($row) || empty($row['name'])) {
                        continue;
                    }
                    $clean[$key][] = [
                        'name' => sanitize_text_field((string) $row['name']),
                        'rating' => max(1, min(5, (int) ($row['rating'] ?? 5))),
                        'date_label' => sanitize_text_field((string) ($row['date_label'] ?? '')),
                        'excerpt' => sanitize_textarea_field((string) ($row['excerpt'] ?? '')),
                    ];
                }
            } elseif (in_array($key, ['home_featured_services', 'home_featured_doctors'], true)) {
                // Checkbox picker submits an array of checked IDs.
                $ids = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : []);
                $clean[$key] = array_values(array_filter(array_map('absint', $ids)));
                // Enforce max limit from registry.
                $maxItems = self::fieldMax($key);
                if ($maxItems > 0 && count($clean[$key]) > $maxItems) {
                    $clean[$key] = array_slice($clean[$key], 0, $maxItems);
                }
            } elseif (self::isUrlField($key)) {
                $clean[$key] = str_starts_with($key, 'promo_slide_') ? self::sanitizePromoUrl((string) $value) : esc_url_raw((string) $value);
            } elseif ($key === 'email') {
                $clean[$key] = sanitize_email((string) $value);
            } elseif (str_starts_with($key, 'brand_color_')) {
                $clean[$key] = sanitize_hex_color((string) $value) ?: ($hasStored ? (string) $stored[$key] : (string) $default);
            } elseif (self::isImageField($key)) {
                $clean[$key] = self::sanitizeImageId($value);
            } elseif ($key === 'hero_title' || $key === 'hero_description') {
                $clean[$key] = wp_kses_post((string) $value);
            } elseif (self::isHistoryCaptionField($key)) {
                $clean[$key] = sanitize_textarea_field((string) $value);
            } elseif (str_starts_with($key, 'promo_slide_') && str_ends_with($key, '_description')) {
                $clean[$key] = sanitize_textarea_field((string) $value);
            } else {
                $clean[$key] = sanitize_text_field((string) $value);
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('[RSPKU Settings] sanitize produced ' . count($clean) . ' keys for tab: ' . (isset($_POST['active_tab']) ? sanitize_key((string) $_POST['active_tab']) : 'unknown'));
        }

        return $clean;
    }

    /**
     * Get the `max` attribute from a field definition in the registry.
     */
    private static function fieldMax(string $fieldKey): int
    {
        $tabs = RSPKU_Settings_Registry::tabs();
        foreach ($tabs as $tab) {
            foreach ($tab['sections'] ?? [] as $section) {
                foreach ($section['fields'] ?? [] as $field) {
                    if (($field['key'] ?? '') === $fieldKey && isset($field['max'])) {
                        return (int) $field['max'];
                    }
                }
            }
        }
        return 0;
    }

    /**
     * Check whether a given tab in the registry owns a field key.
     * Used by the toggle-resolution logic in {@see sanitize()}.
     */
    private static function tabContainsField(string $tabKey, string $fieldKey): bool
    {
        $tabs = RSPKU_Settings_Registry::tabs();
        if (!isset($tabs[$tabKey]['sections']) || !is_array($tabs[$tabKey]['sections'])) {
            return false;
        }

        foreach ($tabs[$tabKey]['sections'] as $section) {
            $fields = $section['fields'] ?? [];
            if (!is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if (($field['key'] ?? '') === $fieldKey) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Keys that must be sanitized as URLs. Using an explicit list is safer
     * than pattern-matching on key substrings because it avoids accidental
     * URL-encoding of handle/label fields.
     */
    private static function isUrlField(string $key): bool
    {
        static $urlKeys = [
            'social_instagram',
            'social_facebook',
            'social_youtube',
            'social_twitter',
            'social_linkedin',
            'google_maps_embed_url',
            'google_maps_link',
            'hero_cta_primary_url',
            'hero_cta_secondary_url',
            'header_cta_url',
            'home_cta_primary_url',
            'home_cta_secondary_url',
            'doctor_appointment_fallback_url',
            'promo_slide_1_cta_url',
            'promo_slide_2_cta_url',
            'promo_slide_3_cta_url',
        ];

        return in_array($key, $urlKeys, true);
    }

    private static function sanitizePromoUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return esc_url_raw($url);
        }

        return wp_parse_url($url, PHP_URL_SCHEME) === 'https' ? esc_url_raw($url) : '';
    }

    /**
     * Keys that store a WordPress attachment ID. Centralising this list
     * keeps the sanitize switch and the Timber context resolver in sync.
     */
    private static function isImageField(string $key): bool
    {
        return in_array($key, self::imageKeys(), true);
    }

    private static function isHistoryCaptionField(string $key): bool
    {
        return in_array($key, [
            'history_hero_caption',
            'history_pioneers_caption',
            'history_child_service_caption',
            'history_first_stone_caption',
            'history_modernization_caption',
        ], true);
    }

    /**
     * @param mixed $value
     */
    private static function sanitizeImageId($value): int
    {
        $id = absint($value);

        return $id > 0 && wp_attachment_is_image($id) ? $id : 0;
    }

    /**
     * @return list<string>
     */
    public static function imageKeys(): array
    {
        return [
            'hero_image_id',
            'promo_slide_1_image_id',
            'promo_slide_2_image_id',
            'promo_slide_3_image_id',
            'home_feature_image',
            'home_cta_image',
            'image_dokter_archive',
            'image_fasilitas',
            'image_berita',
            'image_poliklinik',
            'image_layanan',
            'history_hero_image_id',
            'history_pioneers_image_id',
            'history_child_service_image_id',
            'history_first_stone_image_id',
            'history_modernization_image_id',
        ];
    }

    /**
     * AJAX handler for the post picker search field.
     * Returns up to 10 matching published posts of the requested type.
     */
    public static function ajaxSearchPosts(): void
    {
        check_ajax_referer('rspku_search_posts', '_wpnonce');

        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(null, 403);
        }

        $postType = sanitize_key((string) ($_GET['post_type'] ?? 'post'));
        $query = sanitize_text_field((string) ($_GET['q'] ?? ''));

        if (strlen($query) < 2) {
            wp_send_json_success([]);
        }

        $posts = get_posts([
            'post_type' => $postType,
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => 10,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $results = array_map(static fn (\WP_Post $p): array => [
            'id' => (int) $p->ID,
            'title' => html_entity_decode(get_the_title($p), ENT_QUOTES, get_bloginfo('charset')),
        ], $posts);

        wp_send_json_success($results);
    }

    public static function renderPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }

        $tabs = RSPKU_Settings_Registry::tabs();
        $active_tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : array_key_first($tabs);
        if (!isset($tabs[$active_tab])) {
            $active_tab = array_key_first($tabs);
        }

        $defaults = RSPKU_Settings_Defaults::all();
        $options = get_option(RSPKU_SETTINGS_OPTION_KEY, []);
        if (!is_array($options)) {
            $options = [];
        }
        $options = array_merge($defaults, $options);
        ?>
        <div class="wrap rspku-settings-wrap">
            <h1>RS PKU Settings</h1>
            <p class="description">Kelola konfigurasi theme RS PKU Muhammadiyah Yogyakarta. Perubahan langsung berlaku setelah klik Simpan.</p>

            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
                <div class="notice notice-success is-dismissible"><p><strong>Perubahan berhasil disimpan.</strong></p></div>
            <?php endif; ?>

            <?php
            $importResult = isset($_GET['rspku_import']) ? sanitize_key((string) $_GET['rspku_import']) : '';
            if ($importResult !== ''):
                $importMessages = [
                    'ok' => ['notice-success', 'Import berhasil. Setting sudah diterapkan.'],
                    'missing' => ['notice-error', 'Import gagal: pilih file JSON terlebih dahulu.'],
                    'too_big' => ['notice-error', 'Import gagal: file melebihi 1 MB atau kosong.'],
                    'unreadable' => ['notice-error', 'Import gagal: file tidak dapat dibaca.'],
                    'invalid_json' => ['notice-error', 'Import gagal: isi file bukan JSON yang valid.'],
                ];
                if (isset($importMessages[$importResult])):
                    [$cls, $msg] = $importMessages[$importResult];
            ?>
                <div class="notice <?php echo esc_attr($cls); ?> is-dismissible"><p><strong><?php echo esc_html($msg); ?></strong></p></div>
            <?php endif; endif; ?>

            <nav class="rspku-settings-tabs nav-tab-wrapper" aria-label="Navigasi pengaturan RS PKU">
                <?php foreach ($tabs as $tab_key => $tab): ?>
                    <?php
                    $section_count = count($tab['sections'] ?? []);
                    $field_count = array_sum(array_map(static fn (array $section): int => count($section['fields'] ?? []), $tab['sections'] ?? []));
                    ?>
                    <a href="?page=<?php echo esc_attr(self::MENU_SLUG); ?>&tab=<?php echo esc_attr($tab_key); ?>"
                       class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>"
                       <?php echo $active_tab === $tab_key ? 'aria-current="page"' : ''; ?>>
                        <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                        <span class="rspku-settings-tab-label"><?php echo esc_html($tab['label']); ?></span>
                        <span class="rspku-settings-tab-count" aria-label="<?php echo esc_attr(sprintf('%d section, %d field', $section_count, $field_count)); ?>">
                            <?php echo esc_html((string) $section_count); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ($active_tab === 'tools'): ?>
                <?php self::renderTabContent($tabs[$active_tab], $options, $defaults); ?>

                <div class="rspku-settings-actions">
                    <p class="description rspku-settings-actions__note">Gunakan tombol Export/Import di atas. Tab ini tidak menyimpan perubahan form utama.</p>
                    <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="button button-secondary">
                        <span class="dashicons dashicons-external"></span> Lihat Situs
                    </a>
                </div>
            <?php else: ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="rspku-settings-form">
                    <?php wp_nonce_field('rspku_settings_save', '_rspku_nonce'); ?>
                    <input type="hidden" name="action" value="rspku_settings_save">
                    <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">

                    <?php self::renderTabContent($tabs[$active_tab], $options, $defaults); ?>

                    <div class="rspku-settings-actions">
                        <?php submit_button('Simpan Perubahan', 'primary large', 'submit', false); ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="button button-secondary">
                            <span class="dashicons dashicons-external"></span> Lihat Situs
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the Tools → Export/Import widget. Uses plain admin-post
     * endpoints so the flow works even when JavaScript is disabled.
     */
    private static function renderExportImportField(): void
    {
        $adminPost = admin_url('admin-post.php');
        ?>
        <div class="rspku-tools-grid">
            <div class="card rspku-tools-card">
                <h3><span class="dashicons dashicons-download" aria-hidden="true"></span> Export</h3>
                <p class="description">Unduh seluruh setting sebagai file JSON. Simpan untuk backup atau pindahkan ke environment lain.</p>
                <form method="post" action="<?php echo esc_url($adminPost); ?>">
                    <?php wp_nonce_field('rspku_settings_export'); ?>
                    <input type="hidden" name="action" value="rspku_settings_export">
                    <button type="submit" class="button button-primary">Download JSON</button>
                </form>
            </div>
            <div class="card rspku-tools-card">
                <h3><span class="dashicons dashicons-upload" aria-hidden="true"></span> Import</h3>
                <p class="description"><strong>Perhatian:</strong> Import akan menimpa semua setting saat ini. Pastikan sudah export backup lebih dulu.</p>
                <form method="post" action="<?php echo esc_url($adminPost); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('rspku_settings_import'); ?>
                    <input type="hidden" name="action" value="rspku_settings_import">
                    <input type="file" name="settings_file" accept="application/json,.json" required>
                    <button type="submit" class="button">Upload & Terapkan</button>
                </form>
            </div>
        </div>
        <?php
    }

    public static function handleExport(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die('Unauthorized', 'Forbidden', ['response' => 403]);
        }

        check_admin_referer('rspku_settings_export');

        $payload = [
            'exported_at' => gmdate('c'),
            'site_url' => home_url('/'),
            'plugin_version' => RSPKU_SETTINGS_VERSION,
            'settings' => get_option(RSPKU_SETTINGS_OPTION_KEY, []),
        ];

        $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            wp_die('Gagal meng-encode JSON.', 'Export Error', ['response' => 500]);
        }

        $filename = sprintf('rspku-settings-%s.json', gmdate('Ymd-His'));

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }

    public static function handleImport(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die('Unauthorized', 'Forbidden', ['response' => 403]);
        }

        check_admin_referer('rspku_settings_import');

        $redirect = admin_url('admin.php?page=' . self::MENU_SLUG . '&tab=tools');

        if (empty($_FILES['settings_file']['tmp_name']) || !is_uploaded_file($_FILES['settings_file']['tmp_name'])) {
            wp_safe_redirect(add_query_arg('rspku_import', 'missing', $redirect));
            exit;
        }

        $size = (int) ($_FILES['settings_file']['size'] ?? 0);
        if ($size <= 0 || $size > 1048576) {
            // Reject empty and >1 MB uploads; the real payload is ~5 KB.
            wp_safe_redirect(add_query_arg('rspku_import', 'too_big', $redirect));
            exit;
        }

        $contents = file_get_contents($_FILES['settings_file']['tmp_name']);
        if (!is_string($contents) || $contents === '') {
            wp_safe_redirect(add_query_arg('rspku_import', 'unreadable', $redirect));
            exit;
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            wp_safe_redirect(add_query_arg('rspku_import', 'invalid_json', $redirect));
            exit;
        }

        // Accept both wrapped (from our export) and flat payloads.
        $incoming = is_array($decoded['settings'] ?? null) ? $decoded['settings'] : $decoded;

        // Intersect with known keys to avoid persisting junk from a hand-
        // crafted file and run everything through the same sanitizer as
        // the admin form.
        $defaults = RSPKU_Settings_Defaults::all();
        $filtered = array_intersect_key($incoming, $defaults);
        $clean = self::sanitize($filtered);

        update_option(RSPKU_SETTINGS_OPTION_KEY, $clean);

        wp_safe_redirect(add_query_arg('rspku_import', 'ok', $redirect));
        exit;
    }

    private static function renderTabContent(array $tab, array $options, array $defaults): void
    {
        foreach ($tab['sections'] as $section_key => $section) {
            $field_count = count($section['fields'] ?? []);
            $empty_count = self::countSectionEmptyFields($section['fields'] ?? [], $options, $defaults);
            $completeness_class = $empty_count > 0 ? ' is-incomplete' : ' is-complete';
            $completeness_text = $empty_count > 0 ? sprintf('%d belum terisi', $empty_count) : 'Lengkap';
            $section_id = 'rspku-section-' . sanitize_html_class((string) $section_key);
            $section_body_id = $section_id . '-body';
            ?>
            <div class="card rspku-settings-section" id="<?php echo esc_attr($section_id); ?>" data-section-key="<?php echo esc_attr((string) $section_key); ?>">
                <div class="rspku-settings-section-header">
                    <div class="rspku-settings-section-title-row">
                        <div class="rspku-settings-section-title-group">
                            <h2><?php echo esc_html($section['title']); ?></h2>
                            <span class="rspku-settings-section-count"><?php echo esc_html(sprintf('%d field', $field_count)); ?></span>
                            <span class="rspku-settings-section-completeness<?php echo esc_attr($completeness_class); ?>" aria-label="<?php echo esc_attr('Info kelengkapan: ' . $completeness_text . '. Tetap bisa disimpan.'); ?>"><?php echo esc_html($completeness_text); ?></span>
                        </div>
                        <button type="button"
                                class="button-link rspku-settings-section-toggle"
                                aria-expanded="true"
                                aria-controls="<?php echo esc_attr($section_body_id); ?>">
                            Sembunyikan
                        </button>
                    </div>
                    <?php if (!empty($section['description'])): ?>
                        <p class="description"><?php echo esc_html($section['description']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="rspku-settings-section-body" id="<?php echo esc_attr($section_body_id); ?>">
                    <table class="form-table" role="presentation">
                        <tbody>
                    <?php for ($i = 0, $field_total = count($section['fields']); $i < $field_total; $i++): ?>
                        <?php
                        $field = $section['fields'][$i];
                        $next_field = $section['fields'][$i + 1] ?? null;
                        $key = $field['key'];
                        $value = $options[$key] ?? $defaults[$key] ?? '';
                        if (self::isCardStart($field)) {
                            $card_fields = [];
                            $card_key = (string) ($field['card'] ?? '');
                            for (; $i < $field_total; $i++) {
                                $card_field = $section['fields'][$i];
                                if (($card_field['card'] ?? '') !== $card_key) {
                                    $i--;
                                    break;
                                }
                                $card_fields[] = $card_field;
                            }
                            self::renderFieldCard($card_fields, $options, $defaults);
                            continue;
                        }
                        if (self::isPhonePairStart($field, $next_field)) {
                            $next_key = $next_field['key'];
                            self::renderPhonePair($field, $value, $next_field, $options[$next_key] ?? $defaults[$next_key] ?? '');
                            $i++;
                            continue;
                        }
                        if (self::isCtaPairStart($field, $next_field)) {
                            $next_key = $next_field['key'];
                            self::renderCtaPair($field, $value, $next_field, $options[$next_key] ?? $defaults[$next_key] ?? '');
                            $i++;
                            continue;
                        }
                        self::renderField($field, $value);
                        ?>
                    <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }
    }


    /**
     * Presentational only. Empty fields remain saveable and sanitizer remains authoritative.
     *
     * @param array<int,array<string,mixed>> $fields
     */
    private static function countSectionEmptyFields(array $fields, array $options, array $defaults): int
    {
        $empty = 0;

        foreach ($fields as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            if (self::isCompletenessEmpty($field, $options[$key] ?? $defaults[$key] ?? '')) {
                $empty++;
            }
        }

        return $empty;
    }

    private static function isCompletenessEmpty(array $field, mixed $value): bool
    {
        $type = (string) ($field['type'] ?? 'text');

        if ($type === 'toggle') {
            return false;
        }

        if ($type === 'image') {
            return (int) $value < 1;
        }

        if (is_array($value)) {
            return $value === [];
        }

        return trim((string) $value) === '';
    }

    private static function isPhonePairStart(array $field, mixed $nextField): bool
    {
        return is_array($nextField)
            && ($field['group'] ?? '') === 'call_center'
            && ($field['pair_role'] ?? '') === 'display'
            && ($nextField['group'] ?? '') === 'call_center'
            && ($nextField['pair'] ?? '') === ($field['pair'] ?? '')
            && ($nextField['pair_role'] ?? '') === 'tel';
    }

    private static function isCardStart(array $field): bool
    {
        return in_array((string) ($field['group'] ?? ''), ['promo_card', 'history_slot_card'], true)
            && ($field['card_role'] ?? '') === 'start'
            && !empty($field['card']);
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     */
    private static function renderFieldCard(array $fields, array $options, array $defaults): void
    {
        if ($fields === []) {
            return;
        }

        $first = $fields[0];
        $cardLabel = (string) ($first['card_label'] ?? $first['card'] ?? '');
        $cardClass = 'rspku-settings-field rspku-settings-field--card rspku-settings-card rspku-settings-card--' . sanitize_html_class((string) ($first['group'] ?? 'field'));
        ?>
        <tr class="<?php echo esc_attr($cardClass); ?> rspku-settings-field--card-heading" data-card="<?php echo esc_attr((string) ($first['card'] ?? '')); ?>">
            <th colspan="2"><?php echo esc_html($cardLabel); ?></th>
        </tr>
        <?php foreach ($fields as $field): ?>
            <?php
            $key = $field['key'];
            self::renderField($field, $options[$key] ?? $defaults[$key] ?? '');
            ?>
        <?php endforeach; ?>
        <?php
    }

    private static function renderPhonePair(array $displayField, mixed $displayValue, array $telField, mixed $telValue): void
    {
        $pairLabel = (string) ($displayField['pair'] ?? $displayField['label']);
        ?>
        <tr class="rspku-settings-field rspku-settings-field--phone-pair">
            <th scope="row"><?php echo esc_html($pairLabel); ?></th>
            <td class="rspku-settings-field__control rspku-call-pair">
                <?php self::renderPhonePairInput($displayField, $displayValue, 'Display'); ?>
                <?php self::renderPhonePairInput($telField, $telValue, 'tel: link'); ?>
            </td>
        </tr>
        <?php
    }

    private static function renderPhonePairInput(array $field, mixed $value, string $label): void
    {
        $key = $field['key'];
        $name = RSPKU_SETTINGS_OPTION_KEY . '[' . $key . ']';
        $id = 'rspku-' . $key;
        $descriptionId = $id . '-description';
        ?>
        <div class="rspku-call-pair__item">
            <label for="<?php echo esc_attr($id); ?>" class="rspku-call-pair__label"><?php echo esc_html($label); ?></label>
            <input type="text"
                   id="<?php echo esc_attr($id); ?>"
                   name="<?php echo esc_attr($name); ?>"
                   value="<?php echo esc_attr((string) $value); ?>"
                   class="regular-text rspku-settings-input"
                   <?php if (!empty($field['help'])): ?>aria-describedby="<?php echo esc_attr($descriptionId); ?>"<?php endif; ?>
                   >
            <?php if (!empty($field['help'])): ?>
                <p id="<?php echo esc_attr($descriptionId); ?>" class="rspku-settings-field__description rspku-call-pair__description"><?php echo esc_html((string) $field['help']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function isCtaPairStart(array $field, mixed $nextField): bool
    {
        return is_array($nextField)
            && ($field['group'] ?? '') === 'homepage_cta_pair'
            && ($field['pair_role'] ?? '') === 'text'
            && ($nextField['group'] ?? '') === 'homepage_cta_pair'
            && ($nextField['pair'] ?? '') === ($field['pair'] ?? '')
            && ($nextField['pair_role'] ?? '') === 'url';
    }

    private static function renderCtaPair(array $textField, mixed $textValue, array $urlField, mixed $urlValue): void
    {
        $pairLabel = (string) ($textField['pair'] ?? $textField['label']);
        $previewText = (string) $textValue;
        $previewUrl = (string) $urlValue;
        ?>
        <tr class="rspku-settings-field rspku-settings-field--cta-pair">
            <th scope="row"><?php echo esc_html($pairLabel); ?></th>
            <td class="rspku-settings-field__control">
                <div class="rspku-cta-pair">
                    <?php self::renderCtaPairInput($textField, $textValue, 'Teks tombol'); ?>
                    <?php self::renderCtaPairInput($urlField, $urlValue, 'URL tujuan'); ?>
                </div>
                <div class="rspku-cta-pair__preview" aria-label="Preview CTA tersimpan">
                    <span class="rspku-cta-pair__preview-label">Preview</span>
                    <?php if ($previewText !== '' && $previewUrl !== ''): ?>
                        <a href="<?php echo esc_url($previewUrl); ?>" target="_blank" rel="noopener noreferrer" class="button"><?php echo esc_html($previewText); ?></a>
                    <?php else: ?>
                        <span class="rspku-cta-pair__preview-empty">Lengkapi teks dan URL untuk melihat tombol.</span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    private static function renderCtaPairInput(array $field, mixed $value, string $label): void
    {
        $key = $field['key'];
        $name = RSPKU_SETTINGS_OPTION_KEY . '[' . $key . ']';
        $id = 'rspku-' . $key;
        $descriptionId = $id . '-description';
        ?>
        <div class="rspku-cta-pair__item">
            <label for="<?php echo esc_attr($id); ?>" class="rspku-cta-pair__label"><?php echo esc_html($label); ?></label>
            <input type="text"
                   id="<?php echo esc_attr($id); ?>"
                   name="<?php echo esc_attr($name); ?>"
                   value="<?php echo esc_attr((string) $value); ?>"
                   class="regular-text rspku-settings-input"
                   <?php if (!empty($field['help'])): ?>aria-describedby="<?php echo esc_attr($descriptionId); ?>"<?php endif; ?>
                   >
            <?php if (!empty($field['help'])): ?>
                <p id="<?php echo esc_attr($descriptionId); ?>" class="rspku-settings-field__description rspku-cta-pair__description"><?php echo esc_html((string) $field['help']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function renderField(array $field, mixed $value): void
    {
        $key = $field['key'];
        $name = RSPKU_SETTINGS_OPTION_KEY . '[' . $key . ']';
        $id = 'rspku-' . $key;
        $type = $field['type'] ?? 'text';
        $help = $field['help'] ?? '';
        $descriptionId = $id . '-description';
        $describedBy = $help ? ' aria-describedby="' . esc_attr($descriptionId) . '"' : '';

        $wrapperClass = 'rspku-settings-field rspku-settings-field--' . sanitize_html_class($type);
        ?>
        <tr class="<?php echo esc_attr($wrapperClass); ?>">
            <th scope="row">
                <label for="<?php echo esc_attr($id); ?>" class="rspku-settings-field__label"><?php echo esc_html($field['label']); ?></label>
            </th>
            <td class="rspku-settings-field__control">
            <?php if ($type === 'text' || $type === 'email' || $type === 'url'): ?>
                <input type="<?php echo esc_attr($type); ?>"
                       id="<?php echo esc_attr($id); ?>"
                       name="<?php echo esc_attr($name); ?>"
                       value="<?php echo esc_attr((string) $value); ?>"
                       class="regular-text rspku-settings-input"
                       <?php echo $describedBy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                       >

            <?php elseif ($type === 'textarea'): ?>
                <textarea id="<?php echo esc_attr($id); ?>"
                          name="<?php echo esc_attr($name); ?>"
                          rows="4"
                          class="large-text rspku-settings-textarea"
                          <?php echo $describedBy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                          ><?php echo esc_textarea((string) $value); ?></textarea>

            <?php elseif ($type === 'color'): ?>
                <input type="text"
                       id="<?php echo esc_attr($id); ?>"
                       name="<?php echo esc_attr($name); ?>"
                       value="<?php echo esc_attr((string) $value); ?>"
                       class="rspku-color-picker"
                       <?php echo $describedBy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

            <?php elseif ($type === 'toggle'): ?>
                <label class="rspku-settings-checkbox-label">
                    <input type="checkbox"
                           id="<?php echo esc_attr($id); ?>"
                           name="<?php echo esc_attr($name); ?>"
                           value="1"
                           <?php echo $describedBy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                           <?php checked((bool) $value); ?>>
                    <span class="rspku-settings-toggle-status" aria-hidden="true">
                        <span class="rspku-settings-toggle-status__on">Aktif</span>
                        <span class="rspku-settings-toggle-status__off">Nonaktif</span>
                    </span>
                </label>

            <?php elseif ($type === 'image'): ?>
                <?php
                $image_id = absint($value);
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
                ?>
                <div class="rspku-image-upload" data-field-id="<?php echo esc_attr($id); ?>"<?php echo $describedBy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                    <div class="rspku-image-preview <?php echo $image_url ? '' : 'hidden'; ?>" aria-live="polite">
                        <img src="<?php echo esc_url($image_url ?: ''); ?>" alt=""
                             class="rspku-image-preview-img">
                        <p class="rspku-image-status">
                            Gambar terpilih. Simpan pengaturan untuk menerapkan perubahan.
                        </p>
                        <button type="button"
                                class="rspku-image-remove"
                                aria-label="Hapus gambar <?php echo esc_attr((string) $field['label']); ?>">
                            Hapus gambar
                        </button>
                    </div>
                    <input type="hidden" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((string) $image_id); ?>">
                    <p class="rspku-image-empty <?php echo $image_url ? 'hidden' : ''; ?>">
                        Belum ada gambar. Nilai tersimpan tetap aman sebagai ID lampiran.
                    </p>
                    <button type="button"
                            class="button rspku-image-select <?php echo $image_url ? 'hidden' : ''; ?>"
                            aria-label="Pilih gambar <?php echo esc_attr((string) $field['label']); ?> dari Media Library">
                        Pilih gambar dari Media Library
                    </button>
                </div>

            <?php elseif ($type === 'info'): ?>
                <?php if ($key === 'logo_note'): ?>
                    <div class="rspku-info-card">
                        <p class="description"><?php echo esc_html($help); ?></p>
                        <a href="<?php echo esc_url(admin_url('customize.php?autofocus[control]=custom_logo')); ?>" class="button">
                            <span class="dashicons dashicons-format-image" aria-hidden="true"></span>
                            Ubah Logo
                        </a>
                    </div>
                <?php else: ?>
                    <p class="description"><?php echo esc_html($help); ?></p>
                <?php endif; ?>
                <?php $help = ''; ?>

            <?php elseif ($type === 'export_import'): ?>
                <?php self::renderExportImportField(); ?>
                <?php $help = ''; ?>

            <?php elseif ($type === 'repeater_hours'): ?>
                <div class="rspku-repeater rspku-repeater--hours" data-field="<?php echo esc_attr($key); ?>">
                    <div class="rspku-repeater-header" aria-hidden="true">
                        <span>Unit Layanan</span>
                        <span>Jam Operasional</span>
                        <span>Utama</span>
                        <span>Aksi</span>
                    </div>
                    <?php
                    $rows = is_array($value) ? $value : [];
                    if ($rows === []):
                    ?>
                        <p class="rspku-repeater-empty">Belum ada jam operasional. Tambahkan baris untuk mulai mengisi.</p>
                    <?php
                    endif;
                    foreach ($rows as $i => $row):
                    ?>
                        <div class="rspku-repeater-row rspku-repeater-row--hours">
                            <label class="rspku-repeater-cell">
                                <span class="rspku-repeater-cell__label">Unit Layanan</span>
                                <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][label]"
                                       value="<?php echo esc_attr((string) ($row['label'] ?? '')); ?>"
                                       placeholder="IGD"
                                       class="regular-text">
                            </label>
                            <label class="rspku-repeater-cell">
                                <span class="rspku-repeater-cell__label">Jam Operasional</span>
                                <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][time]"
                                       value="<?php echo esc_attr((string) ($row['time'] ?? '')); ?>"
                                       placeholder="24 Jam"
                                       class="regular-text">
                            </label>
                            <label class="rspku-repeater-highlight">
                                <input type="checkbox" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][highlight]" value="1" <?php checked(!empty($row['highlight'])); ?>>
                                <span>Tampilkan sebagai utama</span>
                            </label>
                            <button type="button" class="button-link-delete rspku-repeater-remove" aria-label="Hapus jam operasional">Hapus</button>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="button rspku-repeater-add" data-name="<?php echo esc_attr($name); ?>">+ Tambah jam operasional</button>
                </div>

            <?php elseif ($type === 'repeater_links'): ?>
                <div class="rspku-repeater" data-field="<?php echo esc_attr($key); ?>">
                    <?php
                    $rows = is_array($value) ? $value : [];
                    foreach ($rows as $i => $row):
                    ?>
                        <div class="rspku-repeater-row rspku-repeater-row--links">
                            <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][label]"
                                   value="<?php echo esc_attr((string) ($row['label'] ?? '')); ?>"
                                   placeholder="Label (mis. Dokter)"
                                   aria-label="Label link cepat"
                                   class="regular-text">
                            <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][url]"
                                   value="<?php echo esc_attr((string) ($row['url'] ?? '')); ?>"
                                   placeholder="URL (mis. /dokter/)"
                                   aria-label="URL link cepat"
                                   class="regular-text">
                            <button type="button" class="button-link-delete rspku-repeater-remove" aria-label="Hapus link cepat">Hapus</button>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="button rspku-repeater-add-link" data-name="<?php echo esc_attr($name); ?>">+ Tambah link</button>
                </div>

            <?php elseif ($type === 'post_picker'): ?>
                <?php
                $postType = $field['post_type'] ?? 'post';
                $maxItems = (int) ($field['max'] ?? 6);
                $selectedIds = is_array($value) ? array_map('absint', $value) : [];
                $allPosts = get_posts([
                    'post_type' => $postType,
                    'post_status' => 'publish',
                    'posts_per_page' => 50,
                    'orderby' => 'title',
                    'order' => 'ASC',
                ]);
                ?>
                <div class="rspku-checkbox-picker">
                    <div class="rspku-checkbox-picker-header">
                        <p id="<?php echo esc_attr($key); ?>-picker-hint" class="rspku-checkbox-picker-hint">
                            <?php echo count($selectedIds); ?> terpilih dari maksimal <?php echo $maxItems; ?> item. Centang layanan/dokter yang tampil di homepage.
                        </p>
                        <span class="rspku-checkbox-picker-count"><?php echo count($allPosts); ?> tersedia</span>
                    </div>
                    <div class="rspku-checkbox-picker-grid" aria-describedby="<?php echo esc_attr($key); ?>-picker-hint">
                        <?php foreach ($allPosts as $p): ?>
                            <?php $isSelected = in_array((int) $p->ID, $selectedIds, true); ?>
                            <label class="rspku-checkbox-picker-item<?php echo $isSelected ? ' is-selected' : ''; ?>">
                                <input type="checkbox"
                                       name="<?php echo esc_attr($name); ?>[]"
                                       value="<?php echo (int) $p->ID; ?>"
                                       <?php checked($isSelected); ?>>
                                <span class="rspku-checkbox-picker-label"><?php echo esc_html(get_the_title($p)); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($allPosts)): ?>
                        <p class="description">Belum ada <?php echo esc_html($postType); ?> yang dipublikasikan.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($type === 'review_repeater'): ?>
                <div class="rspku-repeater rspku-review-repeater" data-field="<?php echo esc_attr($key); ?>">
                    <?php
                    $rows = is_array($value) ? $value : [];
                    foreach ($rows as $i => $row):
                    ?>
                        <div class="rspku-repeater-row rspku-repeater-row--review">
                            <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][name]"
                                   value="<?php echo esc_attr((string) ($row['name'] ?? '')); ?>"
                                   placeholder="Nama reviewer"
                                   aria-label="Nama reviewer"
                                   class="regular-text">
                            <select name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][rating]"
                                    aria-label="Rating ulasan"
                                    class="regular-text">
                                <?php for ($r = 5; $r >= 1; $r--): ?>
                                    <option value="<?php echo $r; ?>" <?php selected((int) ($row['rating'] ?? 5), $r); ?>><?php echo $r; ?> ★</option>
                                <?php endfor; ?>
                            </select>
                            <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][date_label]"
                                   value="<?php echo esc_attr((string) ($row['date_label'] ?? '')); ?>"
                                   placeholder="Bulan Tahun"
                                   aria-label="Bulan dan tahun ulasan"
                                   class="regular-text">
                            <textarea name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][excerpt]"
                                      rows="2"
                                      placeholder="Kutipan ulasan..."
                                      aria-label="Kutipan ulasan"
                                      class="large-text"><?php echo esc_textarea((string) ($row['excerpt'] ?? '')); ?></textarea>
                            <button type="button" class="button-link-delete rspku-repeater-remove" aria-label="Hapus ulasan">Hapus</button>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="button rspku-repeater-add-review" data-name="<?php echo esc_attr($name); ?>">+ Tambah ulasan</button>
                </div>

            <?php endif; ?>

            <?php if ($help): ?>
                <p id="<?php echo esc_attr($descriptionId); ?>" class="rspku-settings-field__description"><?php echo esc_html($help); ?></p>
            <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}
