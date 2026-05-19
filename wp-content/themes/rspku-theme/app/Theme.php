<?php

declare(strict_types=1);

namespace Rspku;

use Rspku\Blocks\Registry as BlockRegistry;
use Rspku\Controllers\TemplateController;
use Rspku\Services\DoctorDirectorySync;
use Rspku\Services\DoctorSearch;
use Rspku\Setup\AdminExperience;
use Rspku\Setup\Assets;
use Rspku\Setup\LoginPage;
use Rspku\Setup\ThemeSetup;
use Rspku\Setup\TimberSetup;

final class Theme
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        ThemeSetup::configureTheme();
        ThemeSetup::registerHooks();
        TimberSetup::register();
        AdminExperience::register();
        Assets::register();
        LoginPage::register();

        // Post types, taxonomies, and doctor fields now live in the
        // `rspku-cpt` plugin (see wp-content/plugins/rspku-cpt). The
        // theme no longer registers them so switching themes never
        // hides hospital content from wp-admin.

        DoctorSearch::register();
        DoctorDirectorySync::register();
        BlockRegistry::register();

        self::registerCacheInvalidation();

        add_action('after_switch_theme', [self::class, 'flushRewriteRules']);
    }

    /**
     * Drop normalized doctor/content payloads when their source posts
     * change. Without this, editors who update a doctor's bio or photo
     * would keep seeing the stale cached version for up to six hours.
     */
    private static function registerCacheInvalidation(): void
    {
        $handler = static function (int $postId): void {
            $type = (string) get_post_type($postId);
            if ($type === 'dokter') {
                \Rspku\Repositories\DoctorRepository::flushCache($postId);
            }
            if ($type === 'post') {
                \Rspku\Repositories\ContentRepository::flushRelatedCache($postId);
                \Rspku\Services\ArticleCtaMapper::flushCache($postId);
            }
        };

        add_action('save_post', $handler, 10, 1);
        add_action('deleted_post', $handler, 10, 1);
        add_action('trashed_post', $handler, 10, 1);

        // The settings plugin fires this action whenever the rspku_settings
        // option is changed. If it's installed, take the opportunity to
        // invalidate content caches that depend on settings-driven data.
        add_action('rspku/settings/flushed', static function (): void {
            if (function_exists('wp_cache_flush_group')) {
                wp_cache_flush_group('rspku_theme');
            }
        });
    }

    public static function render(): void
    {
        TemplateController::render();
    }

    /**
     * Re-flushes rewrite rules when the theme is activated. The `rspku-cpt`
     * plugin owns CPT/taxonomy registration so all we need to do here is
     * make sure permalinks pick up any rewrite rule the theme itself may
     * add in the future.
     */
    public static function flushRewriteRules(): void
    {
        flush_rewrite_rules();
    }
}
