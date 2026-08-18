# Task 6.2 Image Picker UX Evidence

## Scope

- Modified `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php` image field renderer only.
- Modified `wp-content/plugins/rspku-settings/assets/admin.source.css` image picker component styles.
- Rebuilt `wp-content/plugins/rspku-settings/assets/admin.css` from source.
- Modified `wp-content/plugins/rspku-settings/assets/admin.js` image select/remove handlers only.
- Modified `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs` with deterministic image picker contract guards.

## Behavior Preserved

- Hidden saved value remains one `<input type="hidden">` with original `id`, `name`, and attachment ID value.
- WordPress media flow remains `wp.media({ title: 'Pilih Gambar', button: { text: 'Gunakan Gambar Ini' }, multiple: false, library: { type: 'image' } })`.
- Select still writes `attachment.id` to the hidden input and updates `.rspku-image-preview-img`.
- Remove still writes `0` to the hidden input.
- Required selectors preserved: `.rspku-image-upload`, `.rspku-image-select`, `.rspku-image-remove`, `.rspku-image-preview`, `.rspku-image-preview-img`.

## UX Changes

- Added clearer empty text: `Belum ada gambar. Nilai tersimpan tetap aman sebagai ID lampiran.`
- Added selected-image status text: `Gambar terpilih. Simpan pengaturan untuk menerapkan perubahan.`
- Changed action label to `Pilih gambar dari Media Library`.
- Styled preview as a contained card with existing green/slate admin palette tokens.
- Remove now clears stale preview image `src` before hiding preview and showing empty state.

## Regression Guards

- `tests/admin-css.test.mjs` now asserts exactly one hidden input in the image renderer.
- Test asserts original hidden input `id`, `name`, and value contract.
- Test asserts `wp.media` title/button/multiple/library contract.
- Test asserts select updates hidden ID, preview image, preview visibility, and empty state.
- Test asserts remove sets `0`, clears stale preview `src`, hides preview, shows empty state, and restores select action.

## Verification

From `wp-content/plugins/rspku-settings`:

```bash
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php
```

Result: passed, exit code 0.

Additional diagnostics:

- `lsp_diagnostics` clean for `includes/class-rspku-settings-admin.php`.
- `lsp_diagnostics` clean for `assets/admin.js`.
- `lsp_diagnostics` clean for `tests/admin-css.test.mjs`.
- `assets/admin.source.css` and generated `assets/admin.css` still show inherited Biome CSS warnings around `!important` and descending specificity; unchanged known warning class from plan context.

## Manual QA

- Authenticated browser admin save/reload QA not claimed. Existing blocker remains unauthenticated redirect to `http://rspkudev.test/404/`.
