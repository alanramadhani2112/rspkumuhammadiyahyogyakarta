<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

const DEFAULT_IMPORT_ARTIFACT = '.sisyphus/evidence/copywriting-description-import-preflight-uat-20260819.json';
const DEFAULT_MASTER_ARTIFACT = '.sisyphus/evidence/master-data-layanan-101-mapping-master-data-layanan-101-uat-description-current-20260819.json';
const EXPECTED_IMPORT_SHA256 = '4ba99a960f2a2ce2f8f34b8848102381f9b48a90052a6ee927c16f4ee4c69451';
const EXPECTED_BATCH = 'copywriting-description-uat-20260819-auto-safe-30';
const EXPECTED_SAFE = 30;
const POLI_ACF_REF = 'field_68806409564fa';
const META_IMPORT_BATCH = '_rspku_copywriting_import_batch';
const META_IMPORT_SHA = '_rspku_copywriting_source_sha256';
const META_FIX_BATCH = '_rspku_copywriting_field_fix_batch';
const META_FIX_TARGET = '_rspku_copywriting_field_fix_target';
const META_FIX_SHA = '_rspku_copywriting_field_fix_source_sha256';

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
    $valueArgs = ['--wp-root=', '--import-artifact=', '--master-artifact=', '--evidence=', '--backup=', '--unggulan-term-taxonomy-id=', '--penunjang-term-taxonomy-id='];
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
Usage: php .sisyphus/tools/fix-copywriting-field-placement.php [options]

Dry-run by default. Apply requires --apply.

Options:
  --wp-root=<path>                         WordPress root containing wp-load.php. Defaults to cwd search.
  --import-artifact=<path>                 Defaults to .sisyphus/evidence/copywriting-description-import-preflight-uat-20260819.json
  --master-artifact=<path>                 Defaults to .sisyphus/evidence/master-data-layanan-101-mapping-master-data-layanan-101-uat-description-current-20260819.json
  --evidence=<path>                        Optional JSON summary output path.
  --backup=<path>                          Optional apply backup path.
  --unggulan-term-taxonomy-id=<id>         Enables layanan-unggulan term writes for Centre of Excellence.
  --penunjang-term-taxonomy-id=<id>        Enables layanan-penunjang term writes for Penunjang Medis.
  --apply                                  Execute guarded updates.
  --help                                   Show help.

TEXT;
}

function repoRoot(): string
{
    return dirname(__DIR__, 2);
}

function resolvePath(string $path, string $base): string
{
    if ((strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/')) || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
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
    while (!is_file(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wp-load.php') && dirname($dir) !== $dir) {
        $dir = dirname($dir);
    }
    $wpLoad = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wp-load.php';
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
    $decoded = json_decode((string) file_get_contents($path), true);
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
    if ($json === false || file_put_contents($path, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException("Cannot write JSON file: {$path}");
    }
}

function finish(array $summary, int $exitCode = 0): never
{
    $GLOBALS['rspku_copywriting_field_fix_finished'] = true;
    if (isset($summary['evidence_path'])) {
        writeJsonFile((string) $summary['evidence_path'], $summary);
    }
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    exit($exitCode);
}

function fail(array $summary, string $message): never
{
    $GLOBALS['rspku_copywriting_field_fix_finished'] = true;
    $summary['ok'] = false;
    $summary['error'] = $message;
    $summary['db_writes'] ??= 0;
    fwrite(STDERR, $message . "\n");
    finish($summary, 1);
}

function bootstrapWordPress(string $wpLoad, array &$summary): void
{
    $GLOBALS['rspku_copywriting_field_fix_finished'] = false;
    $GLOBALS['rspku_copywriting_field_fix_bootstrapped'] = false;
    ob_start();
    register_shutdown_function(static function () use (&$summary): void {
        if (!empty($GLOBALS['rspku_copywriting_field_fix_finished']) || !empty($GLOBALS['rspku_copywriting_field_fix_bootstrapped'])) {
            return;
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $summary['ok'] = false;
        $summary['error'] = 'WordPress bootstrap failed before DB preflight completed.';
        $summary['db_writes'] = 0;
        if (isset($summary['evidence_path'])) {
            try {
                writeJsonFile((string) $summary['evidence_path'], $summary);
            } catch (Throwable $e) {
                $summary['evidence_error'] = $e->getMessage();
            }
        }
        echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        exit(1);
    });
    require_once $wpLoad;
    $GLOBALS['rspku_copywriting_field_fix_bootstrapped'] = true;
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
}

function scalarInt(?string $value, string $label): ?int
{
    if ($value === null) {
        return null;
    }
    if (!ctype_digit($value) || (int) $value <= 0) {
        throw new InvalidArgumentException("{$label} must be a positive integer.");
    }
    return (int) $value;
}

function validateImportArtifact(array $artifact, string $path): array
{
    if (hash_file('sha256', $path) !== EXPECTED_IMPORT_SHA256) {
        throw new RuntimeException('Import artifact SHA256 mismatch.');
    }
    $safe = [];
    $review = 0;
    foreach (($artifact['mappings'] ?? []) as $mapping) {
        if (($mapping['auto_apply_safe'] ?? false) === true) {
            if (count($mapping['targets'] ?? []) !== 1) {
                throw new RuntimeException('Safe mapping target count mismatch.');
            }
            $target = $mapping['targets'][0];
            $source = (string) ($mapping['source_content'] ?? '');
            $sha = (string) ($mapping['source_content_sha256'] ?? '');
            if ($source === '' || hash('sha256', $source) !== $sha) {
                throw new RuntimeException('Safe mapping source SHA mismatch.');
            }
            $safe[] = ['post_id' => (int) $target['post_id'], 'cpt' => (string) $target['cpt'], 'source' => $source, 'sha' => $sha];
        } else {
            $review++;
        }
    }
    if (count($safe) !== EXPECTED_SAFE || $review !== 17) {
        throw new RuntimeException('Import artifact safe/review count mismatch.');
    }
    return $safe;
}

function masterByPostId(array $master): array
{
    $byPostId = [];
    foreach (($master['entities'] ?? []) as $entity) {
        $postId = (int) ($entity['matched_post_id'] ?? 0);
        if ($postId > 0) {
            $byPostId[$postId] = $entity;
        }
    }
    return $byPostId;
}

function metaValue(int $postId, string $key): string
{
    global $wpdb;
    $value = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id DESC LIMIT 1", $postId, $key));
    return $value === null ? '' : (string) $value;
}

function discoveredMetaValue(string $key): string
{
    global $wpdb;
    $value = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value <> '' ORDER BY meta_id DESC LIMIT 1", $key));
    $value = $value === null ? '' : (string) $value;
    return str_starts_with($value, 'field_') ? $value : '';
}

function metaExists(int $postId, string $key): bool
{
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1", $postId, $key)) !== null;
}

function postRow(int $postId): ?array
{
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT ID, post_title, post_type, post_status, post_content FROM {$wpdb->posts} WHERE ID = %d", $postId), ARRAY_A);
    return is_array($row) ? $row : null;
}

function targetMetaKey(string $cpt): ?string
{
    return ['poliklinik' => 'detail_poli', 'layanan' => 'detail_layanan', 'rawat-inap' => 'deskripsi'][$cpt] ?? null;
}

function buildPlan(array $safe, array $masterByPostId, ?int $unggulanTtId, ?int $penunjangTtId): array
{
    $plan = ['meta' => [], 'terms' => [], 'review' => [], 'warnings' => []];
    $discoveredLayananRef = discoveredMetaValue('_detail_layanan');
    foreach ($safe as $item) {
        $postId = (int) $item['post_id'];
        $row = postRow($postId);
        if ($row === null) {
            throw new RuntimeException("UAT post {$postId} absent.");
        }
        $metaKey = targetMetaKey($item['cpt']);
        if ($metaKey === null || (string) $row['post_type'] !== $item['cpt'] || hash('sha256', (string) $row['post_content']) !== $item['sha'] || metaValue($postId, META_IMPORT_BATCH) !== EXPECTED_BATCH || metaValue($postId, META_IMPORT_SHA) !== $item['sha']) {
            throw new RuntimeException("Post {$postId} guard mismatch.");
        }
        if (metaValue($postId, $metaKey) === '') {
            $acfRef = null;
            if ($item['cpt'] === 'poliklinik') {
                $acfRef = POLI_ACF_REF;
            } elseif ($item['cpt'] === 'layanan') {
                $acfRef = metaValue($postId, '_' . $metaKey) ?: ($discoveredLayananRef ?: null);
                if ($acfRef === null) {
                    $plan['warnings'][] = "Post {$postId} has no _detail_layanan ACF reference; detail_layanan only.";
                }
            }
            $plan['meta'][] = ['post_id' => $postId, 'cpt' => $item['cpt'], 'key' => $metaKey, 'value' => $item['source'], 'sha' => $item['sha'], 'acf_ref' => $acfRef];
        }
        if ($item['cpt'] !== 'layanan') {
            continue;
        }
        $entity = $masterByPostId[$postId] ?? [];
        $termId = null;
        if (($entity['raw_domain'] ?? null) === 'Centre of Excellence') {
            $termId = $unggulanTtId;
            $bucket = 'category_unggulan';
        } elseif (($entity['raw_kategori'] ?? null) === 'Penunjang Medis') {
            $termId = $penunjangTtId;
            $bucket = 'category_penunjang';
        } else {
            $plan['review'][] = ['post_id' => $postId, 'raw_domain' => $entity['raw_domain'] ?? null, 'raw_kategori' => $entity['raw_kategori'] ?? null];
            continue;
        }
        if ($termId === null) {
            $plan['review'][] = ['post_id' => $postId, 'missing_term_option' => $bucket];
            continue;
        }
        $plan['terms'][] = ['post_id' => $postId, 'term_taxonomy_id' => $termId, 'bucket' => $bucket];
    }
    return $plan;
}

function backupRows(array $postIds, string $backupPath): array
{
    global $wpdb;
    sort($postIds);
    $idList = implode(',', array_map('intval', $postIds));
    $records = [
        'posts' => $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE ID IN ({$idList}) ORDER BY ID", ARRAY_A),
        'postmeta' => $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} WHERE post_id IN ({$idList}) ORDER BY post_id, meta_id", ARRAY_A),
        'term_relationships' => $wpdb->get_results("SELECT * FROM {$wpdb->term_relationships} WHERE object_id IN ({$idList}) ORDER BY object_id, term_taxonomy_id", ARRAY_A),
    ];
    writeJsonFile($backupPath, ['task' => 'copywriting-field-placement-backup', 'batch' => EXPECTED_BATCH, 'created_at' => gmdate('c'), 'post_ids' => $postIds, 'records' => $records]);
    return ['backup_path' => $backupPath, 'backup_sha256' => hash_file('sha256', $backupPath)];
}

function insertPostMeta(int $postId, string $key, string $value): void
{
    global $wpdb;
    if ($wpdb->insert($wpdb->postmeta, ['post_id' => $postId, 'meta_key' => $key, 'meta_value' => $value], ['%d', '%s', '%s']) !== 1) {
        throw new RuntimeException("Post {$postId} meta {$key} insert failed: {$wpdb->last_error}");
    }
}

function writePostMeta(int $postId, string $key, string $value): void
{
    global $wpdb;
    if (!metaExists($postId, $key)) {
        insertPostMeta($postId, $key, $value);
        return;
    }
    $updated = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE post_id = %d AND meta_key = %s AND meta_value = ''", $value, $postId, $key));
    if ($updated === false || (int) $wpdb->rows_affected !== 1) {
        throw new RuntimeException("Post {$postId} meta {$key} update failed: {$wpdb->last_error}");
    }
}

function applyPlan(array $plan, array &$summary): void
{
    global $wpdb;
    $wpdb->query('START TRANSACTION');
    try {
        foreach ($plan['meta'] as $item) {
            writePostMeta((int) $item['post_id'], (string) $item['key'], (string) $item['value']);
            $summary['db_writes']++;
            if ($item['acf_ref'] !== null && metaValue((int) $item['post_id'], '_' . (string) $item['key']) === '') {
                writePostMeta((int) $item['post_id'], '_' . (string) $item['key'], (string) $item['acf_ref']);
                $summary['db_writes']++;
            }
            insertPostMeta((int) $item['post_id'], META_FIX_BATCH, EXPECTED_BATCH);
            insertPostMeta((int) $item['post_id'], META_FIX_TARGET, (string) $item['key']);
            insertPostMeta((int) $item['post_id'], META_FIX_SHA, (string) $item['sha']);
            $summary['db_writes'] += 3;
        }
        foreach ($plan['terms'] as $item) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT object_id FROM {$wpdb->term_relationships} WHERE object_id = %d AND term_taxonomy_id = %d", (int) $item['post_id'], (int) $item['term_taxonomy_id']));
            if ($exists === null) {
                if ($wpdb->insert($wpdb->term_relationships, ['object_id' => (int) $item['post_id'], 'term_taxonomy_id' => (int) $item['term_taxonomy_id'], 'term_order' => 0], ['%d', '%d', '%d']) !== 1) {
                    throw new RuntimeException("Term relationship insert failed: {$wpdb->last_error}");
                }
                $summary['db_writes']++;
            }
        }
        $wpdb->query('COMMIT');
    } catch (Throwable $e) {
        $wpdb->query('ROLLBACK');
        throw $e;
    }
}

$args = array_slice($argv, 1);
$summary = ['ok' => false, 'mode' => hasFlag($args, '--apply') ? 'apply' : 'dry-run', 'task' => 'copywriting-field-placement-fix', 'batch' => EXPECTED_BATCH, 'counts' => ['detail_poli' => 0, 'detail_layanan' => 0, 'deskripsi' => 0, 'category_unggulan' => 0, 'category_penunjang' => 0, 'category_review' => 0], 'db_writes' => 0];

try {
    validateArgs($args);
    if (hasFlag($args, '--help')) {
        help();
        exit(0);
    }
    $root = repoRoot();
    $evidence = argValue($args, '--evidence') ?? '.sisyphus/evidence/fix-copywriting-field-placement-' . ($summary['mode'] === 'apply' ? 'apply' : 'dry-run') . '-' . gmdate('Ymd-His') . '.json';
    $backup = argValue($args, '--backup') ?? '.sisyphus/evidence/fix-copywriting-field-placement-backup-' . gmdate('Ymd-His') . '.json';
    $summary['evidence_path'] = resolvePath($evidence, $root);
    bootstrapWordPress(findWpLoad(argValue($args, '--wp-root')), $summary);

    $importPath = resolvePath(argValue($args, '--import-artifact') ?? DEFAULT_IMPORT_ARTIFACT, $root);
    $masterPath = resolvePath(argValue($args, '--master-artifact') ?? DEFAULT_MASTER_ARTIFACT, $root);
    $safe = validateImportArtifact(loadJsonFile($importPath), $importPath);
    $plan = buildPlan($safe, masterByPostId(loadJsonFile($masterPath)), scalarInt(argValue($args, '--unggulan-term-taxonomy-id'), '--unggulan-term-taxonomy-id'), scalarInt(argValue($args, '--penunjang-term-taxonomy-id'), '--penunjang-term-taxonomy-id'));

    foreach ($plan['meta'] as $item) {
        $summary['counts'][(string) $item['key']]++;
    }
    foreach ($plan['terms'] as $item) {
        $summary['counts'][(string) $item['bucket']]++;
    }
    $summary['counts']['category_review'] = count($plan['review']);
    $summary['plan'] = $plan;
    $summary['preflight'] = ['read_only' => $summary['mode'] !== 'apply', 'safe_ids' => array_column($safe, 'post_id'), 'import_artifact_sha256' => EXPECTED_IMPORT_SHA256];

    if ($summary['mode'] === 'apply') {
        $summary['backup'] = backupRows(array_column($safe, 'post_id'), resolvePath($backup, $root));
        applyPlan($plan, $summary);
    }
    $summary['ok'] = true;
    finish($summary);
} catch (Throwable $e) {
    fail($summary, $e->getMessage());
}
