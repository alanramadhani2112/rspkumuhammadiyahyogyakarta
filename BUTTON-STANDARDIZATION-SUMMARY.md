# Button Standardization Summary

**Date:** May 10, 2026  
**Status:** ✅ Component Created, Ready for Migration

## Problem

Button di theme saat ini **BELUM standardized**. Setiap button menggunakan hardcoded markup dengan CSS classes:

```twig
{# Contoh button yang tersebar di 15+ templates #}
<a href="{{ site.url }}/kontak/" class="rspku-button rspku-button-primary w-full gap-2">
  {{ icon('phone', { class: 'h-4 w-4' }) }}
  <span>Hubungi admisi</span>
</a>

<button type="submit" class="rspku-button rspku-button-primary min-w-[10rem]">
  Cari Artikel
</button>

<a href="{{ url }}" class="rspku-button rspku-button-secondary rspku-button-sm">
  Lihat detail
</a>
```

**Issues:**
- ❌ Code duplication di 15+ templates
- ❌ Inconsistent markup patterns
- ❌ Hard to maintain
- ❌ Easy to make mistakes
- ❌ No centralized control

## Solution

**Button Component** yang reusable dan standardized:

```twig
{# Clean, consistent, easy to use #}
{% include 'components/button.twig' with {
  text: 'Hubungi admisi',
  url: site.url ~ '/kontak/',
  variant: 'primary',
  icon: 'phone',
  full_width: true
} only %}
```

## Component Features

### Variants
- ✅ `primary` - Green background (main CTA)
- ✅ `secondary` - White background with border
- ✅ `ghost` - Transparent background
- ✅ `white` - White background (for dark backgrounds)

### Sizes
- ✅ `sm` - Small (~40px height)
- ✅ `default` - Standard (~48px height)
- ✅ `lg` - Large (~56px height)

### Features
- ✅ Icon support (left or right)
- ✅ Full width option
- ✅ External link handling (`target="_blank"`)
- ✅ Disabled state
- ✅ Form button support (`type="submit"`)
- ✅ Automatic semantic HTML (`<a>` vs `<button>`)
- ✅ Accessibility attributes
- ✅ Additional CSS classes support

## Migration Status

### Completed:
- ✅ Button component created
- ✅ Full documentation written
- ✅ `single-rawat-inap.twig` migrated (proof of concept)

### Need Migration (15+ templates):

| Template | Button Count | Priority |
|----------|--------------|----------|
| `front-page.twig` | 6+ | 🔴 High |
| `single-doctor.twig` | 2 | 🔴 High |
| `single-layanan.twig` | 2 | 🔴 High |
| `single-poliklinik.twig` | 2 | 🟡 Medium |
| `single-jurnal.twig` | 2 | 🟡 Medium |
| `page-e-jurnal.twig` | 4+ | 🟡 Medium |
| `page-berita-artikel.twig` | 1 | 🟡 Medium |
| `page-fasilitas-rawat-inap.twig` | 3+ | 🟡 Medium |
| `partials/share-actions.twig` | 3 | 🟢 Low |
| `blocks/doctor-search.twig` | 2 | 🟢 Low |
| Other templates | 10+ | 🟢 Low |

**Total:** ~40+ button instances need migration

## Migration Example

### Before (Hardcoded):
```twig
<a href="{{ site.url }}/kontak/" class="rspku-button rspku-button-primary w-full gap-2">
  {{ icon('phone', { class: 'h-4 w-4' }) }}
  <span>Hubungi admisi</span>
</a>
```
**Lines:** 4  
**Complexity:** High  
**Maintainability:** Low

### After (Component):
```twig
{% include 'components/button.twig' with {
  text: 'Hubungi admisi',
  url: site.url ~ '/kontak/',
  variant: 'primary',
  icon: 'phone',
  full_width: true
} only %}
```
**Lines:** 7 (but cleaner)  
**Complexity:** Low  
**Maintainability:** High

## Benefits

### 1. Consistency
- ✅ Same button styles everywhere
- ✅ Predictable behavior
- ✅ Brand consistency

### 2. Maintainability
- ✅ Update once, apply everywhere
- ✅ Easy to fix bugs
- ✅ Centralized button logic

### 3. Developer Experience
- ✅ Less code to write
- ✅ Clear API with props
- ✅ No need to remember class names
- ✅ Faster development

### 4. Accessibility
- ✅ Automatic semantic HTML
- ✅ Proper ARIA attributes
- ✅ Screen reader friendly

### 5. Flexibility
- ✅ Easy to customize per use case
- ✅ Support for icons
- ✅ Multiple variants and sizes

## Quick Start Guide

### Basic Button
```twig
{% include 'components/button.twig' with {
  text: 'Click me',
  url: '/page/'
} only %}
```

### Primary CTA
```twig
{% include 'components/button.twig' with {
  text: 'Buat janji',
  url: '/dokter/',
  variant: 'primary',
  icon: 'calendar'
} only %}
```

### Secondary Button
```twig
{% include 'components/button.twig' with {
  text: 'Lihat detail',
  url: '/layanan/',
  variant: 'secondary'
} only %}
```

### Small Button
```twig
{% include 'components/button.twig' with {
  text: 'Baca',
  url: '/artikel/',
  size: 'sm'
} only %}
```

### Full Width Button
```twig
{% include 'components/button.twig' with {
  text: 'Hubungi admisi',
  url: '/kontak/',
  full_width: true
} only %}
```

### Submit Button (Form)
```twig
{% include 'components/button.twig' with {
  text: 'Cari',
  type: 'submit',
  icon: 'search'
} only %}
```

### External Link
```twig
{% include 'components/button.twig' with {
  text: 'Unduh PDF',
  url: 'https://example.com/file.pdf',
  target: '_blank',
  icon: 'download'
} only %}
```

## Files

### Created:
- ✅ `wp-content/themes/rspku-theme/resources/views/components/button.twig`

### Documentation:
- ✅ `BUTTON-COMPONENT.md` - Full component documentation
- ✅ `BUTTON-STANDARDIZATION-SUMMARY.md` - This file

### Modified:
- ✅ `wp-content/themes/rspku-theme/resources/views/pages/single-rawat-inap.twig`
- ✅ `COMPONENT-SYSTEM.md` - Updated with button component

## Next Steps

### Phase 1: High Priority (Front-facing pages)
1. [ ] Migrate `front-page.twig` (6+ buttons)
2. [ ] Migrate `single-doctor.twig` (2 buttons)
3. [ ] Migrate `single-layanan.twig` (2 buttons)

### Phase 2: Medium Priority (Content pages)
4. [ ] Migrate `single-poliklinik.twig`
5. [ ] Migrate `single-jurnal.twig`
6. [ ] Migrate `page-e-jurnal.twig`
7. [ ] Migrate `page-berita-artikel.twig`
8. [ ] Migrate `page-fasilitas-rawat-inap.twig`

### Phase 3: Low Priority (Partials & blocks)
9. [ ] Migrate `partials/share-actions.twig`
10. [ ] Migrate `blocks/doctor-search.twig`
11. [ ] Migrate remaining templates

### Phase 4: Enhancement
12. [ ] Add loading state variant
13. [ ] Add icon-only variant
14. [ ] Create button showcase page
15. [ ] Add Storybook documentation (optional)

## Testing Checklist

- [x] Component created
- [x] Documentation written
- [x] Single template migrated
- [x] Build successful
- [ ] Visual regression testing
- [ ] All variants tested
- [ ] All sizes tested
- [ ] Icon positioning tested
- [ ] Responsive behavior tested
- [ ] Accessibility tested
- [ ] Cross-browser tested

## Impact

**Current State:**
- 40+ hardcoded button instances
- Inconsistent patterns
- Hard to maintain
- Easy to make mistakes

**After Full Migration:**
- 40+ standardized button components
- Consistent UI patterns
- Easy to maintain
- Centralized control
- Better accessibility

**Code Quality:** ⭐⭐ → ⭐⭐⭐⭐⭐  
**Maintainability:** ⭐⭐ → ⭐⭐⭐⭐⭐  
**Consistency:** ⭐⭐ → ⭐⭐⭐⭐⭐

## Summary

Button component berhasil dibuat dan siap digunakan. Saat ini button di theme **BELUM standardized** - masih menggunakan hardcoded markup di 15+ templates dengan 40+ instances. 

Component ini menyediakan API yang clean dan flexible untuk menggantikan semua hardcoded button dengan standardized component yang mudah di-maintain dan konsisten.

**Recommendation:** Migrate semua button ke component ini secara bertahap, dimulai dari high-priority pages (front-page, single-doctor, single-layanan).
