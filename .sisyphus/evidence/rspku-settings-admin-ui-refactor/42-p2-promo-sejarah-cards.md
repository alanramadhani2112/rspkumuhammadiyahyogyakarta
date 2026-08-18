# Task 5.3 Promo Cards Dan Sejarah Slot Cards Evidence

## Changed Files

- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php`
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
- `wp-content/plugins/rspku-settings/assets/admin.source.css`
- `wp-content/plugins/rspku-settings/assets/admin.css`
- `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`

## Implementation Summary

- Homepage promo fields now use existing per-field keys with `group => promo_card`, `card`, `card_label`, and `card_role => start` metadata.
- Sejarah archive slots now use existing per-field keys with `group => history_slot_card`, `card`, `card_label`, and `card_role => start` metadata.
- Admin renderer now detects card metadata and wraps contiguous card fields in `.rspku-settings-card` without changing input `name`, `id`, `type`, save shape, sanitizer, export/import, REST, or public output.
- Card CSS was added in `admin.source.css` and rebuilt into `admin.css` using existing design values: `#f8fafc`, `#e2e8f0`, `#0f172a`, `8px` radius, existing 14-18px spacing rhythm.
- Regression guards now assert card selectors, exact promo/history key preservation, expected metadata counts, no replacement card key/type, renderer metadata gates, and image picker selectors.

## Key Preservation Command And Result

Command from `wp-content/plugins/rspku-settings`:

```bash
python -c "from pathlib import Path; s=Path('includes/class-rspku-settings-registry.php').read_text(); promo='promo_slide_1_enabled promo_slide_1_image_id promo_slide_1_title promo_slide_1_description promo_slide_1_cta_text promo_slide_1_cta_url promo_slide_2_enabled promo_slide_2_image_id promo_slide_2_title promo_slide_2_description promo_slide_2_cta_text promo_slide_2_cta_url promo_slide_3_enabled promo_slide_3_image_id promo_slide_3_title promo_slide_3_description promo_slide_3_cta_text promo_slide_3_cta_url'.split(); slots='history_hero history_pioneers history_child_service history_first_stone history_modernization'.split(); hist=[x+'_'+y for x in slots for y in 'image_id year title caption alt'.split()]; bad=[k for k in promo+hist if s.count(chr(39)+'key'+chr(39)+' => '+chr(39)+k+chr(39)) != 1]; assert not bad, bad; assert chr(39)+'key'+chr(39)+' => '+chr(39)+'promo_card'+chr(39) not in s; assert chr(39)+'key'+chr(39)+' => '+chr(39)+'history_card'+chr(39) not in s; assert chr(39)+'type'+chr(39)+' => '+chr(39)+'promo_card'+chr(39) not in s; assert chr(39)+'type'+chr(39)+' => '+chr(39)+'history_card'+chr(39) not in s; print('promo/history exact keys once; no replacement card key/type')"
```

Result:

```text
promo/history exact keys once; no replacement card key/type
```

## Image Picker Selector Note

- `assets/admin.js` was not changed.
- Existing image fields still render through `.rspku-image-upload` with hidden input, `.rspku-image-select`, `.rspku-image-remove`, and `.rspku-image-preview` unchanged.
- Tests assert `.rspku-image-select` and `.rspku-image-remove` selectors remain present.

## Verification Command And Result

Command from `wp-content/plugins/rspku-settings`:

```bash
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php && php -l includes/class-rspku-settings-registry.php
```

Result:

```text
npm run build:css: passed; Tailwind rebuilt assets/admin.css. Browserslist stale warning only.
npm test: passed; 184 passed, 0 failed.
php -l includes/class-rspku-settings-admin.php: No syntax errors detected.
php -l includes/class-rspku-settings-registry.php: No syntax errors detected.
```

Additional diagnostics:

```text
lsp_diagnostics admin.php: clean.
lsp_diagnostics registry.php: clean.
lsp_diagnostics admin-css.test.mjs: clean.
lsp_diagnostics admin.source.css: existing Biome CSS warnings for !important/descending specificity remain; no task-specific diagnostic errors.
```

## Browser/Public Blocker

- Authenticated WordPress admin/browser save-export-public smoke was not performed.
- Existing blocker remains: unauthenticated browser contexts redirect admin/public QA attempts to `http://rspkudev.test/404/`; no live DOM/save/export/public proof was claimed.
