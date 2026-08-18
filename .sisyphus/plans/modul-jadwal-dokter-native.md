# Modul Jadwal Dokter Native

## TL;DR
> **Summary**: Ganti TablePress ID `1` dengan modul jadwal dokter WordPress-native. `dokter` tetap entity utama, `spesialisasi-dokter` tetap taxonomy canonical, jadwal disimpan pada structured post meta dokter.
> **Deliverables**: submenu jadwal terpusat, repository native, importer TablePress satu kali, migrasi tervalidasi, frontend native-only, self-check regresi, dokumentasi admin.
> **Effort**: Large
> **Parallel**: YES - 4 waves
> **Critical Path**: Task 1 → Task 2 → Task 4 → Task 6 → Task 7

## Context

### Original Request
Membuat modul jadwal dokter sendiri yang sinkron dengan profil dokter dan taxonomy spesialisasi, sehingga admin tidak perlu mengelola jadwal melalui TablePress.

### Interview Summary
- UI utama: tabel terpusat pada `Dokter > Jadwal Dokter`.
- Entity dokter: CPT `dokter`; tidak membuat CPT jadwal baru.
- Spesialisasi: taxonomy `spesialisasi-dokter`; term hanya dibuat dari halaman taxonomy canonical.
- Satu dokter boleh memiliki beberapa spesialisasi.
- Jadwal disimpan dalam structured post meta dokter.
- TablePress: import sekali, validasi, lalu runtime native-only.
- Dokter tanpa jadwal tetap publish dan tampil dengan `Jadwal belum tersedia`.
- Test: tests-after minimal melalui self-check/WP-CLI dan QA browser agent.

### Metis Review (gaps addressed)
- Import dibuat eksplisit, dry-run, idempotent, dan tidak berjalan pada page load.
- Existing meta dan TablePress diekspor sebelum migrasi.
- Data ambigu/invalid tidak diimpor diam-diam; ditampilkan dalam laporan review.
- Dokter dicocokkan secara deterministik; konflik nama tidak boleh membuat duplikasi otomatis.
- Save jadwal tidak boleh mengubah foto, bio, konten, status post, atau meta non-jadwal.
- Runtime native harus tetap berfungsi ketika TablePress nonaktif/tidak tersedia.
- Hak akses, nonce, sanitasi, validasi waktu, keyboard navigation, dan feedback error wajib.

## Work Objectives

### Core Objective
Menyediakan satu sumber data jadwal dokter yang WordPress-native, terhubung langsung dengan CPT dokter dan taxonomy spesialisasi, tanpa dependensi runtime terhadap TablePress.

### Deliverables
- Structured schedule schema dan service/repository native.
- Submenu admin `Dokter > Jadwal Dokter`.
- Pilihan dokter existing dan term spesialisasi existing.
- CRUD jadwal dengan validasi hari/jam.
- Importer TablePress ID `1`: dry-run, commit, report, idempotency.
- Frontend `/jadwal-dokter/` dan profil dokter membaca meta native.
- Status empty schedule tanpa unpublish dokter.
- Self-check WP-CLI dan panduan admin/migrasi.

### Definition of Done
- `php -l` bersih untuk seluruh PHP yang disentuh.
- LSP diagnostics error = 0 untuk PHP/JS yang disentuh.
- `npm run build` sukses pada `wp-content/themes/rspku-theme` bila asset berubah.
- Import dry-run tidak menulis DB; import commit dapat dijalankan ulang tanpa duplikasi.
- TablePress dinonaktifkan pada environment uji dan `/jadwal-dokter/`, `/dokter/`, profil dokter tetap berfungsi.
- Dokter baru + spesialisasi existing + jadwal dapat dibuat melalui admin tanpa TablePress.
- Empty schedule tidak mengubah status dokter dan menampilkan pesan yang benar.
- Backup, rollback, dan laporan migrasi tersedia sebelum cutover.

### Must Have
- `manage_options` atau capability dokter yang setara; gunakan capability existing paling sempit yang sudah berlaku pada CPT dokter.
- Nonce pada setiap mutasi.
- Sanitasi dan validasi server-side.
- Format waktu canonical `HH:MM`; label bebas hanya dipertahankan sebagai catatan terpisah bila diperlukan.
- Hari dibatasi pada Senin-Minggu/key existing.
- Term spesialisasi wajib existing dan valid untuk taxonomy `spesialisasi-dokter`.
- Save jadwal bersifat atomic per dokter.
- Cache dokter dibersihkan setelah mutasi/import.

### Must NOT Have
- Custom database table.
- CPT jadwal baru.
- ACF repeater baru.
- Pembuatan spesialisasi inline.
- Runtime fallback permanen ke TablePress.
- Auto-trash/auto-draft dokter saat jadwal kosong.
- Matching dokter ambigu berdasarkan nama tanpa laporan/manual resolution.
- Perubahan layout frontend besar.

## Verification Strategy
> ZERO HUMAN INTERVENTION untuk pemeriksaan teknis; review data migrasi menghasilkan laporan agent-readable.
- Test decision: tests-after minimal menggunakan script WP-CLI/self-check PHP tanpa framework baru.
- QA policy: setiap task memiliki happy path dan failure path agent-executed.
- Evidence: `.sisyphus/evidence/task-{N}-{slug}.{ext}`.

## Execution Strategy

### Parallel Execution Waves
Wave 1: Task 1 data contract; Task 3 admin UI shell; Task 5 migration audit/self-check foundation.
Wave 2: Task 2 native repository; Task 4 persistence/validation.
Wave 3: Task 6 importer + cutover; Task 7 frontend integration.
Wave 4: Task 8 documentation/final regression.

### Dependency Matrix
| Task | Blocked By | Blocks |
|---|---|---|
| 1 | - | 2, 4, 5, 6 |
| 2 | 1 | 4, 6, 7 |
| 3 | - | 4 |
| 4 | 1, 2, 3 | 6, 7 |
| 5 | 1 | 6, 8 |
| 6 | 1, 2, 4, 5 | 7, 8 |
| 7 | 2, 4, 6 | 8 |
| 8 | 5, 6, 7 | Final Verification |

### Agent Dispatch Summary
- Wave 1: 3 tasks — backend, frontend/admin, testing.
- Wave 2: 2 tasks — backend/data persistence.
- Wave 3: 2 tasks — migration, frontend integration.
- Wave 4: 1 task — documentation/regression.

## TODOs

- [x] 1. Tetapkan kontrak data jadwal native

  **What to do**:
  - Definisikan satu schema canonical untuk `_rspku_doctor_schedule`: array slot berisi `day`, `day_label`, `start_time`, `end_time`, `label`, `specialization_term_id`, dan optional `note`.
  - Gunakan day key existing `monday` sampai `sunday` agar kompatibel dengan filter/meta `_rspku_schedule_day`.
  - Tetapkan normalisasi jam `HH:MM`; rentang valid bila start < end. Jadwal teks non-rentang dari TablePress masuk laporan invalid/manual-review, bukan disimpan sebagai slot palsu.
  - Definisikan aturan multi-spesialisasi: setiap slot menunjuk satu term existing; post dokter menerima union seluruh term yang dipakai jadwal tanpa menghapus term dokter curated yang tidak berasal dari jadwal.
  - Pertahankan alias meta `jadwal_praktek` hanya selama kompatibilitas; `_rspku_doctor_schedule` menjadi canonical.

  **Must NOT do**: Jangan membuat table/CPT/ACF schema baru; jangan bergantung pada nama term sebagai foreign key.

  **Recommended Agent Profile**:
  - Category: `implementation` - kontrak backend bounded.
  - Skills: [`senior-backend`] - validasi/data contract.
  - Omitted: [`database-designer`] - tidak ada schema DB baru.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: 2, 4, 5, 6 | Blocked By: none

  **References**:
  - Pattern: `wp-content/themes/rspku-theme/app/Repositories/DoctorScheduleRepository.php:DoctorScheduleRepository::parseRow()` - bentuk record/day/slot existing.
  - Pattern: `wp-content/themes/rspku-theme/app/Services/DoctorDirectorySync.php:DoctorDirectorySync::syncDoctorMeta()` - meta canonical/compatibility yang sekarang ditulis.
  - API/Type: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-fields.php:RSPKU_CPT_DoctorFields::registerPostMeta()` - REST/schema meta existing.
  - Consumer: `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php:DoctorRepository::schedule()` - kontrak yang dipakai profil dokter.

  **Acceptance Criteria**:
  - [ ] Schema terdokumentasi dalam docblock/type array dan digunakan konsisten oleh sanitizer, repository, importer.
  - [ ] Invalid day, invalid term ID, malformed time, dan start >= end ditolak dengan pesan spesifik.

  **QA Scenarios**:
  ```
  Scenario: Slot valid dinormalisasi
    Tool: WP-CLI self-check
    Steps: Beri payload Senin 08.00-10.30 dengan term spesialisasi valid.
    Expected: day=monday, start_time=08:00, end_time=10:30, term ID dipertahankan.
    Evidence: .sisyphus/evidence/task-1-schedule-contract.txt

  Scenario: Slot invalid ditolak
    Tool: WP-CLI self-check
    Steps: Beri day=holiday, term tidak ada, start=12:00, end=09:00.
    Expected: Tidak ada slot tersimpan; tiap invalid field dilaporkan.
    Evidence: .sisyphus/evidence/task-1-schedule-contract-error.txt
  ```

  **Commit**: YES | Message: `refactor(doctors): define native schedule contract` | Files: repository/service/meta schema files

- [x] 2. Ubah repository jadwal menjadi native meta source

  **What to do**:
  - Refactor `DoctorScheduleRepository` agar mengambil dokter publish dan structured post meta, bukan `TablePress::load_model()`.
  - Pertahankan public methods yang dipakai controller/template: `records()`, `summary()`, `dayHeaders()`, `specializations()`, `specializationGroups()`.
  - Hapus `TABLE_ID`, `table()`, TablePress import/runtime dependency dari repository native.
  - Record native memuat doctor post ID/URL, title, specialties, dan slots; dokter tanpa jadwal tetap tersedia bagi profile/archive tetapi tidak dihitung sebagai scheduled row.
  - Tambahkan invalidation hook/service setelah save/import.

  **Must NOT do**: Jangan membaca TablePress sebagai fallback runtime; jangan mengubah template contract tanpa adapter.

  **Recommended Agent Profile**:
  - Category: `implementation` - repository refactor.
  - Skills: [`senior-backend`] - query/cache correctness.
  - Omitted: [`sql-database-assistant`] - WP_Query/meta cukup.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: 4, 6, 7 | Blocked By: 1

  **References**:
  - Target: `wp-content/themes/rspku-theme/app/Repositories/DoctorScheduleRepository.php` - public contract existing.
  - Pattern: `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php` - doctor query, normalization, cache group/flush.
  - Consumer: `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php` - schedule context assembly.
  - View: `wp-content/themes/rspku-theme/resources/views/pages/page-jadwal-dokter.twig` - expected rows/groups/day headers.

  **Acceptance Criteria**:
  - [ ] Tidak ada import/use/runtime call TablePress pada repository native.
  - [ ] Public schedule contract tetap merender halaman existing.
  - [ ] TablePress inactive tidak menimbulkan fatal atau empty page palsu jika meta native ada.

  **QA Scenarios**:
  ```
  Scenario: Repository membaca meta native
    Tool: WP-CLI self-check
    Steps: Seed satu dokter dengan dua slot dan dua spesialisasi; panggil records/summary/groups.
    Expected: Dokter muncul sekali, dua slot tersedia, filter dua term tersedia.
    Evidence: .sisyphus/evidence/task-2-native-repository.json

  Scenario: TablePress tidak tersedia
    Tool: WP-CLI
    Steps: Jalankan repository dalam context tanpa class TablePress setelah meta native tersedia.
    Expected: Tidak fatal; records sama dengan meta native.
    Evidence: .sisyphus/evidence/task-2-no-tablepress.txt
  ```

  **Commit**: YES | Message: `refactor(doctors): read schedules from native meta` | Files: `DoctorScheduleRepository.php`, related controller/cache files

- [x] 3. Buat submenu dan tabel admin Jadwal Dokter

  **What to do**:
  - Tambahkan submenu `Dokter > Jadwal Dokter` pada plugin `rspku-cpt`.
  - Tabel menampilkan dokter, spesialisasi terpasang, ringkasan hari/jam, status jadwal, tombol edit.
  - Editor terpusat memilih dokter existing, term `spesialisasi-dokter` existing, hari, jam mulai/selesai, optional catatan; dapat tambah/hapus slot.
  - Sediakan tautan `Kelola Spesialisasi` ke `edit-tags.php?taxonomy=spesialisasi-dokter&post_type=dokter`.
  - Tampilkan empty state, validation summary, success notice, dan unsaved-change protection sederhana.
  - Gunakan semantic table/form, label, keyboard focus, `aria-live` untuk error/status.

  **Must NOT do**: Jangan membuat dokter/spesialisasi inline; jangan memasukkan inline JS besar dalam PHP.

  **Recommended Agent Profile**:
  - Category: `visual-engineering` - admin UX/accessibility.
  - Skills: [`frontend-ui-ux`] - hierarchy/form UX.
  - Omitted: [`impeccable-style`] - bukan redesign premium.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: 4 | Blocked By: none

  **References**:
  - Bootstrap: `wp-content/plugins/rspku-cpt/rspku-cpt.php` - class loading/register pattern.
  - Admin form: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-fields.php:RSPKU_CPT_DoctorFields::renderMetaBox()`.
  - JS: `wp-content/themes/rspku-theme/resources/js/admin.js` - schedule row add/remove pattern.
  - CSS: `wp-content/themes/rspku-theme/resources/css/admin.css` - structured CPT admin styles.
  - Assets: `wp-content/themes/rspku-theme/app/Setup/Assets.php` - admin enqueue gating.

  **Acceptance Criteria**:
  - [ ] Submenu hanya terlihat bagi user dengan capability yang ditetapkan.
  - [ ] Semua controls berlabel dan dapat dioperasikan keyboard.
  - [ ] Term select hanya berisi taxonomy canonical; link kelola taxonomy benar.

  **QA Scenarios**:
  ```
  Scenario: Admin mengedit jadwal terpusat
    Tool: Playwright
    Steps: Login admin; buka Dokter > Jadwal Dokter; pilih dokter; tambah slot; pilih term existing.
    Expected: Form lengkap, keyboard usable, save notice muncul, nilai tetap setelah reload.
    Evidence: .sisyphus/evidence/task-3-admin-schedule.png

  Scenario: User tanpa capability
    Tool: WP-CLI + Playwright
    Steps: Login sebagai role tanpa hak manage doctor schedules; akses URL submenu langsung.
    Expected: Menu tidak tampil; direct access ditolak dengan pesan WordPress.
    Evidence: .sisyphus/evidence/task-3-admin-permission.txt
  ```

  **Commit**: YES | Message: `feat(doctors): add centralized schedule admin` | Files: new plugin admin class, bootstrap, admin JS/CSS/assets

- [x] 4. Implementasikan save, validasi, taxonomy, dan cache invalidation

  **What to do**:
  - Buat handler save terpusat menggunakan POST admin action, capability check, nonce, absint post/term IDs, sanitizer kontrak Task 1.
  - Simpan schedule canonical dan compatibility meta secara atomic per dokter; update indexed day meta.
  - Set union term spesialisasi yang dipilih jadwal tanpa menghapus term curated yang tidak dikelola modul; tandai term IDs managed-by-schedule pada meta internal agar update berikutnya deterministik.
  - Empty schedule menghapus schedule/day managed meta tetapi mempertahankan post publish, content, photo, biography, dan taxonomy curated.
  - Flush `DoctorRepository` cache setelah save.
  - Pertahankan editor jadwal pada doctor meta box sebagai read-only summary + link ke submenu untuk mencegah dua writer.

  **Must NOT do**: Jangan memanggil `wp_set_object_terms(..., false)` dengan hanya term jadwal tanpa merge; jangan trash/draft dokter.

  **Recommended Agent Profile**:
  - Category: `implementation` - WordPress mutation/security.
  - Skills: [`senior-backend`] - robust handlers.
  - Omitted: [`senior-security`] - standard WP boundary controls cukup; final security review tetap ada.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: 6, 7 | Blocked By: 1, 2, 3

  **References**:
  - Save pattern: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-fields.php:RSPKU_CPT_DoctorFields::save()`.
  - Sanitizer pattern: same file `sanitizeSchedule()`/`replaceIndexedMeta()`.
  - Taxonomy: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php`.
  - Cache: `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php:flushCache()`.
  - Existing sync behavior: `wp-content/themes/rspku-theme/app/Services/DoctorDirectorySync.php:syncDoctorMeta()`/`syncDoctorTerms()`.

  **Acceptance Criteria**:
  - [ ] Valid save persists after reload and frontend cache refresh.
  - [ ] Invalid payload writes nothing.
  - [ ] Empty schedule leaves doctor publish and non-schedule fields byte-equivalent.
  - [ ] Multi-specialization terms persist without deleting curated terms.

  **QA Scenarios**:
  ```
  Scenario: Save valid multi-specialization schedule
    Tool: Playwright + WP-CLI
    Steps: Save two slots using two existing terms; inspect post meta and terms.
    Expected: Canonical meta has two normalized slots; both taxonomy terms attached; indexed days correct.
    Evidence: .sisyphus/evidence/task-4-save-valid.json

  Scenario: Tampered request rejected
    Tool: WP-CLI/curl authenticated test
    Steps: Kirim nonce invalid, foreign post type ID, nonexistent term, malformed times.
    Expected: DB unchanged; permission/validation error surfaced; no partial write.
    Evidence: .sisyphus/evidence/task-4-save-invalid.txt
  ```

  **Commit**: YES | Message: `feat(doctors): persist native schedules safely` | Files: plugin save/service/meta box files

- [x] 5. Buat audit, backup, dan self-check migrasi

  **What to do**:
  - Tambahkan runnable WP-CLI/eval-file self-check tanpa framework baru.
  - Audit TablePress ID `1`: valid rows, invalid rows, duplicate names, unmatched doctors, ambiguous matches, malformed days/times, unknown specializations.
  - Export TablePress CSV/JSON dan snapshot schedule/taxonomy/meta dokter sebelum commit import.
  - Buat output JSON machine-readable berisi counts dan row-level reasons.
  - Definisikan rollback script yang memulihkan snapshot meta/terms dan source mode bila import dibatalkan.

  **Must NOT do**: Audit/dry-run tidak boleh menulis posts/meta/terms/options.

  **Recommended Agent Profile**:
  - Category: `testing` - migration verification.
  - Skills: [`tdd-guide`] - self-check/edge coverage.
  - Omitted: [`db-verifier`] - WP-CLI API lebih aman daripada direct DB mutation.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: 6, 8 | Blocked By: 1

  **References**:
  - Parser: `wp-content/themes/rspku-theme/app/Repositories/DoctorScheduleRepository.php` current TablePress behavior.
  - Matcher: `wp-content/themes/rspku-theme/app/Services/DoctorDirectorySync.php:matchExistingDoctor()`.
  - Existing scripts: `wp-content/themes/rspku-theme/scripts/sync-doctor-profiles-from-tsv.php`, `audit-doctor-profile-sync.php`.
  - Backup convention: server `/home/pkujogja/deploy-backups/` from established deployment workflow.

  **Acceptance Criteria**:
  - [ ] Dry-run before/after database checksum/counts membuktikan zero writes.
  - [ ] Every skipped row memiliki reason.
  - [ ] Backup dan rollback command diverifikasi pada satu fixture doctor.

  **QA Scenarios**:
  ```
  Scenario: Audit valid source
    Tool: WP-CLI
    Steps: Jalankan dry-run pada TablePress ID 1.
    Expected: JSON valid berisi importable, skipped, ambiguous, invalid; DB state tidak berubah.
    Evidence: .sisyphus/evidence/task-5-import-dry-run.json

  Scenario: Ambiguous duplicate doctor
    Tool: WP-CLI fixture/self-check
    Steps: Sediakan dua post dokter dengan normalized name sama.
    Expected: Row masuk ambiguous; tidak ada post/meta berubah.
    Evidence: .sisyphus/evidence/task-5-import-ambiguous.json
  ```

  **Commit**: YES | Message: `test(doctors): add schedule migration checks` | Files: migration/self-check scripts

- [x] 6. Implementasikan importer TablePress satu kali dan cutover native-only

  **What to do**:
  - Implement explicit import command/admin action dengan mode dry-run default dan commit flag eksplisit.
  - Match existing doctor deterministik memakai source name/title/meta; ambiguous rows wajib manual map, bukan auto-create.
  - Existing doctor: update schedule managed meta dan attach existing specialization terms. New doctor: create only untuk unique valid unmatched row setelah explicit commit; publish, tandai imported source, jangan fabricate photo/credentials.
  - Unknown specialization tidak dibuat importer; row masuk blocked report sampai term dibuat di taxonomy admin, lalu import diulang.
  - Record import metadata: timestamp, source table ID, source hash, counts, operator ID, backup path/reference.
  - Setelah successful validation, unregister/remove runtime `DoctorDirectorySync` dan TablePress coupling; importer dipertahankan sebagai maintenance command selama rollback window.
  - Tandai TablePress table read-only secara prosedural/dokumentasi; jangan delete table/plugin dalam task ini.

  **Must NOT do**: Jangan auto-create taxonomy; jangan auto-resolve ambiguity; jangan delete source TablePress.

  **Recommended Agent Profile**:
  - Category: `implementation` - high-risk migration.
  - Skills: [`migration-architect`, `senior-backend`] - idempotency/rollback.
  - Omitted: [`database-schema-designer`] - no schema migration.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: 7, 8 | Blocked By: 1, 2, 4, 5

  **References**:
  - Source adapter: `wp-content/themes/rspku-theme/app/Repositories/DoctorScheduleRepository.php` before native refactor/history.
  - Existing sync: `wp-content/themes/rspku-theme/app/Services/DoctorDirectorySync.php`.
  - Bootstrap removal: `wp-content/themes/rspku-theme/app/Theme.php` `DoctorDirectorySync::register()`.
  - Post/meta/taxonomy APIs: `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-doctor-fields.php`, `class-rspku-cpt-taxonomies.php`.

  **Acceptance Criteria**:
  - [ ] Commit import refuses to run without clean dry-run/explicit override for reviewed blocked rows.
  - [ ] Rerun dengan source hash sama menghasilkan zero duplicate posts/slots.
  - [ ] New unknown specialization blocks row; setelah term dibuat, rerun imports row correctly.
  - [ ] Runtime code tidak bergantung pada TablePress setelah cutover.

  **QA Scenarios**:
  ```
  Scenario: Idempotent import dan cutover
    Tool: WP-CLI
    Steps: Backup; dry-run; commit import; commit import kedua; disable TablePress; run repository self-check.
    Expected: Import pertama sesuai counts; kedua zero additions; native records tetap tersedia tanpa TablePress.
    Evidence: .sisyphus/evidence/task-6-import-cutover.json

  Scenario: Unknown specialization blocked
    Tool: WP-CLI
    Steps: Import fixture dengan term yang belum ada.
    Expected: Tidak membuat term/post; blocked reason mencantumkan taxonomy action URL/instruction.
    Evidence: .sisyphus/evidence/task-6-unknown-specialization.json
  ```

  **Commit**: YES | Message: `feat(doctors): migrate schedules off TablePress` | Files: importer, sync/bootstrap cleanup, option/report helpers

- [x] 7. Integrasikan frontend jadwal dan profil dokter

  **What to do**:
  - Pastikan `/jadwal-dokter/` menggunakan native repository contract dan filter nama/spesialisasi/hari tetap bekerja.
  - Profil dokter menggunakan schedule meta native; multi-specialization tampil konsisten.
  - Dokter publish tanpa slot menampilkan `Jadwal belum tersedia`, bukan hilang/404.
  - Ubah copy frontend yang menyebut “tabel operasional/TablePress” menjadi “data jadwal resmi”.
  - Pastikan schema physician/search/API menerima schedule native tanpa perubahan kontrak eksternal yang tidak perlu.
  - Pastikan cache invalidation membuat perubahan admin terlihat setelah save.

  **Must NOT do**: Jangan redesign layout; jangan expose admin/internal import metadata.

  **Recommended Agent Profile**:
  - Category: `visual-engineering` - frontend behavior/filter/accessibility.
  - Skills: [`frontend-ui-ux`] - empty/error/filter states.
  - Omitted: [`epic-design`] - no cinematic redesign.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: 8 | Blocked By: 2, 4, 6

  **References**:
  - Controller: `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php`.
  - Schedule view: `wp-content/themes/rspku-theme/resources/views/pages/page-jadwal-dokter.twig`.
  - Profile view: `wp-content/themes/rspku-theme/resources/views/pages/single-doctor.twig`.
  - Doctor model: `wp-content/themes/rspku-theme/app/Repositories/DoctorRepository.php`.
  - Search: `wp-content/themes/rspku-theme/app/Services/DoctorSearch.php`, `resources/js/app.js`.
  - Schema: `wp-content/plugins/rspku-schema/includes/class-rspku-schema-physician.php`.

  **Acceptance Criteria**:
  - [ ] Schedule/profile/search pages render 200 with TablePress inactive.
  - [ ] Filter multi-specialization works; no duplicate doctor cards/rows.
  - [ ] Empty schedule message is visible and accessible.
  - [ ] No broken assets, console errors, PHP fatal/warning baru.

  **QA Scenarios**:
  ```
  Scenario: Native schedule visible end-to-end
    Tool: Playwright
    Steps: Save schedule admin; buka `/jadwal-dokter/`; filter dokter/spesialisasi; buka profil dokter.
    Expected: Jadwal terbaru identik pada list/profile; filter benar; no console errors.
    Evidence: .sisyphus/evidence/task-7-native-frontend.png

  Scenario: Published doctor tanpa jadwal
    Tool: Playwright
    Steps: Hapus semua slot dokter fixture; buka archive dan profil.
    Expected: Profil tetap 200/publish; teks `Jadwal belum tersedia`; tidak muncul pada count dokter terjadwal.
    Evidence: .sisyphus/evidence/task-7-empty-schedule.png
  ```

  **Commit**: YES | Message: `feat(doctors): render native schedule data` | Files: controller/repository/views/search/schema as required

- [x] 8. Finalisasi dokumentasi, regression self-check, dan runbook rollback

  **What to do**:
  - Update panduan admin: tambah dokter, kelola spesialisasi canonical, edit jadwal terpusat, empty schedule, troubleshooting.
  - Dokumentasikan bahwa TablePress tidak lagi source runtime dan tidak boleh diedit setelah cutover.
  - Tambahkan command self-check final untuk counts, orphan term IDs, invalid slots, duplicate normalized doctor names, source dependency grep.
  - Dokumentasikan rollback: restore backup meta/terms/options, re-enable legacy source hanya melalui rollback commit/deploy, flush cache/rewrite.
  - Jalankan smoke suite public/admin dan simpan evidence.

  **Must NOT do**: Jangan memasukkan credential, hidden login slug, server key/path rahasia ke docs repo.

  **Recommended Agent Profile**:
  - Category: `testing` - regression/runbook proof.
  - Skills: [`ocs-test-regression-guard`, `runbook-generator`] - concise checks/rollback.
  - Omitted: [`senior-qa`] - stack bukan React/Next test generation.

  **Parallelization**: Can Parallel: NO | Wave 4 | Blocks: Final Verification | Blocked By: 5, 6, 7

  **References**:
  - Guide: `docs/panduan-admin-wordpress-rspku.md`.
  - Existing scripts: `wp-content/themes/rspku-theme/scripts/`.
  - Plugin guide pattern: `wp-content/plugins/rspku-cpt/rspku-cpt.php` and admin labels.
  - Deployment backup convention: `/home/pkujogja/deploy-backups/` (server only; do not commit server credentials).

  **Acceptance Criteria**:
  - [ ] Admin guide no longer instructs TablePress schedule editing.
  - [ ] Self-check exits nonzero for invalid/orphan/duplicate data and zero for clean state.
  - [ ] Secret scan on docs/diff returns zero findings.
  - [ ] Rollback steps executable and reference actual backup artifacts created at deployment.

  **QA Scenarios**:
  ```
  Scenario: Final clean self-check
    Tool: WP-CLI + Bash
    Steps: Run PHP lint, build if changed, migration self-check, dependency grep, secret scan.
    Expected: Semua exit 0; no runtime TablePress reference; no secrets.
    Evidence: .sisyphus/evidence/task-8-final-check.txt

  Scenario: Self-check detects corruption
    Tool: WP-CLI fixture
    Steps: Inject invalid term ID/time into fixture doctor meta; run self-check; restore fixture.
    Expected: Exit nonzero dengan doctor ID dan precise reason; fixture restored.
    Evidence: .sisyphus/evidence/task-8-corruption-detected.txt
  ```

  **Commit**: YES | Message: `docs(doctors): document native schedule workflow` | Files: docs, self-check/runbook files

## Final Verification Wave
> 4 review agents run in parallel. ALL must APPROVE. Present consolidated results; tunggu explicit user approval sebelum completion.
- [ ] F1. Plan Compliance Audit — oracle
- [ ] F2. Code Quality Review — unspecified-high
- [ ] F3. Real Manual QA — unspecified-high + Playwright
- [ ] F4. Scope Fidelity Check — deep

## Commit Strategy
- Atomic commits mengikuti task boundaries; implementation dan self-check terkait dalam commit yang sama bila diperlukan agar commit tetap runnable.
- Jangan commit `.sisyphus/evidence/`, `.playwright-mcp/`, credential, DB dump, atau backup server.
- Push hanya setelah lint/build/self-check task terkait lulus.
- Deploy setelah backup webroot/plugin/theme + DB/meta snapshot; cache flush dan `nginx -t` setelah extraction.

## Success Criteria
- Admin mengelola dokter, spesialisasi, dan jadwal tanpa membuka TablePress.
- Taxonomy tetap dikelola hanya dari `Dokter > Spesialisasi Dokter`.
- Frontend/profile/search memakai native data dan tetap berjalan tanpa TablePress.
- Migrasi satu kali aman, idempotent, dapat diaudit, dan dapat di-rollback.
- Dokter tanpa jadwal tetap publish tanpa kehilangan data kurasi.
- Tidak ada custom table/CPT/ACF baru atau dual runtime source.
