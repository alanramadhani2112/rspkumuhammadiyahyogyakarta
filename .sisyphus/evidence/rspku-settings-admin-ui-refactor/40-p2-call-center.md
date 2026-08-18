# Task 5.1 Evidence - Call Center Display/Tel Pairs

## Changed Files

- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php`
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
- `wp-content/plugins/rspku-settings/assets/admin.source.css`
- `wp-content/plugins/rspku-settings/assets/admin.css`
- `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`

## Implementation Notes

- Added `group`, `pair`, and `pair_role` metadata to the existing six call center fields only.
- Kept each existing key and `type => text` unchanged: `phone_igd`, `phone_igd_link`, `phone_main`, `phone_main_link`, `whatsapp`, `whatsapp_link`.
- Added a narrow admin renderer path for adjacent `call_center` display/tel metadata pairs.
- Kept each input as a normal editable HTML text input using `name="rspku_settings[KEY]"` through `RSPKU_SETTINGS_OPTION_KEY . '[' . $key . ']'`, `id="rspku-KEY"`, escaped values, and the existing save/sanitize path.
- Added pair layout selectors to `admin.source.css`, rebuilt generated `admin.css`.
- Added deterministic test assertions for pair selectors and exact registry key preservation.

## Key Preservation Check

Registry grep found only the six original phone keys, with metadata added in place:

```text
phone_igd
phone_igd_link
phone_main
phone_main_link
whatsapp
whatsapp_link
```

No replacement option key/type was added:

```text
'key' => 'call_center' absent
'type' => 'phone_pair' absent
```

PHP/Node one-liner result:

```text
call center keys preserved: phone_igd, phone_igd_link, phone_main, phone_main_link, whatsapp, whatsapp_link
```

## Verification

Command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php && php -l includes/class-rspku-settings-registry.php
```

Result:

```text
> rspku-settings-admin-ui@0.2.0 build:css
> tailwindcss -c tailwind.admin.config.js -i ./assets/admin.source.css -o ./assets/admin.css --minify

Browserslist: caniuse-lite is outdated. Please run:
  npx update-browserslist-db@latest
  Why you should do it regularly: https://github.com/browserslist/update-db#readme

Rebuilding...

Done in 319ms.

> rspku-settings-admin-ui@0.2.0 test
> node tests/admin-css.test.mjs

Results: 92 passed, 0 failed

No syntax errors detected in includes/class-rspku-settings-admin.php
No syntax errors detected in includes/class-rspku-settings-registry.php
```

Additional checks:

```text
lsp_diagnostics class-rspku-settings-admin.php: No diagnostics found
lsp_diagnostics class-rspku-settings-registry.php: No diagnostics found
lsp_diagnostics tests/admin-css.test.mjs: No diagnostics found
```

## Browser QA Blocker

Authenticated WordPress admin browser QA was not claimed. Inherited blocker remains: unauthenticated browser context redirects admin route with HTTP 302 to `http://rspkudev.test/404/`. DOM/save/export live proof needs an authenticated admin session.
