# Quick Reference Guide

**RS PKU Theme - Design System**  
**Last Updated:** May 11, 2026

---

## 🎨 Typography

### Font Sizes
```twig
text-xs    {# 12px - labels, meta, timestamps #}
text-sm    {# 14px - small text, captions #}
text-base  {# 16px - body text (DEFAULT) #}
text-lg    {# 18px - lead paragraphs #}
text-xl    {# 20px - h5, small headings #}
text-2xl   {# 25px - h4 #}
text-3xl   {# 31px - h3 #}
text-4xl   {# 39px - h2 #}
text-5xl   {# 49px - h1, hero titles #}
text-6xl   {# 61px - extra large hero #}
```

### Line Heights
```twig
leading-tight    {# 1.2 - large headings #}
leading-snug     {# 1.4 - subheadings #}
leading-normal   {# 1.6 - body text (DEFAULT) #}
leading-relaxed  {# 1.8 - long-form content #}
```

### Font Weights
```twig
font-normal    {# 400 - body text #}
font-medium    {# 500 - emphasis, labels #}
font-semibold  {# 600 - headings (DEFAULT) #}
font-bold      {# 700 - strong emphasis #}
```

---

## 📐 Spacing

### Vertical Spacing (space-y-*)
```twig
space-y-2   {# 8px  - tight #}
space-y-4   {# 16px - default (DEFAULT) #}
space-y-6   {# 24px - comfortable #}
space-y-8   {# 32px - spacious #}
space-y-12  {# 48px - section spacing #}
space-y-16  {# 64px - major sections #}
```

### Margins & Padding
```twig
p-4   {# 16px - compact #}
p-5   {# 20px - small cards #}
p-6   {# 24px - default cards (DEFAULT) #}
p-8   {# 32px - large cards #}
p-10  {# 40px - panels #}
p-12  {# 48px - hero sections #}
```

### Gaps
```twig
gap-2   {# 8px  - tight #}
gap-3   {# 12px - compact #}
gap-4   {# 16px - default (DEFAULT) #}
gap-6   {# 24px - comfortable #}
gap-8   {# 32px - spacious #}
```

---

## 🔲 Border Radius

```twig
rounded-sm   {# 12px - buttons, chips, small elements #}
rounded      {# 16px - cards, default (DEFAULT) #}
rounded-lg   {# 24px - panels, images, large cards #}
rounded-xl   {# 32px - hero sections, feature panels #}
rounded-full {# 9999px - avatars, pills #}
```

---

## 🎨 Colors

### Primary (Hospital Green)
```twig
hospital-600  {# #0c8f45 - primary actions, buttons #}
hospital-700  {# #086d35 - primary text, icons #}
hospital-100  {# #d7f1e0 - light backgrounds #}
```

### Neutral (Slate)
```twig
slate-950  {# #020617 - headings #}
slate-900  {# #0f172a - dark headings #}
slate-700  {# #334155 - emphasis text #}
slate-600  {# #475569 - body text (DEFAULT) #}
slate-500  {# #64748b - secondary text (use sparingly) #}
slate-400  {# #94a3b8 - disabled (never for text) #}
slate-200  {# #e2e8f0 - borders #}
slate-100  {# #f1f5f9 - light background #}
slate-50   {# #f8fafc - lightest background #}
```

---

## 🧩 Components

### Button
```twig
{% include 'components/button.twig' with {
  text: 'Button Text',
  url: '/path/',
  variant: 'primary',  {# primary, secondary, white #}
  size: 'default',     {# sm, default #}
  icon: 'arrow-right', {# optional #}
  full_width: false    {# optional #}
} only %}
```

### Card
```twig
<article class="rounded-lg border border-slate-200 bg-white p-6">
  <h3 class="text-xl font-semibold text-slate-950">Title</h3>
  <p class="mt-4 text-sm leading-relaxed text-slate-600">Content</p>
</article>
```

### Info Card
```twig
{% include 'components/info-card.twig' with {
  icon: 'tag',
  label: 'Label',
  value: 'Value'
} only %}
```

### Section Header
```twig
{% include 'components/section-header.twig' with {
  icon: 'check-circle',
  title: 'Section Title',
  size: 'default'  {# default, large #}
} only %}
```

---

## 📏 Layout

### Container
```twig
<div class="rspku-container">
  {# Max width 1280px, centered, responsive padding #}
</div>
```

### Section
```twig
<section class="rspku-section">
  {# Vertical padding: py-12 md:py-16 #}
</section>
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

## ♿ Accessibility

### Touch Targets
```twig
{# Minimum 44x44px #}
<button class="min-h-11 min-w-11">Icon</button>
```

### ARIA Labels
```twig
{# Icon-only buttons #}
<button aria-label="Search">
  <icon name="search" />
</button>

{# Form inputs #}
<label for="search" class="sr-only">Search</label>
<input id="search" type="search" />
```

### Focus States
```twig
focus-visible:outline-2
focus-visible:outline-hospital-600
focus-visible:outline-offset-2
```

---

## 🚫 Don't Use

### ❌ Arbitrary Values
```twig
{# DON'T #}
<div class="mt-[23px] rounded-[1.35rem] text-[17px]">

{# DO #}
<div class="mt-6 rounded-lg text-lg">
```

### ❌ Non-Standard Spacing
```twig
{# DON'T #}
<div class="space-y-5 space-y-7 space-y-9">

{# DO #}
<div class="space-y-4 space-y-6 space-y-8">
```

### ❌ Poor Contrast
```twig
{# DON'T #}
<p class="text-slate-400">Body text</p>

{# DO #}
<p class="text-slate-600">Body text</p>
```

### ❌ Small Touch Targets
```twig
{# DON'T #}
<button class="h-8 w-8">Icon</button>

{# DO #}
<button class="min-h-11 min-w-11">Icon</button>
```

---

## 📝 Common Patterns

### Hero Section
```twig
<section class="py-16 md:py-24">
  <div class="rspku-container space-y-8">
    <h1 class="text-5xl font-semibold leading-tight text-slate-950">
      Hero Title
    </h1>
    <p class="text-lg leading-relaxed text-slate-600 max-w-2xl">
      Lead paragraph
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
<section class="rspku-section">
  <div class="rspku-container space-y-12">
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
      <p class="text-sm leading-relaxed text-slate-600">{{ item.excerpt }}</p>
    </article>
  {% endfor %}
</div>
```

---

## 🔧 Development

### Build Command
```bash
cd wp-content/themes/rspku-theme
npm run build
```

### Watch Mode
```bash
npm run dev
```

### File Structure
```
wp-content/themes/rspku-theme/
├── resources/
│   ├── views/
│   │   ├── components/     # Reusable components
│   │   ├── layouts/        # Base layouts
│   │   ├── pages/          # Page templates
│   │   └── partials/       # Partial templates
│   ├── js/                 # JavaScript
│   └── css/                # CSS
├── app/                    # PHP classes
├── tailwind.config.js      # Design tokens
└── functions.php           # Theme setup
```

---

## 📚 Documentation

- **Design System:** `DESIGN-SYSTEM.md`
- **Week 1 Summary:** `DESIGN-SYSTEM-IMPLEMENTATION-SUMMARY.md`
- **Week 2 Summary:** `WEEK-2-IMPLEMENTATION-SUMMARY.md`
- **Overall Progress:** `OVERALL-PROGRESS-SUMMARY.md`
- **Testing Guide:** `TESTING-GUIDE.md`
- **Audit Report:** `UI-UX-COMPREHENSIVE-AUDIT.md`

---

## 🎯 Checklist for New Components

Before creating a new component:
- [ ] Use standardized spacing (4, 6, 8, 12)
- [ ] Use standardized typography (xs, sm, base, lg, xl, 2xl, 3xl, 4xl, 5xl)
- [ ] Use standardized border radius (sm, md, lg, xl)
- [ ] Ensure color contrast meets WCAG AA
- [ ] Minimum 44x44px touch targets
- [ ] Add ARIA labels for icon-only buttons
- [ ] Proper heading hierarchy
- [ ] Focus states visible

---

**Quick Tip:** When in doubt, check `DESIGN-SYSTEM.md` for detailed guidelines!
