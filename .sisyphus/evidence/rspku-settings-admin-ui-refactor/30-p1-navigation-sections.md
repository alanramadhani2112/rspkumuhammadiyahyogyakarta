# Task 4.1 Evidence - Navigation Sections

## Changed Files

- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
- `wp-content/plugins/rspku-settings/assets/admin.source.css`
- `wp-content/plugins/rspku-settings/assets/admin.css`
- `wp-content/plugins/rspku-settings/assets/admin.js`
- `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`
- `.sisyphus/notepads/rspku-settings-admin-ui-refactor/learnings.md`
- `.sisyphus/evidence/rspku-settings-admin-ui-refactor/30-p1-navigation-sections.md`

## Implementation Notes

- `renderPage()` still uses the existing `?page=rspku-settings&tab=...` query flow and hidden `active_tab` input.
- Tab anchors keep `nav-tab` / `nav-tab-active`; active tab now adds `aria-current="page"` and a section-count badge.
- `renderTabContent()` still loops sections and fields from `RSPKU_Settings_Registry::tabs()`.
- Section wrapper now has stable `id` plus `data-section-key`; header adds field-count pill and a button-based progressive enhancement toggle.
- JavaScript toggle changes only `is-collapsed`, `aria-expanded`, and button text. Inputs remain in DOM; submitted `rspku_settings[...]` names/values are not changed.
- CSS baseline keeps `.rspku-settings-section-body` visible. Collapse only applies through `.rspku-settings-section.is-collapsed .rspku-settings-section-body` after JS runs.

## Grep Checks

Command:

```bash
rg "active_tab|nav-tab|\.rspku-settings-section|rspku_settings\[" wp-content/plugins/rspku-settings -g "*.php" -g "*.css" -g "*.js" -g "*.mjs"
```

Observed:

- `includes/class-rspku-settings-admin.php` still contains `$_GET['tab']`, `nav-tab`, `nav-tab-active`, hidden `name="active_tab"`, and active render via `$tabs[$active_tab]`.
- `assets/admin.source.css` and generated `assets/admin.css` contain `.rspku-settings-section`, `.rspku-settings-section-header`, `.rspku-settings-section-body`, `.rspku-settings-section-toggle`, and `.rspku-settings-section.is-collapsed .rspku-settings-section-body`.
- `assets/admin.js` contains only a click handler for `.rspku-settings-section-toggle` for collapse behavior.
- `tests/admin-css.test.mjs` contains regression assertions for nav/section selectors and `active_tab`.

## Deterministic Count Guard

Command:

```bash
php -r "define('ABSPATH', __DIR__); require 'includes/class-rspku-settings-registry.php'; $tabs = RSPKU_Settings_Registry::tabs(); $sections = array_sum(array_map(fn($tab) => count($tab['sections'] ?? []), $tabs)); echo count($tabs) . ' tabs, ' . $sections . ' sections' . PHP_EOL; exit(count($tabs) === 12 && $sections === 30 ? 0 : 1);"
```

Result:

```text
12 tabs, 30 sections
```

Exit code: `0`.

## Required Verification

Command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php
```

Result:

```text
> rspku-settings-admin-ui@0.2.0 build:css
> tailwindcss -c tailwind.admin.config.js -i ./assets/admin.source.css -o ./assets/admin.css --minify

Rebuilding...
Done in 316ms.

> rspku-settings-admin-ui@0.2.0 test
> node tests/admin-css.test.mjs

CSS Syntax: pass
Required Class Selectors: pass
Design System Values: pass
Responsive Breakpoints: pass
Actions Bar Document Flow: pass
Field Contract Widths: pass
Navigation Sections Contract: pass

No syntax errors detected in includes/class-rspku-settings-admin.php
```

Exit code: `0`.

Note: build emitted existing Browserslist warning: `caniuse-lite is outdated`.

## LSP Diagnostics

- `includes/class-rspku-settings-admin.php`: no diagnostics.
- `assets/admin.js`: no diagnostics.
- `tests/admin-css.test.mjs`: no diagnostics.
- `assets/admin.source.css`: Biome reports existing Tailwind at-rule / `!important` / descending-specificity warnings.
- `assets/admin.css`: Biome reports existing generated CSS `!important` / descending-specificity warnings.

## Browser QA

Live authenticated WP admin DOM QA was not claimed. Fresh/unauthenticated admin access remains blocked by HTTP 302 redirect to `http://rspkudev.test/404/`, inherited from baseline evidence.
