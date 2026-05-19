# RS PKU Settings — Admin CSS Documentation

## Overview

File: `assets/admin.css`  
Purpose: Styling halaman admin settings plugin RS PKU Muhammadiyah Yogyakarta.  
Design: Custom admin UI yang cohesive dengan frontend theme (`rspku-theme`).

---

## Design Tokens

Semua custom properties didefinisikan di `:root`:

| Token | Value | Penggunaan |
|-------|-------|-----------|
| `--s-ink` | `#0f172a` | Teks utama |
| `--s-sub` | `#475569` | Teks sekunder (label, meta) |
| `--s-muted` | `#94a3b8` | Teks tersier (help text, placeholder) |
| `--s-line` | `#e2e8f0` | Border, divider |
| `--s-bg` | `#f8fafc` | Background subtle (section bg, picker bg) |
| `--s-card` | `#ffffff` | Background card |
| `--s-brand` | `#0c8f45` | Primary accent (tombol, active tab, toggle) |
| `--s-brand-deep` | `#065f2e` | Brand dark (header gradient, hover) |
| `--s-brand-hover` | `#0a7a3d` | Button hover state |
| `--s-brand-light` | `#ecfdf5` | Light green bg (picker hover, info card) |
| `--s-brand-glow` | `rgba(12,143,69,0.08)` | Focus ring, subtle glow |
| `--s-radius` | `6px` | Default border radius |
| `--s-radius-lg` | `10px` | Large radius (cards, header) |
| `--s-font` | system stack + Inter | Font family |

---

## Component Architecture

### Layout (`.rspku-settings-wrap`)
- Full-width: `width: calc(100% - 20px)` — mengisi content area WP admin
- Centered: `margin: 20px 10px 40px`
- No max-width constraint di desktop (maks alami dari WP admin shell)

### Header (`.rspku-settings-header`)
- Gradient background: `#065f2e` → brand deep
- Decorative circle ornament (::after pseudo)
- Diagonal gradient overlay (::before pseudo)
- White text, compact padding

### Tabs (`.rspku-settings-tabs`)
- Container: white card dengan border + shadow
- Items: pill/segment style
- Active: solid green background + white text
- Hover: subtle gray background
- Flex-wrap untuk banyak tab

### Sections (`.rspku-settings-section`)
- White card, rounded, subtle shadow
- Header: left green accent border (3px) + gradient bg (`brand-light` → `bg`)
- Body: consistent padding
- `:focus-within` glow effect

### Fields (`.rspku-settings-field`)
- 2-column CSS Grid: `190px` label | `1fr` content
- Bottom border separator antar field
- Label: 12px, semibold, muted color
- Help text (`.description`): spans content column

### Inputs
- Consistent sizing: padding 8-11px, border radius 6px
- Hover: darker border
- Focus: green border + glow ring
- Select: custom chevron SVG, hidden native appearance
- Textarea: resizable vertical, min-height 72px

### Checkbox & Radio
- Custom-styled (appearance: none)
- 15×15px, rounded corners (3px checkbox, circle radio)
- Checked: green fill + white checkmark/dot
- Hover: green border
- Focus-visible: glow ring

### Toggle (`.rspku-toggle` / `.rspku-toggle-slider`)
- Pill switch 42×24px
- Gray → green on checked
- Knob 18px with shadow
- CSS cubic-bezier animation
- Focus-visible ring support

### Repeater (`.rspku-repeater`)
- Flex column, gap 6px
- Rows: grid layout, rounded, surface bg
- Variants: `--links` (3 col), `--review` (responsive 5 col)
- Compact inputs inside rows

### Checkbox Picker (`.rspku-checkbox-picker-grid`)
- 2-column grid (3 col di 1280px+, 1 col di mobile)
- Scrollable container (max-height 300px)
- Items: flex align-center, rounded hover state
- Checked label: green bold

### Post Picker (PHP inline styles)
- Override via `[style*="grid-template-columns"]` selector
- Forces 2-col grid (3 di desktop besar)
- Label rows mendapat hover state via attribute selectors

### Image Upload (`.rspku-image-upload`)
- Preview with rounded border
- Remove link (red, no border)
- Hidden states via `.hidden` class

### Tools Grid (`.rspku-tools-grid`)
- Auto-fit grid, min 280px per card
- Cards with hover elevation

### Actions Bar (`.rspku-settings-actions`)
- Sticky bottom
- Primary button: green, rounded, with shadow
- Secondary: ghost style

---

## Responsive Breakpoints

| Breakpoint | Target | Key Changes |
|-----------|--------|-------------|
| ≥1280px | Large desktop | 3-col picker, wider field labels (210px), more padding |
| 783–1024px | Tablet | Narrower margins, 160px labels, compact padding |
| ≤782px | Mobile (WP admin) | Single column, full-width inputs, stacked layout |
| ≤480px | Small mobile | Implied by mobile rules (review repeater stacks) |

---

## Tailwind Utility Fallbacks

PHP template menggunakan `rs-*` prefix Tailwind classes. Karena build Tailwind mungkin tidak tersedia di semua environment (Laragon local), CSS ini menyertakan fallback rules menggunakan attribute selectors:

```css
.rspku-settings-wrap [class*="rs-grid"] { display: grid; }
.rspku-settings-wrap [class*="rs-flex"] { display: flex; }
.rspku-settings-wrap [class*="rs-text-xs"] { font-size: 12px; }
/* ... dst */
```

**Coverage:** spacing, colors, typography, layout, borders, backgrounds, sizing, visibility.

---

## CSS Classes Reference

| Class | Element | Description |
|-------|---------|-------------|
| `.rspku-settings-wrap` | `<div>` | Container utama |
| `.rspku-settings-header` | `<div>` | Header dengan judul + deskripsi |
| `.rspku-settings-tabs` | `<nav>` | Tab navigation container |
| `.nav-tab` | `<a>` | Individual tab |
| `.nav-tab-active` | `<a>` | Active tab state |
| `.rspku-settings-section` | `<div>` | Section card wrapper |
| `.rspku-settings-section-header` | `<div>` | Section title area |
| `.rspku-settings-section-body` | `<div>` | Section content area |
| `.rspku-settings-field` | `<div>` | Field row (grid 2-col) |
| `.rspku-toggle` | `<label>` | Toggle switch wrapper |
| `.rspku-toggle-slider` | `<span>` | Toggle track |
| `.rspku-repeater` | `<div>` | Repeater container |
| `.rspku-repeater-row` | `<div>` | Single repeater item |
| `.rspku-repeater-row--links` | `<div>` | Link repeater variant |
| `.rspku-repeater-row--review` | `<div>` | Review repeater variant |
| `.rspku-checkbox-picker` | `<div>` | Checkbox picker wrapper |
| `.rspku-checkbox-picker-grid` | `<div>` | Picker grid container |
| `.rspku-checkbox-picker-item` | `<label>` | Single picker item |
| `.rspku-checkbox-picker-label` | `<span>` | Picker item text |
| `.rspku-image-upload` | `<div>` | Image upload wrapper |
| `.rspku-image-preview` | `<div>` | Image preview container |
| `.rspku-image-remove` | `<button>` | Remove image link |
| `.rspku-image-select` | `<button>` | Select image button |
| `.rspku-info-card` | `<div>` | Info callout card |
| `.rspku-tools-grid` | `<div>` | Export/import grid |
| `.rspku-tools-card` | `<div>` | Single tool card |
| `.rspku-settings-actions` | `<div>` | Sticky save bar |

---

## Constraints & Rules

1. **Jangan ubah nama class** — class names dipakai di PHP dan tidak boleh diubah.
2. **Jangan pakai Tailwind CDN** — environment local (Laragon) tidak support external CDN.
3. **`!important` hanya untuk override WP admin defaults** — button-primary, button-secondary, dan inline style overrides saja.
4. **PHP tidak boleh diubah** — hanya CSS yang di-edit.
5. **Harus responsive** — WP admin diakses di mobile juga.

---

## File Dependencies

- **Loaded by:** `class-rspku-settings-admin.php` → `enqueueAssets()` 
- **Hook:** `admin_enqueue_scripts` (only on plugin page)
- **Handle:** `rspku-settings-admin`
- **Dependencies:** none (standalone CSS)
- **Companion JS:** `assets/admin.js` (repeater, image upload, toggle logic)

---

## Maintenance Notes

- Jika menambah field type baru di PHP, tambahkan styling di section yang sesuai.
- Jika Tailwind build aktif, rules `rs-*` fallback akan di-override oleh compiled Tailwind (specificity sama, Tailwind inline class menang karena compiled setelah admin.css).
- Design tokens (`--s-*`) bisa di-sync dengan theme jika theme tokens berubah.
- Test di WP admin dengan sidebar collapsed dan expanded — layout harus accommodate keduanya.
