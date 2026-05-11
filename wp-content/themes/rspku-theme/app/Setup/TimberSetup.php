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

        return $context;
    }

    public static function extendTwig(Environment $twig): Environment
    {
        $twig->addFunction(new TwigFunction(
            'icon',
            static fn (string $name, array $attributes = []): string => Icon::svg($name, $attributes),
            ['is_safe' => ['html']]
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
}
