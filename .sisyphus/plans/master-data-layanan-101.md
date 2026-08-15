# Master Data Layanan 101 Reconciliation Plan

Status: Approved for planning only  
Owner: Sisyphus  
Date: 2026-08-15  
Scope: Local implementation plan only. No implementation code, DB writes, deploy, commit, push, publish, delete, slug change, or taxonomy merge.

## Goal

Reconcile the XLSX service master at `C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx` into WordPress as auditable draft content across existing CPTs.

Developers must be able to execute this without guessing. The work must be deterministic, idempotent, dry-run first, apply only after explicit approval, and safe to roll back by batch manifest.

## Current System References

Read these before implementation:

- `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-post-types.php`, registers public CPTs `layanan`, `poliklinik`, `rawat-inap`.
- `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php`, registers existing hierarchical `kategori-layanan` on `layanan` only, public rewrite `/layanan-medis`.
- `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php`, current theme curated umbrella and content selection behavior.
- `.sisyphus/tools/reconcile-source-2026.php`, prior one-time command pattern with default read-only mode, approval file, batch ID, preflight, apply manifest.
- `.sisyphus/drafts/reconcile-source-2026-approvals.review.json`, prior human approval file shape.
- `.sisyphus/evidence/reconcile-apply-manifest-source-2026-batch1-20260814.json`, prior local batch 1 manifest with drafts `20749` through `20756`.

## Settled Decisions

1. Every one of the 101 XLSX rows becomes one individual post identity.
2. Add 3 separate `layanan` hub draft identities for:
   - Cancer Centre
   - Uronephrology Centre
   - Emergency and Critical Care
3. Target structure is 104 entities total, but implementation must reconcile existing posts rather than duplicate them.
4. Preliminary deterministic CPT split for the 101 XLSX rows:
   - `poliklinik`: 40
   - `layanan`: 56
   - `rawat-inap`: 5
5. Final target after adding the 3 hub drafts:
   - `poliklinik`: 40
   - `layanan`: 59
   - `rawat-inap`: 5
   - total: 104
6. `Bangsal Rawat Inap` rows map to `rawat-inap`.
7. Outpatient specialist or dental rows map to `poliklinik`.
8. Titles beginning `Klinik ` map to `poliklinik`.
9. `One Day Care` maps to `layanan`.
10. All remaining XLSX rows map to `layanan`.
11. Correct verified display titles:
    - raw XLSX title matching NICU correction displays as `NICU`
    - raw XLSX title matching ambulance event support correction displays as `Ambulan Event Support`
12. Preserve each raw XLSX value and correction reason in audit meta.
13. Preserve existing IDs, slugs, status, content, excerpts, media, schedules, custom fields.
14. Existing matches may receive only approved audit metadata and new taxonomy assignments.
15. New rows are `draft` only. Empty body is allowed. No invented descriptions.
16. Reconcile and preserve prior local drafts `20749` through `20756` where identities match.
17. Do not assume those IDs on UAT. UAT matching must use stable identity, title, and manual approval review.
18. Current theme curated umbrella behavior remains unchanged until a later explicitly approved switch.
19. Do not alter existing `kategori-layanan`.
20. Do not make current `kategori-layanan` or theme grouping depend on the new taxonomy.

## New Taxonomy Decision

Create one new hierarchical cross-CPT taxonomy:

- Taxonomy machine slug: `master-layanan-medis`
- Human name: `Master Layanan Medis`
- Attached CPTs: `layanan`, `poliklinik`, `rawat-inap`
- Hierarchical: true
- Public: false unless the implementer finds project conventions require visibility for admin only. If public visibility is needed for admin UX, no front-end route may depend on it.
- REST: true, for admin and audit use.

Mapping rules:

- XLSX `Category` becomes the parent term.
- XLSX `Parent` becomes a child term under the parent term.
- If a child term exists for a row, assign both parent and child terms to the post.
- If no child term exists, assign only the parent term.
- Term matching must use normalized names within taxonomy and parent context.
- Existing `kategori-layanan` terms must not be created, edited, deleted, merged, or assigned by this importer.

## Identity Model

Stable identity tuple:

```text
CPT + normalized raw source name + normalized Category + normalized Parent
```

Rules:

- Normalize by trimming whitespace, decoding HTML entities, lowercasing, collapsing internal whitespace, and converting separators consistently.
- Keep normalization deterministic and documented in the generated mapping artifact.
- Duplicate names under distinct parent context remain separate posts.
- Matching priority:
  1. Existing post with `_rspku_source_identity_key` equal to generated key.
  2. Prior local batch manifest identity match, including drafts `20749` through `20756` locally.
  3. Manual approval file match by stable identity, target CPT, verified title, and reviewed existing post.
  4. Collision queue, no apply.
- Never match solely by title when Category or Parent differs.

## Required Audit Meta

Every matched or created entity touched by the approved batch must carry approved audit metadata only:

- `_rspku_source_import`
- `_rspku_source_row_number`
- `_rspku_source_name_raw`
- `_rspku_source_category_raw`
- `_rspku_source_parent_raw`
- `_rspku_source_identity_key`
- `_rspku_source_hash`
- `_rspku_verified_title`
- `_rspku_source_title_correction`, only when applicable

Recommended values:

- `_rspku_source_import`: `master-data-layanan-medis-101`
- `_rspku_source_hash`: deterministic hash of raw source row fields that define source data, not generated WordPress fields.
- `_rspku_verified_title`: final display title after allowed correction.
- `_rspku_source_title_correction`: reason string for `NICU` and `Ambulan Event Support` only.

## Generated Artifacts

Implementation must generate these artifacts before any apply:

- `.sisyphus/evidence/master-data-layanan-101-mapping-{batch_id}.json`
- `.sisyphus/evidence/master-data-layanan-101-preflight-{batch_id}.json`
- `.sisyphus/drafts/master-data-layanan-101-approvals.{batch_id}.json`

Implementation must generate these artifacts after apply:

- `.sisyphus/evidence/master-data-layanan-101-apply-manifest-{batch_id}.json`
- `.sisyphus/evidence/master-data-layanan-101-rollback-manifest-{batch_id}.json`
- `.sisyphus/evidence/master-data-layanan-101-route-checks-{batch_id}.md`

Mapping artifact must include:

- XLSX absolute source path.
- File hash and sheet metadata.
- Row number for each source row.
- Raw name, raw Category, raw Parent.
- Normalized identity tuple.
- Target CPT.
- Verified display title.
- Title correction reason where applicable.
- Target taxonomy parent term and child term.
- Match candidate details.
- Decision status.
- Collision status.
- Whether the entity is from one of 101 XLSX rows or one of 3 hub drafts.

Count assertions required before apply:

- XLSX rows read: exactly 101.
- XLSX row CPT split: `poliklinik=40`, `layanan=56`, `rawat-inap=5`.
- Hub drafts: exactly 3, all target CPT `layanan`.
- Final target entities: exactly 104.
- Final target CPT split: `poliklinik=40`, `layanan=59`, `rawat-inap=5`.
- Identity keys unique across all 104 entities.
- Any duplicate title under different parent context is allowed only when identity keys differ and the mapping artifact records the reason.
- No apply if any assertion fails.

## Importer Shape

Plan a one-time CLI or PHP command. Default behavior must be read-only.

Required modes:

- Default, read-only summary. No DB writes.
- `--preflight`, validates source, mapping, approvals, collisions, target CPTs, taxonomy registration, and permission gates. No DB writes.
- `--apply`, guarded write mode. Requires all apply gates.

Required arguments:

- `--source="C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx"`
- `--approved-file=<path>`
- `--batch-id=<id>`
- `--apply`, only after local approval or separate UAT approval
- `--preflight`, for dry-run review

Apply gates:

- Source file exists and hash matches reviewed preflight.
- Approved file exists.
- Approved file batch ID matches CLI batch ID.
- Approved file contains explicit approved action per entity.
- Count assertions pass.
- Collision checks pass.
- Taxonomy `master-layanan-medis` is registered and attached to all 3 target CPTs.
- Existing `kategori-layanan` untouched in planned operations.
- Apply manifest path does not already exist for the same batch ID unless command exits as idempotent no-op.
- User has given explicit local apply approval for local apply.
- UAT has separate explicit UAT approval for UAT apply.

Idempotency:

- Re-running preflight with the same source and approval file produces the same mapping and planned operations.
- Re-running apply for an already applied batch must not create duplicates.
- Existing audit meta match plus unchanged hash becomes no-op except missing approved taxonomy assignment.
- Any source hash change after approval invalidates apply.

Collision checks:

- Same identity key mapped to multiple targets blocks apply.
- Same existing post claimed by multiple source identities blocks apply unless manually approved as one canonical match and others held.
- Same title with different identity keys must be recorded, not merged.
- Existing post with conflicting `_rspku_source_identity_key` blocks apply.
- Attempted write to post type outside `layanan`, `poliklinik`, `rawat-inap` blocks apply.

## Preservation Rules

For existing matched posts:

- Preserve ID.
- Preserve slug.
- Preserve post status.
- Preserve post content.
- Preserve excerpt.
- Preserve featured image and media.
- Preserve schedules.
- Preserve custom fields except approved audit metadata keys listed above.
- Add only approved `master-layanan-medis` taxonomy assignments.

For new posts:

- Create as `draft` only.
- Use verified display title.
- Empty body allowed.
- No excerpt unless explicitly approved from real source.
- No media imports.
- No schedules.
- No invented descriptions.
- Slug must be generated by WordPress default rules at creation and then preserved. No later slug updates by this importer.

For prior local drafts:

- Preserve local draft IDs `20749` through `20756` when identity matches.
- Treat prior Centre drafts as candidate matches for the 3 hub draft identities.
- Do not create duplicate hub drafts locally if the identity already exists.
- On UAT, do not reference those numeric IDs as requirements. Use source identity, title, and approval review.

## Local Workflow

1. Build importer and taxonomy registration in minimal files only.
2. Run read-only default summary.
3. Generate mapping artifact.
4. Run `--preflight` and write preflight artifact.
5. Review generated approval file manually.
6. Obtain explicit local apply approval.
7. Run guarded `--apply` locally only.
8. Verify manifests, WordPress admin draft counts, route stability, taxonomy assignments, and preservation rules.
9. Stop. Do not run UAT without separate approval.

## UAT Workflow

UAT is separate from local approval.

1. Take UAT DB backup.
2. Take UAT application snapshot or host snapshot where available.
3. Deploy only reviewed code, not local DB state.
4. Confirm no production step is included.
5. Run read-only summary on UAT.
6. Run UAT `--preflight` with the XLSX source available in an approved UAT path.
7. Save UAT preflight artifact.
8. Review UAT preflight output, especially existing matches because local IDs don't apply.
9. Obtain explicit user approval for UAT apply.
10. Run guarded UAT `--apply` draft-only.
11. Save UAT apply and rollback manifests.
12. Verify draft-only state and no public UI switch.

No UAT apply may reuse local approval as authorization. No production apply is part of this plan.

## Rollback Plan

Rollback must use only the apply manifest and rollback manifest for the batch.

Allowed rollback actions:

- Delete draft posts created by this batch only.
- Remove `master-layanan-medis` term assignments added by this batch only.
- Delete terms created by this batch only when no posts remain assigned to them.
- Remove audit metadata keys added by this batch from pre-existing matched posts only when rollback manifest records previous absence or previous values.

Forbidden rollback actions:

- Delete unrelated posts.
- Delete or update pre-existing posts not created by this batch.
- Delete or alter `kategori-layanan` terms.
- Delete terms not created by this batch.
- Change slugs.
- Change publish status on unrelated posts.
- Restore DB backup as first option.

DB backup restore is last resort only, used when manifest rollback fails or data integrity is uncertain.

## Route And UI Checks

Local route checks after apply:

- Existing `layanan` archive still responds.
- Existing `poliklinik` archive still responds.
- Existing `rawat-inap` archive still responds.
- Existing `/layanan-medis` taxonomy route for `kategori-layanan` still responds as before.
- Legacy `/kategori-layanan/...` redirect behavior still works as before.
- New drafts are not publicly visible while draft.
- Theme curated umbrella behavior in `TemplateController.php` remains unchanged.
- No template, menu, or front-end grouping switches to `master-layanan-medis`.

Evidence path:

- `.sisyphus/evidence/master-data-layanan-101-route-checks-{batch_id}.md`

## Verification Matrix

PHP lint:

- Run PHP lint on any changed PHP file.
- Evidence path: `.sisyphus/evidence/master-data-layanan-101-php-lint-{batch_id}.txt`

LSP:

- Run LSP diagnostics on changed PHP files if available.
- If unavailable, record tool absence and PHP lint result.
- Evidence path: `.sisyphus/evidence/master-data-layanan-101-lsp-{batch_id}.txt`

Tests:

- If plugin or theme test runner exists, run the smallest relevant test set first.
- Add no large test framework for this one-time importer.
- A small assertion script for mapping counts is required if no test runner exists.
- Evidence path: `.sisyphus/evidence/master-data-layanan-101-tests-{batch_id}.txt`

Build:

- Theme build is not required unless theme frontend source changes.
- If any file under `wp-content/themes/rspku-theme/resources/js/`, `resources/css/`, or `resources/views/` changes, run `npm run build` in `wp-content/themes/rspku-theme` and confirm `public/build/.vite/manifest.json` updated.
- This plan expects no theme frontend source changes.

Manual QA:

- Use WordPress admin or query output to confirm draft counts and taxonomy assignments.
- Use route checks above for public behavior.
- Confirm no public UI switch.

## Executable QA Scenarios

Use batch ID `master-data-layanan-101-local-YYYYMMDD-HHMM` in examples. Replace only the timestamp. Planned importer path is `.sisyphus/tools/import-master-data-layanan-101.php`; if implementation chooses a different path, update all commands in one commit and keep the same flags.

### QA-1 Source And Reference Readiness

Tool: PowerShell 7.

Command:

```powershell
Test-Path -LiteralPath "C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx"
```

Expected result:

- Prints `True`.
- No DB writes.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-source-ready-{batch_id}.txt`.

Tool: OpenCode Read/Grep.

Steps:

- Read `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-post-types.php`.
- Read `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php`.
- Read `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php`.
- Read `.sisyphus/tools/reconcile-source-2026.php`.
- Read `.sisyphus/drafts/reconcile-source-2026-approvals.review.json`.
- Read `.sisyphus/evidence/reconcile-apply-manifest-source-2026-batch1-20260814.json`.

Expected result:

- CPTs `layanan`, `poliklinik`, `rawat-inap` confirmed.
- Existing `kategori-layanan` confirmed unchanged in current code.
- Prior batch local draft IDs `20749` through `20756` confirmed as local-only evidence.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-reference-read-{batch_id}.md`.

### QA-2 PHP Lint And LSP For Changed PHP Files

Tool: PowerShell 7.

Command template after implementation:

```powershell
$files = @(
  "wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php",
  ".sisyphus/tools/import-master-data-layanan-101.php"
)
foreach ($file in $files) { php -l $file }
```

Expected result:

- Each changed PHP file prints `No syntax errors detected in <file>`.
- No DB writes.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-php-lint-{batch_id}.txt`.

Tool: OpenCode `lsp_diagnostics`.

Steps:

- Run `lsp_diagnostics` on each changed PHP file.
- Required changed PHP files include the importer and taxonomy registration file if touched.

Expected result:

- Zero errors for every changed PHP file.
- Warnings are either zero or documented as pre-existing/non-blocking.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-lsp-{batch_id}.txt`.

### QA-3 Importer Default Read-Only Mode

Tool: PowerShell 7.

Command:

```powershell
php .sisyphus/tools/import-master-data-layanan-101.php --source="C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx" --batch-id=master-data-layanan-101-local-YYYYMMDD-HHMM
```

Expected result:

- Exit code `0`.
- Output states `mode=read-only`.
- Output states `db_writes=0`.
- Output states `xlsx_rows=101`.
- Output states `planned_entities=104`.
- No posts or terms created/updated/deleted.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-read-only-{batch_id}.txt`.

### QA-4 Mapping Generation And Count Assertions

Tool: PowerShell 7.

Command:

```powershell
php .sisyphus/tools/import-master-data-layanan-101.php --source="C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx" --batch-id=master-data-layanan-101-local-YYYYMMDD-HHMM --generate-mapping
```

Expected result:

- Exit code `0`.
- Writes `.sisyphus/evidence/master-data-layanan-101-mapping-{batch_id}.json`.
- JSON has `source.xlsx_rows=101`.
- JSON has `hub_identities=3`.
- JSON has `total_identities=104`.
- JSON has XLSX CPT counts `poliklinik=40`, `layanan=56`, `rawat-inap=5`.
- JSON has final CPT counts `poliklinik=40`, `layanan=59`, `rawat-inap=5`.
- JSON has zero duplicate `_rspku_source_identity_key` values.
- JSON includes every raw row number.
- JSON includes 3 hub identities flagged as non-XLSX generated hubs.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-mapping-{batch_id}.json`.

Tool: PowerShell 7.

Command:

```powershell
$mapping = Get-Content -Raw ".sisyphus/evidence/master-data-layanan-101-mapping-master-data-layanan-101-local-YYYYMMDD-HHMM.json" | ConvertFrom-Json
@(
  $mapping.source.xlsx_rows -eq 101,
  $mapping.hub_identities -eq 3,
  $mapping.total_identities -eq 104,
  $mapping.final_cpt_counts.poliklinik -eq 40,
  $mapping.final_cpt_counts.layanan -eq 59,
  $mapping.final_cpt_counts.'rawat-inap' -eq 5,
  (($mapping.entities._rspku_source_identity_key | Sort-Object -Unique).Count -eq 104)
) -notcontains $false
```

Expected result:

- Prints `True`.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-mapping-assertions-{batch_id}.txt`.

### QA-5 Taxonomy Term Mapping Assertions

Tool: PowerShell 7.

Command:

```powershell
$mapping = Get-Content -Raw ".sisyphus/evidence/master-data-layanan-101-mapping-master-data-layanan-101-local-YYYYMMDD-HHMM.json" | ConvertFrom-Json
$parentCount = ($mapping.taxonomy.parent_terms | Sort-Object slug -Unique).Count
$childCount = ($mapping.taxonomy.child_terms | Sort-Object parent_slug, slug -Unique).Count
"parents=$parentCount children=$childCount"
```

Expected result:

- Prints exact counts generated from XLSX, expected currently `parents=8 children=17` if source-derived counts match.
- If source-derived counts differ, apply is blocked until the mapping artifact documents the XLSX-derived parent and child values and a human reviewer approves the changed counts.
- No hardcoded term creation outside generated mapping.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-taxonomy-counts-{batch_id}.txt`.

Tool: PowerShell 7.

Command:

```powershell
$mapping = Get-Content -Raw ".sisyphus/evidence/master-data-layanan-101-mapping-master-data-layanan-101-local-YYYYMMDD-HHMM.json" | ConvertFrom-Json
$bad = $mapping.entities | Where-Object { -not $_.taxonomy.parent_term_slug -or ($_.source_parent_raw -and -not $_.taxonomy.child_term_slug) }
$bad.Count
```

Expected result:

- Prints `0`.
- Every row has correct parent term.
- Every row with XLSX Parent has correct child term.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-taxonomy-row-assertions-{batch_id}.txt`.

### QA-6 Duplicate Labels And Corrected Titles

Tool: PowerShell 7.

Command:

```powershell
$mapping = Get-Content -Raw ".sisyphus/evidence/master-data-layanan-101-mapping-master-data-layanan-101-local-YYYYMMDD-HHMM.json" | ConvertFrom-Json
$duplicateLabels = $mapping.entities | Group-Object verified_title | Where-Object { $_.Count -gt 1 }
$duplicateLabels | ForEach-Object { "title=$($_.Name) count=$($_.Count) identities=$(($_.Group._rspku_source_identity_key | Sort-Object -Unique).Count)" }
$mapping.entities | Where-Object { $_.verified_title -in @("NICU", "Ambulan Event Support") } | Select-Object verified_title, source_name_raw, source_title_correction
```

Expected result:

- Two duplicate labels that exist in source remain separate identities when parent context differs.
- Duplicate-label output shows identity count equals row count for each duplicate label.
- `NICU` and `Ambulan Event Support` rows show raw metadata and correction reason.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-title-assertions-{batch_id}.txt`.

### QA-7 Preflight Success

Tool: PowerShell 7.

Command:

```powershell
php .sisyphus/tools/import-master-data-layanan-101.php --source="C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx" --batch-id=master-data-layanan-101-local-YYYYMMDD-HHMM --approved-file=.sisyphus/drafts/master-data-layanan-101-approvals.master-data-layanan-101-local-YYYYMMDD-HHMM.json --preflight
```

Expected result:

- Exit code `0` only after approval file exists with explicit approved decisions.
- Output states `mode=preflight`.
- Output states `db_writes=0`.
- Output states `collisions=0`.
- Output states `apply_ready=true`.
- Writes `.sisyphus/evidence/master-data-layanan-101-preflight-{batch_id}.json`.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-preflight-{batch_id}.json`.

### QA-8 Missing Approval Failure

Tool: PowerShell 7.

Command:

```powershell
php .sisyphus/tools/import-master-data-layanan-101.php --source="C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx" --batch-id=master-data-layanan-101-local-YYYYMMDD-HHMM --approved-file=.sisyphus/drafts/does-not-exist.json --preflight
```

Expected result:

- Non-zero exit code.
- Output or STDERR contains `Missing approved file`.
- Output states or implies `db_writes=0`.
- No posts or terms created/updated/deleted.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-missing-approval-failure-{batch_id}.txt`.

### QA-9 Collision Gate

Tool: PowerShell 7.

Command:

```powershell
php .sisyphus/tools/import-master-data-layanan-101.php --source="C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx" --batch-id=master-data-layanan-101-local-YYYYMMDD-HHMM --approved-file=.sisyphus/drafts/master-data-layanan-101-approvals.collision-fixture.json --preflight
```

Expected result:

- Fixture intentionally maps one source identity to a conflicting existing post or duplicates one identity key.
- Non-zero exit code.
- Output states `collisions>0`.
- Output states `apply_ready=false`.
- Output states `db_writes=0`.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-collision-gate-{batch_id}.txt`.

### QA-10 Local Apply And Manifest Assertions

Tool: PowerShell 7.

Command:

```powershell
php .sisyphus/tools/import-master-data-layanan-101.php --source="C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx" --batch-id=master-data-layanan-101-local-YYYYMMDD-HHMM --approved-file=.sisyphus/drafts/master-data-layanan-101-approvals.master-data-layanan-101-local-YYYYMMDD-HHMM.json --apply
```

Expected result:

- Run only after explicit local user approval.
- Exit code `0`.
- Writes `.sisyphus/evidence/master-data-layanan-101-apply-manifest-{batch_id}.json`.
- Writes `.sisyphus/evidence/master-data-layanan-101-rollback-manifest-{batch_id}.json`.
- Created posts are `draft` only.
- Existing matched posts keep ID, slug, status, content, excerpt, media, schedules, and unrelated custom fields.
- Existing matched posts receive only approved audit meta and `master-layanan-medis` assignments.
- No `kategori-layanan` operations appear in manifest.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-apply-manifest-{batch_id}.json`.

Tool: PowerShell 7.

Command:

```powershell
$manifest = Get-Content -Raw ".sisyphus/evidence/master-data-layanan-101-apply-manifest-master-data-layanan-101-local-YYYYMMDD-HHMM.json" | ConvertFrom-Json
@(
  $manifest.batch_id -eq "master-data-layanan-101-local-YYYYMMDD-HHMM",
  $manifest.deleted_existing_count -eq 0,
  $manifest.published_count -eq 0,
  (($manifest.operations | Where-Object { $_.taxonomy -eq "kategori-layanan" }).Count -eq 0),
  (($manifest.created | Where-Object { $_.status -ne "draft" }).Count -eq 0)
) -notcontains $false
```

Expected result:

- Prints `True`.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-manifest-assertions-{batch_id}.txt`.

### QA-11 Idempotency Check

Tool: PowerShell 7.

Command:

```powershell
php .sisyphus/tools/import-master-data-layanan-101.php --source="C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx" --batch-id=master-data-layanan-101-local-YYYYMMDD-HHMM --approved-file=.sisyphus/drafts/master-data-layanan-101-approvals.master-data-layanan-101-local-YYYYMMDD-HHMM.json --apply
```

Expected result:

- Exit code `0` or documented idempotent no-op code.
- Output states `idempotent_noop=true` or `created_count=0 updated_count=0`.
- No duplicate posts.
- Existing apply manifest is not overwritten unless byte-identical or the command refuses safely.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-idempotency-{batch_id}.txt`.

### QA-12 WordPress Read-Only Snapshot Verification

Tool: PowerShell 7 with PHP bootstrapping `wp-load.php`.

Pre-apply snapshot command:

```powershell
php -r "require 'wp-load.php'; foreach (['layanan','poliklinik','rawat-inap','dokter'] as $pt) { $counts = wp_count_posts($pt); echo $pt . ' publish=' . (int) $counts->publish . ' draft=' . (int) $counts->draft . PHP_EOL; } foreach (['layanan','poliklinik','rawat-inap'] as $pt) { $posts = get_posts(['post_type'=>$pt,'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']); foreach ($posts as $id) { echo 'published_slug ' . $pt . ' ' . $id . ' ' . get_post_field('post_name',$id) . PHP_EOL; } }"
```

Expected result:

- Captures published counts and slugs before apply.
- No DB writes.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-pre-apply-snapshot-{batch_id}.txt`.

Post-apply read-only command:

```powershell
php -r "require 'wp-load.php'; $ids = get_posts(['post_type'=>['layanan','poliklinik','rawat-inap'],'post_status'=>['publish','draft','pending','private'],'posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_rspku_source_import','meta_value'=>'master-data-layanan-medis-101']); echo 'source_identity_records=' . count($ids) . PHP_EOL; $keys=[]; $draftBad=0; foreach ($ids as $id) { $key=(string)get_post_meta($id,'_rspku_source_identity_key',true); $keys[$key]=true; if (get_post_status($id) !== 'draft' && !get_post_meta($id,'_rspku_preexisting_match',true)) { $draftBad++; } } echo 'unique_identity_keys=' . count($keys) . PHP_EOL; echo 'new_non_draft_count=' . $draftBad . PHP_EOL; foreach (['layanan','poliklinik','rawat-inap','dokter'] as $pt) { $counts = wp_count_posts($pt); echo $pt . ' publish=' . (int) $counts->publish . ' draft=' . (int) $counts->draft . PHP_EOL; }"
```

Expected result:

- Mapping artifact represents 101 XLSX row identities, 3 hub identities, total 104.
- Final target identities are `poliklinik=40`, `layanan=59`, `rawat-inap=5`.
- `unique_identity_keys=104`.
- All newly created posts are draft.
- Published counts and published slugs match pre-apply snapshot.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-post-apply-readonly-{batch_id}.txt`.

Taxonomy read-only command:

```powershell
php -r "require 'wp-load.php'; $taxonomy='master-layanan-medis'; $parents=get_terms(['taxonomy'=>$taxonomy,'hide_empty'=>false,'parent'=>0]); echo 'parent_terms=' . count($parents) . PHP_EOL; $children=0; foreach ($parents as $parent) { $terms=get_terms(['taxonomy'=>$taxonomy,'hide_empty'=>false,'parent'=>$parent->term_id]); $children += count($terms); } echo 'child_terms=' . $children . PHP_EOL; echo 'kategori_layanan_attached=' . (int) is_object_in_taxonomy('layanan','kategori-layanan') . PHP_EOL; echo 'master_attached_layanan=' . (int) is_object_in_taxonomy('layanan',$taxonomy) . PHP_EOL; echo 'master_attached_poliklinik=' . (int) is_object_in_taxonomy('poliklinik',$taxonomy) . PHP_EOL; echo 'master_attached_rawat_inap=' . (int) is_object_in_taxonomy('rawat-inap',$taxonomy) . PHP_EOL;"
```

Expected result:

- `parent_terms=8` and `child_terms=17` if source-derived mapping counts assert those values.
- If mapping artifact documents different source-derived counts, expected values must equal generated manifest values and reviewer approval must mention the difference.
- `kategori_layanan_attached=1` remains true for `layanan`.
- `master_attached_layanan=1`, `master_attached_poliklinik=1`, `master_attached_rawat_inap=1`.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-taxonomy-readonly-{batch_id}.txt`.

Dashboard-equivalent query:

```powershell
php -r "require 'wp-load.php'; $drafts=get_posts(['post_type'=>['layanan','poliklinik','rawat-inap'],'post_status'=>'draft','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_rspku_source_import','meta_value'=>'master-data-layanan-medis-101']); echo 'dashboard_equivalent_drafts=' . count($drafts) . PHP_EOL; foreach (array_slice($drafts,0,10) as $id) { echo $id . ' ' . get_post_type($id) . ' ' . get_the_title($id) . PHP_EOL; }"
```

Expected result:

- Drafts created by this import are visible to admin-equivalent query.
- Authenticated dashboard access is optional, not mandatory, because this query proves the same data without credentials.
- Optional admin paths for manual review: `/wp-admin/edit.php?post_type=layanan`, `/wp-admin/edit.php?post_type=poliklinik`, `/wp-admin/edit.php?post_type=rawat-inap`, `/wp-admin/edit-tags.php?taxonomy=master-layanan-medis&post_type=layanan`.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-dashboard-equivalent-{batch_id}.txt`.

### QA-13 Route Checks

Tool: `curl.exe` from PowerShell 7.

Static route commands:

```powershell
curl.exe -I http://rspkudev.test/
curl.exe -I http://rspkudev.test/layanan/
curl.exe -I http://rspkudev.test/poliklinik/
curl.exe -I http://rspkudev.test/rawat-inap/
curl.exe -I http://rspkudev.test/dokter/
curl.exe -I http://rspkudev.test/jadwal-dokter/
```

Expected result:

- Each response contains HTTP status `200`.
- No route depends on `master-layanan-medis`.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-static-routes-{batch_id}.txt`.

Published service and taxonomy URL discovery:

```powershell
php -r "require 'wp-load.php'; $service=get_posts(['post_type'=>'layanan','post_status'=>'publish','posts_per_page'=>1,'orderby'=>'title','order'=>'ASC']); echo 'SERVICE_URL=' . get_permalink($service[0]) . PHP_EOL; $terms=get_terms(['taxonomy'=>'kategori-layanan','hide_empty'=>true,'number'=>1]); echo 'TAXONOMY_URL=' . get_term_link($terms[0]) . PHP_EOL;"
```

Expected result:

- Prints one full local URL beginning `http://rspkudev.test/layanan/` for an existing published service detail.
- Prints one full local URL beginning `http://rspkudev.test/layanan-medis/` for an existing `kategori-layanan` taxonomy archive.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-dynamic-route-discovery-{batch_id}.txt`.

Dynamic route commands:

```powershell
curl.exe -I <SERVICE_URL_FROM_DISCOVERY>
curl.exe -I <TAXONOMY_URL_FROM_DISCOVERY>
```

Expected result:

- Each response contains HTTP status `200`.
- Existing taxonomy archive remains `kategori-layanan`, not `master-layanan-medis`.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-dynamic-routes-{batch_id}.txt`.

Draft title absence check:

```powershell
$html = (Invoke-WebRequest -UseBasicParsing http://rspkudev.test/layanan/).Content
@("Cancer Centre", "Uronephrology Centre", "Emergency and Critical Care", "NICU", "Ambulan Event Support") | ForEach-Object { "$_=" + ($html.Contains($_)) }
```

Expected result:

- Draft hub titles print `False` on public archive unless they were pre-existing published content before this batch.
- New draft-only imported titles are absent from public archives.
- Current curated grouping remains unchanged.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-draft-title-absence-{batch_id}.txt`.

Anonymous draft permalink discovery and check:

```powershell
php -r "require 'wp-load.php'; $draft=get_posts(['post_type'=>['layanan','poliklinik','rawat-inap'],'post_status'=>'draft','posts_per_page'=>1,'meta_key'=>'_rspku_source_import','meta_value'=>'master-data-layanan-medis-101']); echo get_permalink($draft[0]) . PHP_EOL;"
curl.exe -I <DRAFT_URL_FROM_DISCOVERY>
```

Expected result:

- Anonymous `curl.exe` response contains HTTP status `404`.
- If WordPress returns `302` to login for drafts in local config, follow with `curl.exe -L -I <DRAFT_URL_FROM_DISCOVERY>` and expected final status is not `200` public content.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-draft-permalink-404-{batch_id}.txt`.

### QA-14 Rollback Dry-Run And Scope Check

Tool: PowerShell 7.

Command:

```powershell
php .sisyphus/tools/import-master-data-layanan-101.php --batch-id=master-data-layanan-101-local-YYYYMMDD-HHMM --rollback-manifest=.sisyphus/evidence/master-data-layanan-101-rollback-manifest-master-data-layanan-101-local-YYYYMMDD-HHMM.json --rollback-preflight
```

Expected result:

- Exit code `0`.
- Output states `mode=rollback-preflight`.
- Output states proposed rollback touches only batch-created drafts, batch-created terms, batch-added term assignments, and batch-added audit meta.
- Output states no unrelated posts, no pre-existing terms, no `kategori-layanan`, no slug changes.
- No DB writes.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-rollback-preflight-{batch_id}.txt`.

### QA-15 UAT Sequence

Tool: hosting panel, database tool, PowerShell 7 or SSH equivalent. No credentials in plan or evidence.

Steps:

1. Take UAT DB backup before any importer run.
2. Take UAT app/host snapshot if available.
3. Record backup artifact name without credentials.
4. Deploy reviewed code only, not local DB.
5. Run read-only command on UAT with UAT source path.
6. Run mapping generation on UAT.
7. Run UAT preflight with UAT approval file path.
8. Stop before `--apply`.
9. User reviews UAT preflight output.
10. User gives explicit UAT approval.
11. Run UAT `--apply` draft-only.
12. Run UAT read-only snapshot, taxonomy, route, draft permalink, and manifest assertions.

Expected result:

- Apply is explicitly stopped before user approval.
- UAT uses UAT DB backup and snapshot.
- UAT matching does not assume local IDs `20749` through `20756`.
- UAT apply creates drafts only and writes UAT manifests.
- No production action.
- Evidence:
  - `.sisyphus/evidence/master-data-layanan-101-uat-backup-{batch_id}.md`
  - `.sisyphus/evidence/master-data-layanan-101-uat-read-only-{batch_id}.txt`
  - `.sisyphus/evidence/master-data-layanan-101-uat-preflight-{batch_id}.json`
  - `.sisyphus/evidence/master-data-layanan-101-uat-approval-{batch_id}.md`
  - `.sisyphus/evidence/master-data-layanan-101-uat-apply-manifest-{batch_id}.json`
  - `.sisyphus/evidence/master-data-layanan-101-uat-verification-{batch_id}.md`

### QA-16 Final Verification Wave

Tool: PowerShell 7, OpenCode `lsp_diagnostics`, `curl.exe`, PHP bootstrapping `wp-load.php`.

Commands and checks:

```powershell
php -l wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php
php -l .sisyphus/tools/import-master-data-layanan-101.php
php .sisyphus/tools/import-master-data-layanan-101.php --source="C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx" --batch-id=master-data-layanan-101-local-YYYYMMDD-HHMM --approved-file=.sisyphus/drafts/master-data-layanan-101-approvals.master-data-layanan-101-local-YYYYMMDD-HHMM.json --preflight
curl.exe -I http://rspkudev.test/
curl.exe -I http://rspkudev.test/layanan/
curl.exe -I http://rspkudev.test/poliklinik/
curl.exe -I http://rspkudev.test/rawat-inap/
curl.exe -I http://rspkudev.test/dokter/
curl.exe -I http://rspkudev.test/jadwal-dokter/
```

Expected result:

- PHP lint clean.
- LSP diagnostics zero errors on changed PHP files.
- Preflight reports `apply_ready=true`, `collisions=0`, `db_writes=0`.
- All static routes return HTTP `200`.
- Mapping, manifest, read-only WP, taxonomy, title, duplicate, route, idempotency, rollback-preflight evidence files exist.
- Theme build evidence exists only if frontend source changed; otherwise evidence states not applicable.
- Evidence: `.sisyphus/evidence/master-data-layanan-101-final-verification-{batch_id}.md`.

## `/start-work` Tasks

- [x] Confirm source XLSX exists at `C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx`; dependency: local file access; success: file hash recorded.
- [x] Read referenced CPT, taxonomy, theme controller, prior reconcile command, approval file, and apply manifest; dependency: repo access; success: implementation notes cite exact files.
- [x] Add `master-layanan-medis` taxonomy registration attached to `layanan`, `poliklinik`, `rawat-inap`; dependency: CPT taxonomy plugin files; success: taxonomy exists, `kategori-layanan` unchanged.
- [x] Create one-time importer command with default read-only behavior; dependency: command location decision; success: no DB writes without `--apply`.
- [x] Implement deterministic XLSX read and normalization; dependency: available XLSX reader decision; success: 101 rows read and source hash recorded.
- [x] Generate mapping artifact with all 104 entities; dependency: normalization and hub draft rules; success: count assertions pass.
- [x] Implement title corrections for `NICU` and `Ambulan Event Support`; dependency: raw title detection; success: raw value and correction reason in mapping and audit meta.
- [x] Implement identity matching and collision queue; dependency: mapping artifact; success: duplicate titles under different parent context remain separate identities.
- [x] Implement approval file generation and parsing; dependency: preflight output; success: apply blocked without explicit approved decisions.
- [x] Implement `--preflight` gate; dependency: mapping and approvals; success: no DB writes, preflight artifact written.
- [x] Review local preflight output; dependency: preflight artifact; success: user gives explicit local apply approval or apply does not run.
- [x] Implement guarded `--apply`; dependency: explicit local approval; success: draft-only writes, existing content preserved, manifests written.
- [x] Verify local apply with manifests, admin/query checks, and route checks; dependency: local apply; success: evidence files written.
- [x] Implement manifest rollback command or documented rollback procedure; dependency: apply manifest shape; success: rollback touches only batch-created or batch-assigned items.
- [x] Run PHP lint for changed PHP files; dependency: implementation files; success: lint evidence recorded.
- [x] Run LSP diagnostics where available; dependency: implementation files; success: diagnostics evidence recorded or unavailable reason recorded.
- [x] Run mapping count assertion or relevant tests; dependency: importer implementation; success: tests evidence recorded.
- [x] Run theme build only if frontend source changed; dependency: changed file list; success: build evidence recorded or not applicable recorded.
- [x] Prepare UAT preflight package; dependency: local verification; success: UAT instructions include DB backup, snapshot, preflight review, separate approval.
- [x] Stop before UAT apply until explicit UAT approval; dependency: user approval; success: no UAT DB writes during implementation without approval.

## Acceptance Criteria

AC-1, source integrity:

Given the XLSX source path is configured, when default read-only mode runs, then it records the file hash and reads exactly 101 rows without DB writes.

AC-2, deterministic split:

Given the 101 XLSX rows, when mapping runs, then it assigns exactly 40 rows to `poliklinik`, 56 to `layanan`, and 5 to `rawat-inap` before hub drafts.

AC-3, hub drafts:

Given hub draft generation runs, when mapping completes, then `Cancer Centre`, `Uronephrology Centre`, and `Emergency and Critical Care` exist as 3 planned `layanan` draft entities, making 104 total entities.

AC-4, taxonomy isolation:

Given taxonomy registration is loaded, when WordPress initializes, then `master-layanan-medis` is attached to `layanan`, `poliklinik`, and `rawat-inap`, while existing `kategori-layanan` remains unchanged.

AC-5, taxonomy hierarchy:

Given a row has Category and Parent values, when apply runs, then Category is assigned as parent term and Parent as child term, with both assigned where child exists.

AC-6, identity uniqueness:

Given duplicate display names under different parent contexts, when mapping generates identities, then they produce distinct identity keys and no merge occurs.

AC-7, preservation:

Given an existing post matches an identity, when apply runs, then ID, slug, status, content, excerpt, media, schedules, and unrelated custom fields are preserved.

AC-8, new post safety:

Given an approved missing identity, when apply creates it, then the post is `draft`, has verified title, may have empty body, and receives no invented description, media, or schedule.

AC-9, title corrections:

Given rows requiring verified title correction, when mapping and apply run, then display titles are `NICU` and `Ambulan Event Support`, and raw values plus correction reason are stored in audit metadata.

AC-10, prior local drafts:

Given local drafts `20749` through `20756` match stable identities, when local apply runs, then they are preserved and not duplicated.

AC-11, UAT identity matching:

Given UAT has different IDs, when UAT preflight runs, then it matches by stable identity, title, and manual approval review, not local numeric IDs.

AC-12, apply gates:

Given any count assertion, approval, collision, source hash, or taxonomy check fails, when `--apply` is requested, then the command exits without DB writes.

AC-13, idempotency:

Given a batch was already applied, when `--apply` runs again with the same batch ID and source hash, then no duplicate posts or terms are created.

AC-14, rollback scope:

Given rollback runs for a batch, when it reads the rollback manifest, then it touches only posts, terms, term assignments, and audit meta created or assigned by that batch.

AC-15, public behavior:

Given local apply completes, when route checks run, then existing CPT routes and `kategori-layanan` routes behave as before, and no public UI grouping depends on `master-layanan-medis`.

AC-16, approval separation:

Given local apply was approved and completed, when UAT work begins, then UAT still requires its own DB backup, snapshot, preflight review, explicit user approval, and draft-only apply.

## Exclusions

- No publish.
- No delete except manifest rollback of batch-created draft posts after explicit rollback approval.
- No slug changes.
- No taxonomy merge.
- No edits to existing `kategori-layanan`.
- No descriptions invented from medical knowledge or guesswork.
- No media imports.
- No schedule writes.
- No public UI switch.
- No theme grouping dependency on `master-layanan-medis`.
- No UAT action during implementation without explicit UAT approval.
- No production step.
- No new dependency unless unavoidable for XLSX parsing. If unavoidable, document why existing PHP, WordPress, Composer, or project code cannot read the XLSX safely.
- No edits to existing plans as part of this task.

## Stop Conditions

Stop and ask before apply if:

- XLSX row count is not 101.
- Deterministic split does not match `40/56/5`.
- Final entity count is not 104.
- Any identity collision lacks manual approval.
- Any proposed operation touches `kategori-layanan`.
- Any proposed operation changes existing slug, content, excerpt, media, schedule, status, or unrelated custom field.
- UAT approval is missing.
- Backup or snapshot is missing on UAT.

## Final Implementation Evidence Checklist

- [x] Mapping artifact exists and count assertions pass.
- [x] Preflight artifact exists and records zero DB writes.
- [x] Approval file reviewed; no actionable local apply approval exists, so apply is no-op.
- [x] Apply manifest exists after local no-op apply.
- [x] Rollback manifest exists after local no-op apply.
- [x] PHP lint evidence exists for changed PHP files.
- [x] LSP evidence exists or unavailable reason recorded.
- [x] Test or assertion evidence exists.
- [x] Route check evidence exists.
- [x] Theme build evidence exists or not applicable reason recorded.
- [x] UAT package states separate backup, snapshot, preflight review, and explicit approval requirement.
