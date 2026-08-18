# Task 8.3 Public Smoke Pages

Date: 2026-08-18

Scope: QA/evidence only. No public redesign changes made. No production files edited.

## Curl Smoke

| URL | HTTP status | Final URL | Fatal/error text | Main-content signal | Title | Size |
|---|---:|---|---|---|---|---:|
| `http://rspkudev.test/` | 200 | `http://rspkudev.test/` | no | yes | `Beranda - RS PKU Muhammadiyah Yogyakarta` | 164837 |
| `http://rspkudev.test/kontak/` | 200 | `http://rspkudev.test/kontak/` | no | yes | `Kontak - RS PKU Muhammadiyah Yogyakarta` | 90160 |
| `http://rspkudev.test/dokter/` | 200 | `http://rspkudev.test/dokter/` | no | yes | `Dokter Arsip - RS PKU Muhammadiyah Yogyakarta` | 118396 |
| `http://rspkudev.test/sejarah-kami/` | 200 | `http://rspkudev.test/sejarah-kami/` | no | yes | `Sejarah Kami - RS PKU Muhammadiyah Yogyakarta` | 91331 |

Fatal/error text checked with case-insensitive pattern:

```text
Fatal error|Parse error|Warning:|Notice:|There has been a critical error
```

Main-content signal checked with body pattern:

```text
<main\b|id=["']main|class=["'][^"']*main|<article\b|entry-content|wp-site-blocks
```

## Browser Smoke

Playwright loaded all four public pages successfully:

| URL | Browser final URL | Title | Main-content signal | Fatal/error text | Visible body signal |
|---|---|---|---|---|---|
| `http://rspkudev.test/` | `http://rspkudev.test/` | `Beranda - RS PKU Muhammadiyah Yogyakarta` | yes | no | `Melayani dengan sepenuh hati sejak 1923` |
| `http://rspkudev.test/kontak/` | `http://rspkudev.test/kontak/` | `Kontak - RS PKU Muhammadiyah Yogyakarta` | yes | no | `Kami Siap Membantu Anda` |
| `http://rspkudev.test/dokter/` | `http://rspkudev.test/dokter/` | `Dokter Arsip - RS PKU Muhammadiyah Yogyakarta` | yes | no | `Cari dokter berdasarkan jadwal praktik` |
| `http://rspkudev.test/sejarah-kami/` | `http://rspkudev.test/sejarah-kami/` | `Sejarah Kami - RS PKU Muhammadiyah Yogyakarta` | yes | no | `Sejarah RS PKU Muhammadiyah Yogyakarta` |

## Verdict

Public smoke passed for `/`, `/kontak/`, `/dokter/`, and `/sejarah-kami/`. All returned HTTP 200, no fatal/error text, and main content was present via curl plus browser checks.

No public redesign changes were made.

## Verification

Run from `wp-content/plugins/rspku-settings`:

```bash
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php
```

Result: passed. `build:css` completed, `node tests/admin-css.test.mjs` passed all reported CSS checks, and `php -l includes/class-rspku-settings-admin.php` returned without failure.
