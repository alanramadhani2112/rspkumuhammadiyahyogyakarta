<?php

declare(strict_types=1);

$wpLoad = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wpLoad)) {
    fwrite(STDERR, "wp-load.php tidak ditemukan.\n");
    exit(1);
}

require $wpLoad;

$auditFile = import_arg('--audit');
$commit = in_array('--commit', $argv, true);
$allowPartial = in_array('--allow-partial', $argv, true);

if ($auditFile === null || !is_readable($auditFile)) {
    fwrite(STDERR, "Gunakan --audit /path/audit.json.\n");
    exit(1);
}

$audit = json_decode((string) file_get_contents($auditFile), true);
if (!is_array($audit)) {
    fwrite(STDERR, "Audit JSON tidak valid.\n");
    exit(1);
}

$items = is_array($audit['items'] ?? null) ? $audit['items'] : [];
$skipped = array_values(array_filter($items, static fn ($item): bool => is_array($item) && ($item['status'] ?? '') !== 'importable'));
if ($commit && $skipped !== [] && !$allowPartial) {
    fwrite(STDERR, "Import dibatalkan: masih ada " . count($skipped) . " row blocked. Jalankan audit ulang atau pakai --allow-partial setelah review manual.\n");
    exit(2);
}

$beforeHash = import_state_hash();
$imported = [];
$created = [];
$unchanged = [];

foreach ($items as $item) {
    if (!is_array($item) || ($item['status'] ?? '') !== 'importable') {
        continue;
    }

    $doctorId = absint($item['doctor_id'] ?? 0);
    $slots = is_array($item['slots'] ?? null) ? $item['slots'] : [];
    if ($slots === []) {
        continue;
    }

    if ($doctorId <= 0 && !empty($item['create_doctor'])) {
        if (!$commit) {
            $imported[] = 0;
            continue;
        }

        $doctorId = import_create_doctor($item);
        $created[] = $doctorId;
    }

    if ($doctorId <= 0 || get_post_type($doctorId) !== 'dokter') {
        continue;
    }

    $existing = get_post_meta($doctorId, '_rspku_doctor_schedule', true);
    if (import_json($existing) === import_json($slots)) {
        $unchanged[] = $doctorId;
        continue;
    }

    if ($commit) {
        import_persist_schedule($doctorId, $slots);
        update_post_meta($doctorId, '_rspku_schedule_imported_from_tablepress', [
            'table_id' => (string) ($audit['table_id'] ?? '1'),
            'imported_at' => gmdate(DATE_ATOM),
            'audit_file' => $auditFile,
        ]);
    }

    $imported[] = $doctorId;
}

$afterHash = import_state_hash();
$result = [
    'mode' => $commit ? 'commit' : 'dry-run',
    'table_id' => (string) ($audit['table_id'] ?? '1'),
    'source_hash' => md5((string) file_get_contents($auditFile)),
    'blocked_rows' => count($skipped),
    'allow_partial' => $allowPartial,
    'imported_count' => count($imported),
    'created_count' => count($created),
    'unchanged_count' => count($unchanged),
    'imported_doctor_ids' => array_values(array_unique($imported)),
    'created_doctor_ids' => array_values(array_unique($created)),
    'unchanged_doctor_ids' => array_values(array_unique($unchanged)),
    'checksum_before' => $beforeHash,
    'checksum_after' => $afterHash,
    'zero_write' => !$commit && $beforeHash === $afterHash,
];

if ($commit) {
    update_option('rspku_native_schedule_import_last', [
        'timestamp' => gmdate(DATE_ATOM),
        'table_id' => $result['table_id'],
        'source_hash' => $result['source_hash'],
        'counts' => [
            'imported' => count($imported),
            'unchanged' => count($unchanged),
            'blocked' => count($skipped),
        ],
        'operator_id' => get_current_user_id(),
        'audit_file' => $auditFile,
    ], false);
}

echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function import_arg(string $name): ?string
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

function import_create_doctor(array $item): int
{
    $name = sanitize_text_field((string) ($item['doctor_name'] ?? ''));
    if ($name === '') {
        return 0;
    }

    $doctorId = wp_insert_post([
        'post_type' => 'dokter',
        'post_status' => 'publish',
        'post_title' => $name,
    ], true);

    if (is_wp_error($doctorId) || absint($doctorId) <= 0) {
        return 0;
    }

    $doctorId = absint($doctorId);
    update_post_meta($doctorId, '_rspku_synced_from_schedule', 1);
    update_post_meta($doctorId, '_rspku_schedule_source_name', $name);
    update_post_meta($doctorId, 'nama_dokter', $name);

    return $doctorId;
}

function import_persist_schedule(int $doctorId, array $rows): void
{
    update_post_meta($doctorId, '_rspku_doctor_schedule', $rows);
    update_post_meta($doctorId, 'jadwal_praktek', $rows);
    import_replace_indexed_meta($doctorId, '_rspku_schedule_day', array_column($rows, 'day'));
    import_sync_managed_terms($doctorId, import_term_ids($rows));
    clean_post_cache($doctorId);
}

function import_sync_managed_terms(int $doctorId, array $newManagedTerms): void
{
    $oldManagedTerms = wp_parse_id_list(get_post_meta($doctorId, '_rspku_schedule_managed_specializations', true));
    $currentTerms = wp_get_post_terms($doctorId, 'spesialisasi-dokter', ['fields' => 'ids']);
    $currentTerms = is_array($currentTerms) ? wp_parse_id_list($currentTerms) : [];
    $curatedTerms = array_values(array_diff($currentTerms, $oldManagedTerms));
    $mergedTerms = array_values(array_unique(array_merge($curatedTerms, $newManagedTerms)));
    wp_set_object_terms($doctorId, $mergedTerms, 'spesialisasi-dokter', false);
    update_post_meta($doctorId, '_rspku_schedule_managed_specializations', $newManagedTerms);
}

function import_term_ids(array $rows): array
{
    return array_values(array_unique(array_filter(array_map(static fn (array $row): int => absint($row['specialization_term_id'] ?? 0), $rows))));
}

function import_replace_indexed_meta(int $postId, string $metaKey, array $values): void
{
    delete_post_meta($postId, $metaKey);
    foreach (array_values(array_unique(array_filter(array_map('strval', $values)))) as $value) {
        add_post_meta($postId, $metaKey, $value, false);
    }
}

function import_state_hash(): string
{
    $data = [];
    $posts = get_posts(['post_type' => 'dokter', 'post_status' => ['publish', 'draft', 'pending', 'private'], 'posts_per_page' => -1]);
    foreach ($posts as $post) {
        $postId = (int) $post->ID;
        $data[] = [
            'id' => $postId,
            'schedule' => get_post_meta($postId, '_rspku_doctor_schedule', true),
            'legacy' => get_post_meta($postId, 'jadwal_praktek', true),
            'days' => get_post_meta($postId, '_rspku_schedule_day', false),
            'managed_terms' => get_post_meta($postId, '_rspku_schedule_managed_specializations', true),
            'terms' => wp_get_post_terms($postId, 'spesialisasi-dokter', ['fields' => 'ids']),
        ];
    }
    return md5(import_json($data));
}

function import_json(mixed $value): string
{
    return wp_json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
}
