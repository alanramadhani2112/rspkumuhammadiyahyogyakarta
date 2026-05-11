<?php

declare(strict_types=1);

$wpLoad = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wpLoad)) {
    fwrite(STDERR, "wp-load.php tidak ditemukan.\n");
    exit(1);
}

require $wpLoad;

$dryRun = in_array('--dry-run', $argv, true);
$metaKeys = [
    '_rspku_education',
    'pendidikan_dokter',
    '_rspku_experience',
    'pengalaman_dokter',
    '_rspku_training',
    'pelatihan_dokter',
];

$posts = get_posts([
    'post_type' => 'dokter',
    'post_status' => ['publish', 'draft', 'pending', 'private'],
    'posts_per_page' => 1000,
    'fields' => 'ids',
]);

$changes = [];
foreach ($posts as $postId) {
    foreach ($metaKeys as $key) {
        $value = get_post_meta((int) $postId, $key, true);
        if (!is_string($value) || $value === '') {
            continue;
        }

        $normalized = normalize_doctor_credential_text($value);
        if ($normalized === $value) {
            continue;
        }

        $changes[] = [
            'post_id' => (int) $postId,
            'title' => get_the_title((int) $postId),
            'meta_key' => $key,
        ];

        if (!$dryRun) {
            update_post_meta((int) $postId, $key, $normalized);
        }
    }
}

echo json_encode([
    'dry_run' => $dryRun,
    'changed_meta_rows' => count($changes),
    'changes' => array_slice($changes, 0, 50),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function normalize_doctor_credential_text(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = str_replace(['?', '–', '—'], '-', $value);
    $value = preg_replace('/[\x{2013}\x{2014}\x{2212}]/u', '-', $value) ?: $value;
    $value = preg_replace('/(?<!^)(?<!\n)(?<=[\p{L}\)])(?=\d{4}\s*-\s*[\p{L}])/u', "\n", $value) ?: $value;
    $value = preg_replace('/(?<=\d{4})\s*-\s*(?=(?:sekarang|present)\b)/iu', ' - ', $value) ?: $value;
    $value = preg_replace('/(?<=\d{4})\s*-\s*(?=[^\n-])/u', ' - ', $value) ?: $value;
    $value = preg_replace('/\s+-\s+/u', ' - ', $value) ?: $value;
    $value = preg_replace('/[ \t]+\n/u', "\n", $value) ?: $value;
    $value = preg_replace('/\n{3,}/u', "\n\n", $value) ?: $value;

    return trim($value);
}
