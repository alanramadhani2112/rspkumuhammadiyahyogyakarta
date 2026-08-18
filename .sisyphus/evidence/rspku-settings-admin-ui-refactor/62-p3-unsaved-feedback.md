# Task 7.3 Unsaved Change Feedback

## Changed
- Added progressive dirty-state feedback in `wp-content/plugins/rspku-settings/assets/admin.js`.
- Added regression guards in `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`.
- Rebuilt `wp-content/plugins/rspku-settings/assets/admin.css` from existing source.

## Behavior Covered
- `.rspku-settings-form` fields mark `settingsDirty = true` on `input change`.
- Native `beforeunload` warns only while dirty.
- `.rspku-settings-tabs .nav-tab` click asks `window.confirm()` only while dirty; cancel prevents tab navigation.
- Normal `.rspku-settings-form` submit clears dirty state and is not prevented.
- No PHP markup, nonce, action, method, sanitizer, or saved data shape changed.

## Verification
- `lsp_diagnostics` on `assets/admin.js`: clean.
- `lsp_diagnostics` on `tests/admin-css.test.mjs`: clean.
- `npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php`: passed from `wp-content/plugins/rspku-settings`.
- `php -l includes/class-rspku-settings-admin.php`: `No syntax errors detected`.

## Notes
- Browser/admin live QA not claimed. Existing blocker remains unauthenticated redirect to `http://rspkudev.test/404/`.
- CSS diagnostics still report pre-existing generated/source warnings for `!important` and descending specificity; this task added no CSS source rules.
