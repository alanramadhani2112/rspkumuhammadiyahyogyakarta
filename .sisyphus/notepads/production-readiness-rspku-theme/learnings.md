
## Task 2 - Reproducible Theme Build Gate (2026-07-31T12:36:08.7225273+07:00)
- composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader, 
pm ci, and 
pm run build all exit 0 from wp-content/themes/rspku-theme.
- 
pm ci restores local 
ode_modules; git status --short does not report 
ode_modules.
- Vite manifest at public/build/.vite/manifest.json contains esources/js/app.js.


## Task 2 correction - Reproducible Theme Build Gate
- `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`, `npm ci`, and `npm run build` all exit 0 from `wp-content/themes/rspku-theme`.
- `npm ci` restores local `node_modules`; `git status --short` does not report `node_modules`.
- Vite manifest at `public/build/.vite/manifest.json` contains `resources/js/app.js`.

## Task 3 - Hero Title XSS Sink (2026-07-31)
- Timber already registers a `wp_kses_post` Twig filter; adding another filter with the same name causes `LogicException: Filter "wp_kses_post" is already registered`.
- Use `{{ value|wp_kses_post }}` for trusted allowlisted title formatting in Twig; it preserves allowed tags like `<strong>` and `<br>` while stripping `<script>`.

## Task 5 - Doctor Search API and Frontend Error Handling (2026-07-31)
- Doctor search AJAX invalid nonce returns `403` JSON with Indonesian message `Sesi pencarian tidak valid.`.
- Poisoning the current REMOTE_ADDR rate-limit transient produces `429` even when `X-Forwarded-For` is spoofed, confirming Task 4 default-REMOTE_ADDR policy still holds.
- Doctor search result HTML inserted via `innerHTML` comes from server-rendered Timber/Twig templates; frontend error messages should use DOM text insertion instead of raw HTML strings.

## Task 6 - Public RSPKU Core REST Hardening (2026-07-31)
- `/search?per_page=999` clamps to `per_page=50` and returns 50 items.
- `/posts?per_page=999` returns 50 items after collection cap.
- Poisoned `rspku_rl_search_*` and `rspku_rl_collection_post_*` transients return HTTP 429 with `Retry-After: 60` even when `X-Forwarded-For` is spoofed.
- `/site` and `/home` still return HTTP 200 anonymously.

## 2026-07-31 WordPress asset manifest failure patterns
- Official WP admin_notices docs: use admin_notices for admin-only notices; classes notice + notice-error/notice-warning/etc; optional is-dismissible only dismisses current screen.
- Official WP current_user_can docs: gate by capability, not role; current_user_can('manage_options') or tighter capability for admin-only build notices.
- Public theme asset manifest failures should not throw/fatal for visitors. Pattern: file_exists/is_array guard, error_log diagnostic, return early or fallback asset; admin_notices only for capable admins.

## Task 7 - Asset Manifest, Dev URL, and Frontend Production Polish (2026-07-31)
- `Assets.php` now logs missing/invalid Vite manifest and missing entries with `error_log()` while keeping public rendering non-fatal.
- Admin asset notices are gated by `manage_options` or `switch_themes` and escaped with `esc_html()`.
- Homepage evidence had to normalize the local WordPress host because site options are `http://rspkudev.test`; source grep confirms no `rspkudev.test` remains in theme files.

## Task 8 - Homepage Timeout Root Cause and Ship-Gate Verification (2026-07-31)
- Homepage runtime no longer times out; warmup curl returned 200 in 3.033984s and warm-cache curl returned 200 in 2.787795s.
- Homepage HTML scan found no raw Fatal error, Parse error, Warning:, or Database Connection Error text.
- Browser smoke via isolated headless Chrome loaded /, /dokter/, and /berita-artikel/ with status 200 and 0 console fatal/error messages on each page.
- wp-content/debug.log tail after tests contained no Fatal error or Parse error; latest entries are existing ACF PHP Deprecated notices.


## Task 8 continuation - performance root cause (2026-07-31)
- RSPKU code is not the measured root cause of the <2s failure: CLI handler cost was get_site 0.003320s, get_home 0.264841s, and homepage data assembly 0.124921s.
- Generic WordPress REST was also slow with all plugins active: /?rest_route=/ 3.000562s and /wp-json/wp/v2/posts?per_page=1 2.950214s.
- Temporary restored mu-plugin isolation showed homepage under target when third-party plugins were filtered out: all plugins 3.060711s, RSPKU-only 1.373526s, no active plugins 1.330806s.
- Probe file was removed after measurement; no persistent source changes were made.


- F2 code quality review: changed PHP files pass php -l; theme npm build passes; LSP errors clean for Assets.php, DoctorSearch.php, rspku-core.php. Diff hardening reviewed: debug constants off/fatal handler on, trusted proxy headers gated by RSPKU_TRUSTED_PROXY_IPS/filter, REST per_page caps/rate limits present, doctor search errors use textContent, hero titles use wp_kses_post, header link now /karir/.
