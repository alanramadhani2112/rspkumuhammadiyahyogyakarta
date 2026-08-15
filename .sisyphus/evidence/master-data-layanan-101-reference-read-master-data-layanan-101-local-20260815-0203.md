# Reference Read Evidence

Batch: `master-data-layanan-101-local-20260815-0203`
Mode: read-only reference review
DB writes: `0`

## Files Read

- `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-post-types.php`
- `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php`
- `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php`
- `.sisyphus/tools/reconcile-source-2026.php`
- `.sisyphus/drafts/reconcile-source-2026-approvals.review.json`
- `.sisyphus/evidence/reconcile-apply-manifest-source-2026-batch1-20260814.json`

## Findings

- CPT owner: `RSPKU_CPT_PostTypes` in `class-rspku-cpt-post-types.php`.
- Target CPTs confirmed:
  - `poliklinik`, registered in `polyclinic()` with public archive `/poliklinik`.
  - `layanan`, registered in `service()` with public archive `/layanan`.
  - `rawat-inap`, registered by same CPT class.
- Taxonomy owner: `RSPKU_CPT_Taxonomies` in `class-rspku-cpt-taxonomies.php`.
- Taxonomy hook: `RSPKU_CPT_Taxonomies::register()` adds `registerTaxonomies` on `init` priority `100`.
- Existing taxonomy patterns:
  - `spesialisasi-dokter`: public, REST, hierarchical, attached to `dokter`.
  - `kategori-layanan`: public, REST, hierarchical, attached only to `layanan`, public rewrite slug `layanan-medis`.
  - `jenis-konsultasi`: public, REST, non-hierarchical, attached to `dokter`.
- Legacy `/kategori-layanan/...` route support is handled in `RSPKU_CPT_Taxonomies` and must remain untouched.
- Recommended insertion point for `master-layanan-medis`: inside `RSPKU_CPT_Taxonomies::registerTaxonomies()`, after `kategori-layanan` and before `jenis-konsultasi`, using existing `self::labels()` helper.
- Theme `TemplateController::serviceArchiveContext()` and `umbrellaServiceGroups()` use curated display grouping. New taxonomy must not drive public grouping until later explicit switch.
- Prior reconcile command `.sisyphus/tools/reconcile-source-2026.php` provides patterns for default read-only behavior, args (`--preflight`, `--apply`, `--approved-file`, `--batch-id`), manifest writing, and guarded draft creation.
- Prior approval JSON shape has top-level `batch_id`, `mode`, `default_safe_noop`, `allowed_decisions`, `counts`, `approvals`.
- Prior local apply manifest confirms batch `source-2026-batch1-20260814` created 8 drafts, updated `0`, deleted existing `0`.
- Prior local draft IDs `20749` through `20756` are local evidence only; UAT must match by stable identity/title/manual approval, not numeric IDs.

## Implementation Notes

- Register new taxonomy slug `master-layanan-medis` on post types `['layanan', 'poliklinik', 'rawat-inap']`.
- Keep `show_in_rest => true`, `hierarchical => true` for admin/audit use.
- Keep public front-end behavior isolated from the new taxonomy.
- Do not create, edit, delete, merge, or assign existing `kategori-layanan` from the importer.
