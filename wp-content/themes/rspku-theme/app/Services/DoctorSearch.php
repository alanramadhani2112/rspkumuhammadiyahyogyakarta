<?php

declare(strict_types=1);

namespace Rspku\Services;

use Rspku\Helpers\View;
use Rspku\Repositories\DoctorRepository;
use WP_REST_Request;
use WP_REST_Response;

final class DoctorSearch
{
    public static function register(): void
    {
        add_action('wp_ajax_rspku_doctor_search', [self::class, 'ajax']);
        add_action('wp_ajax_nopriv_rspku_doctor_search', [self::class, 'ajax']);
        add_action('rest_api_init', [self::class, 'rest']);
    }

    public static function ajax(): void
    {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'rspku_doctor_search')) {
            wp_send_json_error(['message' => __('Sesi pencarian tidak valid.', 'rspku-theme')], 403);
        }

        $payload = self::search([
            'q' => sanitize_text_field(wp_unslash($_POST['q'] ?? '')),
            'specialization' => sanitize_title(wp_unslash($_POST['specialization'] ?? '')),
            'day' => sanitize_key(wp_unslash($_POST['day'] ?? '')),
            'service' => absint($_POST['service'] ?? 0),
            'page' => absint($_POST['page'] ?? 1),
            'per_page' => absint($_POST['per_page'] ?? 12),
        ]);

        wp_send_json_success($payload);
    }

    public static function rest(): void
    {
        register_rest_route('rspku/v1', '/doctor-search', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => static function (WP_REST_Request $request): WP_REST_Response {
                return new WP_REST_Response(self::search([
                    'q' => sanitize_text_field((string) $request->get_param('q')),
                    'specialization' => sanitize_title((string) $request->get_param('specialization')),
                    'day' => sanitize_key((string) $request->get_param('day')),
                    'service' => absint($request->get_param('service')),
                    'page' => absint($request->get_param('page') ?: 1),
                    'per_page' => absint($request->get_param('per_page') ?: 12),
                ]));
            },
        ]);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private static function search(array $filters): array
    {
        $repository = new DoctorRepository();
        $query = $repository->query($filters);
        $doctors = array_map(fn ($post): array => $repository->normalize($post), $query->posts);
        $pagination = self::pagination((int) $query->max_num_pages, max(1, (int) ($filters['page'] ?? 1)));
        $html = View::compile('partials/doctor-search-results.twig', [
            'doctors' => $doctors,
            'query' => $query,
            'pagination' => $pagination,
            'total' => (int) $query->found_posts,
            'total_pages' => max(1, (int) $query->max_num_pages),
            'current_page' => max(1, (int) ($filters['page'] ?? 1)),
            'per_page' => max(1, (int) ($filters['per_page'] ?? 12)),
        ]);

        return [
            'html' => $html,
            'items' => $doctors,
            'total' => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
        ];
    }

    private static function pagination(int $totalPages, int $currentPage): string
    {
        if ($totalPages <= 1) {
            return '';
        }

        $links = paginate_links([
            'base' => '#page=%#%',
            'format' => '',
            'total' => max(1, $totalPages),
            'current' => max(1, $currentPage),
            'type' => 'list',
            'prev_text' => __('Sebelumnya', 'rspku-theme'),
            'next_text' => __('Berikutnya', 'rspku-theme'),
        ]);

        return is_string($links) ? $links : '';
    }
}
