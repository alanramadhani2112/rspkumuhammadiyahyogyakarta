# Task 6.1 - Jam Operasional Repeater UX

Date: 2026-08-17

## Scope

- Updated only `repeater_hours` UI in `includes/class-rspku-settings-admin.php`.
- Updated repeater source styles in `assets/admin.source.css` and generated `assets/admin.css` via `npm run build:css`.
- Updated `assets/admin.js` add/remove behavior for the hours repeater while preserving `.rspku-repeater-add` and `.rspku-repeater-remove` selectors.
- Updated `tests/admin-css.test.mjs` with deterministic guards for selectors, saved input names, add-row template shape, empty state behavior, and mobile stacking.

## Behavior Protected

- Existing saved row inputs still render as `rspku_settings[key][index][label|time|highlight]`.
- JavaScript add-row template still creates `${name}[${index}][label]`, `${name}[${index}][time]`, and `${name}[${index}][highlight]`.
- No JSON/object serialization replaced the form input shape inside the `repeater_hours` renderer or add-row template.
- Existing rows remain visible/editable without JavaScript because PHP renders labels, inputs, checkbox, remove button, and empty state directly.
- 360px layout stacks by default with `.rspku-repeater-row--hours { grid-template-columns: 1fr; }`; multi-column layout starts only at `@media (min-width: 600px)`.

## Verification

From `wp-content/plugins/rspku-settings`:

```bash
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php
```

Result: passed, exit code 0.

Additional deterministic guard:

```bash
node -e 'const fs=require("fs"); const php=fs.readFileSync("includes/class-rspku-settings-admin.php","utf8"); const js=fs.readFileSync("assets/admin.js","utf8"); const phpBlock=php.slice(php.indexOf("repeater_hours"), php.indexOf("repeater_links")); const jsBlock=js.slice(js.indexOf(".rspku-repeater-add"), js.indexOf("// Repeater: remove row")); for (const s of ["[label]","[time]","[highlight]"]) { if (!phpBlock.includes(s) || !jsBlock.includes(s)) { console.error("missing "+s); process.exit(1); } } if (phpBlock.includes("json_encode") || jsBlock.includes("JSON.stringify")) { console.error("json/object replacement found in hours repeater"); process.exit(1); } console.log("repeater_hours name-shape guard passed");'
```

Result: `repeater_hours name-shape guard passed`.

LSP diagnostics:

- `includes/class-rspku-settings-admin.php`: no diagnostics.
- `assets/admin.js`: no diagnostics.
- `tests/admin-css.test.mjs`: no diagnostics.
- `assets/admin.source.css` and generated `assets/admin.css`: inherited Biome warnings for WordPress override `!important` and descending specificity remain; build/test are authoritative per plan notes.

## Manual QA Note

Live WordPress admin save/reload/browser QA was not claimed. Prior inherited blocker remains: unauthenticated admin browser context redirects to `http://rspkudev.test/404/`.

## Follow-up Fix - Sparse Add Index

Verifier found the hours add handler still used row count:

```js
const index = $container.find('.rspku-repeater-row').length;
```

Failure mode: existing rows `0,1,2`, remove row `1`, then add creates duplicate index `2` and can corrupt submitted values.

Fix: `assets/admin.js` now uses `getNextRepeaterIndex($container, name)`, scanning existing `label|time|highlight` input names and returning max existing index + 1. Sparse indices `0` and `2` now produce next index `3`. Saved names remain `${name}[${index}][label|time|highlight]`.

Added regression guards in `tests/admin-css.test.mjs`:

- `.rspku-repeater-add` no longer uses `find('.rspku-repeater-row').length` inside the hours add block.
- `getNextRepeaterIndex($container, name)` is present and used.
- deterministic sparse-name guard verifies indices `0` and `2` return `3`.

Verification rerun from `wp-content/plugins/rspku-settings`:

```bash
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php
```

Result: passed, exit code 0.

Additional guard:

```bash
node -e 'const names=["rspku_settings[service_hours][0][label]","rspku_settings[service_hours][0][time]","rspku_settings[service_hours][2][highlight]"]; let max=-1; for (const name of names) { const match=String(name||"").match(/\[(\d+)]\[(label|time|highlight)]$/); if (match) max=Math.max(max, Number(match[1])); } const next=max+1; if (next !== 3) { console.error(`expected 3, got ${next}`); process.exit(1); } console.log(`sparse-index guard passed: ${next}`);'
```

Result: `sparse-index guard passed: 3`.
