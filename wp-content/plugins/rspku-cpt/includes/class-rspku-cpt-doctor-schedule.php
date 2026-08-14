<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Canonical structured schedule contract for dokter post meta.
 */
final class RSPKU_CPT_DoctorSchedule
{
    public const META_KEY = '_rspku_doctor_schedule';
    public const LEGACY_META_KEY = 'jadwal_praktek';
    public const MANAGED_TERMS_META_KEY = '_rspku_schedule_managed_specializations';
    public const DAY_INDEX_META_KEY = '_rspku_schedule_day';

    /**
     * @return array<string,string>
     */
    public static function days(): array
    {
        return [
            'monday' => __('Senin', 'rspku-theme'),
            'tuesday' => __('Selasa', 'rspku-theme'),
            'wednesday' => __('Rabu', 'rspku-theme'),
            'thursday' => __('Kamis', 'rspku-theme'),
            'friday' => __('Jumat', 'rspku-theme'),
            'saturday' => __('Sabtu', 'rspku-theme'),
            'sunday' => __('Minggu', 'rspku-theme'),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public static function restSchema(): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'day' => ['type' => 'string'],
                    'day_label' => ['type' => 'string'],
                    'start_time' => ['type' => 'string'],
                    'end_time' => ['type' => 'string'],
                    'label' => ['type' => 'string'],
                    'room' => ['type' => 'string'],
                    'consultation_type' => ['type' => 'string'],
                    'specialization_term_id' => ['type' => 'integer'],
                    'note' => ['type' => 'string'],
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $rows
     * @return array<int,array<string,mixed>>
     */
    public static function sanitizeRows(array $rows): array
    {
        return self::validateRows($rows)['rows'];
    }

    /**
     * @param array<mixed> $rows
     * @return array{rows:array<int,array<string,mixed>>,errors:array<int,array<string,mixed>>}
     */
    public static function validateRows(array $rows): array
    {
        $sanitized = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $errors[] = ['row' => $index, 'field' => 'row', 'message' => 'Row must be an array.'];
                continue;
            }

            $normalized = self::normalizeRow($row, (int) $index, $errors);
            if ($normalized === null) {
                continue;
            }

            $sanitized[] = $normalized;
        }

        return [
            'rows' => array_values($sanitized),
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,array<string,mixed>> $errors
     * @return array<string,mixed>|null
     */
    public static function normalizeRow(array $row, int $index = 0, array &$errors = []): ?array
    {
        $day = self::normalizeDay((string) ($row['day'] ?? $row['hari'] ?? $row['hari_praktek'] ?? ''));
        $start = self::time((string) ($row['start_time'] ?? $row['jam_mulai'] ?? $row['mulai'] ?? ''));
        $end = self::time((string) ($row['end_time'] ?? $row['jam_selesai'] ?? $row['selesai'] ?? ''));
        $label = self::text((string) ($row['label'] ?? ''));
        $room = self::text((string) ($row['room'] ?? $row['ruangan'] ?? ''));
        $consultation = self::text((string) ($row['consultation_type'] ?? $row['jenis_konsultasi'] ?? ''));
        $note = self::text((string) ($row['note'] ?? ''));
        $specializationTermId = absint($row['specialization_term_id'] ?? 0);

        if ($day === '' || !isset(self::days()[$day])) {
            $errors[] = ['row' => $index, 'field' => 'day', 'message' => 'Invalid day.'];
            return null;
        }

        if (($start === '') !== ($end === '')) {
            $errors[] = ['row' => $index, 'field' => 'time', 'message' => 'Start and end time must both use HH:MM.'];
            return null;
        }

        if ($start !== '' && $end !== '' && strcmp($start, $end) >= 0) {
            $errors[] = ['row' => $index, 'field' => 'time', 'message' => 'Start time must be earlier than end time.'];
            return null;
        }

        if ($specializationTermId > 0 && function_exists('get_term')) {
            $term = get_term($specializationTermId, 'spesialisasi-dokter');
            if (!$term instanceof WP_Term) {
                $errors[] = ['row' => $index, 'field' => 'specialization_term_id', 'message' => 'Specialization term does not exist.'];
                return null;
            }
        }

        if ($start === '' && $end === '' && $label === '' && $room === '' && $consultation === '' && $note === '' && $specializationTermId === 0) {
            $errors[] = ['row' => $index, 'field' => 'row', 'message' => 'Schedule row is empty.'];
            return null;
        }

        return [
            'day' => $day,
            'day_label' => self::days()[$day],
            'start_time' => $start,
            'end_time' => $end,
            'label' => $label,
            'room' => $room,
            'consultation_type' => $consultation,
            'specialization_term_id' => $specializationTermId,
            'note' => $note,
        ];
    }

    public static function normalizeDay(string $day): string
    {
        $day = sanitize_key($day);

        return match ($day) {
            'senin' => 'monday',
            'selasa' => 'tuesday',
            'rabu' => 'wednesday',
            'kamis' => 'thursday',
            'jumat', 'jum-at' => 'friday',
            'sabtu' => 'saturday',
            'minggu' => 'sunday',
            default => $day,
        };
    }

    public static function time(string $value): string
    {
        $value = trim(str_replace('.', ':', $value));

        return preg_match('/^\d{2}:\d{2}$/', $value) === 1 ? $value : '';
    }

    private static function text(string $value): string
    {
        return sanitize_text_field($value);
    }
}
