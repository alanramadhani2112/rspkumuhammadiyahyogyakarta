# Task 8.1 Admin Save Reload Matrix

Date: 2026-08-18

## Scope

- QA/evidence only. No production PHP/JS/CSS source edits were made.
- Required tabs checked by static contract and live access attempt: Umum, Kontak, Homepage, Sejarah, Tools.
- Live save/reload/export comparison was attempted but blocked by unauthenticated admin redirect.

## Live Admin Access Attempt

Target URL:

```text
http://rspkudev.test/wp-admin/admin.php?page=rspku-settings
```

Curl result:

```text
HTTP/1.1 302 Found
Location: http://rspkudev.test/404/

HTTP/1.1 404 Not Found
```

Playwright result:

```text
Page URL: http://rspkudev.test/404/
Page Title: Page not found - RS PKU Muhammadiyah Yogyakarta
HTTP status: 404 Not Found
Console: 1 errors, 0 warnings
Snapshot: .playwright-mcp\page-2026-08-18T00-28-50-922Z.yml
Console log: .playwright-mcp\console-2026-08-18T00-28-36-613Z.log#L1
```

Blocker: fresh/unauthenticated browser context cannot reach `wp-admin/admin.php?page=rspku-settings`; WordPress redirects to `/404/`. Therefore no successful live admin save, reload, export, DOM, computed layout, or viewport measurements are claimed.

## Viewport Checks

Required viewports:

- 1440: blocked, admin page redirects to `/404/` before DOM is available.
- 1024: blocked, admin page redirects to `/404/` before DOM is available.
- 782: blocked, admin page redirects to `/404/` before DOM is available.
- 360: blocked, admin page redirects to `/404/` before DOM is available.

Static guard already present from earlier tasks: `tests/admin-css.test.mjs` asserts `.rspku-settings-actions` has no `position: sticky`, no `bottom`, and no `z-index` overlay stacking. This protects the known action-bar overlap regression, but it is not a replacement for live viewport QA.

## Field Type Matrix

Representative field keys from `includes/class-rspku-settings-registry.php`:

| Required type | Representative key | Tab | Registry type | Static contract |
|---|---|---|---|---|
| text | `site_name` | Umum | `text` | standard option-array text renderer path |
| URL | `google_maps_link` | Kontak | `url` | sanitizer uses `esc_url_raw()` for URL type |
| repeater | `service_hours` | Kontak | `repeater_hours` | sanitizer normalizes rows with `label`, `time`, `highlight` |
| image | `hero_image_id` | Homepage | `image` | sanitizer uses image ID path / `absint` guard |
| textarea | `hero_title` | Homepage | `textarea` | sanitizer allows `wp_kses_post()` for hero title/description |
| toggle | `promo_slide_1_enabled` | Homepage | `toggle` | missing checkbox resolved through active-tab ownership |
| post picker | `home_featured_services` | Homepage | `post_picker` | renderer keeps checkbox array name `name="<?php echo esc_attr($name); ?>[]"` |
| post picker | `home_featured_doctors` | Homepage | `post_picker` | renderer keeps checkbox array name `name="<?php echo esc_attr($name); ?>[]"` |
| image | `history_hero_image_id` | Sejarah | `image` | grouped history slot card keeps image renderer path |
| textarea | `history_hero_caption` | Sejarah | `textarea` | history caption sanitizer uses `sanitize_textarea_field()` |
| export/import | `export_import_tool` | Tools | `export_import` | Tools tab provides before/after export/import surface |

Note: no separate registry type named `checkbox_picker` exists. The checkbox picker UI is the renderer used by `post_picker` fields; tests assert `.rspku-checkbox-picker` wrappers and checkbox-array input names remain intact.

## Save / Reload / Export Comparison Attempt

Intended live strategy, blocked by admin redirect:

1. Export baseline JSON from Tools before edits.
2. Edit one representative per required field type across Umum, Kontak, Homepage, Sejarah.
3. Save each active tab, reload same tab, verify field values persist.
4. Export after edits from Tools.
5. Compare key count and key set between before/after exports.
6. Assert touched keys changed only to intended values.
7. Assert untouched keys remain byte-for-byte or structurally equal after JSON decode.

Key loss risk: high if sanitizer treats omitted fields as empty/default, because each admin tab submits only the active tab plus hidden pass-throughs. Existing `sanitize()` avoids this by walking defaults, merging submitted values with stored values, and using defaults only when neither submitted nor stored exists.

Active-tab behavior: existing `sanitize()` uses `$_POST['active_tab']` and `tabContainsField()` so missing unchecked toggles/array fields are interpreted as false/empty only when the active tab owns that field. Missing values from untouched tabs are preserved from stored option values.

## Regression Test Coverage Review

Existing `tests/admin-css.test.mjs` covers save-contract-critical renderer shape:

- option-array input names include `name="<?php echo esc_attr($name); ?>"`.
- Homepage post picker keys `home_featured_services` and `home_featured_doctors` remain `post_picker`, max 6.
- post picker renderer uses `.rspku-checkbox-picker`, `.rspku-checkbox-picker-count`, selected labels, one checkbox template path, no hidden comma/JSON value replacement.
- post picker renderer keeps `name="<?php echo esc_attr($name); ?>[]"`.
- promo/history card renderers keep image fields on existing image picker path.
- admin JS still includes image select/remove selectors.
- action bar overlay regression is guarded.

Coverage gap not modified: tests are static renderer/CSS contract guards, not authenticated WordPress save/reload/export integration tests. Because live admin is blocked and no concrete bug was found, no tests or production files were changed.

## Verification

Command run from `wp-content/plugins/rspku-settings`:

```bash
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php
```

Result: exit code 0.

Observed:

- `npm run build:css` completed; Tailwind rebuilt `assets/admin.css` in 749ms.
- `npm test` ran `node tests/admin-css.test.mjs` and passed all reported assertions.
- `php -l includes/class-rspku-settings-admin.php` completed under the chained command with exit code 0.

Non-failing warning:

```text
Browserslist: caniuse-lite is outdated.
```

## Outcome

- Automated/static verification: pass.
- Live admin save/reload/export matrix: blocked by unauthenticated redirect to `/404/`.
- Viewport DOM checks: blocked by same redirect.
- Production bug found: none.
- Production code changes: none intentional; `assets/admin.css` was regenerated by the required CSS build command.
