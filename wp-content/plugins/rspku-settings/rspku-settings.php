<?php
/**
 * Plugin Name: RSPKU Settings
 * Description: Centralized admin panel to configure all RS PKU theme options — hospital info, contact, hero, social media, history, and feature toggles — without touching code.
 * Version: 0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.3
 * Author: RS PKU Muhammadiyah Yogyakarta
 * Text Domain: rspku-settings
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('RSPKU_SETTINGS_VERSION', '0.2.0');
define('RSPKU_SETTINGS_PATH', __DIR__);
define('RSPKU_SETTINGS_URL', plugin_dir_url(__FILE__));
define('RSPKU_SETTINGS_OPTION_KEY', 'rspku_settings');

require_once RSPKU_SETTINGS_PATH . '/includes/class-rspku-settings-defaults.php';
require_once RSPKU_SETTINGS_PATH . '/includes/class-rspku-settings-registry.php';
require_once RSPKU_SETTINGS_PATH . '/includes/class-rspku-settings-admin.php';
require_once RSPKU_SETTINGS_PATH . '/includes/class-rspku-settings-api.php';

final class RSPKU_Settings
{
    public static function init(): void
    {
        RSPKU_Settings_Admin::register();
        RSPKU_Settings_API::register();
    }
}

add_action('plugins_loaded', [RSPKU_Settings::class, 'init']);
