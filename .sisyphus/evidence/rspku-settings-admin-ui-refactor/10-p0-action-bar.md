# Task 2.1 P0 Action Bar Evidence

## Scope

- Removed overlay-causing behavior from `.rspku-settings-actions` only.
- Kept the single action bar in normal document flow after form content.
- No PHP renderer, save/import/export/API/public behavior changed.

## Files Changed

- `wp-content/plugins/rspku-settings/assets/admin.source.css`
- `wp-content/plugins/rspku-settings/assets/admin.css`
- `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`

## Commands And Results

```bash
cd wp-content/plugins/rspku-settings
npm run build:css
```

Result: passed. Tailwind rebuilt `assets/admin.css`. Warning only: Browserslist `caniuse-lite` outdated.

```bash
cd wp-content/plugins/rspku-settings
npm test
```

Result: passed. `46 passed, 0 failed`.

Regression guard added: generated CSS is parsed with PostCSS; every `.rspku-settings-actions` declaration set is checked for no `position: sticky`, no `bottom`, no `z-index`.

```bash
cd wp-content/plugins/rspku-settings
php -l includes/class-rspku-settings-admin.php
```

Result: passed. `No syntax errors detected in includes/class-rspku-settings-admin.php`.

## Diagnostics

- `tests/admin-css.test.mjs`: no LSP diagnostics.
- `assets/admin.source.css`: Biome reports pre-existing CSS diagnostics: unknown Tailwind at-rule at line 1, existing `!important` warnings, existing descending-specificity warnings.
- `assets/admin.css`: Biome reports pre-existing generated-CSS warnings for `!important` and descending specificity.

## Browser QA

Admin browser DOM viewport pass remains blocked because no authenticated WP admin session is available. No live DOM viewport pass claimed.
