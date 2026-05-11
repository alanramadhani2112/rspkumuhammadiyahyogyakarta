<?php

declare(strict_types=1);

namespace Rspku\Helpers;

final class Icon
{
    /**
     * @var array<string,string|null>
     */
    private static array $cache = [];

    /**
     * @param array<string,mixed> $attributes
     */
    public static function svg(string $name, array $attributes = []): string
    {
        $svg = self::load($name);
        if ($svg === null) {
            return '';
        }

        $svg = preg_replace('/^\s*<!--.*?-->\s*/s', '', $svg, 1);
        if (!is_string($svg) || $svg === '') {
            return '';
        }

        $defaults = [
            'class' => 'h-5 w-5',
            'aria-hidden' => 'true',
            'focusable' => 'false',
        ];

        $merged = array_merge($defaults, $attributes);

        if (!empty($merged['label']) && !isset($merged['aria-label'])) {
            $merged['aria-label'] = (string) $merged['label'];
        }

        if (isset($merged['aria-label'])) {
            unset($merged['aria-hidden']);
            $merged['role'] = 'img';
        }

        unset($merged['label']);

        $existingClass = self::matchAttribute($svg, 'class');
        if ($existingClass !== '' && isset($merged['class'])) {
            $merged['class'] = trim($existingClass . ' ' . (string) $merged['class']);
        }

        if (isset($merged['aria-label'])) {
            $svg = self::removeAttribute($svg, 'aria-hidden');
        }

        foreach ($merged as $attribute => $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }

            $svg = self::setAttribute($svg, $attribute, $value === true ? $attribute : (string) $value);
        }

        return $svg;
    }

    private static function load(string $name): ?string
    {
        $key = sanitize_title($name);

        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $path = trailingslashit(RSPKU_THEME_PATH) . 'resources/icons/lucide/' . $key . '.svg';
        if (!is_readable($path)) {
            self::$cache[$key] = null;
            return null;
        }

        $contents = file_get_contents($path);
        self::$cache[$key] = is_string($contents) ? trim($contents) : null;

        return self::$cache[$key];
    }

    private static function matchAttribute(string $svg, string $name): string
    {
        if (!preg_match('/\b' . preg_quote($name, '/') . '="([^"]*)"/i', $svg, $matches)) {
            return '';
        }

        return trim((string) ($matches[1] ?? ''));
    }

    private static function setAttribute(string $svg, string $name, string $value): string
    {
        $pattern = '/(<svg\b[^>]*?)\s+' . preg_quote($name, '/') . '="[^"]*"/i';
        $replacement = '$1 ' . esc_attr($name) . '="' . esc_attr($value) . '"';

        if (preg_match($pattern, $svg) === 1) {
            $updated = preg_replace($pattern, $replacement, $svg, 1);
            return is_string($updated) ? $updated : $svg;
        }

        $updated = preg_replace(
            '/<svg\b/i',
            '<svg ' . esc_attr($name) . '="' . esc_attr($value) . '"',
            $svg,
            1
        );

        return is_string($updated) ? $updated : $svg;
    }

    private static function removeAttribute(string $svg, string $name): string
    {
        $updated = preg_replace('/(<svg\b[^>]*?)\s+' . preg_quote($name, '/') . '="[^"]*"/i', '$1', $svg, 1);

        return is_string($updated) ? $updated : $svg;
    }
}
