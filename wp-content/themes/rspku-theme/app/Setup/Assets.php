<?php

declare(strict_types=1);

namespace Rspku\Setup;

final class Assets
{
    private const FRONT_HANDLE = 'rspku-theme-app';
    private const ADMIN_HANDLE = 'rspku-theme-admin';
    private const EDITOR_HANDLE = 'rspku-theme-editor-blocks';

    public static function register(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontend']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdmin']);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueEditor']);
        add_filter('script_loader_tag', [self::class, 'useModuleScripts'], 10, 3);
    }

    public static function enqueueFrontend(): void
    {
        self::enqueueAsset(self::FRONT_HANDLE, 'resources/js/app.js');
    }

    public static function enqueueAdmin(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && !in_array($screen->post_type, ['dokter', 'layanan', 'poliklinik', 'jurnal', 'rawat-inap', 'manajemen-rs', 'cabang-rs'], true)) {
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

    public static function useModuleScripts(string $tag, string $handle, string $src): string
    {
        if (!in_array($handle, [self::FRONT_HANDLE, self::ADMIN_HANDLE, self::EDITOR_HANDLE], true)) {
            return $tag;
        }

        if (str_contains($tag, 'type=') || str_contains($tag, 'nomodule')) {
            return $tag;
        }

        return sprintf('<script type="module" src="%s" id="%s-js"></script>' . "\n", esc_url($src), esc_attr($handle));
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
                return null;
            }

            $json = file_get_contents($manifestPath);
            $manifest = is_string($json) ? json_decode($json, true) : null;
        }

        if (!is_array($manifest) || !isset($manifest[$entry]) || !is_array($manifest[$entry])) {
            return null;
        }

        return $manifest[$entry];
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
