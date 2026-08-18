# Task 3.1 Field Contract Evidence

## Changed Files

- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
- `wp-content/plugins/rspku-settings/assets/admin.source.css`
- `wp-content/plugins/rspku-settings/assets/admin.css`
- `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`

## Contract

- `renderField()` now emits one wrapper contract: `.rspku-settings-field`, `.rspku-settings-field__label`, `.rspku-settings-field__control`, `.rspku-settings-field__description`.
- Default text/email/url inputs use `.rspku-settings-input`.
- Default textarea uses `.rspku-settings-textarea`.
- Default field controls are `width: 100%` with `max-width: none`; stale `rs-max-w-lg` removed from default controls.
- Existing setting names remain `rspku_settings[KEY]`; predictable IDs remain `rspku-KEY`; save/sanitizer flow unchanged.

## Command Results

```text
cd wp-content/plugins/rspku-settings
npm run build:css

> rspku-settings-admin-ui@0.2.0 build:css
> tailwindcss -c tailwind.admin.config.js -i ./assets/admin.source.css -o ./assets/admin.css --minify

Done in 281ms.
```

```text
cd wp-content/plugins/rspku-settings
npm test

Results: 56 passed, 0 failed
```

```text
cd wp-content/plugins/rspku-settings
php -l includes/class-rspku-settings-admin.php

No syntax errors detected in includes/class-rspku-settings-admin.php
```

## Grep Checks

```text
includes/*.php: no rs-max-w-lg matches; field contract classes present.
assets/*.css: no rs-max-w-lg or 520px field cap matches; field contract selectors present in source and generated CSS.
repo grep only finds rs-max-w-lg/520px strings inside tests/admin-css.test.mjs regression assertions.
```

## Diagnostics

- `lsp_diagnostics` clean: `includes/class-rspku-settings-admin.php`.
- `lsp_diagnostics` clean: `tests/admin-css.test.mjs`.
- `lsp_diagnostics` on CSS reports pre-existing Biome warnings/unknown `@tailwind`; build and PostCSS parse pass.

## Manual QA

- Authenticated WP admin DOM viewport QA not claimed; inherited blocker remains unauthenticated redirect to `/404/`.
