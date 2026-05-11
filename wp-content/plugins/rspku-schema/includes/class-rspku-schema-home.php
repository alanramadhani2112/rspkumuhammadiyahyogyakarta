<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Front-page schema bundle: Hospital (full organization node) + WebSite
 * (with SearchAction so Google can show a sitelinks search box).
 */
final class RSPKU_Schema_Home {

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function build(): array {
        $home = home_url('/');
        $hospital = RSPKU_Schema_Helpers::hospital_node();

        $website = RSPKU_Schema_Helpers::compact_node([
            '@type' => 'WebSite',
            '@id' => $home . '#website',
            'url' => $home,
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'inLanguage' => str_replace('_', '-', (string) get_bloginfo('language')),
            'publisher' => ['@id' => $home . '#hospital'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $home . '?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ]);

        return [$hospital, $website];
    }
}
