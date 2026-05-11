<?php
/**
 * Theme bootstrap for the RSPKU modular WordPress architecture.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('RSPKU_THEME_PATH', __DIR__);
define('RSPKU_THEME_URL', get_stylesheet_directory_uri());
define('RSPKU_THEME_VERSION', wp_get_theme()->get('Version') ?: '1.0.0');

$composerAutoload = RSPKU_THEME_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Rspku\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = RSPKU_THEME_PATH . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

if (class_exists(\Rspku\Theme::class)) {
    add_action('after_setup_theme', [\Rspku\Theme::class, 'boot'], 0);
}

/**
 * Add reading time to Timber post context
 */
add_filter('timber/post/classmap', function ($classmap) {
    $classmap['post'] = \Rspku\Models\EnhancedPost::class;
    return $classmap;
});
