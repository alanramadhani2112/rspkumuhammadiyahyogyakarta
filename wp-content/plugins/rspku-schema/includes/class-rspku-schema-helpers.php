<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared helpers: JSON-LD output, organization node, address, and common
 * normalisers. All methods are static and stateless.
 */
final class RSPKU_Schema_Helpers {

    /**
     * Canonical organization payload. Can be overridden via filter.
     *
     * @return array<string,mixed>
     */
    public static function organization_data(): array {
        $home = home_url('/');
        $logo = self::site_logo_url();
        $settings = class_exists('RSPKU_Settings_API') ? RSPKU_Settings_API::all() : [];
        $street = self::setting_text($settings, 'address_street', 'Jl. KH. Ahmad Dahlan No.20');
        $district = self::setting_text($settings, 'address_district', 'Ngupasan, Kec. Gondomanan');
        $city = self::setting_text($settings, 'address_city', 'Kota Yogyakarta');
        $province = self::setting_text($settings, 'address_province', 'Daerah Istimewa Yogyakarta 55122');

        $defaults = [
            'name' => self::setting_text($settings, 'site_name', 'RS PKU Muhammadiyah Yogyakarta'),
            'alternate_name' => 'RSPKU Jogja',
            'url' => $home,
            'logo' => $logo,
            'telephone' => self::setting_text($settings, 'phone_main_link', '+62274512653'),
            'email' => self::setting_text($settings, 'email', 'info@rspkujogja.co.id'),
            'address' => [
                'street' => trim($street . ', ' . $district, ', '),
                'locality' => $city,
                'region' => $province,
                'postal_code' => '55122',
                'country' => 'ID',
            ],
            'geo' => [
                'latitude' => -7.8022,
                'longitude' => 110.3644,
            ],
            'same_as' => [
                self::setting_text($settings, 'social_facebook', ''),
                self::setting_text($settings, 'social_instagram', ''),
                self::setting_text($settings, 'social_youtube', ''),
                self::setting_text($settings, 'social_twitter', ''),
                self::setting_text($settings, 'social_linkedin', ''),
            ],
            'opening_hours' => [
                // IGD 24 jam
                [
                    'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    'opens' => '00:00',
                    'closes' => '23:59',
                    'department' => 'IGD',
                ],
                // Rawat Jalan 07:00-20:00 tiap hari
                [
                    'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    'opens' => '07:00',
                    'closes' => '20:00',
                    'department' => 'Rawat Jalan',
                ],
            ],
        ];

        /** @var array<string,mixed> $data */
        $data = apply_filters('rspku/schema/organization', $defaults);

        return $data;
    }

    /**
     * Build the Hospital @graph node used as the anchor for many other
     * schema types via @id references.
     *
     * @return array<string,mixed>
     */
    public static function hospital_node(): array {
        $data = self::organization_data();
        $hospitalId = self::organization_id();

        $node = [
            '@type' => ['Hospital', 'MedicalOrganization', 'LocalBusiness'],
            '@id' => $hospitalId,
            'name' => $data['name'],
            'alternateName' => $data['alternate_name'] ?? null,
            'url' => $data['url'],
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => self::postal_address($data['address'] ?? []),
            'geo' => self::geo_coordinates($data['geo'] ?? []),
            'sameAs' => self::array_of_strings($data['same_as'] ?? []),
            'openingHoursSpecification' => self::opening_hours($data['opening_hours'] ?? []),
        ];

        if (!empty($data['logo'])) {
            $node['logo'] = (string) $data['logo'];
            $node['image'] = (string) $data['logo'];
        }

        return self::compact_node($node);
    }

    public static function yoast_active(): bool {
        return defined('WPSEO_VERSION');
    }

    public static function organization_id(): string {
        return home_url('/') . (self::yoast_active() ? '#organization' : '#hospital');
    }

    /**
     * @return array<string,string>
     */
    public static function organization_id_ref(): array {
        return ['@id' => self::organization_id()];
    }

    /**
     * @param array<string,mixed> $address
     * @return array<string,string>|null
     */
    public static function postal_address(array $address): ?array {
        if ($address === []) {
            return null;
        }

        return self::compact_node([
            '@type' => 'PostalAddress',
            'streetAddress' => (string) ($address['street'] ?? ''),
            'addressLocality' => (string) ($address['locality'] ?? ''),
            'addressRegion' => (string) ($address['region'] ?? ''),
            'postalCode' => (string) ($address['postal_code'] ?? ''),
            'addressCountry' => (string) ($address['country'] ?? ''),
        ]);
    }

    /**
     * @param array<string,mixed> $geo
     * @return array<string,mixed>|null
     */
    public static function geo_coordinates(array $geo): ?array {
        if ($geo === []) {
            return null;
        }

        $lat = isset($geo['latitude']) ? (float) $geo['latitude'] : null;
        $lng = isset($geo['longitude']) ? (float) $geo['longitude'] : null;

        if ($lat === null || $lng === null) {
            return null;
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => $lat,
            'longitude' => $lng,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $entries
     * @return array<int,array<string,mixed>>|null
     */
    public static function opening_hours(array $entries): ?array {
        if ($entries === []) {
            return null;
        }

        $out = [];
        foreach ($entries as $entry) {
            $days = isset($entry['days']) && is_array($entry['days']) ? $entry['days'] : [];
            $out[] = self::compact_node([
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => self::array_of_strings($days),
                'opens' => isset($entry['opens']) ? (string) $entry['opens'] : null,
                'closes' => isset($entry['closes']) ? (string) $entry['closes'] : null,
                'name' => isset($entry['department']) ? (string) $entry['department'] : null,
            ]);
        }

        return $out;
    }

    /**
     * @return array<string,string>|null
     */
    public static function image_from_post(int $postId): ?array {
        $thumbId = get_post_thumbnail_id($postId);
        if (!$thumbId) {
            return null;
        }

        $src = wp_get_attachment_image_src((int) $thumbId, 'full');
        if (!is_array($src)) {
            return null;
        }

        return [
            '@type' => 'ImageObject',
            'url' => (string) $src[0],
            'width' => (string) ((int) $src[1]),
            'height' => (string) ((int) $src[2]),
        ];
    }

    public static function site_logo_url(): ?string {
        $logoId = (int) get_theme_mod('custom_logo');
        if ($logoId <= 0) {
            return null;
        }

        $src = wp_get_attachment_image_src($logoId, 'full');

        return is_array($src) ? (string) $src[0] : null;
    }

    /**
     * Strip null / empty values so schema.org payloads stay lean and
     * Google Rich Results validation stays happy.
     *
     * @param array<string,mixed> $node
     * @return array<string,mixed>
     */
    public static function compact_node(array $node): array {
        $out = [];
        foreach ($node as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * @param mixed $value
     * @return array<int,string>|null
     */
    public static function array_of_strings($value): ?array {
        if (!is_array($value)) {
            return null;
        }

        $strings = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $strings[] = $item;
            }
        }

        return $strings === [] ? null : $strings;
    }

    /**
     * @param array<string,mixed> $settings
     */
    private static function setting_text(array $settings, string $key, string $fallback): string {
        $value = $settings[$key] ?? $fallback;
        if (!is_scalar($value)) {
            return $fallback;
        }

        $text = trim((string) $value);
        return $text !== '' ? $text : $fallback;
    }

    /**
     * Emit an array of schema nodes as a single @graph document.
     *
     * @param array<int,array<string,mixed>> $nodes
     */
    public static function output_graph(array $nodes): void {
        $document = [
            '@context' => 'https://schema.org',
            '@graph' => array_values($nodes),
        ];

        $json = wp_json_encode(
            $document,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if (!is_string($json)) {
            return;
        }

        echo "\n<script type=\"application/ld+json\" id=\"rspku-schema\">\n";
        echo $json;
        echo "\n</script>\n";
    }
}
