<?php

declare(strict_types=1);

$wpLoad = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wpLoad)) {
    fwrite(STDERR, "wp-load.php tidak ditemukan.\n");
    exit(1);
}

require $wpLoad;

$tableId = audit_arg('--table-id') ?: '1';
$snapshotPath = audit_arg('--snapshot');
$before = audit_state_checksum();
$snapshot = audit_snapshot_doctors();
$table = audit_load_table($tableId);
$rows = audit_table_rows($table);
$headers = audit_day_headers($rows[0] ?? []);
$doctorIndex = audit_doctor_index();
$termIndex = audit_term_index();
$sourceNames = [];
$items = [];

foreach (array_slice($rows, 1) as $offset => $row) {
    $rowIndex = $offset + 2;
    $name = audit_clean((string) ($row[0] ?? ''));
    $specialization = audit_clean((string) ($row[1] ?? ''));
    $reasons = [];

    if ($name === '' || $specialization === '' || str_contains($name, 'Dokter') || str_contains($specialization, 'Spesialisasi')) {
        continue;
    }

    $nameKey = audit_name_key($name);
    if ($nameKey === '') {
        $reasons[] = 'empty_name_key';
    } elseif (isset($sourceNames[$nameKey])) {
        $reasons[] = 'duplicate_source_name';
    }
    $sourceNames[$nameKey] = true;

    $matches = audit_match_doctors($name, $doctorIndex);
    if ($matches === []) {
        $reasons[] = 'unmatched_doctor';
    } elseif (count($matches) > 1) {
        $reasons[] = 'ambiguous_doctor_match';
    }

    $termId = $termIndex[audit_term_key($specialization)] ?? 0;
    if ($termId <= 0) {
        $reasons[] = 'unknown_specialization';
    }

    $slots = [];
    foreach ($headers as $column => $day) {
        $cell = audit_clean((string) ($row[$column] ?? ''));
        if ($cell === '') {
            continue;
        }

        foreach (audit_parse_time_ranges($cell) as $slot) {
            if ($slot === null) {
                $reasons[] = 'malformed_time:' . $day;
                continue;
            }

            $slots[] = [
                'day' => $day,
                'day_label' => audit_day_label($day),
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'label' => $slot['label'],
                'room' => '',
                'consultation_type' => '',
                'specialization_term_id' => $termId,
                'note' => '',
            ];
        }
    }

    if ($slots === []) {
        $reasons[] = 'empty_schedule';
    }

    $canCreateDoctor = $matches === [] && $nameKey !== '' && !isset($sourceNames[$nameKey . ':duplicate']) && $termId > 0 && $slots !== [];
    $status = $reasons === [] || $reasons === ['unmatched_doctor'] && $canCreateDoctor ? 'importable' : 'skipped';

    $items[] = [
        'row' => $rowIndex,
        'doctor_name' => $name,
        'specialization' => $specialization,
        'doctor_id' => count($matches) === 1 ? $matches[0]['id'] : 0,
        'create_doctor' => $canCreateDoctor,
        'matched_doctors' => $matches,
        'specialization_term_id' => $termId,
        'slots' => $slots,
        'status' => $status,
        'reasons' => array_values(array_unique($reasons)),
    ];
}

$after = audit_state_checksum();
$result = [
    'mode' => 'dry-run',
    'table_id' => $tableId,
    'generated_at' => gmdate(DATE_ATOM),
    'zero_write' => $before === $after,
    'checksum_before' => $before,
    'checksum_after' => $after,
    'counts' => [
        'rows' => count($items),
        'importable' => count(array_filter($items, static fn (array $item): bool => $item['status'] === 'importable')),
        'skipped' => count(array_filter($items, static fn (array $item): bool => $item['status'] === 'skipped')),
        'headers' => count($headers),
        'doctors_snapshot' => count($snapshot),
    ],
    'headers' => $headers,
    'items' => $items,
    'snapshot' => $snapshot,
];

if ($snapshotPath !== null && $snapshotPath !== '') {
    file_put_contents($snapshotPath, wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function audit_arg(string $name): ?string
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

function audit_load_table(string $tableId): array
{
    if (!class_exists('TablePress')) {
        return [];
    }

    $model = TablePress::load_model('table');
    $table = $model->load($tableId, true, true);

    return is_array($table) ? $table : [];
}

function audit_table_rows(array $table): array
{
    $data = $table['data'] ?? [];
    return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
}

function audit_day_headers(array $row): array
{
    $headers = [];
    foreach ($row as $column => $label) {
        if ((int) $column < 2) {
            continue;
        }
        $day = audit_day_key((string) $label);
        if ($day !== '') {
            $headers[(int) $column] = $day;
        }
    }
    return $headers;
}

function audit_snapshot_doctors(): array
{
    $snapshot = [];
    $posts = get_posts(['post_type' => 'dokter', 'post_status' => ['publish', 'draft', 'pending', 'private'], 'posts_per_page' => -1]);
    foreach ($posts as $post) {
        $postId = (int) $post->ID;
        $snapshot[] = [
            'id' => $postId,
            'title' => get_the_title($post),
            '_rspku_doctor_schedule' => get_post_meta($postId, '_rspku_doctor_schedule', true),
            'jadwal_praktek' => get_post_meta($postId, 'jadwal_praktek', true),
            '_rspku_schedule_day' => get_post_meta($postId, '_rspku_schedule_day', false),
            '_rspku_schedule_managed_specializations' => get_post_meta($postId, '_rspku_schedule_managed_specializations', true),
            'spesialisasi_dokter_terms' => wp_get_post_terms($postId, 'spesialisasi-dokter', ['fields' => 'ids']),
        ];
    }
    return $snapshot;
}

function audit_state_checksum(): string
{
    return md5(wp_json_encode(audit_snapshot_doctors(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
}

function audit_doctor_index(): array
{
    $index = [];
    $posts = get_posts(['post_type' => 'dokter', 'post_status' => ['publish', 'draft', 'pending', 'private'], 'posts_per_page' => -1]);
    foreach ($posts as $post) {
        $names = [get_the_title($post), (string) get_post_meta((int) $post->ID, 'nama_dokter', true), (string) get_post_meta((int) $post->ID, '_rspku_schedule_source_name', true)];
        foreach ($names as $name) {
            $key = audit_name_key($name);
            if ($key !== '') {
                $index[$key][] = ['id' => (int) $post->ID, 'title' => get_the_title($post)];
            }
        }
    }
    return $index;
}

function audit_match_doctors(string $name, array $index): array
{
    $exact = $index[audit_name_key($name)] ?? [];
    if ($exact !== []) {
        return audit_unique_matches($exact);
    }
    return audit_unique_matches($index[audit_loose_key($name)] ?? []);
}

function audit_unique_matches(array $matches): array
{
    $unique = [];
    foreach ($matches as $match) {
        $unique[(int) $match['id']] = $match;
    }
    return array_values($unique);
}

function audit_term_index(): array
{
    $index = [];
    $terms = get_terms(['taxonomy' => 'spesialisasi-dokter', 'hide_empty' => false]);
    if (!is_array($terms)) {
        return $index;
    }
    foreach ($terms as $term) {
        $index[audit_term_key((string) $term->name)] = (int) $term->term_id;
        $index[audit_term_key((string) $term->slug)] = (int) $term->term_id;
    }
    return $index;
}

function audit_parse_time_ranges(string $value): array
{
    $value = preg_replace('/\s+/u', ' ', str_replace(["\xc2\xa0", '–', '—', 's/d', 'sd'], [' ', '-', '-', '-', '-'], strtolower($value)));
    $parts = preg_split('/[,;\n]+/', is_string($value) ? $value : '') ?: [];
    $slots = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '' || str_contains($part, 'libur')) {
            continue;
        }
        $matches = [];
        preg_match_all('/(\d{1,2})[.:](\d{2})\s*-\s*(\d{1,2})[.:](\d{2})/u', $part, $matches, PREG_SET_ORDER);
        if ($matches === []) {
            $slots[] = null;
            continue;
        }

        foreach ($matches as $match) {
            $start = sprintf('%02d:%02d', (int) $match[1], (int) $match[2]);
            $end = sprintf('%02d:%02d', (int) $match[3], (int) $match[4]);
            $slots[] = $start < $end ? ['start_time' => $start, 'end_time' => $end, 'label' => $start . ' - ' . $end] : null;
        }
    }
    return $slots;
}

function audit_day_key(string $value): string
{
    $key = audit_term_key($value);
    return [
        'senin' => 'monday', 'monday' => 'monday',
        'selasa' => 'tuesday', 'tuesday' => 'tuesday',
        'rabu' => 'wednesday', 'wednesday' => 'wednesday',
        'kamis' => 'thursday', 'thursday' => 'thursday',
        'jumat' => 'friday', 'friday' => 'friday',
        'sabtu' => 'saturday', 'saturday' => 'saturday',
        'minggu' => 'sunday', 'ahad' => 'sunday', 'sunday' => 'sunday',
    ][$key] ?? '';
}

function audit_day_label(string $day): string
{
    return ['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'][$day] ?? $day;
}

function audit_clean(string $value): string
{
    return trim(wp_strip_all_tags(html_entity_decode($value, ENT_QUOTES, get_bloginfo('charset'))));
}

function audit_name_key(string $value): string
{
    $value = audit_term_key(preg_replace('/[,].*/u', '', $value) ?: $value);
    return trim(preg_replace('/\b(prof|drg|dr|h|hj)\b/u', '', $value) ?: $value);
}

function audit_loose_key(string $value): string
{
    return preg_replace('/\s+/u', '', audit_name_key($value)) ?: '';
}

function audit_term_key(string $value): string
{
    $value = strtolower(html_entity_decode(trim($value), ENT_QUOTES, 'UTF-8'));
    $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?: $value;
    return trim(preg_replace('/\s+/u', ' ', $value) ?: $value);
}
