<?php

declare(strict_types=1);

define('ABSPATH', __DIR__);

final class WP_Term
{
    public int $term_id = 1;
    public string $taxonomy = 'spesialisasi-dokter';
}

function __(string $text, string $domain = ''): string { return $text; }
function esc_html__(string $text, string $domain = ''): string { return $text; }
function sanitize_key(string $key): string { return strtolower(preg_replace('/[^a-z0-9_\-]/', '', $key) ?? ''); }
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function absint(mixed $value): int { return max(0, (int) $value); }
function get_term(int $termId, string $taxonomy): WP_Term { $term = new WP_Term(); $term->term_id = $termId; $term->taxonomy = $taxonomy; return $term; }
function wp_parse_id_list(mixed $list): array { return array_values(array_unique(array_map('intval', is_array($list) ? $list : [$list]))); }

$GLOBALS['meta'] = [42 => [
    '_rspku_schedule_managed_specializations' => [10],
]];
$GLOBALS['terms'] = [42 => [10, 20]];
$GLOBALS['deleted'] = [];
$GLOBALS['cleaned'] = [];

function get_post_meta(int $postId, string $key, bool $single = false): mixed { return $GLOBALS['meta'][$postId][$key] ?? []; }
function update_post_meta(int $postId, string $key, mixed $value): void { $GLOBALS['meta'][$postId][$key] = $value; }
function delete_post_meta(int $postId, string $key): void { unset($GLOBALS['meta'][$postId][$key]); $GLOBALS['deleted'][] = [$postId, $key]; }
function add_post_meta(int $postId, string $key, mixed $value, bool $unique = false): void { $GLOBALS['meta'][$postId][$key][] = $value; }
function wp_get_post_terms(int $postId, string $taxonomy, array $args = []): array { return $GLOBALS['terms'][$postId] ?? []; }
function wp_set_object_terms(int $postId, array $terms, string $taxonomy, bool $append = false): void { $GLOBALS['terms'][$postId] = array_values($terms); }
function clean_post_cache(int $postId): void { $GLOBALS['cleaned'][] = $postId; }

require __DIR__ . '/../../wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule.php';
require __DIR__ . '/../../wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php';

$persist = new ReflectionMethod('RSPKU_CPT_DoctorScheduleAdmin', 'persistSchedule');
$persist->setAccessible(true);

$rows = [[
    'day' => 'monday',
    'day_label' => 'Senin',
    'start_time' => '08:00',
    'end_time' => '10:00',
    'label' => '08:00 - 10:00',
    'room' => '',
    'consultation_type' => '',
    'specialization_term_id' => 30,
    'note' => '',
]];

$persist->invoke(null, 42, $rows);
assert($GLOBALS['meta'][42]['_rspku_doctor_schedule'] === $rows);
assert($GLOBALS['meta'][42]['jadwal_praktek'] === $rows);
assert($GLOBALS['meta'][42]['_rspku_schedule_day'] === ['monday']);
assert($GLOBALS['meta'][42]['_rspku_schedule_managed_specializations'] === [30]);
assert($GLOBALS['terms'][42] === [20, 30]);
assert($GLOBALS['cleaned'] === [42]);

$persist->invoke(null, 42, []);
assert(!isset($GLOBALS['meta'][42]['_rspku_doctor_schedule']));
assert(!isset($GLOBALS['meta'][42]['jadwal_praktek']));
assert(!isset($GLOBALS['meta'][42]['_rspku_schedule_day']));
assert(!isset($GLOBALS['meta'][42]['_rspku_schedule_managed_specializations']));
assert($GLOBALS['terms'][42] === [20]);

echo "task-4-ok\n";
