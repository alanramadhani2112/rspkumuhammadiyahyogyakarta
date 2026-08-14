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

$errors = [];
$warnings = [];
$doctorNames = [];
$doctorCount = 0;
$scheduledCount = 0;

foreach (check_doctors() as $doctor) {
    $doctorCount++;
    $postId = (int) $doctor->ID;
    $key = check_name_key((string) get_the_title($doctor));
    if ($key !== '') {
        $doctorNames[$key][] = $postId;
    }

    $rows = get_post_meta($postId, RSPKU_CPT_DoctorSchedule::META_KEY, true);
    if (!is_array($rows) || $rows === []) {
        continue;
    }

    $scheduledCount++;
    $validated = RSPKU_CPT_DoctorSchedule::validateRows($rows);
    foreach ($validated['errors'] as $error) {
        $errors[] = [
            'doctor_id' => $postId,
            'type' => 'invalid_slot',
            'message' => $error['message'] ?? 'Invalid schedule row.',
        ];
    }

    foreach ($validated['rows'] as $index => $row) {
        $termId = absint($row['specialization_term_id'] ?? 0);
        if ($termId <= 0) {
            $errors[] = ['doctor_id' => $postId, 'row' => $index + 1, 'type' => 'missing_specialization_term'];
            continue;
        }

        $term = get_term($termId, 'spesialisasi-dokter');
        if (!$term instanceof WP_Term) {
            $errors[] = ['doctor_id' => $postId, 'row' => $index + 1, 'type' => 'orphan_specialization_term', 'term_id' => $termId];
        }
    }

    $indexedDays = array_map('strval', get_post_meta($postId, RSPKU_CPT_DoctorSchedule::DAY_INDEX_META_KEY, false));
    $actualDays = array_values(array_unique(array_map(static fn (array $row): string => (string) ($row['day'] ?? ''), $validated['rows'])));
    sort($indexedDays);
    sort($actualDays);
    if ($indexedDays !== $actualDays) {
        $errors[] = ['doctor_id' => $postId, 'type' => 'day_index_mismatch', 'indexed' => $indexedDays, 'actual' => $actualDays];
    }
}

foreach ($doctorNames as $ids) {
    if (count($ids) > 1) {
        $errors[] = ['type' => 'duplicate_normalized_doctor_name', 'doctor_ids' => $ids];
    }
}

$runtimeFiles = [
    $root . '/wp-content/themes/rspku-theme/app/Repositories/DoctorScheduleRepository.php',
    $root . '/wp-content/themes/rspku-theme/app/Services/DoctorDirectorySync.php',
];
foreach ($runtimeFiles as $file) {
    $source = file_get_contents($file) ?: '';
    foreach (['TablePress::load_model', 'private const TABLE_ID', 'use TablePress'] as $needle) {
        if (str_contains($source, $needle)) {
            $errors[] = ['type' => 'runtime_tablepress_dependency', 'file' => str_replace($root . '/', '', $file), 'needle' => $needle];
        }
    }
}

$result = [
    'ok' => $errors === [],
    'doctor_count' => $doctorCount,
    'scheduled_count' => $scheduledCount,
    'error_count' => count($errors),
    'warning_count' => count($warnings),
    'errors' => $errors,
    'warnings' => $warnings,
];

echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($errors === [] ? 0 : 1);

/** @return array<int,WP_Post> */
function check_doctors(): array
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

function check_name_key(string $name): string
{
    $name = strtolower(remove_accents(wp_strip_all_tags($name)));
    $name = preg_replace('/\b(dr|drg|prof|sp|subsp|mkes|m\.kes|msc|m\.sc)\b/i', ' ', $name) ?? $name;
    $name = preg_replace('/[^a-z0-9]+/', '', $name) ?? $name;

    return trim($name);
}