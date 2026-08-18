# Rencana Implementasi Galeri Dinamis Sejarah Kami

Status: Blocked pada metadata resmi
Tanggal: 2026-08-16
Target eksekusi: `/start-work`
Ruang kerja: `C:\laragon\www\rspkudev`

## 1. TL;DR

Deliverable utama:

- Lima foto arsip untuk `/sejarah-kami`, dapat diedit dari `rspku-settings` memakai WordPress Media Library.
- Lima slot semantik tetap, tanpa plugin baru, CPT, repeater, carousel, dependency, atau REST publik baru.
- Default kosong, sehingga halaman tetap text-only sampai semua metadata resmi lengkap dan disetujui.
- Gambar tampil hanya jika attachment valid dan `year`, `title`, `caption`, `alt` semuanya terisi.
- Derivative foto aman pakai hasil bersih, original PNG tetap tidak disentuh.

Estimasi effort:

- Task 0, metadata dan preprocessing: tergantung user, tidak bisa dipercepat oleh implementasi.
- Task 1 sampai Task 4: 1 sampai 2 sesi fokus.
- Task 5 QA admin dan publik: 1 sesi fokus.
- Task 6 release: hanya setelah approval eksplisit.

Parallel:

- Task 0 bisa berjalan paralel dengan scaffold kode karena default kosong aman.
- Task 1 dan Task 2 berurutan karena context butuh contract settings.
- Task 3 menunggu Task 2.
- Task 4 bisa mulai setelah Task 1 dan Task 2.
- Task 5 menunggu Task 3 dan Task 4.

Critical path:

1. Metadata resmi lima slot disetujui.
2. Settings contract lengkap dan valid.
3. Context menghasilkan hanya slot lengkap.
4. Twig menempatkan foto secara editorial tanpa menebak tahun.
5. QA admin dan publik lulus.

## 2. Konteks

Permintaan asli: halaman `/sejarah-kami` perlu menampilkan 5 foto arsip secara dinamis, bisa diedit melalui sistem `rspku-settings` yang sudah ada dan WordPress Media Library. Implementasi harus tetap sederhana, tanpa plugin baru, CPT, repeater, carousel, dependency, atau hardcoded image URL.

Keputusan user:

- Tepat 5 slot semantik.
- User menyediakan tahun, judul, caption, dan alt resmi untuk setiap slot.
- Foto hasil bersih boleh dibuat sebagai derivative aman.
- Original PNG sumber tidak boleh diubah.
- Tidak boleh menerbitkan atau mengisi seed/import sebelum semua 5 metadata resmi lengkap dan disetujui.
- Jangan pernah menebak tanggal, judul, caption, atau alt dari hasil scan.

Temuan proyek:

- Halaman saat ini: `wp-content/themes/rspku-theme/resources/views/pages/page-sejarah-kami.twig`.
- Halaman saat ini punya hero text-only dan enam milestone timeline hardcoded.
- Context builder: `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php`.
- `historyPageContext()` saat ini hardcode `stats`, `milestones`, `principles`, dan `values`.
- Partial gambar responsif: `wp-content/themes/rspku-theme/resources/views/partials/responsive-image.twig`.
- Partial mendukung `attachment id`, `url`, `alt`, `class`, `sizes`, `width`, `height`, `eager`, `default_size`, `srcset` dari `image_src()`, lazy default kecuali `eager`.
- Registry settings: `wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php`.
- Defaults settings: `wp-content/plugins/rspku-settings/includes/class-rspku-settings-defaults.php`.
- Admin settings: `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`.
- Settings API: `wp-content/plugins/rspku-settings/includes/class-rspku-settings-api.php`.
- Admin JS existing: `wp-content/plugins/rspku-settings/assets/admin.js`, sudah pilih dan hapus attachment untuk field registry `type=image`.

Kesimpulan Metis:

- Pilih manual admin entry sebagai jalur default. Ini paling kecil dan aman.
- Seed script hanya dibuat bila rollout berulang antar environment benar-benar dibutuhkan.
- Scaffold settings dan rendering boleh dikerjakan sebelum metadata resmi, karena default kosong tidak menampilkan gambar publik.
- Upload derivative, pengisian settings, import, seed, atau publishing tetap blocked sampai metadata gate lulus.

## 3. Objectives dan Non-goals

Objectives:

- Tambah tab settings `Sejarah` dengan lima section sesuai urutan cerita.
- Simpan 25 key settings, yaitu 5 image ID dan 20 string metadata.
- Validasi image ID agar hanya attachment gambar valid yang disimpan. Jika tidak valid, simpan `0`.
- Sanitasi `year`, `title`, dan `alt` memakai plain text.
- Sanitasi `caption` memakai multiline-safe sanitizer.
- Bangun array `history_page.gallery` atau `history_page.media` di `historyPageContext()`.
- Render hanya slot lengkap, tanpa broken image dan tanpa blank figure.
- Pertahankan text-only page saat slot kosong atau tidak lengkap.
- Tempatkan foto secara editorial di posisi cerita yang eksplisit, bukan dicocokkan ke tahun milestone.

Non-goals:

- Tidak membuat plugin baru.
- Tidak membuat CPT.
- Tidak membuat repeater.
- Tidak membuat carousel.
- Tidak menambah dependency.
- Tidak memperluas REST publik untuk history fields.
- Tidak mengubah broad history copy.
- Tidak mengganti milestone prose, date, title, atau body yang sudah ada.
- Tidak hardcode image URL.
- Tidak auto-publish slot tidak lengkap.
- Tidak mengubah original PNG.
- Tidak commit, push, deploy, atau mutasi DB tanpa perintah eksplisit.

## 4. Data Contract

Semua key berada dalam opsi existing `rspku_settings` dan diakses via `rspku_setting()`.

| Urutan | Slot | Key | Type | Default | Required untuk publish | Source image semantik |
|---:|---|---|---|---|---|---|
| 1 | Hero bangunan bersejarah | `history_hero_image_id` | image attachment ID | `0` | valid image attachment | `D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-1.png` |
| 1 | Hero bangunan bersejarah | `history_hero_year` | string | `""` | non-empty | historic building/hero |
| 1 | Hero bangunan bersejarah | `history_hero_title` | string | `""` | non-empty | historic building/hero |
| 1 | Hero bangunan bersejarah | `history_hero_caption` | multiline string | `""` | non-empty | historic building/hero |
| 1 | Hero bangunan bersejarah | `history_hero_alt` | string | `""` | non-empty | historic building/hero |
| 2 | Pionir dan tokoh awal | `history_pioneers_image_id` | image attachment ID | `0` | valid image attachment | `D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-5.png` |
| 2 | Pionir dan tokoh awal | `history_pioneers_year` | string | `""` | non-empty | pioneers/group |
| 2 | Pionir dan tokoh awal | `history_pioneers_title` | string | `""` | non-empty | pioneers/group |
| 2 | Pionir dan tokoh awal | `history_pioneers_caption` | multiline string | `""` | non-empty | pioneers/group |
| 2 | Pionir dan tokoh awal | `history_pioneers_alt` | string | `""` | non-empty | pioneers/group |
| 3 | Layanan anak awal | `history_child_service_image_id` | image attachment ID | `0` | valid image attachment | `D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-3.png` |
| 3 | Layanan anak awal | `history_child_service_year` | string | `""` | non-empty | early child service |
| 3 | Layanan anak awal | `history_child_service_title` | string | `""` | non-empty | early child service |
| 3 | Layanan anak awal | `history_child_service_caption` | multiline string | `""` | non-empty | early child service |
| 3 | Layanan anak awal | `history_child_service_alt` | string | `""` | non-empty | early child service |
| 4 | Peletakan batu pertama | `history_first_stone_image_id` | image attachment ID | `0` | valid image attachment | `D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-4.png` |
| 4 | Peletakan batu pertama | `history_first_stone_year` | string | `""` | non-empty | first-stone construction |
| 4 | Peletakan batu pertama | `history_first_stone_title` | string | `""` | non-empty | first-stone construction |
| 4 | Peletakan batu pertama | `history_first_stone_caption` | multiline string | `""` | non-empty | first-stone construction |
| 4 | Peletakan batu pertama | `history_first_stone_alt` | string | `""` | non-empty | first-stone construction |
| 5 | Radiologi dan modernisasi | `history_modernization_image_id` | image attachment ID | `0` | valid image attachment | `D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-2.png` |
| 5 | Radiologi dan modernisasi | `history_modernization_year` | string | `""` | non-empty | radiology/modernization |
| 5 | Radiologi dan modernisasi | `history_modernization_title` | string | `""` | non-empty | radiology/modernization |
| 5 | Radiologi dan modernisasi | `history_modernization_caption` | multiline string | `""` | non-empty | radiology/modernization |
| 5 | Radiologi dan modernisasi | `history_modernization_alt` | string | `""` | non-empty | radiology/modernization |

Public slot contract:

- Slot dianggap complete hanya jika `image_id` adalah attachment gambar valid dan `year`, `title`, `caption`, `alt` semuanya non-empty setelah trim.
- Slot incomplete dihapus dari array publik, bukan dikirim sebagai figure kosong.
- Template menerima hanya slot complete. Jika helper internal butuh flag `complete`, flag tidak perlu diekspos ke Twig publik.
- Urutan array publik tetap: hero, pioneers, child_service, first_stone, modernization.

Suggested normalized shape:

```php
history_page['gallery'] = [
    [
        'slot' => 'hero',
        'image_id' => 123,
        'year' => '...',
        'title' => '...',
        'caption' => '...',
        'alt' => '...',
        'placement' => 'hero',
    ],
];
```

## 5. Publishing Gate dan Preprocessing Workflow

Hard gate:

- Tidak boleh upload derivative ke Media Library untuk publik.
- Tidak boleh seed/import settings.
- Tidak boleh mengisi production settings.
- Tidak boleh publish visual di `/sejarah-kami`.
- Tidak boleh menebak metadata dari scan.
- Gate lulus hanya jika 5 paket metadata resmi tersedia dan disetujui: `year`, `title`, `caption`, `alt` untuk setiap slot.

Yang boleh sebelum gate:

- Scaffold kode settings, defaults, sanitizer, context, Twig, dan tests.
- Default tetap kosong, sehingga halaman publik tetap text-only.
- QA baseline zero slots.

Preprocessing derivative:

1. Salin sumber PNG ke working area sementara di luar source path.
2. Catat checksum dan dimensi original sebelum proses.
3. Bersihkan paper margins, embedded captions, dan CamScanner watermark hanya pada derivative.
4. Pertahankan B&W.
5. Jangan colorization.
6. Pilih crop aman sesuai isi foto. Jika crop belum terverifikasi, pakai wrapper aman dan `object-contain`.
7. Optimasi derivative lewat WordPress generated sizes atau local processing yang cocok.
8. WebP boleh dipakai jika pipeline WordPress menghasilkan ukuran kompatibel.
9. Catat checksum dan dimensi derivative setelah proses.
10. Upload derivative via Media Library hanya setelah metadata resmi approved.

Template log preprocessing:

```text
Slot: history_hero
Source: D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-1.png
Original checksum: <sha256>
Original dimensions: <width>x<height>
Derivative file: <path or media filename>
Derivative checksum: <sha256>
Derivative dimensions: <width>x<height>
Cleaning notes: <margin/caption/watermark removal details>
Approved metadata reference: <approval note/date>
```

## 6. Implementation Strategy

Minimal affected files:

- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-defaults.php`
  - Add 25 empty defaults. Reason: safe no-publication baseline.
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php`
  - Add tab `sejarah` and five sections. Reason: existing registry already drives admin UI.
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
  - Add 5 image keys to `Admin::imageKeys()`.
  - Add narrow caption key list and sanitize captions with `sanitize_textarea_field`.
  - Validate selected image attachment IDs using `wp_attachment_is_image()` or `get_post_mime_type()`.
  - Reason: central save sanitization prevents invalid IDs from persisting.
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-api.php`
  - No public REST expansion for history fields.
  - Confirm image URL sibling behavior remains unchanged for image keys.
  - Reason: Timber context already receives settings.
- `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php`
  - Build normalized `history_page.gallery` from `rspku_setting()` values.
  - Include only complete slots.
  - Reason: one context contract for Twig, no logic scattered in views.
- `wp-content/themes/rspku-theme/resources/views/pages/page-sejarah-kami.twig`
  - Render editorial figures in explicit placements.
  - Use `resources/views/partials/responsive-image.twig`.
  - Reason: existing responsive image partial handles srcset/lazy behavior.
- Optional minimal test file, location chosen by existing test layout after inspection.
  - If no framework exists, add one small runnable PHP/static contract check.

Files that should not change:

- `wp-content/plugins/rspku-settings/assets/admin.js`, unless existing picker fails. Expected no change.
- `wp-content/themes/rspku-theme/public/build/`, never commit.
- Any original PNG source under `D:\LabMu\Project\PKU Jogja\...`, never edit.

Sanitizer decision:

- Use narrow explicit caption key list in admin sanitization. It is shorter and safer than widening registry behavior.
- `year`, `title`, `alt` stay `sanitize_text_field`.
- Image IDs sanitize to valid image attachment ID, else `0`.

Placement decision:

- Hero slot integrates into hero composition.
- Pioneers, child_service, and first_stone attach to appropriate timeline/story positions by explicit slot placement.
- Modernization renders near final timeline or present-day section.
- Do not match user metadata to current milestone year text.
- Do not replace milestone year, title, or body.
- Render user metadata as archival context inside `<figure>` and `<figcaption>`.

Visual direction:

- Editorial history, not gallery grid.
- Hero two-column at large breakpoint, single column on mobile.
- Timeline figures alternate consistently with current timeline.
- Stable aspect ratio, prefer `4:3` or `3:2` after crop verification.
- Use `object-cover` only when crop is verified.
- Otherwise use `object-contain` in a safe wrapper.
- Hero image eager and high priority only when present.
- Other images lazy.
- Prevent layout shift with intrinsic width and height when available.

Accessibility:

- Use approved meaningful alt for each image.
- If caption covers most visual meaning, alt still must be concise and approved.
- Use `<figure>` and `<figcaption>` semantics.
- Do not make image clickable unless there is a real destination.
- Keep keyboard flow unchanged.
- Ensure caption contrast and legibility.

Admin UX:

- Tab label: `Sejarah`.
- Five section names match story order:
  - `Hero Bangunan Bersejarah`
  - `Pionir dan Tokoh Awal`
  - `Layanan Anak Awal`
  - `Peletakan Batu Pertama`
  - `Radiologi dan Modernisasi`
- Each section includes image, year, title, caption, alt.
- Help text states: image and all metadata are required before the slot appears on `/sejarah-kami`.
- Help text states image source role and warns not to infer metadata from scans.
- Keep capability `manage_options`.
- Keep nonce, save, import, export behavior.

## 7. Tasks

- [ ] Task 0, official metadata gate dan immutable source inventory/preprocess derivatives
  - Owner/category: content + visual assets, with developer support only for evidence logging.
  - Dependencies: user approval for 5 metadata sets. Hard gate ini memblokir population/publish dan complete five-slot runtime QA, tetapi tidak memblokir scaffold aman Tasks 1-4.
  - Precise edits: no app file edits. Create only derivative working copies outside original source paths if approved.
  - MUST: collect official `year`, `title`, `caption`, `alt` for all slots.
  - MUST: map source images exactly as listed in Data Contract.
  - MUST: record before/after checksum and dimensions for each original and derivative.
  - MUST: preserve originals unchanged.
  - MUST: preserve B&W and avoid colorization.
  - MUST NOT: infer dates or captions from scans.
  - MUST NOT: upload, seed, import, or publish before all five metadata sets are approved.
  - Acceptance criteria: all 5 slots have approved metadata and preprocessing log entries, or task remains blocked.
  - Verification/evidence: approval record, checksum/dimensions table, derivative review screenshots or file notes.
  - QA scenarios:
    - Tool/command:
      - `Get-FileHash -Algorithm SHA256 "D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-1.png"`
      - `Get-FileHash -Algorithm SHA256 "D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-5.png"`
      - `Get-FileHash -Algorithm SHA256 "D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-3.png"`
      - `Get-FileHash -Algorithm SHA256 "D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-4.png"`
      - `Get-FileHash -Algorithm SHA256 "D:\LabMu\Project\PKU Jogja\Sejarah RS PKU Muhammadiyah Yogyakarta\Sejarah RS PKU Muhammadiyah Yogyakarta-2.png"`
      - Image metadata inspection before upload: `Add-Type -AssemblyName System.Drawing; $img=[System.Drawing.Image]::FromFile("<image-path>"); "${($img.Width)}x${($img.Height)} $($img.PixelFormat)"; $img.Dispose()`.
      - Image metadata inspection after local upload: WordPress Media Library attachment details for dimensions and generated sizes.
    - Concrete steps:
      - Record original SHA256 and dimensions for all five source PNGs before any derivative work.
      - Create cleaned derivative copies only from copied working files, never from in-place source edits.
      - Run `Get-FileHash -Algorithm SHA256 <derivative-path>` for each derivative.
      - Inspect derivative dimensions and color mode.
      - Visually compare original versus derivative for crop safety.
      - Verify paper margins, embedded captions, and CamScanner watermark are removed only from derivatives.
      - Re-run `Get-FileHash -Algorithm SHA256 <original-path>` for all five originals after derivative creation.
      - Confirm five approval records exist, one per slot, each containing official `year`, `title`, `caption`, `alt`.
    - Exact expected results:
      - Original hashes before and after are identical.
      - Each derivative has recorded hash and dimensions.
      - B&W retained, no colorization.
      - Forbidden watermark/margins removed only on derivatives.
      - All five metadata sets approved. If any approval missing, write `[blocked]` and perform no upload, seed, import, or public population.
    - Evidence to retain:
      - `.sisyphus/evidence/history-task-0-source-inventory.txt`
      - `.sisyphus/evidence/history-task-0-derivative-inventory.txt`
      - `.sisyphus/evidence/history-task-0-approval-records.txt`
      - `.sisyphus/evidence/history-task-0-visual-review/`

- [x] Task 1, settings contract/defaults/registry
  - Owner/category: backend WordPress settings.
  - Dependencies: none. Can proceed before metadata approval because defaults are empty.
  - Precise edits:
    - Add 25 defaults in `wp-content/plugins/rspku-settings/includes/class-rspku-settings-defaults.php`.
    - Add tab `sejarah` in `wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php`.
    - Add five sections and five fields per section.
  - MUST: image defaults are `0`.
  - MUST: text defaults are empty strings.
  - MUST: use exact key names from Data Contract.
  - MUST: section order follows story order.
  - MUST: admin help text states completeness requirement.
  - MUST NOT: add new dependency or admin JS.
  - Acceptance criteria: registry exposes tab `Sejarah`, all 25 fields render in admin, other tabs keep existing behavior.
  - Verification/evidence: PHP lint on changed PHP, LSP diagnostics on changed PHP, admin screen QA after Task 5.
  - QA scenarios:
    - Tool/command:
      - `php -l wp-content/plugins/rspku-settings/includes/class-rspku-settings-defaults.php`
      - `php -l wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php`
      - `lsp_diagnostics` on `wp-content/plugins/rspku-settings/includes/class-rspku-settings-defaults.php`.
      - `lsp_diagnostics` on `wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php`.
      - Authenticated browser: `wp-admin/admin.php?page=rspku-settings&tab=sejarah`.
    - Concrete steps:
      - Run PHP lint commands on defaults and registry files.
      - Run LSP diagnostics on both exact files.
      - Log in as admin with `manage_options`.
      - Open `wp-admin/admin.php?page=rspku-settings&tab=sejarah`.
      - Count five sections and 25 fields.
      - Inspect labels and help text for all five sections.
      - Confirm semantic section order: hero, pioneers, child_service, first_stone, modernization.
      - Confirm image defaults are empty/`0` and text defaults are empty strings before user entry.
      - Navigate existing settings tabs and confirm they still render.
      - Open browser console and check for JS errors.
    - Exact expected results:
      - `php -l` returns `No syntax errors detected` for both files.
      - LSP diagnostics show no new errors in both files.
      - Admin tab `Sejarah` exists.
      - Five sections and 25 fields appear in semantic order.
      - Help text states image plus all metadata are required before public display.
      - Other tabs remain present.
      - Empty image/text defaults are shown.
      - Browser console has no JS error.
    - Evidence to retain:
      - `.sisyphus/evidence/history-task-1-settings-contract.txt`
      - `.sisyphus/evidence/history-task-1-admin-sejarah-tab.png`

- [x] Task 2, image validation/API/context normalization
  - Owner/category: backend WordPress context.
  - Dependencies: Task 1.
  - Precise edits:
    - Add 5 image IDs to `Admin::imageKeys()` in `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`.
    - Add explicit caption-key detection for the 5 caption keys and sanitize via `sanitize_textarea_field`.
    - Validate image IDs during sanitization using `wp_attachment_is_image()` or `get_post_mime_type()`, else save `0`.
    - Keep API behavior in `wp-content/plugins/rspku-settings/includes/class-rspku-settings-api.php`, no REST expansion for history fields.
    - Build complete-only gallery array in `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php`.
  - MUST: invalid image attachment IDs become `0`.
  - MUST: deleted or non-image attachments don't publish.
  - MUST: incomplete metadata omits the slot entirely.
  - MUST: context order stays semantic order.
  - MUST NOT: expose history fields in public REST payload unless explicitly requested later.
  - MUST NOT: hardcode image URL.
  - Acceptance criteria: `history_page.gallery` contains zero slots when settings empty, contains each slot only when valid image and all metadata are present, and keeps exact order.
  - Verification/evidence: PHP lint, LSP diagnostics, runnable contract check in Task 4.
  - QA scenarios:
    - Tool/command:
      - `php -l wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
      - `php -l wp-content/plugins/rspku-settings/includes/class-rspku-settings-api.php`
      - `php -l wp-content/themes/rspku-theme/app/Controllers/TemplateController.php`
      - `lsp_diagnostics` on `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`.
      - `lsp_diagnostics` on `wp-content/plugins/rspku-settings/includes/class-rspku-settings-api.php`.
      - `lsp_diagnostics` on `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php`.
      - Planned contract check: `php wp-content/themes/rspku-theme/scripts/check-history-gallery-contract.php`.
      - Temporary WP bootstrap driver, only if Task 4 script not yet created: `php .sisyphus/tmp/history-context-driver.php`.
    - Concrete steps:
      - Run PHP lint on Admin, API, and TemplateController files.
      - Run LSP diagnostics on those exact files.
      - Execute contract check/driver with empty settings fixture.
      - Execute positive non-image attachment ID fixture.
      - Execute deleted attachment ID fixture.
      - Execute fixture with one metadata field missing.
      - Execute fixture with five valid image attachments and complete metadata.
      - Include one caption fixture with line breaks.
      - Request existing settings REST payload and compare before/after key set.
    - Exact expected results:
      - `php -l` returns `No syntax errors detected` for all three files.
      - LSP diagnostics show no new errors.
      - Gallery counts are exactly `0`, `0`, `0`, `0`, `5` for empty, non-image, deleted, missing metadata, and five complete slots.
      - Complete fixture order is exactly `hero`, `pioneers`, `child_service`, `first_stone`, `modernization`.
      - Invalid IDs sanitize to `0`.
      - Captions keep line breaks after sanitization.
      - Public REST payload does not gain `history_*` keys.
    - Evidence to retain:
      - `.sisyphus/evidence/history-task-2-context-contract.txt`

- [x] Task 3, editorial Twig integration
  - Owner/category: frontend UI/UX.
  - Dependencies: Task 2.
  - Precise edits:
    - Update `wp-content/themes/rspku-theme/resources/views/pages/page-sejarah-kami.twig`.
    - Use `wp-content/themes/rspku-theme/resources/views/partials/responsive-image.twig` for all images.
    - Place hero slot in hero composition.
    - Place `pioneers`, `child_service`, and `first_stone` near relevant timeline/story positions by explicit slot name.
    - Place `modernization` near final timeline or present-day section.
  - MUST: preserve existing milestone prose, dates, titles, and body.
  - MUST: render metadata in `<figure>` and `<figcaption>`.
  - MUST: keep text-only page intact when gallery empty.
  - MUST: avoid grid gallery and carousel.
  - MUST: avoid matching by year text.
  - MUST: hero image eager/high priority only when present.
  - MUST: other images lazy.
  - MUST: prevent layout shift with width/height or stable wrapper.
  - MUST NOT: add clickable images without destination.
  - Acceptance criteria: `/sejarah-kami` shows zero figures when settings empty, shows complete slots in intended positions when all valid, and doesn't show blank figures for incomplete slots.
  - Verification/evidence: theme build, manifest check, Playwright QA in Task 5.
  - QA scenarios:
    - Tool/command:
      - `cd wp-content/themes/rspku-theme && npm run build`
      - Read `wp-content/themes/rspku-theme/public/build/.vite/manifest.json`.
      - Playwright/browser QA: open `/sejarah-kami`, set viewport widths `1440`, `768`, `360`, inspect DOM/console/network.
    - Concrete steps:
      - Run theme production build after Twig changes.
      - Read manifest and record app asset hashes/timestamps.
      - Baseline fixture: keep history settings empty, open `/sejarah-kami` at 1440, 768, and 360.
      - Complete fixture: local-only approved data or dummy fixture with five valid local attachments, open `/sejarah-kami` at 1440, 768, and 360.
      - Inspect hero slot placement.
      - Inspect four archival placements for `pioneers`, `child_service`, `first_stone`, `modernization`.
      - Compare visible milestone text before/after to confirm text, dates, and prose unchanged.
      - Inspect DOM for `<figure>` and `<figcaption>`.
      - Inspect image attributes for `alt`, `srcset`, `sizes`, `loading`, and hero priority.
      - Check console errors and horizontal overflow.
    - Exact expected results:
      - Build exits `0`.
      - Manifest points to latest generated app assets.
      - Baseline zero-slot page stays text-only with no blank figures.
      - Complete fixture renders hero plus four exact archival placements.
      - Existing text/milestones unchanged.
      - Hero image has eager/high priority only when present.
      - Non-hero images are lazy.
      - Each complete slot uses figure/figcaption, exact alt, exact caption, `srcset`, and `sizes`.
      - No blank figures, overflow, broken images, or console errors at 1440/768/360.
    - Evidence to retain:
      - `.sisyphus/evidence/history-task-3-build.txt`
      - `.sisyphus/evidence/history-task-3-dom-metrics.txt`
      - `.sisyphus/evidence/history-task-3-desktop.png`
      - `.sisyphus/evidence/history-task-3-tablet.png`
      - `.sisyphus/evidence/history-task-3-mobile.png`

- [x] Task 4, runnable regression contract checks
  - Owner/category: QA/backend static contract.
  - Dependencies: Task 1 and Task 2.
  - Precise edits: add one small runnable PHP/static check at `wp-content/themes/rspku-theme/scripts/check-history-gallery-contract.php` if current test framework is absent.
  - MUST: assert 25 defaults exist.
  - MUST: assert 5 image keys exist.
  - MUST: assert semantic order.
  - MUST: assert completeness behavior.
  - MUST: assert no publication for incomplete, deleted, or non-image attachments.
  - MUST NOT: add test framework or dependency.
  - Acceptance criteria: contract check exits `0` when implementation satisfies data contract and fails clearly when key order or completeness breaks.
  - Verification/evidence: command output from the runnable check.
  - QA scenarios:
    - Tool/command:
      - `php -l wp-content/themes/rspku-theme/scripts/check-history-gallery-contract.php`
      - `php wp-content/themes/rspku-theme/scripts/check-history-gallery-contract.php`
    - Concrete steps:
      - Add or run the exact check path `wp-content/themes/rspku-theme/scripts/check-history-gallery-contract.php`.
      - Lint the check file.
      - Run the check from repo root.
      - Confirm positive fixture covers five complete slots.
      - Confirm negative fixtures cover missing metadata, invalid ID, deleted ID, and non-image ID.
      - Confirm script prints explicit PASS lines.
    - Exact expected results:
      - Lint returns `No syntax errors detected`.
      - Script exits `0`.
      - Output includes `PASS 25 defaults`.
      - Output includes `PASS 5 image IDs and semantic order`.
      - Output includes `PASS complete-only behavior`.
      - Output includes `PASS invalid/deleted/non-image omissions`.
      - Negative fixture behavior assertion proves each invalid case yields zero published slots.
    - Evidence to retain:
      - `.sisyphus/evidence/history-task-4-regression-checks.txt`

- [ ] Task 5, admin QA dan public responsive QA
  - Owner/category: QA runtime.
  - Dependencies: Task 3 and Task 4. Task 5A baseline/incomplete QA can proceed before Task 0. Task 5B complete data QA waits for Task 0 approval and manual entry. Task 5 cleanup restores local settings from export.
  - Precise actions:
    - Admin authenticated QA in settings page.
    - Public QA for `/sejarah-kami` at 1440, 768, and 360 widths.
    - Regression QA for `/`, `/kontak/`, and one existing settings image consumer.
  - MUST: test all five pickers select, remove, save, reload.
  - MUST: test metadata fields preserve multiline captions.
  - MUST: test other settings tabs retain values.
  - MUST: test import/export compatibility.
  - MUST: test baseline zero slots, incomplete omitted, complete five exact order.
  - MUST: confirm hero LCP priority only for hero when present.
  - MUST: confirm other images lazy.
  - MUST: inspect `srcset`, `sizes`, no broken images, no overflow, no console errors.
  - MUST: verify captions and alt exactly match official values.
  - MUST NOT: populate production settings before approval.
  - Acceptance criteria: admin save flow works, public responsive layouts pass, regressions unaffected.
  - Verification/evidence: QA notes, screenshot set, console status, network/image loading notes.
  - QA scenarios:
    - Tool/command:
      - Authenticated browser to `wp-admin/admin.php?page=rspku-settings&tab=sejarah`.
      - Authenticated browser to existing settings export/import screens.
      - Public browser QA for `/sejarah-kami`, `/`, `/kontak/`, and `/dokter/` or another existing settings image consumer.
      - Viewports: `1440`, `768`, `360`.
    - Concrete steps:
      - Export local settings JSON first and save it as rollback fixture.
      - Task 5A baseline: clear five history image IDs in local settings, save, reload, open `/sejarah-kami` at 1440/768/360.
      - Task 5A incomplete: enter one image with one missing metadata field, save, reload, open `/sejarah-kami` at 1440/768/360.
      - Task 5B complete after Task 0: select, remove, and reselect all five images.
      - Enter approved metadata for all five slots, including at least one multiline caption.
      - Save, reload, and verify all fields persist exactly.
      - Switch to existing tabs and verify a sentinel value remains unchanged.
      - Export settings JSON locally.
      - Import the same JSON locally and verify roundtrip preserves history keys and existing tabs.
      - Open public `/sejarah-kami` at 1440/768/360.
      - Open regression URLs `/`, `/kontak/`, and `/dokter/` or existing hero image consumer.
      - Restore local settings from the initial export after QA.
    - Exact expected results:
      - Initial export file exists before any local mutation.
      - Select/remove/reselect works for all five image fields.
      - Save/reload preserves all image IDs and official metadata, including multiline caption line breaks.
      - Other tab sentinel unchanged after saving `Sejarah`.
      - Local export/import roundtrip preserves settings.
      - 5A baseline shows text-only page and zero figures.
      - 5A incomplete omits incomplete slot and shows no blank figure.
      - 5B complete shows five slots in exact semantic order and exact placements.
      - Public alt/captions match official values exactly.
      - `/`, `/kontak/`, and `/dokter/` or selected settings image consumer show no regression.
      - Local settings restored from export at end.
    - Evidence to retain:
      - `.sisyphus/evidence/history-task-5-local-settings-before.json`
      - `.sisyphus/evidence/history-task-5-admin-flow.txt`
      - `.sisyphus/evidence/history-task-5-admin-sejarah-complete.png`
      - `.sisyphus/evidence/history-task-5-public-baseline-desktop.png`
      - `.sisyphus/evidence/history-task-5-public-baseline-tablet.png`
      - `.sisyphus/evidence/history-task-5-public-baseline-mobile.png`
      - `.sisyphus/evidence/history-task-5-public-complete-desktop.png`
      - `.sisyphus/evidence/history-task-5-public-complete-tablet.png`
      - `.sisyphus/evidence/history-task-5-public-complete-mobile.png`
      - `.sisyphus/evidence/history-task-5-regression-urls.txt`
      - `.sisyphus/evidence/history-task-5-local-settings-after-restore.json`

- [x] Task 6, release/deploy only after explicit approval
  - Owner/category: release.
  - Dependencies: Task 0 through Task 5 complete, explicit user approval.
  - Precise actions:
    - Start from clean worktree at latest `origin/main`.
    - Exclude unrelated local changes.
    - Do not include `wp-content/themes/rspku-theme/public/build/` in commit.
    - Normal deploy: server `git pull`, then `cd wp-content/themes/rspku-theme && npm ci && npm run build`.
    - If server has no Node.js, upload entire locally built `wp-content/themes/rspku-theme/public/build/` manually.
  - MUST: export current `rspku_settings` JSON before changes to production settings.
  - MUST: verify `wp-content/themes/rspku-theme/public/build/.vite/manifest.json` points to latest app assets after build.
  - MUST: verify live page loaded assets match new manifest.
  - MUST NOT: commit, push, deploy, or mutate DB until explicitly requested.
  - Acceptance criteria: deployment performed only on approval, settings backup exists, live QA passes.
  - Verification/evidence: build output, manifest timestamp/hash note, settings export location, live QA notes.
  - QA scenarios:
    - Tool/command:
      - `git status --short`
      - `git diff -- .sisyphus/plans/sejarah-kami-gallery-dinamis.md wp-content/plugins/rspku-settings/includes/class-rspku-settings-defaults.php wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php wp-content/plugins/rspku-settings/includes/class-rspku-settings-api.php wp-content/themes/rspku-theme/app/Controllers/TemplateController.php wp-content/themes/rspku-theme/resources/views/pages/page-sejarah-kami.twig wp-content/themes/rspku-theme/scripts/check-history-gallery-contract.php`
      - `cd wp-content/themes/rspku-theme && npm ci && npm run build`
      - Read `wp-content/themes/rspku-theme/public/build/.vite/manifest.json`.
      - `git status --short wp-content/themes/rspku-theme/public/build`
      - Normal server deploy after approval: `git pull`, then `cd wp-content/themes/rspku-theme && npm ci && npm run build`.
      - Fallback server without Node after approval: upload entire locally built `wp-content/themes/rspku-theme/public/build/` manually.
    - Concrete steps:
      - Before release work, confirm explicit user approval exists.
      - Check worktree status and identify unrelated local changes.
      - Confirm release starts from clean worktree at latest `origin/main` or isolate intended changes only.
      - Review diff for intended files only.
      - Run production build.
      - Record manifest asset hashes.
      - Confirm `wp-content/themes/rspku-theme/public/build/` is not staged or committed.
      - Export production `rspku_settings` JSON before population.
      - Deploy normal path only after approval.
      - If no Node.js on server, use documented fallback and upload entire `public/build/`.
      - Runtime check live `/sejarah-kami`, `/`, `/kontak/`, and `/dokter/` or chosen image consumer.
      - Verify HTTP 200, loaded CSS/JS asset hashes match manifest, DOM has expected figure count, no console errors.
      - Rollback drill: clear five history image IDs to `0` in settings and confirm `/sejarah-kami` returns text-only; code rollback remains independent because unknown keys are harmless.
    - Exact expected results:
      - No release action occurs without explicit approval.
      - Worktree contains only intended feature changes before commit/release.
      - Build exits `0`.
      - Manifest points to latest assets.
      - `public/build/` excluded from commit.
      - Server deploy builds assets normally, or fallback upload includes complete `public/build/`.
      - Runtime URLs return HTTP 200.
      - Loaded assets match manifest hashes.
      - `/sejarah-kami` DOM matches expected baseline or complete state depending on approved settings.
      - Rollback drill criteria pass: clearing five image IDs removes public figures without breaking page.
    - Evidence to retain:
      - `.sisyphus/evidence/history-task-6-release.txt`

## 8. Parallel Waves dan Dependency Graph

Wave A, safe scaffold:

- Task 1 can start immediately.
- Task 2 can start after Task 1.
- Task 3 can start after Task 2.
- Task 4 can start after Task 1 and Task 2.

Wave B, metadata and assets:

- Task 0 runs in parallel with Wave A.
- Task 0 blocks manual entry, derivative upload, seed/import, public complete-slot QA, and production publication.

Wave C, QA:

- Task 5 baseline zero-slot QA can run after Task 3 with empty defaults.
- Task 5 complete-slot QA waits for Task 0 and safe manual admin entry in local environment.

Wave D, release:

- Task 6 waits for Tasks 0 to 5 and explicit approval.

Dependency graph:

```text
Task 1 -> Task 2 -> Task 3 -> Task 5 baseline
          Task 2 -> Task 4 -> Task 5 baseline
Task 0 ---------------------> Task 5 complete
Task 5 complete ------------> Task 6
Explicit approval ----------> Task 6
```

## 9. Final Verification F1-F4

F1, scope/goal:

- Confirm exactly five semantic slots.
- Confirm no plugin, CPT, repeater, carousel, dependency, REST expansion, or hardcoded image URL.
- Confirm page stays text-only with empty defaults.
- Confirm no broad history copy rewrite.

F2, code quality:

- PHP lint changed PHP files.
- LSP diagnostics on changed PHP files.
- One runnable contract check passes.
- Theme build passes after Twig changes:

```bash
cd wp-content/themes/rspku-theme
npm run build
```

- Verify manifest after build:

```text
wp-content/themes/rspku-theme/public/build/.vite/manifest.json
```

- Do not commit `wp-content/themes/rspku-theme/public/build/`.

F3, security/data integrity:

- Invalid positive IDs sanitize to `0`.
- Non-image attachments sanitize to `0`.
- Deleted attachments don't publish.
- Caption sanitizer allows intended multiline text without unsafe markup.
- Existing `manage_options`, nonce, save, import, export behavior retained.
- Current settings JSON exported before production changes.

F4, runtime responsive/admin:

- Admin QA: five picker flows select, remove, save, reload.
- Admin QA: metadata persists and other tabs retain values.
- Public QA 1440, 768, 360: baseline zero slots remains text-only.
- Public QA 1440, 768, 360: incomplete slots omitted.
- Public QA 1440, 768, 360: complete five slots appear in exact order and placement.
- Public QA: hero eager/high priority only when present.
- Public QA: other images lazy.
- Public QA: `srcset` and `sizes` present from responsive image partial.
- Public QA: no broken images, no overflow, no console errors.
- Regression QA: `/`, `/kontak/`, and one existing settings image consumer unaffected.

Optional plugin commands:

```bash
cd wp-content/plugins/rspku-settings
npm test
```

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
```

Run these only if plugin test scripts exist. Run `npm run build:css` only if admin CSS changes, ideally no CSS changes.

## 10. Rollback

Code rollback:

- Revert only implementation commits or files from this feature.
- Unknown settings keys are harmless if code no longer reads them.
- Keep unrelated local changes untouched.

Settings rollback:

- Export current `rspku_settings` JSON before any production edits.
- To disable public images without code rollback, clear these five image IDs to `0`:
  - `history_hero_image_id`
  - `history_pioneers_image_id`
  - `history_child_service_image_id`
  - `history_first_stone_image_id`
  - `history_modernization_image_id`
- Clearing image IDs returns `/sejarah-kami` to text-only mode because incomplete slots are omitted.
- Keep official metadata in settings if desired, but no image ID means no public figure.

Asset rollback:

- Retain source PNGs unchanged.
- Remove or unpublish derivative Media Library items only if rollback policy requires it.
- Do not delete originals.

Deployment rollback:

- Rebuild theme assets on server after code rollback:

```bash
cd wp-content/themes/rspku-theme
npm ci
npm run build
```

- Verify manifest and public page after rollback.

## 11. Completion Checklist

- [ ] Metadata gate has 5 approved official metadata sets.
- [ ] Original source inventory complete with checksum and dimensions.
- [ ] Derivative preprocessing log complete with checksum and dimensions.
- [ ] `class-rspku-settings-defaults.php` has all 25 defaults.
- [ ] `class-rspku-settings-registry.php` has tab `Sejarah` and five ordered sections.
- [ ] `class-rspku-settings-admin.php` has five image keys and caption sanitizer.
- [ ] Image ID validation rejects non-images and deleted attachments.
- [ ] `TemplateController::historyPageContext()` outputs complete-only gallery array.
- [ ] `page-sejarah-kami.twig` renders editorial figures via responsive image partial.
- [ ] Existing milestone copy and years stay unchanged.
- [ ] Contract check passes.
- [ ] PHP lint and LSP diagnostics pass.
- [ ] Theme `npm run build` passes.
- [ ] Manifest verified and `public/build/` not committed.
- [ ] Admin QA passes.
- [ ] Public responsive QA passes at 1440, 768, 360.
- [ ] Regression pages pass.
- [ ] Settings export backup exists before production settings edits.
- [ ] No commit, push, deploy, DB mutation, seed, import, or upload happens without explicit approval.

## 12. Explicit Blocked Status

Current status: implementation plan ready, implementation blocked only for public population.

Scaffold may proceed before metadata approval because empty defaults publish nothing. The implementation must keep `/sejarah-kami` text-only when settings are empty or incomplete.

Blocked until Task 0 passes:

- Uploading cleaned derivatives for public use.
- Manual entry of image IDs and metadata into production settings.
- Any seed/import script.
- Any public publishing of the five archival photo slots.
- Complete-slot public QA with official values.

Hard rule: never infer dates, captions, titles, or alt text from scans. Official metadata must come from user approval only.
