<?php

declare(strict_types=1);

namespace Rspku;

use Rspku\Blocks\Registry as BlockRegistry;
use Rspku\Controllers\TemplateController;
use Rspku\Fields\DoctorFields;
use Rspku\PostTypes\Registry as PostTypeRegistry;
use Rspku\Services\DoctorDirectorySync;
use Rspku\Services\DoctorSearch;
use Rspku\Setup\AdminExperience;
use Rspku\Setup\Assets;
use Rspku\Setup\ThemeSetup;
use Rspku\Setup\TimberSetup;
use Rspku\Taxonomies\Registry as TaxonomyRegistry;

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
        PostTypeRegistry::register();
        TaxonomyRegistry::register();
        DoctorFields::register();
        DoctorSearch::register();
        DoctorDirectorySync::register();
        BlockRegistry::register();

        add_action('after_switch_theme', [self::class, 'flushRewriteRules']);
    }

    public static function render(): void
    {
        TemplateController::render();
    }

    public static function flushRewriteRules(): void
    {
        PostTypeRegistry::registerPostTypes();
        TaxonomyRegistry::registerTaxonomies();
        flush_rewrite_rules();
    }
}
