<?php

declare(strict_types=1);

namespace Rspku\Blocks;

use Rspku\Helpers\View;
use Rspku\Repositories\ContentRepository;
use Rspku\Repositories\DoctorRepository;

final class Registry
{
    public static function register(): void
    {
        add_action('init', [self::class, 'registerBlocks']);
        add_filter('block_categories_all', [self::class, 'blockCategories']);
    }

    public static function registerBlocks(): void
    {
        $blocks = [
            'hero-banner' => [
                'title' => __('RSPKU Hero Banner', 'rspku-theme'),
                'render' => [self::class, 'heroBanner'],
                'attributes' => self::textAttributes(['eyebrow', 'title', 'description', 'ctaLabel', 'ctaUrl', 'secondaryLabel', 'secondaryUrl']),
            ],
            'doctor-search' => [
                'title' => __('RSPKU Doctor Search', 'rspku-theme'),
                'render' => [self::class, 'doctorSearch'],
                'attributes' => [
                    'limit' => ['type' => 'number', 'default' => 8],
                ],
            ],
            'doctor-grid' => [
                'title' => __('RSPKU Doctor Grid', 'rspku-theme'),
                'render' => [self::class, 'doctorGrid'],
                'attributes' => [
                    'limit' => ['type' => 'number', 'default' => 8],
                    'specialization' => ['type' => 'string', 'default' => ''],
                ],
            ],
            'service-cards' => [
                'title' => __('RSPKU Service Cards', 'rspku-theme'),
                'render' => [self::class, 'serviceCards'],
                'attributes' => ['limit' => ['type' => 'number', 'default' => 8]],
            ],
            'faq' => [
                'title' => __('RSPKU FAQ', 'rspku-theme'),
                'render' => [self::class, 'faq'],
                'attributes' => ['items' => ['type' => 'array', 'default' => []]],
            ],
            'cta-banner' => [
                'title' => __('RSPKU CTA Banner', 'rspku-theme'),
                'render' => [self::class, 'ctaBanner'],
                'attributes' => self::textAttributes(['title', 'description', 'ctaLabel', 'ctaUrl']),
            ],
            'insurance-partners' => [
                'title' => __('RSPKU Insurance Partners', 'rspku-theme'),
                'render' => [self::class, 'insurancePartners'],
                'attributes' => ['items' => ['type' => 'array', 'default' => []]],
            ],
            'journal-list' => [
                'title' => __('RSPKU Journal List', 'rspku-theme'),
                'render' => [self::class, 'journalList'],
                'attributes' => ['limit' => ['type' => 'number', 'default' => 6]],
            ],
            'article-list' => [
                'title' => __('RSPKU Article List', 'rspku-theme'),
                'render' => [self::class, 'articleList'],
                'attributes' => ['limit' => ['type' => 'number', 'default' => 6]],
            ],
        ];

        foreach ($blocks as $slug => $block) {
            register_block_type('rspku/' . $slug, [
                'api_version' => 2,
                'title' => $block['title'],
                'category' => 'rspku',
                'icon' => 'heart',
                'attributes' => $block['attributes'],
                'render_callback' => $block['render'],
            ]);
        }
    }

    /**
     * @param array<int,array<string,mixed>> $categories
     * @return array<int,array<string,mixed>>
     */
    public static function blockCategories(array $categories): array
    {
        array_unshift($categories, [
            'slug' => 'rspku',
            'title' => __('RSPKU Components', 'rspku-theme'),
            'icon' => 'heart',
        ]);

        return $categories;
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function heroBanner(array $attributes): string
    {
        return View::compile('blocks/hero-banner.twig', [
            'attributes' => $attributes,
        ]);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function doctorSearch(array $attributes): string
    {
        $repository = new DoctorRepository();
        $limit = min(max((int) ($attributes['limit'] ?? 8), 1), 24);

        return View::compile('blocks/doctor-search.twig', [
            'attributes' => $attributes,
            'doctors' => $repository->list(['per_page' => $limit]),
            'filter_options' => $repository->filterOptions(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rspku_doctor_search'),
        ]);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function doctorGrid(array $attributes): string
    {
        $repository = new DoctorRepository();
        $limit = min(max((int) ($attributes['limit'] ?? 8), 1), 24);

        return View::compile('blocks/doctor-grid.twig', [
            'attributes' => $attributes,
            'doctors' => $repository->list([
                'per_page' => $limit,
                'specialization' => (string) ($attributes['specialization'] ?? ''),
            ]),
        ]);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function serviceCards(array $attributes): string
    {
        $repository = new ContentRepository();
        $limit = min(max((int) ($attributes['limit'] ?? 8), 1), 24);

        return View::compile('blocks/service-cards.twig', [
            'attributes' => $attributes,
            'services' => $repository->featuredServices($limit),
        ]);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function faq(array $attributes): string
    {
        return View::compile('blocks/faq.twig', [
            'items' => is_array($attributes['items'] ?? null) ? $attributes['items'] : [],
        ]);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function ctaBanner(array $attributes): string
    {
        return View::compile('blocks/cta-banner.twig', [
            'attributes' => $attributes,
        ]);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function insurancePartners(array $attributes): string
    {
        return View::compile('blocks/insurance-partners.twig', [
            'items' => is_array($attributes['items'] ?? null) ? $attributes['items'] : [],
        ]);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function journalList(array $attributes): string
    {
        $repository = new ContentRepository();
        $limit = min(max((int) ($attributes['limit'] ?? 6), 1), 24);

        return View::compile('blocks/journal-list.twig', [
            'journals' => $repository->latestJournals($limit),
        ]);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function articleList(array $attributes): string
    {
        $repository = new ContentRepository();
        $limit = min(max((int) ($attributes['limit'] ?? 6), 1), 24);

        return View::compile('blocks/article-list.twig', [
            'articles' => $repository->latestArticles($limit),
        ]);
    }

    /**
     * @param array<int,string> $names
     * @return array<string,array<string,string>>
     */
    private static function textAttributes(array $names): array
    {
        $attributes = [];
        foreach ($names as $name) {
            $attributes[$name] = ['type' => 'string', 'default' => ''];
        }

        return $attributes;
    }
}
