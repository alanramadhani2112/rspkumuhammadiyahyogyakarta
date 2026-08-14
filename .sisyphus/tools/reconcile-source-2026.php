<?php

declare(strict_types=1);

/**
 * Source 2026 reconciliation command.
 * Default behavior is read-only. Apply mode creates approved drafts only.
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
$applyMode = in_array('--apply', $args, true);
$preflightMode = in_array('--preflight', $args, true);
$makeApprovalPackage = in_array('--approval-package', $args, true);

function argument_value(array $args, string $name): ?string
{
    $prefix = $name . '=';
    foreach ($args as $arg) {
        if (str_starts_with($arg, $prefix)) {
            return substr($arg, strlen($prefix));
        }
    }

    return null;
}

$approvedFile = argument_value($args, '--approved-file');
$batchId = argument_value($args, '--batch-id');

if (($applyMode || $preflightMode) && ($approvedFile === null || $batchId === null || $batchId === '')) {
    fwrite(STDERR, "Apply/preflight requires --approved-file=<path> and --batch-id=<id>.\n");
    exit(2);
}

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

function matching_post_ids(array $queryArgs): array
{
    $query = new WP_Query(array_merge([
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields' => 'ids',
    ], $queryArgs));

    return array_map('intval', $query->posts);
}

function apply_approved_drafts(string $approvedFile, string $batchId, string $projectRoot, bool $write = true): array
{
    $resolvedFile = $approvedFile;
    if (!str_starts_with($resolvedFile, '/') && !preg_match('/^[A-Za-z]:[\\\\\/]/', $resolvedFile)) {
        $resolvedFile = $projectRoot . '/' . ltrim(str_replace('\\', '/', $resolvedFile), '/');
    }

    if (!is_file($resolvedFile)) {
        throw new RuntimeException("Approval file not found: {$resolvedFile}");
    }

    $approvalJson = ltrim((string) file_get_contents($resolvedFile), "\xEF\xBB\xBF");
    $package = json_decode($approvalJson, true, 512, JSON_THROW_ON_ERROR);
    $rows = $package['approvals'] ?? null;
    if (!is_array($rows)) {
        throw new RuntimeException('Approval file must contain an approvals array.');
    }

    $allowedPostTypes = ['dokter', 'layanan', 'poliklinik', 'rawat-inap'];
    $preflight = [];
    $errors = [];

    foreach ($rows as $row) {
        if (($row['decision'] ?? 'skip') !== 'create-draft') {
            continue;
        }

        $sourceId = trim((string) ($row['source_id'] ?? ''));
        $title = trim((string) ($row['canonical_title'] ?? $row['source_name'] ?? ''));
        $postType = (string) ($row['target_cpt'] ?? '');
        $approvedBy = trim((string) ($row['approved_by'] ?? ''));
        $approvedAt = trim((string) ($row['approved_at'] ?? ''));
        $reason = trim((string) ($row['reason'] ?? ''));
        $classification = (string) ($row['classification'] ?? 'missing');
        $slug = sanitize_title($title);

        if ($sourceId === '' || $title === '' || !in_array($postType, $allowedPostTypes, true)) {
            $errors[] = "Invalid approved row: {$sourceId}";
            continue;
        }
        if ($approvedBy === '' || $approvedAt === '' || $reason === '' || $reason === 'Pending human review. Default is safe no-op.') {
            $errors[] = "Missing human approval metadata: {$sourceId}";
            continue;
        }
        if (($row['allow_slug_change'] ?? false) !== false || ($row['allow_term_create'] ?? false) !== false || ($row['allow_term_assign'] ?? false) !== false) {
            $errors[] = "Batch 1 forbids slug/taxonomy mutation: {$sourceId}";
            continue;
        }

        $sourceMatches = matching_post_ids([
            'post_type' => $postType,
            'meta_key' => '_source_2026_key',
            'meta_value' => $sourceId,
        ]);
        $titleMatches = matching_post_ids([
            'post_type' => $postType,
            'title' => $title,
        ]);
        $slugMatches = matching_post_ids([
            'post_type' => $postType,
            'name' => $slug,
        ]);

        if ($sourceMatches !== [] || $titleMatches !== [] || $slugMatches !== []) {
            $errors[] = sprintf(
                'Collision for %s (source=%s title=%s slug=%s)',
                $sourceId,
                implode(',', $sourceMatches),
                implode(',', $titleMatches),
                implode(',', $slugMatches)
            );
            continue;
        }

        $preflight[] = [
            'source_id' => $sourceId,
            'source_name' => (string) ($row['source_name'] ?? $title),
            'canonical_title' => $title,
            'slug' => $slug,
            'target_cpt' => $postType,
            'classification' => $classification,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
            'reason' => $reason,
        ];
    }

    if ($errors !== []) {
        throw new RuntimeException("Preflight blocked apply:\n- " . implode("\n- ", $errors));
    }
    if ($preflight === []) {
        throw new RuntimeException('No create-draft approvals found. Nothing applied.');
    }

    if (!$write) {
        return [
            'manifest' => null,
            'created' => [],
            'preflight' => $preflight,
        ];
    }

    $created = [];
    try {
        foreach ($preflight as $item) {
            $postId = wp_insert_post([
                'post_type' => $item['target_cpt'],
                'post_status' => 'draft',
                'post_title' => $item['canonical_title'],
                'post_name' => $item['slug'],
            ], true);

            if (is_wp_error($postId)) {
                throw new RuntimeException($postId->get_error_message());
            }

            $postId = (int) $postId;
            update_post_meta($postId, '_source_2026_key', $item['source_id']);
            update_post_meta($postId, '_source_2026_hash', hash('sha256', json_encode($item, JSON_UNESCAPED_UNICODE)));
            update_post_meta($postId, '_reconcile_batch_id', $batchId);
            update_post_meta($postId, '_reconcile_classification', $item['classification']);
            update_post_meta($postId, '_reconcile_approved_by', $item['approved_by']);
            update_post_meta($postId, '_reconcile_approved_at', $item['approved_at']);

            $created[] = array_merge($item, [
                'post_id' => $postId,
                'status' => get_post_status($postId),
                'previous_values' => null,
                'new_values' => [
                    'post_type' => $item['target_cpt'],
                    'post_status' => 'draft',
                    'post_title' => $item['canonical_title'],
                    'post_name' => $item['slug'],
                    '_source_2026_key' => $item['source_id'],
                    '_reconcile_batch_id' => $batchId,
                ],
                'rollback' => "Delete draft post {$postId} only if batch rollback is approved.",
            ]);
        }
    } catch (Throwable $error) {
        foreach ($created as $item) {
            if (($item['status'] ?? null) === 'draft') {
                wp_delete_post((int) $item['post_id'], true);
            }
        }
        throw $error;
    }

    $manifest = [
        'batch_id' => $batchId,
        'applied_at' => date(DATE_ATOM),
        'approval_file' => $approvedFile,
        'created_count' => count($created),
        'updated_count' => 0,
        'deleted_existing_count' => 0,
        'created' => $created,
    ];
    $manifestPath = $projectRoot . '/.sisyphus/evidence/reconcile-apply-manifest-' . slug_key($batchId) . '.json';
    write_json($manifestPath, $manifest);

    return ['manifest' => $manifestPath, 'created' => $created];
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

$applyResult = null;
if ($applyMode || $preflightMode) {
    if (!is_file($wpLoad)) {
        fwrite(STDERR, "Missing WordPress bootstrap: {$wpLoad}\n");
        exit(1);
    }

    try {
        $applyResult = apply_approved_drafts((string) $approvedFile, (string) $batchId, $projectRoot, $applyMode);
    } catch (Throwable $error) {
        fwrite(STDERR, $error->getMessage() . PHP_EOL);
        exit(3);
    }
}

echo 'DRY_RUN_OK' . PHP_EOL;
echo 'doctors=' . count($doctors) . PHP_EOL;
echo 'services=' . count($services) . PHP_EOL;
echo 'evidence=' . $evidenceDir . PHP_EOL;
if ($makeApprovalPackage) {
    echo 'approval_package=.sisyphus/drafts/reconcile-source-2026-approvals.review.json' . PHP_EOL;
}
if ($applyResult !== null && $preflightMode) {
    echo 'PREFLIGHT_OK' . PHP_EOL;
    echo 'approved=' . count($applyResult['preflight']) . PHP_EOL;
}
if ($applyResult !== null && $applyMode) {
    echo 'APPLY_OK' . PHP_EOL;
    echo 'created=' . count($applyResult['created']) . PHP_EOL;
    echo 'manifest=' . $applyResult['manifest'] . PHP_EOL;
}
