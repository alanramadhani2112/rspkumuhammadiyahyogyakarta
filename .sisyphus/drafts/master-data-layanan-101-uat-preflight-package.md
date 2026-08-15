# Master Data Layanan 101 UAT Preflight Package

Batch: `master-data-layanan-101-local-20260815-0203`

Purpose: prepare UAT preflight only. This package stops before any UAT apply, publish, delete, taxonomy mutation, production action, or deploy outside the preflight package.

Required gate: `UAT APPLY REQUIRES SEPARATE EXPLICIT APPROVAL`

## Source

1. Source XLSX: `C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx`
2. Source SHA256: `D7F3F839594CBFD71F5D9BAC32CFE2F813074A9748C14395048C4B72249CD48A`
3. Source size from local evidence: `12590` bytes

## Files To Transfer Or Review For UAT

1. Importer: `.sisyphus/tools/import-master-data-layanan-101.php`
2. Approval JSON: `.sisyphus/drafts/master-data-layanan-101-approvals.master-data-layanan-101-local-20260815-0203.json`
3. Mapping evidence: `.sisyphus/evidence/master-data-layanan-101-mapping-master-data-layanan-101-local-20260815-0203.json`
4. Preflight evidence reference: `.sisyphus/evidence/master-data-layanan-101-preflight-master-data-layanan-101-local-20260815-0203.json`
5. Apply evidence reference: `.sisyphus/evidence/master-data-layanan-101-apply-master-data-layanan-101-local-20260815-0203.json`
6. Rollback evidence reference: `.sisyphus/evidence/master-data-layanan-101-rollback-master-data-layanan-101-local-20260815-0203.json`

## UAT Sequence

1. Back up the UAT database first. Record the backup path in the UAT change notes before running importer commands.
2. Upload `.sisyphus/tools/import-master-data-layanan-101.php` and the approval JSON to UAT, or check out the code version that contains the importer.
3. Confirm taxonomy `master-layanan-medis` is registered on UAT for `layanan`, `poliklinik`, and `rawat-inap`.
4. Confirm taxonomy `kategori-layanan` is unchanged. Do not mutate `kategori-layanan`.
5. Run mapping/default read-only mode on UAT:

```bash
php .sisyphus/tools/import-master-data-layanan-101.php --source "C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx" --batch-id master-data-layanan-101-local-20260815-0203
```

6. Generate or review approval file `.sisyphus/drafts/master-data-layanan-101-approvals.master-data-layanan-101-local-20260815-0203.json`. Expected current local file has 104 approval rows, all `skip`.
7. Run UAT preflight only:

```bash
php .sisyphus/tools/import-master-data-layanan-101.php --source "C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx" --batch-id master-data-layanan-101-local-20260815-0203 --approved-file .sisyphus/drafts/master-data-layanan-101-approvals.master-data-layanan-101-local-20260815-0203.json --preflight
```

8. Review the generated UAT preflight artifact. Confirm `db_writes=0`, counts match, approval totals match, and `apply_ready` reflects the UAT approval decisions.
9. Stop. Do not apply.

## Expected UAT Preflight Outcomes

1. `db_writes=0`
2. Approval rows: `104`
3. Target counts: `poliklinik=40`, `layanan=59`, `rawat-inap=5`
4. `apply_ready=false` if approval decisions remain all `skip`
5. `apply_ready` depends on explicit UAT approval decisions in the approval JSON
6. No writes occur during default read-only mode or `--preflight`

## Forbidden Actions

1. Do not run `--apply`.
2. Do not publish posts.
3. Do not delete posts.
4. Do not mutate `kategori-layanan`.
5. Do not run against production.
6. Do not deploy beyond the UAT preflight package.
7. Do not modify the WordPress database during this UAT preflight.

## Rollback Readiness

1. Create a UAT database backup before preflight. Record the exact backup path in the UAT change notes.
2. Keep rollback evidence format aligned with `.sisyphus/evidence/master-data-layanan-101-rollback-master-data-layanan-101-local-20260815-0203.json`.
3. A rollback manifest is required before any future UAT apply.
4. Future apply and rollback both require separate explicit approval.

## Approval Gate

Preflight may run after DB backup and package review. UAT apply may not run from this package.

`UAT APPLY REQUIRES SEPARATE EXPLICIT APPROVAL`
