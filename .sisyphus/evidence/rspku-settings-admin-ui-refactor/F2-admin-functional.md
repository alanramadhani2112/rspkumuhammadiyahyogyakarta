# F2 Admin Functional Verdict

Date: 2026-08-18

VERDICT: APPROVE

## Scope

Live browser admin access still redirects to `/404/`, so this verdict uses the allowed equivalent proof: WordPress bootstrap, original option backup, simulated active-tab submissions through `RSPKU_Settings_Admin::sanitize()`, `update_option()`, reload via `get_option()`, untouched sentinel checks, and guaranteed restore.

No production PHP/JS/CSS files were intentionally edited.

## Admin Access Status

Live URL remains blocked:

```bash
curl.exe -s -o NUL -L -w "final_url=%{url_effective}`nhttp_code=%{http_code}`nredirects=%{num_redirects}`n" "http://rspkudev.test/wp-admin/admin.php?page=rspku-settings"
```

Result from previous F2 attempt:

```text
final_url=http://rspkudev.test/404/
http_code=404
redirects=1
```

## Programmatic Save/Reload Proof

Temporary script path, outside repo:

```text
C:\Users\LENOVO\AppData\Local\Temp\opencode\rspku-f2-admin-proof.php
```

Safety behavior:

- Bootstrapped WordPress through `C:/laragon/www/rspkudev/wp-load.php`.
- Read option key from `RSPKU_SETTINGS_OPTION_KEY`.
- Backed up original `rspku_settings` option before any write.
- Simulated each tab by setting `$_POST['active_tab']` before calling `RSPKU_Settings_Admin::sanitize($input)`.
- Saved with `update_option(RSPKU_SETTINGS_OPTION_KEY, $clean)`.
- Reloaded with `get_option(RSPKU_SETTINGS_OPTION_KEY, [])` after each save.
- Verified touched keys changed to expected sanitized values.
- Verified a sentinel untouched key survived each active-tab save.
- Restored original option in `finally`, then verified restore.

First attempt proved cleanup but failed image proof because fake attachment IDs sanitize to `0` by design:

```text
status=FAIL
error=homepage.hero_image_id mismatch: expected 12345 got 0
restore=PASS
```

The passing run used existing image attachment IDs discovered from WordPress media: `20345`, `20344`.

Passing command:

```bash
php "C:\Users\LENOVO\AppData\Local\Temp\opencode\rspku-f2-admin-proof.php"
```

Passing result:

```json
{
    "status": "PASS",
    "run_id": "F2-20260818022120",
    "option_existed_before": true,
    "matrix": [
        {
            "tab": "umum",
            "touched": [
                "site_name"
            ],
            "sentinel": "google_maps_link"
        },
        {
            "tab": "kontak",
            "touched": [
                "google_maps_link",
                "service_hours"
            ],
            "sentinel": "site_name"
        },
        {
            "tab": "homepage",
            "touched": [
                "hero_title",
                "promo_slide_1_enabled",
                "hero_image_id",
                "home_featured_services",
                "home_featured_doctors"
            ],
            "sentinel": "google_maps_link"
        },
        {
            "tab": "sejarah",
            "touched": [
                "history_hero_image_id",
                "history_hero_caption"
            ],
            "sentinel": "hero_image_id"
        }
    ],
    "tools_contract": {
        "export": "static-reviewed: current_user_can + rspku_settings_export nonce + wrapped settings payload",
        "import": "static-reviewed: current_user_can + rspku_settings_import nonce + wrapped/flat JSON + known-key filtering + sanitize + update_option"
    },
    "pre_restore_key_count": 125,
    "restore": "PASS",
    "restored_key_count": 82
}
```

## Matrix Coverage

| Tab | Type | Key(s) | Proof |
|---|---|---|---|
| Umum | text | `site_name` | saved/reloaded, `google_maps_link` sentinel survived |
| Kontak | URL | `google_maps_link` | saved/reloaded, `site_name` sentinel survived |
| Kontak | repeater | `service_hours` | saved/reloaded sanitized rows, `highlight` boolean verified |
| Homepage | textarea/text | `hero_title` | saved/reloaded with allowed `<span>` HTML preserved |
| Homepage | toggle | `promo_slide_1_enabled` | saved/reloaded with active-tab checkbox semantics |
| Homepage | image | `hero_image_id` | saved/reloaded using valid image attachment `20345` |
| Homepage | checkbox picker/post picker | `home_featured_services`, `home_featured_doctors` | saved/reloaded as `absint` arrays |
| Sejarah | image | `history_hero_image_id` | saved/reloaded using valid image attachment `20344` |
| Sejarah | textarea | `history_hero_caption` | saved/reloaded with newline-preserving sanitized textarea |
| Tools | import/export | `handleExport()`, `handleImport()` | static contract reviewed; Tools has no normal save form |

## Selector JS Evidence

- `assets/admin.js` unsaved-change guard sets dirty on input/change and clears dirty on submit; it does not prevent submit.
- Image controls use `.rspku-image-upload`, hidden input, `.rspku-image-preview-img`, `.rspku-image-preview`, `.rspku-image-empty`, `.rspku-image-select`, `.rspku-image-remove`.
- Repeater controls use `.rspku-repeater`, `.rspku-repeater-add`, `.rspku-repeater-row`, `.rspku-repeater-remove`; generated `service_hours[index][label|time|highlight]` names match sanitizer.
- Existing `npm test` covers required admin selectors, checkbox picker wrappers, checkbox-array post picker names, non-sticky actions, and submit-prevention regression.

## Nonce/Capability/Admin-Post Evidence

- `RSPKU_Settings_Admin::register()` hooks `admin_post_rspku_settings_save`, `admin_post_rspku_settings_export`, `admin_post_rspku_settings_import`.
- `handleSave()` requires `current_user_can(self::CAPABILITY)`, `check_admin_referer('rspku_settings_save', '_rspku_nonce')`, extracts `$_POST[RSPKU_SETTINGS_OPTION_KEY]`, sanitizes, then `update_option()`.
- Save form posts to `admin-post.php` with `action=rspku_settings_save`, `_rspku_nonce`, and `active_tab`.
- `handleExport()` requires `current_user_can(self::CAPABILITY)` and `check_admin_referer('rspku_settings_export')`, then emits wrapped JSON with `settings => get_option(RSPKU_SETTINGS_OPTION_KEY, [])`.
- `handleImport()` requires `current_user_can(self::CAPABILITY)` and `check_admin_referer('rspku_settings_import')`, accepts wrapped or flat JSON, filters to known default keys, sanitizes, then `update_option()`.

## Value-Loss Risk

Equivalent proof verified active-tab save/reload without losing sentinel values from other tabs.

Static reasoning matches runtime proof: `sanitize()` walks defaults, prefers submitted values, preserves stored values for unsubmitted non-active-tab keys, uses defaults only when neither submitted nor stored exists, and interprets missing active-tab booleans/arrays as unchecked/empty only for that tab.

Restore was confirmed: `restore=PASS`, restored key count `82`.

## Required Verification

From `wp-content/plugins/rspku-settings`:

```bash
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php
```

Result: passed. Tailwind build completed, `npm test` admin CSS/JS assertions passed, PHP lint returned `No syntax errors detected in includes/class-rspku-settings-admin.php`.
