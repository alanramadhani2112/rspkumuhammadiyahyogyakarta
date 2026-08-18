# Task 5.2 CTA Text Dan URL Pairs

Date: 2026-08-17

## Changed Files

- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php`
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
- `wp-content/plugins/rspku-settings/assets/admin.source.css`
- `wp-content/plugins/rspku-settings/assets/admin.css`
- `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`

## Implementation Notes

- Homepage CTA text/URL fields now declare registry metadata with `group => homepage_cta_pair`, shared `pair`, and `pair_role => text|url`.
- Renderer groups only metadata-matched Homepage CTA pairs through `isCtaPairStart()` and `renderCtaPair()`.
- Each grouped input still renders `name="rspku_settings[KEY]"`, `id="rspku-KEY"`, escaped saved values, and `class="rspku-settings-input"`.
- Lightweight preview uses current saved text and URL only; no JavaScript, validation, save flow, export/import, REST, nonce, or public template behavior changed.
- URL/text sanitizers unchanged: registry `type` remains `text` for all eight CTA fields, so existing `sanitize_text_field()` path remains active.

## Key Preservation Check

Command:

```bash
cd wp-content/plugins/rspku-settings
php -r '$php=file_get_contents("includes/class-rspku-settings-registry.php"); $q=chr(39); $keys=["hero_cta_primary_text","hero_cta_primary_url","hero_cta_secondary_text","hero_cta_secondary_url","home_cta_primary_text","home_cta_primary_url","home_cta_secondary_text","home_cta_secondary_url"]; foreach ($keys as $key) { $count=substr_count($php, $q."key".$q." => ".$q.$key.$q); if ($count !== 1) { fwrite(STDERR, $key." count ".$count.PHP_EOL); exit(1); } } $metadata=preg_match_all("/".$q."group".$q."\\s*=>\\s*".$q."homepage_cta_pair".$q."/", $php); if ($metadata !== 8) { fwrite(STDERR, "metadata count ".$metadata.PHP_EOL); exit(1); } if (str_contains($php, $q."key".$q." => ".$q."homepage_cta_pair".$q) || str_contains($php, $q."type".$q." => ".$q."cta_pair".$q)) { fwrite(STDERR, "replacement key/type present".PHP_EOL); exit(1); } echo "CTA keys preserved; metadata count 8; no replacement key/type.".PHP_EOL;'
```

Result:

```text
CTA keys preserved; metadata count 8; no replacement key/type.
```

## Regression Guards

- `tests/admin-css.test.mjs` now asserts CTA pair selectors exist in generated CSS.
- It asserts all eight exact CTA keys exist once.
- It asserts all eight stay `type => text`.
- It asserts eight `homepage_cta_pair` metadata declarations.
- It asserts no replacement `homepage_cta_pair` key and no `cta_pair` type.
- It asserts Header CTA stays outside Homepage CTA pair metadata.
- It asserts the admin renderer keeps option array input names.

## Verification

Command:

```bash
cd wp-content/plugins/rspku-settings
npm run build:css && npm test && php -l includes/class-rspku-settings-admin.php && php -l includes/class-rspku-settings-registry.php
```

Result:

```text
npm run build:css: passed; generated assets/admin.css rebuilt. Browserslist stale warning only.
npm test: passed; Results: 123 passed, 0 failed.
php -l includes/class-rspku-settings-admin.php: No syntax errors detected.
php -l includes/class-rspku-settings-registry.php: No syntax errors detected.
```

LSP diagnostics:

```text
class-rspku-settings-registry.php: No diagnostics found.
class-rspku-settings-admin.php: No diagnostics found.
tests/admin-css.test.mjs: No diagnostics found.
admin.source.css/admin.css: existing Biome warnings remain for legacy !important/noDescendingSpecificity; Tailwind unknown-at-rule warning was scoped away.
```

## Browser/Public Smoke

- Browser admin/save/export/public live QA not claimed.
- Blocker inherited from prior evidence: unauthenticated browser context redirects admin URL to `http://rspkudev.test/404/`.
