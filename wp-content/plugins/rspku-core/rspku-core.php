<?php
/**
 * Plugin Name: RSPKU Core
 * Description: Headless CMS helpers and normalized REST API endpoints for RS PKU Muhammadiyah Yogyakarta.
 * Version: 0.1.0
 * Author: RSPKU Muhammadiyah Yogyakarta
 * Text Domain: rspku-core
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class RSPKU_Core {
    private const REST_NAMESPACE = 'rspku/v1';

    private const SEARCH_PER_PAGE_MAX = 50;

    private const COLLECTION_PER_PAGE_MAX = 50;

    private const HEADLESS_POST_TYPES = [
        'post',
        'page',
        'dokter',
        'layanan',
        'poliklinik',
        'manajemen-rs',
        'jurnal',
        'rawat-inap',
    ];

    private const SEARCH_POST_TYPES = [
        'post',
        'dokter',
        'layanan',
        'poliklinik',
        'manajemen-rs',
        'jurnal',
        'rawat-inap',
    ];

    private const SEARCH_TYPE_LABELS = [
        'post' => 'Artikel',
        'dokter' => 'Dokter',
        'layanan' => 'Layanan',
        'poliklinik' => 'Poliklinik',
        'manajemen-rs' => 'Manajemen RS',
        'jurnal' => 'Jurnal',
        'rawat-inap' => 'Rawat Inap',
    ];

    private const SEARCH_TYPE_ALIASES = [
        'artikel' => 'post',
        'articles' => 'post',
        'posts' => 'post',
        'doctor' => 'dokter',
        'doctors' => 'dokter',
        'dokter' => 'dokter',
        'service' => 'layanan',
        'services' => 'layanan',
        'layanan' => 'layanan',
        'polyclinic' => 'poliklinik',
        'polyclinics' => 'poliklinik',
        'poli' => 'poliklinik',
        'poliklinik' => 'poliklinik',
        'management' => 'manajemen-rs',
        'manajemen' => 'manajemen-rs',
        'manajemen-rs' => 'manajemen-rs',
        'journal' => 'jurnal',
        'journals' => 'jurnal',
        'jurnal' => 'jurnal',
        'room' => 'rawat-inap',
        'rooms' => 'rawat-inap',
        'rawat-inap' => 'rawat-inap',
    ];

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_rest_api']);
    }

    public static function register_rest_api(): void {
        /*
         * rspku_acf exposes a normalized dump of ACF fields for headless
         * consumers. This can leak internal or unvetted meta (private notes,
         * draft copy, reference IDs) that was not meant for public output.
         *
         * Disabled by default since M3 (security hardening). Consumers that
         * actually need it can opt in per post type via filter:
         *
         *     add_filter( 'rspku/rest/expose_acf', '__return_true' );
         *     add_filter( 'rspku/rest/expose_acf/dokter', '__return_true' );
         *
         * The normalizers (normalize_doctor, normalize_service, ...) still
         * expose the curated, non-sensitive subset via the collection
         * endpoints.
         */
        foreach (self::HEADLESS_POST_TYPES as $postType) {
            $expose = (bool) apply_filters('rspku/rest/expose_acf', false, $postType);
            $expose = (bool) apply_filters('rspku/rest/expose_acf/' . $postType, $expose);

            if (!$expose) {
                continue;
            }

            register_rest_field(
                $postType,
                'rspku_acf',
                [
                    'get_callback' => static function (array $object): array {
                        return self::get_normalized_acf_fields((int) $object['id']);
                    },
                    'schema' => [
                        'description' => 'Normalized ACF fields for headless frontend usage. Disabled by default.',
                        'type' => 'object',
                        'context' => ['view', 'edit'],
                    ],
                ]
            );
        }

        register_rest_field(
            self::HEADLESS_POST_TYPES,
            'rspku_featured_image',
            [
                'get_callback' => static function (array $object): ?array {
                    return self::get_featured_image((int) $object['id']);
                },
                'schema' => [
                    'description' => 'Normalized featured image object.',
                    'type' => ['object', 'null'],
                    'context' => ['view', 'edit'],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/site',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_site'],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/home',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_home'],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/menu/(?P<slug>[A-Za-z0-9_-]+)',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_menu'],
                'permission_callback' => '__return_true',
                'args' => [
                    'slug' => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key',
                    ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/search',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_search'],
                'permission_callback' => '__return_true',
                'args' => [
                    'q' => [
                        'required' => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'search' => [
                        'required' => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'post_type' => [
                        'required' => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'types' => [
                        'required' => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'page' => [
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'per_page' => [
                        'default' => 10,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );

        self::register_collection_routes('posts', 'post', [self::class, 'normalize_article']);
        self::register_collection_routes('doctors', 'dokter', [self::class, 'normalize_doctor']);
        self::register_collection_routes('services', 'layanan', [self::class, 'normalize_service']);
        self::register_collection_routes('polyclinics', 'poliklinik', [self::class, 'normalize_polyclinic']);
        self::register_collection_routes('management', 'manajemen-rs', [self::class, 'normalize_management']);
        self::register_collection_routes('journals', 'jurnal', [self::class, 'normalize_journal']);
        self::register_collection_routes('rooms', 'rawat-inap', [self::class, 'normalize_room']);
    }

    /**
     * @param callable(WP_Post, bool): array $normalizer
     */
    private static function register_collection_routes(string $route, string $postType, callable $normalizer): void {
        register_rest_route(
            self::REST_NAMESPACE,
            '/' . $route,
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => static function (WP_REST_Request $request) use ($postType, $normalizer): WP_REST_Response {
                    return self::get_collection($request, $postType, $normalizer);
                },
                'permission_callback' => '__return_true',
                'args' => self::get_collection_args(),
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/' . $route . '/(?P<slug>[A-Za-z0-9_-]+)',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => static function (WP_REST_Request $request) use ($postType, $normalizer) {
                    return self::get_single_by_slug($request, $postType, $normalizer);
                },
                'permission_callback' => '__return_true',
                'args' => [
                    'slug' => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_title',
                    ],
                ],
            ]
        );
    }

    private static function get_collection_args(): array {
        return [
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'default' => 20,
                'sanitize_callback' => 'absint',
            ],
            'search' => [
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'orderby' => [
                'default' => 'title',
                'sanitize_callback' => 'sanitize_key',
            ],
            'order' => [
                'default' => 'asc',
                'sanitize_callback' => 'sanitize_key',
            ],
            'category' => [
                'required' => false,
                'sanitize_callback' => 'sanitize_title',
            ],
            'tag' => [
                'required' => false,
                'sanitize_callback' => 'sanitize_title',
            ],
            'polyclinic' => [
                'required' => false,
                'sanitize_callback' => 'sanitize_title',
            ],
        ];
    }

    public static function get_site(): array {
        $customLogoId = (int) get_theme_mod('custom_logo');

        return [
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'home_url' => home_url('/'),
            'rest_url' => rest_url(),
            'timezone' => wp_timezone_string(),
            'language' => get_bloginfo('language'),
            'logo' => $customLogoId > 0 ? self::normalize_attachment($customLogoId) : null,
        ];
    }

    public static function get_menu(WP_REST_Request $request) {
        $slug = (string) $request['slug'];
        $menu = wp_get_nav_menu_object($slug);

        if (!$menu && $slug === 'primary') {
            $menu = wp_get_nav_menu_object('Primary Menu');
        }

        if (!$menu) {
            return new WP_Error(
                'rspku_menu_not_found',
                'Menu not found.',
                ['status' => 404]
            );
        }

        $items = wp_get_nav_menu_items($menu->term_id, ['update_post_term_cache' => false]);
        if (!is_array($items)) {
            $items = [];
        }

        $flatItems = [];
        foreach ($items as $item) {
            $item = wp_setup_nav_menu_item($item);
            $id = (int) $item->ID;
            $objectId = isset($item->object_id) ? (int) $item->object_id : 0;
            $url = isset($item->url) ? (string) $item->url : '';
            $title = trim((string) $item->title);

            if ($title === '' && $objectId > 0) {
                $title = get_the_title($objectId);
            }

            $flatItems[$id] = [
                'id' => $id,
                'parent_id' => isset($item->menu_item_parent) ? (int) $item->menu_item_parent : 0,
                'order' => isset($item->menu_order) ? (int) $item->menu_order : 0,
                'title' => html_entity_decode($title, ENT_QUOTES, get_bloginfo('charset')),
                'url' => $url,
                'path' => self::url_to_path($url),
                'is_external' => self::is_external_url($url),
                'target' => isset($item->target) ? (string) $item->target : '',
                'type' => isset($item->type) ? (string) $item->type : '',
                'object' => isset($item->object) ? (string) $item->object : '',
                'object_id' => $objectId,
                'classes' => array_values(array_filter((array) ($item->classes ?? []))),
                'children' => [],
            ];
        }

        $tree = [];
        foreach ($flatItems as $id => &$item) {
            $parentId = (int) $item['parent_id'];
            if ($parentId > 0 && isset($flatItems[$parentId])) {
                $flatItems[$parentId]['children'][] = &$item;
                continue;
            }

            $tree[] = &$item;
        }
        unset($item);

        return [
            'id' => (int) $menu->term_id,
            'name' => (string) $menu->name,
            'slug' => (string) $menu->slug,
            'items' => $tree,
        ];
    }

    public static function get_search(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $query = trim((string) ($request->get_param('q') ?: $request->get_param('search')));

        /*
         * M3 hardening: validate query length before doing expensive
         * LIKE '%...%' queries that JOIN postmeta. Both empty-but-present
         * queries and overly long queries are rejected early.
         */
        if ($query !== '') {
            $queryLength = function_exists('mb_strlen') ? mb_strlen($query) : strlen($query);
            if ($queryLength < 2 || $queryLength > 100) {
                $error = new WP_REST_Response([
                    'code' => 'rspku_invalid_query',
                    'message' => 'Search query must be between 2 and 100 characters.',
                    'data' => ['status' => 400, 'length' => $queryLength],
                ], 400);

                return $error;
            }
        }

        /*
         * Rate limit per client IP. 60 requests / 60 seconds is generous for
         * interactive use but slams the door on scripted abuse that would
         * otherwise chew through DB cycles.
         */
        $rateLimitResponse = self::enforce_rate_limit('search', 60, 60);
        if ($rateLimitResponse instanceof WP_REST_Response) {
            return $rateLimitResponse;
        }

        $postTypes = self::parse_search_post_types($request->get_param('post_type') ?: $request->get_param('types'));
        $perPage = min(max((int) $request->get_param('per_page'), 1), self::SEARCH_PER_PAGE_MAX);
        $page = max((int) $request->get_param('page'), 1);

        if ($query === '') {
            return new WP_REST_Response([
                'query' => '',
                'post_types' => $postTypes,
                'page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'total_pages' => 0,
                'items' => [],
            ]);
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $typePlaceholders = implode(',', array_fill(0, count($postTypes), '%s'));
        $offset = ($page - 1) * $perPage;
        $metaJoin = "LEFT JOIN {$wpdb->postmeta} pm
                     ON pm.post_id = p.ID
                    AND pm.meta_key NOT LIKE '\\_%'";
        $where = "p.post_status = 'publish'
                  AND p.post_type IN ({$typePlaceholders})
                  AND (
                    p.post_title LIKE %s
                    OR p.post_excerpt LIKE %s
                    OR p.post_content LIKE %s
                    OR pm.meta_value LIKE %s
                  )";

        $countSql = "SELECT COUNT(DISTINCT p.ID)
                     FROM {$wpdb->posts} p
                     {$metaJoin}
                     WHERE {$where}";
        $countArgs = array_merge($postTypes, [$like, $like, $like, $like]);
        $total = (int) $wpdb->get_var($wpdb->prepare($countSql, $countArgs));

        $scoreSql = "CASE
                        WHEN p.post_title LIKE %s THEN 100
                        WHEN pm.meta_key IN ('nama_dokter','nama_layanan','nama_poli','nama','jabatan','judul_jurnal','nama_kamar')
                             AND pm.meta_value LIKE %s THEN 90
                        WHEN p.post_excerpt LIKE %s THEN 70
                        WHEN pm.meta_key IN ('deskripsi_singkat_layanan','deskripsi_singkat','profil_dokter','deskripsi_jurnal','deskripsi')
                             AND pm.meta_value LIKE %s THEN 65
                        WHEN p.post_content LIKE %s THEN 50
                        WHEN pm.meta_value LIKE %s THEN 30
                        ELSE 0
                     END";
        $idsSql = "SELECT p.ID, MAX({$scoreSql}) AS relevance
                   FROM {$wpdb->posts} p
                   {$metaJoin}
                   WHERE {$where}
                   GROUP BY p.ID
                   ORDER BY relevance DESC, p.post_date DESC, p.post_title ASC
                   LIMIT %d OFFSET %d";
        $idsArgs = array_merge(
            [$like, $like, $like, $like, $like, $like],
            $postTypes,
            [$like, $like, $like, $like, $perPage, $offset]
        );
        $ids = array_map('intval', $wpdb->get_col($wpdb->prepare($idsSql, $idsArgs)));

        $items = [];
        foreach ($ids as $postId) {
            $post = get_post($postId);
            if ($post instanceof WP_Post) {
                $items[] = self::normalize_search_result($post);
            }
        }

        $response = new WP_REST_Response([
            'query' => $query,
            'post_types' => $postTypes,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
            'items' => $items,
        ]);
        $response->header('X-WP-Total', (string) $total);
        $response->header('X-WP-TotalPages', (string) ceil($total / $perPage));

        return $response;
    }

    /**
     * @param callable(WP_Post, bool): array $normalizer
     */
    private static function get_collection(WP_REST_Request $request, string $postType, callable $normalizer): WP_REST_Response {
        $queryParams = $request->get_query_params();
        $rateLimitResponse = self::enforce_rate_limit('collection_' . $postType, 120, 60);
        if ($rateLimitResponse instanceof WP_REST_Response) {
            return $rateLimitResponse;
        }

        $perPage = min(max((int) $request->get_param('per_page'), 1), self::COLLECTION_PER_PAGE_MAX);
        $page = max((int) $request->get_param('page'), 1);
        $defaultOrder = $postType === 'post' ? 'desc' : 'asc';
        $defaultOrderby = $postType === 'post' ? 'date' : 'title';
        $orderParam = array_key_exists('order', $queryParams) ? (string) $request->get_param('order') : $defaultOrder;
        $orderbyParam = array_key_exists('orderby', $queryParams) ? (string) $request->get_param('orderby') : $defaultOrderby;
        $order = strtolower($orderParam) === 'desc' ? 'DESC' : 'ASC';
        $orderby = self::sanitize_orderby($orderbyParam);

        $args = [
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'order' => $order,
            'orderby' => $orderby,
            'no_found_rows' => false,
        ];

        $search = trim((string) $request->get_param('search'));
        if ($search !== '') {
            $args['s'] = $search;
        }

        if ($postType === 'layanan') {
            $category = trim((string) $request->get_param('category'));
            if ($category !== '') {
                $args['tax_query'] = [
                    [
                        'taxonomy' => 'kategori-layanan',
                        'field' => 'slug',
                        'terms' => $category,
                    ],
                ];
            }
        }

        if ($postType === 'post') {
            $taxQuery = [];
            $category = trim((string) $request->get_param('category'));
            if ($category !== '') {
                $taxQuery[] = [
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => $category,
                ];
            }

            $tag = trim((string) $request->get_param('tag'));
            if ($tag !== '') {
                $taxQuery[] = [
                    'taxonomy' => 'post_tag',
                    'field' => 'slug',
                    'terms' => $tag,
                ];
            }

            if ($taxQuery) {
                $args['tax_query'] = count($taxQuery) > 1
                    ? array_merge(['relation' => 'AND'], $taxQuery)
                    : $taxQuery;
            }
        }

        if ($postType === 'dokter') {
            $polyclinic = trim((string) $request->get_param('polyclinic'));
            if ($polyclinic !== '') {
                $polyclinicPost = get_page_by_path($polyclinic, OBJECT, 'poliklinik');
                if ($polyclinicPost instanceof WP_Post) {
                    $args['meta_query'] = self::get_polyclinic_meta_query((int) $polyclinicPost->ID);
                }
            }
        }

        $query = new WP_Query($args);
        $items = array_map(
            static fn (WP_Post $post): array => $normalizer($post, false),
            $query->posts
        );

        $response = new WP_REST_Response($items);
        $response->header('X-WP-Total', (string) $query->found_posts);
        $response->header('X-WP-TotalPages', (string) $query->max_num_pages);

        return $response;
    }

    /**
     * @param callable(WP_Post, bool): array $normalizer
     */
    private static function get_single_by_slug(WP_REST_Request $request, string $postType, callable $normalizer) {
        $slug = (string) $request['slug'];
        $post = get_page_by_path($slug, OBJECT, $postType);

        if (!$post instanceof WP_Post || $post->post_status !== 'publish') {
            return new WP_Error(
                'rspku_post_not_found',
                'Post not found.',
                ['status' => 404]
            );
        }

        return $normalizer($post, true);
    }

    public static function get_home(): array {
        $homePage = get_page_by_path('beranda', OBJECT, 'page');
        $homePayload = null;

        if ($homePage instanceof WP_Post && $homePage->post_status === 'publish') {
            $homePayload = self::normalize_page($homePage, true);
        }

        return [
            'site' => self::get_site(),
            'page' => $homePayload,
            'menu' => self::get_menu_by_slug('primary'),
            'content_modules' => [
                'latest_posts' => self::get_recent_items('post', [self::class, 'normalize_article'], 6, 'date', 'DESC'),
                'featured_services' => self::get_recent_items('layanan', [self::class, 'normalize_service'], 8, 'title', 'ASC'),
                'polyclinics' => self::get_recent_items('poliklinik', [self::class, 'normalize_polyclinic'], 12, 'title', 'ASC'),
                'doctors' => self::get_recent_items('dokter', [self::class, 'normalize_doctor'], 6, 'title', 'ASC'),
                'management' => self::get_recent_items('manajemen-rs', [self::class, 'normalize_management'], 9, 'menu_order', 'ASC'),
                'rooms' => self::get_recent_items('rawat-inap', [self::class, 'normalize_room'], 6, 'title', 'ASC'),
            ],
            'counts' => [
                'posts' => self::count_published_posts('post'),
                'doctors' => self::count_published_posts('dokter'),
                'services' => self::count_published_posts('layanan'),
                'polyclinics' => self::count_published_posts('poliklinik'),
                'management' => self::count_published_posts('manajemen-rs'),
                'journals' => self::count_published_posts('jurnal'),
                'rooms' => self::count_published_posts('rawat-inap'),
            ],
        ];
    }

    public static function normalize_article(WP_Post $post, bool $includeDetail = true): array {
        $data = self::normalize_base_post($post);
        $data['excerpt'] = self::normalize_excerpt($post);
        $data['categories'] = self::normalize_terms((int) $post->ID, 'category');
        $data['tags'] = self::normalize_terms((int) $post->ID, 'post_tag');
        $data['author'] = self::normalize_author((int) $post->post_author);
        $data['seo'] = self::normalize_seo((int) $post->ID, $data['title'], $data['excerpt'], $data['url']);

        if ($includeDetail) {
            $data['content'] = apply_filters('the_content', $post->post_content);
        }

        return $data;
    }

    public static function normalize_page(WP_Post $post, bool $includeDetail = true): array {
        $data = self::normalize_base_post($post);
        $data['excerpt'] = self::normalize_excerpt($post);
        $data['seo'] = self::normalize_seo((int) $post->ID, $data['title'], $data['excerpt'], $data['url']);

        if ($includeDetail) {
            $data['content'] = apply_filters('the_content', $post->post_content);
            $data['elementor'] = [
                'has_data' => self::has_elementor_data((int) $post->ID),
            ];
        }

        return $data;
    }

    public static function normalize_doctor(WP_Post $post, bool $includeDetail = true): array {
        $postId = (int) $post->ID;
        $polyclinic = self::normalize_post_reference(self::get_field($postId, 'pilih_poliklinik_dokter'));

        $data = self::normalize_base_post($post);
        $data['name'] = self::field_text($postId, 'nama_dokter', $data['title']);
        $data['photo'] = self::image_from_field(self::get_field($postId, 'foto_dokter'));
        $data['profile'] = self::field_html($postId, 'profil_dokter');
        $data['polyclinic'] = $polyclinic;
        $data['schedule'] = self::normalize_acf_value(self::get_field($postId, 'jadwal_praktek'));

        if ($includeDetail) {
            $data['education'] = self::normalize_acf_value(self::get_field($postId, 'pendidikan_dokter'));
            $data['experience'] = self::normalize_acf_value(self::get_field($postId, 'pengalaman_dokter'));
            $data['training'] = self::normalize_acf_value(self::get_field($postId, 'pelatihan_dokter'));
        }

        return $data;
    }

    public static function normalize_service(WP_Post $post, bool $includeDetail = true): array {
        $postId = (int) $post->ID;
        $data = self::normalize_base_post($post);
        $data['name'] = self::field_text($postId, 'nama_layanan', $data['title']);
        $data['image'] = self::image_from_field(self::get_field($postId, 'gambar_layanan'));
        $data['summary'] = self::field_text($postId, 'deskripsi_singkat_layanan');
        $data['categories'] = self::normalize_terms($postId, 'kategori-layanan');

        if ($includeDetail) {
            $data['detail'] = self::field_html($postId, 'detail_layanan');
        }

        return $data;
    }

    public static function normalize_polyclinic(WP_Post $post, bool $includeDetail = true): array {
        $postId = (int) $post->ID;
        $data = self::normalize_base_post($post);
        $data['name'] = self::field_text($postId, 'nama_poli', $data['title']);
        $data['image'] = self::image_from_field(self::get_field($postId, 'gambar_poli'));
        $data['summary'] = self::field_text($postId, 'deskripsi_singkat');

        if ($includeDetail) {
            $data['detail'] = self::field_html($postId, 'detail_poli');
            $data['doctors'] = self::get_doctors_for_polyclinic($postId);
        }

        return $data;
    }

    public static function normalize_management(WP_Post $post, bool $includeDetail = true): array {
        $postId = (int) $post->ID;
        $data = self::normalize_base_post($post);
        $data['name'] = self::field_text($postId, 'nama', $data['title']);
        $data['position'] = self::field_text($postId, 'jabatan');
        $data['photo'] = self::image_from_field(self::get_field($postId, 'foto_profile'));

        return $data;
    }

    public static function normalize_journal(WP_Post $post, bool $includeDetail = true): array {
        $postId = (int) $post->ID;
        $data = self::normalize_base_post($post);
        $data['journal_title'] = self::field_text($postId, 'judul_jurnal', $data['title']);
        $data['description'] = self::field_text($postId, 'deskripsi_jurnal');
        $data['document'] = self::file_from_field(self::get_field($postId, 'file_dokumen'));

        return $data;
    }

    public static function normalize_room(WP_Post $post, bool $includeDetail = true): array {
        $postId = (int) $post->ID;
        $data = self::normalize_base_post($post);
        $data['name'] = self::field_text($postId, 'nama_kamar', $data['title']);
        $data['category'] = self::field_text($postId, 'kategori_kamar');
        $data['bed_count'] = self::field_int($postId, 'jumlah_tempat_tidur');
        $data['area_square_meters'] = self::normalize_number(self::get_field_by_prefix($postId, 'luas_kamar'));
        $data['daily_rate'] = self::field_text($postId, 'tarif_per_hari_rp');
        $data['facilities'] = self::normalize_acf_value(self::get_field($postId, 'fasilitas_kamar'));
        $data['included'] = self::normalize_acf_value(self::get_field($postId, 'sudah_termasuk'));
        $data['gallery'] = self::gallery_from_field(self::get_field($postId, 'foto_kamar'));

        if ($includeDetail) {
            $data['description'] = self::field_text($postId, 'deskripsi');
        }

        return $data;
    }

    private static function normalize_search_result(WP_Post $post): array {
        $postId = (int) $post->ID;
        $base = self::normalize_base_post($post);
        $result = [
            'id' => $base['id'],
            'type' => $base['type'],
            'type_label' => self::SEARCH_TYPE_LABELS[$post->post_type] ?? $post->post_type,
            'module' => self::get_search_module($post->post_type),
            'slug' => $base['slug'],
            'title' => $base['title'],
            'url' => $base['url'],
            'path' => $base['path'],
            'date' => $base['date'],
            'modified' => $base['modified'],
            'excerpt' => self::normalize_excerpt($post),
            'image' => $base['featured_image'],
        ];

        switch ($post->post_type) {
            case 'dokter':
                $polyclinic = self::normalize_post_reference(self::get_field($postId, 'pilih_poliklinik_dokter'));
                $result['title'] = self::field_text($postId, 'nama_dokter', $base['title']);
                $result['subtitle'] = $polyclinic['title'] ?? '';
                $result['excerpt'] = self::excerpt_from_text(self::field_html($postId, 'profil_dokter'));
                $result['image'] = self::image_from_field(self::get_field($postId, 'foto_dokter'));
                $result['polyclinic'] = $polyclinic;
                break;

            case 'layanan':
                $result['title'] = self::field_text($postId, 'nama_layanan', $base['title']);
                $result['excerpt'] = self::field_text($postId, 'deskripsi_singkat_layanan');
                $result['image'] = self::image_from_field(self::get_field($postId, 'gambar_layanan'));
                $result['categories'] = self::normalize_terms($postId, 'kategori-layanan');
                break;

            case 'poliklinik':
                $result['title'] = self::field_text($postId, 'nama_poli', $base['title']);
                $result['excerpt'] = self::field_text($postId, 'deskripsi_singkat');
                $result['image'] = self::image_from_field(self::get_field($postId, 'gambar_poli'));
                break;

            case 'manajemen-rs':
                $result['title'] = self::field_text($postId, 'nama', $base['title']);
                $result['subtitle'] = self::field_text($postId, 'jabatan');
                $result['image'] = self::image_from_field(self::get_field($postId, 'foto_profile'));
                break;

            case 'jurnal':
                $result['title'] = self::field_text($postId, 'judul_jurnal', $base['title']);
                $result['excerpt'] = self::field_text($postId, 'deskripsi_jurnal');
                $result['document'] = self::file_from_field(self::get_field($postId, 'file_dokumen'));
                break;

            case 'rawat-inap':
                $gallery = self::gallery_from_field(self::get_field($postId, 'foto_kamar'));
                $result['title'] = self::field_text($postId, 'nama_kamar', $base['title']);
                $result['subtitle'] = self::field_text($postId, 'kategori_kamar');
                $result['excerpt'] = self::field_text($postId, 'deskripsi');
                $result['image'] = $result['image'] ?: ($gallery[0] ?? null);
                $result['daily_rate'] = self::field_text($postId, 'tarif_per_hari_rp');
                break;

            case 'post':
                $result['categories'] = self::normalize_terms($postId, 'category');
                break;
        }

        if (!isset($result['subtitle'])) {
            $result['subtitle'] = '';
        }

        return $result;
    }

    private static function parse_search_post_types($value): array {
        if (is_array($value)) {
            $rawTypes = $value;
        } else {
            $rawTypes = preg_split('/[,\s]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        $postTypes = [];
        foreach ($rawTypes ?: [] as $rawType) {
            $key = sanitize_key((string) $rawType);
            $postType = self::SEARCH_TYPE_ALIASES[$key] ?? $key;
            if (in_array($postType, self::SEARCH_POST_TYPES, true)) {
                $postTypes[] = $postType;
            }
        }

        $postTypes = array_values(array_unique($postTypes));

        return $postTypes ?: self::SEARCH_POST_TYPES;
    }

    private static function get_search_module(string $postType): string {
        return match ($postType) {
            'post' => 'posts',
            'dokter' => 'doctors',
            'layanan' => 'services',
            'poliklinik' => 'polyclinics',
            'manajemen-rs' => 'management',
            'jurnal' => 'journals',
            'rawat-inap' => 'rooms',
            default => $postType,
        };
    }

    private static function normalize_base_post(WP_Post $post): array {
        $title = get_the_title($post);
        $link = get_permalink($post);

        return [
            'id' => (int) $post->ID,
            'type' => $post->post_type,
            'slug' => $post->post_name,
            'title' => html_entity_decode($title, ENT_QUOTES, get_bloginfo('charset')),
            'url' => $link,
            'path' => self::url_to_path($link),
            'date' => get_the_date(DATE_ATOM, $post),
            'modified' => get_the_modified_date(DATE_ATOM, $post),
            'featured_image' => self::get_featured_image((int) $post->ID),
        ];
    }

    private static function get_doctors_for_polyclinic(int $polyclinicId): array {
        $query = new WP_Query([
            'post_type' => 'dokter',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => self::get_polyclinic_meta_query($polyclinicId),
        ]);

        return array_map(
            static fn (WP_Post $post): array => self::normalize_doctor($post, false),
            $query->posts
        );
    }

    private static function get_polyclinic_meta_query(int $polyclinicId): array {
        return [
            'relation' => 'OR',
            [
                'key' => 'pilih_poliklinik_dokter',
                'value' => (string) $polyclinicId,
                'compare' => '=',
            ],
            [
                'key' => 'pilih_poliklinik_dokter',
                'value' => '"' . $polyclinicId . '"',
                'compare' => 'LIKE',
            ],
        ];
    }

    /**
     * @param callable(WP_Post, bool): array $normalizer
     */
    private static function get_recent_items(string $postType, callable $normalizer, int $limit, string $orderby, string $order): array {
        $query = new WP_Query([
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => self::sanitize_orderby($orderby),
            'order' => strtoupper($order) === 'DESC' ? 'DESC' : 'ASC',
            'no_found_rows' => true,
        ]);

        return array_map(
            static fn (WP_Post $post): array => $normalizer($post, false),
            $query->posts
        );
    }

    private static function count_published_posts(string $postType): int {
        $count = wp_count_posts($postType);
        return isset($count->publish) ? (int) $count->publish : 0;
    }

    private static function get_featured_image(int $postId): ?array {
        $thumbnailId = get_post_thumbnail_id($postId);
        return $thumbnailId ? self::normalize_attachment((int) $thumbnailId) : null;
    }

    private static function image_from_field($value): ?array {
        if ($value instanceof WP_Post) {
            return self::normalize_attachment((int) $value->ID);
        }

        if (is_numeric($value)) {
            return self::normalize_attachment((int) $value);
        }

        if (is_array($value)) {
            $id = 0;
            if (isset($value['ID']) && is_numeric($value['ID'])) {
                $id = (int) $value['ID'];
            } elseif (isset($value['id']) && is_numeric($value['id'])) {
                $id = (int) $value['id'];
            }

            if ($id > 0) {
                return self::normalize_attachment($id);
            }

            if (!empty($value['url']) && is_string($value['url'])) {
                return [
                    'id' => null,
                    'url' => $value['url'],
                    'alt' => isset($value['alt']) ? (string) $value['alt'] : '',
                    'title' => isset($value['title']) ? (string) $value['title'] : '',
                    'width' => isset($value['width']) ? (int) $value['width'] : null,
                    'height' => isset($value['height']) ? (int) $value['height'] : null,
                ];
            }
        }

        return null;
    }

    private static function file_from_field($value): ?array {
        if ($value instanceof WP_Post) {
            return self::normalize_attachment((int) $value->ID);
        }

        if (is_numeric($value)) {
            return self::normalize_attachment((int) $value);
        }

        if (is_array($value)) {
            $id = 0;
            if (isset($value['ID']) && is_numeric($value['ID'])) {
                $id = (int) $value['ID'];
            } elseif (isset($value['id']) && is_numeric($value['id'])) {
                $id = (int) $value['id'];
            }

            if ($id > 0) {
                return self::normalize_attachment($id);
            }

            if (!empty($value['url']) && is_string($value['url'])) {
                return [
                    'id' => null,
                    'url' => $value['url'],
                    'title' => isset($value['title']) ? (string) $value['title'] : '',
                    'filename' => isset($value['filename']) ? (string) $value['filename'] : basename((string) $value['url']),
                    'mime_type' => isset($value['mime_type']) ? (string) $value['mime_type'] : null,
                    'filesize' => isset($value['filesize']) ? (int) $value['filesize'] : null,
                ];
            }
        }

        if (is_string($value) && preg_match('#^https?://#i', $value)) {
            return [
                'id' => null,
                'url' => $value,
                'title' => basename($value),
                'filename' => basename($value),
                'mime_type' => null,
                'filesize' => null,
            ];
        }

        return null;
    }

    private static function gallery_from_field($value): array {
        if (!is_array($value)) {
            return [];
        }

        $images = [];
        foreach ($value as $item) {
            $image = self::image_from_field($item);
            if ($image !== null) {
                $images[] = $image;
            }
        }

        return $images;
    }

    private static function normalize_attachment(int $attachmentId): ?array {
        if ($attachmentId <= 0 || get_post_type($attachmentId) !== 'attachment') {
            return null;
        }

        $src = wp_get_attachment_image_src($attachmentId, 'full');
        $url = $src ? $src[0] : wp_get_attachment_url($attachmentId);
        if (!$url) {
            return null;
        }

        $metadata = wp_get_attachment_metadata($attachmentId);
        $sizes = [];
        if (is_array($metadata) && isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $sizeName => $sizeData) {
                $sizeSrc = wp_get_attachment_image_src($attachmentId, (string) $sizeName);
                if (!$sizeSrc) {
                    continue;
                }

                $sizes[$sizeName] = [
                    'url' => $sizeSrc[0],
                    'width' => (int) $sizeSrc[1],
                    'height' => (int) $sizeSrc[2],
                ];
            }
        }

        $attachedFile = get_attached_file($attachmentId);
        $urlPath = wp_parse_url($url, PHP_URL_PATH);
        $filename = is_string($attachedFile) && $attachedFile !== ''
            ? basename($attachedFile)
            : ($urlPath ? basename($urlPath) : '');

        return [
            'id' => $attachmentId,
            'url' => $url,
            'alt' => (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
            'title' => get_the_title($attachmentId),
            'filename' => $filename,
            'width' => $src ? (int) $src[1] : null,
            'height' => $src ? (int) $src[2] : null,
            'mime_type' => get_post_mime_type($attachmentId),
            'filesize' => self::get_attachment_filesize($attachmentId),
            'sizes' => $sizes,
        ];
    }

    private static function get_normalized_acf_fields(int $postId): array {
        $fields = [];

        if (function_exists('get_fields')) {
            $acfFields = get_fields($postId);
            if (is_array($acfFields)) {
                $fields = $acfFields;
            }
        } else {
            foreach (get_post_meta($postId) as $key => $values) {
                if (str_starts_with((string) $key, '_')) {
                    continue;
                }

                $fields[$key] = count($values) === 1 ? maybe_unserialize($values[0]) : array_map('maybe_unserialize', $values);
            }
        }

        return self::normalize_acf_value($fields);
    }

    private static function get_field(int $postId, string $fieldName) {
        if (function_exists('get_field')) {
            return get_field($fieldName, $postId);
        }

        return get_post_meta($postId, $fieldName, true);
    }

    private static function field_text(int $postId, string $fieldName, string $default = ''): string {
        $value = self::get_field($postId, $fieldName);
        if (is_scalar($value)) {
            $value = trim(wp_strip_all_tags((string) $value));
            return $value !== '' ? $value : $default;
        }

        return $default;
    }

    private static function field_html(int $postId, string $fieldName): string {
        $value = self::get_field($postId, $fieldName);
        if (!is_scalar($value)) {
            return '';
        }

        return wp_kses_post((string) $value);
    }

    private static function field_int(int $postId, string $fieldName): ?int {
        $value = self::get_field($postId, $fieldName);
        if ($value === '' || $value === null || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private static function get_field_by_prefix(int $postId, string $prefix) {
        if (function_exists('get_fields')) {
            $fields = get_fields($postId);
            if (is_array($fields)) {
                foreach ($fields as $key => $value) {
                    if (is_string($key) && str_starts_with($key, $prefix)) {
                        return $value;
                    }
                }
            }
        }

        foreach (get_post_meta($postId) as $key => $values) {
            if (is_string($key) && !str_starts_with($key, '_') && str_starts_with($key, $prefix)) {
                return isset($values[0]) ? maybe_unserialize($values[0]) : null;
            }
        }

        return null;
    }

    private static function normalize_acf_value($value) {
        if ($value instanceof WP_Post) {
            return self::normalize_post_reference($value);
        }

        if (is_array($value)) {
            if (self::looks_like_image_array($value)) {
                $image = self::image_from_field($value);
                if ($image !== null) {
                    return $image;
                }
            }

            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = self::normalize_acf_value($item);
            }

            return $normalized;
        }

        return $value;
    }

    private static function normalize_number($value): ?float {
        if ($value === '' || $value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $numbersOnly = preg_replace('/[^0-9,.-]/', '', $value);
            $normalized = $numbersOnly !== null ? str_replace(',', '.', $numbersOnly) : '';
            return is_numeric($normalized) ? (float) $normalized : null;
        }

        return null;
    }

    private static function looks_like_image_array(array $value): bool {
        return isset($value['url'])
            && (isset($value['ID']) || isset($value['id']) || isset($value['mime_type']) || isset($value['sizes']));
    }

    private static function normalize_post_reference($value): ?array {
        $post = null;

        if ($value instanceof WP_Post) {
            $post = $value;
        } elseif (is_numeric($value)) {
            $post = get_post((int) $value);
        } elseif (is_array($value) && isset($value['ID']) && is_numeric($value['ID'])) {
            $post = get_post((int) $value['ID']);
        }

        if (!$post instanceof WP_Post) {
            return null;
        }

        $link = get_permalink($post);

        return [
            'id' => (int) $post->ID,
            'type' => $post->post_type,
            'slug' => $post->post_name,
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES, get_bloginfo('charset')),
            'url' => $link,
            'path' => self::url_to_path($link),
        ];
    }

    private static function normalize_terms(int $postId, string $taxonomy): array {
        $terms = get_the_terms($postId, $taxonomy);
        if (!is_array($terms)) {
            return [];
        }

        return array_values(array_map(
            static fn (WP_Term $term): array => [
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'taxonomy' => $term->taxonomy,
            ],
            $terms
        ));
    }

    private static function normalize_author(int $authorId): ?array {
        if ($authorId <= 0) {
            return null;
        }

        $user = get_userdata($authorId);
        if (!$user) {
            return null;
        }

        return [
            'id' => (int) $user->ID,
            'name' => $user->display_name,
            'slug' => $user->user_nicename,
        ];
    }

    private static function get_attachment_filesize(int $attachmentId): ?int {
        $file = get_attached_file($attachmentId);
        if (!$file || !is_file($file)) {
            return null;
        }

        $filesize = filesize($file);
        return $filesize === false ? null : (int) $filesize;
    }

    private static function normalize_excerpt(WP_Post $post): string {
        $excerpt = has_excerpt($post)
            ? $post->post_excerpt
            : wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 32, '...');

        return html_entity_decode(trim((string) $excerpt), ENT_QUOTES, get_bloginfo('charset'));
    }

    private static function excerpt_from_text(string $text, int $words = 32): string {
        $excerpt = wp_trim_words(wp_strip_all_tags(strip_shortcodes($text)), $words, '...');
        return html_entity_decode(trim($excerpt), ENT_QUOTES, get_bloginfo('charset'));
    }

    private static function normalize_seo(int $postId, string $fallbackTitle, string $fallbackDescription, string $canonical): array {
        $seoTitle = trim((string) get_post_meta($postId, '_yoast_wpseo_title', true));
        $seoDescription = trim((string) get_post_meta($postId, '_yoast_wpseo_metadesc', true));

        return [
            'title' => $seoTitle !== '' ? wp_strip_all_tags($seoTitle) : $fallbackTitle,
            'description' => $seoDescription !== '' ? wp_strip_all_tags($seoDescription) : $fallbackDescription,
            'canonical' => $canonical,
        ];
    }

    private static function has_elementor_data(int $postId): bool {
        $elementorData = get_post_meta($postId, '_elementor_data', true);
        return is_string($elementorData) && $elementorData !== '';
    }

    private static function get_menu_by_slug(string $slug): ?array {
        $request = new WP_REST_Request('GET', '/' . self::REST_NAMESPACE . '/menu/' . $slug);
        $request->set_param('slug', $slug);
        $menu = self::get_menu($request);

        return is_array($menu) ? $menu : null;
    }

    private static function sanitize_orderby(string $orderby): string {
        $allowed = ['date', 'modified', 'title', 'menu_order', 'rand'];
        return in_array($orderby, $allowed, true) ? $orderby : 'title';
    }

    private static function url_to_path(string $url): string {
        if ($url === '' || $url === '#') {
            return $url;
        }

        $homeHost = wp_parse_url(home_url(), PHP_URL_HOST);
        $urlHost = wp_parse_url($url, PHP_URL_HOST);

        if ($urlHost && $homeHost && strtolower((string) $urlHost) !== strtolower((string) $homeHost)) {
            return $url;
        }

        $path = wp_parse_url($url, PHP_URL_PATH);
        $query = wp_parse_url($url, PHP_URL_QUERY);
        $fragment = wp_parse_url($url, PHP_URL_FRAGMENT);

        $normalized = $path ?: '/';
        if ($query) {
            $normalized .= '?' . $query;
        }
        if ($fragment) {
            $normalized .= '#' . $fragment;
        }

        return $normalized;
    }

    private static function is_external_url(string $url): bool {
        if ($url === '' || $url === '#') {
            return false;
        }

        $homeHost = wp_parse_url(home_url(), PHP_URL_HOST);
        $urlHost = wp_parse_url($url, PHP_URL_HOST);

        return (bool) $urlHost
            && (bool) $homeHost
            && strtolower((string) $urlHost) !== strtolower((string) $homeHost);
    }

    /*
     * -------------------------------------------------------------------
     * Security helpers (added in M3 — REST API hardening).
     * -------------------------------------------------------------------
     */

    /**
     * Throttle a public REST endpoint per client IP using transients.
     *
     * Returns a WP_REST_Response (HTTP 429 + Retry-After) if the caller
     * has exceeded $limit requests in the last $windowSeconds. Returns
     * null when the caller is still within budget.
     */
    private static function enforce_rate_limit(string $bucket, int $limit, int $windowSeconds): ?WP_REST_Response {
        if ($limit <= 0 || $windowSeconds <= 0) {
            return null;
        }

        $ip = self::client_ip();
        $key = 'rspku_rl_' . sanitize_key($bucket) . '_' . md5($ip);
        $count = (int) get_transient($key);

        if ($count >= $limit) {
            $response = new WP_REST_Response([
                'code' => 'rspku_rate_limited',
                'message' => 'Too many requests. Please slow down.',
                'data' => ['status' => 429],
            ], 429);
            $response->header('Retry-After', (string) $windowSeconds);
            $response->header('X-RateLimit-Limit', (string) $limit);
            $response->header('X-RateLimit-Remaining', '0');

            return $response;
        }

        set_transient($key, $count + 1, $windowSeconds);

        return null;
    }

    /**
     * Best-effort resolution of the client IP. Trusts common CDN/proxy
     * headers only when REMOTE_ADDR is an explicit trusted proxy.
     *
     * Falls back to 0.0.0.0 so transient keys stay deterministic even
     * when upstream did not provide a usable address.
     */
    private static function client_ip(): string {
        $remote = self::valid_ip_from_server('REMOTE_ADDR');

        if ($remote !== null && self::is_trusted_proxy($remote)) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $header) {
                $forwarded = self::valid_ip_from_server($header);

                if ($forwarded !== null) {
                    return $forwarded;
                }
            }
        }

        return $remote ?? '0.0.0.0';
    }

    private static function valid_ip_from_server(string $key): ?string {
        if (empty($_SERVER[$key])) {
            return null;
        }

        $raw = sanitize_text_field((string) wp_unslash((string) $_SERVER[$key]));
        $first = trim((string) explode(',', $raw)[0]);

        return $first !== '' && filter_var($first, FILTER_VALIDATE_IP) !== false ? $first : null;
    }

    private static function is_trusted_proxy(string $remote): bool {
        $trusted = defined('RSPKU_TRUSTED_PROXY_IPS') ? constant('RSPKU_TRUSTED_PROXY_IPS') : [];
        $trusted = apply_filters('rspku_trusted_proxy_ips', $trusted);

        if (is_string($trusted)) {
            $trusted = explode(',', $trusted);
        }

        foreach ((array) $trusted as $ip) {
            if ($remote === trim((string) $ip)) {
                return true;
            }
        }

        return false;
    }
}

RSPKU_Core::init();
