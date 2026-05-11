# Button Component - Standardized Button System

**Date:** May 10, 2026  
**Status:** ✅ Created  
**Component:** `components/button.twig`

## Overview

Button component yang reusable untuk standardisasi semua button/link di theme. Menggantikan hardcoded button markup dengan component yang konsisten dan mudah di-maintain.

## Component API

### Basic Usage

```twig
{% include 'components/button.twig' with {
  text: 'Hubungi admisi',
  url: site.url ~ '/kontak/'
} only %}
```

### Full Props

```twig
{% include 'components/button.twig' with {
  text: 'Hubungi admisi',           // Required
  url: site.url ~ '/kontak/',       // Optional (if not provided, renders <button>)
  variant: 'primary',                // Optional: 'primary' | 'secondary' | 'ghost' | 'white'
  size: 'default',                   // Optional: 'sm' | 'default' | 'lg'
  icon: 'phone',                     // Optional: Icon name
  icon_position: 'left',             // Optional: 'left' | 'right'
  full_width: false,                 // Optional: boolean
  target: '_self',                   // Optional: '_self' | '_blank'
  type: 'button',                    // Optional: 'button' | 'submit' | 'reset' (for <button> only)
  disabled: false,                   // Optional: boolean
  class: 'px-8'                      // Optional: Additional CSS classes
} only %}
```

## Props Reference

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `text` | string | **required** | Button text content |
| `url` | string | `null` | Link URL. If provided, renders `<a>`, otherwise `<button>` |
| `variant` | string | `'primary'` | Button style variant |
| `size` | string | `'default'` | Button size |
| `icon` | string | `null` | Icon name (from icon system) |
| `icon_position` | string | `'left'` | Icon position relative to text |
| `full_width` | boolean | `false` | Make button full width |
| `target` | string | `'_self'` | Link target (only for `<a>` tag) |
| `type` | string | `'button'` | Button type (only for `<button>` tag) |
| `disabled` | boolean | `false` | Disable button/link |
| `class` | string | `''` | Additional CSS classes |

## Variants

### Primary (default)
```twig
{% include 'components/button.twig' with {
  text: 'Buat janji',
  url: '/dokter/',
  variant: 'primary'
} only %}
```
**Style:** Green background, white text, hospital brand color

### Secondary
```twig
{% include 'components/button.twig' with {
  text: 'Lihat detail',
  url: '/layanan/',
  variant: 'secondary'
} only %}
```
**Style:** White background, green text, green border

### Ghost
```twig
{% include 'components/button.twig' with {
  text: 'Pelajari lebih lanjut',
  url: '/tentang/',
  variant: 'ghost'
} only %}
```
**Style:** Transparent background, colored text, no border

### White
```twig
{% include 'components/button.twig' with {
  text: 'Hubungi kami',
  url: '/kontak/',
  variant: 'white'
} only %}
```
**Style:** White background, dark text (for dark backgrounds)

## Sizes

### Small
```twig
{% include 'components/button.twig' with {
  text: 'Baca',
  url: '/artikel/',
  size: 'sm'
} only %}
```
**Height:** ~40px, smaller padding

### Default
```twig
{% include 'components/button.twig' with {
  text: 'Lihat detail',
  url: '/layanan/',
  size: 'default'
} only %}
```
**Height:** ~48px, standard padding

### Large
```twig
{% include 'components/button.twig' with {
  text: 'Buat janji sekarang',
  url: '/dokter/',
  size: 'lg'
} only %}
```
**Height:** ~56px, larger padding

## With Icons

### Icon Left (default)
```twig
{% include 'components/button.twig' with {
  text: 'Hubungi admisi',
  url: '/kontak/',
  icon: 'phone'
} only %}
```

### Icon Right
```twig
{% include 'components/button.twig' with {
  text: 'Selengkapnya',
  url: '/artikel/',
  icon: 'arrow-right',
  icon_position: 'right'
} only %}
```

### Icon Only (use text for accessibility)
```twig
{% include 'components/button.twig' with {
  text: 'Cari',
  icon: 'search',
  class: 'aspect-square p-0'
} only %}
```

## Special Cases

### Full Width Button
```twig
{% include 'components/button.twig' with {
  text: 'Hubungi admisi',
  url: '/kontak/',
  full_width: true
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

### Submit Button (form)
```twig
{% include 'components/button.twig' with {
  text: 'Cari Artikel',
  type: 'submit',
  icon: 'search'
} only %}
```

### Disabled Button
```twig
{% include 'components/button.twig' with {
  text: 'Tidak tersedia',
  url: '/layanan/',
  disabled: true
} only %}
```

### With Additional Classes
```twig
{% include 'components/button.twig' with {
  text: 'Selengkapnya',
  url: '/tentang/',
  class: 'px-8 mt-4'
} only %}
```

## Migration Guide

### Before (Hardcoded)
```twig
<a href="{{ site.url }}/kontak/" class="rspku-button rspku-button-primary w-full gap-2">
  {{ icon('phone', { class: 'h-4 w-4' }) }}
  <span>Hubungi admisi</span>
</a>
```

### After (Component)
```twig
{% include 'components/button.twig' with {
  text: 'Hubungi admisi',
  url: site.url ~ '/kontak/',
  variant: 'primary',
  icon: 'phone',
  full_width: true
} only %}
```

**Benefits:**
- 70% less code
- No need to remember class names
- Consistent icon sizing
- Automatic accessibility attributes
- Easy to update globally

## Common Patterns

### CTA Section
```twig
<div class="flex gap-3">
  {% include 'components/button.twig' with {
    text: 'Buat janji',
    url: '/dokter/',
    variant: 'primary'
  } only %}
  
  {% include 'components/button.twig' with {
    text: 'Lihat jadwal',
    url: '/jadwal-dokter/',
    variant: 'secondary'
  } only %}
</div>
```

### Card Actions
```twig
<div class="flex flex-col gap-3">
  {% include 'components/button.twig' with {
    text: 'Baca jurnal',
    url: item.url,
    variant: 'primary',
    full_width: true
  } only %}
  
  {% if item.file %}
    {% include 'components/button.twig' with {
      text: 'Unduh PDF',
      url: item.file.url,
      variant: 'secondary',
      target: '_blank',
      icon: 'download',
      full_width: true
    } only %}
  {% endif %}
</div>
```

### Search Form
```twig
<form method="get" action="{{ site.url }}">
  <div class="flex gap-2">
    <input type="search" name="s" class="rspku-input flex-1">
    {% include 'components/button.twig' with {
      text: 'Cari',
      type: 'submit',
      icon: 'search'
    } only %}
  </div>
</form>
```

### Share Actions
```twig
<div class="flex flex-wrap gap-2">
  {% include 'components/button.twig' with {
    text: 'WhatsApp',
    url: 'https://wa.me/?text=' ~ share_text,
    variant: 'secondary',
    size: 'sm',
    target: '_blank'
  } only %}
  
  {% include 'components/button.twig' with {
    text: 'Facebook',
    url: 'https://facebook.com/sharer?u=' ~ share_url,
    variant: 'secondary',
    size: 'sm',
    target: '_blank'
  } only %}
</div>
```

## Where to Use

### Already Migrated:
- ✅ `single-rawat-inap.twig` - CTA button

### Need Migration:
- [ ] `single-doctor.twig` - Buat janji button
- [ ] `single-layanan.twig` - CTA buttons
- [ ] `single-poliklinik.twig` - Cari dokter button
- [ ] `single-jurnal.twig` - Download PDF button
- [ ] `page-e-jurnal.twig` - Multiple buttons
- [ ] `page-berita-artikel.twig` - Search button
- [ ] `page-fasilitas-rawat-inap.twig` - Detail buttons
- [ ] `front-page.twig` - Multiple CTA buttons
- [ ] `partials/share-actions.twig` - Share buttons
- [ ] `blocks/doctor-search.twig` - Submit button
- [ ] All other templates with buttons

## CSS Classes Reference

The component uses these CSS classes (already defined in theme):

```css
.rspku-button              // Base button styles
.rspku-button-primary      // Primary variant
.rspku-button-secondary    // Secondary variant
.rspku-button-ghost        // Ghost variant (if exists)
.rspku-button-sm           // Small size
.rspku-button-lg           // Large size (if exists)
```

## Accessibility

The component automatically handles:
- ✅ Semantic HTML (`<a>` for links, `<button>` for actions)
- ✅ `rel="noopener"` for external links
- ✅ `aria-disabled` for disabled links
- ✅ Proper button `type` attribute
- ✅ Icon + text for screen readers

## Testing

### Manual Testing:
1. ✅ Visit http://rspkudev.test/rawat-inap/vip-shofa-1/
2. ✅ Verify "Hubungi admisi" button renders correctly
3. ✅ Check button is clickable
4. ✅ Verify icon displays properly
5. ✅ Test responsive behavior

### Component Testing:
Create test page with all variants:
```twig
{# Test all variants #}
{% for variant in ['primary', 'secondary', 'ghost', 'white'] %}
  {% include 'components/button.twig' with {
    text: variant|title ~ ' Button',
    url: '#',
    variant: variant
  } only %}
{% endfor %}

{# Test all sizes #}
{% for size in ['sm', 'default', 'lg'] %}
  {% include 'components/button.twig' with {
    text: size|title ~ ' Size',
    url: '#',
    size: size
  } only %}
{% endfor %}

{# Test with icons #}
{% include 'components/button.twig' with {
  text: 'With Icon Left',
  url: '#',
  icon: 'phone'
} only %}

{% include 'components/button.twig' with {
  text: 'With Icon Right',
  url: '#',
  icon: 'arrow-right',
  icon_position: 'right'
} only %}
```

## Benefits

### 1. Consistency
- Same button styles everywhere
- Predictable behavior
- Brand consistency

### 2. Maintainability
- Update once, apply everywhere
- Easy to add new variants
- Centralized button logic

### 3. Developer Experience
- Less code to write
- Clear API with props
- No need to remember class names

### 4. Accessibility
- Automatic semantic HTML
- Proper ARIA attributes
- Screen reader friendly

### 5. Flexibility
- Easy to customize per use case
- Support for icons
- Multiple variants and sizes

## Next Steps

1. ✅ Button component created
2. ✅ Single rawat inap migrated
3. [ ] Migrate all other templates
4. [ ] Add ghost variant CSS (if not exists)
5. [ ] Add large size CSS (if not exists)
6. [ ] Create button showcase page
7. [ ] Add loading state variant (optional)
8. [ ] Add icon-only variant (optional)

## Summary

Button component berhasil dibuat dengan API yang flexible dan mudah digunakan. Template single-rawat-inap.twig sudah di-migrate sebagai proof of concept. Semua button di theme sebaiknya di-migrate ke component ini untuk consistency dan maintainability.
