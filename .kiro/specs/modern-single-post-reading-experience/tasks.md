# Tasks: Modern Single Post Reading Experience

## Overview

Task breakdown untuk implementasi fitur-fitur di `requirements.md`. Diorganisir dalam 5 phase:

- **Phase 0 — Prework & Contract** *(tidak menyentuh UI, non-blocking)*
- **Phase 1 — Data Layer & Services** *(backend PHP, tidak menyentuh UI)*
- **Phase 2 — View Components (Twig + CSS)** *(UI work)*
- **Phase 3 — Interactive Enhancements (JS/Alpine)** *(UI work)*
- **Phase 4 — Verification & Polish** *(mix)*

User preference: **Phase 0 & 1 dikerjakan dulu modular, Phase 2+ menyusul**.

Tiap task diakhiri dengan referensi ke requirement yang dipenuhi (format `_Requirements: Rx.y_`).

---

## Phase 0 — Prework & Contract

### 0.1 Validasi Baseline
- [ ] Screenshot `single-post.twig` saat ini di 3 viewport (mobile 375, tablet 768, desktop 1440) sebagai baseline before/after
- [ ] Dokumentasikan fitur yang sudah ada vs gap di Issues GitHub / catatan
- [ ] Pilih 3 artikel representatif sebagai test case: (1) artikel panjang dengan banyak heading, (2) artikel pendek tanpa heading, (3) artikel dengan gambar inline & quote
- _Verifikasi acuan untuk R1, R3, R4, R5_

### 0.2 Typography Tokens Finalization
- [ ] Audit `tailwind.config.js` dan `resources/css/app.css` untuk `rspku-prose`, `rspku-section-lead`, `rspku-page-title`
- [ ] Pastikan token line-height, font-size untuk body artikel sesuai R3 (line-height ≥1.7, font-size ≥17px)
- [ ] Tambahkan utility class `rspku-reading-container` dengan `max-width: 68ch` kalau belum ada
- _Requirements: R3.1, R3.2, R3.3_

### 0.3 Inventarisasi Helper Existing
- [ ] Cek `app/Models/EnhancedPost.php` — `reading_time()` sudah ada
- [ ] Cek `app/Helpers/ReadingTime.php` — sudah ada, review apakah akurat untuk content ACF
- [ ] Daftar helper yang perlu baru: `TocGenerator`, `RelatedArticlesScorer`
- _Requirements: R1.4, R4, R8_

---

## Phase 1 — Data Layer & Services (Backend)

### 1.1 TocGenerator Helper
- [ ] Bikin `app/Helpers/TocGenerator.php` dengan method static `fromHtml(string $html): array`
- [ ] Input: post content HTML; parse heading `h2` dan `h3` pakai `DOMDocument`
- [ ] Output: nested array `[{id, level, text, anchor}, …]` dengan anchor unik
- [ ] Inject `id` ke heading di HTML output menggunakan `DOMDocument` (return HTML yang sudah di-augment)
- [ ] Handle edge case: heading tanpa teks, heading kosong, karakter unicode di slug
- [ ] Unit test: minimum 5 skenario (no heading, 2 headings, nested h2+h3, heading dengan emoji, duplicate text → slug berbeda)
- _Requirements: R4.1, R4.2, R4.8_

### 1.2 Related Articles Scorer
- [ ] Refactor `ContentRepository::relatedArticles()` untuk pakai scoring:
  - Score 100: same category
  - Score 50: same tag
  - Score 10: recent fallback
- [ ] Cache hasil via transient `rspku_related_{post_id}` dengan TTL 6 jam
- [ ] Invalidate cache di `save_post`, `deleted_post`
- [ ] Unit test untuk scorer
- _Requirements: R8.2, R8.4_

### 1.3 Single Post Context Extender
- [ ] Tambah method baru di `TemplateController::articleSingleContext()`:
  - `toc`: array dari `TocGenerator`
  - `has_toc`: bool (true kalau ≥2 heading)
  - `content_with_anchors`: HTML content yang sudah ditambahi `id` heading
  - `cta`: array `{heading, description, links: [cari-dokter, poliklinik]}` — contextual kalau ada tag match, default generic
- [ ] Pisahkan `articleSingleContext` ke class baru `app/Contexts/ArticleSingleContext.php` agar `TemplateController` tidak bengkak
- _Requirements: R4, R7_

### 1.4 Contextual CTA Mapper
- [ ] Bikin `app/Services/ArticleCtaMapper.php`
- [ ] Input: `WP_Post` (artikel)
- [ ] Logic: cek tag post → cari polyclinic dengan slug/nama mirip → kalau match, CTA pakai link poliklinik tersebut. Kalau tidak, generic ke `/dokter/` + `/poliklinik/`
- [ ] Unit test: 3 skenario (tag match, partial match, no match)
- _Requirements: R7.3_

### 1.5 Enhanced Post — Credentials Field
- [ ] Extend `app/Models/EnhancedPost.php` dengan method `author_credentials(): string`
- [ ] Ambil dari user meta `_rspku_author_credentials` kalau ada
- [ ] Helper di admin: tambahkan field user profile (via `show_user_profile` + `edit_user_profile` hook) untuk input kredensial
- _Requirements: R2.4_

### 1.6 Reading Time Accuracy
- [ ] Review `ReadingTime::calculate()` — pastikan strip tag HTML, shortcode, comment
- [ ] Handle bahasa Indonesia: 200 WPM reasonable tapi kalau banyak istilah medis latin, perlu threshold berbeda? → default 200 dulu, tuning belakangan
- _Requirements: R1.4_

### 1.7 Unit Test Setup (jika belum ada)
- [ ] Tambah `phpunit.xml.dist`, `composer require --dev phpunit/phpunit`
- [ ] Bootstrap file yang load WP test env atau gunakan Brain Monkey/WP_Mock untuk mock WP
- [ ] Test direktori: `tests/Unit/Helpers`, `tests/Unit/Services`, `tests/Unit/Models`
- [ ] Dihook ke CI (dikerjakan juga di M4)
- _Requirements: NFR5_

---

## Phase 2 — View Components (Twig + CSS) 🎨 *UI work*

### 2.1 Restructure single-post.twig
- [ ] Ekstrak komponen dari `pages/single-post.twig` ke partials baru:
  - `partials/article-header.twig` (breadcrumb, meta, title, author mini, lead)
  - `partials/article-featured-image.twig` (dengan LCP optimization)
  - `partials/article-body.twig` (content-with-anchors + inline share)
  - `partials/article-author-bio.twig` (full card dengan kredensial)
  - `partials/article-cta.twig` (konsultasi CTA)
  - `components/toc.twig` (reusable TOC, desktop + mobile variant)
  - `components/reading-progress.twig` (bar DOM element)
  - `components/floating-share.twig` (desktop vertical)
- _Requirements: R1, R2, R4, R5, R6, R7_

### 2.2 Typography CSS
- [ ] Tambah/revisi `resources/css/app.css` section `.rspku-prose` untuk memenuhi R3
- [ ] Utility `.rspku-reading-container` untuk max-width 68ch
- [ ] Style `blockquote`, `figure`, `figcaption`, `ul`, `ol`, `code`, `pre` dalam `.rspku-prose`
- _Requirements: R3_

### 2.3 Author Card Styling
- [ ] Styling ava fallback dengan inisial + background `hospital-100`
- [ ] Layout author bio card responsive
- _Requirements: R2.1, R2.2, R2.3, R2.4_

### 2.4 Table of Contents Component
- [ ] Twig partial `components/toc.twig` render list nested (h2 → h3 children)
- [ ] Desktop: sticky sidebar (dalam `aside`, di atas "Info artikel")
- [ ] Mobile: accordion `<details>` di atas article body, default collapsed
- [ ] Use `<nav aria-label="Daftar isi artikel">`
- _Requirements: R4.3, R4.4, R4.5, R4.7, R10.4_

### 2.5 Reading Progress Bar Component
- [ ] Twig partial: DOM bar element dengan `aria-hidden="true"`
- [ ] Position fixed top 0, height 3px, background `hospital-500`
- _Requirements: R5.4, R10.3_

### 2.6 Floating Share Component
- [ ] Twig partial: vertical share di sidebar kiri (desktop only)
- [ ] Inline share tetap di bawah content (mobile fallback)
- [ ] Tombol Copy Link dengan toast area `aria-live="polite"`
- _Requirements: R6, R10.5_

### 2.7 End-of-Article CTA Component
- [ ] Twig partial dengan styling `hospital-50` + border `hospital-200`
- [ ] 2 tombol CTA (Cari Dokter, Poliklinik) full-width di mobile
- _Requirements: R7_

### 2.8 Related Articles Grid (kalau perlu refactor)
- [ ] Review `components/content-card.twig` untuk support artikel
- [ ] Kalau perlu, buat varian `components/related-article-card.twig`
- [ ] Grid responsive 1→2→3 kolom
- _Requirements: R8.3, R8.5, R8.6, R8.7_

### 2.9 Rangkai Ulang single-post.twig
- [ ] Rewrite `pages/single-post.twig` pakai partial-partial baru
- [ ] Layout 3 kolom di `xl`: share (sticky kiri) | content | sidebar (TOC + info + popular)
- [ ] Layout 1 kolom di mobile/tablet dengan TOC accordion & inline share
- _Requirements: R9, R13_

---

## Phase 3 — Interactive Enhancements (JS/Alpine) 🎨 *UI work*

### 3.1 Reading Progress Logic
- [ ] Alpine component / vanilla module `resources/js/modules/reading-progress.js`
- [ ] Listen ke `scroll` passive, hitung % terhadap article body (bukan full page)
- [ ] Respect `prefers-reduced-motion`
- _Requirements: R5.1, R5.2, R5.3, R5.5, R10.9_

### 3.2 TOC Active State (IntersectionObserver)
- [ ] Observasi heading `h2`/`h3` dalam article body
- [ ] Highlight link TOC untuk heading yang sedang di-viewport
- [ ] Debounce untuk kinerja
- _Requirements: R4.6_

### 3.3 Smooth Scroll with Offset
- [ ] Handle klik TOC link: `scrollIntoView({ behavior: 'smooth', block: 'start' })` + offset header sticky
- [ ] Update URL hash tanpa jump default
- _Requirements: R4.3_

### 3.4 Copy Link with Clipboard API
- [ ] Implementasi `navigator.clipboard.writeText()` + fallback `document.execCommand('copy')`
- [ ] Toast / aria-live announcement
- _Requirements: R6.3 (Copy Link), R6.6, R10.5_

### 3.5 TOC Mobile Accordion
- [ ] Native `<details>` atau Alpine toggle
- [ ] Default `closed` di mobile
- _Requirements: R4.5_

### 3.6 Wire Up ke Vite Build
- [ ] Entry point `resources/js/modules/single-post.js` yang bundle ketiga module di atas
- [ ] Conditional load di `Assets::enqueueFrontend()` — hanya di `is_singular('post')`
- _Requirements: R11.7_

---

## Phase 4 — Verification & Polish

### 4.1 Accessibility Audit
- [ ] Run axe DevTools & WAVE di 3 test article
- [ ] Manual keyboard navigation audit: Tab order sesuai R10.8
- [ ] Screen reader test (NVDA): heading order, TOC nav, copy confirmation
- [ ] Zoom 200% audit
- _Requirements: R10_

### 4.2 Performance Audit
- [ ] Lighthouse score target: Performance ≥90, Accessibility ≥95, Best Practices ≥95, SEO ≥95
- [ ] Verifikasi Core Web Vitals: LCP <2.5s, CLS <0.1, INP <200ms
- [ ] Verifikasi featured image LCP optimization (`fetchpriority="high"`, explicit w/h)
- _Requirements: R11_

### 4.3 Cross-browser Test
- [ ] Test di Chrome, Safari, Firefox, Edge (terbaru)
- [ ] Fallback graceful di browser tanpa IntersectionObserver (TOC tetap static)
- _Requirements: NFR1_

### 4.4 Cross-device Test
- [ ] Device real: iPhone SE, iPhone 14 Pro, Android mid-range, iPad
- [ ] Rotation test
- _Requirements: R13, NFR1_

### 4.5 Content Compatibility Test
- [ ] Test dengan 5+ artikel real dari database, variasi:
  - Artikel panjang dengan banyak heading
  - Artikel pendek tanpa heading
  - Artikel dengan Elementor content
  - Artikel dengan shortcode
  - Artikel dengan embed (YouTube, Twitter)
- _Requirements: NFR2_

### 4.6 REST API Backward Compat Check
- [ ] Request `/wp-json/rspku/v1/posts/{slug}` sebelum & sesudah perubahan
- [ ] Pastikan field existing tidak berubah format
- [ ] Tambah `toc` & `reading_time_minutes` sebagai field baru (opt-in)
- _Requirements: NFR3_

### 4.7 Playwright Snapshot
- [ ] Update `.playwright-cli/` script untuk ambil screenshot single-post after
- [ ] Compare dengan baseline di 0.1 untuk before/after showcase
- [ ] Commit screenshot ke `output/playwright/single-post-after-*.png`
- _Requirements: Success Criteria (di design.md)_

### 4.8 Documentation Update
- [ ] Update `README.md` section "Fitur Utama" dengan single-post capabilities
- [ ] Tambah catatan di `COMPONENT-SYSTEM.md` tentang partial/component baru
- [ ] Archive dokumen MD root yang sudah tidak relevan
- _Requirements: housekeeping_

### 4.9 CHANGELOG
- [ ] Tambah entri `CHANGELOG.md` (bikin kalau belum ada) untuk versi theme
- _Requirements: housekeeping_

---

## Dependency Graph

```
Phase 0 ──► Phase 1 ──► Phase 2 ──► Phase 3 ──► Phase 4
  │            │            │            │            ▲
  │            │            │            └────────────┤
  │            │            └─────────────────────────┤
  │            └──────────────────────────────────────┤
  └───────────────────────────────────────────────────┘

Cross-module dependencies:
- M4 (PHPStan + CI) ──► Blocks Phase 1.7 (PHPUnit setup)
- M7 (rspku-schema) ──► Fulfills R12 (JSON-LD)
- M8 (Caching) ──► Supports 1.2 (related articles cache)
- M9 (i18n) ──► Fulfills NFR4
```

---

## Execution Order Recommendation

Urutan eksekusi yang disarankan (assumsi modular, komit terpisah):

1. **0.1** Baseline screenshot (5 menit)
2. **0.3** Inventarisasi helper (15 menit) *[sekarang]*
3. **1.1** TocGenerator helper + tests (2 jam)
4. **1.2** Related Articles Scorer (1.5 jam)
5. **1.4** Contextual CTA Mapper (1 jam)
6. **1.3** Single Post Context Extender (1 jam)
7. **1.5** Author credentials field (1 jam)
8. **0.2** Typography tokens audit (30 menit)
9. **1.7** PHPUnit setup (di-cover M4)
10. — **🛑 pause backend work, lanjut UI phase kapan diputuskan** —
11. Phase 2, 3, 4 berurutan

---

## Done Criteria Per Phase

- **Phase 0 done:** baseline ada, token CSS siap, helper inventaris jelas
- **Phase 1 done:** semua service/helper backend berfungsi dengan unit test, context siap di-consume Twig
- **Phase 2 done:** semua partial Twig render tanpa error di 3 test article, styling lulus visual review
- **Phase 3 done:** JS functionality bekerja di 4 browser, graceful degradation terverifikasi
- **Phase 4 done:** Lighthouse ≥90, axe clean, Playwright screenshot tersimpan, dokumentasi update

---

## Non-Goals

Task-task ini **tidak** dalam spec ini, jangan dikerjakan di sini:
- Redesign archive page
- Redesign CPT single page (dokter, poliklinik, dll)
- Implementasi komentar
- Implementasi newsletter subscription
- Audio narration, dark mode, print stylesheet

---

## Traceability Matrix (Requirement → Tasks)

| Requirement | Tasks |
|---|---|
| R1 Article Header | 2.1, 2.9 |
| R2 Author Card | 1.5, 2.3 |
| R3 Typography | 0.2, 2.2 |
| R4 TOC | 1.1, 2.4, 3.2, 3.3, 3.5 |
| R5 Progress Bar | 2.5, 3.1 |
| R6 Share Buttons | 2.6, 3.4 |
| R7 End CTA | 1.3, 1.4, 2.7 |
| R8 Related Articles | 1.2, 2.8 |
| R9 Sidebar | 2.9 |
| R10 Accessibility | 2.4, 2.5, 2.6, 4.1 |
| R11 Performance | 2.1 (LCP), 3.6, 4.2 |
| R12 SEO & Schema | (dihandle M7 rspku-schema) |
| R13 Mobile | 2.9, 4.4 |
| NFR1 Browsers | 4.3 |
| NFR2 Content Compat | 4.5 |
| NFR3 API Compat | 4.6 |
| NFR4 i18n | (dihandle M9) |
| NFR5 Testability | 1.7 |
