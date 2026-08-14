<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public API helper for reading RSPKU settings from theme & plugins.
 *
 * Usage in PHP:
 *   $phone = rspku_setting('phone_igd');
 *
 * Usage in Twig (exposed via Timber context):
 *   {{ rspku.phone_igd }}
 */
final class RSPKU_Settings_API
{
    public static function register(): void
    {
        // Inject all settings into Timber context as `rspku`
        add_filter('timber/context', [self::class, 'addToTimberContext']);

        // Inject dynamic CSS custom properties from brand colors
        add_action('wp_head', [self::class, 'renderBrandColorsCSS'], 5);

        // Bridge feature toggles into plugins that listen for filters.
        // rspku-schema plugin checks this filter at init time.
        add_filter('rspku/schema/enabled', [self::class, 'filterSchemaEnabled']);

        // Expose a read-only REST endpoint for headless consumers.
        add_action('rest_api_init', [self::class, 'registerRestRoutes']);

        // Flush downstream caches when settings change so the UI reflects
        // the new values without needing a manual purge.
        add_action('update_option_' . RSPKU_SETTINGS_OPTION_KEY, [self::class, 'onOptionUpdated'], 10, 2);
        add_action('add_option_' . RSPKU_SETTINGS_OPTION_KEY, [self::class, 'onOptionAdded'], 10, 2);
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public static function addToTimberContext(array $context): array
    {
        $settings = self::all();

        // Resolve image IDs to URLs so Twig templates don't have to hit
        // wp_get_attachment_image_url(). We expose a sibling `_url` key
        // for each image attachment ID. The raw ID is kept intact so
        // templates can call `image_src(id)` for responsive srcset.
        foreach (self::imageKeys() as $key) {
            $id = absint($settings[$key] ?? 0);
            $urlKey = $key === 'hero_image_id' ? 'hero_image_url' : $key . '_url';
            $settings[$urlKey] = $id > 0 ? (wp_get_attachment_image_url($id, 'rspku-hero') ?: '') : '';
        }

        $settings['promo_slides'] = self::promoSlides($settings);

        $context['rspku'] = $settings;

        return $context;
    }

    /**
     * @return list<string>
     */
    private static function imageKeys(): array
    {
        if (class_exists('RSPKU_Settings_Admin')) {
            return RSPKU_Settings_Admin::imageKeys();
        }

        return [
            'hero_image_id',
            'promo_slide_1_image_id',
            'promo_slide_2_image_id',
            'promo_slide_3_image_id',
            'home_feature_image',
            'home_cta_image',
            'image_dokter_archive',
            'image_fasilitas',
            'image_berita',
            'image_poliklinik',
            'image_layanan',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        $defaults = RSPKU_Settings_Defaults::all();
        $saved = get_option(RSPKU_SETTINGS_OPTION_KEY, []);
        if (is_array($saved)) {
            $saved = RSPKU_Settings_Defaults::normalizeLegacyContactDefaults($saved);
        }
        $settings = array_merge($defaults, is_array($saved) ? $saved : []);

        $settings['footer_quick_links'] = self::normalizeFooterQuickLinks(
            $settings['footer_quick_links'] ?? null,
            $defaults['footer_quick_links'] ?? []
        );

        return $settings;
    }

    /**
     * @param array<string,mixed> $settings
     * @return list<array{image_id:int,image_url:string,image_alt:string,cta_url:string}>
     */
    private static function promoSlides(array $settings): array
    {
        $slides = [];

        for ($i = 1; $i <= 3; $i++) {
            $prefix = 'promo_slide_' . $i;
            $imageId = absint($settings[$prefix . '_image_id'] ?? 0);
            $imageUrl = (string) ($settings[$prefix . '_image_id_url'] ?? '');

            if ($imageId < 1 || $imageUrl === '') {
                continue;
            }

            $slides[] = [
                'image_id' => $imageId,
                'image_url' => $imageUrl,
                'image_alt' => trim((string) get_post_meta($imageId, '_wp_attachment_image_alt', true)) ?: get_the_title($imageId),
                'cta_url' => trim((string) ($settings[$prefix . '_cta_url'] ?? '')),
            ];
        }

        return $slides;
    }

    /**
     * @param mixed $links
     * @param mixed $fallback
     * @return list<array{label:string,url:string}>
     */
    private static function normalizeFooterQuickLinks(mixed $links, mixed $fallback): array
    {
        $normalized = [];

        if (is_array($links)) {
            foreach ($links as $link) {
                if (!is_array($link)) {
                    continue;
                }

                $label = sanitize_text_field((string) ($link['label'] ?? ''));
                $url = esc_url_raw((string) ($link['url'] ?? ''));

                if ($label === '' || $url === '') {
                    continue;
                }

                $normalized[] = [
                    'label' => $label,
                    'url' => $url,
                ];
            }
        }

        if ($normalized !== [] || !is_array($fallback)) {
            return $normalized;
        }

        return self::normalizeFooterQuickLinks($fallback, []);
    }

    public static function get(string $key, mixed $fallback = null): mixed
    {
        return RSPKU_Settings_Defaults::get($key, $fallback);
    }

    /**
     * Emit a complete dynamic color palette into <head> that overrides
     * every Tailwind `hospital-*` utility class plus the CSS custom
     * properties used by `.rspku-button-*`, `.rspku-prose`, etc.
     *
     * The palette is generated from the single "Warna Primer" hex value
     * using HSL manipulation — no external dependency needed. This means
     * changing one color in the admin panel rebrands the entire site.
     */
    public static function renderBrandColorsCSS(): void
    {
        $primary = self::get('brand_color_primary', '#004DAA');
        $primaryDark = self::get('brand_color_primary_dark', '#003f8c');
        $accent = self::get('brand_color_accent', '#F5BD15');

        if (!is_string($primary) || !is_string($primaryDark) || !is_string($accent)) {
            return;
        }

        $palette = self::generatePalette($primary, $primaryDark);

        $css = ":root{\n";
        $css .= "  --rspku-brand:{$palette[600]};\n";
        $css .= "  --rspku-brand-dark:{$palette[700]};\n";
        $css .= "  --rspku-brand-soft:{$palette[50]};\n";
        $css .= "  --rspku-brand-secondary:{$palette[500]};\n";
        $css .= "  --rspku-accent:{$accent};\n";
        $css .= "  --rspku-danger:#D82C35;\n";
        $css .= "}\n";

        // Override every hospital-* shade used in Tailwind utilities.
        $shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900];
        $properties = ['bg', 'text', 'border', 'from', 'to', 'via'];

        foreach ($shades as $shade) {
            $hex = $palette[$shade];
            $css .= ".bg-hospital-{$shade}{background-color:{$hex}!important}\n";
            $css .= ".text-hospital-{$shade}{color:{$hex}!important}\n";
            $css .= ".border-hospital-{$shade}{border-color:{$hex}!important}\n";
            $css .= ".from-hospital-{$shade}{--tw-gradient-from:{$hex};--tw-gradient-from-position:}\n";
            $css .= ".to-hospital-{$shade}{--tw-gradient-to:{$hex};--tw-gradient-to-position:}\n";
            $css .= ".via-hospital-{$shade}{--tw-gradient-via:{$hex};--tw-gradient-via-position:}\n";
        }

        // Hover/focus/group-hover variants for the most-used shades.
        $hoverShades = [100, 300, 600, 700, 800];
        foreach ($hoverShades as $shade) {
            $hex = $palette[$shade];
            $css .= ".hover\\:bg-hospital-{$shade}:hover{background-color:{$hex}!important}\n";
            $css .= ".hover\\:text-hospital-{$shade}:hover{color:{$hex}!important}\n";
            $css .= ".hover\\:border-hospital-{$shade}:hover{border-color:{$hex}!important}\n";
            $css .= ".group-hover\\:bg-hospital-{$shade}:is(.group:hover *){background-color:{$hex}!important}\n";
            $css .= ".group-hover\\:text-hospital-{$shade}:is(.group:hover *){color:{$hex}!important}\n";
        }

        // Focus ring
        $css .= ".focus\\:ring-hospital-500:focus{--tw-ring-color:{$palette[500]}}\n";

        // Button component overrides (use CSS vars but force here too).
        $css .= ".rspku-button-primary{border-color:{$palette[600]}!important;background:{$palette[600]}!important}\n";
        $css .= ".rspku-button-primary:hover{border-color:{$palette[700]}!important;background:{$palette[700]}!important}\n";
        $css .= ".rspku-button-secondary:hover{border-color:{$palette[600]}!important;color:{$palette[700]}!important}\n";

        echo '<style id="rspku-dynamic-brand">' . $css . "</style>\n";
    }

    /**
     * Generate a 10-shade palette (50–900) from a primary hex color.
     *
     * Uses HSL color space: keeps hue constant, varies saturation and
     * lightness to produce a coherent scale. The `primaryDark` input
     * anchors shade 700 so the admin's "dark" picker is respected.
     *
     * @return array<int,string> Keyed by shade number (50,100,...,900)
     */
    private static function generatePalette(string $primaryHex, string $darkHex): array
    {
        if (strcasecmp($primaryHex, '#004DAA') === 0) {
            return [
                50 => '#eaf6ff',
                100 => '#d5edff',
                200 => '#aadfff',
                300 => '#75ccff',
                400 => '#38b8f5',
                500 => '#009EE6',
                600 => '#004DAA',
                700 => '#003f8c',
                800 => '#00336f',
                900 => '#002653',
            ];
        }

        $hsl = self::hexToHsl($primaryHex);
        $darkHsl = self::hexToHsl($darkHex);

        $h = $hsl[0];
        $s = $hsl[1];

        // Lightness targets for each shade (approximate Tailwind defaults).
        $lightnessMap = [
            50 => 96,
            100 => 90,
            200 => 80,
            300 => 65,
            400 => 50,
            500 => 40,
            600 => $hsl[2],  // Use actual primary lightness
            700 => $darkHsl[2], // Use actual dark lightness
            800 => max(15, $darkHsl[2] - 8),
            900 => max(10, $darkHsl[2] - 14),
        ];

        // Saturation adjustments (lighter shades are less saturated).
        $saturationMap = [
            50 => max(20, $s * 0.4),
            100 => max(30, $s * 0.55),
            200 => max(40, $s * 0.7),
            300 => max(45, $s * 0.8),
            400 => $s * 0.9,
            500 => $s,
            600 => $s,
            700 => min(100, $s * 1.05),
            800 => min(100, $s * 1.1),
            900 => min(100, $s * 1.1),
        ];

        $palette = [];
        foreach ($lightnessMap as $shade => $l) {
            $sat = $saturationMap[$shade];
            $palette[$shade] = self::hslToHex($h, $sat, $l);
        }

        return $palette;
    }

    /**
     * @return array{0:float,1:float,2:float} [hue 0-360, saturation 0-100, lightness 0-100]
     */
    private static function hexToHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, round($l * 100, 1)];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        $h = match ($max) {
            $r => (($g - $b) / $d + ($g < $b ? 6 : 0)) * 60,
            $g => (($b - $r) / $d + 2) * 60,
            default => (($r - $g) / $d + 4) * 60,
        };

        return [round($h, 1), round($s * 100, 1), round($l * 100, 1)];
    }

    private static function hslToHex(float $h, float $s, float $l): string
    {
        $s /= 100;
        $l /= 100;

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0.0],
            $h < 120 => [$x, $c, 0.0],
            $h < 180 => [0.0, $c, $x],
            $h < 240 => [0.0, $x, $c],
            $h < 300 => [$x, 0.0, $c],
            default => [$c, 0.0, $x],
        };

        $r = (int) round(($r + $m) * 255);
        $g = (int) round(($g + $m) * 255);
        $b = (int) round(($b + $m) * 255);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Bridge the admin toggle `schema_enabled` into rspku-schema plugin.
     * Defaults to the incoming value so other filters in the chain still
     * work; we only force-disable when the editor opts out.
     */
    public static function filterSchemaEnabled(bool $enabled): bool
    {
        $setting = self::get('schema_enabled', true);

        if ($setting === false || $setting === 0 || $setting === '0') {
            return false;
        }

        return $enabled;
    }

    /**
     * Register the public read-only REST endpoint.
     *
     * Route: GET /wp-json/rspku/v1/settings
     *
     * Sensitive-ish data (tel: links, map URLs) are intentionally public
     * because they are also rendered on the marketing pages. Anything
     * that shouldn't be public must not be stored in this option.
     */
    public static function registerRestRoutes(): void
    {
        register_rest_route(
            'rspku/v1',
            '/settings',
            [
                'methods' => 'GET',
                'permission_callback' => '__return_true',
                'callback' => static function (): \WP_REST_Response {
                    $payload = self::publicSettingsPayload();

                    $response = new \WP_REST_Response($payload);
                    $response->header('Cache-Control', 'public, max-age=300');

                    return $response;
                },
            ]
        );
    }

    /**
     * Build the payload for the public REST endpoint. Keeps the surface
     * small and predictable so frontend clients aren't surprised when a
     * new admin-only field is added later.
     *
     * @return array<string,mixed>
     */
    private static function publicSettingsPayload(): array
    {
        $all = self::all();

        $imageUrls = [];
        foreach (self::imageKeys() as $key) {
            $id = absint($all[$key] ?? 0);
            $urlKey = $key === 'hero_image_id' ? 'hero_image_url' : $key . '_url';
            $imageUrls[$urlKey] = $id > 0 ? (wp_get_attachment_image_url($id, 'rspku-hero') ?: '') : '';
        }

        return [
            'identity' => [
                'site_name' => (string) ($all['site_name'] ?? ''),
                'tagline' => (string) ($all['tagline'] ?? ''),
                'founded_year' => (string) ($all['founded_year'] ?? ''),
            ],
            'contact' => [
                'phone_igd' => (string) ($all['phone_igd'] ?? ''),
                'phone_igd_link' => (string) ($all['phone_igd_link'] ?? ''),
                'phone_main' => (string) ($all['phone_main'] ?? ''),
                'phone_main_link' => (string) ($all['phone_main_link'] ?? ''),
                'whatsapp' => (string) ($all['whatsapp'] ?? ''),
                'whatsapp_link' => (string) ($all['whatsapp_link'] ?? ''),
                'email' => (string) ($all['email'] ?? ''),
            ],
            'address' => [
                'street' => (string) ($all['address_street'] ?? ''),
                'district' => (string) ($all['address_district'] ?? ''),
                'city' => (string) ($all['address_city'] ?? ''),
                'province' => (string) ($all['address_province'] ?? ''),
            ],
            'maps' => [
                'embed_url' => (string) ($all['google_maps_embed_url'] ?? ''),
                'link' => (string) ($all['google_maps_link'] ?? ''),
                'place_id' => (string) ($all['google_maps_place_id'] ?? ''),
            ],
            'service_hours' => is_array($all['service_hours'] ?? null) ? $all['service_hours'] : [],
            'socials' => self::socialsPayload($all),
            'hero' => [
                'eyebrow' => (string) ($all['hero_eyebrow'] ?? ''),
                'title' => (string) ($all['hero_title'] ?? ''),
                'description' => (string) ($all['hero_description'] ?? ''),
                'cta_primary' => [
                    'text' => (string) ($all['hero_cta_primary_text'] ?? ''),
                    'url' => (string) ($all['hero_cta_primary_url'] ?? ''),
                ],
                'cta_secondary' => [
                    'text' => (string) ($all['hero_cta_secondary_text'] ?? ''),
                    'url' => (string) ($all['hero_cta_secondary_url'] ?? ''),
                ],
                'image_url' => $imageUrls['hero_image_url'] ?? '',
            ],
            'metrics' => [
                ['value' => (string) ($all['metric_1_value'] ?? ''), 'label' => (string) ($all['metric_1_label'] ?? '')],
                ['value' => (string) ($all['metric_2_value'] ?? ''), 'label' => (string) ($all['metric_2_label'] ?? '')],
                ['value' => (string) ($all['metric_3_value'] ?? ''), 'label' => (string) ($all['metric_3_label'] ?? '')],
            ],
            'promo_slides' => self::promoSlides(array_merge($all, $imageUrls)),
            'branding' => [
                'primary' => (string) ($all['brand_color_primary'] ?? ''),
                'primary_dark' => (string) ($all['brand_color_primary_dark'] ?? ''),
                'accent' => (string) ($all['brand_color_accent'] ?? ''),
            ],
            'features' => [
                'reading_progress' => (bool) ($all['feature_reading_progress'] ?? false),
                'toc' => (bool) ($all['feature_toc'] ?? false),
                'floating_share' => (bool) ($all['feature_floating_share'] ?? false),
                'related_posts' => (bool) ($all['feature_related_posts'] ?? false),
                'popular_articles' => (bool) ($all['feature_popular_articles'] ?? false),
                'gtranslate' => (bool) ($all['feature_gtranslate'] ?? false),
                'reviews_carousel' => (bool) ($all['feature_reviews_carousel'] ?? false),
                'schema' => (bool) ($all['schema_enabled'] ?? false),
            ],
            'page_images' => [
                'home_feature' => $imageUrls['home_feature_image_url'] ?? '',
                'home_cta' => $imageUrls['home_cta_image_url'] ?? '',
                'dokter_archive' => $imageUrls['image_dokter_archive_url'] ?? '',
                'fasilitas' => $imageUrls['image_fasilitas_url'] ?? '',
                'berita' => $imageUrls['image_berita_url'] ?? '',
                'poliklinik' => $imageUrls['image_poliklinik_url'] ?? '',
                'layanan' => $imageUrls['image_layanan_url'] ?? '',
            ],
        ];
    }

    /**
     * @param array<string,mixed> $all
     * @return list<array<string,string>>
     */
    private static function socialsPayload(array $all): array
    {
        $platforms = [
            'instagram' => 'social_instagram',
            'facebook' => 'social_facebook',
            'youtube' => 'social_youtube',
            'twitter' => 'social_twitter',
            'linkedin' => 'social_linkedin',
        ];

        $out = [];
        foreach ($platforms as $name => $urlKey) {
            $url = (string) ($all[$urlKey] ?? '');
            if ($url === '') {
                continue;
            }
            $out[] = [
                'name' => $name,
                'url' => $url,
                'handle' => (string) ($all[$urlKey . '_handle'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param mixed $old
     * @param mixed $new
     */
    public static function onOptionUpdated($old, $new): void
    {
        self::flushDependentCaches();
    }

    /**
     * @param string $optionName
     * @param mixed $value
     */
    public static function onOptionAdded($optionName, $value): void
    {
        self::flushDependentCaches();
    }

    /**
     * Clear caches that depend on the settings payload. Third parties can
     * hook `rspku/settings/flushed` to invalidate their own caches.
     */
    private static function flushDependentCaches(): void
    {
        wp_cache_delete('rspku_settings_public_payload', 'rspku');

        // Object cache group used by theme repositories for schedule data.
        // `wp_cache_flush_group` exists in WP 6.1+ and only flushes a
        // group when the backing object cache supports it; otherwise it
        // is a no-op. Either way we don't want to crash on misconfig.
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('rspku_theme');
        }

        // Invalidate transient-based caches that depend on settings.
        // These keys are created by the theme's repositories / mappers
        // and would otherwise serve stale copy until their individual
        // TTLs expire (up to 12 hours).
        self::flushRspkuTransients();

        // Invalidate LiteSpeed / popular page caches if available so the
        // frontend reflects the saved changes immediately. We feature-
        // detect each integration rather than assume installation.
        if (function_exists('wp_cache_flush') && defined('WP_CACHE') && WP_CACHE) {
            wp_cache_flush();
        }

        if (class_exists('\LiteSpeed\Purge')) {
            do_action('litespeed_purge_all');
        }

        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        do_action('rspku/settings/flushed');
    }

    /**
     * Delete every transient whose key starts with one of the plugin
     * prefixes. Uses a direct DB query because WP core doesn't expose
     * a prefix-based helper.
     */
    private static function flushRspkuTransients(): void
    {
        global $wpdb;
        if (!isset($wpdb)) {
            return;
        }

        $prefixes = [
            '_transient_rspku_',
            '_transient_timeout_rspku_',
            '_transient_rspku_related_',
            '_transient_rspku_doctor_',
            '_transient_rspku_article_cta_',
        ];

        foreach ($prefixes as $prefix) {
            $like = $wpdb->esc_like($prefix) . '%';
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $like
                )
            );
        }
    }
}

/**
 * Global helper function for easy access from anywhere.
 */
if (!function_exists('rspku_setting')) {
    function rspku_setting(string $key, mixed $fallback = null): mixed
    {
        return RSPKU_Settings_API::get($key, $fallback);
    }
}
