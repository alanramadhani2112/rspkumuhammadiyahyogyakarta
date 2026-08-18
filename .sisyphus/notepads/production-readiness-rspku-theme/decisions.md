
## Task 1 - Production Debug Config Gate
- Set WP_DEBUG=false, WP_DEBUG_DISPLAY=false, WP_DEBUG_LOG=false, and WP_DISABLE_FATAL_ERROR_HANDLER=false in wp-config.php for safe production defaults.
- Preserved DB constants and table prefix unchanged: DB_NAME, DB_USER, DB_PASSWORD, DB_HOST, $table_prefix.
- Verification passed: php -l wp-config.php; bootstrap check printed debug-off and atal-on.

`nCorrection: bootstrap check output was `fatal-on`; evidence files rewritten as ASCII exact command output.
Correction 2: bootstrap check output was fatal-on; evidence files are ASCII and contain exact verification output.

## Task 4 - Shared Trusted Client IP Policy (2026-07-31)
- Default effective IP now comes from REMOTE_ADDR; forwarded headers are ignored unless REMOTE_ADDR is in RSPKU_TRUSTED_PROXY_IPS or rspku_trusted_proxy_ips.
- Duplicated the tiny helper in theme and plugin to avoid cross-plugin/theme coupling.
- Existing rate-limit key shape remains rspku_rl_<bucket>_<md5(effective IP)>; evidence shows repeated calls produce the same key.

## Task 5 - Doctor Search API and Frontend Error Handling (2026-07-31)
- Kept `DoctorSearch.php` unchanged for Task 5 because Task 4 trusted proxy behavior and rate-limit response codes already satisfy the hardening requirement.
- Added `response.ok` handling in `resources/js/app.js` before trusting AJAX payload success; 403/429 messages now render through `textContent` to avoid adding a new raw user-input HTML path.
- Preserved existing server-rendered doctor results and pagination flow instead of rewriting client templating.

## Task 6 - Public RSPKU Core REST Hardening (2026-07-31)
- Kept public REST endpoints public; no auth added.
- Used constants `SEARCH_PER_PAGE_MAX` and `COLLECTION_PER_PAGE_MAX`, both 50, to avoid magic caps.
- Added collection route rate limiting via existing `enforce_rate_limit()` instead of new middleware.
- Kept response fields unchanged; task adds caps and throttling only.

## Task 7 - Asset Manifest, Dev URL, and Frontend Production Polish (2026-07-31)
- Kept asset enqueue behavior fail-open for public visitors; missing assets skip enqueue but no exception/fatal is thrown.
- Used one private `reportMissingAsset()` helper to deduplicate `error_log()` and admin notice messages without new dependencies.
- Replaced hardcoded E-Career dev host with `{{ site.url }}/karir/` instead of adding settings UI.
