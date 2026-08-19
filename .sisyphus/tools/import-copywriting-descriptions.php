<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

const DEFAULT_ARTIFACT = '.sisyphus/evidence/copywriting-description-import-preflight-uat-20260819.json';
const EXPECTED_ARTIFACT_SHA256 = '4ba99a960f2a2ce2f8f34b8848102381f9b48a90052a6ee927c16f4ee4c69451';
const EXPECTED_TARGET_FIELD = 'post_content';
const EXPECTED_MAPPINGS = 47;
const EXPECTED_SAFE = 30;
const EXPECTED_REVIEW = 17;
const BATCH_ID = 'copywriting-description-uat-20260819-auto-safe-30';
const META_BATCH = '_rspku_copywriting_import_batch';
const META_HEADING = '_rspku_copywriting_source_heading';
const META_SHA256 = '_rspku_copywriting_source_sha256';

function argValue(array $args, string $name): ?string
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

function validateArgs(array $args): void
{
    $valueArgs = ['--wp-root=', '--artifact=', '--evidence=', '--backup='];
    $flagArgs = ['--help', '--apply'];

    foreach ($args as $arg) {
        if (in_array($arg, $flagArgs, true)) {
            continue;
        }
        foreach ($valueArgs as $prefix) {
            if (str_starts_with($arg, $prefix) && strlen($arg) > strlen($prefix)) {
                continue 2;
            }
        }
        throw new InvalidArgumentException("Unknown argument: {$arg}");
    }
}

function help(): void
{
    echo <<<'TEXT'
Usage: php .sisyphus/tools/import-copywriting-descriptions.php [options]

Dry-run by default. Apply requires --apply.

Options:
  --wp-root=<path>    WordPress root containing wp-load.php. Defaults to cwd search.
  --artifact=<path>   Mapping artifact JSON. Defaults to .sisyphus/evidence/copywriting-description-import-preflight-uat-20260819.json
  --evidence=<path>   Optional JSON summary output path. Apply defaults to .sisyphus/evidence/import-copywriting-descriptions-apply-*.json
  --backup=<path>     Optional apply backup path.
  --apply             Execute the approved 30 safe updates.
  --help              Show help.

TEXT;
}

function finish(array $summary, int $exitCode = 0): never
{
    if (isset($summary['evidence_path'])) {
        writeJsonFileAtomic((string) $summary['evidence_path'], $summary);
    }
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    exit($exitCode);
}

function finishWithoutEvidence(array $summary, int $exitCode): never
{
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    exit($exitCode);
}

function fail(array $summary, string $message, int $exitCode = 1): never
{
    $summary['ok'] = false;
    $summary['error'] = $message;
    $summary['db_writes'] ??= 0;
    fwrite(STDERR, $message . "\n");
    if (isset($summary['evidence_path'])) {
        try {
            writeJsonFileAtomic((string) $summary['evidence_path'], $summary);
        } catch (Throwable $evidenceError) {
            $summary['evidence_failed'] = true;
            $summary['evidence_error'] = $evidenceError->getMessage();
        }
    }
    finishWithoutEvidence($summary, $exitCode);
}

function repoRoot(): string
{
    $dir = __DIR__;
    while (!is_file($dir . DIRECTORY_SEPARATOR . 'wp-load.php') && dirname($dir) !== $dir) {
        $dir = dirname($dir);
    }
    return $dir;
}

function resolvePath(string $path, string $base): string
{
    if (preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1 || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
        return $path;
    }
    return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function findWpLoad(?string $wpRoot): string
{
    $dir = $wpRoot !== null ? $wpRoot : getcwd();
    if ($dir === false || $dir === '') {
        throw new RuntimeException('Cannot resolve current working directory.');
    }

    $dir = rtrim($dir, DIRECTORY_SEPARATOR);
    while (!is_file($dir . DIRECTORY_SEPARATOR . 'wp-load.php') && dirname($dir) !== $dir) {
        $dir = dirname($dir);
    }

    $wpLoad = $dir . DIRECTORY_SEPARATOR . 'wp-load.php';
    if (!is_file($wpLoad)) {
        throw new RuntimeException('wp-load.php not found; pass --wp-root=<path>.');
    }

    return $wpLoad;
}

function loadJsonFile(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("JSON file not found: {$path}");
    }
    $json = file_get_contents($path);
    if ($json === false) {
        throw new RuntimeException("Cannot read JSON file: {$path}");
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        throw new RuntimeException("Invalid JSON file: {$path}");
    }
    return $decoded;
}

function writeJsonFile(string $path, array $payload): void
{
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException("Cannot create directory: {$dir}");
    }
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Cannot encode JSON output.');
    }
    if (file_put_contents($path, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException("Cannot write JSON file: {$path}");
    }
}

function assertWritableJsonTarget(string $path): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        throw new RuntimeException("Evidence directory does not exist: {$dir}");
    }
    if (!is_writable($dir)) {
        throw new RuntimeException("Evidence directory is not writable: {$dir}");
    }
    if (file_exists($path) && !is_writable($path)) {
        throw new RuntimeException("Evidence file is not writable: {$path}");
    }
}

function writeJsonFileAtomic(string $path, array $payload): void
{
    assertWritableJsonTarget($path);
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Cannot encode JSON output.');
    }
    $temp = dirname($path) . DIRECTORY_SEPARATOR . '.tmp-' . basename($path) . '-' . bin2hex(random_bytes(4));
    if (file_put_contents($temp, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException("Cannot write temporary JSON file: {$temp}");
    }
    if (file_exists($path) && !unlink($path)) {
        @unlink($temp);
        throw new RuntimeException("Cannot replace JSON file: {$path}");
    }
    if (!rename($temp, $path)) {
        @unlink($temp);
        throw new RuntimeException("Cannot rename temporary JSON file to: {$path}");
    }
}

function requireString(array $data, string $key, string $label): string
{
    $value = $data[$key] ?? null;
    if (!is_string($value) || $value === '') {
        throw new RuntimeException("{$label} missing {$key}.");
    }
    return $value;
}

function validateArtifact(array $artifact, string $artifactPath): array
{
    $artifactSha = hash_file('sha256', $artifactPath);
    if ($artifactSha !== EXPECTED_ARTIFACT_SHA256) {
        throw new RuntimeException('Artifact SHA256 mismatch.');
    }
    if (($artifact['target_field'] ?? null) !== EXPECTED_TARGET_FIELD) {
        throw new RuntimeException('Artifact target_field mismatch.');
    }
    if (!isset($artifact['mappings']) || !is_array($artifact['mappings']) || count($artifact['mappings']) !== EXPECTED_MAPPINGS) {
        throw new RuntimeException('Artifact mapping count mismatch.');
    }

    $safe = [];
    $review = [];
    $safeTargets = [];

    foreach ($artifact['mappings'] as $index => $mapping) {
        if (!is_array($mapping)) {
            throw new RuntimeException("Mapping {$index} is invalid.");
        }
        $targets = $mapping['targets'] ?? null;
        if (!is_array($targets)) {
            throw new RuntimeException("Mapping {$index} targets invalid.");
        }
        $isSafe = ($mapping['auto_apply_safe'] ?? null) === true;
        if ($isSafe) {
            if (count($targets) !== 1) {
                throw new RuntimeException("Safe mapping {$index} does not have exactly one target.");
            }
            $target = $targets[0];
            if (!is_array($target)) {
                throw new RuntimeException("Safe mapping {$index} target invalid.");
            }
            $postId = (int) ($target['post_id'] ?? 0);
            if ($postId <= 0 || isset($safeTargets[$postId])) {
                throw new RuntimeException("Safe mapping {$index} target is missing or duplicated.");
            }
            $safeTargets[$postId] = true;
            if (($target['status'] ?? null) !== 'draft' || ($target['match_status'] ?? null) !== 'matched-by-identity' || (int) ($target['content_len'] ?? -1) !== 0) {
                throw new RuntimeException("Safe mapping {$index} target guard mismatch.");
            }
            if (($mapping['review_reasons'] ?? null) !== []) {
                throw new RuntimeException("Safe mapping {$index} has review reasons.");
            }
            requireString($mapping, 'source_content', "Mapping {$index}");
            requireString($mapping, 'source_heading', "Mapping {$index}");
            $sourceSha = requireString($mapping, 'source_content_sha256', "Mapping {$index}");
            if (hash('sha256', (string) $mapping['source_content']) !== $sourceSha) {
                throw new RuntimeException("Safe mapping {$index} source_content_sha256 mismatch.");
            }
            $sourceLength = (int) ($mapping['source_content_length'] ?? -1);
            if ($sourceLength !== mb_strlen((string) $mapping['source_content'], 'UTF-8')) {
                throw new RuntimeException("Safe mapping {$index} source_content_length mismatch.");
            }
            $safe[] = $mapping;
        } else {
            $review[] = $mapping;
        }
    }

    if (count($safe) !== EXPECTED_SAFE || count($review) !== EXPECTED_REVIEW) {
        throw new RuntimeException('Artifact safe/review count mismatch.');
    }

    return ['artifact_sha256' => $artifactSha, 'safe' => $safe, 'review' => $review];
}

function target(array $mapping): array
{
    $target = $mapping['targets'][0] ?? null;
    if (!is_array($target)) {
        throw new RuntimeException('Mapping target invalid.');
    }
    return $target;
}

function allTargetIds(array $safe, array $review): array
{
    $ids = [];
    foreach ([$safe, $review] as $group) {
        foreach ($group as $mapping) {
            foreach (($mapping['targets'] ?? []) as $target) {
                if (is_array($target) && (int) ($target['post_id'] ?? 0) > 0) {
                    $ids[(int) $target['post_id']] = true;
                }
            }
        }
    }
    return array_map('intval', array_keys($ids));
}

function postRow(int $postId): ?array
{
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT ID, post_title, post_type, post_status, post_content, post_excerpt, post_name, post_modified, post_modified_gmt FROM {$wpdb->posts} WHERE ID = %d",
        $postId
    ), ARRAY_A);
    return is_array($row) ? $row : null;
}

function auditMeta(int $postId): array
{
    global $wpdb;

    $meta = [META_BATCH => '', META_HEADING => '', META_SHA256 => ''];
    foreach (array_keys($meta) as $key) {
        $wpdb->last_error = '';
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
            $postId,
            $key
        ));
        if ($wpdb->last_error !== '') {
            throw new RuntimeException("Post {$postId} audit meta {$key} read failed: {$wpdb->last_error}");
        }
        if (count($rows) > 1) {
            throw new RuntimeException("Post {$postId} audit meta {$key} has duplicate rows.");
        }
        if (count($rows) === 1) {
            $meta[$key] = (string) $rows[0];
        }
    }

    return $meta;
}

function assertLiveGuards(array $safe): array
{
    $checked = [];
    foreach ($safe as $mapping) {
        $expected = target($mapping);
        $postId = (int) $expected['post_id'];
        $row = postRow($postId);
        if ($row === null) {
            throw new RuntimeException("Post {$postId} not found.");
        }
        if ((string) $row['post_title'] !== (string) $expected['title'] || (string) $row['post_type'] !== (string) $expected['cpt'] || (string) $row['post_status'] !== 'draft') {
            throw new RuntimeException("Post {$postId} live identity guard mismatch.");
        }
        if ((string) $row['post_content'] !== '') {
            throw new RuntimeException("Post {$postId} already has content.");
        }
        $meta = auditMeta($postId);
        if ($meta[META_BATCH] !== '' || $meta[META_SHA256] !== '') {
            throw new RuntimeException("Post {$postId} already has copywriting audit meta.");
        }
        $checked[] = $postId;
    }
    return $checked;
}

function backupRows(array $targetIds, string $backupPath, string $artifactSha): array
{
    $records = [];
    sort($targetIds);
    foreach ($targetIds as $postId) {
        $post = postRow($postId);
        if ($post === null) {
            throw new RuntimeException("Cannot back up missing post {$postId}.");
        }
        $records[] = ['post' => $post, 'audit_meta' => auditMeta($postId)];
    }
    if (count($records) !== count($targetIds)) {
        throw new RuntimeException('Backup target count mismatch.');
    }
    $payload = [
        'task' => 'copywriting-description-import-backup',
        'batch' => BATCH_ID,
        'created_at' => gmdate('c'),
        'artifact_sha256' => $artifactSha,
        'target_ids' => $targetIds,
        'records' => $records,
    ];
    $payload['records_sha256'] = hash('sha256', json_encode($records, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    writeJsonFile($backupPath, $payload);
    $payload['backup_path'] = $backupPath;
    $backupFileSha = hash_file('sha256', $backupPath);
    if (!is_string($backupFileSha) || $backupFileSha === '') {
        throw new RuntimeException("Cannot hash backup file: {$backupPath}");
    }
    $payload['backup_file_sha256'] = $backupFileSha;
    return $payload;
}

function requireDbQuery(string $sql, string $label): void
{
    global $wpdb;
    if ($wpdb->query($sql) === false) {
        throw new RuntimeException("{$label} failed: {$wpdb->last_error}");
    }
}

function requireOneAffectedRow(string $label): void
{
    global $wpdb;
    if ((int) $wpdb->rows_affected !== 1) {
        throw new RuntimeException("{$label} affected {$wpdb->rows_affected} rows, expected 1.");
    }
}

function applySafeMappings(array $safe, array &$summary): int
{
    global $wpdb;
    $dbWrites = 0;
    foreach ($safe as $mapping) {
        $target = target($mapping);
        $postId = (int) $target['post_id'];
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->posts} SET post_content = %s WHERE ID = %d AND post_status = 'draft' AND post_content = ''",
            (string) $mapping['source_content'],
            $postId
        ));
        if ($updated === false) {
            throw new RuntimeException("Post {$postId} content update failed: {$wpdb->last_error}");
        }
        requireOneAffectedRow("Post {$postId} content update");
        $summary['db_writes'] = ++$dbWrites;

        insertAuditMeta($postId, META_BATCH, BATCH_ID);
        $summary['db_writes'] = ++$dbWrites;
        insertAuditMeta($postId, META_HEADING, (string) $mapping['source_heading']);
        $summary['db_writes'] = ++$dbWrites;
        insertAuditMeta($postId, META_SHA256, (string) $mapping['source_content_sha256']);
        $summary['db_writes'] = ++$dbWrites;
    }
    return $dbWrites;
}

function insertAuditMeta(int $postId, string $key, string $value): void
{
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
        $postId,
        $key
    ));
    if ($exists !== null) {
        throw new RuntimeException("Post {$postId} audit meta {$key} already exists.");
    }
    $inserted = $wpdb->insert($wpdb->postmeta, [
        'post_id' => $postId,
        'meta_key' => $key,
        'meta_value' => $value,
    ], ['%d', '%s', '%s']);
    if ($inserted !== 1) {
        throw new RuntimeException("Post {$postId} audit meta {$key} insert failed: {$wpdb->last_error}");
    }
}

function verifyApplied(array $safe, array $review): array
{
    $safeVerified = [];
    $appliedByPostId = [];
    foreach ($safe as $mapping) {
        $postId = (int) target($mapping)['post_id'];
        $row = postRow($postId);
        if ($row === null || hash('sha256', (string) $row['post_content']) !== (string) $mapping['source_content_sha256']) {
            throw new RuntimeException("Post {$postId} content verification failed.");
        }
        $meta = auditMeta($postId);
        if ($meta[META_BATCH] !== BATCH_ID || $meta[META_HEADING] !== (string) $mapping['source_heading'] || $meta[META_SHA256] !== (string) $mapping['source_content_sha256']) {
            throw new RuntimeException("Post {$postId} audit meta verification failed.");
        }
        $safeVerified[] = $postId;
        $appliedByPostId[$postId] = [
            'source_heading' => (string) $mapping['source_heading'],
            'source_content_sha256' => (string) $mapping['source_content_sha256'],
        ];
    }

    $reviewChecked = 0;
    foreach ($review as $mapping) {
        $reviewHeading = (string) ($mapping['source_heading'] ?? '');
        $reviewSha = (string) ($mapping['source_content_sha256'] ?? '');
        foreach (($mapping['targets'] ?? []) as $target) {
            if (!is_array($target) || (int) ($target['post_id'] ?? 0) <= 0) {
                continue;
            }
            $postId = (int) $target['post_id'];
            $meta = auditMeta($postId);
            $allowedOverlap = isset($appliedByPostId[$postId])
                && $meta[META_HEADING] === $appliedByPostId[$postId]['source_heading']
                && $meta[META_SHA256] === $appliedByPostId[$postId]['source_content_sha256'];
            if (!$allowedOverlap && (($reviewHeading !== '' && $meta[META_HEADING] === $reviewHeading) || ($reviewSha !== '' && $meta[META_SHA256] === $reviewSha))) {
                throw new RuntimeException("Review mapping was applied to post {$target['post_id']}.");
            }
            $reviewChecked++;
        }
    }

    return ['safe_verified' => $safeVerified, 'review_targets_checked' => $reviewChecked];
}

$args = array_slice($argv, 1);
try {
    validateArgs($args);
} catch (InvalidArgumentException $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    echo json_encode([
        'ok' => false,
        'mode' => hasFlag($args, '--apply') ? 'apply' : 'dry-run',
        'batch' => BATCH_ID,
        'db_writes' => 0,
        'error' => $error->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}
if (hasFlag($args, '--help')) {
    help();
    exit(0);
}

$summary = [
    'ok' => false,
    'mode' => hasFlag($args, '--apply') ? 'apply' : 'dry-run',
    'batch' => BATCH_ID,
    'db_writes' => 0,
];

try {
    $root = repoRoot();
    $artifactPath = resolvePath(argValue($args, '--artifact') ?? DEFAULT_ARTIFACT, $root);
    $evidence = argValue($args, '--evidence');
    if ($evidence !== null) {
        $summary['evidence_path'] = resolvePath($evidence, $root);
    }
    if ($summary['mode'] === 'apply' && !isset($summary['evidence_path'])) {
        $summary['evidence_path'] = resolvePath('.sisyphus/evidence/import-copywriting-descriptions-apply-' . gmdate('Ymd-His') . '.json', $root);
    }

    $artifact = loadJsonFile($artifactPath);
    $validated = validateArtifact($artifact, $artifactPath);
    $summary['artifact_path'] = $artifactPath;
    $summary['artifact_sha256'] = $validated['artifact_sha256'];
    $summary['mappings'] = EXPECTED_MAPPINGS;
    $summary['auto_apply_safe'] = count($validated['safe']);
    $summary['review'] = count($validated['review']);

    require_once findWpLoad(argValue($args, '--wp-root'));

    $checked = assertLiveGuards($validated['safe']);
    $summary['live_guarded_post_ids'] = $checked;

    if ($summary['mode'] !== 'apply') {
        $summary['ok'] = true;
        finish($summary);
    }

    assertWritableJsonTarget((string) $summary['evidence_path']);
    $summary['status'] = 'pending';
    writeJsonFileAtomic((string) $summary['evidence_path'], $summary);

    $targetIds = allTargetIds($validated['safe'], $validated['review']);
    $backupPath = resolvePath(argValue($args, '--backup') ?? ('.sisyphus/evidence/import-copywriting-descriptions-backup-' . gmdate('Ymd-His') . '.json'), $root);
    $backup = backupRows($targetIds, $backupPath, $validated['artifact_sha256']);
    $summary['backup_path'] = $backupPath;
    $summary['backup_file_sha256'] = $backup['backup_file_sha256'];

    $transactionActive = false;
    $committed = false;
    requireDbQuery('START TRANSACTION', 'START TRANSACTION');
    $transactionActive = true;
    try {
        $summary['db_writes'] = applySafeMappings($validated['safe'], $summary);
        $summary['verification'] = verifyApplied($validated['safe'], $validated['review']);
        requireDbQuery('COMMIT', 'COMMIT');
        $transactionActive = false;
        $committed = true;
    } catch (Throwable $writeError) {
        if ($transactionActive) {
            requireDbQuery('ROLLBACK', 'ROLLBACK');
            $summary['attempted_db_writes'] = $summary['db_writes'];
            $summary['db_writes'] = 0;
            $summary['status'] = 'rolled_back';
        }
        throw $writeError;
    }

    $summary['ok'] = true;
    $summary['status'] = 'committed';
    try {
        writeJsonFileAtomic((string) $summary['evidence_path'], $summary);
    } catch (Throwable $evidenceError) {
        $summary['ok'] = false;
        $summary['committed'] = $committed;
        $summary['committed_but_evidence_failed'] = true;
        $summary['evidence_error'] = $evidenceError->getMessage();
        finishWithoutEvidence($summary, 1);
    }
    finishWithoutEvidence($summary, 0);
} catch (Throwable $error) {
    fail($summary, $error->getMessage());
}
