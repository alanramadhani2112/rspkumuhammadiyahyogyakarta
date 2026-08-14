<?php

declare(strict_types=1);

$wpLoad = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wpLoad)) {
    fwrite(STDERR, "wp-load.php tidak ditemukan.\n");
    exit(1);
}

require $wpLoad;

$file = restore_arg('--snapshot');
$commit = in_array('--commit', $argv, true);

if ($file === null || !is_readable($file)) {
    fwrite(STDERR, "Gunakan --snapshot /path/report.json.\n");
    exit(1);
}

$payload = json_decode((string) file_get_contents($file), true);
$snapshot = is_array($payload) ? ($payload['snapshot'] ?? []) : [];
if (!is_array($snapshot)) {
    fwrite(STDERR, "Snapshot tidak valid.\n");
    exit(1);
}

$restored = [];
foreach ($snapshot as $item) {
    if (!is_array($item)) {
        continue;
    }

    $postId = absint($item['id'] ?? 0);
    if ($postId <= 0 || get_post_type($postId) !== 'dokter') {
        continue;
    }

    $restored[] = $postId;
    if (!$commit) {
        continue;
    }

    restore_meta($postId, '_rspku_doctor_schedule', $item['_rspku_doctor_schedule'] ?? []);
    restore_meta($postId, 'jadwal_praktek', $item['jadwal_praktek'] ?? []);
    restore_multi_meta($postId, '_rspku_schedule_day', $item['_rspku_schedule_day'] ?? []);
    restore_meta($postId, '_rspku_schedule_managed_specializations', $item['_rspku_schedule_managed_specializations'] ?? []);
    wp_set_object_terms($postId, wp_parse_id_list($item['spesialisasi_dokter_terms'] ?? []), 'spesialisasi-dokter', false);
    clean_post_cache($postId);
}

echo wp_json_encode([
    'mode' => $commit ? 'commit' : 'dry-run',
    'restorable_doctors' => count($restored),
    'doctor_ids' => $restored,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function restore_arg(string $name): ?string
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

function restore_meta(int $postId, string $key, mixed $value): void
{
    if ($value === '' || $value === null || $value === []) {
        delete_post_meta($postId, $key);
        return;
    }

    update_post_meta($postId, $key, $value);
}

function restore_multi_meta(int $postId, string $key, mixed $values): void
{
    delete_post_meta($postId, $key);
    foreach (array_values(array_filter(array_map('strval', is_array($values) ? $values : []))) as $value) {
        add_post_meta($postId, $key, $value, false);
    }
}
