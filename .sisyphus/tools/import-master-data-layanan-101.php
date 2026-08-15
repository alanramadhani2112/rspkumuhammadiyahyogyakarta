<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

const IMPORT_NAME = 'master-data-layanan-medis-101';
const TAXONOMY = 'master-layanan-medis';
const EXPECTED_SOURCE_ROWS = 101;
const EXPECTED_TOTAL_ENTITIES = 104;
const EXPECTED_POLIKLINIK = 40;
const EXPECTED_LAYANAN = 59;
const EXPECTED_RAWAT_INAP = 5;
const EXPECTED_SHEET = 'Master Layanan 1-N';
const SPREADSHEET_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
const OFFICE_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
const PACKAGE_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';
const EXPECTED_HEADERS = [
    'No.',
    'Domain',
    'Kategori',
    'Parent',
    'Nama Layanan',
    'Detail / Deskripsi dari Source',
    'Sumber',
    'Dokumen',
    'Catatan Validasi',
];
const ROW_FIELDS = [
    'no',
    'domain',
    'kategori',
    'parent',
    'nama_layanan',
    'detail_deskripsi_dari_source',
    'sumber',
    'dokumen',
    'catatan_validasi',
];
const APPROVAL_DECISIONS = ['skip', 'review-required', 'create', 'update', 'assign'];

function argumentValue(array $args, string $name): ?string
{
    $prefix = $name . '=';

    foreach ($args as $arg) {
        if (str_starts_with($arg, $prefix)) {
            return substr($arg, strlen($prefix));
        }
    }

    return null;
}

function hasFlag(array $args, string $flag): bool
{
    return in_array($flag, $args, true);
}

function printHelp(): void
{
    echo <<<'TEXT'
Usage: php .sisyphus/tools/import-master-data-layanan-101.php [options]

Read-only by default for master-data-layanan-medis-101. Apply requires explicit local approval.

Options:
  --source=<path>         Planned XLSX source path.
  --batch-id=<id>        Planned batch identifier.
  --approved-file=<path> Planned approval file path.
  --preflight            Planned read-only preflight mode.
  --apply                Guarded local write mode; draft creates only.
  --help                 Show this help.

TEXT;
}

function failReadOnly(array $summary, string $message, int $exitCode = 1): never
{
    $summary['error'] = $message;
    $summary['db_writes'] = 0;

    fwrite(STDERR, $message . "\n");
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    exit($exitCode);
}

function loadXml(string $xml, string $name): SimpleXMLElement
{
    $loaded = simplexml_load_string($xml);

    if (!$loaded instanceof SimpleXMLElement) {
        throw new RuntimeException("Invalid XLSX XML: {$name}");
    }

    return $loaded;
}

function zipRead(ZipArchive $zip, string $path): string
{
    $contents = $zip->getFromName($path);

    if ($contents === false) {
        throw new RuntimeException("Missing XLSX part: {$path}");
    }

    return $contents;
}

function normalizeZipPath(string $basePath, string $target): string
{
    if (str_starts_with($target, '/')) {
        return ltrim($target, '/');
    }

    $parts = explode('/', trim(dirname($basePath) . '/' . $target, '/'));
    $normalized = [];

    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }

        if ($part === '..') {
            array_pop($normalized);
            continue;
        }

        $normalized[] = $part;
    }

    return implode('/', $normalized);
}

function columnIndex(string $cellReference): int
{
    if (!preg_match('/^([A-Z]+)/i', $cellReference, $matches)) {
        throw new RuntimeException("Invalid cell reference: {$cellReference}");
    }

    $index = 0;

    foreach (str_split(strtoupper($matches[1])) as $letter) {
        $index = ($index * 26) + (ord($letter) - 64);
    }

    return $index - 1;
}

function sharedStringValue(SimpleXMLElement $item): string
{
    $item->registerXPathNamespace('x', SPREADSHEET_NS);
    $pieces = [];

    foreach ($item->xpath('.//x:t') ?: [] as $text) {
        $pieces[] = (string) $text;
    }

    return trim(implode('', $pieces));
}

/**
 * @return list<string>
 */
function readSharedStrings(ZipArchive $zip): array
{
    if ($zip->locateName('xl/sharedStrings.xml') === false) {
        return [];
    }

    $xml = loadXml(zipRead($zip, 'xl/sharedStrings.xml'), 'xl/sharedStrings.xml');
    $xml->registerXPathNamespace('x', SPREADSHEET_NS);
    $strings = [];

    foreach ($xml->xpath('/x:sst/x:si') ?: [] as $item) {
        $strings[] = sharedStringValue($item);
    }

    return $strings;
}

function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
{
    $cell->registerXPathNamespace('x', SPREADSHEET_NS);
    $type = (string) ($cell['t'] ?? '');

    if ($type === 'inlineStr') {
        $inlineStrings = $cell->xpath('x:is') ?: [];
        return isset($inlineStrings[0]) ? trim(sharedStringValue($inlineStrings[0])) : '';
    }

    $values = $cell->xpath('x:v') ?: [];
    $raw = isset($values[0]) ? (string) $values[0] : '';

    if ($type === 's') {
        if ($raw === '' || !array_key_exists((int) $raw, $sharedStrings)) {
            return '';
        }

        return trim($sharedStrings[(int) $raw]);
    }

    return trim($raw);
}

function readSheetPath(ZipArchive $zip, string $sheetName): string
{
    $workbook = loadXml(zipRead($zip, 'xl/workbook.xml'), 'xl/workbook.xml');
    $rels = loadXml(zipRead($zip, 'xl/_rels/workbook.xml.rels'), 'xl/_rels/workbook.xml.rels');
    $workbook->registerXPathNamespace('x', SPREADSHEET_NS);
    $rels->registerXPathNamespace('r', PACKAGE_REL_NS);
    $relationshipById = [];

    foreach ($rels->xpath('/r:Relationships/r:Relationship') ?: [] as $relationship) {
        $relationshipById[(string) $relationship['Id']] = (string) $relationship['Target'];
    }

    foreach ($workbook->xpath('/x:workbook/x:sheets/x:sheet') ?: [] as $sheet) {
        if ((string) $sheet['name'] !== $sheetName) {
            continue;
        }

        $attributes = $sheet->attributes(OFFICE_REL_NS);
        $relationshipId = (string) $attributes['id'];

        if (!isset($relationshipById[$relationshipId])) {
            throw new RuntimeException("Missing relationship for sheet: {$sheetName}");
        }

        return normalizeZipPath('xl/workbook.xml', $relationshipById[$relationshipId]);
    }

    throw new RuntimeException("Missing expected sheet: {$sheetName}");
}

/**
 * @return array{headers:list<string>, rows:list<array<string, string|int>>, row_count:int}
 */
function readNormalizedXlsxRows(string $sourcePath): array
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('PHP ZipArchive extension is required to read XLSX; no DB writes performed.');
    }

    if (!is_file($sourcePath)) {
        throw new RuntimeException("Source XLSX not found: {$sourcePath}");
    }

    $zip = new ZipArchive();

    if ($zip->open($sourcePath) !== true) {
        throw new RuntimeException("Unable to open XLSX source: {$sourcePath}");
    }

    try {
        $sharedStrings = readSharedStrings($zip);
        $sheetPath = readSheetPath($zip, EXPECTED_SHEET);
        $sheet = loadXml(zipRead($zip, $sheetPath), $sheetPath);
        $sheet->registerXPathNamespace('x', SPREADSHEET_NS);
        $rawRows = [];

        foreach ($sheet->xpath('/x:worksheet/x:sheetData/x:row') ?: [] as $row) {
            $row->registerXPathNamespace('x', SPREADSHEET_NS);
            $rowNumber = (int) $row['r'];
            $cells = [];

            foreach ($row->xpath('x:c') ?: [] as $cell) {
                $cells[columnIndex((string) $cell['r'])] = cellValue($cell, $sharedStrings);
            }

            ksort($cells);
            $rawRows[$rowNumber] = $cells;
        }
    } finally {
        $zip->close();
    }

    if ($rawRows === []) {
        throw new RuntimeException('Expected sheet has no rows.');
    }

    $headerRowNumber = min(array_keys($rawRows));
    $headers = [];

    for ($index = 0; $index < count(EXPECTED_HEADERS); $index++) {
        $headers[] = $rawRows[$headerRowNumber][$index] ?? '';
    }

    if ($headers !== EXPECTED_HEADERS) {
        throw new RuntimeException('Unexpected headers: ' . json_encode($headers, JSON_UNESCAPED_UNICODE));
    }

    $normalizedRows = [];

    foreach ($rawRows as $rowNumber => $cells) {
        if ($rowNumber === $headerRowNumber) {
            continue;
        }

        $values = [];

        for ($index = 0; $index < count(EXPECTED_HEADERS); $index++) {
            $values[] = $cells[$index] ?? '';
        }

        if (implode('', $values) === '') {
            continue;
        }

        $normalized = ['row_number' => $rowNumber];

        foreach (ROW_FIELDS as $index => $field) {
            $normalized[$field] = $values[$index];
        }

        $normalizedRows[] = $normalized;
    }

    if (count($normalizedRows) !== EXPECTED_SOURCE_ROWS) {
        throw new RuntimeException('Expected row_count=' . EXPECTED_SOURCE_ROWS . '; got row_count=' . count($normalizedRows));
    }

    return [
        'headers' => $headers,
        'rows' => $normalizedRows,
        'row_count' => count($normalizedRows),
    ];
}

function normalizeIdentityPart(string $value): string
{
    return strtolower((string) preg_replace('/\s+/u', ' ', trim($value)));
}

function identityKey(string $targetCpt, string $name, string $kategori, string $parent): string
{
    return implode('|', array_map('normalizeIdentityPart', [$targetCpt, $name, $kategori, $parent]));
}

function sourceHash(array $payload): string
{
    return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function targetCptForRow(array $row): string
{
    $kategori = (string) $row['kategori'];
    $parent = (string) $row['parent'];
    $name = (string) $row['nama_layanan'];

    if ($kategori === 'Bangsal Rawat Inap') {
        return 'rawat-inap';
    }

    if (str_starts_with($name, 'Klinik ')) {
        return 'poliklinik';
    }

    if ($kategori === 'Layanan Rawat Jalan' && (str_contains($parent, 'Spesialis') || str_contains($parent, 'Gigi'))) {
        return 'poliklinik';
    }

    return 'layanan';
}

/**
 * @return array{from:string, to:string, reason:string}|null
 */
function titleCorrectionForRawName(string $rawName): ?array
{
    return match ($rawName) {
        'Neonatal Intensive Care Unit (PICU)' => [
            'from' => $rawName,
            'to' => 'NICU',
            'reason' => 'verified acronym correction: neonatal intensive care unit is NICU, not PICU',
        ],
        'AmbulanEvent Support' => [
            'from' => $rawName,
            'to' => 'Ambulan Event Support',
            'reason' => 'verified spacing correction from source typo',
        ],
        default => null,
    };
}

/**
 * @return list<array<string, mixed>>
 */
function hubRows(): array
{
    return [
        ['name' => 'Cancer Centre', 'kategori' => 'Hub Layanan Unggulan', 'parent' => 'Pusat Layanan Unggulan'],
        ['name' => 'Uronephrology Centre', 'kategori' => 'Hub Layanan Unggulan', 'parent' => 'Pusat Layanan Unggulan'],
        ['name' => 'Emergency and Critical Care', 'kategori' => 'Hub Layanan Unggulan', 'parent' => 'Pusat Layanan Unggulan'],
    ];
}

/**
 * @param list<array<string, string|int>> $rows
 * @return array{entities:list<array<string, mixed>>, counts:array<string, int>, source_identities:int, hub_identities:int, mapping_count:int}
 */
function buildMapping(array $rows): array
{
    $entities = [];
    $counts = ['poliklinik' => 0, 'layanan' => 0, 'rawat-inap' => 0];

    foreach ($rows as $row) {
        $targetCpt = targetCptForRow($row);
        $rawName = (string) $row['nama_layanan'];
        $rawKategori = (string) $row['kategori'];
        $rawParent = (string) $row['parent'];
        $titleCorrection = titleCorrectionForRawName($rawName);
        $identityKey = identityKey($targetCpt, $rawName, $rawKategori, $rawParent);
        $sourcePayload = [
            'source_kind' => 'xlsx-row',
            'source_row_number' => (int) $row['row_number'],
            'raw_domain' => (string) $row['domain'],
            'raw_kategori' => $rawKategori,
            'raw_parent' => $rawParent,
            'raw_name' => $rawName,
            'target_cpt' => $targetCpt,
        ];

        $entity = $sourcePayload + [
            'source_id' => 'xlsx-row-' . (int) $row['row_number'],
            'target_cpt' => $targetCpt,
            'verified_title' => $titleCorrection['to'] ?? $rawName,
            'identity_key' => $identityKey,
            'source_hash' => sourceHash($sourcePayload),
        ];

        if ($titleCorrection !== null) {
            $entity['title_correction'] = $titleCorrection;
        }

        $entities[] = $entity;
        $counts[$targetCpt]++;
    }

    foreach (hubRows() as $index => $hub) {
        $targetCpt = 'layanan';
        $identityKey = identityKey($targetCpt, $hub['name'], $hub['kategori'], $hub['parent']);
        $sourcePayload = [
            'source_kind' => 'hub',
            'source_row_number' => null,
            'raw_domain' => 'Layanan Medis',
            'raw_kategori' => $hub['kategori'],
            'raw_parent' => $hub['parent'],
            'raw_name' => $hub['name'],
            'target_cpt' => $targetCpt,
        ];

        $entities[] = $sourcePayload + [
            'entity_id' => 'hub-' . ($index + 1),
            'target_cpt' => $targetCpt,
            'verified_title' => $hub['name'],
            'identity_key' => $identityKey,
            'source_hash' => sourceHash($sourcePayload),
        ];
        $counts[$targetCpt]++;
    }

    return [
        'entities' => $entities,
        'counts' => $counts,
        'source_identities' => count($rows),
        'hub_identities' => count(hubRows()),
        'mapping_count' => count($entities),
    ];
}

function projectRoot(): string
{
    $projectRoot = dirname(__DIR__);

    while (!is_file($projectRoot . '/wp-load.php') && dirname($projectRoot) !== $projectRoot) {
        $projectRoot = dirname($projectRoot);
    }

    return $projectRoot;
}

function bootstrapWordPressReadOnly(): bool
{
    $wpLoad = projectRoot() . '/wp-load.php';

    if (!is_file($wpLoad)) {
        return false;
    }

    if (!defined('WP_USE_THEMES')) {
        define('WP_USE_THEMES', false);
    }

    require_once $wpLoad;

    return class_exists(WP_Query::class);
}

function normalizeTitleKey(string $value): string
{
    return normalizeIdentityPart(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/**
 * @return array{by_identity:array<string, list<array<string, mixed>>>, by_cpt_title:array<string, list<array<string, mixed>>>}
 */
function buildExistingPostIndex(): array
{
    $index = ['by_identity' => [], 'by_cpt_title' => []];

    foreach (['layanan', 'poliklinik', 'rawat-inap'] as $postType) {
        $query = new WP_Query([
            'post_type' => $postType,
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        foreach (array_map('intval', $query->posts) as $postId) {
            $post = [
                'id' => $postId,
                'title' => get_the_title($postId),
                'status' => get_post_status($postId),
                'post_type' => $postType,
            ];
            $identityKey = (string) get_post_meta($postId, '_rspku_source_identity_key', true);

            if ($identityKey !== '') {
                $index['by_identity'][$identityKey][] = $post;
            }

            $titleKey = $postType . '|' . normalizeTitleKey($post['title']);
            $index['by_cpt_title'][$titleKey][] = $post;
        }
    }

    return $index;
}

/**
 * @param list<array<string, mixed>> $entities
 * @return array<string, list<array{source_id:string, identity_key:string}>>
 */
function duplicateTitleGroups(array $entities): array
{
    $groups = [];

    foreach ($entities as $entity) {
        $groupKey = $entity['target_cpt'] . '|' . normalizeTitleKey((string) $entity['verified_title']);
        $groups[$groupKey][] = [
            'source_id' => (string) ($entity['source_id'] ?? $entity['entity_id'] ?? ''),
            'identity_key' => (string) $entity['identity_key'],
        ];
    }

    return array_filter($groups, static function (array $group): bool {
        return count(array_unique(array_column($group, 'identity_key'))) > 1;
    });
}

/**
 * @param list<array<string, mixed>> $entities
 * @param array{by_identity:array<string, list<array<string, mixed>>>, by_cpt_title:array<string, list<array<string, mixed>>>}|null $postIndex
 * @return array{entities:list<array<string, mixed>>, match_counts:array<string, int>}
 */
function enrichMappingMatches(array $entities, ?array $postIndex): array
{
    $titleDuplicates = duplicateTitleGroups($entities);
    $matchCounts = ['matched-by-identity' => 0, 'possible-existing-title' => 0, 'missing' => 0, 'collision' => 0];

    foreach ($entities as $offset => $entity) {
        $identityKey = (string) $entity['identity_key'];
        $titleKey = $entity['target_cpt'] . '|' . normalizeTitleKey((string) $entity['verified_title']);
        $identityMatches = $postIndex['by_identity'][$identityKey] ?? [];
        $titleMatches = $postIndex['by_cpt_title'][$titleKey] ?? [];
        $collisionReasons = [];
        $matchStatus = 'missing';
        $matchedPost = null;

        if (isset($titleDuplicates[$titleKey])) {
            $entity['duplicate_title_info'] = [
                'normalized_title' => normalizeTitleKey((string) $entity['verified_title']),
                'same_cpt_sources' => $titleDuplicates[$titleKey],
            ];
        }

        if (count($identityMatches) === 1) {
            $matchStatus = 'matched-by-identity';
            $matchedPost = $identityMatches[0];
        } elseif (count($identityMatches) > 1) {
            $matchStatus = 'collision';
            $collisionReasons[] = 'multiple posts share _rspku_source_identity_key';
        } elseif (count($titleMatches) === 1) {
            $matchStatus = 'possible-existing-title';
            $matchedPost = $titleMatches[0];
        } elseif (count($titleMatches) > 1) {
            $matchStatus = 'collision';
            $collisionReasons[] = 'multiple same-CPT title candidates';
        }

        $entity['match_status'] = $matchStatus;
        $entity['matched_post_id'] = $matchedPost['id'] ?? null;
        $entity['matched_post_title'] = $matchedPost['title'] ?? null;
        $entity['matched_post_status'] = $matchedPost['status'] ?? null;
        $entity['collision_reasons'] = $collisionReasons;
        $entities[$offset] = $entity;
        $matchCounts[$matchStatus]++;
    }

    return ['entities' => $entities, 'match_counts' => $matchCounts];
}

function assertExpectedMapping(array $mapping): void
{
    if ($mapping['mapping_count'] !== EXPECTED_TOTAL_ENTITIES) {
        throw new RuntimeException('Expected mapping_count=' . EXPECTED_TOTAL_ENTITIES . '; got mapping_count=' . $mapping['mapping_count']);
    }

    $expected = ['poliklinik' => EXPECTED_POLIKLINIK, 'layanan' => EXPECTED_LAYANAN, 'rawat-inap' => EXPECTED_RAWAT_INAP];

    foreach ($expected as $targetCpt => $count) {
        if (($mapping['counts'][$targetCpt] ?? 0) !== $count) {
            throw new RuntimeException("Expected {$targetCpt}={$count}; got {$targetCpt}=" . ($mapping['counts'][$targetCpt] ?? 0));
        }
    }
}

function mappingArtifactPath(string $batchId): string
{
    return dirname(__DIR__) . '/evidence/master-data-layanan-101-mapping-' . $batchId . '.json';
}

function preflightArtifactPath(string $batchId): string
{
    return dirname(__DIR__) . '/evidence/master-data-layanan-101-preflight-' . $batchId . '.json';
}

function applyArtifactPath(string $batchId): string
{
    return dirname(__DIR__) . '/evidence/master-data-layanan-101-apply-' . $batchId . '.json';
}

function rollbackArtifactPath(string $batchId): string
{
    return dirname(__DIR__) . '/evidence/master-data-layanan-101-rollback-' . $batchId . '.json';
}

function ensureWritableDirectory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
        throw new RuntimeException("Unable to create directory: {$path}");
    }
}

/**
 * @param list<array<string, mixed>> $entities
 * @return list<array<string, mixed>>
 */
function approvalRows(array $entities): array
{
    return array_map(static function (array $entity): array {
        return [
            'source_id' => (string) ($entity['source_id'] ?? $entity['entity_id'] ?? ''),
            'target_cpt' => (string) $entity['target_cpt'],
            'verified_title' => (string) $entity['verified_title'],
            'raw_name' => (string) $entity['raw_name'],
            'raw_kategori' => (string) $entity['raw_kategori'],
            'raw_parent' => (string) $entity['raw_parent'],
            'identity_key' => (string) $entity['identity_key'],
            'match_status' => (string) ($entity['match_status'] ?? 'missing'),
            'matched_post_id' => $entity['matched_post_id'] ?? null,
            'decision' => 'skip',
            'approved_by' => null,
            'approved_at' => null,
            'notes' => null,
        ];
    }, $entities);
}

function buildApprovalPackage(array $summary, array $mapping): array
{
    return [
        'import' => IMPORT_NAME,
        'batch_id' => $summary['batch_id'],
        'source_sha256' => $summary['source_sha256'],
        'source_size' => $summary['source_size'],
        'generated_at' => gmdate('c'),
        'db_writes' => 0,
        'default_decision' => 'skip',
        'allowed_decisions' => APPROVAL_DECISIONS,
        'counts' => [
            'approvals' => $mapping['mapping_count'],
            'target_cpt' => $mapping['counts'],
            'match_status' => $mapping['match_counts'],
        ],
        'approvals' => approvalRows($mapping['entities']),
    ];
}

function writeApprovalPackage(string $path, array $summary, array $mapping): void
{
    ensureWritableDirectory(dirname($path));
    $package = buildApprovalPackage($summary, $mapping);

    if (file_put_contents($path, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n") === false) {
        throw new RuntimeException("Unable to write approval file: {$path}");
    }
}

function validateApprovalFile(string $path, array $summary): array
{
    if (!is_file($path)) {
        throw new RuntimeException("Approved file not found: {$path}");
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    if (!is_array($decoded)) {
        throw new RuntimeException("Approved file is not valid JSON: {$path}");
    }

    if (($decoded['import'] ?? null) !== IMPORT_NAME) {
        throw new RuntimeException('Approved file import mismatch.');
    }

    if (($decoded['batch_id'] ?? null) !== $summary['batch_id']) {
        throw new RuntimeException('Approved file batch_id mismatch.');
    }

    if (($decoded['source_sha256'] ?? null) !== $summary['source_sha256']) {
        throw new RuntimeException('Approved file source_sha256 mismatch.');
    }

    if (($decoded['source_size'] ?? null) !== $summary['source_size']) {
        throw new RuntimeException('Approved file source_size mismatch.');
    }

    $approvals = $decoded['approvals'] ?? null;

    if (!is_array($approvals) || count($approvals) !== EXPECTED_TOTAL_ENTITIES) {
        throw new RuntimeException('Approved file must contain exactly ' . EXPECTED_TOTAL_ENTITIES . ' approval rows.');
    }

    $seen = [];

    foreach ($approvals as $row) {
        if (!is_array($row)) {
            throw new RuntimeException('Approved file contains a non-object approval row.');
        }

        $sourceId = $row['source_id'] ?? null;

        if (!is_string($sourceId) || $sourceId === '') {
            throw new RuntimeException('Approved file contains an empty source_id.');
        }

        if (isset($seen[$sourceId])) {
            throw new RuntimeException("Approved file contains duplicate source_id: {$sourceId}");
        }

        $seen[$sourceId] = true;

        if (!in_array($row['decision'] ?? null, APPROVAL_DECISIONS, true)) {
            throw new RuntimeException("Approved file contains invalid decision for source_id {$sourceId}.");
        }
    }

    return $decoded;
}

function approvalDecisionCounts(array $approvals): array
{
    $counts = array_fill_keys(APPROVAL_DECISIONS, 0);

    foreach ($approvals as $row) {
        $counts[(string) $row['decision']]++;
    }

    $counts['actionable'] = $counts['create'] + $counts['update'] + $counts['assign'];

    return $counts;
}

function buildPreflightArtifact(array $summary, array $mapping, array $approved): array
{
    $approvalDecisions = approvalDecisionCounts($approved['approvals']);
    $blockingErrors = [];
    $warnings = [];
    $approvedSourceIds = array_map(static fn (array $row): string => (string) $row['source_id'], $approved['approvals']);
    $mappingSourceIds = array_map(static fn (array $entity): string => (string) ($entity['source_id'] ?? $entity['entity_id'] ?? ''), $mapping['entities']);

    sort($approvedSourceIds);
    sort($mappingSourceIds);

    if ($approvedSourceIds !== $mappingSourceIds) {
        $blockingErrors[] = 'approval source_id set does not match current mapping';
    }

    if (($mapping['match_counts']['collision'] ?? 0) > 0) {
        $blockingErrors[] = 'collision matches must be resolved before apply';
    }

    if ($approvalDecisions['actionable'] === 0) {
        $blockingErrors[] = 'no approved actionable decisions';
    }

    if ($approvalDecisions['review-required'] > 0) {
        $warnings[] = 'review-required decisions remain held';
    }

    if ($approvalDecisions['skip'] > 0) {
        $warnings[] = 'skip decisions are read-only holds';
    }

    return [
        'import' => IMPORT_NAME,
        'taxonomy' => TAXONOMY,
        'mode' => 'preflight',
        'batch_id' => $summary['batch_id'],
        'source' => $summary['source'],
        'source_sha256' => $summary['source_sha256'],
        'source_size' => $summary['source_size'],
        'approved_file' => $summary['approved_file'],
        'db_writes' => 0,
        'apply_ready' => $blockingErrors === [],
        'blocking_errors' => $blockingErrors,
        'warnings' => $warnings,
        'counts' => [
            'source_rows' => $summary['row_count'],
            'mapping' => $mapping['mapping_count'],
            'target_cpt' => $mapping['counts'],
            'approvals' => count($approved['approvals']),
        ],
        'match_counts' => $mapping['match_counts'],
        'approval_decisions' => $approvalDecisions,
        'wordpress_bootstrapped' => $mapping['wordpress_bootstrapped'],
    ];
}

function writePreflightArtifact(string $path, array $artifact): void
{
    ensureWritableDirectory(dirname($path));

    if (file_put_contents($path, json_encode($artifact, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n") === false) {
        throw new RuntimeException("Unable to write preflight artifact: {$path}");
    }
}

function writeJsonArtifact(string $path, array $artifact): void
{
    ensureWritableDirectory(dirname($path));

    if (file_put_contents($path, json_encode($artifact, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n") === false) {
        throw new RuntimeException("Unable to write artifact: {$path}");
    }
}

function entitySourceId(array $entity): string
{
    return (string) ($entity['source_id'] ?? $entity['entity_id'] ?? '');
}

function assertApprovalAlignment(array $mapping, array $approved): void
{
    $entities = [];

    foreach ($mapping['entities'] as $entity) {
        $entities[entitySourceId($entity)] = $entity;
    }

    foreach ($approved['approvals'] as $row) {
        $sourceId = (string) $row['source_id'];
        $entity = $entities[$sourceId] ?? null;

        if ($entity === null) {
            throw new RuntimeException("Approved source_id missing from mapping: {$sourceId}");
        }

        foreach (['target_cpt', 'verified_title', 'identity_key'] as $field) {
            if (($row[$field] ?? null) !== ($entity[$field] ?? null)) {
                throw new RuntimeException("Approved {$field} mismatch for source_id {$sourceId}.");
            }
        }
    }
}

function requireActionApproval(array $row): void
{
    if (($row['approved_by'] ?? null) === null || trim((string) $row['approved_by']) === '') {
        throw new RuntimeException('Actionable approval missing approved_by for source_id ' . (string) $row['source_id']);
    }

    if (($row['approved_at'] ?? null) === null || trim((string) $row['approved_at']) === '') {
        throw new RuntimeException('Actionable approval missing approved_at for source_id ' . (string) $row['source_id']);
    }
}

function auditMetaForEntity(array $entity): array
{
    $meta = [
        '_rspku_source_import' => IMPORT_NAME,
        '_rspku_source_row_number' => $entity['source_row_number'],
        '_rspku_source_name_raw' => (string) $entity['raw_name'],
        '_rspku_source_category_raw' => (string) $entity['raw_kategori'],
        '_rspku_source_parent_raw' => (string) $entity['raw_parent'],
        '_rspku_source_identity_key' => (string) $entity['identity_key'],
        '_rspku_source_hash' => (string) $entity['source_hash'],
        '_rspku_verified_title' => (string) $entity['verified_title'],
    ];

    if (isset($entity['title_correction'])) {
        $meta['_rspku_source_title_correction'] = json_encode($entity['title_correction'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return $meta;
}

function postSnapshot(int $postId): array
{
    $post = get_post($postId);

    if (!$post instanceof WP_Post) {
        throw new RuntimeException("Post not found: {$postId}");
    }

    $meta = [];

    foreach (array_keys(auditMetaForEntity([
        'source_row_number' => null,
        'raw_name' => '',
        'raw_kategori' => '',
        'raw_parent' => '',
        'identity_key' => '',
        'source_hash' => '',
        'verified_title' => '',
    ])) as $key) {
        $meta[$key] = get_post_meta($postId, $key, true);
    }

    $meta['_rspku_source_title_correction'] = get_post_meta($postId, '_rspku_source_title_correction', true);

    return [
        'id' => $postId,
        'post_type' => $post->post_type,
        'post_status' => $post->post_status,
        'post_title' => $post->post_title,
        'post_name' => $post->post_name,
        'post_content' => $post->post_content,
        'post_excerpt' => $post->post_excerpt,
        'post_date' => $post->post_date,
        'post_date_gmt' => $post->post_date_gmt,
        'meta_input' => $meta,
        'terms' => wp_get_object_terms($postId, TAXONOMY, ['fields' => 'names']),
    ];
}

function updateAuditMeta(int $postId, array $entity): array
{
    $before = [];
    $after = [];

    foreach (auditMetaForEntity($entity) as $key => $value) {
        $before[$key] = get_post_meta($postId, $key, true);
        update_post_meta($postId, $key, $value);
        $after[$key] = get_post_meta($postId, $key, true);
    }

    return ['prior_values' => $before, 'new_values' => $after];
}

function termNamesForEntity(array $entity): array
{
    return array_values(array_unique(array_filter([
        (string) $entity['raw_parent'],
        (string) $entity['raw_kategori'],
    ], static fn (string $value): bool => trim($value) !== '')));
}

function applyApprovedDecisions(array $summary, array $mapping, array $approved, array $preflight): array
{
    if (!function_exists('wp_insert_post')) {
        throw new RuntimeException('WordPress bootstrap unavailable; apply blocked.');
    }

    if (!taxonomy_exists(TAXONOMY)) {
        throw new RuntimeException('Required taxonomy missing: ' . TAXONOMY);
    }

    assertApprovalAlignment($mapping, $approved);

    $onlyNoActionable = $preflight['blocking_errors'] === ['no approved actionable decisions'];
    if (!$preflight['apply_ready'] && !$onlyNoActionable) {
        throw new RuntimeException('Apply blocked by preflight: ' . implode('; ', $preflight['blocking_errors']));
    }

    $entities = [];
    foreach ($mapping['entities'] as $entity) {
        $entities[entitySourceId($entity)] = $entity;
    }

    $created = [];
    $updated = [];
    $assigned = [];
    $operations = [];
    $dbWrites = 0;
    $createdIds = [];

    try {
        foreach ($approved['approvals'] as $row) {
            $decision = (string) $row['decision'];

            if ($decision === 'skip' || $decision === 'review-required') {
                continue;
            }

            requireActionApproval($row);
            $sourceId = (string) $row['source_id'];
            $entity = $entities[$sourceId];

            if ($decision === 'create') {
                if (($entity['match_status'] ?? null) !== 'missing') {
                    throw new RuntimeException("Create requires missing match_status for source_id {$sourceId}.");
                }

                $postId = wp_insert_post([
                    'post_type' => (string) $entity['target_cpt'],
                    'post_status' => 'draft',
                    'post_title' => (string) $entity['verified_title'],
                    'post_content' => '',
                ], true);

                if (is_wp_error($postId)) {
                    throw new RuntimeException($postId->get_error_message());
                }

                $postId = (int) $postId;
                $createdIds[] = $postId;
                $metaChange = updateAuditMeta($postId, $entity);
                $dbWrites += 1 + count($metaChange['new_values']);
                $created[] = [
                    'source_id' => $sourceId,
                    'post_id' => $postId,
                    'status' => get_post_status($postId),
                    'prior_values' => null,
                    'new_values' => postSnapshot($postId),
                    'rollback_notes' => "Delete draft post {$postId} only if rollback is explicitly approved for this failed or applied batch.",
                ];
                $operations[] = ['decision' => 'create', 'source_id' => $sourceId, 'post_id' => $postId];
                continue;
            }

            $postId = (int) ($entity['matched_post_id'] ?? 0);
            if ($postId <= 0) {
                throw new RuntimeException("{$decision} requires matched_post_id for source_id {$sourceId}.");
            }

            if ($decision === 'update') {
                $before = postSnapshot($postId);
                $metaChange = updateAuditMeta($postId, $entity);
                $dbWrites += count($metaChange['new_values']);
                $updated[] = [
                    'source_id' => $sourceId,
                    'post_id' => $postId,
                    'prior_values' => $before,
                    'new_values' => postSnapshot($postId),
                    'changed_meta' => $metaChange,
                    'rollback_notes' => 'Restore audit meta prior_values only; do not alter content/body/slug/status/excerpt/media.',
                ];
                $operations[] = ['decision' => 'update', 'source_id' => $sourceId, 'post_id' => $postId];
                continue;
            }

            if ($decision === 'assign') {
                $before = postSnapshot($postId);
                $termNames = termNamesForEntity($entity);
                $result = wp_set_object_terms($postId, $termNames, TAXONOMY, false);

                if (is_wp_error($result)) {
                    throw new RuntimeException($result->get_error_message());
                }

                $dbWrites++;
                $assigned[] = [
                    'source_id' => $sourceId,
                    'post_id' => $postId,
                    'taxonomy' => TAXONOMY,
                    'assigned_terms' => $termNames,
                    'prior_values' => $before,
                    'new_values' => postSnapshot($postId),
                    'rollback_notes' => 'Restore prior ' . TAXONOMY . ' terms only; never mutate kategori-layanan.',
                ];
                $operations[] = ['decision' => 'assign', 'source_id' => $sourceId, 'post_id' => $postId, 'taxonomy' => TAXONOMY];
            }
        }
    } catch (Throwable $error) {
        foreach ($createdIds as $createdId) {
            if (get_post_status($createdId) === 'draft') {
                wp_delete_post($createdId, true);
            }
        }

        throw $error;
    }

    return [
        'import' => IMPORT_NAME,
        'taxonomy' => TAXONOMY,
        'mode' => 'apply',
        'batch_id' => $summary['batch_id'],
        'source' => $summary['source'],
        'source_sha256' => $summary['source_sha256'],
        'source_size' => $summary['source_size'],
        'approved_file' => $summary['approved_file'],
        'applied_at' => gmdate('c'),
        'preflight' => $preflight,
        'db_writes' => $dbWrites,
        'created_count' => count($created),
        'updated_count' => count($updated),
        'assigned_count' => count($assigned),
        'deleted_existing_count' => 0,
        'published_count' => 0,
        'created' => $created,
        'updated' => $updated,
        'assigned' => $assigned,
        'operations' => $operations,
        'rollback_notes' => 'Created drafts may be deleted only by explicit rollback approval; existing posts restore audit meta or ' . TAXONOMY . ' terms from prior_values.',
    ];
}

$args = array_slice($argv, 1);
$summary = [
    'import' => IMPORT_NAME,
    'taxonomy' => TAXONOMY,
    'source' => argumentValue($args, '--source'),
    'batch_id' => argumentValue($args, '--batch-id'),
    'approved_file' => argumentValue($args, '--approved-file'),
    'preflight' => hasFlag($args, '--preflight'),
    'apply' => hasFlag($args, '--apply'),
    'expected_counts' => [
        'source_rows' => EXPECTED_SOURCE_ROWS,
        'total_entities' => EXPECTED_TOTAL_ENTITIES,
        'poliklinik' => EXPECTED_POLIKLINIK,
        'layanan' => EXPECTED_LAYANAN,
        'rawat_inap' => EXPECTED_RAWAT_INAP,
    ],
    'db_writes' => 0,
];

if (hasFlag($args, '--help')) {
    printHelp();
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

$source = $summary['source'];

if (($summary['preflight'] || $summary['apply']) && ($summary['approved_file'] === null || $summary['approved_file'] === '')) {
    failReadOnly($summary, 'Preflight/apply requires --approved-file for explicit approval gating.');
}

if (($summary['preflight'] || $summary['apply']) && ($summary['batch_id'] === null || $summary['batch_id'] === '')) {
    failReadOnly($summary, 'Preflight/apply requires --batch-id for explicit batch gating.');
}

if (($summary['preflight'] || $summary['apply']) && ($source === null || $source === '')) {
    failReadOnly($summary, 'Preflight/apply requires --source for source hash validation.');
}

if ($source !== null) {
    try {
        $normalized = readNormalizedXlsxRows($source);
    } catch (Throwable $exception) {
        failReadOnly($summary, $exception->getMessage());
    }

    $summary['source_sha256'] = strtoupper(hash_file('sha256', $source));
    $summary['source_size'] = filesize($source);
    $summary['sheet'] = EXPECTED_SHEET;
    $summary['headers'] = $normalized['headers'];
    $summary['row_count'] = $normalized['row_count'];

    try {
        $mapping = buildMapping($normalized['rows']);
        assertExpectedMapping($mapping);
        $wpBootstrapped = bootstrapWordPressReadOnly();
        $matchIndex = $wpBootstrapped ? buildExistingPostIndex() : null;
        $matches = enrichMappingMatches($mapping['entities'], $matchIndex);
        $mapping['entities'] = $matches['entities'];
        $mapping['match_counts'] = $matches['match_counts'];
        $mapping['wordpress_bootstrapped'] = $wpBootstrapped;
    } catch (Throwable $exception) {
        failReadOnly($summary, $exception->getMessage());
    }

    $summary['mapping_count'] = $mapping['mapping_count'];
    $summary['mapping_counts'] = $mapping['counts'];
    $summary['source_identities'] = $mapping['source_identities'];
    $summary['hub_identities'] = $mapping['hub_identities'];
    $summary['match_counts'] = $mapping['match_counts'];
    $summary['wordpress_bootstrapped'] = $mapping['wordpress_bootstrapped'];

    if ($summary['batch_id'] !== null && $summary['batch_id'] !== '') {
        $artifact = [
            'import' => IMPORT_NAME,
            'taxonomy' => TAXONOMY,
            'source' => $summary['source'],
            'source_sha256' => $summary['source_sha256'],
            'source_size' => $summary['source_size'],
            'batch_id' => $summary['batch_id'],
            'sheet' => EXPECTED_SHEET,
            'mapping_count' => $mapping['mapping_count'],
            'mapping_counts' => $mapping['counts'],
            'source_identities' => $mapping['source_identities'],
            'hub_identities' => $mapping['hub_identities'],
            'match_counts' => $mapping['match_counts'],
            'wordpress_bootstrapped' => $mapping['wordpress_bootstrapped'],
            'db_writes' => 0,
            'entities' => $mapping['entities'],
        ];
        $artifactPath = mappingArtifactPath($summary['batch_id']);
        $artifactDir = dirname($artifactPath);

        try {
            ensureWritableDirectory($artifactDir);
        } catch (Throwable $exception) {
            failReadOnly($summary, $exception->getMessage());
        }

        if (file_put_contents($artifactPath, json_encode($artifact, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n") === false) {
            failReadOnly($summary, "Unable to write mapping artifact: {$artifactPath}");
        }

        $summary['mapping_artifact'] = $artifactPath;
    }

    if ($summary['approved_file'] !== null && $summary['approved_file'] !== '') {
        try {
            if (!is_file($summary['approved_file'])) {
                if ($summary['preflight'] || $summary['apply']) {
                    throw new RuntimeException("Approved file not found: {$summary['approved_file']}");
                }

                writeApprovalPackage($summary['approved_file'], $summary, $mapping);
                $summary['approval_file_generated'] = $summary['approved_file'];
                $summary['approval_default_decision'] = 'skip';
                $summary['approval_count'] = $mapping['mapping_count'];
                $summary['rows'] = $normalized['rows'];
                echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
                exit(0);
            }

            $approved = validateApprovalFile($summary['approved_file'], $summary);
            $summary['approved_file_valid'] = true;
            $summary['approval_count'] = count($approved['approvals']);
            $summary['approval_decisions'] = approvalDecisionCounts($approved['approvals']);
        } catch (Throwable $exception) {
            failReadOnly($summary, $exception->getMessage());
        }
    }

    if ($summary['preflight']) {
        try {
            $preflight = buildPreflightArtifact($summary, $mapping, $approved);
            $preflightPath = preflightArtifactPath((string) $summary['batch_id']);
            writePreflightArtifact($preflightPath, $preflight);
            $summary['mode'] = 'preflight';
            $summary['preflight_artifact'] = $preflightPath;
            $summary['apply_ready'] = $preflight['apply_ready'];
            $summary['blocking_errors'] = $preflight['blocking_errors'];
            $summary['warnings'] = $preflight['warnings'];
            $summary['db_writes'] = 0;
            echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            exit(0);
        } catch (Throwable $exception) {
            failReadOnly($summary, $exception->getMessage());
        }
    }

    if ($summary['apply']) {
        try {
            $preflight = buildPreflightArtifact($summary, $mapping, $approved);
            $preflightPath = preflightArtifactPath((string) $summary['batch_id']);
            writePreflightArtifact($preflightPath, $preflight);

            $manifest = applyApprovedDecisions($summary, $mapping, $approved, $preflight);
            $manifestPath = applyArtifactPath((string) $summary['batch_id']);
            $rollbackPath = rollbackArtifactPath((string) $summary['batch_id']);
            writeJsonArtifact($manifestPath, $manifest);
            writeJsonArtifact($rollbackPath, [
                'import' => IMPORT_NAME,
                'batch_id' => $summary['batch_id'],
                'source_sha256' => $summary['source_sha256'],
                'apply_manifest' => $manifestPath,
                'created' => $manifest['created'],
                'updated' => $manifest['updated'],
                'assigned' => $manifest['assigned'],
                'deleted_existing_count' => 0,
                'rollback_notes' => $manifest['rollback_notes'],
            ]);

            $summary['mode'] = 'apply';
            $summary['preflight_artifact'] = $preflightPath;
            $summary['apply_manifest'] = $manifestPath;
            $summary['rollback_manifest'] = $rollbackPath;
            $summary['apply_ready'] = $preflight['apply_ready'];
            $summary['blocking_errors'] = $preflight['blocking_errors'];
            $summary['created'] = $manifest['created_count'];
            $summary['updated'] = $manifest['updated_count'];
            $summary['assigned'] = $manifest['assigned_count'];
            $summary['deleted'] = $manifest['deleted_existing_count'];
            $summary['db_writes'] = $manifest['db_writes'];
            echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            exit(0);
        } catch (Throwable $exception) {
            failReadOnly($summary, $exception->getMessage());
        }
    }

    $summary['rows'] = $normalized['rows'];
}

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
exit(0);
