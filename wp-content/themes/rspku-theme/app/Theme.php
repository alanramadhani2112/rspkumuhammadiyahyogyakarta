<?php

declare(strict_types=1);

namespace Rspku;

use Rspku\Blocks\Registry as BlockRegistry;
use Rspku\Controllers\TemplateController;
use Rspku\Services\DoctorDirectorySync;
use Rspku\Services\DoctorSearch;
use Rspku\Setup\AdminExperience;
use Rspku\Setup\Assets;
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
        TimberSetup::register();
        AdminExperience::register();
        Assets::register();

        // Post types, taxonomies, and doctor fields now live in the
        // `rspku-cpt` plugin (see wp-content/plugins/rspku-cpt). The
        // theme no longer registers them so switching themes never
        // hides hospital content from wp-admin.

        DoctorSearch::register();
        DoctorDirectorySync::register();
        BlockRegistry::register();

        add_action('after_switch_theme', [self::class, 'flushRewriteRules']);
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
