<?php

declare(strict_types=1);

define('ABSPATH', __DIR__);

function __(string $text, string $domain = ''): string { return $text; }
function sanitize_key(string $key): string { return strtolower((string) preg_replace('/[^a-zA-Z0-9_\-]/', '', $key)); }
function sanitize_text_field(string $text): string { return trim(strip_tags($text)); }
function absint(mixed $value): int { return max(0, (int) $value); }
function get_term(int $termId, string $taxonomy): object { return new WP_Term(); }

class WP_Term {}

require __DIR__ . '/../../wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule.php';

$rows = RSPKU_CPT_DoctorSchedule::sanitizeRows([
    ['day' => 'senin', 'start_time' => '08.00', 'end_time' => '12:00', 'specialization_term_id' => '17', 'note' => '<b>Poli pagi</b>'],
    ['day' => 'monday', 'start_time' => '12:00', 'end_time' => '08:00'],
    ['day' => 'minggu', 'start_time' => '09:00'],
    ['day' => 'bogus', 'start_time' => '08:00', 'end_time' => '09:00'],
]);

assert(count($rows) === 1);
assert($rows[0]['day'] === 'monday');
assert($rows[0]['day_label'] === 'Senin');
assert($rows[0]['start_time'] === '08:00');
assert($rows[0]['end_time'] === '12:00');
assert($rows[0]['specialization_term_id'] === 17);
assert($rows[0]['note'] === 'Poli pagi');

$result = RSPKU_CPT_DoctorSchedule::validateRows([
    ['day' => 'holiday', 'start_time' => '08:00', 'end_time' => '09:00'],
    ['day' => 'monday', 'start_time' => '12:00', 'end_time' => '09:00'],
]);

assert($result['rows'] === []);
assert(array_column($result['errors'], 'field') === ['day', 'time']);

echo "task-1-ok\n";
