# Requirements: Modern Single Post Reading Experience

## Introduction

Dokumen ini mendefinisikan requirement fungsional dan non-fungsional untuk transformasi halaman single post (`is_singular('post')`) RS PKU Muhammadiyah Yogyakarta menjadi pengalaman baca modern yang fokus pada keterbacaan, kepercayaan (trust), dan engagement.

**Ruang lingkup:** hanya halaman single post jenis artikel/berita. CPT lain (`dokter`, `poliklinik`, `layanan`, `jurnal`, `rawat-inap`, `manajemen-rs`) **tidak** termasuk.

**Sasaran pengguna:**
- Pasien & keluarga pasien mencari informasi kesehatan
- Pengunjung umum mencari berita rumah sakit
- Profesional kesehatan merujuk edukasi kesehatan

**Baseline saat ini:** `resources/views/pages/single-post.twig` sudah punya breadcrumb, metadata bar, title, author mini-card, lead, featured image, content, tags, share buttons, author bio, sidebar (info + popular), related articles.

**Gap fungsional yang akan ditutup:**
- Table of Contents (TOC) otomatis
- Reading progress bar
- Sticky/floating share buttons
- End-of-article CTA konsultasi
- Peningkatan tipografi & spacing untuk keterbacaan optimal
- Aksesibilitas (heading order, ARIA, keyboard nav)
- Performance (lazy load, preload, minimal CLS)

---

## Requirements

### R1 — Article Header Yang Informatif & Dapat Dipindai

**User Story:** Sebagai pembaca, saya ingin langsung memahami konteks artikel (kategori, kebaruan, penulis, durasi baca) dalam ≤3 detik, agar bisa memutuskan relevansi sebelum menginvestasikan waktu membaca.

**Acceptance Criteria:**

1. WHEN halaman single post dimuat, THEN sistem harus menampilkan breadcrumb dengan urutan Home → Berita & Artikel → [Kategori Primer] → [Judul].
2. WHEN post memiliki kategori, THEN sistem harus menampilkan kategori primer sebagai chip di atas judul dengan styling `hospital-700`.
3. WHEN post memiliki `post_modified` ≠ `post_date`, THEN sistem harus menampilkan kedua tanggal dengan label "Diterbitkan" dan "Diperbarui".
4. WHEN post memiliki konten ≥50 kata, THEN sistem harus menghitung reading time (200 kata/menit) dan menampilkannya dengan ikon jam.
5. WHEN judul melebihi 3 baris di desktop, THEN sistem harus membatasi ke maksimal 3 baris dengan `line-clamp` dan tetap readable di mobile.
6. IF post memiliki `post_excerpt` atau paragraf pertama, THEN sistem harus menampilkan lead paragraph dengan tipografi `rspku-section-lead` (≥18px, warna mute).

---

### R2 — Trust Signal: Author Card

**User Story:** Sebagai pembaca edukasi kesehatan, saya ingin tahu kredibilitas penulis sebelum percaya pada informasi medis, agar saya tidak terpengaruh sumber tidak terverifikasi.

**Acceptance Criteria:**

1. WHEN post memiliki author, THEN sistem harus menampilkan author mini-card di header: avatar, nama, role/jabatan (jika tersedia).
2. WHEN post memiliki author dengan `description` (bio), THEN sistem harus menampilkan author bio card lengkap di akhir artikel dengan avatar besar (96px), nama, role, bio (maks 3 baris), dan link ke profil (jika ada).
3. IF author tidak memiliki avatar custom, THEN sistem harus menampilkan inisial huruf pertama nama dengan background `hospital-100` dan warna `hospital-700`.
4. WHEN author memiliki meta `_rspku_author_credentials` (ACF/user meta), THEN sistem harus menampilkannya sebagai baris kredensial di bawah nama (mis. "Sp.A., M.Kes.").

---

### R3 — Typography & Reading Container

**User Story:** Sebagai pembaca, saya ingin membaca artikel panjang tanpa lelah mata, dengan line-length optimal dan spacing yang nyaman.

**Acceptance Criteria:**

1. WHEN viewport ≥ `lg` (1024px), THEN lebar kolom konten harus 65-75 karakter (sekitar 680-720px) untuk paragraf teks.
2. WHEN konten artikel di-render, THEN `line-height` paragraf harus minimal 1.7 dan ukuran font body minimal 1.0625rem (17px).
3. WHEN viewport ≥ `md` (768px), THEN margin antar paragraf harus 1.25-1.5em untuk memberi jeda baca.
4. WHEN heading (`h2`, `h3`) muncul dalam konten, THEN heading harus punya margin-top 2em dan margin-bottom 0.75em untuk visual separation.
5. WHEN ada gambar dalam konten, THEN gambar harus `max-width: 100%`, `rounded-lg`, dan punya caption (jika `figure > figcaption`).
6. WHEN ada quote (`blockquote`), THEN quote harus punya border-left `hospital-500`, italic, dan padding-left cukup.
7. WHEN viewport < `md`, THEN tipografi tetap readable: body 16px, line-height 1.7, padding horizontal 1rem.

---

### R4 — Auto-Generated Table of Contents (TOC)

**User Story:** Sebagai pembaca artikel panjang (>600 kata dengan heading), saya ingin melompat ke bagian spesifik dan memahami struktur artikel, agar bisa membaca selektif.

**Acceptance Criteria:**

1. WHEN konten artikel memiliki ≥2 heading `h2` atau `h3`, THEN sistem harus generate TOC otomatis dari heading tersebut.
2. WHEN TOC di-generate, THEN tiap heading harus diberi `id` slug yang unik (dari `sanitize_title` judul heading).
3. WHEN link TOC di-klik, THEN halaman harus scroll halus ke heading dengan offset header sticky (kalau ada).
4. WHEN viewport ≥ `xl` (1280px), THEN TOC harus sticky di sidebar kiri (atau kanan atas, menggantikan sidebar "Info artikel" saat scroll sampai konten).
5. WHEN viewport < `xl`, THEN TOC harus dapat di-collapse/di-toggle (accordion) di atas artikel body, default collapsed.
6. WHEN pembaca scroll, THEN heading yang sedang di-viewport harus di-highlight di TOC (menggunakan IntersectionObserver).
7. WHEN TOC muncul, THEN harus ada heading "Daftar Isi" dengan font semibold dan ikon `list`.
8. IF artikel < 2 heading, THEN TOC tidak ditampilkan (tidak ada DOM sampah).

---

### R5 — Reading Progress Bar

**User Story:** Sebagai pembaca artikel panjang, saya ingin tahu progress baca saya, agar bisa memperkirakan berapa lagi sampai selesai.

**Acceptance Criteria:**

1. WHEN user scroll di article body, THEN progress bar di `top: 0` viewport harus update real-time.
2. WHEN scroll position 0, THEN progress bar = 0%.
3. WHEN scroll position mencapai akhir article body (bukan footer/related), THEN progress bar = 100%.
4. WHEN progress bar di-render, THEN harus pakai `position: fixed` dengan tinggi 3px, warna `hospital-500`, z-index di atas header.
5. WHEN `prefers-reduced-motion: reduce`, THEN tidak ada animasi transisi pada progress bar (langsung snap).
6. WHEN JavaScript dinonaktifkan, THEN progress bar tidak ditampilkan (graceful degradation, tidak ada DOM kosong).

---

### R6 — Sticky / Floating Share Buttons

**User Story:** Sebagai pembaca yang terinspirasi di tengah artikel, saya ingin membagikan artikel tanpa scroll ke atas atau bawah.

**Acceptance Criteria:**

1. WHEN viewport ≥ `lg`, THEN tombol share harus muncul sebagai floating vertical di sisi kiri article body (posisi `sticky`, top 30% viewport).
2. WHEN viewport < `lg`, THEN tombol share inline saja (tidak sticky) untuk hemat ruang.
3. WHEN share button di-klik, THEN harus:
   - WhatsApp: buka `https://wa.me/?text={encoded title + URL}`
   - Facebook: `https://www.facebook.com/sharer/sharer.php?u={URL}`
   - Twitter/X: `https://twitter.com/intent/tweet?text={title}&url={URL}`
   - Copy Link: copy URL ke clipboard + tampilkan toast "Link disalin"
4. WHEN tombol share dimuat, THEN masing-masing harus punya `aria-label` deskriptif dan ikon visual (Lucide icons).
5. WHEN user scroll sampai area author bio card, THEN floating share boleh di-hide (tidak menutupi CTA).
6. WHEN Copy Link berhasil, THEN `navigator.clipboard.writeText` harus dipakai dengan fallback `document.execCommand('copy')` untuk browser lama.

---

### R7 — End-of-Article Konsultasi CTA

**User Story:** Sebagai pembaca yang tertarik setelah baca artikel kesehatan, saya ingin tahu cara melanjutkan ke konsultasi atau layanan terkait, agar bisa take action saat momentum masih tinggi.

**Acceptance Criteria:**

1. WHEN pembaca mencapai akhir article body (sebelum tags/share/related), THEN sistem harus menampilkan CTA card "Butuh Konsultasi?".
2. WHEN CTA muncul, THEN harus berisi:
   - Heading "Konsultasikan dengan dokter kami"
   - Deskripsi 1-2 baris
   - 2 tombol: "Cari Dokter" (link ke `/dokter/` atau `/cari-dokter/`) dan "Jadwal Poliklinik" (link ke `/poliklinik/` atau `/jadwal-dokter/`)
3. IF post memiliki tag yang cocok dengan slug poliklinik tertentu, THEN CTA harus prioritaskan link ke poliklinik relevan (contextual CTA).
4. WHEN CTA dirender di mobile, THEN tombol harus stack vertical, full-width, dengan `min-height: 44px` untuk touch target.
5. WHEN CTA dirender, THEN styling harus pakai `hospital-50` background dengan border `hospital-200` untuk visual distinct dari content.

---

### R8 — Related Articles Yang Relevan

**User Story:** Sebagai pembaca yang selesai baca artikel, saya ingin rekomendasi artikel lain yang relevan (bukan random), agar engagement berlanjut.

**Acceptance Criteria:**

1. WHEN artikel selesai, THEN sistem harus menampilkan 3 artikel related di bawah area konten.
2. WHEN related di-query, THEN algoritma prioritas:
   - Prioritas 1: artikel dalam kategori yang sama, diurut by date desc
   - Prioritas 2: artikel dengan tag yang sama (jika jumlah kategori <3)
   - Prioritas 3: artikel terbaru umum (fallback)
3. WHEN related card dirender, THEN tiap card harus: thumbnail, kategori chip, judul (2 baris max), tanggal.
4. WHEN related tidak ada yang memenuhi kriteria, THEN section tidak ditampilkan (tidak ada "No posts found" message).
5. WHEN viewport < `md`, THEN related harus stack vertical (1 kolom).
6. WHEN viewport `md`..`xl`, THEN 2 kolom.
7. WHEN viewport ≥ `xl`, THEN 3 kolom.

---

### R9 — Sidebar Yang Berguna (Desktop Only)

**User Story:** Sebagai pembaca desktop, saya ingin konteks dan navigasi tambahan di sidebar tanpa mengganggu fokus baca.

**Acceptance Criteria:**

1. WHEN viewport ≥ `xl`, THEN sidebar harus muncul di kanan dengan lebar 20rem (320px), posisi sticky.
2. WHEN sidebar dirender, THEN harus berisi (urutan):
   - TOC (jika artikel punya heading ≥2) — *lihat R4*
   - Info artikel (tanggal, penulis, kategori, reading time)
   - Artikel populer (top 5 by view count, fallback top 5 terbaru)
3. WHEN viewport < `xl`, THEN sidebar tidak ditampilkan (content full-width).
4. WHEN TOC muncul di sidebar desktop, THEN section "Info artikel" turun posisinya di bawah TOC.

---

### R10 — Aksesibilitas (WCAG 2.2 AA)

**User Story:** Sebagai pengguna dengan screen reader atau keyboard-only, saya ingin bisa membaca dan navigasi artikel tanpa hambatan.

**Acceptance Criteria:**

1. WHEN halaman dimuat, THEN heading order harus hierarkis: `h1` (judul) → `h2` (section headings TOC) → `h3` (sub-sections) tanpa skip level.
2. WHEN link TOC dan share di-focus via keyboard, THEN harus ada focus ring visible dengan kontras ≥3:1.
3. WHEN reading progress bar update, THEN tidak boleh memicu `aria-live` announcement (bising untuk screen reader). Hanya `aria-hidden="true"`.
4. WHEN TOC sticky di sidebar, THEN harus pakai `<nav aria-label="Daftar isi artikel">`.
5. WHEN tombol share di-klik, THEN status (success copy) harus diumumkan ke screen reader via `aria-live="polite"` region.
6. WHEN ada gambar konten tanpa alt, THEN sistem harus log warning di PHP (development) agar editor lebih disiplin; tapi tidak boleh break halaman.
7. WHEN viewport zoom ke 200%, THEN konten harus tetap readable tanpa horizontal scroll (kecuali tabel kompleks).
8. WHEN user pakai keyboard Tab, THEN urutan fokus harus: breadcrumb → meta → title → (skip to content) → TOC → content → share → related.
9. WHEN `prefers-reduced-motion: reduce`, THEN semua transisi ≥300ms harus disable atau dipangkas <100ms.

---

### R11 — Performance

**User Story:** Sebagai pengunjung mobile dengan koneksi lambat, saya ingin artikel muncul dengan cepat agar tidak bounce.

**Acceptance Criteria:**

1. WHEN halaman dimuat di mobile 3G, THEN Largest Contentful Paint (LCP) harus < 2.5 detik.
2. WHEN halaman dimuat, THEN Cumulative Layout Shift (CLS) harus < 0.1.
3. WHEN interaksi pertama, THEN Interaction to Next Paint (INP) harus < 200ms.
4. WHEN featured image dirender, THEN harus pakai `loading="eager"` + `fetchpriority="high"` (karena dia kandidat LCP).
5. WHEN gambar inline di konten, THEN harus pakai `loading="lazy"` + `decoding="async"`.
6. WHEN featured image dimuat, THEN harus ada `width` dan `height` explicit untuk hindari CLS.
7. WHEN JS TOC/progress bar dimuat, THEN harus lewat Vite manifest (code-splitting), tidak blocking render.
8. WHEN font dimuat, THEN harus `font-display: swap` atau preload.

---

### R12 — SEO & Social Sharing

**User Story:** Sebagai pengunjung yang datang dari Google atau share WhatsApp, saya ingin judul, meta, dan gambar preview akurat.

**Acceptance Criteria:**

1. WHEN halaman di-crawl, THEN harus ada JSON-LD `Article` schema (Heading, author, datePublished, dateModified, image, publisher).
2. WHEN link artikel di-share ke WhatsApp/FB/Twitter, THEN preview harus menggunakan Yoast SEO title/description/og:image.
3. WHEN artikel punya tag kesehatan, THEN JSON-LD schema `Article` harus `@type: MedicalScholarlyArticle` untuk artikel edukasi medis.
4. WHEN breadcrumb dirender, THEN harus ada JSON-LD `BreadcrumbList` schema.
5. WHEN canonical URL ada, THEN `<link rel="canonical">` harus menunjuk ke URL bersih (tanpa query string `?utm_*` dll).

(Catatan: implementasi JSON-LD untuk schema akan ditangani di modul M7 — plugin `rspku-schema`.)

---

### R13 — Mobile Parity

**User Story:** Sebagai pengguna mobile (>70% trafik), saya ingin pengalaman baca yang sama nyamannya dengan desktop.

**Acceptance Criteria:**

1. WHEN viewport < `md`, THEN semua elemen (TOC, share, CTA) tetap accessible via alternative layout (TOC accordion, share inline, CTA full-width).
2. WHEN user tap touch target, THEN ukuran minimum 44×44 px (WCAG 2.2).
3. WHEN user scroll di mobile, THEN tidak ada horizontal overflow di manapun.
4. WHEN user rotate device, THEN layout harus responsive tanpa broken state.

---

## Non-Functional Requirements

### NFR1 — Browser Support

Harus berfungsi di: Chrome 90+, Safari 14+, Firefox 88+, Edge 90+. Graceful degradation di browser lama (TOC & progress bar fallback ke static saja).

### NFR2 — Kompatibilitas Content Existing

Implementasi **tidak boleh** membuat artikel lama (Classic Editor / Gutenberg / Elementor) rusak tampilannya. Harus tetap render content lama dengan baik.

### NFR3 — Backward Compatibility API

Tidak ada perubahan breaking di REST API (`/wp-json/rspku/v1/posts`). Bisa tambah field baru (misal `toc`, `reading_time_minutes`), tapi tidak ubah/hapus field existing.

### NFR4 — Localization

Semua string UI baru harus dibungkus `__()` dengan text domain `rspku-theme`. Default bahasa: Indonesian (`id_ID`).

### NFR5 — Testability

Fitur logic (TOC generator, reading time calculator, related article scoring) harus bisa di-unit-test via PHPUnit. Tidak boleh dibenamkan di Twig tanpa service layer.

---

## Out of Scope

Hal-hal berikut **tidak** termasuk dalam spec ini dan akan ditangani terpisah:

- Komentar artikel (pakai WP native atau plugin komentar lain)
- Newsletter subscription (butuh integrasi mail service provider — fase terpisah)
- View counter (sudah ada `wp-postviews` plugin, tidak diubah)
- A/B testing framework
- Dark mode
- Print stylesheet
- Offline reading (PWA)
- Audio narration
- Translation toggle (sudah ada `gtranslate` plugin)

---

## Dependencies

- **M7 (rspku-schema plugin):** JSON-LD injection untuk R12.
- **M8 (caching layer):** Performance related articles query untuk R8.
- **M9 (i18n):** String localization untuk NFR4.
- **Tailwind config:** token typography & spacing sudah ada di `tailwind.config.js`.
- **Alpine.js:** untuk interaktivitas TOC toggle, progress bar, copy link.
- **Lucide icons:** sudah tersedia via `Icon::svg()`.

---

## Glossary

- **TOC:** Table of Contents — daftar isi otomatis dari heading artikel
- **LCP:** Largest Contentful Paint — Core Web Vital metric
- **CLS:** Cumulative Layout Shift — Core Web Vital metric
- **INP:** Interaction to Next Paint — Core Web Vital metric (successor FID)
- **Lead paragraph:** paragraf pembuka yang merangkum artikel (mirip dek koran)
- **Trust signal:** elemen visual yang membangun kredibilitas (author, kredensial, sumber)
- **Reading container:** kolom tempat body artikel dirender
- **EARS:** Easy Approach to Requirements Syntax — format acceptance criteria WHEN/THEN
