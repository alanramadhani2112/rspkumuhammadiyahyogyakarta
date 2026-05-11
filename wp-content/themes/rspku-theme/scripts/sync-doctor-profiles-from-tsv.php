<?php

declare(strict_types=1);

$wpLoad = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wpLoad)) {
    fwrite(STDERR, "wp-load.php tidak ditemukan.\n");
    exit(1);
}

require $wpLoad;

$dryRun = in_array('--dry-run', $argv, true);
$input = stream_get_contents(STDIN);
if (!is_string($input) || trim($input) === '') {
    fwrite(STDERR, "TSV kosong. Pipe data dokter ke STDIN.\n");
    exit(1);
}

$rows = parse_doctor_profile_tsv($input);
if ($rows === []) {
    fwrite(STDERR, "Tidak ada baris dokter yang bisa diparse.\n");
    exit(1);
}

$existingPosts = get_posts([
    'post_type' => 'dokter',
    'post_status' => ['publish', 'draft', 'pending', 'private'],
    'posts_per_page' => 800,
    'orderby' => 'title',
    'order' => 'ASC',
]);

$index = build_doctor_index($existingPosts);
$stats = [
    'rows' => count($rows),
    'updated' => 0,
    'created' => 0,
    'exact' => 0,
    'loose' => 0,
    'fuzzy' => 0,
    'skipped' => 0,
];
$created = [];
$updated = [];
$fuzzyMatches = [];
$skipped = [];

foreach ($rows as $row) {
    $name = clean_text($row['Nama Dokter'] ?? '');
    if ($name === '') {
        $stats['skipped']++;
        $skipped[] = 'Baris tanpa nama dokter';
        continue;
    }

    $degree = clean_text($row['Gelar'] ?? '');
    $displayName = display_name($name, $degree);
    $match = match_doctor_post($name, $displayName, $existingPosts, $index);
    $postId = (int) ($match['post_id'] ?? 0);
    $isCreate = $postId <= 0;

    if ($isCreate) {
        $payload = [
            'post_type' => 'dokter',
            'post_status' => 'publish',
            'post_title' => $displayName,
            'post_name' => sanitize_title($displayName),
            'post_author' => get_current_user_id() ?: 1,
        ];

        $profileHtml = profile_html((string) ($row['Profil'] ?? ''));
        if ($profileHtml !== '') {
            $payload['post_content'] = $profileHtml;
            $payload['post_excerpt'] = wp_trim_words(wp_strip_all_tags($profileHtml), 24, '');
        }

        if (!$dryRun) {
            $result = wp_insert_post($payload, true);
            if (is_wp_error($result)) {
                $stats['skipped']++;
                $skipped[] = $displayName . ' gagal dibuat: ' . $result->get_error_message();
                continue;
            }

            $postId = (int) $result;
            $post = get_post($postId);
            if ($post instanceof WP_Post) {
                $existingPosts[] = $post;
                $index = build_doctor_index($existingPosts);
            }
        }

        $stats['created']++;
        $created[] = $displayName;
    } else {
        $stats['updated']++;
        $updated[] = $displayName;
        $stats[(string) $match['strategy']]++;

        $profileHtml = profile_html((string) ($row['Profil'] ?? ''));
        if (!$dryRun) {
            $payload = [
                'ID' => $postId,
                'post_title' => $displayName,
            ];

            if ($profileHtml !== '') {
                $payload['post_content'] = $profileHtml;
                $payload['post_excerpt'] = wp_trim_words(wp_strip_all_tags($profileHtml), 24, '');
            }

            wp_update_post($payload);
        }

        if (($match['strategy'] ?? '') === 'fuzzy') {
            $fuzzyMatches[] = sprintf('%s => %s', $displayName, get_the_title($postId));
        }
    }

    if ($dryRun || $postId <= 0) {
        continue;
    }

    sync_doctor_profile_meta($postId, $row, $displayName);

    $isSyncedFromSchedule = (string) get_post_meta($postId, '_rspku_synced_from_schedule', true) === '1';
    if (!$isSyncedFromSchedule) {
        sync_profile_specialization_term($postId, clean_text($row['Poli / Spesialisasi Utama'] ?? ''));
    }
}

echo json_encode([
    'dry_run' => $dryRun,
    'stats' => $stats,
    'created' => $created,
    'fuzzy_matches' => $fuzzyMatches,
    'skipped' => $skipped,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

/**
 * @return array<int,array<string,string>>
 */
function parse_doctor_profile_tsv(string $input): array
{
    $stream = fopen('php://temp', 'r+');
    if ($stream === false) {
        return [];
    }

    fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $input) ?: $input);
    rewind($stream);

    $headers = fgetcsv($stream, 0, "\t", '"', '\\');
    if (!is_array($headers)) {
        return [];
    }

    $headers = array_map(static fn ($header): string => trim((string) $header), $headers);
    $rows = [];

    while (($columns = fgetcsv($stream, 0, "\t", '"', '\\')) !== false) {
        if (!is_array($columns) || array_filter($columns, static fn ($value): bool => trim((string) $value) !== '') === []) {
            continue;
        }

        $row = [];
        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $row[$header] = clean_text((string) ($columns[$index] ?? ''));
        }

        $rows[] = $row;
    }

    fclose($stream);

    return $rows;
}

/**
 * @param array<int,WP_Post> $posts
 * @return array<string,int>
 */
function build_doctor_index(array $posts): array
{
    $index = [];

    foreach ($posts as $post) {
        foreach (doctor_names_for_matching($post) as $name) {
            $exact = normalize_doctor_key($name);
            if ($exact !== '') {
                $index[$exact] = (int) $post->ID;
            }

            $loose = loose_doctor_key($name);
            if ($loose !== '' && !isset($index['loose:' . $loose])) {
                $index['loose:' . $loose] = (int) $post->ID;
            }
        }
    }

    return $index;
}

/**
 * @return array<int,string>
 */
function doctor_names_for_matching(WP_Post $post): array
{
    return array_values(array_filter([
        get_the_title($post),
        (string) get_post_meta($post->ID, '_rspku_doctor_name', true),
        (string) get_post_meta($post->ID, '_rspku_schedule_source_name', true),
        (string) get_post_meta($post->ID, 'nama_dokter', true),
    ]));
}

/**
 * @param array<int,WP_Post> $posts
 * @param array<string,int> $index
 * @return array{post_id:int,strategy:string}
 */
function match_doctor_post(string $name, string $displayName, array $posts, array $index): array
{
    foreach ([$displayName, $name] as $candidate) {
        $key = normalize_doctor_key($candidate);
        if ($key !== '' && isset($index[$key])) {
            return ['post_id' => $index[$key], 'strategy' => 'exact'];
        }
    }

    $loose = loose_doctor_key($name);
    if ($loose !== '' && isset($index['loose:' . $loose])) {
        return ['post_id' => $index['loose:' . $loose], 'strategy' => 'loose'];
    }

    $alias = known_doctor_alias($loose);
    if ($alias !== '' && isset($index['loose:' . $alias])) {
        return ['post_id' => $index['loose:' . $alias], 'strategy' => 'loose'];
    }

    $bestPostId = 0;
    $bestDistance = 99;
    foreach ($posts as $post) {
        foreach (doctor_names_for_matching($post) as $postName) {
            $postLoose = loose_doctor_key($postName);
            if ($postLoose === '' || $loose === '') {
                continue;
            }

            if (str_starts_with($loose, $postLoose . ' ') || str_starts_with($postLoose, $loose . ' ')) {
                return ['post_id' => (int) $post->ID, 'strategy' => 'fuzzy'];
            }

            $distance = levenshtein($loose, $postLoose);
            $limit = max(2, (int) floor(min(strlen($loose), strlen($postLoose)) * 0.22));
            if ($distance <= $limit && $distance < $bestDistance) {
                $bestDistance = $distance;
                $bestPostId = (int) $post->ID;
            }
        }
    }

    if ($bestPostId > 0) {
        return ['post_id' => $bestPostId, 'strategy' => 'fuzzy'];
    }

    return ['post_id' => 0, 'strategy' => 'created'];
}

function sync_doctor_profile_meta(int $postId, array $row, string $displayName): void
{
    $degree = clean_text($row['Gelar'] ?? '');
    $specialization = clean_text($row['Poli / Spesialisasi Utama'] ?? '');
    $subSpecialization = clean_text($row['Subspesialisasi'] ?? '');
    $profileHtml = profile_html((string) ($row['Profil'] ?? ''));
    $education = clean_text($row['Pendidikan'] ?? '');
    $experience = clean_text($row['Pengalaman'] ?? '');
    $training = clean_text($row['Pelatihan'] ?? '');

    update_post_meta($postId, '_rspku_profile_source', 'team-rspku');
    update_post_meta($postId, '_rspku_profile_imported_at', current_time('mysql'));
    update_post_meta($postId, '_rspku_doctor_name', $displayName);
    update_post_meta($postId, '_rspku_degree', $degree);
    update_post_meta($postId, '_rspku_profile_specialization', $specialization);
    update_post_meta($postId, '_rspku_sub_specialization', $subSpecialization);

    if ($profileHtml !== '') {
        update_post_meta($postId, '_rspku_doctor_biography', $profileHtml);
        update_post_meta($postId, 'profil_dokter', $profileHtml);
    }

    update_or_delete_meta($postId, '_rspku_education', $education);
    update_or_delete_meta($postId, 'pendidikan_dokter', $education);
    update_or_delete_meta($postId, '_rspku_experience', $experience);
    update_or_delete_meta($postId, 'pengalaman_dokter', $experience);
    update_or_delete_meta($postId, '_rspku_training', $training);
    update_or_delete_meta($postId, 'pelatihan_dokter', $training);
}

function sync_profile_specialization_term(int $postId, string $specialization): void
{
    if ($specialization === '') {
        return;
    }

    $term = term_exists(sanitize_title($specialization), 'spesialisasi-dokter');
    if (!$term) {
        $term = wp_insert_term($specialization, 'spesialisasi-dokter', ['slug' => sanitize_title($specialization)]);
    }

    if (is_wp_error($term)) {
        return;
    }

    $termId = is_array($term) ? (int) ($term['term_id'] ?? 0) : (int) $term;
    if ($termId > 0) {
        wp_set_object_terms($postId, [$termId], 'spesialisasi-dokter', false);
        update_post_meta($postId, '_rspku_specialization_name', $specialization);
        update_post_meta($postId, '_rspku_specialization_slug', sanitize_title($specialization));
    }
}

function update_or_delete_meta(int $postId, string $key, string $value): void
{
    if ($value === '') {
        delete_post_meta($postId, $key);
        return;
    }

    update_post_meta($postId, $key, $value);
}

function display_name(string $name, string $degree): string
{
    $name = clean_text($name);
    $degree = clean_text($degree);
    if ($degree === '') {
        return $name;
    }

    if (str_contains(normalize_doctor_key($name), normalize_doctor_key($degree))) {
        return $name;
    }

    return rtrim($name, ', ') . ', ' . $degree;
}

function profile_html(string $value): string
{
    $value = clean_text($value);
    if ($value === '') {
        return '';
    }

    if (str_contains($value, '<p>')) {
        return wp_kses_post($value);
    }

    $paragraphs = preg_split('/\R{2,}/u', $value) ?: [];
    $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));

    return implode('', array_map(static fn (string $paragraph): string => '<p>' . esc_html($paragraph) . '</p>', $paragraphs));
}

function clean_text(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace("/[ \t]+\n/u", "\n", $value) ?: $value;
    $value = preg_replace("/\n{3,}/u", "\n\n", $value) ?: $value;

    return trim($value);
}

function normalize_doctor_key(string $value): string
{
    $value = clean_text($value);
    $value = strtolower($value);
    $value = str_replace(['&', '/', '\\', '(', ')', '.', ',', '-', "'", '’', '‘', '`'], ' ', $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

    return trim($value);
}

function loose_doctor_key(string $value): string
{
    $value = normalize_doctor_key($value);
    $value = preg_replace('/\b(prof|drg|dr|h|hj|kh|ir)\b/u', ' ', $value) ?: $value;
    $value = preg_replace('/\b(sp|spb|sppd|subsp|m|kes|msc|mmed|med|klin|mmr|mph|ph|d|finasim|fihfaa|fccp|fisr|aifo|kgh|gh|khom|kri|ri|bd|da|k|kl|onk|kg|kj|pd|jp|rad|pk|pa|og|ot|bs|ba|bm|kfr|tht|bkl|dve|perio|ort|an|u|n|s|p|a|b)\b/u', ' ', $value) ?: $value;
    $value = preg_replace('/\b[a-z]\b/u', ' ', $value) ?: $value;
    $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

    return trim($value);
}

function known_doctor_alias(string $looseKey): string
{
    $aliases = [
        'adi sihono' => 'adi shono',
        'sagiran' => 'sagian',
        'taufiek hikmawan' => 'taufiek hybs',
        'barkah djaka purwanto' => 'barkah djoko purwanto',
        'mardiah suci hardianti' => 'mardiah suci hardiati',
        'petrina theda philotra' => 'petrina theda philothra',
        'anggita putri kantilaras' => 'anggita putri',
    ];

    return $aliases[$looseKey] ?? '';
}
