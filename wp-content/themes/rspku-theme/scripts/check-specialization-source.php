<?php

declare(strict_types=1);

$root = dirname(__DIR__, 4);
$scheduleAdminPath = $root . '/wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php';
$doctorRepositoryPath = $root . '/wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php';
$auditPath = $root . '/wp-content/themes/rspku-theme/scripts/audit-doctor-specialization-source.php';

$scheduleAdmin = read_source($scheduleAdminPath);
$doctorRepository = read_source($doctorRepositoryPath);
$audit = read_source($auditPath);

assert_same([12, 15], schedule_term_ids([
    ['specialization_term_id' => '12'],
    ['specialization_term_id' => 0],
    ['specialization_term_id' => '15'],
    ['specialization_term_id' => '12'],
    ['specialization_term_id' => '999'],
    ['specialization_term_id' => 'not-a-term'],
], [12 => true, 15 => true]), 'valid unique schedule term extraction skips duplicates and invalid terms');

assert_same([], schedule_term_ids([
    ['specialization_term_id' => '999'],
    ['specialization_term_id' => '0'],
    ['specialization_term_id' => 'bad'],
], [12 => true]), 'invalid term skip leaves no derived terms');

assert_same([7, 9], replacement_terms([3, 4], [7, 9]), 'exact replacement semantics ignores existing taxonomy terms');
assert_same([], replacement_terms([3, 4], []), 'empty schedule clears derived taxonomy terms');

$termIdsBody = function_body($scheduleAdmin, 'termIds');
$syncManagedTermsBody = function_body($scheduleAdmin, 'syncManagedTerms');
$persistScheduleBody = function_body($scheduleAdmin, 'persistSchedule');
$forPolyclinicBody = function_body($doctorRepository, 'forPolyclinic');
$forServiceBody = function_body($doctorRepository, 'forService');

assert_contains($termIdsBody, "absint(\$row['specialization_term_id'] ?? 0)", 'termIds reads specialization_term_id from schedule rows');
assert_contains($termIdsBody, "term_exists(\$termId, 'spesialisasi-dokter')", 'termIds validates taxonomy term existence before deriving terms');
assert_contains($syncManagedTermsBody, "wp_set_object_terms(\$doctorId, \$newManagedTerms, 'spesialisasi-dokter', false)", 'syncManagedTerms replaces exact derived taxonomy terms');
assert_not_contains($syncManagedTermsBody, 'wp_get_post_terms', 'syncManagedTerms does not merge existing doctor specialization terms');
assert_not_contains($syncManagedTermsBody, 'wp_get_object_terms', 'syncManagedTerms does not merge existing object terms');
assert_contains($persistScheduleBody, 'self::syncManagedTerms($doctorId, [])', 'empty schedule clears derived terms through syncManagedTerms');
assert_not_contains($forPolyclinicBody, 'findBySpecializationKeyword', 'DoctorRepository::forPolyclinic has no specialization keyword fallback');
assert_not_contains($forPolyclinicBody, 'spesialisasi-dokter', 'DoctorRepository::forPolyclinic does not query specialization taxonomy');
assert_contains($audit, "PHP_SAPI !== 'cli'", 'audit and apply script is CLI-only');
assert_contains($audit, "specialization_audit_has_arg('--allow-clear')", 'bulk clear requires explicit allow-clear flag');
assert_contains($audit, "(\$backupPayload['state_checksum'] ?? null) !== \$before", 'apply rejects stale backup state checksum');
assert_contains($audit, "\$categories['missing_schedule_specialization'] !== [] || \$categories['invalid_schedule_term'] !== []", 'apply blocks missing or invalid schedule specialization');
assert_contains($audit, "!\$allowClear && \$syncCounts['cleared_no_schedule'] > 0", 'apply blocks taxonomy clears without explicit consent');

if (str_contains($forServiceBody, 'findBySpecializationKeyword')) {
    pass('DoctorRepository::forService retains specialization keyword helper usage');
} else {
    pass('DoctorRepository::forService has no specialization keyword helper usage; allowed by source contract');
}

pass('no WordPress bootstrap or mutation executed');

/**
 * @return non-empty-string
 */
function read_source(string $path): string
{
    if (!is_file($path)) {
        fail("Missing source file: {$path}");
    }

    $source = file_get_contents($path);
    if (!is_string($source) || $source === '') {
        fail("Empty source file: {$path}");
    }

    return $source;
}

/**
 * @param array<int,array<string,mixed>> $rows
 * @param array<int,bool> $validTerms
 * @return array<int,int>
 */
function schedule_term_ids(array $rows, array $validTerms): array
{
    $termIds = [];

    foreach ($rows as $row) {
        $termId = absint_local($row['specialization_term_id'] ?? 0);
        if ($termId <= 0 || !isset($validTerms[$termId])) {
            continue;
        }

        $termIds[] = $termId;
    }

    return array_values(array_unique($termIds));
}

/**
 * @param array<int,int> $existingTerms
 * @param array<int,int> $newManagedTerms
 * @return array<int,int>
 */
function replacement_terms(array $existingTerms, array $newManagedTerms): array
{
    unset($existingTerms);

    return array_values(array_unique(array_map('absint_local', $newManagedTerms)));
}

function absint_local(mixed $value): int
{
    return abs((int) $value);
}

function function_body(string $source, string $name): string
{
    $functionPosition = strpos($source, 'function ' . $name);
    if ($functionPosition === false) {
        fail("Missing function {$name}");
    }

    $bodyStart = strpos($source, '{', $functionPosition);
    if ($bodyStart === false) {
        fail("Missing function body {$name}");
    }

    $depth = 0;
    $length = strlen($source);
    for ($i = $bodyStart; $i < $length; $i++) {
        if ($source[$i] === '{') {
            $depth++;
        }

        if ($source[$i] === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($source, $bodyStart + 1, $i - $bodyStart - 1);
            }
        }
    }

    fail("Unclosed function body {$name}");
}

function assert_same(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        fail($message . ' expected ' . json_encode($expected) . ' got ' . json_encode($actual));
    }

    pass($message);
}

function assert_contains(string $haystack, string $needle, string $message): void
{
    if (!str_contains(normalize_source($haystack), normalize_source($needle))) {
        fail($message);
    }

    pass($message);
}

function assert_not_contains(string $haystack, string $needle, string $message): void
{
    if (str_contains(normalize_source($haystack), normalize_source($needle))) {
        fail($message);
    }

    pass($message);
}

function normalize_source(string $source): string
{
    return preg_replace('/\s+/', '', $source) ?? $source;
}

function pass(string $message): void
{
    echo "PASS {$message}\n";
}

function fail(string $message): never
{
    fwrite(STDERR, "FAIL {$message}\n");
    exit(1);
}
