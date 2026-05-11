<?php

declare(strict_types=1);

namespace Rspku\Setup;

final class ThemeSetup
{
    public static function register(): void
    {
        add_action('after_setup_theme', [self::class, 'configureTheme']);
    }

    public static function configureTheme(): void
    {
        load_theme_textdomain('rspku-theme', RSPKU_THEME_PATH . '/languages');

        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support('custom-logo', [
            'height' => 96,
            'width' => 96,
            'flex-height' => true,
            'flex-width' => true,
        ]);
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
            'navigation-widgets',
        ]);
        add_theme_support('editor-styles');
        add_theme_support('responsive-embeds');
        add_theme_support('align-wide');
        add_theme_support('wp-block-styles');

        add_image_size('rspku-hero', 1600, 900, true);
        add_image_size('rspku-card', 960, 720, true);
        add_image_size('rspku-doctor', 720, 900, true);
        add_image_size('rspku-square', 800, 800, true);

        register_nav_menus([
            'primary' => __('Primary Menu', 'rspku-theme'),
            'footer' => __('Footer Menu', 'rspku-theme'),
            'utility' => __('Utility Menu', 'rspku-theme'),
        ]);

        global $content_width;
        $content_width = $content_width ?? 1200;
    }
}
