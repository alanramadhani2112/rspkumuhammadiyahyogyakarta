<?php

declare(strict_types=1);

// Applies a plan from plan-doctor-schedule-sync-pendaftaran.php. Dry-run unless --commit.
// Usage: php apply-doctor-schedule-sync-pendaftaran.php --plan=plan.json --snapshot=snapshot.json [--commit]
// Snapshot uses the same format as restore-native-doctor-schedule-snapshot.php (restore with that script).

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
$commit = in_array('--commit', $argv, true);
$planFile = $opt('--plan');
$snapFile = $opt('--snapshot');
if ($planFile === null || !is_readable($planFile) || ($commit && $snapFile === null)) {
    fwrite(STDERR, "Gunakan --plan=plan.json --snapshot=snapshot.json [--commit]\n");
    exit(1);
}

$items = json_decode((string) file_get_contents($planFile), true)['items'] ?? [];
$todo = array_values(array_filter($items, static fn ($i) => ($i['status'] ?? '') === 'will_change'));

$todo = array_values(array_filter($todo, static function ($i): bool {
    $v = RSPKU_CPT_DoctorSchedule::validateRows($i['proposed_schedule']);
    if ($v['errors'] !== []) {
        fwrite(STDERR, "SKIP {$i['wp_name']}: baris tidak valid\n");
        return false;
    }
    foreach ($v['rows'] as $r) {
        if (absint($r['specialization_term_id']) <= 0) {
            fwrite(STDERR, "SKIP {$i['wp_name']}: term kosong\n");
            return false;
        }
    }
    return get_post_type((int) $i['doctor_id']) === 'dokter';
}));

if ($commit) {
    $snapshot = [];
    foreach (get_posts(['post_type' => 'dokter', 'post_status' => ['publish', 'draft', 'pending', 'private'], 'posts_per_page' => -1]) as $p) {
        $id = (int) $p->ID;
        $snapshot[] = [
            'id' => $id,
            'title' => get_the_title($p),
            '_rspku_doctor_schedule' => get_post_meta($id, '_rspku_doctor_schedule', true),
            'jadwal_praktek' => get_post_meta($id, 'jadwal_praktek', true),
            '_rspku_schedule_day' => get_post_meta($id, '_rspku_schedule_day', false),
            '_rspku_schedule_managed_specializations' => get_post_meta($id, '_rspku_schedule_managed_specializations', true),
            'spesialisasi_dokter_terms' => wp_get_post_terms($id, 'spesialisasi-dokter', ['fields' => 'ids']),
        ];
    }
    file_put_contents($snapFile, wp_json_encode(['snapshot' => $snapshot], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // persistSchedule() is private; reuse it so meta, day index, managed terms and caches match the admin editor.
    $persist = new ReflectionMethod(RSPKU_CPT_DoctorScheduleAdmin::class, 'persistSchedule');
    foreach ($todo as $i) {
        $persist->invoke(null, (int) $i['doctor_id'], RSPKU_CPT_DoctorSchedule::validateRows($i['proposed_schedule'])['rows']);
    }
}

echo json_encode(['mode' => $commit ? 'commit' : 'dry-run', 'doctors' => count($todo), 'snapshot' => $commit ? $snapFile : null], JSON_PRETTY_PRINT) . "\n";
