<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$wpLoad = $root . '/wp-load.php';
if (!file_exists($wpLoad)) {
    fwrite(STDERR, "wp-load.php tidak ditemukan.\n");
    exit(1);
}

require $wpLoad;
require_once $root . '/wp-content/themes/rspku-theme/app/Repositories/DoctorScheduleRepository.php';

$repositoryFile = $root . '/wp-content/themes/rspku-theme/app/Repositories/DoctorScheduleRepository.php';
$repositorySource = file_get_contents($repositoryFile) ?: '';
assert(!str_contains($repositorySource, 'TablePress::load_model'));
assert(!str_contains($repositorySource, 'private const TABLE_ID'));
assert(!str_contains($repositorySource, 'use TablePress'));

$copySource = file_get_contents($root . '/wp-content/themes/rspku-theme/resources/views/pages/page-jadwal-dokter.twig') ?: '';
assert(!str_contains($copySource, 'tabel operasional'));
assert(str_contains($copySource, 'data jadwal resmi internal'));

$repository = new \Rspku\Repositories\DoctorScheduleRepository();
$summary = $repository->summary();
$headers = $repository->dayHeaders();
$records = $repository->records();

assert(($summary['source'] ?? '') === 'native');
assert(count($headers) === 7);
assert(is_array($records));

echo "task-7-native-frontend-ok\n";
