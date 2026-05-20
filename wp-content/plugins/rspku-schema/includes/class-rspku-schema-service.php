<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Single-layanan schema — MedicalBusiness / Service. Most RS PKU offerings
 * (Medical Check Up, Home Care, Klinik Kecantikan, etc.) are services
 * rather than clinical procedures, so we emit a generic Service with the
 * Hospital as the provider. A single layanan can optionally be flagged as
 * a MedicalProcedure via the ACF field "is_medical_procedure".
 */
final class RSPKU_Schema_Service {

    /**
     * @return array<string,mixed>|null
     */
    public static function build(int $postId): ?array {
        $post = get_post($postId);
        if (!$post instanceof \WP_Post || $post->post_status !== 'publish') {
            return null;
        }

        $url = (string) get_permalink($post);
        $name = self::field_text($postId, 'nama_layanan', (string) get_the_title($post));

        $isMedicalProcedure = (bool) (function_exists('get_field')
            ? get_field('is_medical_procedure', $postId)
            : false);

        $type = $isMedicalProcedure ? 'MedicalProcedure' : 'Service';

        $node = RSPKU_Schema_Helpers::compact_node([
            '@type' => $type,
            '@id' => $url . '#service',
            'name' => $name,
            'url' => $url,
            'description' => self::field_text($postId, 'deskripsi_singkat_layanan'),
            'image' => self::image_url($postId, 'gambar_layanan'),
            'category' => self::service_category($postId),
            'provider' => RSPKU_Schema_Helpers::organization_id_ref(),
        ]);

        if ($type === 'Service') {
            $node['serviceType'] = $name;
            $node['areaServed'] = [
                '@type' => 'City',
                'name' => 'Yogyakarta',
            ];
        }

        return $node;
    }

    private static function service_category(int $postId): ?string {
        $terms = get_the_terms($postId, 'kategori-layanan');
        if (!is_array($terms) || $terms === []) {
            return null;
        }

        $first = $terms[0];
        return $first instanceof \WP_Term ? $first->name : null;
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
