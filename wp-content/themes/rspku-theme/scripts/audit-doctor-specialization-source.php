<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$wpLoad = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wpLoad)) {
    fwrite(STDERR, "wp-load.php tidak ditemukan.\n");
    exit(1);
}

require $wpLoad;

$outputPath = specialization_audit_arg('--output');
$backupPath = specialization_audit_arg('--backup');
$applyMode = specialization_audit_has_arg('--apply');
$allowClear = specialization_audit_has_arg('--allow-clear');

if ($applyMode && ($backupPath === null || $backupPath === '' || !is_file($backupPath))) {
    fwrite(STDERR, "Apply mode membutuhkan --backup=<path> yang sudah ada. Tidak ada data diubah.\n");
    exit(1);
}

$before = specialization_audit_state_checksum();
$validTerms = specialization_audit_term_index();
$doctors = [];
$backup = [];
$syncCounts = [
    'changed' => 0,
    'unchanged' => 0,
    'skipped_invalid_term' => 0,
    'cleared_no_schedule' => 0,
];
$syncActions = [];
$categories = [
    'missing_schedule_specialization' => [],
    'invalid_schedule_term' => [],
    'taxonomy_without_schedule' => [],
    'schedule_without_taxonomy' => [],
    'taxonomy_differs_from_schedule' => [],
    'polyclinic_relation_missing' => [],
];

foreach (specialization_audit_doctors() as $doctor) {
    $postId = (int) $doctor->ID;
    $taxonomyTerms = specialization_audit_doctor_terms($postId);
    $schedule = specialization_audit_schedule($postId);
    $scheduleTermIds = [];
    $missingRows = [];
    $invalidRows = [];

    foreach ($schedule['rows'] as $index => $row) {
        if (!is_array($row)) {
            continue;
        }

        $termId = absint($row['specialization_term_id'] ?? 0);
        if ($termId <= 0) {
            $missingRows[] = $index + 1;
            continue;
        }

        if (!isset($validTerms[$termId])) {
            $invalidRows[] = ['row' => $index + 1, 'term_id' => $termId];
            continue;
        }

        $scheduleTermIds[] = $termId;
    }

    $scheduleTermIds = specialization_audit_sorted_ids($scheduleTermIds);
    $taxonomyTermIds = specialization_audit_sorted_ids(array_column($taxonomyTerms, 'id'));
    $polyclinicRelations = specialization_audit_polyclinic_relations($postId);
    $doctorCategories = [];
    $willChange = $scheduleTermIds !== $taxonomyTermIds;

    $syncCounts[$willChange ? 'changed' : 'unchanged']++;
    $syncCounts['skipped_invalid_term'] += count($invalidRows);
    if ($willChange && $scheduleTermIds === [] && $taxonomyTermIds !== []) {
        $syncCounts['cleared_no_schedule']++;
    }

    if ($missingRows !== []) {
        $doctorCategories[] = 'missing_schedule_specialization';
    }
    if ($invalidRows !== []) {
        $doctorCategories[] = 'invalid_schedule_term';
    }
    if ($schedule['rows'] === [] && $taxonomyTermIds !== []) {
        $doctorCategories[] = 'taxonomy_without_schedule';
    }
    if ($scheduleTermIds !== [] && $taxonomyTermIds === []) {
        $doctorCategories[] = 'schedule_without_taxonomy';
    }
    if ($scheduleTermIds !== [] && $taxonomyTermIds !== [] && $scheduleTermIds !== $taxonomyTermIds) {
        $doctorCategories[] = 'taxonomy_differs_from_schedule';
    }
    if ($polyclinicRelations === []) {
        $doctorCategories[] = 'polyclinic_relation_missing';
    }

    foreach ($doctorCategories as $category) {
        $categories[$category][] = $postId;
    }

    $doctors[] = [
        'id' => $postId,
        'title' => get_the_title($doctor),
        'schedule_source' => $schedule['source'],
        'schedule_rows' => count($schedule['rows']),
        'schedule_term_ids' => $scheduleTermIds,
        'taxonomy_term_ids' => $taxonomyTermIds,
        'missing_schedule_specialization_rows' => $missingRows,
        'invalid_schedule_term_rows' => $invalidRows,
        'polyclinic_relation_ids' => $polyclinicRelations,
        'categories' => $doctorCategories,
        'sync' => [
            'will_change' => $willChange,
            'target_term_ids' => $scheduleTermIds,
        ],
    ];

    $syncActions[] = ['doctor_id' => $postId, 'term_ids' => $scheduleTermIds, 'will_change' => $willChange];

    $backup[] = [
        'id' => $postId,
        'title' => get_the_title($doctor),
        'spesialisasi_dokter_terms' => $taxonomyTerms,
    ];
}

if ($applyMode) {
    $backupPayload = specialization_audit_read_backup((string) $backupPath);
    if (($backupPayload['doctor_count'] ?? null) !== count($doctors)
        || ($backupPayload['state_checksum'] ?? null) !== $before) {
        fwrite(STDERR, "Backup tidak cocok dengan kondisi data saat ini. Buat backup dry-run baru. Tidak ada data diubah.\n");
        exit(1);
    }
    if ($categories['missing_schedule_specialization'] !== [] || $categories['invalid_schedule_term'] !== []) {
        fwrite(STDERR, "Apply diblokir karena jadwal memiliki spesialisasi kosong atau term tidak valid. Tidak ada data diubah.\n");
        exit(1);
    }
    if (!$allowClear && $syncCounts['cleared_no_schedule'] > 0) {
        fwrite(STDERR, "Apply akan mengosongkan spesialisasi dokter tanpa jadwal. Tinjau audit lalu ulangi dengan --allow-clear. Tidak ada data diubah.\n");
        exit(1);
    }

    foreach ($syncActions as $action) {
        if (!$action['will_change']) {
            continue;
        }

        wp_set_object_terms((int) $action['doctor_id'], $action['term_ids'], 'spesialisasi-dokter', false);
    }
}

$after = specialization_audit_state_checksum();
$result = [
    'mode' => $applyMode ? 'apply' : 'dry-run',
    'generated_at' => gmdate(DATE_ATOM),
    'zero_write' => $before === $after,
    'checksum_before' => $before,
    'checksum_after' => $after,
    'summary' => [
        'doctor_count' => count($doctors),
        'valid_specialization_terms' => count($validTerms),
        'counts' => array_map('count', $categories),
        'sync_counts' => $syncCounts,
        'doctor_ids' => $categories,
    ],
    'doctors' => $doctors,
];

$backupPayload = [
    'mode' => 'dry-run',
    'generated_at' => gmdate(DATE_ATOM),
    'doctor_count' => count($backup),
    'state_checksum' => $before,
    'doctors' => $backup,
];

if ($outputPath !== null && $outputPath !== '') {
    specialization_audit_write_json($outputPath, $result);
}
if (!$applyMode && $backupPath !== null && $backupPath !== '') {
    specialization_audit_write_json($backupPath, $backupPayload);
}

echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function specialization_audit_arg(string $name): ?string
{
    global $argv;
    foreach ($argv as $index => $arg) {
        if ($arg === $name) {
            return isset($argv[$index + 1]) ? (string) $argv[$index + 1] : null;
        }
        if (str_starts_with((string) $arg, $name . '=')) {
            return substr((string) $arg, strlen($name) + 1);
        }
    }

    return null;
}

function specialization_audit_has_arg(string $name): bool
{
    global $argv;
    return in_array($name, $argv, true);
}

function specialization_audit_read_backup(string $path): array
{
    $payload = json_decode((string) file_get_contents($path), true);
    if (!is_array($payload)
        || ($payload['mode'] ?? null) !== 'dry-run'
        || !is_int($payload['doctor_count'] ?? null)
        || !is_string($payload['state_checksum'] ?? null)
        || !is_array($payload['doctors'] ?? null)) {
        fwrite(STDERR, "Format backup tidak valid. Tidak ada data diubah.\n");
        exit(1);
    }

    return $payload;
}

function specialization_audit_doctors(): array
{
    return get_posts([
        'post_type' => 'dokter',
        'post_status' => ['publish', 'future', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'orderby' => 'ID',
        'order' => 'ASC',
    ]);
}

function specialization_audit_schedule(int $postId): array
{
    $rows = get_post_meta($postId, '_rspku_doctor_schedule', true);
    if (is_array($rows) && $rows !== []) {
        return ['source' => '_rspku_doctor_schedule', 'rows' => array_values($rows)];
    }

    $legacyRows = get_post_meta($postId, 'jadwal_praktek', true);
    if (is_array($legacyRows) && $legacyRows !== []) {
        return ['source' => 'jadwal_praktek', 'rows' => array_values($legacyRows)];
    }

    return ['source' => 'none', 'rows' => []];
}

function specialization_audit_term_index(): array
{
    $terms = get_terms(['taxonomy' => 'spesialisasi-dokter', 'hide_empty' => false]);
    if (!is_array($terms)) {
        return [];
    }

    $index = [];
    foreach ($terms as $term) {
        if ($term instanceof WP_Term) {
            $index[(int) $term->term_id] = $term->name;
        }
    }

    return $index;
}

function specialization_audit_doctor_terms(int $postId): array
{
    $terms = wp_get_post_terms($postId, 'spesialisasi-dokter');
    if (!is_array($terms)) {
        return [];
    }

    return array_map(static fn (WP_Term $term): array => [
        'id' => (int) $term->term_id,
        'name' => $term->name,
    ], array_values(array_filter($terms, static fn ($term): bool => $term instanceof WP_Term)));
}

function specialization_audit_polyclinic_relations(int $postId): array
{
    $ids = array_merge(
        specialization_audit_ids(get_post_meta($postId, 'pilih_poliklinik_dokter', true)),
        specialization_audit_ids(get_post_meta($postId, '_rspku_related_polyclinic', true)),
        specialization_audit_ids(get_post_meta($postId, '_rspku_related_polyclinics', true))
    );

    return specialization_audit_sorted_ids($ids);
}

function specialization_audit_ids(mixed $value): array
{
    if (is_array($value)) {
        return wp_parse_id_list($value);
    }

    if (is_numeric($value)) {
        return [(int) $value];
    }

    return [];
}

function specialization_audit_sorted_ids(array $ids): array
{
    $ids = wp_parse_id_list($ids);
    sort($ids);

    return array_values(array_unique($ids));
}

function specialization_audit_state_checksum(): string
{
    $snapshot = [];
    foreach (specialization_audit_doctors() as $doctor) {
        $postId = (int) $doctor->ID;
        $snapshot[] = [
            'id' => $postId,
            '_rspku_doctor_schedule' => get_post_meta($postId, '_rspku_doctor_schedule', true),
            'jadwal_praktek' => get_post_meta($postId, 'jadwal_praktek', true),
            'spesialisasi_dokter_terms' => wp_get_post_terms($postId, 'spesialisasi-dokter', ['fields' => 'ids']),
            'pilih_poliklinik_dokter' => get_post_meta($postId, 'pilih_poliklinik_dokter', true),
            '_rspku_related_polyclinic' => get_post_meta($postId, '_rspku_related_polyclinic', true),
            '_rspku_related_polyclinics' => get_post_meta($postId, '_rspku_related_polyclinics', true),
        ];
    }

    return md5(wp_json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
}

function specialization_audit_write_json(string $path, array $payload): void
{
    $directory = dirname($path);
    if ($directory !== '' && $directory !== '.' && !is_dir($directory)) {
        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Direktori output gagal dibuat: ' . $directory);
        }
    }

    $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($json) || file_put_contents($path, $json) === false) {
        throw new RuntimeException('Output gagal ditulis: ' . $path);
    }
}
