<?php

declare(strict_types=1);

namespace Rspku\Repositories;

use TablePress;
use WP_Post;

final class DoctorScheduleRepository
{
    private const TABLE_ID = '1';

    /**
     * @var array<string,mixed>|null
     */
    private static ?array $tableCache = null;

    /**
     * @var array<int,array<string,mixed>>|null
     */
    private static ?array $recordsCache = null;

    /**
     * @return array<string,mixed>
     */
    public function summary(): array
    {
        $records = $this->records();
        $specializations = [];
        $categories = [];

        foreach ($records as $record) {
            $specializations[(string) ($record['specialization'] ?? '')] = true;
            $categories[(string) ($record['specialization_category'] ?? 'Lainnya')] = true;
        }

        return [
            'table_id' => self::TABLE_ID,
            'table_name' => (string) ($this->table()['name'] ?? 'Jadwal Dokter'),
            'last_modified' => (string) ($this->table()['last_modified'] ?? ''),
            'doctor_count' => count($records),
            'specialization_count' => count(array_filter(array_keys($specializations))),
            'category_count' => count(array_filter(array_keys($categories))),
        ];
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function dayHeaders(): array
    {
        $headers = [];
        $table = $this->table();
        $rows = is_array($table['data'] ?? null) ? $table['data'] : [];

        foreach ($this->headers($rows[0] ?? []) as $dayKey) {
            $headers[] = [
                'key' => $dayKey,
                'label' => $this->dayLabel($dayKey),
            ];
        }

        return $headers;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function records(): array
    {
        if (self::$recordsCache !== null) {
            return self::$recordsCache;
        }

        $table = $this->table();
        $data = $table['data'] ?? [];
        $records = [];

        if (!is_array($data) || $data === []) {
            return self::$recordsCache = [];
        }

        $headers = $this->headers($data[0] ?? []);

        for ($index = 1, $limit = count($data); $index < $limit; $index++) {
            $row = is_array($data[$index] ?? null) ? $data[$index] : [];
            $record = $this->parseRow($row, $headers, $index);
            if ($record !== null) {
                $records[] = $record;
            }
        }

        return self::$recordsCache = $records;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function specializations(): array
    {
        $items = [];

        foreach ($this->records() as $record) {
            $spec = (string) ($record['specialization'] ?? '');
            if ($spec === '') {
                continue;
            }

            $items[$spec] = [
                'name' => $spec,
                'slug' => sanitize_title($spec),
                'category' => (string) ($record['specialization_category'] ?? 'Lainnya'),
            ];
        }

        ksort($items);

        return array_values($items);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function specializationGroups(): array
    {
        $groups = [];

        foreach ($this->specializations() as $item) {
            $category = (string) ($item['category'] ?? 'Lainnya');
            if (!isset($groups[$category])) {
                $groups[$category] = [
                    'title' => $category,
                    'items' => [],
                    'order' => $this->categoryOrder($category),
                ];
            }

            $groups[$category]['items'][] = $item;
        }

        uasort(
            $groups,
            static fn (array $left, array $right): int => ($left['order'] <=> $right['order']) ?: strcmp((string) $left['title'], (string) $right['title'])
        );

        return array_map(
            static function (array $group): array {
                unset($group['order']);
                return $group;
            },
            array_values($groups)
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByName(string $name): ?array
    {
        $normalized = $this->normalizeName($name);
        foreach ($this->records() as $record) {
            if ($this->normalizeName((string) $record['name']) === $normalized) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findBySpecialization(string $specialization): ?array
    {
        $normalized = $this->normalizeText($specialization);
        foreach ($this->records() as $record) {
            if ($this->normalizeText((string) $record['specialization']) === $normalized) {
                return $record;
            }
        }

        return null;
    }

    public function sourceHash(): string
    {
        return md5((string) wp_json_encode($this->records(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string,mixed>
     */
    public function table(): array
    {
        if (self::$tableCache !== null) {
            return self::$tableCache;
        }

        if (!class_exists(TablePress::class)) {
            return self::$tableCache = [];
        }

        $model = TablePress::load_model('table');
        $table = $model->load(self::TABLE_ID, true, true);

        if (is_wp_error($table)) {
            return self::$tableCache = [];
        }

        return self::$tableCache = $table;
    }

    /**
     * @param array<int,mixed> $headerRow
     * @return array<int,string>
     */
    private function headers(array $headerRow): array
    {
        $headers = [];
        foreach ($headerRow as $index => $value) {
            if ($index < 2) {
                continue;
            }

            $label = $this->clean((string) $value);
            if ($label === '') {
                continue;
            }

            $key = $this->normalizeDayKey($label);
            if ($key === '') {
                continue;
            }

            $headers[$index] = $key;
        }

        return $headers;
    }

    /**
     * @param array<int,mixed> $row
     * @param array<int,string> $headers
     * @return array<string,mixed>|null
     */
    private function parseRow(array $row, array $headers, int $rowIndex): ?array
    {
        $name = $this->clean((string) ($row[0] ?? ''));
        $specialization = $this->clean((string) ($row[1] ?? ''));

        if ($name === '' || $specialization === '' || str_contains($name, 'Dokter') || str_contains($specialization, 'Spesialisasi')) {
            return null;
        }

        if ($this->isGenericDirectoryLabel($name)) {
            return null;
        }

        $days = [];
        $slots = [];

        foreach ($headers as $columnIndex => $dayKey) {
            $dayLabel = $this->dayLabel($dayKey);
            $cell = $this->clean((string) ($row[$columnIndex] ?? ''));
            $parsedSlots = $this->parseTimeRanges($cell);

            $days[$dayKey] = [
                'key' => $dayKey,
                'label' => $dayLabel,
                'raw' => $cell,
                'slots' => $parsedSlots,
            ];

            foreach ($parsedSlots as $slot) {
                $slots[] = [
                    'day' => $dayKey,
                    'day_label' => $dayLabel,
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'label' => $slot['label'],
                    'raw' => $cell,
                ];
            }
        }

        $category = $this->specializationCategory($specialization);
        $relatedService = $this->relatedUnit($specialization);
        $specializationCopy = $this->specializationCopy($name, $specialization, $category);

        return [
            'row_index' => $rowIndex,
            'name' => $name,
            'slug' => sanitize_title($name),
            'specialization' => $specialization,
            'specialization_slug' => sanitize_title($specialization),
            'specialization_category' => $category,
            'specialization_category_slug' => sanitize_title($category),
            'days' => $days,
            'schedule' => $slots,
            'schedule_summary' => $this->scheduleSummary($slots),
            'summary' => $specializationCopy['summary'],
            'profile' => $specializationCopy['profile'],
            'education' => $specializationCopy['education'],
            'experience' => $specializationCopy['experience'],
            'consultation_type' => $specializationCopy['consultation_type'],
            'related_services' => $relatedService['services'],
            'related_polyclinics' => $relatedService['polyclinics'],
            'raw' => $row,
        ];
    }

    /**
     * @return array<int,array{start_time:string,end_time:string,label:string}>
     */
    private function parseTimeRanges(string $cell): array
    {
        if ($cell === '' || $cell === '-') {
            return [];
        }

        $normalized = preg_replace('/\s+/u', ' ', str_replace(["\xc2\xa0", '–', '—'], [' ', '-', '-'], $cell));
        $normalized = is_string($normalized) ? trim($normalized) : trim($cell);

        $matches = [];
        preg_match_all('/(\d{1,2}[.:]\d{2})\s*-\s*(\d{1,2}[.:]\d{2})/u', $normalized, $matches, PREG_SET_ORDER);

        $slots = [];
        foreach ($matches as $match) {
            $start = $this->normalizeTime((string) ($match[1] ?? ''));
            $end = $this->normalizeTime((string) ($match[2] ?? ''));
            if ($start === '' || $end === '') {
                continue;
            }

            $slots[] = [
                'start_time' => $start,
                'end_time' => $end,
                'label' => $start . ' - ' . $end,
            ];
        }

        if ($slots !== []) {
            return $slots;
        }

        return [
            [
                'start_time' => '',
                'end_time' => '',
                'label' => $normalized,
            ],
        ];
    }

    private function normalizeTime(string $value): string
    {
        $value = trim(str_replace('.', ':', $value));

        if (!preg_match('/^\d{1,2}:\d{2}$/', $value)) {
            return '';
        }

        [$hour, $minute] = array_map('intval', explode(':', $value));

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function scheduleSummary(array $slots): string
    {
        $summary = [];
        foreach ($slots as $slot) {
            $day = (string) ($slot['day_label'] ?? '');
            $label = (string) ($slot['label'] ?? '');
            if ($day === '' || $label === '') {
                continue;
            }

            $summary[] = trim($day . ' ' . $label);
        }

        return implode(', ', $summary);
    }

    private function specializationCategory(string $specialization): string
    {
        $spec = $this->normalizeText($specialization);

        return match (true) {
            str_contains($spec, 'anak') || str_contains($spec, 'tumbuh kembang') => 'Anak',
            str_contains($spec, 'bedah') || str_contains($spec, 'urologi') => 'Bedah',
            str_contains($spec, 'gigi') => 'Gigi',
            str_contains($spec, 'jantung') => 'Jantung',
            str_contains($spec, 'kandungan') || str_contains($spec, 'ginekologi') => 'Kandungan',
            str_contains($spec, 'kulit') => 'Kulit & Kelamin',
            str_contains($spec, 'mata') => 'Mata',
            str_contains($spec, 'paru') => 'Paru',
            str_contains($spec, 'saraf') => 'Saraf',
            str_contains($spec, 'tht') => 'THT',
            str_contains($spec, 'dalam') || str_contains($spec, 'ginjal') || str_contains($spec, 'hemat') => 'Penyakit Dalam',
            str_contains($spec, 'jiwa') || str_contains($spec, 'psikolog') => 'Jiwa',
            str_contains($spec, 'radiologi') || str_contains($spec, 'patologi') || str_contains($spec, 'fisioterapi') || str_contains($spec, 'rehabilitasi') || str_contains($spec, 'okupasi') || str_contains($spec, 'terapi wicara') => 'Penunjang & Terapi',
            str_contains($spec, 'obsgyn') => 'Kandungan',
            default => 'Lainnya',
        };
    }

    /**
     * @return array{services: array<int,array<string,mixed>>, polyclinics: array<int,array<string,mixed>>}
     */
    private function relatedUnit(string $specialization): array
    {
        $map = $this->specializationUnitMap();
        $key = $this->normalizeText($specialization);
        $matched = null;

        foreach ($map as $needle => $item) {
            if (str_contains($key, $needle)) {
                $matched = $item;
                break;
            }
        }

        if ($matched === null) {
            return ['services' => [], 'polyclinics' => []];
        }

        $services = [];
        $polyclinics = [];

        foreach ($matched['services'] as $serviceId) {
            $post = get_post($serviceId);
            if ($post instanceof WP_Post) {
                $services[] = [
                    'id' => (int) $post->ID,
                    'title' => get_the_title($post),
                    'url' => get_permalink($post),
                    'type' => $post->post_type,
                ];
            }
        }

        foreach ($matched['polyclinics'] as $postId) {
            $post = get_post($postId);
            if ($post instanceof WP_Post) {
                $polyclinics[] = [
                    'id' => (int) $post->ID,
                    'title' => get_the_title($post),
                    'url' => get_permalink($post),
                    'type' => $post->post_type,
                ];
            }
        }

        return [
            'services' => $services,
            'polyclinics' => $polyclinics,
        ];
    }

    /**
     * @return array<string,array{services: array<int,int>, polyclinics: array<int,int>}>
     */
    private function specializationUnitMap(): array
    {
        return [
            'saraf' => [
                'services' => [],
                'polyclinics' => [16990],
            ],
            'tht' => [
                'services' => [],
                'polyclinics' => [16993],
            ],
            'mata' => [
                'services' => [],
                'polyclinics' => [16982],
            ],
            'kulit' => [
                'services' => [],
                'polyclinics' => [16978],
            ],
            'jantung dan pembuluh darah' => [
                'services' => [],
                'polyclinics' => [16970],
            ],
            'jantung anak' => [
                'services' => [],
                'polyclinics' => [16971],
            ],
            'anak imunisasi' => [
                'services' => [],
                'polyclinics' => [15638],
            ],
            'tumbuh kembang anak' => [
                'services' => [],
                'polyclinics' => [17001],
            ],
            'bedah anak' => [
                'services' => [],
                'polyclinics' => [16936],
            ],
            'bedah digestif' => [
                'services' => [],
                'polyclinics' => [16937],
            ],
            'bedah mulut' => [
                'services' => [],
                'polyclinics' => [16940],
            ],
            'bedah onkologi' => [
                'services' => [],
                'polyclinics' => [16941],
            ],
            'orthopedi' => [
                'services' => [],
                'polyclinics' => [16944],
            ],
            'bedah orthopedi' => [
                'services' => [],
                'polyclinics' => [16944],
            ],
            'bedah saraf' => [
                'services' => [],
                'polyclinics' => [16946],
            ],
            'bedah syaraf' => [
                'services' => [],
                'polyclinics' => [16946],
            ],
            'bedah umum' => [
                'services' => [],
                'polyclinics' => [16948],
            ],
            'bedah urologi' => [
                'services' => [],
                'polyclinics' => [16949],
            ],
            'gigi endodonsi' => [
                'services' => [],
                'polyclinics' => [16950],
            ],
            'gigi ortodonti' => [
                'services' => [],
                'polyclinics' => [16951],
            ],
            'gigi pedodonti' => [
                'services' => [],
                'polyclinics' => [16952],
            ],
            'gigi periodonti' => [
                'services' => [],
                'polyclinics' => [16958],
            ],
            'gigi umum' => [
                'services' => [],
                'polyclinics' => [16960],
            ],
            'ginekologi' => [
                'services' => [],
                'polyclinics' => [16959, 16972],
            ],
            'ginjal' => [
                'services' => [],
                'polyclinics' => [16961],
            ],
            'hemato' => [
                'services' => [],
                'polyclinics' => [16962],
            ],
            'home care' => [
                'services' => [],
                'polyclinics' => [16967],
            ],
            'jiwa' => [
                'services' => [],
                'polyclinics' => [16969],
            ],
            'obsgyn' => [
                'services' => [],
                'polyclinics' => [16972],
            ],
            'paru' => [
                'services' => [],
                'polyclinics' => [16983],
            ],
            'patologi' => [
                'services' => [],
                'polyclinics' => [16980, 16981],
            ],
            'penyakit dalam' => [
                'services' => [],
                'polyclinics' => [16989],
            ],
            'psikolog' => [
                'services' => [],
                'polyclinics' => [16991],
            ],
            'radiologi' => [
                'services' => [],
                'polyclinics' => [16992],
            ],
            'rehabilitasi' => [
                'services' => [],
                'polyclinics' => [16998],
            ],
            'terapi tumbuh kembang' => [
                'services' => [],
                'polyclinics' => [17001],
            ],
            'terapi wicara' => [
                'services' => [],
                'polyclinics' => [17000],
            ],
            'anestesi' => [
                'services' => [],
                'polyclinics' => [19301],
            ],
            'umum / instalasi gawat darurat' => [
                'services' => [],
                'polyclinics' => [16630],
            ],
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function specializationCopy(string $name, string $specialization, string $category): array
    {
        $spec = $this->clean($specialization);
        $categoryLabel = $category !== 'Lainnya' ? $category : 'pelayanan medis';
        $role = $this->profileRole($name, $spec);
        $focus = match (true) {
            str_contains($this->normalizeText($spec), 'anak') || str_contains($this->normalizeText($spec), 'tumbuh kembang') => 'mendampingi tumbuh kembang dan kesehatan anak secara menyeluruh',
            str_contains($this->normalizeText($spec), 'bedah') || str_contains($this->normalizeText($spec), 'urologi') => 'memberikan evaluasi dan tindakan bedah yang terukur dan aman',
            str_contains($this->normalizeText($spec), 'gigi') => 'menangani kebutuhan kesehatan gigi dan mulut dengan pendekatan yang teliti',
            str_contains($this->normalizeText($spec), 'jantung') => 'memusatkan layanan pada pemeriksaan dan penanganan kesehatan jantung',
            str_contains($this->normalizeText($spec), 'saraf') => 'menangani keluhan saraf, nyeri kepala, kesemutan, dan gangguan neurologis lain',
            str_contains($this->normalizeText($spec), 'tht') => 'fokus pada telinga, hidung, dan tenggorokan dengan evaluasi klinis yang rapi',
            str_contains($this->normalizeText($spec), 'kulit') => 'berfokus pada kesehatan kulit dan kelamin dengan pelayanan yang nyaman',
            str_contains($this->normalizeText($spec), 'paru') => 'membantu evaluasi gangguan pernapasan dan kesehatan paru',
            str_contains($this->normalizeText($spec), 'dalam') || str_contains($this->normalizeText($spec), 'ginjal') || str_contains($this->normalizeText($spec), 'hemat') => 'menangani keluhan penyakit dalam secara komprehensif',
            str_contains($this->normalizeText($spec), 'jiwa') || str_contains($this->normalizeText($spec), 'psikolog') => 'memberikan ruang konsultasi yang lebih tenang dan suportif',
            str_contains($this->normalizeText($spec), 'radiologi') || str_contains($this->normalizeText($spec), 'patologi') => 'mendukung diagnosis dengan layanan penunjang yang akurat',
            str_contains($this->normalizeText($spec), 'rehabilitasi') || str_contains($this->normalizeText($spec), 'terapi') || str_contains($this->normalizeText($spec), 'okupasi') => 'membantu proses pemulihan fungsi dan kualitas hidup pasien',
            default => 'memberikan layanan yang berfokus pada keselamatan, kejelasan informasi, dan pengalaman pasien yang lebih baik',
        };

        $profile = sprintf(
            '<p>%s merupakan %s di RS PKU Muhammadiyah Yogyakarta.</p><p>Fokus pelayanannya %s. Profil ini diturunkan langsung dari jadwal dokter resmi sehingga akan mengikuti perubahan jadwal praktik saat tabel sumber diperbarui.</p><p>Pasien dapat memeriksa jadwal praktik terbaru, layanan terkait, dan informasi konsultasi melalui halaman profil dokter ini.</p>',
            esc_html($name),
            esc_html($role),
            esc_html($focus)
        );

        $summary = sprintf(
            '%s di RS PKU Muhammadiyah Yogyakarta dengan fokus %s.',
            $spec,
            $categoryLabel
        );

        return [
            'profile' => $profile,
            'summary' => $summary,
            'education' => '',
            'experience' => '',
            'consultation_type' => $categoryLabel,
        ];
    }

    private function clean(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES, get_bloginfo('charset'));
        $value = preg_replace('/\s+/u', ' ', trim($value));

        return is_string($value) ? $value : '';
    }

    private function normalizeText(string $value): string
    {
        $value = strtolower($this->clean($value));
        $value = str_replace(['&', '/', '(', ')', '.', ',', '-', "'", '’', '‘'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return is_string($value) ? trim($value) : '';
    }

    private function normalizeName(string $name): string
    {
        return $this->normalizeText($name);
    }

    private function normalizeDayKey(string $label): string
    {
        return match ($this->normalizeText($label)) {
            'senin' => 'monday',
            'selasa' => 'tuesday',
            'rabu' => 'wednesday',
            'kamis' => 'thursday',
            'jumat' => 'friday',
            'sabtu' => 'saturday',
            'minggu' => 'sunday',
            default => sanitize_key($label),
        };
    }

    private function dayLabel(string $dayKey): string
    {
        return match ($dayKey) {
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            'sabtu' => 'Sabtu',
            'minggu' => 'Minggu',
            default => ucfirst($dayKey),
        };
    }

    private function isGenericDirectoryLabel(string $name): bool
    {
        return in_array(
            $this->normalizeText($name),
            [
                'fisioterapi',
                'okupasi terapis',
                'terapis wicara',
            ],
            true
        );
    }

    private function profileRole(string $name, string $specialization): string
    {
        $normalizedName = $this->normalizeText($name);
        $normalizedSpecialization = $this->normalizeText($specialization);
        $isDoctor = str_contains($normalizedName, 'dr') || str_contains($normalizedName, 'drg') || str_contains($normalizedName, 'prof');

        if ($isDoctor) {
            return 'dokter spesialis ' . $specialization;
        }

        if (str_contains($normalizedSpecialization, 'psikolog')) {
            return 'psikolog klinis';
        }

        if (
            str_contains($normalizedSpecialization, 'terapi')
            || str_contains($normalizedSpecialization, 'fisioterapi')
            || str_contains($normalizedSpecialization, 'okupasi')
        ) {
            return 'praktisi ' . $specialization;
        }

        return 'tenaga profesional ' . $specialization;
    }

    private function categoryOrder(string $category): int
    {
        return match ($category) {
            'Anak' => 10,
            'Bedah' => 20,
            'Gigi' => 30,
            'Jantung' => 40,
            'Kandungan' => 50,
            'Kulit & Kelamin' => 60,
            'Mata' => 70,
            'Paru' => 80,
            'Saraf' => 90,
            'THT' => 100,
            'Penyakit Dalam' => 110,
            'Jiwa' => 120,
            'Penunjang & Terapi' => 130,
            default => 999,
        };
    }
}
