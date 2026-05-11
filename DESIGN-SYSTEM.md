# Design System - RS PKU Theme

**Version:** 2.0  
**Date:** May 11, 2026  
**Status:** ✅ Standardized

---

## 🎨 Design Tokens

### Spacing Scale

Use consistent spacing throughout the theme:

```scss
// Tailwind classes
space-y-1   // 4px   - very tight
space-y-2   // 8px   - tight
space-y-4   // 16px  - default ✅
space-y-6   // 24px  - comfortable
space-y-8   // 32px  - spacious
space-y-12  // 48px  - section spacing
space-y-16  // 64px  - major sections

// Padding/Margin
p-4   // 16px - compact
p-5   // 20px - small cards
p-6   // 24px - default cards ✅
p-8   // 32px - large cards
p-10  // 40px - panels
p-12  // 48px - hero sections

// Gaps
gap-2   // 8px  - tight
gap-3   // 12px - compact
gap-4   // 16px - default ✅
gap-6   // 24px - comfortable
gap-8   // 32px - spacious
```

**Usage Guidelines:**
- **Tight (4-8px):** Related elements, inline items
- **Default (16px):** Standard spacing between elements
- **Comfortable (24px):** Section internal spacing
- **Spacious (32-48px):** Between major sections
- **Major (64px+):** Page-level sections

---

### Typography Scale

Based on **Major Third (1.25)** ratio for harmonious hierarchy:

```scss
// Font Sizes
text-xs    // 12px - labels, meta, timestamps
text-sm    // 14px - small text, captions
text-base  // 16px - body text ✅
text-lg    // 18px - lead paragraphs
text-xl    // 20px - h5, small headings
text-2xl   // 25px - h4
text-3xl   // 31px - h3
text-4xl   // 39px - h2
text-5xl   // 49px - h1, hero titles
text-6xl   // 61px - extra large hero

// Line Heights (automatically applied)
leading-tight    // 1.2 - large headings
leading-snug     // 1.4 - subheadings
leading-normal   // 1.6 - body text ✅
leading-relaxed  // 1.8 - long-form content
```

**Usage Guidelines:**

| Element | Size | Line Height | Weight |
|---------|------|-------------|--------|
| Hero Title | text-5xl | tight | semibold |
| H1 | text-4xl | tight | semibold |
| H2 | text-3xl | tight | semibold |
| H3 | text-2xl | snug | semibold |
| H4 | text-xl | snug | semibold |
| H5 | text-lg | snug | semibold |
| Lead | text-lg | normal | normal |
| Body | text-base | normal | normal |
| Small | text-sm | normal | normal |
| Meta | text-xs | normal | medium |

**Font Weights:**
- `font-normal` (400) - body text
- `font-medium` (500) - emphasis, labels
- `font-semibold` (600) - headings ✅
- `font-bold` (700) - strong emphasis

---

### Border Radius

Standardized to 4 values:

```scss
rounded-sm   // 12px - buttons, chips, small elements
rounded      // 16px - cards, default ✅
rounded-lg   // 24px - panels, images, large cards
rounded-xl   // 32px - hero sections, feature panels
rounded-2xl  // 40px - extra large sections

// Special
rounded-full // 9999px - avatars, pills
```

**Usage Guidelines:**
- **Small (12px):** Buttons, chips, badges
- **Default (16px):** Cards, inputs, standard elements
- **Large (24px):** Image containers, panels
- **XL (32px):** Hero sections, feature areas
- **Full:** Avatars, circular elements

---

### Colors

#### Primary (Hospital Green)
```scss
hospital-50   // #eef9f2 - lightest background
hospital-100  // #d7f1e0 - light background
hospital-200  // #b2e2c6 - subtle accents
hospital-300  // #7dcca0 - disabled states
hospital-400  // #47b676 - hover states
hospital-500  // #179b56 - unused
hospital-600  // #0c8f45 - primary actions ✅
hospital-700  // #086d35 - primary text, icons ✅
hospital-800  // #07552c - dark accents
hospital-900  // #063f22 - darkest
```

#### Neutral (Slate)
```scss
slate-50   // #f8fafc - lightest background
slate-100  // #f1f5f9 - light background
slate-200  // #e2e8f0 - borders ✅
slate-300  // #cbd5e1 - disabled
slate-400  // #94a3b8 - placeholder (use sparingly)
slate-500  // #64748b - secondary text (use sparingly)
slate-600  // #475569 - body text ✅
slate-700  // #334155 - emphasis text
slate-800  // #1e293b - dark text
slate-900  // #0f172a - headings
slate-950  // #020617 - darkest headings ✅
```

**Usage Guidelines:**

| Purpose | Color | Contrast |
|---------|-------|----------|
| Headings | slate-950 | AAA |
| Body text | slate-600 | AA ✅ |
| Secondary text | slate-600 | AA |
| Meta text | slate-500 | AA (borderline) |
| Disabled | slate-400 | - |
| Borders | slate-200 | - |
| Backgrounds | slate-50/100 | - |
| Primary CTA | hospital-600 | AAA |
| Primary text | hospital-700 | AAA |
| Hover | hospital-400 | AA |

**Accessibility:**
- ✅ **Always use slate-600 or darker** for body text
- ⚠️ **Avoid slate-500** on white (borderline contrast)
- ❌ **Never use slate-400** for text (fails WCAG)

---

### Shadows

```scss
shadow-sm   // Subtle - cards on hover
shadow      // Default - elevated cards
shadow-md   // Medium - dropdowns
shadow-lg   // Large - modals
shadow-soft // Custom - hero sections
```

**Usage:**
- **None:** Flat cards, inline elements
- **SM:** Hover states
- **Default:** Elevated cards
- **MD:** Dropdowns, popovers
- **LG:** Modals, overlays

---

## 🧩 Component Patterns

### Buttons

```twig
{# Primary - Main CTA (1 per section) #}
{% include 'components/button.twig' with {
  text: 'Buat Janji',
  variant: 'primary',
  size: 'default'
} only %}

{# Secondary - Alternative action #}
{% include 'components/button.twig' with {
  text: 'Lihat Jadwal',
  variant: 'secondary'
} only %}

{# Small - Compact spaces #}
{% include 'components/button.twig' with {
  text: 'Baca',
  size: 'sm'
} only %}
```

**Hierarchy:**
1. **Primary** - 1 per section, main action
2. **Secondary** - Alternative, less important
3. **Ghost/Link** - Tertiary actions

---

### Cards

```twig
{# Standard card #}
<div class="rounded-lg border border-slate-200 bg-white p-6">
  <h3 class="text-xl font-semibold text-slate-950">Title</h3>
  <p class="mt-4 text-base text-slate-600">Content</p>
</div>

{# Large card #}
<div class="rounded-xl border border-slate-200 bg-white p-8">
  {# More spacious #}
</div>

{# Compact card #}
<div class="rounded-md border border-slate-200 bg-white p-5">
  {# Tighter spacing #}
</div>
```

---

### Spacing Patterns

```twig
{# Page structure #}
<section class="py-12 md:py-16">  {# Section padding #}
  <div class="container space-y-12">  {# Major sections #}
    
    <div class="space-y-6">  {# Section content #}
      <h2 class="text-3xl">Title</h2>
      <p class="text-lg">Lead</p>
    </div>
    
    <div class="grid gap-8">  {# Card grid #}
      {# Cards #}
    </div>
    
  </div>
</section>
```

---

## 📏 Layout Guidelines

### Container Widths
```scss
max-w-7xl   // 1280px - default container
max-w-6xl   // 1152px - narrow content
max-w-4xl   // 896px  - article content
max-w-3xl   // 768px  - forms
max-w-2xl   // 672px  - narrow text
```

### Grid Patterns
```twig
{# 3-column grid #}
<div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">

{# 4-column grid #}
<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">

{# Sidebar layout #}
<div class="grid gap-8 lg:grid-cols-[1fr_20rem]">
```

---

## ♿ Accessibility Standards

### Minimum Requirements

✅ **Color Contrast:**
- Body text: 4.5:1 (WCAG AA)
- Large text: 3:1 (WCAG AA)
- Use slate-600 or darker for text

✅ **Touch Targets:**
- Minimum: 44x44px
- Buttons: min-h-11 (44px)
- Icon buttons: min-h-11 min-w-11

✅ **Focus States:**
```scss
focus-visible:outline-2
focus-visible:outline-hospital-600
focus-visible:outline-offset-2
```

✅ **ARIA Labels:**
```twig
{# Icon-only buttons #}
<button aria-label="Cari dokter">
  <icon name="search" />
</button>

{# Form inputs #}
<label for="search" class="sr-only">Cari</label>
<input id="search" type="search" />
```

✅ **Heading Hierarchy:**
- Never skip levels (h1 → h2 → h3)
- One h1 per page
- Logical structure

---

## 🎯 Usage Examples

### Hero Section
```twig
<section class="py-16 md:py-24">
  <div class="container space-y-8">
    <h1 class="text-5xl font-semibold leading-tight text-slate-950">
      Hero Title
    </h1>
    <p class="text-lg leading-relaxed text-slate-600 max-w-2xl">
      Lead paragraph with comfortable reading width
    </p>
    {% include 'components/button.twig' with {
      text: 'Get Started',
      variant: 'primary'
    } only %}
  </div>
</section>
```

### Content Section
```twig
<section class="py-12">
  <div class="container space-y-12">
    <div class="space-y-4">
      <h2 class="text-3xl font-semibold text-slate-950">Section Title</h2>
      <p class="text-lg text-slate-600">Section description</p>
    </div>
    
    <div class="grid gap-8 md:grid-cols-3">
      {# Cards #}
    </div>
  </div>
</section>
```

### Card Grid
```twig
<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
  {% for item in items %}
    <article class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
      <h3 class="text-xl font-semibold text-slate-950">{{ item.title }}</h3>
      <p class="text-base text-slate-600">{{ item.excerpt }}</p>
    </article>
  {% endfor %}
</div>
```

---

## 🚫 Anti-Patterns (Avoid)

### ❌ Don't:
```twig
{# Arbitrary values #}
<div class="mt-[23px] rounded-[1.35rem]">

{# Inconsistent spacing #}
<div class="space-y-5">  {# Use space-y-4 or space-y-6 #}

{# Too many font sizes #}
<p class="text-[17px]">  {# Use text-lg (18px) #}

{# Poor contrast #}
<p class="text-slate-400">  {# Use slate-600 minimum #}

{# Skipped heading levels #}
<h1>Title</h1>
<h3>Subtitle</h3>  {# Missing h2 #}

{# Small touch targets #}
<button class="h-8 w-8">  {# Too small, use min-h-11 #}
```

### ✅ Do:
```twig
{# Standard values #}
<div class="mt-6 rounded-lg">

{# Consistent spacing #}
<div class="space-y-6">

{# Standard font sizes #}
<p class="text-lg">

{# Good contrast #}
<p class="text-slate-600">

{# Proper heading hierarchy #}
<h1>Title</h1>
<h2>Subtitle</h2>

{# Accessible touch targets #}
<button class="min-h-11 min-w-11">
```

---

## 📚 Resources

- **Tailwind Config:** `tailwind.config.js`
- **Components:** `resources/views/components/`
- **Audit Report:** `UI-UX-COMPREHENSIVE-AUDIT.md`

---

## ✅ Checklist

Before creating new components:
- [ ] Use standardized spacing (4, 6, 8, 12)
- [ ] Use standardized typography (xs, sm, base, lg, xl, 2xl, 3xl, 4xl, 5xl)
- [ ] Use standardized border radius (sm, md, lg, xl)
- [ ] Ensure color contrast meets WCAG AA
- [ ] Minimum 44x44px touch targets
- [ ] Add ARIA labels for icon-only buttons
- [ ] Proper heading hierarchy
- [ ] Focus states visible

---

**Version History:**
- v2.0 (May 11, 2026) - Standardized design system
- v1.0 (Initial) - Ad-hoc values
