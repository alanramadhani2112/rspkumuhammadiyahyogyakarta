<?php

declare(strict_types=1);

/**
 * Read-only reconciliation dry-run for source 2026 data.
 * Default behavior writes evidence JSON only; it never mutates WordPress.
 */

$root = dirname(__DIR__, 1);
$wpRoot = dirname(__DIR__, 1);
$projectRoot = dirname(__DIR__, 1);
while (!is_file($projectRoot . '/wp-load.php') && dirname($projectRoot) !== $projectRoot) {
    $projectRoot = dirname($projectRoot);
}

$auditPath = $projectRoot . '/.sisyphus/drafts/audit-source-2026-vs-website.md';
$evidenceDir = $projectRoot . '/.sisyphus/evidence';
$wpLoad = $projectRoot . '/wp-load.php';

$args = array_slice($argv, 1);
if (in_array('--apply', $args, true)) {
    fwrite(STDERR, "Apply mode is intentionally not implemented in T1-T3. Use dry-run only.\n");
    exit(2);
}

$makeApprovalPackage = in_array('--approval-package', $args, true);

if (!is_file($auditPath)) {
    fwrite(STDERR, "Missing audit report: {$auditPath}\n");
    exit(1);
}

if (!is_dir($evidenceDir)) {
    mkdir($evidenceDir, 0777, true);
}

function slug_key(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = strtolower($value);
    $value = str_replace(['&', '/', '+'], ' ', $value);
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    return trim($value, '-');
}

function parse_matrix(string $markdown, string $heading, string $sourcePrefix): array
{
    $start = strpos($markdown, "## {$heading}");
    if ($start === false) {
        return [];
    }

    $next = strpos($markdown, "\n## ", $start + 4);
    $section = $next === false ? substr($markdown, $start) : substr($markdown, $start, $next - $start);
    $rows = [];

    foreach (preg_split('/\R/', $section) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] !== '|' || str_contains($line, '---') || str_contains($line, 'Classification |')) {
            continue;
        }

        $cells = array_map('trim', explode('|', trim($line, '|')));
        if (count($cells) < 7) {
            continue;
        }

        $sourceName = $cells[1];
        $sourceGroup = $cells[2];
        $rows[] = [
            'source_id' => $sourcePrefix . '::' . slug_key($sourceName) . '::' . slug_key($sourceGroup),
            'classification' => $cells[0],
            'source_name' => $sourceName,
            'source_group' => $sourceGroup,
            'current_wp_cpt' => $cells[3] ?: null,
            'matched_existing_title' => $cells[4] ?: null,
            'canonical_title' => $cells[5] ?: $sourceName,
            'recommended_action' => $cells[6],
        ];
    }

    return $rows;
}

function write_json(string $path, array $payload): void
{
    file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

$audit = file_get_contents($auditPath);
$doctors = parse_matrix($audit, 'Doctor Matrix', 'doctor');
$services = parse_matrix($audit, 'Service Matrix', 'service');

$wpSnapshot = [
    'generated_at' => date(DATE_ATOM),
    'mode' => 'read-only',
    'post_types' => [],
    'taxonomies' => [],
];

if (is_file($wpLoad)) {
    require $wpLoad;

    foreach (['dokter', 'layanan', 'poliklinik', 'rawat-inap'] as $postType) {
        $query = new WP_Query([
            'post_type' => $postType,
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
        ]);

        $wpSnapshot['post_types'][$postType] = array_map(static function (int $id): array {
            return [
                'id' => $id,
                'title' => get_the_title($id),
                'slug' => get_post_field('post_name', $id),
                'status' => get_post_status($id),
                'url' => get_permalink($id),
            ];
        }, $query->posts);
    }

    foreach (['spesialisasi-dokter', 'kategori-layanan', 'jenis-konsultasi'] as $taxonomy) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
        $wpSnapshot['taxonomies'][$taxonomy] = array_map(static function (WP_Term $term): array {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
            ];
        }, is_array($terms) ? $terms : []);
    }
}

$sourceSnapshot = [
    'generated_at' => date(DATE_ATOM),
    'source' => 'audit-source-2026-vs-website.md derived from Profil 2026 Markdown files',
    'counts' => [
        'doctors' => count($doctors),
        'services' => count($services),
    ],
    'doctors' => $doctors,
    'services' => $services,
];

$dryRun = [
    'generated_at' => date(DATE_ATOM),
    'mode' => 'dry-run',
    'mutates_wordpress' => false,
    'summary' => [
        'doctors' => array_count_values(array_column($doctors, 'classification')),
        'services' => array_count_values(array_column($services, 'classification')),
    ],
    'blocked_known_false_matches' => [
        'Klinik Kesehatan Anak != Klinik Kecantikan Ayna',
        'Klinik Urologi != Klinik Patologi',
        'Klinik Bedah Kepala Leher != Klinik Bedah Syaraf',
    ],
    'requires_approval' => array_values(array_filter(array_merge($doctors, $services), static function (array $row): bool {
        return in_array($row['classification'], ['possible-match', 'missing', 'editorial-review'], true);
    })),
];

write_json($evidenceDir . '/source-2026-normalized.json', $sourceSnapshot);
write_json($evidenceDir . '/wp-existing-snapshot.json', $wpSnapshot);
write_json($evidenceDir . '/reconcile-dry-run.json', $dryRun);

if ($makeApprovalPackage) {
    $approvalRows = [];
    foreach ($dryRun['requires_approval'] as $row) {
        $classification = $row['classification'];
        $defaultDecision = $classification === 'editorial-review' ? 'editorial-hold' : 'skip';
        $targetCpt = $row['current_wp_cpt'];

        if ($targetCpt === null && str_starts_with($row['source_id'], 'doctor::')) {
            $targetCpt = 'dokter';
        }

        $approvalRows[] = [
            'source_id' => $row['source_id'],
            'source_name' => $row['source_name'],
            'source_group' => $row['source_group'],
            'classification' => $classification,
            'current_wp_cpt' => $row['current_wp_cpt'],
            'matched_existing_title' => $row['matched_existing_title'],
            'canonical_title' => $row['canonical_title'],
            'recommended_action' => $row['recommended_action'],
            'decision' => $defaultDecision,
            'target_cpt' => $targetCpt,
            'target_wp_id' => null,
            'parent_wp_id' => null,
            'allow_slug_change' => false,
            'allow_term_create' => false,
            'allow_term_assign' => false,
            'approved_by' => null,
            'approved_at' => null,
            'reason' => 'Pending human review. Default is safe no-op.',
        ];
    }

    $approvalPackage = [
        'batch_id' => 'source-2026-review-' . date('Ymd-His'),
        'mode' => 'human-review-required',
        'default_safe_noop' => true,
        'allowed_decisions' => ['keep', 'confirm-match', 'create-draft', 'add-child-detail', 'editorial-hold', 'orphan-review', 'skip'],
        'counts' => [
            'rows_requiring_review' => count($approvalRows),
            'editorial_hold' => count(array_filter($approvalRows, static fn(array $row): bool => $row['decision'] === 'editorial-hold')),
            'skip' => count(array_filter($approvalRows, static fn(array $row): bool => $row['decision'] === 'skip')),
        ],
        'approvals' => $approvalRows,
    ];

    write_json($projectRoot . '/.sisyphus/drafts/reconcile-source-2026-approvals.review.json', $approvalPackage);

    $guide = "# Panduan Review Approval Rekonsiliasi Source 2026\n\n";
    $guide .= "File approval: `.sisyphus/drafts/reconcile-source-2026-approvals.review.json`\n\n";
    $guide .= "## Prinsip\n\n";
    $guide .= "- Default semua row aman: `skip` atau `editorial-hold`.\n";
    $guide .= "- Tidak ada apply sebelum manusia mengisi `decision`, `approved_by`, `approved_at`, dan `reason`.\n";
    $guide .= "- `editorial-review` jangan diubah dari `editorial-hold` sebelum RS memvalidasi source.\n";
    $guide .= "- `create-draft` hanya untuk dokter/layanan utama yang benar-benar belum ada.\n";
    $guide .= "- `add-child-detail` untuk prosedur/fasilitas seperti CT Scan, ECG, Treadmill, varian Ambulans.\n";
    $guide .= "- Jangan set `allow_slug_change=true` kecuali ada keputusan eksplisit.\n\n";
    $guide .= "## Ringkasan Review\n\n";
    $guide .= "- Rows requiring review: " . count($approvalRows) . "\n";
    $guide .= "- Editorial hold default: " . $approvalPackage['counts']['editorial_hold'] . "\n";
    $guide .= "- Skip default: " . $approvalPackage['counts']['skip'] . "\n\n";
    $guide .= "## Urutan Review Disarankan\n\n";
    $guide .= "1. Validasi semua `editorial-review`.\n";
    $guide .= "2. Konfirmasi semua `possible-match`.\n";
    $guide .= "3. Pecah `missing` layanan menjadi `create-draft` atau `add-child-detail`.\n";
    $guide .= "4. Untuk dokter missing, cek jadwal/relasi dulu sebelum `create-draft`.\n";
    $guide .= "5. Jalankan apply hanya setelah approval package bersih.\n";
    file_put_contents($projectRoot . '/.sisyphus/drafts/reconcile-source-2026-review-guide.md', $guide);
}

echo 'DRY_RUN_OK' . PHP_EOL;
echo 'doctors=' . count($doctors) . PHP_EOL;
echo 'services=' . count($services) . PHP_EOL;
echo 'evidence=' . $evidenceDir . PHP_EOL;
if ($makeApprovalPackage) {
    echo 'approval_package=.sisyphus/drafts/reconcile-source-2026-approvals.review.json' . PHP_EOL;
}
