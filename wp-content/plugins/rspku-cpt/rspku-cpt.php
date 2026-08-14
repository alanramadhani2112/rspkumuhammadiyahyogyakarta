<?php
/**
 * Plugin Name: RSPKU Custom Post Types
 * Description: Registers RS PKU custom post types (dokter, poliklinik, layanan, jurnal, manajemen-rs, rawat-inap), taxonomies (spesialisasi-dokter, kategori-layanan, jenis-konsultasi), and the doctor meta box previously bundled with the theme. Keeping content types in a plugin means switching themes does not hide the underlying data in wp-admin.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.3
 * Author: RS PKU Muhammadiyah Yogyakarta
 * Text Domain: rspku-theme
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('RSPKU_CPT_VERSION', '0.1.0');
define('RSPKU_CPT_PATH', __DIR__);

require_once RSPKU_CPT_PATH . '/includes/class-rspku-cpt-post-types.php';
require_once RSPKU_CPT_PATH . '/includes/class-rspku-cpt-taxonomies.php';
require_once RSPKU_CPT_PATH . '/includes/class-rspku-cpt-doctor-schedule.php';
require_once RSPKU_CPT_PATH . '/includes/class-rspku-cpt-doctor-fields.php';
require_once RSPKU_CPT_PATH . '/includes/class-rspku-cpt-doctor-schedule-admin.php';

final class RSPKU_CPT {

    public static function init(): void {
        RSPKU_CPT_PostTypes::register();
        RSPKU_CPT_Taxonomies::register();
        RSPKU_CPT_DoctorFields::register();
        RSPKU_CPT_DoctorScheduleAdmin::register();
    }

    /**
     * Plugin activation: register post types + taxonomies immediately and
     * flush rewrite rules so the new permalinks resolve without asking
     * the admin to visit Settings → Permalinks.
     */
    public static function activate(): void {
        RSPKU_CPT_PostTypes::registerPostTypes();
        RSPKU_CPT_Taxonomies::registerTaxonomies();
        RSPKU_CPT_Taxonomies::registerLegacyRewriteRules();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation: flush rewrite rules so the server stops
     * directing traffic to post types that no longer exist.
     */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}

add_action('plugins_loaded', [RSPKU_CPT::class, 'init']);

register_activation_hook(__FILE__, [RSPKU_CPT::class, 'activate']);
register_deactivation_hook(__FILE__, [RSPKU_CPT::class, 'deactivate']);
