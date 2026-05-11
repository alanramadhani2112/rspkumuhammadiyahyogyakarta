<?php
/**
 * Plugin Name: RSPKU Schema
 * Description: Injects schema.org JSON-LD (Hospital, Physician, MedicalClinic, Service, Article, BreadcrumbList) tailored for RS PKU Muhammadiyah Yogyakarta. Designed to complement (not replace) Yoast SEO's own graph.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.3
 * Author: RS PKU Muhammadiyah Yogyakarta
 * Text Domain: rspku-schema
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('RSPKU_SCHEMA_VERSION', '0.1.0');
define('RSPKU_SCHEMA_PATH', __DIR__);
define('RSPKU_SCHEMA_URL', plugin_dir_url(__FILE__));

require_once RSPKU_SCHEMA_PATH . '/includes/class-rspku-schema-helpers.php';
require_once RSPKU_SCHEMA_PATH . '/includes/class-rspku-schema-home.php';
require_once RSPKU_SCHEMA_PATH . '/includes/class-rspku-schema-physician.php';
require_once RSPKU_SCHEMA_PATH . '/includes/class-rspku-schema-clinic.php';
require_once RSPKU_SCHEMA_PATH . '/includes/class-rspku-schema-service.php';
require_once RSPKU_SCHEMA_PATH . '/includes/class-rspku-schema-article.php';
require_once RSPKU_SCHEMA_PATH . '/includes/class-rspku-schema-breadcrumb.php';

final class RSPKU_Schema {

    public static function init(): void {
        // Disable entirely via filter when callers need to troubleshoot.
        if (!(bool) apply_filters('rspku/schema/enabled', true)) {
            return;
        }

        // Priority 15 places us after Yoast SEO (which outputs at 1) and
        // before most theme head hooks, so crawlers see both graphs.
        add_action('wp_head', [self::class, 'render'], 15);
    }

    public static function render(): void {
        if (is_admin() || is_feed() || is_preview()) {
            return;
        }

        $nodes = [];

        // Breadcrumb always emits on non-home pages.
        $breadcrumb = RSPKU_Schema_Breadcrumb::build();
        if ($breadcrumb !== null) {
            $nodes[] = $breadcrumb;
        }

        // Page-specific nodes.
        if (is_front_page() || is_home()) {
            foreach (RSPKU_Schema_Home::build() as $node) {
                $nodes[] = $node;
            }
        } elseif (is_singular('dokter')) {
            $node = RSPKU_Schema_Physician::build((int) get_queried_object_id());
            if ($node !== null) {
                $nodes[] = $node;
            }
        } elseif (is_singular('poliklinik')) {
            $node = RSPKU_Schema_Clinic::build((int) get_queried_object_id());
            if ($node !== null) {
                $nodes[] = $node;
            }
        } elseif (is_singular('layanan')) {
            $node = RSPKU_Schema_Service::build((int) get_queried_object_id());
            if ($node !== null) {
                $nodes[] = $node;
            }
        } elseif (is_singular('post') || is_singular('jurnal')) {
            $node = RSPKU_Schema_Article::build((int) get_queried_object_id());
            if ($node !== null) {
                $nodes[] = $node;
            }
        }

        // Allow extenders to append / modify nodes.
        $nodes = apply_filters('rspku/schema/nodes', $nodes);

        if ($nodes === []) {
            return;
        }

        RSPKU_Schema_Helpers::output_graph($nodes);
    }
}

add_action('plugins_loaded', [RSPKU_Schema::class, 'init']);
