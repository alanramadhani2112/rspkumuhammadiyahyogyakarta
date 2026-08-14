# Source 2026 Reconciliation Handoff

## Current State

- Batch ID: `source-2026-batch1-20260814`
- Environment changed: local WordPress only
- Created: 8 drafts
- Updated: 0
- Deleted existing: 0
- Published: 0
- UAT: not touched by this reconciliation batch
- Production: not touched

## Touched Records

| Post ID | CPT | Status | Title |
| ---: | --- | --- | --- |
| 20749 | `poliklinik` | draft | Klinik Nyeri Terpadu |
| 20750 | `poliklinik` | draft | Klinik TB-DOTS |
| 20751 | `poliklinik` | draft | Klinik Berhenti Merokok |
| 20752 | `poliklinik` | draft | Klinik Keluarga Sakinah |
| 20753 | `poliklinik` | draft | Klinik Laktasi |
| 20754 | `layanan` | draft | Cancer Centre |
| 20755 | `layanan` | draft | Uronephrology Centre |
| 20756 | `layanan` | draft | Emergency and Critical Care |

## Counts

| CPT | Published before | Published after | Draft before | Draft after |
| --- | ---: | ---: | ---: | ---: |
| `dokter` | 100 | 100 | 0 | 0 |
| `layanan` | 15 | 15 | 0 | 3 |
| `poliklinik` | 37 | 37 | 0 | 5 |
| `rawat-inap` | 1 | 1 | 0 | 0 |

## Approval Status

- The eight rows above received explicit batch-1 approval for draft creation only.
- Slug changes, taxonomy mutations, schedules, media, publishing, existing-record updates, UAT apply, and production apply were not approved.
- One Day Care, Bedah Sentral, all doctor changes, remaining services, and all parent-detail copy remain held for later review.

## Verification

- All eight records remain drafts with complete reconciliation audit metadata.
- Public archives expose no draft links.
- Anonymous draft permalinks return HTTP 404.
- Published counts and existing public routes remain unchanged.
- Repeat preflight is blocked by source-key, title, and slug collisions.
- Dashboard UI review is blocked because anonymous local `/wp-admin/` redirects to `/404/`; equivalent batch/title queries confirm five poliklinik drafts and three layanan drafts.

## Evidence

- Apply manifest: `.sisyphus/evidence/reconcile-apply-manifest-source-2026-batch1-20260814.json`
- Draft verification: `.sisyphus/evidence/task-5-draft-create.json`
- Mapping policy: `.sisyphus/drafts/reconcile-source-2026-service-mapping.md`
- Visibility QA: `.sisyphus/evidence/task-7-draft-visibility.json`
- Route QA: `.sisyphus/evidence/task-7-route-smoke.txt`
- Technical QA: `.sisyphus/evidence/task-8-php-lint.txt`, `.sisyphus/evidence/task-8-build-status.txt`
- Pre-apply DB backup: `.sisyphus/backups/source-2026-pre-batch1-20260814-2315.sql`

## Rollback

Rollback is irreversible for the selected draft records and requires explicit approval.

1. Verify IDs `20749` through `20756` still have status `draft` and `_reconcile_batch_id=source-2026-batch1-20260814`.
2. Export a fresh DB backup.
3. Delete only those eight drafts through the WordPress API or dashboard.
4. Re-run public route and count checks.
5. Use the full pre-apply DB backup only if a broader database restoration is explicitly approved.

No rollback command has been executed.

## Next Gate

1. Obtain authenticated dashboard QA or accept read-only query evidence.
2. Review titles, destinations, clinical accuracy, copy, parent/detail relationships, taxonomy, and media while records remain drafts.
3. Approve UAT apply explicitly before copying data to UAT.
4. Approve publishing separately after UAT content QA.
5. Approve production separately after UAT sign-off and a fresh backup.

Credentials, passwords, keys, and secret values must never be copied into this handoff or evidence files.
