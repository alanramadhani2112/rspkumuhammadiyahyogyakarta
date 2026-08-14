<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Defines all tabs and fields for the admin settings panel.
 */
final class RSPKU_Settings_Registry
{
    /**
     * @return array<string,array<string,mixed>>
     */
    public static function tabs(): array
    {
        return [
            'umum' => [
                'label' => 'Umum',
                'icon' => 'dashicons-admin-site',
                'sections' => [
                    'identity' => [
                        'title' => 'Identitas Rumah Sakit',
                        'description' => 'Informasi dasar yang tampil di seluruh situs.',
                        'fields' => [
                            ['key' => 'site_name', 'label' => 'Nama Rumah Sakit', 'type' => 'text'],
                            ['key' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'help' => 'Contoh: Melayani dengan sepenuh hati sejak 1923'],
                            ['key' => 'founded_year', 'label' => 'Tahun Berdiri', 'type' => 'text', 'help' => 'Contoh: 1923'],
                        ],
                    ],
                ],
            ],
            'kontak' => [
                'label' => 'Kontak',
                'icon' => 'dashicons-phone',
                'sections' => [
                    'phone' => [
                        'title' => 'Nomor Telepon',
                        'description' => 'Nomor yang tampil di topbar, footer, dan halaman kontak.',
                        'fields' => [
                            ['key' => 'phone_igd', 'label' => 'Nomor IGD (Display)', 'type' => 'text', 'help' => 'Contoh: 0274 512321'],
                            ['key' => 'phone_igd_link', 'label' => 'Nomor IGD (tel: link)', 'type' => 'text', 'help' => 'Format internasional tanpa spasi. Contoh: +62274512321'],
                            ['key' => 'phone_main', 'label' => 'Nomor Utama (Display)', 'type' => 'text'],
                            ['key' => 'phone_main_link', 'label' => 'Nomor Utama (tel: link)', 'type' => 'text'],
                            ['key' => 'whatsapp', 'label' => 'WhatsApp (Display)', 'type' => 'text'],
                            ['key' => 'whatsapp_link', 'label' => 'WhatsApp (wa.me link)', 'type' => 'text', 'help' => 'Format: 628886412345 (tanpa + dan spasi)'],
                            ['key' => 'email', 'label' => 'Email Resmi', 'type' => 'email'],
                        ],
                    ],
                    'address' => [
                        'title' => 'Alamat',
                        'description' => 'Ditampilkan di footer dan halaman kontak.',
                        'fields' => [
                            ['key' => 'address_street', 'label' => 'Nama Jalan', 'type' => 'text'],
                            ['key' => 'address_district', 'label' => 'Kecamatan', 'type' => 'text'],
                            ['key' => 'address_city', 'label' => 'Kota', 'type' => 'text'],
                            ['key' => 'address_province', 'label' => 'Provinsi & Kode Pos', 'type' => 'text'],
                        ],
                    ],
                    'hours' => [
                        'title' => 'Jam Operasional',
                        'description' => 'Format: Label | Waktu | Highlight (1 baris per entri, pisah dengan " | ")',
                        'fields' => [
                            ['key' => 'service_hours', 'label' => 'Jam Operasional', 'type' => 'repeater_hours'],
                        ],
                    ],
                    'maps' => [
                        'title' => 'Google Maps',
                        'fields' => [
                            ['key' => 'google_maps_embed_url', 'label' => 'Google Maps Embed URL', 'type' => 'url', 'help' => 'URL iframe embed dari Google Maps'],
                            ['key' => 'google_maps_link', 'label' => 'Google Maps Link (untuk petunjuk arah)', 'type' => 'url'],
                            ['key' => 'google_maps_place_id', 'label' => 'Google Place ID', 'type' => 'text', 'help' => 'Opsional. Dipakai untuk Schema.org LocalBusiness / LocalHospital. Cari di https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder'],
                        ],
                    ],
                ],
            ],
            'social' => [
                'label' => 'Media Sosial',
                'icon' => 'dashicons-share',
                'sections' => [
                    'social_accounts' => [
                        'title' => 'Akun Media Sosial',
                        'description' => 'Kosongkan URL untuk menyembunyikan ikon social tertentu.',
                        'fields' => [
                            ['key' => 'social_instagram', 'label' => 'Instagram URL', 'type' => 'url'],
                            ['key' => 'social_instagram_handle', 'label' => 'Instagram Handle', 'type' => 'text', 'help' => 'Contoh: @rspkujogja'],
                            ['key' => 'social_facebook', 'label' => 'Facebook URL', 'type' => 'url'],
                            ['key' => 'social_facebook_handle', 'label' => 'Facebook Handle', 'type' => 'text'],
                            ['key' => 'social_youtube', 'label' => 'YouTube URL', 'type' => 'url'],
                            ['key' => 'social_youtube_handle', 'label' => 'YouTube Handle', 'type' => 'text'],
                            ['key' => 'social_twitter', 'label' => 'Twitter/X URL', 'type' => 'url'],
                            ['key' => 'social_twitter_handle', 'label' => 'Twitter/X Handle', 'type' => 'text'],
                            ['key' => 'social_linkedin', 'label' => 'LinkedIn URL', 'type' => 'url'],
                            ['key' => 'social_linkedin_handle', 'label' => 'LinkedIn Handle', 'type' => 'text'],
                        ],
                    ],
                ],
            ],
            'homepage' => [
                'label' => 'Homepage',
                'icon' => 'dashicons-admin-home',
                'sections' => [
                    'hero' => [
                        'title' => 'Hero Section',
                        'description' => 'Konten di bagian atas halaman beranda.',
                        'fields' => [
                            ['key' => 'hero_image_id', 'label' => 'Gambar Hero Homepage', 'type' => 'image', 'help' => 'Gambar utama di hero section homepage (rasio 4:3, min 1200px lebar)'],
                            ['key' => 'hero_eyebrow', 'label' => 'Badge Text', 'type' => 'text'],
                            ['key' => 'hero_title', 'label' => 'Judul Utama', 'type' => 'textarea', 'help' => 'Boleh HTML. Gunakan <span class="text-hospital-600">...</span> untuk highlight warna utama'],
                            ['key' => 'hero_description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                            ['key' => 'hero_cta_primary_text', 'label' => 'Tombol Utama - Teks', 'type' => 'text'],
                            ['key' => 'hero_cta_primary_url', 'label' => 'Tombol Utama - URL', 'type' => 'text'],
                            ['key' => 'hero_cta_secondary_text', 'label' => 'Tombol Kedua - Teks', 'type' => 'text'],
                            ['key' => 'hero_cta_secondary_url', 'label' => 'Tombol Kedua - URL', 'type' => 'text'],
                        ],
                    ],
                    'homepage_cta' => [
                        'title' => 'CTA Akhir Homepage',
                        'description' => 'Tombol pada section "Jaga Kesehatan Anda Bersama Kami" di akhir homepage.',
                        'fields' => [
                            ['key' => 'home_cta_primary_text', 'label' => 'Tombol Utama - Teks', 'type' => 'text'],
                            ['key' => 'home_cta_primary_url', 'label' => 'Tombol Utama - URL', 'type' => 'text', 'help' => 'Bisa URL relatif seperti /dokter/, URL penuh, tel:, atau mailto:.'],
                            ['key' => 'home_cta_secondary_text', 'label' => 'Tombol Kedua - Teks', 'type' => 'text'],
                            ['key' => 'home_cta_secondary_url', 'label' => 'Tombol Kedua - URL', 'type' => 'text', 'help' => 'Bisa URL relatif seperti /kontak/, URL penuh, tel:, atau mailto:.'],
                        ],
                    ],
                    'promo_slider' => [
                        'title' => 'Promo Slider',
                        'description' => 'Tiga slot promo tetap yang tampil setelah Quick Actions di homepage.',
                        'fields' => [
                            ['key' => 'promo_slide_1_enabled', 'label' => 'Promo 1 - Aktif', 'type' => 'toggle'],
                            ['key' => 'promo_slide_1_image_id', 'label' => 'Promo 1 - Gambar', 'type' => 'image', 'help' => 'Rasio rekomendasi 16:6, minimal 1600px lebar.'],
                            ['key' => 'promo_slide_1_title', 'label' => 'Promo 1 - Judul', 'type' => 'text'],
                            ['key' => 'promo_slide_1_description', 'label' => 'Promo 1 - Deskripsi', 'type' => 'textarea'],
                            ['key' => 'promo_slide_1_cta_text', 'label' => 'Promo 1 - CTA Label', 'type' => 'text'],
                            ['key' => 'promo_slide_1_cta_url', 'label' => 'Promo 1 - CTA URL', 'type' => 'text', 'help' => 'Gunakan path internal seperti /dokter/ atau URL HTTPS.'],
                            ['key' => 'promo_slide_2_enabled', 'label' => 'Promo 2 - Aktif', 'type' => 'toggle'],
                            ['key' => 'promo_slide_2_image_id', 'label' => 'Promo 2 - Gambar', 'type' => 'image', 'help' => 'Rasio rekomendasi 16:6, minimal 1600px lebar.'],
                            ['key' => 'promo_slide_2_title', 'label' => 'Promo 2 - Judul', 'type' => 'text'],
                            ['key' => 'promo_slide_2_description', 'label' => 'Promo 2 - Deskripsi', 'type' => 'textarea'],
                            ['key' => 'promo_slide_2_cta_text', 'label' => 'Promo 2 - CTA Label', 'type' => 'text'],
                            ['key' => 'promo_slide_2_cta_url', 'label' => 'Promo 2 - CTA URL', 'type' => 'text', 'help' => 'Gunakan path internal seperti /dokter/ atau URL HTTPS.'],
                            ['key' => 'promo_slide_3_enabled', 'label' => 'Promo 3 - Aktif', 'type' => 'toggle'],
                            ['key' => 'promo_slide_3_image_id', 'label' => 'Promo 3 - Gambar', 'type' => 'image', 'help' => 'Rasio rekomendasi 16:6, minimal 1600px lebar.'],
                            ['key' => 'promo_slide_3_title', 'label' => 'Promo 3 - Judul', 'type' => 'text'],
                            ['key' => 'promo_slide_3_description', 'label' => 'Promo 3 - Deskripsi', 'type' => 'textarea'],
                            ['key' => 'promo_slide_3_cta_text', 'label' => 'Promo 3 - CTA Label', 'type' => 'text'],
                            ['key' => 'promo_slide_3_cta_url', 'label' => 'Promo 3 - CTA URL', 'type' => 'text', 'help' => 'Gunakan path internal seperti /dokter/ atau URL HTTPS.'],
                        ],
                    ],
                    'metrics' => [
                        'title' => 'Metrics / Angka Statistik',
                        'description' => '3 angka yang tampil di bawah hero.',
                        'fields' => [
                            ['key' => 'metric_1_value', 'label' => 'Metric 1 - Nilai', 'type' => 'text'],
                            ['key' => 'metric_1_label', 'label' => 'Metric 1 - Label', 'type' => 'text'],
                            ['key' => 'metric_2_value', 'label' => 'Metric 2 - Nilai', 'type' => 'text'],
                            ['key' => 'metric_2_label', 'label' => 'Metric 2 - Label', 'type' => 'text'],
                            ['key' => 'metric_3_value', 'label' => 'Metric 3 - Nilai', 'type' => 'text'],
                            ['key' => 'metric_3_label', 'label' => 'Metric 3 - Label', 'type' => 'text'],
                        ],
                    ],
                    'home_sections' => [
                        'title' => 'Gambar Section Homepage',
                        'description' => 'Gambar yang tampil di tengah dan akhir homepage (selain hero).',
                        'fields' => [
                            ['key' => 'home_feature_image', 'label' => 'Gambar Section "Pelayanan Profesional"', 'type' => 'image', 'help' => 'Tampil di section feature tengah halaman (rasio 4:3, min 1200px lebar). Kosongkan untuk pakai gambar service pertama.'],
                            ['key' => 'home_cta_image', 'label' => 'Gambar CTA "Jaga Kesehatan"', 'type' => 'image', 'help' => 'Tampil di CTA gradient hijau di akhir homepage (rasio 1:1, min 600px). Kosongkan untuk pakai gambar room/service pertama.'],
                        ],
                    ],
                    'home_featured' => [
                        'title' => 'Layanan Unggulan',
                        'description' => 'Pilih maksimal 6 layanan yang ingin ditonjolkan di homepage. Kosongkan untuk auto-populate dari layanan terbaru.',
                        'fields' => [
                            ['key' => 'home_featured_services', 'label' => 'Pilih Layanan', 'type' => 'post_picker', 'post_type' => 'layanan', 'max' => 6, 'help' => 'Centang layanan yang akan ditampilkan di homepage.'],
                        ],
                    ],
                    'home_doctors' => [
                        'title' => 'Dokter Unggulan',
                        'description' => 'Pilih maksimal 6 dokter yang ingin ditonjolkan di homepage. Kosongkan untuk auto-populate dari dokter terbaru.',
                        'fields' => [
                            ['key' => 'home_featured_doctors', 'label' => 'Pilih Dokter', 'type' => 'post_picker', 'post_type' => 'dokter', 'max' => 6, 'help' => 'Centang dokter yang akan ditampilkan di homepage.'],
                        ],
                    ],
                    'home_reviews' => [
                        'title' => 'Ulasan Terbaik',
                        'description' => 'Kurasi ulasan Google Maps yang ingin ditampilkan di homepage.',
                        'fields' => [
                            ['key' => 'home_featured_reviews', 'label' => 'Ulasan Pilihan', 'type' => 'review_repeater', 'help' => 'Tambahkan ulasan manual dari Google Maps (nama, rating, tanggal, kutipan). Kosongkan untuk pakai data ulasan default.'],
                        ],
                    ],
                ],
            ],
            'gambar' => [
                'label' => 'Gambar',
                'icon' => 'dashicons-format-image',
                'sections' => [
                    'page_images' => [
                        'title' => 'Gambar Hero Halaman',
                        'description' => 'Gambar yang tampil di bagian hero/header setiap halaman. Kosongkan untuk menggunakan gambar default dari konten.',
                        'fields' => [
                            ['key' => 'image_dokter_archive', 'label' => 'Halaman Cari Dokter', 'type' => 'image', 'help' => 'Gambar hero di /dokter/ (rasio 16:10, min 1200px)'],
                            ['key' => 'image_fasilitas', 'label' => 'Halaman Fasilitas Rawat Inap', 'type' => 'image', 'help' => 'Gambar hero di /fasilitas-rawat-inap/ (rasio 16:10, min 1200px)'],
                            ['key' => 'image_berita', 'label' => 'Halaman Berita & Artikel', 'type' => 'image', 'help' => 'Gambar hero di /berita-artikel/ (rasio 16:10, min 1200px)'],
                            ['key' => 'image_poliklinik', 'label' => 'Halaman Poliklinik', 'type' => 'image', 'help' => 'Gambar hero di /poliklinik/ (rasio 16:10, min 1200px)'],
                            ['key' => 'image_layanan', 'label' => 'Halaman Layanan', 'type' => 'image', 'help' => 'Gambar hero di /layanan/ (rasio 16:10, min 1200px)'],
                        ],
                    ],
                ],
            ],
            'branding' => [
                'label' => 'Branding',
                'icon' => 'dashicons-art',
                'sections' => [
                    'logos' => [
                        'title' => 'Logo',
                        'description' => 'Logo digunakan di header, footer, dan favicon. Gunakan Appearance > Customize untuk mengubah logo utama.',
                        'fields' => [
                            ['key' => 'logo_note', 'label' => 'Cara ganti logo', 'type' => 'info', 'help' => 'Klik tombol "Ubah Logo" untuk mengatur custom logo WordPress.'],
                        ],
                    ],
                    'colors' => [
                        'title' => 'Warna Brand',
                        'description' => 'Warna utama theme (hex color). Perubahan langsung berlaku setelah refresh.',
                        'fields' => [
                            ['key' => 'brand_color_primary', 'label' => 'Warna Primer', 'type' => 'color'],
                            ['key' => 'brand_color_primary_dark', 'label' => 'Warna Primer (Dark)', 'type' => 'color'],
                            ['key' => 'brand_color_accent', 'label' => 'Warna Aksen', 'type' => 'color'],
                        ],
                    ],
                ],
            ],
            'features' => [
                'label' => 'Fitur',
                'icon' => 'dashicons-admin-settings',
                'sections' => [
                    'feature_toggles' => [
                        'title' => 'Toggle Fitur',
                        'description' => 'Aktifkan atau non-aktifkan fitur theme.',
                        'fields' => [
                            ['key' => 'feature_reading_progress', 'label' => 'Reading Progress Bar', 'type' => 'toggle', 'help' => 'Bar hijau di atas halaman saat baca artikel'],
                            ['key' => 'feature_toc', 'label' => 'Table of Contents (TOC)', 'type' => 'toggle', 'help' => 'Daftar isi otomatis di sidebar artikel'],
                            ['key' => 'feature_floating_share', 'label' => 'Share Buttons di Sidebar', 'type' => 'toggle'],
                            ['key' => 'feature_related_posts', 'label' => 'Related Articles', 'type' => 'toggle'],
                            ['key' => 'feature_popular_articles', 'label' => 'Populer Dibaca (sidebar)', 'type' => 'toggle'],
                            ['key' => 'feature_gtranslate', 'label' => 'Language Switcher (GTranslate)', 'type' => 'toggle'],
                            ['key' => 'feature_reviews_carousel', 'label' => 'Reviews Carousel (homepage)', 'type' => 'toggle'],
                            ['key' => 'schema_enabled', 'label' => 'JSON-LD Schema (SEO)', 'type' => 'toggle'],
                        ],
                    ],
                    'language_switcher' => [
                        'title' => 'Dual Bahasa',
                        'description' => 'Label aksesibilitas switcher bahasa Indonesia/English di header.',
                        'fields' => [
                            ['key' => 'language_switcher_label_id', 'label' => 'Label Bahasa Indonesia', 'type' => 'text'],
                            ['key' => 'language_switcher_label_en', 'label' => 'Label English', 'type' => 'text'],
                        ],
                    ],
                ],
            ],
            'header' => [
                'label' => 'Header',
                'icon' => 'dashicons-align-pull-left',
                'sections' => [
                    'header_behavior' => [
                        'title' => 'Perilaku Header',
                        'description' => 'Konfigurasi tampilan header situs.',
                        'fields' => [
                            ['key' => 'header_logo_alt_id', 'label' => 'Logo Alternatif (Opsional)', 'type' => 'image', 'help' => 'Logo untuk background hijau/gelap. Kosongkan untuk pakai logo utama dari Customizer.'],
                            ['key' => 'header_sticky', 'label' => 'Sticky Header', 'type' => 'toggle', 'help' => 'Header menempel di atas saat user scroll.'],
                            ['key' => 'header_topbar_enabled', 'label' => 'Tampilkan Top Bar', 'type' => 'toggle', 'help' => 'Bar tipis di atas header dengan info IGD dan alamat.'],
                            ['key' => 'header_emergency_enabled', 'label' => 'Tampilkan Badge IGD', 'type' => 'toggle', 'help' => 'Badge merah "IGD 24/7" di pojok kanan top bar.'],
                            ['key' => 'header_emergency_label', 'label' => 'Teks Badge IGD', 'type' => 'text', 'help' => 'Contoh: IGD 24/7'],
                        ],
                    ],
                    'header_cta' => [
                        'title' => 'CTA Header',
                        'description' => 'Tombol utama di header desktop dan menu mobile, contoh: Daftar Online.',
                        'fields' => [
                            ['key' => 'header_cta_text', 'label' => 'Teks Tombol', 'type' => 'text'],
                            ['key' => 'header_cta_url', 'label' => 'URL Tujuan', 'type' => 'text', 'help' => 'Bisa URL relatif seperti /dokter/, URL penuh, tel:, atau mailto:.'],
                        ],
                    ],
                ],
            ],
            'cta' => [
                'label' => 'CTA',
                'icon' => 'dashicons-megaphone',
                'sections' => [
                    'doctor_cta' => [
                        'title' => 'CTA Profil Dokter',
                        'description' => 'Fallback tombol buat janji ketika profil dokter belum punya URL appointment khusus.',
                        'fields' => [
                            ['key' => 'doctor_appointment_cta_text', 'label' => 'Teks Tombol Buat Janji', 'type' => 'text'],
                            ['key' => 'doctor_appointment_fallback_url', 'label' => 'Fallback URL Buat Janji', 'type' => 'text', 'help' => 'Dipakai jika dokter belum punya appointment URL. Contoh: /kontak/ atau /dokter/.'],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'label' => 'Footer',
                'icon' => 'dashicons-align-pull-right',
                'sections' => [
                    'footer_content' => [
                        'title' => 'Konten Footer',
                        'description' => 'Teks yang tampil di bagian bawah setiap halaman.',
                        'fields' => [
                            ['key' => 'footer_tagline', 'label' => 'Tagline Footer', 'type' => 'textarea', 'help' => 'Deskripsi singkat rumah sakit di footer (1–2 baris).'],
                            ['key' => 'footer_copyright', 'label' => 'Teks Copyright', 'type' => 'text', 'help' => 'Contoh: © 2026 RS PKU Muhammadiyah Yogyakarta. Hak Cipta Dilindungi.'],
                            ['key' => 'footer_disclaimer', 'label' => 'Disclaimer Medis', 'type' => 'textarea', 'help' => 'Disclaimer tentang informasi medis (opsional).'],
                        ],
                    ],
                    'footer_links' => [
                        'title' => 'Quick Links Footer',
                        'description' => 'Tautan cepat yang tampil di kolom footer.',
                        'fields' => [
                            ['key' => 'footer_quick_links', 'label' => 'Quick Links', 'type' => 'repeater_links', 'help' => 'Tambahkan 4–8 tautan penting. Format: Label | URL'],
                        ],
                    ],
                ],
            ],
            'tools' => [
                'label' => 'Tools',
                'icon' => 'dashicons-admin-tools',
                'sections' => [
                    'export_import' => [
                        'title' => 'Export & Import Settings',
                        'description' => 'Pindahkan konfigurasi antar environment (staging → production) tanpa copy-paste manual.',
                        'fields' => [
                            ['key' => 'export_import_tool', 'label' => 'Export / Import', 'type' => 'export_import'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
