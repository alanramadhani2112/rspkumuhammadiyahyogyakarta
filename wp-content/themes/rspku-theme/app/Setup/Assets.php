<?php

declare(strict_types=1);

namespace Rspku\Setup;

final class Assets
{
    private const FRONT_HANDLE = 'rspku-theme-app';
    private const ADMIN_HANDLE = 'rspku-theme-admin';
    private const EDITOR_HANDLE = 'rspku-theme-editor-blocks';

    /**
     * @var array<int,string>
     */
    private static array $assetWarnings = [];

    public static function register(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontend']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdmin']);
        add_action('admin_notices', [self::class, 'displayAssetWarnings']);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueEditor']);
        add_filter('script_loader_tag', [self::class, 'useModuleScripts'], 10, 3);
    }

    public static function displayAssetWarnings(): void
    {
        if (!current_user_can('manage_options') && !current_user_can('switch_themes')) {
            return;
        }

        self::manifestEntry('resources/js/app.js');

        foreach (array_unique(self::$assetWarnings) as $message) {
            printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($message));
        }
    }

    public static function enqueueFrontend(): void
    {
        // wp_enqueue_scripts normally doesn't fire for REST/AJAX requests,
        // but some plugins (e.g. preview renderers) invoke do_action on it
        // out of the usual flow. Skip those cases — the frontend bundle is
        // only meaningful for rendered HTML responses.
        if (self::isNonRenderedRequest()) {
            return;
        }

        self::enqueueAsset(self::FRONT_HANDLE, 'resources/js/app.js');
    }

    private static function isNonRenderedRequest(): bool
    {
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return true;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return true;
        }

        return false;
    }

    public static function enqueueAdmin(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) {
            return;
        }

        // Only load admin JS on edit screens for our custom post types.
        // Other admin pages (dashboard, settings, tools) don't need it.
        $relevantTypes = ['dokter', 'layanan', 'poliklinik', 'jurnal', 'rawat-inap', 'manajemen-rs'];
        $relevantBases = ['post', 'post-new'];

        if (!in_array((string) $screen->post_type, $relevantTypes, true)) {
            return;
        }

        if (!in_array((string) $screen->base, $relevantBases, true)) {
            return;
        }

        self::enqueueAsset(self::ADMIN_HANDLE, 'resources/js/admin.js');
    }

    public static function enqueueEditor(): void
    {
        self::enqueueAsset(self::EDITOR_HANDLE, 'resources/js/editor-blocks.js', [
            'wp-blocks',
            'wp-block-editor',
            'wp-components',
            'wp-element',
            'wp-i18n',
            'wp-server-side-render',
        ]);
    }

    /**
     * Convert our Vite-built scripts to ES modules. WordPress core does
     * not natively support `type="module"` on enqueued scripts (the
     * Script Modules API is a separate system). This filter is the
     * standard community approach for Vite-based themes.
     *
     * Guards:
     * - Only touches our own handles (no third-party interference).
     * - Skips admin handle (admin.js is plain jQuery, not a module).
     * - Skips if `type=` is already present (avoids double-injection
     *   from caching plugins that rewrite script tags).
     * - Skips `nomodule` fallback tags.
     */
    public static function useModuleScripts(string $tag, string $handle, string $src): string
    {
        // Admin JS is plain jQuery — not an ES module. Skip conversion.
        if ($handle === self::ADMIN_HANDLE) {
            return $tag;
        }

        if (!in_array($handle, [self::FRONT_HANDLE, self::EDITOR_HANDLE], true)) {
            return $tag;
        }

        if (str_contains($tag, 'type=') || str_contains($tag, 'nomodule')) {
            return $tag;
        }

        // Build a clean module tag. Using a manual sprintf avoids issues
        // with regex replacements on malformed tags from other filters.
        return sprintf(
            '<script type="module" src="%s" id="%s-js"></script>' . "\n",
            esc_url($src),
            esc_attr($handle)
        );
    }

    /**
     * @param array<int,string> $dependencies
     */
    private static function enqueueAsset(string $handle, string $entry, array $dependencies = []): void
    {
        $asset = self::manifestEntry($entry);
        if ($asset === null) {
            return;
        }

        if (!empty($asset['file'])) {
            wp_enqueue_script(
                $handle,
                self::assetUrl((string) $asset['file']),
                $dependencies,
                self::assetVersion((string) $asset['file']),
                true
            );
        }

        if (!empty($asset['css']) && is_array($asset['css'])) {
            foreach ($asset['css'] as $index => $cssFile) {
                wp_enqueue_style(
                    $handle . '-style-' . (string) $index,
                    self::assetUrl((string) $cssFile),
                    [],
                    self::assetVersion((string) $cssFile)
                );
            }
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function manifestEntry(string $entry): ?array
    {
        static $manifest = null;

        if ($manifest === null) {
            $manifestPath = RSPKU_THEME_PATH . '/public/build/.vite/manifest.json';
            if (!file_exists($manifestPath)) {
                self::reportMissingAsset('RSPKU theme Vite manifest is missing: ' . $manifestPath);
                return null;
            }

            $json = file_get_contents($manifestPath);
            $manifest = is_string($json) ? json_decode($json, true) : null;

            if (!is_array($manifest)) {
                self::reportMissingAsset('RSPKU theme Vite manifest is invalid: ' . $manifestPath);
                return null;
            }
        }

        if (!isset($manifest[$entry]) || !is_array($manifest[$entry])) {
            self::reportMissingAsset('RSPKU theme Vite manifest entry is missing: ' . $entry);
            return null;
        }

        return $manifest[$entry];
    }

    private static function reportMissingAsset(string $message): void
    {
        if (in_array($message, self::$assetWarnings, true)) {
            return;
        }

        self::$assetWarnings[] = $message;
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log($message);
    }

    private static function assetUrl(string $file): string
    {
        return trailingslashit(RSPKU_THEME_URL . '/public/build') . ltrim($file, '/');
    }

    private static function assetVersion(string $file): string
    {
        $path = RSPKU_THEME_PATH . '/public/build/' . ltrim($file, '/');
        return file_exists($path) ? (string) filemtime($path) : RSPKU_THEME_VERSION;
    }
}
