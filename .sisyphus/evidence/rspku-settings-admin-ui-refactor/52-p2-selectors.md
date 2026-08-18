# Task 6.3 Post Picker / Checkbox Picker UX Evidence

## Scope

- Modified `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php` only inside `$type === 'post_picker'` renderer.
- Modified `wp-content/plugins/rspku-settings/assets/admin.source.css` and rebuilt `assets/admin.css`.
- Updated `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs` with deterministic post picker contract guards.
- Did not touch `assets/admin.js`; current renderer remains PHP checkbox array, not legacy comma-value `.rspku-post-picker`.

## Contract Preserved

- Registry keys remain `home_featured_services` and `home_featured_doctors`.
- Registry type remains `post_picker` for both fields.
- Registry max remains `6` for both fields.
- Renderer keeps checkbox input shape: `name="<?php echo esc_attr($name); ?>[]"`.
- Renderer does not introduce hidden replacement input, JSON serialization, comma-string serialization, or `.rspku-post-picker-value`.

## UX Changes

- Added `.rspku-checkbox-picker` wrapper contract to the PHP renderer.
- Added `.rspku-checkbox-picker-header`, `.rspku-checkbox-picker-hint`, and `.rspku-checkbox-picker-count`.
- Added selected item class `.rspku-checkbox-picker-item.is-selected` while keeping checkbox checked state authoritative.
- Removed inline grid-template style from renderer; grid now lives in CSS and stacks to one column at the existing mobile breakpoint.
- Improved spacing via existing green/slate design values already present in the admin CSS.

## Verification

- `npm run build:css` passed and regenerated `assets/admin.css`.
- `npm test` passed, including new post picker contract checks.
- `php -l includes/class-rspku-settings-admin.php` passed.
- `lsp_diagnostics` found no PHP or test diagnostics. CSS diagnostics only reported pre-existing `!important`/specificity warnings in generated/admin CSS areas, not task-specific errors.

## Live QA

- Authenticated browser save/reload/import/export QA remains blocked by inherited unauthenticated redirect to `http://rspkudev.test/404/`; no live admin persistence claim made.
