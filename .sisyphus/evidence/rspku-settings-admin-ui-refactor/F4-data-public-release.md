# F4 Data Public Release Verdict

Date: 2026-08-18 09:31:17 Asia/Jakarta

VERDICT: APPROVE

## Scope

QA/evidence only. No production source edits intended. Existing evidence and static plugin code were reviewed, then the required plugin verification command was run.

## Evidence Reviewed

- `.sisyphus/evidence/rspku-settings-admin-ui-refactor/71-import-export-roundtrip.md`
- `.sisyphus/evidence/rspku-settings-admin-ui-refactor/72-public-smoke.md`
- `.sisyphus/evidence/rspku-settings-admin-ui-refactor/F2-admin-functional.md`
- `wp-content/plugins/rspku-settings/rspku-settings.php`
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-defaults.php`
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php`
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-api.php`

## Required Release Gates

| Gate | Result | Evidence |
|---|---|---|
| Import/export roundtrip unchanged or equivalent proof | PASS | `71-import-export-roundtrip.md` shows export wraps `settings => get_option(RSPKU_SETTINGS_OPTION_KEY, [])`; import accepts wrapped or flat payloads, filters with `array_intersect_key($incoming, RSPKU_Settings_Defaults::all())`, sanitizes via `RSPKU_Settings_Admin::sanitize()`, then saves to `RSPKU_SETTINGS_OPTION_KEY`. Exact live admin roundtrip was blocked, but equivalent static contract proof supports stable settings key/value roundtrip after sanitizer normalization. |
| Wrapped export | PASS | `handleExport()` builds metadata plus `settings` array and encodes with `wp_json_encode(... JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)`. |
| Import accepts wrapped and flat JSON | PASS | `handleImport()` uses `is_array($decoded['settings'] ?? null) ? $decoded['settings'] : $decoded`. |
| Known-key filtering | PASS | `handleImport()` intersects incoming keys with `RSPKU_Settings_Defaults::all()` before sanitize/save. |
| Invalid JSON behavior | PASS | `handleImport()` redirects with `rspku_import=invalid_json` when `json_decode(..., true)` is not an array. |
| Public smoke pages | PASS | `72-public-smoke.md` shows `/`, `/kontak/`, `/dokter/`, and `/sejarah-kami/` returned HTTP 200, no fatal/error text, and main content present via curl plus browser checks. |
| Option key/schema unchanged | PASS | `rspku-settings.php` still defines `RSPKU_SETTINGS_OPTION_KEY` as `rspku_settings`; defaults/registry remain the canonical key set; no required field-key rename found in static review. |
| Field keys unchanged | PASS | Registry/defaults still expose the checked fields including `site_name`, `google_maps_link`, `service_hours`, `hero_title`, `promo_slide_1_enabled`, `hero_image_id`, `home_featured_services`, `home_featured_doctors`, `history_hero_image_id`, and `history_hero_caption`; F2 evidence saved/reloaded these representative keys. |
| Sanitizer unchanged/safe | PASS | `RSPKU_Settings_Admin::sanitize()` still iterates defaults, merges stored values for unsubmitted non-active-tab fields, handles booleans/arrays by active tab, sanitizes URLs/text/repeaters/images/post IDs, and is reused by save/import. F2 programmatic proof verified representative sanitized save/reload paths and restore. |
| API/public output unchanged or smoke-equivalent | PASS | `RSPKU_Settings_API::publicSettingsPayload()` remains read-only public REST output backed by `RSPKU_Settings_API::all()` merging defaults plus saved option; public smoke pages passed with main content and no fatal text. |

## Verification

Run from `wp-content/plugins/rspku-settings`:

```bash
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php
```

Result: passed. `build:css` completed, `node tests/admin-css.test.mjs` passed, and `php -l includes/class-rspku-settings-admin.php` reported no syntax errors.

Note: `npm run build:css` regenerated `assets/admin.css` during verification; that self-generated verification artifact was restored to the pre-command `HEAD` content so this task only leaves the F4 evidence file and notepad append.
