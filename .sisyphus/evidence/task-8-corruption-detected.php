<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$wpLoad = $root . '/wp-load.php';
$check = $root . '/wp-content/themes/rspku-theme/scripts/check-native-doctor-schedule.php';

require $wpLoad;

$doctorId = task8_first_scheduled_doctor();
if ($doctorId <= 0) {
    fwrite(STDERR, "No scheduled doctor fixture found.\n");
    exit(1);
}

$original = get_post_meta($doctorId, '_rspku_doctor_schedule', true);
if (!is_array($original) || $original === []) {
    fwrite(STDERR, "Fixture schedule missing.\n");
    exit(1);
}

$corrupt = $original;
$corrupt[0]['specialization_term_id'] = 999999999;

try {
    update_post_meta($doctorId, '_rspku_doctor_schedule', $corrupt);
    $output = [];
    $exitCode = 0;
    exec(PHP_BINARY . ' ' . escapeshellarg($check) . ' 2>&1', $output, $exitCode);
    $text = implode("\n", $output);

    if ($exitCode === 0 || !str_contains($text, 'Specialization term does not exist.')) {
        fwrite(STDERR, $text . "\n");
        exit(1);
    }
} finally {
    update_post_meta($doctorId, '_rspku_doctor_schedule', $original);
    clean_post_cache($doctorId);
}

echo "task-8-corruption-detected-ok\n";

function task8_first_scheduled_doctor(): int
{
    $posts = get_posts([
        'post_type' => 'dokter',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'no_found_rows' => true,
    ]);

    foreach ($posts as $post) {
        $rows = get_post_meta((int) $post->ID, '_rspku_doctor_schedule', true);
        if (is_array($rows) && $rows !== []) {
            return (int) $post->ID;
        }
    }

    return 0;
}
