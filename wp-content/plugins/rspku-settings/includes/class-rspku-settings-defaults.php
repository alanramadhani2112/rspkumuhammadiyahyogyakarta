<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Default values for all theme options.
 * Used when option hasn't been saved yet.
 */
final class RSPKU_Settings_Defaults
{
    /**
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        return [
            // General
            'site_name' => 'RS PKU Muhammadiyah Yogyakarta',
            'tagline' => 'Menjaga kesehatan keluarga sejak 1923',
            'founded_year' => '1923',

            // Contact
            'phone_igd' => '0274 512321',
            'phone_igd_link' => '+62274512321',
            'phone_main' => '0274 512653',
            'phone_main_link' => '+62274512653',
            'whatsapp' => '0888 6412345',
            'whatsapp_link' => '628886412345',
            'email' => 'info@rspkujogja.co.id',
            'address_street' => 'Jl. KH. Ahmad Dahlan No. 20',
            'address_district' => 'Ngupasan, Kec. Gondomanan',
            'address_city' => 'Kota Yogyakarta',
            'address_province' => 'Daerah Istimewa Yogyakarta 55122',

            // Service Hours (JSON array)
            'service_hours' => [
                ['label' => 'IGD (Instalasi Gawat Darurat)', 'time' => '24 Jam Setiap Hari', 'highlight' => true],
                ['label' => 'Rawat Jalan / Poliklinik', 'time' => '07.00 - 20.00 WIB', 'highlight' => false],
                ['label' => 'Administrasi', 'time' => '08.00 - 16.00 WIB', 'highlight' => false],
                ['label' => 'Pendaftaran Online', 'time' => '24 Jam Setiap Hari', 'highlight' => false],
                ['label' => 'Farmasi', 'time' => '24 Jam Setiap Hari', 'highlight' => false],
                ['label' => 'Laboratorium', 'time' => '07.00 - 22.00 WIB', 'highlight' => false],
            ],

            // Social Media
            'social_instagram' => 'https://instagram.com/rspkujogja',
            'social_instagram_handle' => '@rspkujogja',
            'social_facebook' => 'https://facebook.com/rspkujogja',
            'social_facebook_handle' => 'RS PKU Muhammadiyah Yogyakarta',
            'social_youtube' => 'https://youtube.com/@rspkujogja',
            'social_youtube_handle' => 'RS PKU Yogyakarta',
            'social_twitter' => '',
            'social_twitter_handle' => '',
            'social_linkedin' => '',
            'social_linkedin_handle' => '',

            // Hero Homepage
            'hero_eyebrow' => 'Menjaga kesehatan keluarga sejak 1923',
            'hero_title' => 'Layanan kesehatan <span class="text-hospital-600">terpercaya</span> untuk Anda dan keluarga',
            'hero_description' => 'Lebih dari satu abad mendampingi masyarakat Yogyakarta dan sekitarnya dengan layanan kesehatan yang profesional, humanis, dan berlandaskan nilai Islami.',
            'hero_cta_primary_text' => 'Cari Dokter',
            'hero_cta_primary_url' => '/dokter/',
            'hero_cta_secondary_text' => 'Lihat Layanan',
            'hero_cta_secondary_url' => '/poliklinik/',

            // Global CTA destinations
            'header_cta_text' => 'Daftar Online',
            'header_cta_url' => '/dokter/',
            'home_cta_primary_text' => 'Buat janji sekarang',
            'home_cta_primary_url' => '/dokter/',
            'home_cta_secondary_text' => 'Hubungi IGD',
            'home_cta_secondary_url' => 'tel:0274512321',
            'doctor_appointment_cta_text' => 'Buat Janji',
            'doctor_appointment_fallback_url' => '/kontak/',

            // Homepage promo slider
            'promo_slide_1_enabled' => false,
            'promo_slide_1_image_id' => 0,
            'promo_slide_1_title' => '',
            'promo_slide_1_description' => '',
            'promo_slide_1_cta_text' => '',
            'promo_slide_1_cta_url' => '',
            'promo_slide_2_enabled' => false,
            'promo_slide_2_image_id' => 0,
            'promo_slide_2_title' => '',
            'promo_slide_2_description' => '',
            'promo_slide_2_cta_text' => '',
            'promo_slide_2_cta_url' => '',
            'promo_slide_3_enabled' => false,
            'promo_slide_3_image_id' => 0,
            'promo_slide_3_title' => '',
            'promo_slide_3_description' => '',
            'promo_slide_3_cta_text' => '',
            'promo_slide_3_cta_url' => '',

            // Metrics
            'metric_1_value' => '24/7',
            'metric_1_label' => 'IGD siap melayani',
            'metric_2_value' => '75+',
            'metric_2_label' => 'Dokter berpengalaman',
            'metric_3_value' => '31+',
            'metric_3_label' => 'Spesialisasi medis',

            // Branding
            'brand_color_primary' => '#004DAA',
            'brand_color_primary_dark' => '#003f8c',
            'brand_color_accent' => '#F5BD15',

            // Features Toggle
            'feature_reading_progress' => true,
            'feature_toc' => true,
            'feature_floating_share' => true,
            'feature_related_posts' => true,
            'feature_popular_articles' => true,
            'feature_gtranslate' => true,
            'feature_reviews_carousel' => true,
            'language_switcher_label_id' => 'Bahasa Indonesia',
            'language_switcher_label_en' => 'English',

            // SEO & Schema
            'schema_enabled' => true,
            'google_maps_place_id' => '',
            'google_maps_embed_url' => 'https://maps.google.com/maps?q=Jl.%20KH.%20Ahmad%20Dahlan%20No.20%2C%20Yogyakarta&t=m&z=14&output=embed',
            'google_maps_link' => 'https://maps.app.goo.gl/RSPKUJogja',

            // Page Images (attachment IDs)
            'hero_image_id' => 0,
            'home_feature_image' => 0,
            'home_cta_image' => 0,
            'image_dokter_archive' => 0,
            'image_fasilitas' => 0,
            'image_berita' => 0,
            'image_poliklinik' => 0,
            'image_layanan' => 0,

            // Header
            'header_logo_alt_id' => 0,
            'header_sticky' => true,
            'header_topbar_enabled' => true,
            'header_emergency_enabled' => true,
            'header_emergency_label' => 'IGD 24/7',

            // Footer
            'footer_copyright' => '© 2026 RS PKU Muhammadiyah Yogyakarta. Hak Cipta Dilindungi.',
            'footer_tagline' => 'Mendampingi kesehatan Anda dan keluarga dengan pelayanan profesional yang berlandaskan nilai Islami.',
            'footer_disclaimer' => 'Informasi medis di situs ini bersifat umum dan tidak menggantikan konsultasi dokter.',
            'footer_quick_links' => [
                ['label' => 'Dokter', 'url' => '/dokter/'],
                ['label' => 'Poliklinik', 'url' => '/poliklinik/'],
                ['label' => 'Layanan', 'url' => '/layanan/'],
                ['label' => 'Kontak', 'url' => '/kontak/'],
            ],

            // Homepage dynamic pickers (arrays of post IDs)
            'home_featured_services' => [],
            'home_featured_doctors' => [],
            'home_featured_reviews' => [],
        ];
    }

    /**
     * Get a single option with fallback to default.
     */
    public static function get(string $key, mixed $fallback = null): mixed
    {
        $options = get_option(RSPKU_SETTINGS_OPTION_KEY, []);
        $defaults = self::all();
        if (!is_array($options)) {
            $options = [];
        }

        if (isset($options[$key]) && $options[$key] !== '') {
            return $options[$key];
        }

        return $defaults[$key] ?? $fallback;
    }
}
