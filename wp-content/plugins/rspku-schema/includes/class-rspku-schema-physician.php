<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Single-doctor schema — Physician with worksFor reference to the
 * Hospital node. Pulls data from ACF fields already used by the theme
 * (nama_dokter, foto_dokter, profil_dokter) and the spesialisasi-dokter
 * taxonomy.
 */
final class RSPKU_Schema_Physician {

    /**
     * @return array<string,mixed>|null
     */
    public static function build(int $postId): ?array {
        $post = get_post($postId);
        if (!$post instanceof \WP_Post || $post->post_status !== 'publish') {
            return null;
        }

        $home = home_url('/');
        $name = self::field_text($postId, 'nama_dokter', (string) get_the_title($post));
        $url = (string) get_permalink($post);

        $node = RSPKU_Schema_Helpers::compact_node([
            '@type' => 'Physician',
            '@id' => $url . '#physician',
            'name' => $name,
            'url' => $url,
            'description' => self::field_text($postId, 'profil_dokter'),
            'image' => self::physician_image($postId),
            'medicalSpecialty' => self::medical_specialty($postId),
            'worksFor' => ['@id' => $home . '#hospital'],
            'affiliation' => ['@id' => $home . '#hospital'],
        ]);

        return $node;
    }

    /**
     * @return array<int,string>|null
     */
    private static function medical_specialty(int $postId): ?array {
        $terms = get_the_terms($postId, 'spesialisasi-dokter');
        if (!is_array($terms) || $terms === []) {
            return null;
        }

        $specialties = [];
        foreach ($terms as $term) {
            if ($term instanceof \WP_Term) {
                $specialties[] = $term->name;
            }
        }

        return $specialties === [] ? null : $specialties;
    }

    private static function physician_image(int $postId): ?string {
        $photoField = self::acf_value($postId, 'foto_dokter');

        if (is_array($photoField) && !empty($photoField['url']) && is_string($photoField['url'])) {
            return (string) $photoField['url'];
        }

        if (is_numeric($photoField)) {
            $src = wp_get_attachment_image_src((int) $photoField, 'full');
            if (is_array($src)) {
                return (string) $src[0];
            }
        }

        $thumb = get_the_post_thumbnail_url($postId, 'full');
        return is_string($thumb) && $thumb !== '' ? $thumb : null;
    }

    private static function field_text(int $postId, string $key, string $fallback = ''): string {
        $value = self::acf_value($postId, $key);
        if (is_scalar($value)) {
            $text = trim(wp_strip_all_tags((string) $value));
            return $text !== '' ? $text : $fallback;
        }

        return $fallback;
    }

    /**
     * @return mixed
     */
    private static function acf_value(int $postId, string $key) {
        if (function_exists('get_field')) {
            return get_field($key, $postId);
        }

        return get_post_meta($postId, $key, true);
    }
}
