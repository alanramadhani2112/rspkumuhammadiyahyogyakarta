# Batch 1 Reconciliation Handoff

## Result

- Batch: `source-2026-batch1-20260814`
- Scope: local WordPress only
- Created: 8 drafts
- Updated: 0
- Deleted existing: 0
- Published: 0
- Taxonomy, schedules, media: unchanged

## Created Drafts

- `20749` Klinik Nyeri Terpadu (`poliklinik`)
- `20750` Klinik TB-DOTS (`poliklinik`)
- `20751` Klinik Berhenti Merokok (`poliklinik`)
- `20752` Klinik Keluarga Sakinah (`poliklinik`)
- `20753` Klinik Laktasi (`poliklinik`)
- `20754` Cancer Centre (`layanan`)
- `20755` Uronephrology Centre (`layanan`)
- `20756` Emergency and Critical Care (`layanan`)

## Verification

- Every post remains `draft` and has all six reconciliation audit metadata fields.
- Published counts remain: dokter 100, layanan 15, poliklinik 37, rawat-inap 1.
- Draft counts increased only by approved rows: layanan +3, poliklinik +5.
- Public archives return HTTP 200 and contain no links to the drafts.
- Sample published search queries return zero matching posts. Search headings may echo the query text.
- Draft permalinks return HTTP 404 anonymously.
- Re-running preflight is blocked by source-key, title, and slug collisions.

## Evidence

- `.sisyphus/evidence/reconcile-apply-manifest-source-2026-batch1-20260814.json`
- `.sisyphus/evidence/task-5-draft-create.json`
- `.sisyphus/backups/source-2026-pre-batch1-20260814-2315.sql`

## Rollback

Rollback requires explicit approval. Preferred rollback deletes only draft IDs `20749` through `20756`, after confirming they remain in batch `source-2026-batch1-20260814`. Full database restore is fallback only.

No UAT or production deployment occurred.
