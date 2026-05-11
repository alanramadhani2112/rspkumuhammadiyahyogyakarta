<?php

declare(strict_types=1);

$wpLoad = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wpLoad)) {
    fwrite(STDERR, "wp-load.php tidak ditemukan.\n");
    exit(1);
}

require $wpLoad;

$posts = get_posts([
    'post_type' => 'dokter',
    'post_status' => ['publish', 'draft', 'pending', 'private'],
    'posts_per_page' => 1000,
    'orderby' => 'title',
    'order' => 'ASC',
]);

$metaCount = static function (string $key, string $value = '') use ($posts): int {
    $count = 0;
    foreach ($posts as $post) {
        $metaValue = (string) get_post_meta((int) $post->ID, $key, true);
        if ($value !== '') {
            $count += $metaValue === $value ? 1 : 0;
            continue;
        }

        $count += $metaValue !== '' ? 1 : 0;
    }

    return $count;
};

$duplicates = [];
$groups = [];
foreach ($posts as $post) {
    $display = (string) get_post_meta($post->ID, '_rspku_doctor_name', true);
    $display = $display !== '' ? $display : get_the_title($post);
    $key = audit_name_key($display);
    if ($key !== '') {
        $groups[$key][] = [
            'id' => (int) $post->ID,
            'title' => get_the_title($post),
            'display' => $display,
        ];
    }
}

foreach ($groups as $key => $items) {
    if (count($items) > 1) {
        $duplicates[$key] = $items;
    }
}

echo json_encode([
    'all_doctor_posts' => count($posts),
    'published_doctors' => (int) wp_count_posts('dokter')->publish,
    'team_profile_source' => $metaCount('_rspku_profile_source', 'team-rspku'),
    'synced_from_schedule' => $metaCount('_rspku_synced_from_schedule', '1'),
    'biography_filled' => $metaCount('_rspku_doctor_biography'),
    'education_filled' => $metaCount('_rspku_education'),
    'experience_filled' => $metaCount('_rspku_experience'),
    'training_filled' => $metaCount('_rspku_training'),
    'duplicate_groups' => $duplicates,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function audit_name_key(string $value): string
{
    $value = strtolower(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
    $value = preg_replace('/\b(prof|drg|dr|h|hj)\b/u', ' ', $value) ?: $value;
    $value = preg_replace('/[,].*/u', ' ', $value) ?: $value;
    $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?: $value;
    $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

    return trim($value);
}
