# Konsolidasi Spesialisasi Dokter dari Jadwal Dokter

## TL;DR
> **Summary**: Jadikan slot `Jadwal Dokter.specialization_term_id` sebagai satu-satunya sumber Spesialisasi Dokter. Taxonomy `spesialisasi-dokter` menjadi data turunan untuk filter/tampilan dokter; Poliklinik tetap CPT terpisah dan hanya punya relasi dokter eksplisit.
> **Deliverables**:
> - Audit mismatch spesialisasi sebelum mutasi data.
> - Sinkronisasi taxonomy dokter dari jadwal dengan replace total, bukan merge curated terms.
> - UI/admin guardrail agar spesialisasi dokter dibaca sebagai read-only dari Jadwal Dokter.
> - Penghapusan fallback Poliklinik berbasis keyword spesialisasi.
> - Verifikasi agent-executed + evidence per task.
> **Effort**: Medium
> **Parallel**: YES - 3 waves
> **Critical Path**: Task 1 -> Task 2 -> Task 4 -> Final Verification Wave

## Context

### Original Request
User bertanya kenapa Spesialisasi berbeda-beda, dengan ekspektasi: “Source of Truth Spesialisasi itu adalah dari jadwal dokter.” User setuju rekomendasi: Jadwal Dokter menjadi sumber resmi; Poliklinik tidak disinkronkan sebagai Spesialisasi.

### Interview Summary
- Spesialisasi Dokter harus berasal dari slot jadwal dokter.
- Taxonomy `spesialisasi-dokter` boleh tetap dipakai sebagai derived data untuk query, filter, badge, archive.
- Poliklinik adalah CPT layanan/unit, bukan sinonim Spesialisasi.
- Relasi Dokter-Poliklinik harus eksplisit/manual, bukan hasil cocok nama.
- Audit data harus dilakukan sebelum rewrite/migrasi.

### Research Findings
- `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php:25` register taxonomy `spesialisasi-dokter` untuk CPT `dokter`.
- `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-post-types.php:45` register CPT `poliklinik` terpisah.
- `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule.php:14` canonical meta `_rspku_doctor_schedule`; `:111` row field `specialization_term_id`.
- `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php:325` `persistSchedule()` menyimpan jadwal; `:356` `syncManagedTerms()` saat ini merge `curatedTerms + newManagedTerms`.
- `wp-content/themes/rspku-theme/app/Repositories/DoctorScheduleRepository.php:214` public schedule records dibangun dari dokter, taxonomy, schedule meta; `:272` membaca `_rspku_doctor_schedule`/`jadwal_praktek` dan mapping `specialization_term_id` ke term.
- `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php:37` query dokter filter taxonomy; `:133` `forPolyclinic()` direct relation lalu fallback keyword; `:443` `buildNormalized()` membaca `wp_get_post_terms($postId, 'spesialisasi-dokter')`.
- `wp-content/themes/rspku-theme/app/Repositories/ContentRepository.php:274` Poliklinik dibaca dari CPT `poliklinik`.
- `wp-content/themes/rspku-theme/app/Services/DoctorDirectorySync.php:15-22` legacy sync inactive; jangan jadikan dasar behavior baru.

### Metis Review (gaps addressed)
- Audit wajib sebelum data rewrite.
- Jangan bergantung pada `DoctorDirectorySync.php` karena inactive.
- Jangan infer spesialisasi dari nama term, slug, Poliklinik, dokter, bio, atau keyword.
- Tentukan perilaku dokter tanpa jadwal: terms dari jadwal menjadi kosong; data lama dicatat di audit dulu.
- Tambah dry-run/backup evidence sebelum mutasi.
- Validasi `specialization_term_id` dengan taxonomy `spesialisasi-dokter`.
- Hindari scope creep: tidak membuat taxonomy/CPT baru, tidak re-enable legacy sync, tidak membuat auto-matching Poliklinik.

## Work Objectives

### Core Objective
Membuat Spesialisasi Dokter konsisten: satu input resmi dari `Jadwal Dokter.specialization_term_id`, satu derived query surface via taxonomy `spesialisasi-dokter`, tanpa fallback Poliklinik/keyword.

### Deliverables
- Audit mismatch read-only.
- Sinkronisasi taxonomy dokter dari jadwal dengan replace total.
- Admin guardrail/read-only messaging.
- Removal of Poliklinik keyword fallback.
- Regression/self-checks untuk audit, sync, query, public display.

### Definition of Done (verifiable conditions with commands)
- `php -l wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php` passes.
- `php -l wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-fields.php` passes if touched.
- `php -l wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php` passes.
- `php -l wp-content/themes/rspku-theme/scripts/audit-doctor-specialization-source.php` passes if script added.
- Audit command/action produces evidence file `.sisyphus/evidence/task-1-specialization-audit.*` with counts for mismatch classes.
- Saving a schedule rewrites `spesialisasi-dokter` terms to exactly the set of non-empty valid `specialization_term_id` in `_rspku_doctor_schedule`.
- Doctor with empty schedule has no schedule-derived `spesialisasi-dokter` terms after save/sync.
- Poliklinik related doctors no longer use title/specialization keyword fallback.
- Jadwal Dokter page still renders specialization filters from schedule/taxonomy.

### Must Have
- No data mutation before audit evidence exists.
- Dry-run mode for any bulk sync/migration.
- Backup/export of pre-change doctor taxonomy assignments before bulk mutation.
- Explicit handling for missing/invalid `specialization_term_id`.
- Agent-executed QA for every implementation task.

### Must NOT Have
- Do not make Poliklinik source of Spesialisasi.
- Do not use keyword/title matching as source of truth.
- Do not re-enable `DoctorDirectorySync.php`.
- Do not add new taxonomy/CPT.
- Do not silently delete curated terms without audit evidence.
- Do not preserve curated `spesialisasi-dokter` terms after final source-of-truth rule is active.

## Verification Strategy
> ZERO HUMAN INTERVENTION - all verification is agent-executed.
- Test decision: tests-after + small PHP self-check/WP-CLI/admin dry-run scripts where project infra allows.
- QA policy: Every task has agent-executed scenarios.
- Evidence: `.sisyphus/evidence/task-{N}-{slug}.{ext}`.

## Execution Strategy

### Parallel Execution Waves
> Target: 5-8 tasks per wave. <3 per wave (except final) = under-splitting.

Wave 1: Task 1 audit/backup, Task 2 schedule sync design helper, Task 3 Poliklinik relation cleanup can start after code references verified.
Wave 2: Task 4 apply replace-total sync, Task 5 admin UI guardrail, Task 6 audit/migration dry-run command/page.
Wave 3: Task 7 regression checks, Task 8 public/admin QA pass.

### Dependency Matrix (full, all tasks)
- Task 1 blocks Task 4 and Task 6.
- Task 2 blocks Task 4.
- Task 3 independent after confirming no hidden dependency on fallback.
- Task 4 blocks Task 7 and Task 8.
- Task 5 blocks Task 8.
- Task 6 blocks Task 7 and Task 8.
- Task 7 blocks final verification.
- Task 8 blocks final verification.

### Agent Dispatch Summary
- Wave 1: 3 tasks → implementation, quick, implementation.
- Wave 2: 3 tasks → implementation, quick, implementation.
- Wave 3: 2 tasks → testing, testing.

## TODOs
> Implementation + Test = ONE task. Never separate.
> EVERY task MUST have: Agent Profile + Parallelization + QA Scenarios.

- [x] 1. Build Read-Only Specialization Audit + Backup Export

  **What to do**: Add the smallest read-only audit surface, preferably one PHP script under `wp-content/themes/rspku-theme/scripts/audit-doctor-specialization-source.php` if project script pattern supports it. It must load WordPress, iterate published and non-trashed `dokter` posts, read `_rspku_doctor_schedule`, collect valid `specialization_term_id` values, read current `spesialisasi-dokter` term IDs, report mismatches, rows missing specialization, invalid term IDs, doctors with taxonomy but no schedule, doctors with schedule but no taxonomy, Poliklinik relation presence via `pilih_poliklinik_dokter`/`_rspku_related_polyclinic`. Export JSON to `.sisyphus/evidence/task-1-specialization-audit.json` and CSV/JSON backup of current term assignments to `.sisyphus/evidence/task-1-specialization-backup.json`. Dry-run only; no `wp_set_object_terms`, `update_post_meta`, `delete_post_meta`.
  **Must NOT do**: Do not mutate posts, terms, meta, caches, or options. Do not infer from Poliklinik title. Do not use `DoctorDirectorySync.php`.

  **Recommended Agent Profile**:
  - Category: `implementation` - Needs focused PHP script with WordPress bootstrap safety.
  - Skills: [] - Existing patterns enough.
  - Omitted: [`database-designer`] - No schema changes.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [4, 6] | Blocked By: []

  **References**:
  - Pattern: `wp-content/themes/rspku-theme/scripts/audit-native-doctor-schedule-import.php` - Follow script bootstrap/output conventions if present.
  - API/Type: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule.php:14` - `_rspku_doctor_schedule` meta contract.
  - API/Type: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule.php:111` - `specialization_term_id` row field.
  - Pattern: `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php:443` - Current doctor specialization reads from taxonomy.
  - Pattern: `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php:133` - Current Poliklinik relation meta keys.

  **Acceptance Criteria**:
  - [ ] `php -l wp-content/themes/rspku-theme/scripts/audit-doctor-specialization-source.php` exits 0.
  - [ ] Running the audit in dry-run mode creates `.sisyphus/evidence/task-1-specialization-audit.json`.
  - [ ] Audit JSON includes counts and per-doctor IDs for `missing_schedule_specialization`, `invalid_schedule_term`, `taxonomy_without_schedule`, `schedule_without_taxonomy`, `taxonomy_differs_from_schedule`, `polyclinic_relation_missing`.
  - [ ] Backup JSON includes each doctor ID, title, current `spesialisasi-dokter` term IDs/names before mutation.
  - [ ] Grep/read confirms script contains no `wp_set_object_terms`, `update_post_meta`, `delete_post_meta`, `wp_insert_term`, `wp_delete_term`.

  **QA Scenarios**:
  ```
  Scenario: Audit dry-run produces mismatch report
    Tool: Bash
    Steps: Run `php wp-content/themes/rspku-theme/scripts/audit-doctor-specialization-source.php --dry-run --output=.sisyphus/evidence/task-1-specialization-audit.json --backup=.sisyphus/evidence/task-1-specialization-backup.json`
    Expected: Exit 0; both files exist; audit JSON has top-level `summary` and `doctors` keys; no database mutation warning appears.
    Evidence: .sisyphus/evidence/task-1-specialization-audit.json

  Scenario: Audit blocks mutation primitives
    Tool: Bash
    Steps: Run text search for mutation functions in `wp-content/themes/rspku-theme/scripts/audit-doctor-specialization-source.php`
    Expected: No matches for `wp_set_object_terms`, `update_post_meta`, `delete_post_meta`, `wp_insert_term`, `wp_delete_term`.
    Evidence: .sisyphus/evidence/task-1-audit-no-mutation.txt
  ```

  **Commit**: YES | Message: `feat(dokter): add specialization audit` | Files: [`wp-content/themes/rspku-theme/scripts/audit-doctor-specialization-source.php`, `.sisyphus/evidence/*`]

- [x] 2. Extract Schedule Term Set Helper

  **What to do**: In `RSPKU_CPT_DoctorScheduleAdmin`, create a minimal private helper that derives valid specialization term IDs from schedule rows. It should collect unique positive `specialization_term_id`, validate each via `term_exists($termId, 'spesialisasi-dokter')` or `get_term($termId, 'spesialisasi-dokter')`, ignore invalid IDs for sync, and allow caller to report invalid rows if needed. Keep helper private/static unless reused by script is impossible; no new class unless necessary.
  **Must NOT do**: Do not add abstraction layer, service container, or new dependency. Do not infer terms from label/name.

  **Recommended Agent Profile**:
  - Category: `quick` - Small local PHP refactor.
  - Skills: [] - No special skill needed.
  - Omitted: [`senior-architect`] - Not architectural.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [4] | Blocked By: []

  **References**:
  - Pattern: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php:348` - Current `termIds()` helper.
  - API/Type: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule.php:128` - Existing term validation in row normalization.

  **Acceptance Criteria**:
  - [ ] `termIds()` or replacement returns only valid unique term IDs from `specialization_term_id`.
  - [ ] Invalid/deleted term IDs never get passed to `wp_set_object_terms()`.
  - [ ] `php -l wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php` exits 0.

  **QA Scenarios**:
  ```
  Scenario: Valid schedule terms retained
    Tool: Bash
    Steps: Run `php -l wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php`; inspect helper with grep/read.
    Expected: Syntax OK; helper uses `specialization_term_id`; validation references `spesialisasi-dokter`.
    Evidence: .sisyphus/evidence/task-2-helper-valid.txt

  Scenario: Invalid term IDs ignored
    Tool: Bash
    Steps: Run a small WP bootstrap check or add an assert-based script that passes rows with `specialization_term_id` 0 and a deleted/nonexistent ID.
    Expected: Result excludes 0 and nonexistent IDs; no fatal error.
    Evidence: .sisyphus/evidence/task-2-helper-invalid.txt
  ```

  **Commit**: YES | Message: `refactor(dokter): derive schedule terms safely` | Files: [`wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php`]

- [x] 3. Remove Poliklinik Keyword Fallback

  **What to do**: Update `DoctorRepository::forPolyclinic()` so related doctors come only from explicit meta relation: `pilih_poliklinik_dokter` and `_rspku_related_polyclinic`. Remove Strategy 2 fallback that calls `findBySpecializationKeyword($polyclinicName, ...)` for Poliklinik. Keep `forService()` unchanged unless tests prove shared helper removal would break it.
  **Must NOT do**: Do not remove explicit meta relation. Do not change CPT `poliklinik`. Do not change public Poliklinik templates beyond resulting doctor list behavior.

  **Recommended Agent Profile**:
  - Category: `implementation` - Behavior change in repository with regression risk.
  - Skills: [] - Existing code enough.
  - Omitted: [`frontend-ui-ux`] - No UI redesign.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [8] | Blocked By: []

  **References**:
  - Pattern: `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php:133` - `forPolyclinic()` current behavior.
  - Pattern: `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php:107` - `polyclinic_doctors` context.
  - View: `wp-content/themes/rspku-theme/resources/views/pages/single-poliklinik.twig:95` - Related doctors panel display.

  **Acceptance Criteria**:
  - [ ] `forPolyclinic()` no longer calls `findBySpecializationKeyword()`.
  - [ ] `forPolyclinic()` still filters `_rspku_synced_from_schedule = 1` and explicit Poliklinik relation meta.
  - [ ] `forService()` behavior unchanged.
  - [ ] `php -l wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php` exits 0.

  **QA Scenarios**:
  ```
  Scenario: Explicit Poliklinik relation still returns doctors
    Tool: Bash
    Steps: Run targeted WP bootstrap check: create/read existing doctor with `pilih_poliklinik_dokter` or `_rspku_related_polyclinic`; call `DoctorRepository::forPolyclinic($id, 4)`.
    Expected: Related doctor appears only when explicit meta relation exists.
    Evidence: .sisyphus/evidence/task-3-polyclinic-explicit.txt

  Scenario: Keyword-only match no longer returns doctors
    Tool: Bash
    Steps: Use a Poliklinik title matching a specialization but with no explicit doctor meta relation; call `DoctorRepository::forPolyclinic($id, 4)`.
    Expected: No doctors returned from keyword/title match alone.
    Evidence: .sisyphus/evidence/task-3-polyclinic-no-keyword.txt
  ```

  **Commit**: YES | Message: `fix(poliklinik): require explicit doctor relation` | Files: [`wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php`]

- [x] 4. Replace Dokter Specialization Taxonomy from Schedule on Save

  **What to do**: Change `syncManagedTerms()` flow so saving Jadwal Dokter sets `spesialisasi-dokter` terms to exactly the valid unique term IDs from current schedule rows. If rows are empty or no valid `specialization_term_id`, set empty terms for `spesialisasi-dokter`. Remove `curatedTerms` merge from the active path. Keep `_rspku_schedule_managed_specializations` only as optional debug/meta if useful; it must no longer protect curated terms from replacement. Flush doctor caches with correct namespace/class check; current class_exists string may need verification because namespace in `DoctorRepository.php` is `Rspku\Repositories`.
  **Must NOT do**: Do not preserve manually assigned `spesialisasi-dokter` terms. Do not touch other taxonomies. Do not mutate Poliklinik relation meta.

  **Recommended Agent Profile**:
  - Category: `implementation` - Core behavior change with data consistency risk.
  - Skills: [] - Local WordPress/PHP change.
  - Omitted: [`migration-architect`] - No zero-downtime infra migration; audit/backup enough.

  **Parallelization**: Can Parallel: NO | Wave 2 | Blocks: [7, 8] | Blocked By: [1, 2]

  **References**:
  - Pattern: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php:325` - Schedule persist entry point.
  - Current behavior: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php:356` - Merge curated + managed terms; replace this.
  - Cache: `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php:424` - Flush doctor normalized cache.
  - Display: `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php:454` - Doctor display reads taxonomy.

  **Acceptance Criteria**:
  - [ ] Saving schedule with terms `[A, B]` results in doctor taxonomy exactly `[A, B]`.
  - [ ] Saving schedule after removing term `B` results in doctor taxonomy exactly `[A]`.
  - [ ] Saving empty schedule results in no `spesialisasi-dokter` terms for that doctor.
  - [ ] Existing manually curated `spesialisasi-dokter` terms are listed in audit backup before removal, not preserved silently.
  - [ ] `php -l wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php` exits 0.

  **QA Scenarios**:
  ```
  Scenario: Schedule save replaces taxonomy exactly
    Tool: Bash
    Steps: In a WP bootstrap check, pick test doctor, assign manual extra `spesialisasi-dokter` term, persist schedule rows with one valid `specialization_term_id`, then read `wp_get_post_terms()`.
    Expected: Only schedule term remains; manual extra term removed.
    Evidence: .sisyphus/evidence/task-4-replace-taxonomy.txt

  Scenario: Empty schedule clears specialization terms
    Tool: Bash
    Steps: Persist empty schedule for test doctor, then read `wp_get_post_terms($doctorId, 'spesialisasi-dokter')`.
    Expected: Empty term list; no unrelated taxonomy touched.
    Evidence: .sisyphus/evidence/task-4-empty-clears.txt
  ```

  **Commit**: YES | Message: `fix(dokter): sync specialization from schedule` | Files: [`wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php`]

- [x] 5. Make Dokter Admin Specialization Read-Only by Policy

  **What to do**: Adjust admin messaging in `RSPKU_CPT_DoctorFields::renderMetaBox()` so synced doctor profiles clearly state nama/spesialisasi/jadwal are managed from Jadwal Dokter. If there is any editable taxonomy panel for `spesialisasi-dokter` visible on Dokter edit screen, add the minimal WordPress admin hook/CSS/capability adjustment needed to prevent casual manual editing from that screen, or add a warning if hiding is too risky. The accepted minimum: visible read-only notice + no new manual specialization field in doctor metabox.
  **Must NOT do**: Do not remove taxonomy registration globally. Do not block technical admins from managing terms list if needed for schedule dropdown. Do not redesign admin.

  **Recommended Agent Profile**:
  - Category: `quick` - Small admin UX guardrail.
  - Skills: [] - WordPress hooks enough.
  - Omitted: [`ui-ux-pro-max`] - No visual redesign.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [8] | Blocked By: []

  **References**:
  - Pattern: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-fields.php:46` - Synced profile detection.
  - Pattern: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-fields.php:51` - Existing notice about schedule source.
  - Taxonomy: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php:25` - Taxonomy still needed.

  **Acceptance Criteria**:
  - [ ] Dokter edit screen for synced doctors states “Spesialisasi dikelola dari Jadwal Dokter” or equivalent.
  - [ ] No editable specialization input is introduced in `Detail Dokter` metabox.
  - [ ] If taxonomy panel remains, warning explains manual changes are overwritten by schedule save.
  - [ ] `php -l wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-fields.php` exits 0 if touched.

  **QA Scenarios**:
  ```
  Scenario: Synced doctor admin shows source warning
    Tool: Playwright
    Steps: Log into wp-admin, open a synced Dokter edit page, inspect `Detail Dokter` metabox.
    Expected: Notice says specialization/name/schedule should be managed from Jadwal Dokter; no conflicting editable specialization field in metabox.
    Evidence: .sisyphus/evidence/task-5-admin-warning.png

  Scenario: Term management remains available for schedule dropdown
    Tool: Playwright
    Steps: Open `edit-tags.php?taxonomy=spesialisasi-dokter&post_type=dokter` as admin.
    Expected: Admin can still manage terms needed by Jadwal Dokter dropdown, unless capability intentionally restricted and documented.
    Evidence: .sisyphus/evidence/task-5-term-management.png
  ```

  **Commit**: YES | Message: `chore(dokter): clarify specialization ownership` | Files: [`wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-fields.php`]

- [x] 6. Add Guarded Bulk Sync / Audit Action

  **What to do**: Provide one guarded way to apply schedule-to-taxonomy sync across existing doctors after audit. Cheapest acceptable implementation: extend audit script with `--apply` guarded by required `--backup=<path>` existing file and default `--dry-run`, or add a small admin action under Dokter only if script pattern unsuitable. Apply mode must rewrite `spesialisasi-dokter` terms exactly from schedule-derived term IDs for each doctor. It must skip invalid term IDs and report skipped rows. It must write evidence `.sisyphus/evidence/task-6-specialization-sync-apply.json`.
  **Must NOT do**: Do not run apply automatically. Do not require web UI if script is enough. Do not delete terms from taxonomy catalog; only object term assignments change.

  **Recommended Agent Profile**:
  - Category: `implementation` - Guarded data mutation tool with rollback evidence.
  - Skills: [] - Existing PHP/WP APIs enough.
  - Omitted: [`migration-architect`] - Focused batch tool, not migration framework.

  **Parallelization**: Can Parallel: NO | Wave 2 | Blocks: [7, 8] | Blocked By: [1]

  **References**:
  - Pattern: `wp-content/themes/rspku-theme/scripts/import-native-doctor-schedule.php` - Existing import script convention if present.
  - Pattern: `wp-content/themes/rspku-theme/scripts/restore-native-doctor-schedule-snapshot.php` - Restore/snapshot convention if present.
  - API: `wp_set_object_terms($doctorId, $termIds, 'spesialisasi-dokter', false)` - Only mutation allowed for specialization assignment.

  **Acceptance Criteria**:
  - [ ] Default run is dry-run and mutates nothing.
  - [ ] Apply mode requires explicit `--apply` and existing backup path argument.
  - [ ] Apply report includes changed, unchanged, skipped_invalid_term, cleared_no_schedule counts.
  - [ ] Apply mode touches only `spesialisasi-dokter` object terms for `dokter` posts.
  - [ ] `php -l` passes for touched script/file.

  **QA Scenarios**:
  ```
  Scenario: Bulk sync dry-run safe by default
    Tool: Bash
    Steps: Run sync script without `--apply`; compare a sampled doctor's `spesialisasi-dokter` terms before/after.
    Expected: No term assignment changes; report marks mode `dry-run`.
    Evidence: .sisyphus/evidence/task-6-dry-run-safe.json

  Scenario: Bulk sync apply rewrites exact terms with backup
    Tool: Bash
    Steps: Run sync script with `--apply --backup=.sisyphus/evidence/task-1-specialization-backup.json --output=.sisyphus/evidence/task-6-specialization-sync-apply.json` on controlled test/local data.
    Expected: Changed doctors have taxonomy exactly equal to schedule term IDs; report lists changes.
    Evidence: .sisyphus/evidence/task-6-specialization-sync-apply.json
  ```

  **Commit**: YES | Message: `feat(dokter): add guarded specialization sync` | Files: [`wp-content/themes/rspku-theme/scripts/audit-doctor-specialization-source.php`]

- [x] 7. Add Targeted Regression Checks

  **What to do**: Add minimal runnable checks without introducing a new test framework. Prefer assert-based PHP script under `wp-content/themes/rspku-theme/scripts/check-specialization-source.php` or extend existing audit script with `--self-check`. Cover schedule row term extraction, exact taxonomy replacement semantics, invalid term skip, Poliklinik no-keyword fallback. Ensure checks are safe on local/dev data, either by using temporary test posts/terms and cleaning them up, or by dry-run stubs if bootstrap constraints require.
  **Must NOT do**: Do not add PHPUnit/Pest setup unless already configured and cheaper. Do not test broad WordPress internals.

  **Recommended Agent Profile**:
  - Category: `testing` - Focused regression harness.
  - Skills: [] - Minimal self-check preferred.
  - Omitted: [`laravel-tdd`] - Not Laravel.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: [Final] | Blocked By: [4, 6]

  **References**:
  - Behavior: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php:325` - Schedule save/sync.
  - Behavior: `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php:133` - Poliklinik relation.
  - Script pattern: `wp-content/themes/rspku-theme/scripts/*.php`.

  **Acceptance Criteria**:
  - [ ] `php -l wp-content/themes/rspku-theme/scripts/check-specialization-source.php` exits 0 if added.
  - [ ] Check command exits 0 on passing state and non-zero on failed assertion.
  - [ ] Checks include exact replacement, empty schedule clear, invalid term skip, no Poliklinik keyword fallback.
  - [ ] Evidence output saved to `.sisyphus/evidence/task-7-regression-checks.txt`.

  **QA Scenarios**:
  ```
  Scenario: Regression checks pass
    Tool: Bash
    Steps: Run `php wp-content/themes/rspku-theme/scripts/check-specialization-source.php > .sisyphus/evidence/task-7-regression-checks.txt`
    Expected: Exit 0; output lists all checks PASS.
    Evidence: .sisyphus/evidence/task-7-regression-checks.txt

  Scenario: Syntax checks pass
    Tool: Bash
    Steps: Run `php -l` on every touched PHP file.
    Expected: Every command prints `No syntax errors detected`.
    Evidence: .sisyphus/evidence/task-7-php-lint.txt
  ```

  **Commit**: YES | Message: `test(dokter): guard specialization source` | Files: [`wp-content/themes/rspku-theme/scripts/check-specialization-source.php`, `.sisyphus/evidence/*`]

- [ ] 8. Execute Public/Admin QA Pass

  **What to do**: Run agent-executed QA against admin and public surfaces: Jadwal Dokter admin save, Dokter archive filter, single Dokter badge, Jadwal Dokter page filter, single Poliklinik related doctors. Use Playwright if browser available; otherwise WP bootstrap/render checks plus screenshots where possible. Capture evidence files.
  **Must NOT do**: Do not rely on human visual confirmation. Do not mark QA done without evidence.

  **Recommended Agent Profile**:
  - Category: `testing` - Browser + repository behavior QA.
  - Skills: [`playwright`] - Browser verification if UI accessible.
  - Omitted: [`full-page-screenshot`] - Targeted screenshots enough.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: [Final] | Blocked By: [3, 4, 5, 6]

  **References**:
  - Admin: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-schedule-admin.php:31` - Jadwal Dokter admin page.
  - Public schedule: `wp-content/themes/rspku-theme/resources/views/pages/page-jadwal-dokter.twig:47` - specialization filter.
  - Single doctor: `wp-content/themes/rspku-theme/resources/views/pages/single-doctor.twig:30` - specialization badge.
  - Single poliklinik: `wp-content/themes/rspku-theme/resources/views/pages/single-poliklinik.twig:95` - related doctors panel.

  **Acceptance Criteria**:
  - [ ] Jadwal Dokter admin can save a row with specialization term; success notice appears.
  - [ ] After save, Dokter single badge shows saved specialization.
  - [ ] Dokter archive filter by specialization returns that doctor.
  - [ ] Jadwal Dokter public page filter by specialization returns matching row.
  - [ ] Single Poliklinik related doctors shows only explicitly related doctors; keyword-only matches absent.

  **QA Scenarios**:
  ```
  Scenario: Schedule-driven specialization visible publicly
    Tool: Playwright
    Steps: Save schedule specialization for a test doctor in wp-admin; open that doctor's public page; open `/dokter/` with specialization filter; open `/jadwal-dokter/` and select same specialization.
    Expected: Doctor badge and both filters reflect schedule specialization consistently.
    Evidence: .sisyphus/evidence/task-8-public-specialization.png

  Scenario: Poliklinik does not auto-match by keyword
    Tool: Playwright
    Steps: Open a Poliklinik page whose title resembles a specialization but has no explicit related doctor meta.
    Expected: Related doctor panel does not show keyword-only doctors; no fatal/template error.
    Evidence: .sisyphus/evidence/task-8-polyclinic-explicit-only.png
  ```

  **Commit**: YES | Message: `test(dokter): verify specialization UX` | Files: [`.sisyphus/evidence/*`]

## Final Verification Wave (MANDATORY — after ALL implementation tasks)
> 4 review agents run in PARALLEL. ALL must APPROVE. Present consolidated results to user and get explicit "okay" before completing.
> **Do NOT auto-proceed after verification. Wait for user's explicit approval before marking work complete.**
> **Never mark F1-F4 as checked before getting user's okay.** Rejection or user feedback -> fix -> re-run -> present again -> wait for okay.
- [ ] F1. Plan Compliance Audit — oracle
- [ ] F2. Code Quality Review — unspecified-high
- [ ] F3. Real Manual QA — unspecified-high (+ playwright if UI)
- [ ] F4. Scope Fidelity Check — deep

## Commit Strategy
- Commit per task if repo policy allows; keep audit/evidence commits separate from behavior commits.
- Do not commit `.sisyphus/evidence/*` unless project convention wants execution evidence in git. If not, keep evidence local and commit only code/scripts.
- Suggested order:
  1. `feat(dokter): add specialization audit`
  2. `fix(dokter): sync specialization from schedule`
  3. `fix(poliklinik): require explicit doctor relation`
  4. `chore(dokter): clarify specialization ownership`
  5. `test(dokter): guard specialization source`

## Success Criteria
- Spesialisasi visible di Dokter, Jadwal Dokter, dan filter berasal dari schedule term IDs.
- Poliklinik tidak lagi mempengaruhi spesialisasi dokter.
- Manual/curated taxonomy terms tidak menjadi sumber ganda setelah schedule save/bulk sync.
- Audit report tersedia sebelum mutation.
- Dry-run tersedia untuk bulk sync.
- Public pages tetap render tanpa fatal error.
- Final four-agent verification approved and user explicitly says okay.
