# Task 8.2 Import Export Roundtrip Evidence

Timestamp: 2026-08-18 07:52:18 Asia/Jakarta

## Scope

- QA/evidence only.
- No production source edits.
- Target handlers reviewed in `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`.

## Live Admin Attempt

Live admin roundtrip was blocked by access/routing, so no live success is claimed.

Commands attempted from repo root:

```bash
curl.exe -s -o NUL -L -w "final_url=%{url_effective}`nhttp_code=%{http_code}`nredirects=%{num_redirects}`n" "http://rspkudev.test/wp-admin/admin.php?page=rspku-settings&tab=tools"
```

Result:

```text
final_url=http://rspkudev.test/404/
http_code=404
redirects=1
```

Unauthenticated export POST attempt:

```bash
curl.exe -s -o NUL -L -w "final_url=%{url_effective}`nhttp_code=%{http_code}`nredirects=%{num_redirects}`n" -X POST "http://rspkudev.test/wp-admin/admin-post.php" -d "action=rspku_settings_export"
```

Result:

```text
final_url=http://rspkudev.test/wp-admin/admin-post.php
http_code=400
redirects=0
```

Unauthenticated import POST attempt:

```bash
curl.exe -s -o NUL -L -w "final_url=%{url_effective}`nhttp_code=%{http_code}`nredirects=%{num_redirects}`n" -X POST "http://rspkudev.test/wp-admin/admin-post.php" -F "action=rspku_settings_import" -F "settings_file=@NUL;filename=invalid.json;type=application/json"
```

Result:

```text
final_url=http://rspkudev.test/wp-admin/admin-post.php
http_code=400
redirects=0
```

## Static Export Contract

Reviewed `RSPKU_Settings_Admin::handleExport()` at `includes/class-rspku-settings-admin.php:634`.

- Requires `current_user_can(self::CAPABILITY)` before export.
- Requires nonce `check_admin_referer('rspku_settings_export')`.
- Exports pretty JSON with `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
- Payload shape is wrapped metadata plus option array:

```php
$payload = [
    'exported_at' => gmdate('c'),
    'site_url' => home_url('/'),
    'plugin_version' => RSPKU_SETTINGS_VERSION,
    'settings' => get_option(RSPKU_SETTINGS_OPTION_KEY, []),
];
```

- Download headers use `Content-Type: application/json; charset=utf-8` and filename `rspku-settings-YYYYmmdd-HHMMSS.json`.

## Static Import Contract

Reviewed `RSPKU_Settings_Admin::handleImport()` at `includes/class-rspku-settings-admin.php:664`.

- Requires `current_user_can(self::CAPABILITY)` before import.
- Requires nonce `check_admin_referer('rspku_settings_import')`.
- Validates uploaded file presence with `is_uploaded_file()`.
- Rejects empty and `> 1048576` byte uploads with `rspku_import=too_big`.
- Reads uploaded contents and redirects `rspku_import=unreadable` when empty/unreadable.
- Decodes with `json_decode($contents, true)`.
- Invalid JSON or non-array JSON redirects to `rspku_import=invalid_json`.
- Accepts both wrapped exports and flat payloads:

```php
$incoming = is_array($decoded['settings'] ?? null) ? $decoded['settings'] : $decoded;
```

- Filters to known default keys only:

```php
$defaults = RSPKU_Settings_Defaults::all();
$filtered = array_intersect_key($incoming, $defaults);
```

- Sanitizes through the same admin sanitizer before persistence:

```php
$clean = self::sanitize($filtered);
update_option(RSPKU_SETTINGS_OPTION_KEY, $clean);
```

## Roundtrip Shape Assessment

No import/export schema drift found.

- Export wraps the stored option array under `settings`.
- Import recognizes that wrapped `settings` array.
- Import compares keys against `RSPKU_Settings_Defaults::all()` via `array_intersect_key()`.
- Import saves only sanitized known keys back to `RSPKU_SETTINGS_OPTION_KEY`.
- Export-after-import would contain changing metadata (`exported_at`) but the `settings` key/value set should remain stable after sanitizer normalization.

## Invalid JSON Behavior

Existing invalid JSON path is explicit in code:

```php
$decoded = json_decode($contents, true);
if (!is_array($decoded)) {
    wp_safe_redirect(add_query_arg('rspku_import', 'invalid_json', $redirect));
    exit;
}
```

The unauthenticated curl upload could not reach this path because WordPress returned HTTP 400 before handler execution could pass nonce/capability checks. Static handler review confirms the intended `rspku_import=invalid_json` redirect once an authenticated admin submits a valid nonce with invalid JSON content.

## Key/Value Comparison Status

Exact live baseline/import/export-after comparison was not possible because admin tools page access ended at `http://rspkudev.test/404/` and unauthenticated `admin-post.php` requests returned HTTP 400.

Static key/value contract comparison:

- Baseline export source: `get_option(RSPKU_SETTINGS_OPTION_KEY, [])`.
- Import source: exported file's `settings` array when present.
- Accepted keys: intersection with `RSPKU_Settings_Defaults::all()`.
- Saved values: `self::sanitize($filtered)`.
- Next export source: same `RSPKU_SETTINGS_OPTION_KEY`.

Conclusion: unchanged shape expected for valid in-schema values; sanitizer may normalize values by registered/default type, which is existing behavior rather than schema drift.

## Verification

Passed from `wp-content/plugins/rspku-settings`:

```bash
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php
```

Observed result:

- `npm run build:css` completed Tailwind rebuild successfully.
- `npm test` completed `node tests/admin-css.test.mjs` successfully.
- `php -l includes/class-rspku-settings-admin.php` completed syntax check successfully.
