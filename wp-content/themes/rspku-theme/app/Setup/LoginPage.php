<?php

declare(strict_types=1);

namespace Rspku\Setup;

/**
 * Custom Login Page styling for RS PKU Muhammadiyah Yogyakarta.
 * Matches the branding and design system of the frontend theme.
 */
final class LoginPage
{
    public static function register(): void
    {
        add_action('login_enqueue_scripts', [self::class, 'enqueueStyles']);
        add_filter('login_headerurl', [self::class, 'headerUrl']);
        add_filter('login_headertext', [self::class, 'headerText']);
        add_filter('login_message', [self::class, 'renderLeftPanel']);
        add_action('login_footer', [self::class, 'footerCredit']);
    }

    public static function enqueueStyles(): void
    {
        wp_enqueue_style(
            'rspku-login',
            get_theme_file_uri('assets/login.css'),
            ['login'],
            wp_get_theme()->get('Version')
        );
    }

    public static function headerUrl(): string
    {
        return home_url('/');
    }

    public static function headerText(): string
    {
        return get_bloginfo('name');
    }

    /**
     * Render the left image panel inside #login.
     * Uses the `login_message` filter which outputs inside #login div,
     * between h1 and the form.
     */
    public static function renderLeftPanel(string $message): string
    {
        $logoUrl = '';
        $customLogoId = get_theme_mod('custom_logo');
        if ($customLogoId) {
            $logoUrl = (string) wp_get_attachment_image_url($customLogoId, 'medium');
        }

        $imageUrl = '/wp-content/uploads/2023/12/640px-Gedung_PKU_Muhammadiyah_Yogyakarta_Kota_Jogjakarta_200_Tahun_plate_after_page_96.jpg';

        $panel = '<div class="rspku-login-panel" aria-hidden="true">';
        $panel .= '<div class="rspku-login-panel-image" style="background-image: linear-gradient(170deg, rgba(6,95,46,0.85) 0%, rgba(12,143,69,0.55) 40%, rgba(4,70,36,0.92) 100%), url(' . esc_url($imageUrl) . ')">';
        $panel .= '<p class="rspku-login-panel-text">Kelola website rumah sakit<br>dari satu dashboard terpusat.</p>';
        $panel .= '</div>';
        $panel .= '</div>';

        return $panel . $message;
    }

    public static function footerCredit(): void
    {
        echo '<div class="rspku-login-footer">';
        echo '<p>&copy; ' . date('Y') . ' RS PKU Muhammadiyah Yogyakarta</p>';
        echo '<p class="rspku-login-dev">Dikembangkan oleh <a href="https://labs.muhammadiyah.or.id" target="_blank" rel="noopener">LabMu</a> — Muhammadiyah Software Labs</p>';
        echo '</div>';
    }
}
