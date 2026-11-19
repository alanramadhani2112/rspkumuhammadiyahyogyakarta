<?php

declare(strict_types=1);

// DRY-RUN ONLY. Never writes to WordPress.
// Usage: php plan-doctor-schedule-sync-pendaftaran.php --source=source.json [--out=plan.json]
// source.json comes from scrape-pendaftaran-schedule.php.

define('DISABLE_WP_CRON', true);
require dirname(__DIR__, 4) . '/wp-load.php';

$opt = static function (string $name) use ($argv): ?string {
    foreach ($argv as $a) {
        if (str_starts_with($a, "$name=")) {
            return substr($a, strlen($name) + 1);
        }
    }
    return null;
};
$sourceFile = $opt('--source');
if ($sourceFile === null || !is_readable($sourceFile)) {
    fwrite(STDERR, "Gunakan --source=source.json\n");
    exit(1);
}
$outFile = $opt('--out') ?? dirname(__DIR__, 4) . '/.sisyphus/evidence/doctor-schedule-sync-plan.json';

// Poli layanan/penunjang di sistem pendaftaran, bukan spesialisasi praktik dokter: jangan masuk jadwal WP.
const SKIP_POLI = ['EKG', 'EEG', 'CEK UP/KIR DOKTER', 'KHEMOTERAPI', 'ASY SYIFA', 'AYNA AESTHETIC CENTER', 'MEDICAL CHECK UP',
    'CODE BLUE', 'HOME CARE', 'POLI COVID', 'POLI PCR', 'POLI RTA', 'POLI SALIVA PCR', 'VACCIN CENTER', 'FISIOTERAPI'];
const MIN_OCCURRENCES = 2; // hari+jam yang muncul <2x di rentang tanggal sumber dianggap ad-hoc
const DAYS = ['senin' => 'monday', 'selasa' => 'tuesday', 'rabu' => 'wednesday', 'kamis' => 'thursday',
    'jumat' => 'friday', "jum'at" => 'friday', 'sabtu' => 'saturday', 'minggu' => 'sunday', 'ahad' => 'sunday'];
const STOP = ['sp', 'spa', 'spog', 'spd', 'spjp', 'spb', 'mkes', 'msc', 'sub', 'subsp', 'med', 'kes', 'sps', 'spm', 'spkj', 'spkk', 'spt',
    'spot', 'spbs', 'spu', 'sppd', 'spp', 'spk', 'spkg', 'sprad', 'pd', 'mmr', 'mars', 'mph', 'phd', 'psi', 'psikolog', 'fiihfaa', 'fics',
    'finasim', 'kmn', 'fipm', 'dve', 'ort', 'perio', 'kga', 'bm', 'ba', 'bd', 'an', 'kfr', 'pa', 'pk', 'thtkl', 'onk', 'hk', 'kgh', 'khom',
    'ilmu', 'dsc', 'mdsc', 'fccp', 'fisr', 'aifo', 'urogin', 'kardio', 'klin', 'dr', 'drg', 'dra', 'apt', 'prof', 'hj', 'rm'];

function nt(string $x): array
{
    $x = strtolower(preg_replace('/[^a-z ]/i', ' ', html_entity_decode($x)));
    return array_values(array_filter(preg_split('/\s+/', trim($x)), static fn ($t) => strlen($t) > 2 && !in_array($t, STOP, true)));
}

function name_tokens(string $x): array
{
    return nt(explode(',', $x)[0]) ?: nt($x);
}

function sim(array $a, array $b): float
{
    $m = 0;
    foreach ($a as $t) {
        foreach ($b as $u) {
            if ($t === $u || (strlen($t) > 4 && strlen($u) > 4 && (levenshtein($t, $u) <= 1 || substr($t, 0, 5) === substr($u, 0, 5)))) {
                $m++;
                break;
            }
        }
    }
    return $m / max(1, min(count($a), count($b)));
}

// --- source -> per doctor: day|start-end => count (hanya poli praktik)
$src = [];
foreach (json_decode((string) file_get_contents($sourceFile), true) as $r) {
    $k = $r['name'];
    $src[$k]['poli'][$r['poli']] = true;
    if (in_array($r['poli'], SKIP_POLI, true)) {
        $src[$k]['skipped'][$r['poli']] = true;
        continue;
    }
    foreach ($r['slots'] as $sl) {
        $day = DAYS[strtolower(trim(explode(',', $sl['date'])[0]))] ?? null;
        if ($day === null || !preg_match('/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})\s*$/', $sl['time'], $m)) {
            continue;
        }
        $src[$k]['slots']["$day|$m[1]-$m[2]"] = ($src[$k]['slots']["$day|$m[1]-$m[2]"] ?? 0) + 1;
    }
}

$doctors = get_posts(['post_type' => 'dokter', 'post_status' => 'any', 'numberposts' => -1]);
$labels = RSPKU_CPT_DoctorSchedule::days();
$plan = [];
$used = [];

foreach ($src as $name => $s) {
    $best = null;
    $bs = 0.0;
    $a = name_tokens($name);
    foreach ($doctors as $d) {
        $sc = sim($a, name_tokens($d->post_title));
        if ($sc > $bs) {
            $bs = $sc;
            $best = $d;
        }
    }
    $item = ['source_name' => $name, 'poli' => array_keys($s['poli']), 'ignored_poli' => array_keys($s['skipped'] ?? [])];
    if ($best === null || $bs < 0.67) {
        $item['status'] = 'no_wp_match';
        $plan[] = $item;
        continue;
    }
    $used[$best->ID] = true;
    $item += ['doctor_id' => $best->ID, 'wp_name' => $best->post_title, 'match_score' => round($bs, 2)];

    $slots = $s['slots'] ?? [];
    $stable = array_keys(array_filter($slots, static fn ($c) => $c >= MIN_OCCURRENCES));
    $adhoc = array_values(array_diff(array_keys($slots), $stable));
    $item['ignored_adhoc'] = $adhoc;
    $existing = get_post_meta($best->ID, RSPKU_CPT_DoctorSchedule::META_KEY, true);
    $existing = is_array($existing) ? $existing : [];
    if ($stable === []) {
        $item['status'] = 'source_empty_keep_wp';
        $plan[] = $item;
        continue;
    }

    // term id: pakai term dari baris WP di hari yang sama, kalau tidak ada pakai term dominan dokter.
    $termByDay = [];
    $termCount = [];
    foreach ($existing as $row) {
        $tid = absint($row['specialization_term_id'] ?? 0);
        if ($tid > 0) {
            $termByDay[$row['day']] ??= $tid;
            $termCount[$tid] = ($termCount[$tid] ?? 0) + 1;
        }
    }
    arsort($termCount);
    $mainTerm = (int) (array_key_first($termCount) ?? 0);
    $oldByKey = [];
    foreach ($existing as $row) {
        $oldByKey[$row['day'] . '|' . $row['start_time'] . '-' . $row['end_time']] = $row;
    }

    $rows = [];
    $blocked = false;
    foreach ($stable as $key) {
        if (isset($oldByKey[$key])) {
            $rows[] = $oldByKey[$key]; // tidak berubah: pertahankan apa adanya (note/room/raw)
            continue;
        }
        [$day, $range] = explode('|', $key);
        [$st, $en] = explode('-', $range);
        $tid = $termByDay[$day] ?? $mainTerm;
        if ($tid <= 0) {
            $blocked = true;
        }
        $rows[] = ['day' => $day, 'day_label' => $labels[$day], 'start_time' => $st, 'end_time' => $en, 'label' => "$st - $en",
            'room' => '', 'consultation_type' => '', 'specialization_term_id' => $tid, 'note' => ''];
    }
    $order = array_flip(array_keys($labels));
    usort($rows, static fn ($x, $y) => [$order[$x['day']], $x['start_time']] <=> [$order[$y['day']], $y['start_time']]);

    $norm = static fn (array $rs) => array_map(static fn ($r) => $r['day'] . '|' . $r['start_time'] . '-' . $r['end_time'], $rs);
    $oldKeys = $norm($existing);
    $newKeys = $norm($rows);
    sort($oldKeys);
    sort($newKeys);
    $item['added'] = array_values(array_diff($newKeys, $oldKeys));
    $item['removed'] = array_values(array_diff($oldKeys, $newKeys));
    $item['proposed_schedule'] = $rows;
    $item['status'] = $blocked ? 'blocked_no_term' : (($item['added'] || $item['removed']) ? 'will_change' : 'unchanged');
    $plan[] = $item;
}

$counts = array_count_values(array_column($plan, 'status'));
$summary = ['generated_at' => gmdate(DATE_ATOM), 'dry_run' => true, 'counts' => $counts,
    'wp_only_doctors' => count($doctors) - count($used)];
file_put_contents($outFile, json_encode(['summary' => $summary, 'items' => $plan], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\nPlan: $outFile\n";
