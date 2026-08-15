# Local Apply Verification — master-data-layanan-101-local-20260815-0203

- Source: `C:\Users\LENOVO\Downloads\master-data-layanan-medis-rs-pku-muhammadiyah-yogyakarta.xlsx`
- Source SHA256: `D7F3F839594CBFD71F5D9BAC32CFE2F813074A9748C14395048C4B72249CD48A`
- Approval file: `.sisyphus/drafts/master-data-layanan-101-approvals.master-data-layanan-101-local-20260815-0203.json`
- Apply manifest: `.sisyphus/evidence/master-data-layanan-101-apply-master-data-layanan-101-local-20260815-0203.json`
- Rollback manifest: `.sisyphus/evidence/master-data-layanan-101-rollback-master-data-layanan-101-local-20260815-0203.json`

## Manifest Results

- `db_writes`: `0`
- `created_count`: `0`
- `updated_count`: `0`
- `assigned_count`: `0`
- `deleted_existing_count`: `0`
- `published_count`: `0`
- Reason: current approval package has `104` rows, all `decision=skip`; no actionable local approval.

## WordPress Counts After Apply Command

- `layanan:publish=15`
- `layanan:draft=3`
- `poliklinik:publish=37`
- `poliklinik:draft=5`
- `rawat-inap:publish=1`
- `rawat-inap:draft=0`

## Route Checks

- `http://rspkudev.test/layanan/` => `200`
- `http://rspkudev.test/poliklinik/` => `200`
- `http://rspkudev.test/layanan/ambulans/` => `200`
- `http://rspkudev.test/dokter/` => `200`
- `http://rspkudev.test/jadwal-dokter/` => `200`

## Scope

- No UAT writes.
- No production writes.
- No publish.
- No delete.
- No `kategori-layanan` mutation.
