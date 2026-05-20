<?php

declare(strict_types=1);

namespace Rspku\Setup;

use Rspku\Helpers\Icon;
use Timber\Menu;
use Timber\Timber;
use Twig\Environment;
use Twig\TwigFunction;

final class TimberSetup
{
    public static function register(): void
    {
        if (!class_exists(Timber::class)) {
            return;
        }

        Timber::$dirname = ['resources/views'];

        add_filter('timber/context', [self::class, 'addGlobalContext']);
        add_filter('timber/twig', [self::class, 'extendTwig']);
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public static function addGlobalContext(array $context): array
    {
        $context['theme'] = [
            'name' => wp_get_theme()->get('Name'),
            'version' => RSPKU_THEME_VERSION,
            'url' => RSPKU_THEME_URL,
            'ajax_url' => admin_url('admin-ajax.php'),
        ];

        $context['menus'] = [
            'primary' => self::menu('primary'),
            'footer' => self::menu('footer'),
            'utility' => self::menu('utility'),
        ];

        $context['site_logo'] = self::siteLogo();
        $context['labmu_logo'] = self::labmuLogo();

        return $context;
    }

    public static function extendTwig(Environment $twig): Environment
    {
        $twig->addFunction(new TwigFunction(
            'icon',
            static fn (string $name, array $attributes = []): string => Icon::svg($name, $attributes),
            ['is_safe' => ['html']]
        ));

        $twig->addFunction(new TwigFunction(
            'image_src',
            /**
             * Build a responsive image descriptor for an attachment.
             *
             * @return array{url:string,srcset:string,sizes:string,width:int,height:int,alt:string}|null
             */
            static function (int $attachmentId, string $size = 'rspku-hero', string $sizes = '(min-width: 1024px) 50vw, 100vw'): ?array {
                if ($attachmentId <= 0) {
                    return null;
                }

                $src = wp_get_attachment_image_src($attachmentId, $size);
                if (!$src) {
                    return null;
                }

                $srcset = wp_get_attachment_image_srcset($attachmentId, $size);

                return [
                    'url' => (string) $src[0],
                    'width' => (int) $src[1],
                    'height' => (int) $src[2],
                    'srcset' => is_string($srcset) ? $srcset : '',
                    'sizes' => $sizes,
                    'alt' => (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
                ];
            }
        ));

        return $twig;
    }

    private static function menu(string $location): ?Menu
    {
        $locations = get_nav_menu_locations();
        if (!isset($locations[$location])) {
            return null;
        }

        return Timber::get_menu($location);
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function siteLogo(): ?array
    {
        $logoId = (int) get_theme_mod('custom_logo');
        if ($logoId <= 0) {
            return null;
        }

        $image = wp_get_attachment_image_src($logoId, 'full');

        return [
            'id' => $logoId,
            'url' => $image ? $image[0] : wp_get_attachment_url($logoId),
            'alt' => (string) get_post_meta($logoId, '_wp_attachment_image_alt', true),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function labmuLogo(): array
    {
        return [
            'url' => get_template_directory_uri() . '/resources/images/labmu-logo.png',
            'alt' => 'LabMu - Muhammadiyah Software Labs',
        ];
    }
}
