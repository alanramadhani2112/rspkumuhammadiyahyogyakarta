# Task 7.1 Accessibility Pass Evidence

Date: 2026-08-18

## Scope

- Updated `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`.
- Updated `wp-content/plugins/rspku-settings/assets/admin.source.css`.
- Rebuilt `wp-content/plugins/rspku-settings/assets/admin.css`.
- Updated `wp-content/plugins/rspku-settings/assets/admin.js` for dynamic repeater accessible names.
- Updated `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`.

## Accessibility Contracts

- Standard field help now renders deterministic IDs from existing control IDs: `rspku-{key}-description`.
- Standard text, email, url, textarea, color, and toggle controls now reference help with `aria-describedby` when help exists.
- Phone pair and CTA pair inputs now connect help text with deterministic `aria-describedby` IDs.
- Section collapse remains a real `button type="button"` with `aria-expanded="true"` and `aria-controls`; admin JS keeps `aria-expanded` synchronized while only toggling `is-collapsed`.
- Image picker/remove buttons now expose contextual `aria-label` text.
- Link/review repeater controls without visible labels now have `aria-label`; dynamic JS-created rows match the PHP-rendered contract.
- Focus-visible styling now covers tabs, section toggles, action buttons, image buttons, repeater controls, standard controls, and checkbox picker items.

## Regression Guards

- `tests/admin-css.test.mjs` now asserts deterministic description ID wiring, pair `aria-describedby`, image/repeater accessible names, collapse `aria-expanded` sync, focus-visible selectors, and no remove/detach/disable during collapse.

## Verification

Command run from `wp-content/plugins/rspku-settings`:

```bash
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php
```

Result: passed, exit code 0.

Additional diagnostics:

- `lsp_diagnostics` on `includes/class-rspku-settings-admin.php`: no diagnostics.
- `lsp_diagnostics` on `assets/admin.js`: no diagnostics.
- `lsp_diagnostics` on `tests/admin-css.test.mjs`: no diagnostics.
- `lsp_diagnostics` on `assets/admin.source.css` and generated `assets/admin.css`: pre-existing Biome warnings for `!important` and descending specificity remain; no task-caused syntax errors.

## Manual QA

- Browser/admin live keyboard QA not claimed. Existing blocker remains: unauthenticated admin context redirects to `http://rspkudev.test/404/`.

## Failure Follow-up: Focus Selector Test

- Failure: `npm test` failed on `Focus selector present: .rspku-checkbox-picker-item input[type="checkbox"]:focus-visible` after `npm run build:css`.
- Cause: source CSS kept `.rspku-checkbox-picker-item input[type="checkbox"]:focus-visible`, but generated minified CSS rewrote it as `.rspku-checkbox-picker-item input[type=checkbox]:focus-visible`.
- Fix: `tests/admin-css.test.mjs` now parses generated CSS with PostCSS, splits selector lists, and normalizes `[type=checkbox]` to `[type="checkbox"]` before asserting selector presence.
- Coverage preserved: the test still requires the real focus-visible selector in generated CSS; it no longer depends on quote formatting.
