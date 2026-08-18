# RSPKU Settings Admin UI Refactor Plan

Status: Draft untuk implementasi bertahap  
Bahasa: Indonesia  
Target: WordPress admin UI plugin `wp-content/plugins/rspku-settings`  
Larangan utama: plan saja, jangan ubah kode aplikasi saat membaca plan ini

## Tujuan

Perbaiki UI admin RSPKU Settings secara bertahap tanpa rewrite besar. UI saat ini berisi 12 tab, 30 section, 127 field. Beban terbesar ada di Homepage 41 field, Sejarah 25 field, Kontak 15 field. Fokus kerja adalah menghilangkan bug overlay, merapikan kontrak renderer dan CSS, memperjelas navigasi dan section, lalu meningkatkan komponen field yang paling padat.

## Batasan Wajib

1. Wajib mempertahankan option key, schema option, field keys, field names, sanitizer, save flow, import flow, export flow, API output, dan public output.
2. Wajib mempertahankan nonce, capability, dan behavior `admin-post` yang sudah ada.
3. Wajib mempertahankan progressive enhancement. Form save, import, export, input standar, dan behavior non JavaScript yang sudah bekerja tetap harus bekerja tanpa JavaScript.
4. Tidak boleh menambah React, Vue, Alpine baru, CSS framework baru, dependency baru, DB migration, atau framework admin baru.
5. Tidak boleh redesign public pages.
6. Tidak boleh rename settings key atau mengubah DB schema.
7. Tidak boleh menggabungkan semua perubahan dalam satu task besar.
8. Tidak boleh membuat semua section collapsed secara default tanpa menjaga discoverability dan visibility error.
9. Tidak boleh mengandalkan screenshot saja. QA wajib inspeksi DOM, computed layout, dan saved values.

## File, Symbol, dan Dependency Wajib

1. Renderer utama ada di `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`.
2. Symbol renderer wajib dipakai sebagai batas edit: `renderPage()`, `renderTabContent()`, `renderField()`.
3. CSS source wajib diubah di `wp-content/plugins/rspku-settings/assets/admin.source.css`.
4. CSS generated tracked output wajib ikut diperbarui di `wp-content/plugins/rspku-settings/assets/admin.css` setelah build.
5. JavaScript admin yang boleh disentuh hanya jika selector berubah atau perlu progressive enhancement: `wp-content/plugins/rspku-settings/assets/admin.js`.
6. Test CSS existing: `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`.
7. Plugin commands existing:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
```

## Root Cause Audit Yang Harus Ditangani

1. P0: `.rspku-settings-actions` sticky bottom dengan `z-index: 10` menimpa field di bawah.
2. P1: ada dua sistem layout bersamaan, renderer utilities `rs-grid` dan CSS komponen lama `.rspku-settings-field`.
3. P1: fallback `rs-max-w-lg` menjadi 520px, membuat field terlalu sempit dan menyisakan whitespace besar.
4. P1: registry flat dan 12 tab padat membuat Homepage, Sejarah, dan Kontak sulit dipindai.

## Gate Sebelum Mulai Implementasi

1. Implementasi wajib dilakukan di worktree bersih dan terisolasi dari latest `origin/main`.
2. Jangan memasukkan perubahan dirty main yang tidak terkait.
3. Jalankan:

```bash
git fetch origin
git status --short
git rev-parse --abbrev-ref HEAD
```

4. Acceptance criteria:
   1. Given developer mulai kerja, when `git status --short` dijalankan, then output kosong sebelum perubahan fase pertama.
   2. Given branch lokal dibuat, when dibandingkan dengan `origin/main`, then hanya file plugin dan evidence terkait task ini yang berubah.
5. Evidence: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/00-clean-worktree.txt`.
6. Rollback boundary: sebelum Phase 1 tidak ada file aplikasi boleh berubah.

## Phase 1. Characterization Dan Regression Baseline

### Task 1.1 Inventaris UI Dan Kontrak Save

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/01-baseline.md`

Ruang edit: tidak ada. Read only.

Langkah implementasi:
1. Catat jumlah tab, section, dan field dari registry settings. Audit count wajib tetap 12 tab, 30 section, 127 field sebelum refactor.
2. Catat tab berat: Homepage 41 field, Sejarah 25 field, Kontak 15 field.
3. Catat action form, nonce field, capability check, `admin-post` action, import action, export action, dan option key yang dipakai.
4. Catat selector yang dipakai `assets/admin.js` untuk media picker, repeater, checkbox picker, post picker, dan remove image.

Acceptance criteria:
1. Given baseline dibuat, when registry dihitung, then hasilnya 12 tab, 30 section, 127 field.
2. Given baseline dibuat, when tab berat dihitung, then Homepage 41 field, Sejarah 25 field, Kontak 15 field tercatat.
3. Given baseline dibuat, when save, import, export, API, dan public output ditinjau, then tidak ada rencana rename key atau schema.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm test
```

2. Steps: jalankan test existing, buka admin RSPKU Settings, ekspor setting JSON, simpan baseline DOM untuk tab Umum, Kontak, Homepage, Sejarah, Tools.
3. Expected result: test existing pass, export berhasil, semua tab bisa dibuka, field existing muncul.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/01-baseline.md`.

Rollback boundary:
1. Tidak ada rollback kode karena task ini read only.
2. Jika baseline count tidak cocok dengan audit, stop dan update plan sebelum implementasi.

### Task 1.2 Baseline Layout Overlay Dan Width

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/02-baseline-layout.json` (admin DOM blocked by 302 to `/404/`, recorded)

Ruang edit: tidak ada. Read only.

Langkah implementasi:
1. Buka admin page pada width 1440, 1024, 782, dan 360.
2. Inspeksi computed style untuk `.rspku-settings-actions`, field terakhir, dan input dengan `rs-max-w-lg`.
3. Catat apakah action bar overlap field dan apakah input 520px membuat whitespace.

Acceptance criteria:
1. Given viewport 1440, 1024, 782, 360, when `.rspku-settings-actions` dan field terakhir diukur, then baseline overlap tercatat dengan angka DOMRect.
2. Given input memakai `rs-max-w-lg`, when computed width diukur, then batas 520px tercatat.

QA scenario:
1. Tool atau command: Browser DevTools Console di halaman admin RSPKU Settings.
2. Steps: pada setiap viewport, jalankan snippet berikut dan simpan output.

```js
JSON.stringify({
  viewport: [innerWidth, innerHeight],
  actions: document.querySelector('.rspku-settings-actions')?.getBoundingClientRect(),
  lastField: [...document.querySelectorAll('[name^="rspku_settings"]')].at(-1)?.getBoundingClientRect(),
  maxWidthInput: document.querySelector('.rs-max-w-lg') && getComputedStyle(document.querySelector('.rs-max-w-lg')).maxWidth
}, null, 2)
```

3. Expected result: baseline numerical evidence tersedia, bukan screenshot saja.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/02-baseline-layout.json`.

Rollback boundary:
1. Tidak ada rollback kode karena task ini read only.

## Phase 2. P0 Action Bar Fix

### Task 2.1 Hapus Overlay Action Bar

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/10-p0-action-bar.md`

Ruang edit:
1. `wp-content/plugins/rspku-settings/assets/admin.source.css`
2. `wp-content/plugins/rspku-settings/assets/admin.css`, generated dari `npm run build:css`
3. `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`, hanya jika perlu mengunci behavior anti overlay

Keputusan implementasi:
1. Gunakan satu action bar saja, yaitu `.rspku-settings-actions` existing di `renderPage()`.
2. Hilangkan behavior sticky bottom yang menimpa konten.
3. Jadikan action bar mengikuti document flow, mudah ditemukan setelah form content.
4. Jangan tambah floating duplicate action bar.
5. Jangan ubah action form, nonce, capability, atau `admin-post` behavior.

Acceptance criteria:
1. Given admin page dibuka, when viewport 1440, 1024, 782, dan 360 diuji, then `.rspku-settings-actions` tidak overlap field apa pun.
2. Given DOM admin page dibaca, when `.rspku-settings-actions` dihitung, then hanya ada satu action bar.
3. Given user mengubah field text sederhana lalu klik Simpan, when halaman reload, then value tersimpan dan notice sukses muncul.
4. Given JavaScript dinonaktifkan, when user mengubah field text lalu submit form, then save tetap berjalan sesuai behavior existing.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
```

2. Steps: buka Umum, Kontak, Homepage, Sejarah, Tools pada width 1440, 1024, 782, 360. Jalankan DOM check overlap.

```js
const actions = document.querySelector('.rspku-settings-actions');
const fields = [...document.querySelectorAll('[name^="rspku_settings"]')];
const actionRect = actions.getBoundingClientRect();
const overlaps = fields.filter((field) => {
  const rect = field.getBoundingClientRect();
  return !(rect.bottom <= actionRect.top || rect.top >= actionRect.bottom || rect.right <= actionRect.left || rect.left >= actionRect.right);
}).map((field) => field.name);
JSON.stringify({ count: document.querySelectorAll('.rspku-settings-actions').length, overlaps }, null, 2)
```

3. Expected result: `count` adalah 1, `overlaps` adalah array kosong di semua viewport.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/10-p0-action-bar.json`.

Rollback boundary:
1. Jika save gagal, rollback semua perubahan Phase 2.
2. Jika action bar hilang dari DOM, rollback Phase 2.
3. Jika CSS test gagal karena selector wajib hilang, jangan hapus test, update implementation atau test sesuai behavior baru.

## Phase 3. P1 Renderer Dan CSS Contract Serta Responsive Widths

### Task 3.1 Satukan Kontrak Renderer Field

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/20-p1-renderer-widths.json`

Ruang edit:
1. `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
2. `wp-content/plugins/rspku-settings/assets/admin.source.css`
3. `wp-content/plugins/rspku-settings/assets/admin.css`
4. `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`

Keputusan implementasi:
1. `renderField()` tetap menjadi satu pintu rendering field.
2. Pilih satu contract CSS untuk field wrapper. Gunakan class component yang eksplisit dan konsisten, bukan campuran stale `.rspku-settings-field` grid lama dan utilities yang saling bertabrakan.
3. Jika registry perlu metadata, tambahkan metadata presentational seperti `layout`, `variant`, `group`, `width`, atau `description_ui` tanpa mengubah key setting atau sanitizer.
4. Field default harus full responsive dalam container, bukan terkunci `rs-max-w-lg` 520px untuk semua kasus.
5. Field sempit seperti number kecil boleh punya variant narrow lewat metadata, bukan hardcode global.

Acceptance criteria:
1. Given `renderField()` merender text, textarea, URL, toggle, image, repeater, checkbox picker, dan post picker, when DOM diperiksa, then semua memakai wrapper dan width contract yang konsisten.
2. Given viewport 1440 dan 1024, when field panjang di Homepage dan Sejarah diperiksa, then input tidak terkunci 520px kecuali field memang variant narrow.
3. Given viewport 782 dan 360, when form diperiksa, then label, description, dan control stack vertikal tanpa overflow horizontal.
4. Given registry metadata baru ditambahkan, when save dilakukan, then option schema dan setting keys tidak berubah.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: buka Homepage dan Sejarah. Inspeksi field text, textarea, URL, toggle, image, repeater, checkbox picker, post picker. Jalankan width check.

```js
JSON.stringify([...document.querySelectorAll('[name^="rspku_settings"]')].slice(0, 40).map((field) => ({
  name: field.name,
  width: field.getBoundingClientRect().width,
  maxWidth: getComputedStyle(field).maxWidth,
  overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth
})), null, 2)
```

3. Expected result: no global 520px cap untuk field default, no horizontal overflow, PHP lint pass, CSS build dan test pass.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/20-p1-renderer-widths.json`.

Rollback boundary:
1. Jika satu field type gagal render, rollback Task 3.1.
2. Jika field key berubah di export JSON, rollback Task 3.1.

## Phase 4. P1 Navigation Dan Collapsible Section Information Architecture

### Task 4.1 Perjelas 12 Tab Dan 30 Section Tanpa Mengubah Routing

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/30-p1-navigation-sections.md`

Ruang edit:
1. `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
2. `wp-content/plugins/rspku-settings/assets/admin.source.css`
3. `wp-content/plugins/rspku-settings/assets/admin.css`
4. `wp-content/plugins/rspku-settings/assets/admin.js`, hanya jika collapse membutuhkan progressive enhancement

Keputusan implementasi:
1. `renderPage()` tetap memakai query `tab` existing.
2. `renderTabContent()` tetap loop sections dan fields dari registry, namun boleh membaca metadata section seperti summary, priority, default open, dan field count.
3. Tambahkan visual hierarchy untuk 12 tab, misalnya label lebih jelas, count per tab, dan state active yang kuat.
4. Tambahkan collapsible section sebagai progressive enhancement. Tanpa JavaScript, semua section tetap terlihat.
5. Jangan membuat semua section collapsed by default. Section penting dan section yang memiliki error atau recently changed harus tetap discoverable.

Acceptance criteria:
1. Given 12 tab ada, when admin page dibuka, then semua tab tetap dapat diakses lewat URL `tab` existing.
2. Given JavaScript mati, when tab berat Homepage dan Sejarah dibuka, then semua section tetap terlihat dan editable.
3. Given JavaScript hidup, when section collapsible digunakan, then state collapse tidak menghapus input dari form dan tidak mengubah submitted values.
4. Given section berisi validation notice atau field error, when page reload, then section tersebut terbuka atau error terlihat tanpa user harus menebak.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: buka Umum, Kontak, Homepage, Sejarah, Tools. Toggle collapse pada beberapa section, isi satu field di section terbuka dan satu field di section collapsed, save, reload.
3. Expected result: tab URL tidak berubah, values tersimpan, no hidden lost input, no JS requirement untuk basic save.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/30-p1-navigation-sections.md`.

Rollback boundary:
1. Jika URL tab existing berubah, rollback Phase 4.
2. Jika section collapsed menyebabkan input tidak tersubmit, rollback Phase 4.

## Phase 5. P2 Grouped Components Untuk Field Berpasangan Dan Slot Padat

### Task 5.1 Call Center Display Dan Tel Pairs

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/40-p2-call-center.md`

Ruang edit:
1. Registry file yang mendefinisikan field Call Center.
2. `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
3. `wp-content/plugins/rspku-settings/assets/admin.source.css`
4. `wp-content/plugins/rspku-settings/assets/admin.css`

Keputusan implementasi:
1. Gunakan metadata registry untuk menyatakan pasangan display dan tel.
2. `renderField()` atau helper renderer boleh menampilkan pasangan sebagai grouped component, tetapi input `name` tetap key existing.
3. Jangan gabungkan dua setting menjadi satu object baru.

Acceptance criteria:
1. Given Call Center memiliki display dan tel pair, when UI dibuka, then pasangan tampil satu group dengan label jelas.
2. Given user mengubah display dan tel lalu save, when reload, then kedua value tetap tersimpan pada key existing.
3. Given export JSON dibuat, when dibandingkan dengan baseline key, then tidak ada key baru pengganti dan tidak ada key hilang.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: buka Kontak, ubah satu display number dan satu tel number, save, reload, export JSON.
3. Expected result: value UI sama dengan export JSON, pair jelas, no setting loss.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/40-p2-call-center.md`.

Rollback boundary:
1. Jika key pair berubah di export, rollback Task 5.1.

### Task 5.2 CTA Text Dan URL Pairs

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/41-p2-cta-pairs.md`

Ruang edit sama dengan Task 5.1, disesuaikan registry CTA.

Keputusan implementasi:
1. Gunakan variant grouped pair untuk text dan URL.
2. URL tetap memakai sanitizer URL existing.
3. Text tetap memakai sanitizer text existing.

Acceptance criteria:
1. Given CTA text dan URL ada di Homepage, when UI dibuka, then text dan URL tampil sebagai pasangan satu maksud.
2. Given URL invalid dimasukkan sesuai behavior existing, when save dilakukan, then sanitizer existing tetap menentukan hasil.
3. Given public page `/` dibuka setelah save, when CTA tampil, then output tidak berubah selain value yang sengaja disimpan.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: ubah CTA text dan URL di Homepage, save, reload admin, export JSON, smoke `/`.
3. Expected result: pair tersimpan, sanitizer tidak berubah, public smoke aman.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/41-p2-cta-pairs.md`.

Rollback boundary:
1. Jika sanitizer URL berubah, rollback Task 5.2.

### Task 5.3 Promo Cards Dan Sejarah Slot Cards

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/42-p2-promo-sejarah-cards.md`

Ruang edit:
1. Registry Promo dan Sejarah slots.
2. `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
3. `wp-content/plugins/rspku-settings/assets/admin.source.css`
4. `wp-content/plugins/rspku-settings/assets/admin.css`
5. `wp-content/plugins/rspku-settings/assets/admin.js`, hanya jika image picker selector butuh penyesuaian

Keputusan implementasi:
1. Gunakan metadata `group` atau `card` untuk mengelompokkan field per Promo card dan Sejarah slot.
2. Pertahankan setiap input individual dan key existing.
3. Jangan ubah output public `/` dan `/sejarah-kami/` selain perubahan value sengaja.

Acceptance criteria:
1. Given Homepage memiliki Promo card fields, when UI dibuka, then field per card terlihat sebagai satu unit kerja.
2. Given Sejarah memiliki slot card fields, when UI dibuka, then tiap slot jelas dan tidak bercampur dengan slot lain.
3. Given user mengubah text, URL, dan image pada satu card, when save dan reload dilakukan, then hanya card tersebut berubah dan field lain tidak hilang.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: ubah satu Promo card di Homepage dan satu Sejarah slot card, save, reload, export JSON, smoke `/` dan `/sejarah-kami/`.
3. Expected result: grouped card jelas, values tersimpan, no settings loss, public pages tetap render.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/42-p2-promo-sejarah-cards.md`.

Rollback boundary:
1. Jika media picker tidak membuka media library untuk card image, rollback Task 5.3 atau update selector dengan test.
2. Jika satu slot menyimpan ke key slot lain, rollback Task 5.3.

## Phase 6. P2 Jam Operasional Repeater, Image Picker, Post Dan Checkbox Selectors

### Task 6.1 Jam Operasional Repeater UX

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/50-p2-jam-operasional-repeater.md`

Ruang edit:
1. `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
2. `wp-content/plugins/rspku-settings/assets/admin.source.css`
3. `wp-content/plugins/rspku-settings/assets/admin.css`
4. `wp-content/plugins/rspku-settings/assets/admin.js`

Keputusan implementasi:
1. Pertahankan struktur data repeater existing.
2. Perbaiki layout row, label, remove button, add button, dan empty state tanpa mengganti mekanisme save.
3. Selector jQuery existing harus tetap stabil atau diperbarui bersama test manual.

Acceptance criteria:
1. Given Jam Operasional repeater dibuka, when row ditambah, diedit, dihapus, dan disimpan, then struktur saved value sama dengan schema existing.
2. Given JavaScript mati, when repeater existing field ditampilkan, then existing values masih bisa dibaca dan form tidak rusak.
3. Given viewport 360, when repeater row diperiksa, then tidak overflow horizontal.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: di tab terkait Jam Operasional, tambah row, edit hari dan jam, hapus row lain, save, reload, export JSON.
3. Expected result: row order benar, values tersimpan, no duplicate index corrupt, no overflow di 360.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/50-p2-jam-operasional-repeater.md`.

Rollback boundary:
1. Jika add atau remove repeater rusak, rollback Task 6.1.

### Task 6.2 Image Picker UX

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/51-p2-image-picker.md`

Ruang edit sama dengan Task 6.1.

Keputusan implementasi:
1. Pertahankan media library WordPress dan selector existing.
2. Perbaiki preview, remove, empty state, dan label action.
3. Jangan ubah format saved image value.

Acceptance criteria:
1. Given image field dibuka, when user pilih gambar dari media library, then preview muncul dan value tersimpan setelah reload.
2. Given user remove image, when save dan reload dilakukan, then value kosong sesuai behavior existing.
3. Given image field diuji tanpa JavaScript, when halaman dibuka, then input hidden dan current value tidak menyebabkan form rusak.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: pilih image di Homepage, save, reload, remove, save, reload.
3. Expected result: preview dan saved value sinkron, remove tidak menyisakan stale preview.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/51-p2-image-picker.md`.

Rollback boundary:
1. Jika media picker WordPress tidak terbuka, rollback Task 6.2.

### Task 6.3 Post Picker Dan Checkbox Picker UX

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/52-p2-selectors.md`

Ruang edit sama dengan Task 6.1.

Keputusan implementasi:
1. Pertahankan key dan array value existing.
2. Perbaiki grouping, search hint jika existing ada, spacing, dan selected state.
3. Jangan mengganti picker dengan dependency baru.

Acceptance criteria:
1. Given checkbox picker dibuka, when user memilih dan melepas beberapa item, then saved array setelah reload sama dengan pilihan.
2. Given post picker dibuka, when user memilih post dan save, then ID yang tersimpan sama dengan pilihan.
3. Given import/export roundtrip dilakukan, when value picker dibandingkan, then array dan ID tetap sama.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: ubah checkbox picker dan post picker di tab yang tersedia, save, reload, export, import kembali file yang sama, reload.
3. Expected result: values tidak hilang, selected state benar, import/export roundtrip unchanged.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/52-p2-selectors.md`.

Rollback boundary:
1. Jika selected state UI berbeda dari saved JSON, rollback Task 6.3.

## Phase 7. P3 Accessibility, Validation, Completeness, Unsaved Change Feedback

### Task 7.1 Accessibility Pass

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/60-p3-accessibility.md`

Ruang edit:
1. `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
2. `wp-content/plugins/rspku-settings/assets/admin.source.css`
3. `wp-content/plugins/rspku-settings/assets/admin.css`
4. `wp-content/plugins/rspku-settings/assets/admin.js`, hanya untuk keyboard behavior progressive enhancement

Keputusan implementasi:
1. Semua input wajib punya label atau `aria-label` yang jelas.
2. Description wajib terhubung dengan `aria-describedby` bila praktis tanpa rewrite.
3. Focus state wajib terlihat.
4. Collapse control wajib keyboard accessible dan menyatakan expanded state.

Acceptance criteria:
1. Given user navigasi dengan keyboard, when melewati tabs, section controls, inputs, image picker buttons, repeater buttons, then focus visible dan urutan masuk akal.
2. Given screen reader semantics diperiksa lewat DOM, when control collapse ada, then `aria-expanded` sinkron dengan state.
3. Given field memiliki description, when DOM diperiksa, then hubungan label dan description tidak hilang.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: keyboard only pada Umum, Kontak, Homepage, Sejarah, Tools. Inspeksi `label[for]`, `aria-expanded`, dan focus style.
3. Expected result: semua control penting bisa dicapai keyboard, focus terlihat, collapse state terbaca.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/60-p3-accessibility.md`.

Rollback boundary:
1. Jika keyboard save atau tab navigation rusak, rollback Task 7.1.

### Task 7.2 Validation Dan Completeness Feedback

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/61-p3-validation-completeness.md`

Ruang edit sama dengan Task 7.1.

Keputusan implementasi:
1. Gunakan validation dan sanitizer existing sebagai sumber kebenaran.
2. Tambah feedback UI untuk completeness hanya pada level presentational.
3. Jangan blok save baru kecuali behavior existing sudah memblokir.
4. Jangan membuat aturan validasi baru yang mengubah data tersimpan tanpa approval.

Acceptance criteria:
1. Given field kosong yang optional, when save dilakukan, then save behavior tidak berubah.
2. Given field URL, when input disimpan, then sanitizer URL existing tetap berlaku.
3. Given section memiliki field penting kosong, when completeness indicator tampil, then indicator tidak mencegah save dan tidak mengubah value.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: kosongkan optional text, isi URL, ubah toggle, save, reload, export JSON.
3. Expected result: behavior sanitizer dan save sama seperti baseline, completeness hanya feedback visual.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/61-p3-validation-completeness.md`.

Rollback boundary:
1. Jika save mulai menolak data yang sebelumnya valid, rollback Task 7.2.

### Task 7.3 Unsaved Change Feedback

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/62-p3-unsaved-feedback.md`

Ruang edit:
1. `wp-content/plugins/rspku-settings/assets/admin.js`
2. `wp-content/plugins/rspku-settings/assets/admin.source.css`
3. `wp-content/plugins/rspku-settings/assets/admin.css`
4. `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`, hanya jika butuh markup status

Keputusan implementasi:
1. Unsaved feedback adalah progressive enhancement.
2. Tanpa JavaScript, form tetap save normal.
3. Feedback tidak boleh mengganti nonce, action, atau submit behavior.
4. Feedback boleh memakai browser confirm saat meninggalkan halaman dengan perubahan belum tersimpan.

Acceptance criteria:
1. Given user mengubah field lalu belum save, when pindah tab atau reload, then user mendapat feedback unsaved change.
2. Given user sudah save, when reload selesai, then feedback unsaved hilang.
3. Given JavaScript mati, when user save form, then behavior tetap existing.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: ubah text, coba pindah tab tanpa save, cancel, save, reload, coba pindah lagi.
3. Expected result: warning muncul hanya saat dirty, tidak muncul setelah save, no data loss.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/62-p3-unsaved-feedback.md`.

Rollback boundary:
1. Jika feedback memblokir submit normal, rollback Task 7.3.

## Phase 8. Full Admin, Public Regression, Dan Release

### Task 8.1 Admin Save Reload Matrix

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/70-admin-save-reload-matrix.md`

Ruang edit: hanya test atau evidence jika ada gap. Jangan refactor UI baru di phase ini kecuali fixing bug hasil QA.

Wajib diuji di tab Umum, Kontak, Homepage, Sejarah, Tools.

Field type wajib diuji:
1. text
2. textarea
3. URL
4. toggle
5. image
6. repeater
7. checkbox picker
8. post picker

Acceptance criteria:
1. Given setiap field type diubah, when save dan reload dilakukan, then value tetap tersimpan.
2. Given save dilakukan beberapa kali, when export JSON dibandingkan, then tidak ada settings loss pada field yang tidak disentuh.
3. Given viewport 1440, 1024, 782, 360, when admin UI diperiksa, then tidak ada action bar overlap atau field horizontal overflow.

QA scenario:
1. Tool atau command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
php -l includes/class-rspku-settings-admin.php
```

2. Steps: jalankan matrix field type di Umum, Kontak, Homepage, Sejarah, Tools. Export sebelum dan sesudah. Bandingkan key count dan touched values.
3. Expected result: touched values berubah sesuai input, untouched values sama, no overlap di semua viewport.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/70-admin-save-reload-matrix.md`.

Rollback boundary:
1. Jika ada settings loss, rollback phase terakhir yang menyentuh renderer atau JS selector.

### Task 8.2 Import Export Roundtrip Unchanged

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/71-import-export-roundtrip.md`

Ruang edit: tidak ada kecuali bug fix hasil QA.

Acceptance criteria:
1. Given export JSON baseline dibuat, when file diimport kembali, then values tetap sama.
2. Given import sukses, when admin reload dan export lagi, then key set dan value set sama dengan file import.
3. Given file invalid diimport, when request diproses, then error behavior existing tetap sama.

QA scenario:
1. Tool atau command: admin Tools import dan export.
2. Steps: export file A, import file A, export file B, compare A dan B. Coba invalid JSON kecil.
3. Expected result: roundtrip unchanged, invalid JSON tetap memberi error existing, no schema drift.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/71-import-export-roundtrip.md`.

Rollback boundary:
1. Jika import/export berubah shape, rollback semua perubahan terkait renderer metadata yang ikut tersimpan secara salah.

### Task 8.3 Public Smoke Pages

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/72-public-smoke.md`

Ruang edit: tidak ada kecuali bug fix hasil QA.

Public pages wajib tetap unchanged setelah save:
1. `/`
2. `/kontak/`
3. `/dokter/`
4. `/sejarah-kami/`

Acceptance criteria:
1. Given settings disimpan dari admin, when `/` dibuka, then page render tanpa error dan output setting sesuai baseline atau value yang sengaja diubah.
2. Given settings disimpan dari admin, when `/kontak/` dibuka, then contact values tetap render.
3. Given settings disimpan dari admin, when `/dokter/` dibuka, then page render tidak terpengaruh refactor admin.
4. Given settings disimpan dari admin, when `/sejarah-kami/` dibuka, then sejarah content tetap render.

QA scenario:
1. Tool atau command: browser dan optional `curl` lokal bila environment tersedia.
2. Steps: setelah final admin save, buka empat URL, cek HTTP 200, cek tidak ada PHP fatal, cek konten utama tetap tampil.
3. Expected result: semua page smoke pass, tidak ada redesign public pages.
4. Evidence file path: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/72-public-smoke.md`.

Rollback boundary:
1. Jika public page berubah karena key atau output berubah, rollback fase penyebab sebelum release.

## Final Verification Wave F1 Sampai F4

### F1 Code Contract Verdict

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/F1-code-contract.txt` verdict APPROVE

Command:

```bash
cd wp-content/plugins/rspku-settings
php -l includes/class-rspku-settings-admin.php
npm run build:css
npm test
```

APPROVE jika PHP lint pass, CSS build pass, CSS test pass, dan `assets/admin.css` generated dari `assets/admin.source.css`.  
REJECT jika satu command gagal atau generated CSS tidak ikut diperbarui.

Evidence: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/F1-code-contract.txt`.

### F2 Admin Functional Verdict

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/F2-admin-functional.md` verdict APPROVE

APPROVE jika Umum, Kontak, Homepage, Sejarah, Tools lulus save reload untuk text, textarea, URL, toggle, image, repeater, checkbox picker, dan post picker.  
REJECT jika ada value hilang, selector JS rusak, nonce atau capability behavior berubah, atau `admin-post` behavior berubah.

Evidence: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/F2-admin-functional.md`.

### F3 Layout Accessibility Verdict

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/F3-layout-accessibility.json` verdict APPROVE

APPROVE jika satu action bar saja, tidak ada overlap pada width 1440, 1024, 782, 360, tidak ada overflow horizontal, keyboard access lulus, label dan aria dasar lulus.  
REJECT jika field overlap, action bar duplicate, focus hilang, atau collapsed section menyembunyikan error.

Evidence: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/F3-layout-accessibility.json`.

### F4 Data Public Release Verdict

- [x] Completed: evidence `.sisyphus/evidence/rspku-settings-admin-ui-refactor/F4-data-public-release.md` verdict APPROVE

APPROVE jika import/export roundtrip unchanged, public smoke `/`, `/kontak/`, `/dokter/`, `/sejarah-kami/` pass, option key/schema unchanged, field keys unchanged, sanitizer unchanged, API/public output unchanged.  
REJECT jika schema drift, key rename, import/export mismatch, atau public page error.

Evidence: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/F4-data-public-release.md`.

## Release Gate

1. Worktree wajib bersih dari unrelated dirty main changes.
2. Branch wajib berasal dari latest `origin/main`.
3. Jangan commit, push, atau deploy tanpa explicit user request.
4. Untuk plugin CSS deploy, sertakan keduanya:
   1. `wp-content/plugins/rspku-settings/assets/admin.source.css`
   2. `wp-content/plugins/rspku-settings/assets/admin.css`
5. Theme `wp-content/themes/rspku-theme/public/build/` tidak terlibat kecuali theme source berubah. Plan ini tidak mengubah theme source.
6. Jangan commit `wp-content/themes/rspku-theme/public/build/`.
7. Final pre release command:

```bash
git status --short
git diff -- wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php wp-content/plugins/rspku-settings/assets/admin.source.css wp-content/plugins/rspku-settings/assets/admin.css wp-content/plugins/rspku-settings/assets/admin.js wp-content/plugins/rspku-settings/tests/admin-css.test.mjs
cd wp-content/plugins/rspku-settings
npm run build:css
npm test
```

Acceptance criteria:
1. Given user belum meminta commit, when implementation selesai, then tidak ada commit, push, deploy.
2. Given final diff dicek, when files listed, then hanya perubahan terkait plugin admin UI dan evidence yang masuk.
3. Given plugin CSS berubah, when diff dicek, then source dan generated CSS sama sama ada.
4. Given theme source tidak berubah, when release dicek, then theme `public/build/` tidak disentuh.

Evidence: `.sisyphus/evidence/rspku-settings-admin-ui-refactor/release-gate.txt`.

## Definition Of Done

1. Semua phase selesai berurutan.
2. Semua rollback boundary tidak terpicu atau sudah diselesaikan dengan fix minimal.
3. F1, F2, F3, dan F4 memiliki verdict APPROVE.
4. Tidak ada rename settings key.
5. Tidak ada schema atau DB migration.
6. Tidak ada dependency baru.
7. Tidak ada rewrite penuh.
8. Admin UI lebih jelas, native WordPress compatible, accessible, responsive, dan tetap boring.
