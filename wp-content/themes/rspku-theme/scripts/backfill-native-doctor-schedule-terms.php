<?php

declare(strict_types=1);

$root = dirname(__DIR__, 4);
$wpLoad = $root . '/wp-load.php';
if (!file_exists($wpLoad)) {
    fwrite(STDERR, "wp-load.php tidak ditemukan.\n");
    exit(1);
}

require $wpLoad;
require_once $root . '/wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule.php';

$commit = in_array('--commit', $argv, true);
$updated = [];
$blocked = [];

foreach (backfill_doctors() as $doctor) {
    $postId = (int) $doctor->ID;
    $rows = get_post_meta($postId, RSPKU_CPT_DoctorSchedule::META_KEY, true);
    if (!is_array($rows) || $rows === []) {
        continue;
    }

    $termId = backfill_single_specialization_term_id($postId);
    if ($termId <= 0) {
        $terms = wp_get_post_terms($postId, 'spesialisasi-dokter', ['fields' => 'ids']);
        $terms = is_array($terms) ? wp_parse_id_list($terms) : [];
        $blocked[] = ['doctor_id' => $postId, 'reason' => 'term_count_' . count($terms)];
        continue;
    }

    $changed = false;
    foreach ($rows as &$row) {
        if (!is_array($row)) {
            continue;
        }
        $existingTermId = absint($row['specialization_term_id'] ?? 0);
        if ($existingTermId > 0 && get_term($existingTermId, 'spesialisasi-dokter') instanceof WP_Term) {
            continue;
        }
        $row['specialization_term_id'] = $termId;
        $changed = true;
    }
    unset($row);

    if (!$changed) {
        continue;
    }

    if ($commit) {
        update_post_meta($postId, RSPKU_CPT_DoctorSchedule::META_KEY, $rows);
        update_post_meta($postId, RSPKU_CPT_DoctorSchedule::LEGACY_META_KEY, $rows);
        update_post_meta($postId, RSPKU_CPT_DoctorSchedule::MANAGED_TERMS_META_KEY, [$termId]);
        clean_post_cache($postId);
    }

    $updated[] = $postId;
}

function backfill_single_specialization_term_id(int $postId): int
{
    $terms = wp_get_post_terms($postId, 'spesialisasi-dokter');
    $terms = is_array($terms) ? array_values(array_filter($terms, static fn ($term): bool => $term instanceof WP_Term)) : [];
    if ($terms === []) {
        return 0;
    }

    $children = array_values(array_filter($terms, static fn (WP_Term $term): bool => (int) $term->parent > 0));
    if (count($children) === 1) {
        return (int) $children[0]->term_id;
    }

    if (count($terms) === 1) {
        return (int) $terms[0]->term_id;
    }

    return 0;
}

$result = [
    'mode' => $commit ? 'commit' : 'dry-run',
    'updated_count' => count($updated),
    'blocked_count' => count($blocked),
    'updated_doctor_ids' => $updated,
    'blocked' => $blocked,
];

echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(0);

/** @return array<int,WP_Post> */
function backfill_doctors(): array
{
    $posts = get_posts([
        'post_type' => 'dokter',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ]);

    return array_values(array_filter($posts, static fn ($post): bool => $post instanceof WP_Post));
}
