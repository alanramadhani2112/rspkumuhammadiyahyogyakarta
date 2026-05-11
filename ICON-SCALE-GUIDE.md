# Icon Scale Guide

**RS PKU Theme - Icon Standardization**  
**Date:** May 11, 2026

---

## 🎯 Icon Size Scale

### Standard Sizes

```twig
{# Extra Small - 16px #}
{{ icon('name', { class: 'h-4 w-4' }) }}
Use: Inline with text, small badges

{# Small - 20px #}
{{ icon('name', { class: 'h-5 w-5' }) }}
Use: Buttons, chips, navigation (DEFAULT)

{# Medium - 24px #}
{{ icon('name', { class: 'h-6 w-6' }) }}
Use: Cards, headers, section icons

{# Large - 32px #}
{{ icon('name', { class: 'h-8 w-8' }) }}
Use: Hero sections, features, large cards

{# Extra Large - 48px #}
{{ icon('name', { class: 'h-12 w-12' }) }}
Use: Empty states, placeholders, large features
```

---

## 📏 Usage Guidelines

### By Context

**Buttons:**
```twig
{# Small buttons #}
{{ icon('arrow-right', { class: 'h-4 w-4' }) }}

{# Default buttons #}
{{ icon('arrow-right', { class: 'h-5 w-5' }) }}

{# Large buttons #}
{{ icon('arrow-right', { class: 'h-6 w-6' }) }}
```

**Navigation:**
```twig
{# Menu items #}
{{ icon('chevron-down', { class: 'h-4 w-4' }) }}

{# Icon buttons #}
{{ icon('search', { class: 'h-5 w-5' }) }}
```

**Cards:**
```twig
{# Card headers #}
{{ icon('hospital', { class: 'h-6 w-6' }) }}

{# Info cards #}
{{ icon('tag', { class: 'h-5 w-5' }) }}
```

**Hero Sections:**
```twig
{# Feature icons #}
{{ icon('check-circle', { class: 'h-8 w-8' }) }}

{# Large decorative #}
{{ icon('hospital', { class: 'h-12 w-12' }) }}
```

---

## 🎨 Icon Containers

### With Background

**Small Container (40px):**
```twig
<div class="grid h-10 w-10 place-items-center rounded-lg bg-hospital-100">
  {{ icon('name', { class: 'h-5 w-5 text-hospital-700' }) }}
</div>
```

**Medium Container (56px):**
```twig
<div class="grid h-14 w-14 place-items-center rounded-lg bg-hospital-100">
  {{ icon('name', { class: 'h-6 w-6 text-hospital-700' }) }}
</div>
```

**Large Container (64px):**
```twig
<div class="grid h-16 w-16 place-items-center rounded-xl bg-hospital-100">
  {{ icon('name', { class: 'h-8 w-8 text-hospital-700' }) }}
</div>
```

---

## 🚫 Don't Use

### ❌ Arbitrary Sizes
```twig
{# DON'T #}
{{ icon('name', { class: 'h-7 w-7' }) }}  {# 28px - not in scale #}
{{ icon('name', { class: 'h-9 w-9' }) }}  {# 36px - not in scale #}

{# DO #}
{{ icon('name', { class: 'h-6 w-6' }) }}  {# 24px - standard #}
{{ icon('name', { class: 'h-8 w-8' }) }}  {# 32px - standard #}
```

---

## 📊 Size Reference Table

| Size | Pixels | Tailwind | Use Case |
|------|--------|----------|----------|
| XS | 16px | h-4 w-4 | Inline text, small badges |
| SM | 20px | h-5 w-5 | Buttons, chips, nav (DEFAULT) |
| MD | 24px | h-6 w-6 | Cards, headers |
| LG | 32px | h-8 w-8 | Hero, features |
| XL | 48px | h-12 w-12 | Empty states |

---

## 🎯 Current Usage

### Already Standardized
- ✅ Button component: h-4 w-4 (16px)
- ✅ Navigation: h-4 w-4 (16px)
- ✅ Info cards: h-5 w-5 (20px)
- ✅ Section headers: h-5 w-5 (20px)

### Needs Review
- ⚠️ Some hero sections use h-7 w-7 (28px)
- ⚠️ Some cards use inconsistent sizes

---

## ✅ Implementation Checklist

When adding new icons:
- [ ] Choose appropriate size from scale (4, 5, 6, 8, 12)
- [ ] Match context (button, card, hero, etc.)
- [ ] Use consistent size within same component type
- [ ] Add proper color classes
- [ ] Ensure accessibility (aria-hidden="true" for decorative)

---

**Quick Reference:**
- Buttons: h-4 or h-5
- Cards: h-5 or h-6
- Hero: h-8 or h-12
- Navigation: h-4 or h-5
