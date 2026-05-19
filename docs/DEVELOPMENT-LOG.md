# RS PKU Muhammadiyah Yogyakarta — Development Log

## Project Overview

**Website:** RS PKU Muhammadiyah Yogyakarta  
**Stack:** WordPress 6.5+ / PHP 8.3 / Timber+Twig / TailwindCSS / Alpine.js / Vite  
**Developer:** LabMu — Muhammadiyah Software Labs  
**Repository:** github.com/alanramadhani2112/rspkumuhammadiyahyogyakarta

---

## Scope of Work (Session)

Berikut adalah pekerjaan yang diselesaikan dalam session ini:

### 1. Admin CSS Redesign (`rspku-settings` plugin)

**File:** `wp-content/plugins/rspku-settings/assets/admin.css`

**Deliverables:**
- Full rewrite admin.css agar cohesive dengan design system frontend theme
- Design tokens matching theme (`--s-ink`, `--s-brand`, `--s-line`, dll)
- Header: gradient hijau dengan decorative elements
- Tabs: pill/segment style dalam white card
- Sections: white card dengan left accent border hijau
- Fields: 2-column grid layout (label | content)
- Custom checkbox & radio styling (appearance: none + custom states)
- Toggle switch isolation fix — hidden controller checkbox tidak lagi terlihat
- Checkbox picker grid: forced 2-col (desktop) / 3-col (large) / 1-col (mobile)
- Tailwind `rs-*` utility fallback CSS untuk non-build environments
- Responsive: 4 breakpoints (1280px+, desktop, tablet 783-1024px, mobile <782px)
- Post picker inline style override via attribute selectors

**Test Suite:** `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`
- 65 assertions covering: syntax, selectors, tokens, breakpoints, toggle isolation, constraints, picker grid, utility fallbacks, file size
- Run: `npm test` (dari folder plugin)

**Documentation:** `wp-content/plugins/rspku-settings/ADMIN-CSS.md`

---

### 2. Custom Login Page

**Files:**
- `wp-content/themes/rspku-theme/app/Setup/LoginPage.php`
- `wp-content/themes/rspku-theme/assets/login.css`

**Design:**
- Split card layout: gambar gedung RS di kiri + form di kanan (desktop)
- Stacked card pada mobile (image atas, form bawah)
- Card centered di halaman dengan background `#eef2ee`
- Panel kiri: foto Gedung PKU (historis) + green gradient overlay + tagline text
- Panel kanan: logo RS PKU + heading + form inputs + button
- Custom styled inputs, checkbox, submit button (brand green)
- Responsive: 860px breakpoint (split → stacked), 480px (compact)

**Architecture:**
- `LoginPage::register()` hooks into `login_enqueue_scripts`, `login_message`, `login_headerurl`, `login_headertext`, `login_footer`
- Panel image injected via `login_message` filter (renders inside `#login` div)
- CSS uses `position: absolute` for left panel + `padding-left` on `#login` for space
- WP default `h1.wp-login-logo` repurposed to show logo image

---

### 3. Single Rawat Inap Page Redesign

**File:** `wp-content/themes/rspku-theme/resources/views/pages/single-rawat-inap.twig`

**Changes:**
- Removed heavy `.rspku-side-panel` card wrappers from Fasilitas & Sudah Termasuk sections
- Changed from vertical list (check-list.twig) to **3-column grid** layout
- Items displayed inline with small check icons, compact padding
- Sections separated by simple `border-t` instead of separate cards
- Result: significantly shorter page, more scannable, better UX

---

### 4. User Guide Documentation

**File:** `docs/panduan-penggunaan-theme.html`

**Contents:**
- 17 chapters covering all admin operations
- Table of contents with anchors
- Step-by-step numbered instructions
- Reference tables (image sizes, URLs, features, settings tabs)
- Info boxes and warning boxes
- Print-friendly CSS (`@media print`)
- Screenshot placeholders linked to `docs/images/`

**Screenshot Capture Script:** `docs/capture-screenshots.mjs`
- Playwright-based automated screenshot capture
- 18 screenshots: admin pages, settings tabs, content lists, frontend pages
- Config: edit `LOGIN_URL`, `ADMIN_USER`, `ADMIN_PASS`
- Run: `node docs/capture-screenshots.mjs`

---

### 5. Git & CI

**Branch:** `feature/admin-css-redesign`  
**Commit:** `fad3f3c` — feat(rspku-settings): redesign admin CSS with theme design system

**.gitignore updated:** Added `!/wp-content/plugins/rspku-settings/` to whitelist + exclude `node_modules/`

**Note:** Push to GitHub pending authentication fix (WPS Hide Login + token auth required).

---

## File Inventory (Changed/Created)

| File | Action | Description |
|------|--------|-------------|
| `.gitignore` | Modified | Whitelisted rspku-settings plugin |
| `wp-content/plugins/rspku-settings/assets/admin.css` | Rewritten | Full admin UI redesign |
| `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs` | Created | CSS test suite (65 tests) |
| `wp-content/plugins/rspku-settings/package.json` | Modified | Added `test` script |
| `wp-content/plugins/rspku-settings/ADMIN-CSS.md` | Created | Admin CSS documentation |
| `wp-content/themes/rspku-theme/app/Setup/LoginPage.php` | Created | Custom login page PHP class |
| `wp-content/themes/rspku-theme/assets/login.css` | Created | Custom login page styles |
| `wp-content/themes/rspku-theme/app/Theme.php` | Modified | Registered LoginPage class |
| `wp-content/themes/rspku-theme/resources/views/pages/single-rawat-inap.twig` | Modified | Redesigned fasilitas/included sections |
| `docs/panduan-penggunaan-theme.html` | Created | User guide (17 chapters) |
| `docs/capture-screenshots.mjs` | Created | Playwright screenshot script |
| `docs/images/` | Created | Screenshot output folder |

---

## Technical Decisions

### Why CSS-only admin panel (no Tailwind build)?
Tailwind build (`npm run build:css`) may not be available in all dev environments (Laragon local). The admin.css is standalone with `rs-*` utility fallbacks so it works without compilation.

### Why `position: absolute` for login panel?
WordPress login page DOM (`#login > h1, form, #nav, #backtoblog`) doesn't support CSS Grid spanning because elements from `login_message` filter and WP internals create unpredictable implicit grid rows. Absolute positioning with `padding-left` on `#login` is the most reliable cross-browser approach.

### Why `login_message` filter instead of `login_header` action?
`login_header` action renders content **outside** the `#login` div (directly on `body`). `login_message` filter outputs content **inside** `#login`, between h1 and the form — exactly where we need the image panel.

### Why no external images for login?
Login page uses a photo already in the Media Library (`640px-Gedung_PKU...jpg`). The overlay gradient makes low resolution acceptable. For production, recommend uploading a higher-res (1200px+) version.

---

## How to Deploy

1. Ensure all files are committed to the `feature/admin-css-redesign` branch
2. Fix GitHub authentication (Personal Access Token or SSH key)
3. Push: `git push -u origin feature/admin-css-redesign`
4. Create PR to `develop` → review → merge
5. On production server: `git pull` on the appropriate branch

### Post-deploy checks:
- [ ] Visit `/wp-admin/admin.php?page=rspku-settings` — verify admin panel styling
- [ ] Visit `/rspku-log/` (or custom login URL) — verify login page
- [ ] Visit `/rawat-inap/vip-shofa-1/` — verify fasilitas section compact layout
- [ ] Test mobile views on all three pages
- [ ] Clear any caching plugin (LiteSpeed Cache)

---

## How to Run Tests

```bash
cd wp-content/plugins/rspku-settings
npm test
```

Expected output: `65 passed, 0 failed`

---

## How to Generate Documentation Screenshots

```bash
# Edit credentials in the script first:
# LOGIN_URL, ADMIN_USER, ADMIN_PASS
node docs/capture-screenshots.mjs
```

Output: 18 PNG files in `docs/images/`

---

## Maintenance Notes

- **Admin CSS tokens** (`--s-*`) should stay in sync with theme's `app.css` `:root` variables if theme tokens change
- **Login page image** path is hardcoded in both `login.css` and `LoginPage.php` — update both if image changes
- **Login URL** (`/rspku-log/`) is set via WPS Hide Login plugin — if changed, update `docs/capture-screenshots.mjs`
- **Toggle fix** (`:not([class*="rs-sr-only"])`) is critical — any new checkbox type added in PHP should not use `rs-sr-only` or `rs-peer` classes unless it's a toggle controller
- **Rawat inap template** now uses inline icon rendering instead of `check-list.twig` component for the fasilitas section

---

*Last updated: May 19, 2026*
