<?php

declare(strict_types=1);

$wpLoad = dirname(__DIR__, 4) . '/wp-load.php';
if (!is_readable($wpLoad)) {
    fwrite(STDERR, "wp-load.php tidak ditemukan.\n");
    exit(1);
}

require $wpLoad;

$apply = in_array('--apply', $argv, true);
$source = import_live_articles_arg('--source') ?? 'https://rspkujogja.com';
$after = import_live_articles_arg('--after') ?? '2026-05-06T15:17:42';
$limit = max(1, min(100, (int) (import_live_articles_arg('--limit') ?? 20)));

$posts = import_live_articles_fetch($source, $after, $limit);
$created = [];
$existing = [];

foreach ($posts as $post) {
    if (!is_array($post)) {
        continue;
    }

    $slug = sanitize_title((string) ($post['slug'] ?? ''));
    if ($slug === '') {
        continue;
    }

    $local = get_page_by_path($slug, OBJECT, 'post');
    if ($local instanceof WP_Post) {
        $existing[] = $slug;
        continue;
    }

    $title = wp_strip_all_tags((string) ($post['title']['rendered'] ?? ''));
    $content = (string) ($post['content']['rendered'] ?? '');
    $excerpt = wp_strip_all_tags((string) ($post['excerpt']['rendered'] ?? ''));
    $date = (string) ($post['date'] ?? current_time('mysql'));
    $dateGmt = (string) ($post['date_gmt'] ?? get_gmt_from_date($date));

    if ($title === '' || $content === '') {
        continue;
    }

    if ($apply) {
        $postId = wp_insert_post([
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_date' => str_replace('T', ' ', $date),
            'post_date_gmt' => str_replace('T', ' ', $dateGmt),
        ], true);

        if (is_wp_error($postId)) {
            fwrite(STDERR, $slug . ': ' . $postId->get_error_message() . "\n");
            continue;
        }

        update_post_meta((int) $postId, '_rspku_imported_from_live_url', esc_url_raw((string) ($post['link'] ?? '')));
        update_post_meta((int) $postId, '_rspku_imported_from_live_at', gmdate(DATE_ATOM));
    }

    $created[] = $slug;
}

echo wp_json_encode([
    'mode' => $apply ? 'apply' : 'dry-run',
    'source' => $source,
    'after' => $after,
    'fetched' => count($posts),
    'existing' => $existing,
    'created' => $created,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

/**
 * @return array<int,mixed>
 */
function import_live_articles_fetch(string $source, string $after, int $limit): array
{
    $url = add_query_arg([
        'after' => $after,
        'order' => 'asc',
        'orderby' => 'date',
        'per_page' => $limit,
        '_fields' => 'id,date,date_gmt,slug,link,title,content,excerpt',
    ], rtrim($source, '/') . '/wp-json/wp/v2/posts');

    $response = wp_remote_get($url, ['timeout' => 30]);
    if (is_wp_error($response)) {
        fwrite(STDERR, $response->get_error_message() . "\n");
        exit(2);
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        fwrite(STDERR, "REST API gagal HTTP {$code}.\n");
        exit(2);
    }

    $payload = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($payload)) {
        fwrite(STDERR, "REST API mengembalikan JSON tidak valid.\n");
        exit(2);
    }

    return $payload;
}

function import_live_articles_arg(string $name): ?string
{
    global $argv;

    foreach ($argv as $index => $arg) {
        if ($arg === $name) {
            return isset($argv[$index + 1]) ? (string) $argv[$index + 1] : null;
        }

        if (str_starts_with($arg, $name . '=')) {
            return substr($arg, strlen($name) + 1);
        }
    }

    return null;
}
