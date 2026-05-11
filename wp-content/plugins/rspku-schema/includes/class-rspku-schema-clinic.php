<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Single-poliklinik schema — MedicalClinic with parentOrganization
 * reference to the Hospital node.
 */
final class RSPKU_Schema_Clinic {

    /**
     * @return array<string,mixed>|null
     */
    public static function build(int $postId): ?array {
        $post = get_post($postId);
        if (!$post instanceof \WP_Post || $post->post_status !== 'publish') {
            return null;
        }

        $home = home_url('/');
        $url = (string) get_permalink($post);
        $name = self::field_text($postId, 'nama_poli', (string) get_the_title($post));

        return RSPKU_Schema_Helpers::compact_node([
            '@type' => 'MedicalClinic',
            '@id' => $url . '#clinic',
            'name' => $name,
            'url' => $url,
            'description' => self::field_text($postId, 'deskripsi_singkat'),
            'image' => self::image_url($postId, 'gambar_poli'),
            'parentOrganization' => ['@id' => $home . '#hospital'],
            'isPartOf' => ['@id' => $home . '#hospital'],
        ]);
    }

    private static function image_url(int $postId, string $acfKey): ?string {
        $value = function_exists('get_field') ? get_field($acfKey, $postId) : null;

        if (is_array($value) && !empty($value['url']) && is_string($value['url'])) {
            return (string) $value['url'];
        }

        if (is_numeric($value)) {
            $src = wp_get_attachment_image_src((int) $value, 'full');
            if (is_array($src)) {
                return (string) $src[0];
            }
        }

        $thumb = get_the_post_thumbnail_url($postId, 'full');
        return is_string($thumb) && $thumb !== '' ? $thumb : null;
    }

    private static function field_text(int $postId, string $key, string $fallback = ''): string {
        $value = function_exists('get_field') ? get_field($key, $postId) : get_post_meta($postId, $key, true);
        if (is_scalar($value)) {
            $text = trim(wp_strip_all_tags((string) $value));
            return $text !== '' ? $text : $fallback;
        }

        return $fallback;
    }
}
