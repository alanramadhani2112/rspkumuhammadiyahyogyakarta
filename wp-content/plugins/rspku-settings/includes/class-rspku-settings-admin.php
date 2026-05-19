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
                $clean[$key] = esc_url_raw((string) $value);
            } elseif ($key === 'email') {
                $clean[$key] = sanitize_email((string) $value);
            } elseif (str_starts_with($key, 'brand_color_')) {
                $clean[$key] = sanitize_hex_color((string) $value) ?: ($hasStored ? (string) $stored[$key] : (string) $default);
            } elseif (self::isImageField($key)) {
                $clean[$key] = absint($value);
            } elseif ($key === 'hero_title' || $key === 'hero_description') {
                $clean[$key] = wp_kses_post((string) $value);
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
        ];

        return in_array($key, $urlKeys, true);
    }

    /**
     * Keys that store a WordPress attachment ID. Centralising this list
     * keeps the sanitize switch and the Timber context resolver in sync.
     */
    private static function isImageField(string $key): bool
    {
        return in_array($key, self::imageKeys(), true);
    }

    /**
     * @return list<string>
     */
    public static function imageKeys(): array
    {
        return [
            'hero_image_id',
            'home_feature_image',
            'home_cta_image',
            'image_dokter_archive',
            'image_fasilitas',
            'image_berita',
            'image_poliklinik',
            'image_layanan',
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

        $options = get_option(RSPKU_SETTINGS_OPTION_KEY, []);
        $defaults = RSPKU_Settings_Defaults::all();
        ?>
        <div class="wrap rspku-settings-wrap">
            <div class="rspku-settings-header">
                <h1>
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-right:8px;flex-shrink:0;opacity:.9"><path d="M22 14h-4.5a2.5 2.5 0 0 0 0 5H20"/><path d="M12 21V10"/><path d="M2 21V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/><path d="M2 10h20"/><path d="M8 14v2"/><path d="M16 14v2"/></svg>
                    RS PKU Settings
                </h1>
                <p class="description">Kelola semua konfigurasi theme RS PKU Muhammadiyah Yogyakarta dari satu tempat. Perubahan langsung berlaku setelah klik Simpan.</p>
            </div>

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

            <nav class="rspku-settings-tabs nav-tab-wrapper">
                <?php foreach ($tabs as $tab_key => $tab): ?>
                    <a href="?page=<?php echo esc_attr(self::MENU_SLUG); ?>&tab=<?php echo esc_attr($tab_key); ?>"
                       class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                        <?php echo esc_html($tab['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="rspku-settings-form">
                <?php wp_nonce_field('rspku_settings_save', '_rspku_nonce'); ?>
                <input type="hidden" name="action" value="rspku_settings_save">
                <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">

                <?php self::renderTabContent($tabs[$active_tab], $options, $defaults); ?>

                <div class="rspku-settings-actions">
                    <?php if ($active_tab !== 'tools'): ?>
                        <?php submit_button('Simpan Perubahan', 'primary large', 'submit', false); ?>
                    <?php else: ?>
                        <p class="description" style="margin:0;flex:1;">Gunakan tombol Export/Import di atas. Tab ini tidak menyimpan perubahan form utama.</p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="button button-secondary">
                        <span class="dashicons dashicons-external"></span> Lihat Situs
                    </a>
                </div>
            </form>
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
        <div class="rs-grid rs-gap-4 rs-max-w-2xl" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
            <div class="rs-p-4 rs-bg-white rs-border rs-border-slate-200 rs-rounded-lg">
                <h3 class="rs-flex rs-items-center rs-gap-1.5 rs-m-0 rs-mb-2 rs-text-sm rs-font-bold rs-text-slate-900">
                    <span class="dashicons dashicons-download rs-text-brand-600" style="font-size:16px;width:16px;height:16px;"></span>
                    Export
                </h3>
                <p class="rs-m-0 rs-mb-3 rs-text-xs rs-text-slate-500 rs-leading-relaxed">
                    Unduh seluruh setting sebagai file JSON. Simpan untuk backup atau pindahkan ke environment lain.
                </p>
                <form method="post" action="<?php echo esc_url($adminPost); ?>" class="rs-flex rs-flex-col rs-gap-2 rs-items-start">
                    <?php wp_nonce_field('rspku_settings_export'); ?>
                    <input type="hidden" name="action" value="rspku_settings_export">
                    <button type="submit" class="button button-primary rs-text-xs">Download JSON</button>
                </form>
            </div>
            <div class="rs-p-4 rs-bg-white rs-border rs-border-slate-200 rs-rounded-lg">
                <h3 class="rs-flex rs-items-center rs-gap-1.5 rs-m-0 rs-mb-2 rs-text-sm rs-font-bold rs-text-slate-900">
                    <span class="dashicons dashicons-upload rs-text-brand-600" style="font-size:16px;width:16px;height:16px;"></span>
                    Import
                </h3>
                <p class="rs-m-0 rs-mb-3 rs-text-xs rs-text-slate-500 rs-leading-relaxed">
                    <strong>Perhatian:</strong> Import akan menimpa semua setting saat ini. Pastikan sudah export backup lebih dulu.
                </p>
                <form method="post" action="<?php echo esc_url($adminPost); ?>" enctype="multipart/form-data" class="rs-flex rs-flex-col rs-gap-2 rs-items-start">
                    <?php wp_nonce_field('rspku_settings_import'); ?>
                    <input type="hidden" name="action" value="rspku_settings_import">
                    <input type="file" name="settings_file" accept="application/json,.json" required class="rs-text-xs rs-max-w-full">
                    <button type="submit" class="button button-secondary rs-text-xs">Upload & Terapkan</button>
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
            ?>
            <div class="rspku-settings-section">
                <div class="rspku-settings-section-header">
                    <h2><?php echo esc_html($section['title']); ?></h2>
                    <?php if (!empty($section['description'])): ?>
                        <p class="description"><?php echo esc_html($section['description']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="rspku-settings-section-body">
                    <?php foreach ($section['fields'] as $field): ?>
                        <?php
                        $key = $field['key'];
                        $value = $options[$key] ?? $defaults[$key] ?? '';
                        self::renderField($field, $value);
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
    }

    private static function renderField(array $field, mixed $value): void
    {
        $key = $field['key'];
        $name = RSPKU_SETTINGS_OPTION_KEY . '[' . $key . ']';
        $id = 'rspku-' . $key;
        $type = $field['type'] ?? 'text';
        $help = $field['help'] ?? '';

        // Field wrapper: 2-col grid on md+, stack on mobile
        $isToggle = $type === 'toggle';
        $wrapperClass = 'rs-grid rs-gap-4 rs-items-start'
            . ($isToggle ? ' rs-grid-cols-[200px_1fr] rs-items-center' : ' rs-grid-cols-[200px_1fr]');
        ?>
        <div class="<?php echo esc_attr($wrapperClass); ?>">
            <label for="<?php echo esc_attr($id); ?>"
                   class="rs-text-xs rs-font-semibold rs-text-slate-700 rs-pt-2" style="letter-spacing:0.01em;">
                <?php echo esc_html($field['label']); ?>
            </label>

            <div class="rs-min-w-0">
            <?php if ($type === 'text' || $type === 'email' || $type === 'url'): ?>
                <input type="<?php echo esc_attr($type); ?>"
                       id="<?php echo esc_attr($id); ?>"
                       name="<?php echo esc_attr($name); ?>"
                       value="<?php echo esc_attr((string) $value); ?>"
                       class="rs-w-full rs-max-w-lg rs-border rs-border-slate-300 rs-rounded-md rs-px-3 rs-py-2 rs-text-sm rs-bg-white rs-text-slate-900 focus:rs-outline-none focus:rs-border-brand-600"
                       style="box-shadow: none; transition: border-color 0.15s;">

            <?php elseif ($type === 'textarea'): ?>
                <textarea id="<?php echo esc_attr($id); ?>"
                          name="<?php echo esc_attr($name); ?>"
                          rows="4"
                          class="rs-w-full rs-max-w-lg rs-border rs-border-slate-300 rs-rounded-md rs-px-3 rs-py-2 rs-text-sm rs-bg-white rs-text-slate-900 focus:rs-outline-none focus:rs-border-brand-600"
                          style="box-shadow: none; transition: border-color 0.15s; resize: vertical;"><?php echo esc_textarea((string) $value); ?></textarea>

            <?php elseif ($type === 'color'): ?>
                <input type="text"
                       id="<?php echo esc_attr($id); ?>"
                       name="<?php echo esc_attr($name); ?>"
                       value="<?php echo esc_attr((string) $value); ?>"
                       class="rspku-color-picker">

            <?php elseif ($type === 'toggle'): ?>
                <label class="rs-inline-flex rs-items-center rs-gap-3 rs-cursor-pointer rs-select-none">
                    <input type="checkbox"
                           id="<?php echo esc_attr($id); ?>"
                           name="<?php echo esc_attr($name); ?>"
                           value="1"
                           class="rs-sr-only rs-peer"
                           <?php checked((bool) $value); ?>>
                    <span class="rs-relative rs-inline-block rs-w-14 rs-h-7 rs-rounded-full rs-border rs-border-slate-300 rs-bg-slate-200
                                 peer-checked:rs-bg-brand-600 peer-checked:rs-border-brand-700
                                 peer-focus-visible:rs-outline peer-focus-visible:rs-outline-2 peer-focus-visible:rs-outline-brand-600 peer-focus-visible:rs-outline-offset-2"
                          style="transition: background 0.18s ease, border-color 0.18s ease;">
                        <span class="rs-absolute rs-top-0.5 rs-left-0.5 rs-w-6 rs-h-6 rs-rounded-full rs-bg-white"
                              style="box-shadow: 0 2px 6px rgba(15,23,42,0.18); transition: transform 0.18s ease;"
                              x-bind:style="'transform: translateX(' + ($el.closest('label').querySelector('input').checked ? '28px' : '0') + ')'">
                        </span>
                    </span>
                    <span class="rs-text-xs rs-font-semibold rs-text-slate-500 peer-checked:rs-text-brand-600 rs-min-w-[52px]"
                          id="<?php echo esc_attr($id); ?>-label">
                        <?php echo (bool) $value ? 'Aktif' : 'Nonaktif'; ?>
                    </span>
                </label>
                <script>
                (function(){
                    var cb = document.getElementById('<?php echo esc_js($id); ?>');
                    if (!cb) return;
                    var lbl = document.getElementById('<?php echo esc_js($id); ?>-label');
                    var knob = cb.closest('label').querySelector('span > span');
                    function sync() {
                        if (lbl) lbl.textContent = cb.checked ? 'Aktif' : 'Nonaktif';
                        if (knob) knob.style.transform = 'translateX(' + (cb.checked ? '28px' : '0') + ')';
                    }
                    cb.addEventListener('change', sync);
                })();
                </script>

            <?php elseif ($type === 'image'): ?>
                <?php
                $image_id = absint($value);
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
                ?>
                <div class="rspku-image-upload rs-max-w-xs" data-field-id="<?php echo esc_attr($id); ?>">
                    <div class="rspku-image-preview rs-mb-2 <?php echo $image_url ? '' : 'hidden'; ?>">
                        <img src="<?php echo esc_url($image_url ?: ''); ?>" alt=""
                             class="rs-max-w-[260px] rs-max-h-40 rs-object-cover rs-rounded-md rs-border rs-border-slate-200">
                        <button type="button"
                                class="rspku-image-remove rs-mt-1.5 rs-block rs-text-xs rs-text-red-600 rs-cursor-pointer rs-bg-transparent rs-border-0 rs-p-0">
                            Hapus gambar
                        </button>
                    </div>
                    <input type="hidden" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((string) $image_id); ?>">
                    <button type="button"
                            class="button rspku-image-select <?php echo $image_url ? 'hidden' : ''; ?>">
                        Pilih Gambar
                    </button>
                </div>

            <?php elseif ($type === 'info'): ?>
                <?php if ($key === 'logo_note'): ?>
                    <div class="rs-flex rs-flex-col rs-gap-2 rs-p-3 rs-bg-green-50 rs-border rs-border-green-200 rs-rounded-md rs-max-w-sm">
                        <p class="rs-m-0 rs-text-xs rs-text-slate-600 rs-italic"><?php echo esc_html($help); ?></p>
                        <a href="<?php echo esc_url(admin_url('customize.php?autofocus[control]=custom_logo')); ?>"
                           class="button button-secondary rs-self-start rs-inline-flex rs-items-center rs-gap-1 rs-text-xs">
                            <span class="dashicons dashicons-format-image" style="font-size:14px;width:14px;height:14px;line-height:1.8;"></span>
                            Ubah Logo
                        </a>
                    </div>
                <?php else: ?>
                    <p class="rs-m-0 rs-text-xs rs-text-slate-500 rs-italic rs-pt-1"><?php echo esc_html($help); ?></p>
                <?php endif; ?>
                <?php $help = ''; ?>

            <?php elseif ($type === 'export_import'): ?>
                <?php self::renderExportImportField(); ?>
                <?php $help = ''; ?>

            <?php elseif ($type === 'repeater_hours'): ?>
                <div class="rspku-repeater rs-flex rs-flex-col rs-gap-1.5 rs-max-w-xl" data-field="<?php echo esc_attr($key); ?>">
                    <?php
                    $rows = is_array($value) ? $value : [];
                    foreach ($rows as $i => $row):
                    ?>
                        <div class="rspku-repeater-row rs-grid rs-gap-1.5 rs-items-center rs-p-2 rs-bg-slate-50 rs-border rs-border-slate-200 rs-rounded-md"
                             style="grid-template-columns: 1.5fr 1fr auto auto;">
                            <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][label]"
                                   value="<?php echo esc_attr((string) ($row['label'] ?? '')); ?>"
                                   placeholder="Label (mis. IGD)"
                                   class="rs-border rs-border-slate-300 rs-rounded rs-px-2 rs-py-1.5 rs-text-xs rs-bg-white">
                            <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][time]"
                                   value="<?php echo esc_attr((string) ($row['time'] ?? '')); ?>"
                                   placeholder="Waktu (mis. 24 Jam)"
                                   class="rs-border rs-border-slate-300 rs-rounded rs-px-2 rs-py-1.5 rs-text-xs rs-bg-white">
                            <label class="rs-inline-flex rs-items-center rs-gap-1 rs-text-xs rs-text-slate-500 rs-whitespace-nowrap">
                                <input type="checkbox" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][highlight]" value="1" <?php checked(!empty($row['highlight'])); ?>>
                                <span>Highlight</span>
                            </label>
                            <button type="button" class="button-link-delete rspku-repeater-remove rs-text-red-600 rs-text-xs rs-cursor-pointer rs-whitespace-nowrap">Hapus</button>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="button rspku-repeater-add rs-self-start rs-text-xs rs-mt-1" data-name="<?php echo esc_attr($name); ?>">+ Tambah baris</button>
                </div>

            <?php elseif ($type === 'repeater_links'): ?>
                <div class="rspku-repeater rs-flex rs-flex-col rs-gap-1.5 rs-max-w-xl" data-field="<?php echo esc_attr($key); ?>">
                    <?php
                    $rows = is_array($value) ? $value : [];
                    foreach ($rows as $i => $row):
                    ?>
                        <div class="rspku-repeater-row rspku-repeater-row--links rs-grid rs-gap-1.5 rs-items-center rs-p-2 rs-bg-slate-50 rs-border rs-border-slate-200 rs-rounded-md"
                             style="grid-template-columns: 1fr 1.5fr auto;">
                            <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][label]"
                                   value="<?php echo esc_attr((string) ($row['label'] ?? '')); ?>"
                                   placeholder="Label (mis. Dokter)"
                                   class="rs-border rs-border-slate-300 rs-rounded rs-px-2 rs-py-1.5 rs-text-xs rs-bg-white">
                            <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][url]"
                                   value="<?php echo esc_attr((string) ($row['url'] ?? '')); ?>"
                                   placeholder="URL (mis. /dokter/)"
                                   class="rs-border rs-border-slate-300 rs-rounded rs-px-2 rs-py-1.5 rs-text-xs rs-bg-white">
                            <button type="button" class="button-link-delete rspku-repeater-remove rs-text-red-600 rs-text-xs rs-cursor-pointer">Hapus</button>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="button rspku-repeater-add-link rs-self-start rs-text-xs rs-mt-1" data-name="<?php echo esc_attr($name); ?>">+ Tambah link</button>
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
                <div class="rs-max-w-xl">
                    <p class="rs-m-0 rs-mb-2 rs-text-xs rs-font-medium rs-text-slate-500">
                        Pilih maksimal <?php echo $maxItems; ?> item. Centang untuk menampilkan di homepage.
                    </p>
                    <div class="rs-grid rs-gap-1 rs-max-h-72 rs-overflow-y-auto rs-border rs-border-slate-200 rs-rounded-md rs-p-2 rs-bg-slate-50"
                         style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                        <?php foreach ($allPosts as $p): ?>
                            <label class="rs-flex rs-items-center rs-gap-2 rs-px-2 rs-py-1.5 rs-rounded rs-cursor-pointer hover:rs-bg-green-50 rs-transition-colors">
                                <input type="checkbox"
                                       name="<?php echo esc_attr($name); ?>[]"
                                       value="<?php echo (int) $p->ID; ?>"
                                       class="rs-flex-shrink-0"
                                       <?php checked(in_array((int) $p->ID, $selectedIds, true)); ?>>
                                <span class="rs-text-xs rs-text-slate-700 rs-leading-snug"><?php echo esc_html(get_the_title($p)); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($allPosts)): ?>
                        <p class="rs-mt-2 rs-text-xs rs-text-slate-400">Belum ada <?php echo esc_html($postType); ?> yang dipublikasikan.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($type === 'review_repeater'): ?>
                <div class="rspku-repeater rspku-review-repeater rs-flex rs-flex-col rs-gap-2 rs-max-w-2xl" data-field="<?php echo esc_attr($key); ?>">
                    <?php
                    $rows = is_array($value) ? $value : [];
                    foreach ($rows as $i => $row):
                    ?>
                        <div class="rspku-repeater-row rspku-repeater-row--review rs-p-3 rs-bg-slate-50 rs-border rs-border-slate-200 rs-rounded-md rs-grid rs-gap-2"
                             style="grid-template-columns: 1.2fr 70px 1fr 2fr auto; align-items: start;">
                            <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][name]"
                                   value="<?php echo esc_attr((string) ($row['name'] ?? '')); ?>"
                                   placeholder="Nama reviewer"
                                   class="rs-border rs-border-slate-300 rs-rounded rs-px-2 rs-py-1.5 rs-text-xs rs-bg-white rs-w-full">
                            <select name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][rating]"
                                    class="rs-border rs-border-slate-300 rs-rounded rs-px-1.5 rs-py-1.5 rs-text-xs rs-bg-white rs-w-full">
                                <?php for ($r = 5; $r >= 1; $r--): ?>
                                    <option value="<?php echo $r; ?>" <?php selected((int) ($row['rating'] ?? 5), $r); ?>><?php echo $r; ?> ★</option>
                                <?php endfor; ?>
                            </select>
                            <input type="text" name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][date_label]"
                                   value="<?php echo esc_attr((string) ($row['date_label'] ?? '')); ?>"
                                   placeholder="Bulan Tahun"
                                   class="rs-border rs-border-slate-300 rs-rounded rs-px-2 rs-py-1.5 rs-text-xs rs-bg-white rs-w-full">
                            <textarea name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][excerpt]"
                                      rows="2"
                                      placeholder="Kutipan ulasan..."
                                      class="rs-border rs-border-slate-300 rs-rounded rs-px-2 rs-py-1.5 rs-text-xs rs-bg-white rs-w-full rs-resize-y"><?php echo esc_textarea((string) ($row['excerpt'] ?? '')); ?></textarea>
                            <button type="button" class="button-link-delete rspku-repeater-remove rs-text-red-600 rs-text-xs rs-cursor-pointer rs-whitespace-nowrap rs-self-start rs-mt-1">Hapus</button>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="button rspku-repeater-add-review rs-self-start rs-text-xs rs-mt-1" data-name="<?php echo esc_attr($name); ?>">+ Tambah ulasan</button>
                </div>

            <?php endif; ?>

            <?php if ($help): ?>
                <p class="rs-mt-1 rs-mb-0 rs-text-xs rs-text-slate-400 rs-leading-relaxed"><?php echo esc_html($help); ?></p>
            <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
