# Rekonsiliasi Data Source 2026 vs WordPress

## TL;DR
> **Summary**: Sinkronkan data dokter dan layanan dengan source Profil 2026 memakai strategi **reconcile, bukan replace**. Source 2026 menjadi acuan canonical; WordPress existing tetap menjaga ID, slug, jadwal, foto, metadata, dan relasi.
> **Deliverables**: WP-CLI dry-run reconciler, approval file, draft-only apply flow, audit logs, UAT verification, rollback notes.
> **Effort**: Large
> **Parallel**: YES - 3 waves
> **Critical Path**: T1 approval schema → T2 parser/snapshot → T3 dry-run matcher → T4 human approval → T5 draft apply → T8 UAT verification

## Context
### Original Request
User: `Susun plannya`

Planning target: safe execution plan after audit matrix `.sisyphus/drafts/audit-source-2026-vs-website.md` showed source 2026 does not fully match current WordPress.

### Source of Truth
- Doctor source: `C:\Users\LENOVO\Downloads\data-dokter-rs-pku-muhammadiyah-yogyakarta(1).md`
- Service source: `C:\Users\LENOVO\Downloads\layanan-medis-rs-pku-muhammadiyah-yogyakarta.md`
- Audit report: `.sisyphus/drafts/audit-source-2026-vs-website.md`

### Existing WordPress Model
- CPTs: `dokter`, `layanan`, `poliklinik`, `rawat-inap`
- Taxonomies: `spesialisasi-dokter`, `kategori-layanan`, `jenis-konsultasi`
- Counts from audit:
  - Doctors: source 126, WP published 100, match 92, possible-match 2, missing 25, editorial-review 7
  - Services: source 104, WP combined 53, match 37, possible-match 4, missing 57, editorial-review 6

### Oracle Guidance
- Use an idempotent WP-CLI reconciliation script.
- Default mode must be dry-run.
- Apply requires explicit approval file.
- New records must be `draft`.
- Never delete records.
- Never change existing slugs unless explicitly approved.
- Preserve photos, schedules, operational copy, metadata, and relations unless a mapped field is approved.
- Add audit metadata for every touched record.

### Metis Review: Gaps Addressed
- Defined canonical parsing rules and identity keys.
- Defined approval file schema.
- Blocked automated apply for `editorial-review`.
- Defined orphan-existing as report-only.
- Defined parent/detail mapping for procedures/facilities.
- Defined taxonomy policy: create/assign only via approval; never delete terms.
- Defined slug collision and media/schedule policies.
- Added backup/rollback and UAT acceptance checks.

## Work Objectives
### Core Objective
Safely reconcile source 2026 doctor/service data into WordPress without breaking existing IDs, slugs, schedules, photos, routes, SEO, or operational metadata.

### Deliverables
- `.sisyphus/drafts/reconcile-source-2026-approvals.example.json`
- WP-CLI command/script for read-only dry-run and approved draft-only apply
- Generated snapshots:
  - `.sisyphus/evidence/source-2026-normalized.json`
  - `.sisyphus/evidence/wp-existing-snapshot.json`
  - `.sisyphus/evidence/reconcile-dry-run.json`
  - `.sisyphus/evidence/reconcile-apply-manifest.json`
- Draft posts for approved missing doctors/services only
- No deletion, no bulk replace, no forced publish
- UAT verification evidence

### Definition of Done
- Dry-run can be executed without changing WordPress.
- Dry-run output reproduces audit counts or explains differences.
- Apply refuses to run without approval file.
- Apply creates only approved missing records as `draft`.
- Apply does not delete posts/terms.
- Apply does not change existing post slugs by default.
- Existing `/dokter/`, `/jadwal-dokter/`, `/layanan/`, `/layanan/{slug}/`, `/poliklinik/`, `/layanan-medis/{term}/` still return HTTP 200.
- New draft records contain `_source_2026_key`, `_source_2026_hash`, `_reconcile_batch_id`, `_reconcile_classification`.
- Rollback instructions and batch manifest exist.

### Must Have
- Reconcile-not-replace strategy.
- Human approval gates for `possible-match` and `editorial-review`.
- Draft-first creation.
- Parent/detail decision for every missing service.
- Existing-only/orphan records reported only.
- UAT rollout before production.

### Must NOT Have
- No direct DB writes.
- No destructive bulk import.
- No deleting/unpublishing existing posts.
- No automatic slug change.
- No schedule/foto/media import.
- No taxonomy merge/delete.
- No production deploy in first pass.
- No commit unless user explicitly asks.

## Verification Strategy
> ZERO HUMAN INTERVENTION for technical checks. Human approval only for clinical/editorial decisions.
- Test decision: tests-after via CLI dry-run, PHP lint, WordPress route smoke, manifest review.
- QA policy: every implementation task has happy + failure scenario.
- Evidence path: `.sisyphus/evidence/task-{N}-{slug}.{ext}`

## Execution Strategy
### Parallel Execution Waves
Wave 1: T1 approval schema, T2 source/WP snapshot, T3 classifier dry-run
Wave 2: T4 human approval package, T5 draft apply, T6 taxonomy/detail mapping
Wave 3: T7 dashboard review support, T8 UAT verification, T9 rollback/handoff

### Dependency Matrix
| Task | Blocked By | Blocks |
| --- | --- | --- |
| T1 | none | T4, T5 |
| T2 | none | T3 |
| T3 | T1, T2 | T4 |
| T4 | T3 | T5, T6 |
| T5 | T4 | T7, T8 |
| T6 | T4 | T7, T8 |
| T7 | T5, T6 | T8 |
| T8 | T5, T6, T7 | T9 |
| T9 | T8 | final |

### Agent Dispatch Summary
| Wave | Tasks | Category |
| --- | --- | --- |
| 1 | T1-T3 | implementation + database-designer |
| 2 | T4-T6 | implementation + migration-architect |
| 3 | T7-T9 | testing + doc-writer |

## TODOs

- [x] T1. Define Approval Schema and Source Identity Keys

  **What to do**: Create approval schema file `.sisyphus/drafts/reconcile-source-2026-approvals.example.json`. Define stable source IDs:
  - Doctor key: `doctor::{normalized_name}::{source_group}`.
  - Service key: `service::{normalized_title}::{source_section}`.
  - Include `source_file`, `source_line`, `source_name`, `classification`, `decision`, `target_cpt`, `target_wp_id`, `parent_wp_id`, `canonical_title`, `approved_by`, `approved_at`, `reason`.
  - Allowed decisions: `keep`, `confirm-match`, `create-draft`, `add-child-detail`, `editorial-hold`, `orphan-review`, `skip`.

  **Must NOT do**: Do not approve any row automatically. Do not write WordPress data.

  **Recommended Agent Profile**:
  - Category: `implementation` - schema + validation logic.
  - Skills: `database-designer`, `migration-architect`.
  - Omitted: `frontend-ui-ux` - no UI work.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: T3, T4, T5 | Blocked By: none

  **References**:
  - Audit: `.sisyphus/drafts/audit-source-2026-vs-website.md`
  - Source doctor MD: `C:\Users\LENOVO\Downloads\data-dokter-rs-pku-muhammadiyah-yogyakarta(1).md`
  - Source layanan MD: `C:\Users\LENOVO\Downloads\layanan-medis-rs-pku-muhammadiyah-yogyakarta.md`

  **Acceptance Criteria**:
  - [x] JSON example has all required fields.
  - [x] Invalid decision values are documented as rejected.
  - [x] `editorial-review` rows require `decision=editorial-hold` or explicit approval; never default apply.

  **QA Scenarios**:
  ```
  Scenario: approval schema documents safe missing doctor creation
    Tool: Bash
    Steps: grep for `create-draft`, `doctor::`, `_source_2026_key` in approval example
    Expected: all strings found
    Evidence: .sisyphus/evidence/task-1-approval-schema.txt

  Scenario: invalid decision blocked by schema docs
    Tool: Bash
    Steps: grep for rejected decision guidance and confirm no `delete` decision exists
    Expected: `delete` not listed as allowed decision
    Evidence: .sisyphus/evidence/task-1-approval-schema-error.txt
  ```

  **Commit**: NO | Message: `docs(data): define reconciliation approval schema` | Files: `.sisyphus/drafts/reconcile-source-2026-approvals.example.json`

- [x] T2. Build Read-Only Source and WordPress Snapshots

  **What to do**: Implement read-only snapshot generation. Parse both Markdown sources and export normalized records with source line refs. Query WordPress through `wp-load.php` or WP-CLI if available for published `dokter`, `layanan`, `poliklinik`, `rawat-inap`, and terms. Save JSON evidence.

  **Must NOT do**: No WP writes. No direct DB mutation. Do not include DB credentials/secrets in output.

  **Recommended Agent Profile**:
  - Category: `implementation` - parser/snapshot script.
  - Skills: `database-designer`.
  - Omitted: `senior-frontend` - no frontend.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: T3 | Blocked By: none

  **References**:
  - CPT registration: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-post-types.php`
  - Taxonomy registration: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php`

  **Acceptance Criteria**:
  - [x] Source snapshot contains 126 doctor records.
  - [x] Source service snapshot contains 104 audited service rows.
  - [x] WP snapshot contains published counts: `dokter` 100, `layanan` 15, `poliklinik` 37, `rawat-inap` 1, unless DB changed; if changed, report delta.
  - [x] No `INSERT`, `UPDATE`, `DELETE`, `wp_update_post`, `wp_insert_post` in snapshot command.

  **QA Scenarios**:
  ```
  Scenario: read-only source snapshot generated
    Tool: Bash
    Steps: run snapshot command with read-only flag; inspect counts in `.sisyphus/evidence/source-2026-normalized.json`
    Expected: counts match audit or delta file exists
    Evidence: .sisyphus/evidence/task-2-source-snapshot.json

  Scenario: write function absent
    Tool: Bash
    Steps: grep snapshot script for write APIs
    Expected: no mutation API found
    Evidence: .sisyphus/evidence/task-2-no-write-grep.txt
  ```

  **Commit**: NO | Message: `feat(data): add read-only source snapshots` | Files: [script path, evidence]

- [x] T3. Generate Conservative Dry-Run Reconciliation

  **What to do**: Build classifier that outputs `match`, `possible-match`, `missing`, `editorial-review`, `orphan-existing`. Use conservative semantic rules. Never match cross-clinical services by substring only. Services must also classify granularity: `top-level`, `child-detail`, `procedure`, `facility`, `center`, `room`, `orphan-existing`.

  **Must NOT do**: No apply. No auto-fixing false matches.

  **Recommended Agent Profile**:
  - Category: `implementation` - matching logic.
  - Skills: `database-designer`, `migration-architect`.
  - Omitted: `performance-profiler` - data volume small.

  **Parallelization**: Can Parallel: NO | Wave 1 | Blocks: T4 | Blocked By: T1, T2

  **References**:
  - Final audit: `.sisyphus/drafts/audit-source-2026-vs-website.md`
  - Known false-match corrections in audit summary.

  **Acceptance Criteria**:
  - [x] Dry-run report contains no match of `Klinik Kesehatan Anak` to `Klinik Kecantikan Ayna`.
  - [x] Dry-run report contains no match of `Klinik Urologi` to `Klinik Patologi`.
  - [x] Dry-run report contains no match of `Klinik Bedah Kepala Leher` to `Klinik Bedah Syaraf`.
  - [x] Remaining possible service matches are only the 4 validated candidates unless new evidence is documented.

  **QA Scenarios**:
  ```
  Scenario: dry-run blocks known false matches
    Tool: Bash
    Steps: grep dry-run JSON for false pair strings
    Expected: false pairs absent
    Evidence: .sisyphus/evidence/task-3-false-match-check.txt

  Scenario: dry-run is non-mutating
    Tool: Bash
    Steps: record counts before and after dry-run using read-only WP snapshot
    Expected: counts unchanged
    Evidence: .sisyphus/evidence/task-3-dry-run-counts.json
  ```

  **Commit**: NO | Message: `feat(data): add conservative reconciliation dry-run` | Files: [script path, evidence]

- [x] T4. Prepare Human Approval Package

  **What to do**: Convert dry-run into review package. Pre-fill approval rows only for `missing`, `possible-match`, `editorial-review`, and `orphan-existing`. Mark all `editorial-review` as blocked. Add exact human decision questions for 2 doctor possible, 4 service possible, 7 doctor editorial, 6 service editorial, and 57 service missing granularity decisions.

  **Must NOT do**: Do not apply decisions. Do not hide ambiguous rows.

  **Recommended Agent Profile**:
  - Category: `writing` - review docs.
  - Skills: `migration-architect`.
  - Omitted: `frontend-ui-ux`.

  **Parallelization**: Can Parallel: NO | Wave 2 | Blocks: T5, T6 | Blocked By: T3

  **References**:
  - Approval schema from T1.
  - Dry-run output from T3.

  **Acceptance Criteria**:
  - [x] Approval package includes every non-match row.
  - [x] Every row has one allowed decision field.
  - [x] No `editorial-review` row defaults to apply.
  - [x] Every missing service has proposed `top-level` vs `child-detail` decision pending.

  **QA Scenarios**:
  ```
  Scenario: approval package complete
    Tool: Bash
    Steps: compare non-match count in dry-run vs approval rows
    Expected: row counts equal
    Evidence: .sisyphus/evidence/task-4-approval-counts.json

  Scenario: editorial rows blocked
    Tool: Bash
    Steps: grep approval file for `editorial-review` rows and decisions
    Expected: all editorial rows decision `editorial-hold` unless explicitly approved
    Evidence: .sisyphus/evidence/task-4-editorial-hold.txt
  ```

  **Commit**: NO | Message: `docs(data): prepare source reconciliation approvals` | Files: [approval package]

- [x] T5. Implement Approved Draft-Only Apply Command

  **What to do**: Create apply command that requires `--apply --approved-file=... --batch-id=...`. It may create new draft posts and safe metadata only for approved rows. It must never delete. It must preserve existing slugs unless approval explicitly has `allow_slug_change=true`. New slugs must be deterministic and collision-safe; if collision occurs, block and report, do not append random suffix.

  **Must NOT do**: No publish. No production. No schedule/media import. No taxonomy deletion.

  **Recommended Agent Profile**:
  - Category: `implementation` - WP-CLI mutation guarded.
  - Skills: `migration-architect`, `database-designer`.
  - Omitted: `frontend-ui-ux`.

  **Parallelization**: Can Parallel: NO | Wave 2 | Blocks: T7, T8 | Blocked By: T4

  **References**:
  - WordPress root: `C:\laragon\www\rspkudev`
  - CPTs: `dokter`, `layanan`, `poliklinik`, `rawat-inap`

  **Acceptance Criteria**:
  - [x] Command without `--apply` performs dry-run only.
  - [x] Command with missing approval file exits non-zero.
  - [x] New records are `draft`.
  - [x] Apply manifest lists every touched post ID, previous values, new values, and rollback note.
  - [x] Existing counts increase only by approved draft creates.

  **QA Scenarios**:
  ```
  Scenario: apply refuses missing approval
    Tool: Bash
    Steps: run apply command without approval file
    Expected: non-zero exit, no WP count changes
    Evidence: .sisyphus/evidence/task-5-missing-approval.txt

  Scenario: approved dry sample creates draft only
    Tool: Bash
    Steps: run against a tiny approved-file fixture in local/UAT-safe environment
    Expected: created post status `draft`, audit meta present
    Evidence: .sisyphus/evidence/task-5-draft-create.json
  ```

  **Commit**: NO | Message: `feat(data): add guarded source reconciliation apply` | Files: [script path]

- [x] T6. Map Taxonomy and Parent/Detail Policies

  **What to do**: Produce exact mapping table for service granularity:
  - Top-level `poliklinik`: clinical outpatient clinics.
  - Top-level `layanan`: hospital support services, centres, major service products.
  - Child/detail content: procedures, facilities, equipment, ambulance variants, lab/radiology test variants.
  - `rawat-inap`: room classes and facilities.
  Taxonomy creation/assignment only when approval row says `allow_term_create=true` or `allow_term_assign=true`. Never merge/delete terms in this phase.

  **Must NOT do**: No automatic term cleanup. No `spesialisasi-dokter` dedupe.

  **Recommended Agent Profile**:
  - Category: `implementation` - data mapping.
  - Skills: `database-designer`.
  - Omitted: `senior-security`.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: T7, T8 | Blocked By: T4

  **References**:
  - Service source MD.
  - Audit service matrix.
  - Existing taxonomy registration file.

  **Acceptance Criteria**:
  - [x] `CT Scan`, `Digital X-ray`, `ECG`, `Treadmill`, ambulance variants are not top-level by default.
  - [x] `Cancer Centre`, `Uronephrology Centre`, `Emergency and Critical Care` require explicit top-level approval.
  - [x] No taxonomy delete/merge operation exists.

  **QA Scenarios**:
  ```
  Scenario: procedures mapped as child-detail
    Tool: Bash
    Steps: inspect mapping for CT Scan, ECG, ESWL
    Expected: each classified child/detail unless explicitly approved otherwise
    Evidence: .sisyphus/evidence/task-6-child-detail-map.txt

  Scenario: taxonomy mutation guarded
    Tool: Bash
    Steps: grep apply script for term creation guards
    Expected: term creation requires approval flag; no term deletion API
    Evidence: .sisyphus/evidence/task-6-taxonomy-guards.txt
  ```

  **Commit**: NO | Message: `docs(data): define taxonomy and detail mapping` | Files: [mapping file]

- [x] T7. Dashboard Review and UAT Content QA

  **What to do**: After approved draft apply in UAT/local, review WordPress dashboard lists for created drafts. Verify draft records appear in correct CPT, preserve existing records, and do not pollute public archives until published. Check doctor search and layanan umbrella for no regressions.

  **Must NOT do**: Do not publish drafts automatically. Do not deploy production.

  **Recommended Agent Profile**:
  - Category: `testing` - QA.
  - Skills: `db-verifier`.
  - Omitted: `migration-architect` - already covered.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: T8 | Blocked By: T5, T6

  **References**:
  - Dashboard CPTs: `dokter`, `layanan`, `poliklinik`, `rawat-inap`
  - Routes: `/dokter/`, `/jadwal-dokter/`, `/layanan/`, `/poliklinik/`

  **Acceptance Criteria**:
  - [x] Draft counts equal approved creates.
  - [x] Existing published counts and slugs unchanged unless approved.
  - [x] Public archives do not show drafts.
  - [x] Dashboard can filter/search newly drafted records by title. [user accepted equivalent read-only batch/title query evidence because anonymous local wp-admin redirects to `/404/`]

  **QA Scenarios**:
  ```
  Scenario: drafts visible only in dashboard/read snapshot
    Tool: Bash
    Steps: query `post_status=draft` for batch ID; fetch public archive
    Expected: drafts in WP query; absent from public archive
    Evidence: .sisyphus/evidence/task-7-draft-visibility.json

  Scenario: existing URLs still live
    Tool: Bash
    Steps: HTTP smoke `/dokter/`, `/jadwal-dokter/`, `/layanan/`, `/layanan/ambulans/`, `/layanan-medis/layanan-penunjang/`
    Expected: all 200
    Evidence: .sisyphus/evidence/task-7-route-smoke.txt
  ```

  **Commit**: NO | Message: `test(data): verify reconciliation drafts` | Files: [evidence]

- [x] T8. Build, Route, and Regression Verification

  **What to do**: If any theme/admin UI source changes occurred, run `cd wp-content/themes/rspku-theme && npm run build`; otherwise skip build and record reason. Run PHP lint for added PHP scripts. Verify source-dry-run/apply commands. Verify no `public/build` committed. Verify old and new URL routes.

  **Must NOT do**: Do not commit build artifacts. Do not claim frontend done without build if frontend changed.

  **Recommended Agent Profile**:
  - Category: `testing` - final technical QA.
  - Skills: `db-verifier`.
  - Omitted: `frontend-ui-ux` unless UI changed.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: T9 | Blocked By: T5, T6, T7

  **References**:
  - `AGENTS.md` frontend build rule.
  - Existing UAT deploy note: server lacks npm; production plan must include local build fallback if frontend assets change.

  **Acceptance Criteria**:
  - [x] `php -l` passes for any PHP script touched.
  - [x] `npm run build` passes if theme frontend changed. [not applicable: no theme/admin frontend source changed]
  - [x] `git status --short -- wp-content/themes/rspku-theme/public/build` empty.
  - [x] Route smoke passes.

  **QA Scenarios**:
  ```
  Scenario: PHP scripts lint clean
    Tool: Bash
    Steps: run `php -l` on every added/changed PHP file
    Expected: no syntax errors
    Evidence: .sisyphus/evidence/task-8-php-lint.txt

  Scenario: build artifact not tracked
    Tool: Bash
    Steps: run git status for `public/build`
    Expected: no tracked changes
    Evidence: .sisyphus/evidence/task-8-build-status.txt
  ```

  **Commit**: NO | Message: `test(data): verify reconciliation routes` | Files: [evidence]

- [x] T9. Rollback and Production Handoff

  **What to do**: Create `.sisyphus/drafts/reconcile-source-2026-handoff.md` with batch ID, touched IDs, draft IDs, pre/post counts, manifest path, rollback steps, production preflight, and exact approval status. Rollback options: delete only new draft posts from batch, restore previous field values from manifest for approved updates, restore DB backup if broader issue. Production requires explicit user confirmation after UAT approval.

  **Must NOT do**: Do not perform rollback unless requested. Do not deploy production.

  **Recommended Agent Profile**:
  - Category: `writing` - operational handoff.
  - Skills: `migration-architect`.
  - Omitted: `frontend-ui-ux`.

  **Parallelization**: Can Parallel: NO | Wave 3 | Blocks: final | Blocked By: T8

  **References**:
  - Apply manifest from T5.
  - Evidence from T7/T8.

  **Acceptance Criteria**:
  - [x] Handoff lists every touched post ID and status.
  - [x] Handoff includes rollback commands/steps.
  - [x] Handoff states production not touched unless explicitly approved.
  - [x] Handoff states credentials/secrets must not be copied into docs.

  **QA Scenarios**:
  ```
  Scenario: handoff complete
    Tool: Bash
    Steps: grep handoff for batch ID, touched IDs, rollback, production gate
    Expected: all sections present
    Evidence: .sisyphus/evidence/task-9-handoff-check.txt

  Scenario: no secrets leaked
    Tool: Bash
    Steps: grep handoff for DB password/key patterns and known credential path content
    Expected: no secrets present
    Evidence: .sisyphus/evidence/task-9-secret-check.txt
  ```

  **Commit**: NO | Message: `docs(data): add reconciliation handoff` | Files: `.sisyphus/drafts/reconcile-source-2026-handoff.md`

## Final Verification Wave
> 4 review agents run in PARALLEL. ALL must APPROVE. Present consolidated results to user and get explicit `okay` before completing.
> Do NOT auto-proceed after verification. Wait for user's explicit approval before marking work complete.
- [x] F1. Plan Compliance Audit — oracle [dashboard UI blocker resolved by explicit user acceptance of equivalent read-only query evidence]
- [x] F2. Code Quality Review — unspecified-high
- [x] F3. Real Manual QA — unspecified-high
- [x] F4. Scope Fidelity Check — deep

## Commit Strategy
- No commit unless user explicitly requests.
- Suggested sequence if approved later:
  1. `feat(data): add source 2026 reconciliation dry-run`
  2. `feat(data): add guarded draft reconciliation apply`
  3. `docs(data): add source 2026 approval and handoff docs`
- Never commit `wp-content/themes/rspku-theme/public/build/`.

## Success Criteria
- Source 2026 can be reconciled safely without destructive replacement.
- Dashboard receives only reviewed draft additions/updates.
- Existing public URLs and relationships survive.
- Human decisions are explicit and auditable.
- UAT proves no route/search/service regressions before production.
