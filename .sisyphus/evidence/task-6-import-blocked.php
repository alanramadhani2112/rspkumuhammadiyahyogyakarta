<?php

declare(strict_types=1);

$audit = [
    'table_id' => '1',
    'items' => [
        ['status' => 'importable', 'doctor_id' => 101, 'slots' => [['day' => 'monday', 'specialization_term_id' => 7]]],
        ['status' => 'skipped', 'reasons' => ['malformed_time:monday']],
    ],
];

$auditFile = sys_get_temp_dir() . '/rspku-import-blocked-audit.json';
file_put_contents($auditFile, json_encode($audit, JSON_THROW_ON_ERROR));

$script = __DIR__ . '/../../wp-content/themes/rspku-theme/scripts/import-native-doctor-schedule.php';
$command = 'php ' . escapeshellarg($script) . ' --audit ' . escapeshellarg($auditFile) . ' --commit 2>&1';
exec($command, $output, $exitCode);

assert($exitCode === 2);
assert(str_contains(implode("\n", $output), 'Import dibatalkan'));

@unlink($auditFile);
echo "task-6-import-blocked-ok\n";