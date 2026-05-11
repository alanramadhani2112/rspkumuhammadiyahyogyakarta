<?php

declare(strict_types=1);

namespace Rspku\Helpers;

use Timber\Timber;

final class View
{
    /**
     * @param string|array<int,string> $templates
     * @param array<string,mixed> $context
     */
    public static function render(string|array $templates, array $context = []): void
    {
        if (!class_exists(Timber::class)) {
            wp_die(
                esc_html__('Timber belum terpasang. Jalankan composer install di folder theme.', 'rspku-theme'),
                esc_html__('Theme dependency belum lengkap', 'rspku-theme'),
                ['response' => 500]
            );
        }

        Timber::render($templates, $context);
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function compile(string $template, array $context = []): string
    {
        if (!class_exists(Timber::class)) {
            return '';
        }

        if (method_exists(Timber::class, 'compile')) {
            return (string) Timber::compile($template, $context);
        }

        ob_start();
        Timber::render($template, $context);
        return (string) ob_get_clean();
    }
}
