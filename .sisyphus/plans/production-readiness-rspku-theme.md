# Production Readiness RSPKU Theme

## TL;DR
> **Summary**: Perbaiki blocker production pada theme/plugin RSPKU tanpa redesign: debug config, build reproducibility, XSS sink, REST/rate-limit hardening, dev URL leak, manifest failure, dan homepage timeout.
> **Deliverables**:
> - Production-safe WordPress config guidance/fix.
> - Reproducible Vite/Composer build verification.
> - Hardened public REST and doctor search throttling.
> - Escaped/allowlisted Twig hero titles.
> - Removed dev-domain leak and safer frontend error handling.
> - Agent-executed verification evidence in `.sisyphus/evidence/`.
> **Effort**: Medium
> **Parallel**: YES - 3 waves
> **Critical Path**: Task 1 -> Task 2 -> Task 7 -> Final Verification Wave

## Context

### Original Request
- User: “Apakah ada yang perlu kita perbaiki?”
- User: “Susun dulu plannya”

### Interview Summary
- Audit verdict: theme belum layak production penuh.
- Scope requested: susun plan dulu, bukan eksekusi fix.
- Scope chosen: minimal production-readiness, no redesign, no new dependencies.

### Metis Review (gaps addressed)
- Public REST intent belum dikunci: plan defaults to public read-only API, harden with rate limit/cache/schema preservation, no auth added blindly.
- Production proxy/CDN unknown: plan defaults to not trusting `HTTP_X_FORWARDED_FOR`; allow Cloudflare only when source IP/header validation can be proven.
- Build source of truth unknown: plan chooses deploy-time build from existing lockfiles (`composer install`, `npm ci`, `npm run build`); never commit `node_modules`.
- `wp-config.php` ownership unknown: plan separates production config change/checklist from theme/plugin source tasks; verify constants are production-safe before go-live.
- Stored content trust boundary unknown: plan allows limited safe markup via `wp_kses_post`/Timber equivalent, not raw HTML.

## Work Objectives

### Core Objective
Make `wp-content/themes/rspku-theme` and directly coupled RSPKU plugins pass a minimal production readiness gate without changing site design, content model, or API compatibility unless explicitly noted.

### Deliverables
- Fixed production debug/fatal-handler settings or documented ops-safe config gate.
- Reproducible frontend/backend dependency build path using existing `package-lock.json` and `composer.lock`.
- Hardened Twig output for hero titles.
- Hardened REST throttle/client-IP logic for `DoctorSearch` and `rspku-core` public endpoints.
- Visible failure/logging when Vite manifest or entry missing.
- Removed hardcoded local dev URL in footer.
- Safer doctor search frontend fetch handling.
- Verified homepage HTTP response under target threshold after fixes.

### Definition of Done (verifiable conditions with commands)
- From repo root: `php -l wp-config.php` passes.
- From theme dir: `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader` completes or reports already installed cleanly.
- From theme dir: `npm ci` completes from `package-lock.json`.
- From theme dir: `npm run build` completes and `public/build/.vite/manifest.json` exists.
- From repo root: PHP lint passes for theme and RSPKU plugins touched.
- `curl.exe -L --max-time 10 -s -o NUL -w "%{http_code} %{time_total}" http://rspkudev.test/` returns `200` and time `< 2.000000` on local machine after cache warmup, or evidence records exact blocker if local environment prevents timing.
- REST/doctor-search rate-limit tests show spoofed `X-Forwarded-For` cannot bypass limit when remote address is untrusted.

### Must Have
- Keep WordPress/Timber/Twig architecture.
- Keep public REST endpoints public unless task acceptance criteria proves endpoint leaks private data.
- Keep existing route names and JSON top-level shapes stable.
- Keep current visual output materially unchanged.
- Use existing dependencies only.
- Add no framework, gateway, auth system, or monitoring system.

### Must NOT Have
- No UI redesign.
- No CPT/data model rewrite.
- No doctor search rewrite.
- No API versioning project.
- No `node_modules` commit.
- No broad replacement of Twig templates.
- No “security by hiding endpoint” without compatibility check.

## Verification Strategy
> ZERO HUMAN INTERVENTION - all verification is agent-executed.
- Test decision: tests-after + command/browser/curl/PHP self-checks. Existing formal test framework not confirmed; do not add one.
- QA policy: Every task has agent-executed happy + failure scenario.
- Evidence: `.sisyphus/evidence/task-{N}-{slug}.{ext}`.

## Execution Strategy

### Parallel Execution Waves
> Target: 5-8 tasks per wave. <3 per wave (except final) = under-splitting.
> Extract shared dependencies as Wave-1 tasks for max parallelism.

Wave 1: Task 1 config gate, Task 2 build pipeline, Task 3 Twig XSS, Task 4 REST/IP helper design.
Wave 2: Task 5 doctor search hardening, Task 6 core REST hardening, Task 7 manifest/dev URL/frontend fetch fixes.
Wave 3: Task 8 homepage timeout investigation and full ship-gate verification.

### Dependency Matrix (full, all tasks)
- Task 1 blocks Task 8 only.
- Task 2 blocks Task 7 and Task 8.
- Task 3 independent; verified again in Task 8.
- Task 4 blocks Task 5 and Task 6.
- Task 5 depends on Task 4.
- Task 6 depends on Task 4.
- Task 7 depends on Task 2.
- Task 8 depends on Tasks 1-7.

### Agent Dispatch Summary
- Wave 1: 4 tasks -> quick, implementation, security.
- Wave 2: 3 tasks -> implementation, security, visual-engineering.
- Wave 3: 1 task -> testing.

## TODOs
> Implementation + Test = ONE task. Never separate.
> EVERY task MUST have: Agent Profile + Parallelization + QA Scenarios.

- [x] 1. Production Debug Config Gate

  **What to do**: Make production config safe. Inspect `wp-config.php` lines 74, 77, 78, 83. Change production-intended values to: `WP_DEBUG=false`, `WP_DEBUG_DISPLAY=false`, `WP_DEBUG_LOG=false` or non-public safe path, and remove/disable `WP_DISABLE_FATAL_ERROR_HANDLER=true`. If local dev still needs debug, gate with explicit environment constant so production path is safe by default.
  **Must NOT do**: Do not expose debug output. Do not delete DB credentials. Do not change salts/keys. Do not change table prefix.

  **Recommended Agent Profile**:
  - Category: `quick` - Reason: small config-only fix with high production impact.
  - Skills: [] - no extra skill needed.
  - Omitted: [`senior-devops`] - not full infrastructure work.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [8] | Blocked By: []

  **References**:
  - Pattern: `wp-config.php:74` - current `define( 'WP_DEBUG', true );` production blocker.
  - Pattern: `wp-config.php:77` - `WP_DEBUG_DISPLAY=false` already safe for display.
  - Pattern: `wp-config.php:78` - `WP_DEBUG_LOG=true` creates persistent runtime logs.
  - Pattern: `wp-config.php:83` - `WP_DISABLE_FATAL_ERROR_HANDLER=true` disables production recovery.

  **Acceptance Criteria**:
  - [ ] `php -l wp-config.php` returns no syntax errors.
  - [ ] `php -r "require 'wp-load.php'; var_export([WP_DEBUG, defined('WP_DISABLE_FATAL_ERROR_HANDLER') ? WP_DISABLE_FATAL_ERROR_HANDLER : null]);"` shows debug false and fatal handler not disabled for production path.
  - [ ] No DB constants changed from existing working values.

  **QA Scenarios**:
  ```
  Scenario: Production constants safe
    Tool: Bash
    Steps: Run `php -r "require 'wp-load.php'; echo (WP_DEBUG ? 'debug-on' : 'debug-off'), PHP_EOL; echo (defined('WP_DISABLE_FATAL_ERROR_HANDLER') && WP_DISABLE_FATAL_ERROR_HANDLER ? 'fatal-off' : 'fatal-on'), PHP_EOL;"`
    Expected: Output contains `debug-off` and `fatal-on`.
    Evidence: .sisyphus/evidence/task-1-production-debug.txt

  Scenario: Syntax error not introduced
    Tool: Bash
    Steps: Run `php -l wp-config.php`
    Expected: `No syntax errors detected in wp-config.php`.
    Evidence: .sisyphus/evidence/task-1-production-debug-lint.txt
  ```

  **Commit**: YES | Message: `chore(config): harden production debug flags` | Files: [`wp-config.php`]

- [x] 2. Reproducible Theme Build Gate

  **What to do**: Restore and document reproducible build using existing lockfiles. In `wp-content/themes/rspku-theme`, run `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`, `npm ci`, `npm run build`. Do not modify dependencies unless lockfile install proves package metadata is broken. Verify `vite` comes from `devDependencies` at `package.json:15-20` and build writes `public/build/.vite/manifest.json`.
  **Must NOT do**: Do not commit `node_modules`. Do not switch bundler. Do not add packages. Do not delete existing `public/build` unless build regenerates it.

  **Recommended Agent Profile**:
  - Category: `implementation` - Reason: dependency install/build verification may require small metadata fix.
  - Skills: [] - existing npm/composer tools enough.
  - Omitted: [`ci-cd-pipeline-builder`] - no CI pipeline requested.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [7, 8] | Blocked By: []

  **References**:
  - Pattern: `wp-content/themes/rspku-theme/package.json:6-10` - build scripts.
  - Pattern: `wp-content/themes/rspku-theme/package.json:15-20` - `vite` exists in devDependencies.
  - Pattern: `wp-content/themes/rspku-theme/composer.json:6-13` - PHP/Timber requirement and PSR-4 autoload.
  - Audit finding: `npm run build` failed with `vite is not recognized` because `node_modules` absent.

  **Acceptance Criteria**:
  - [ ] `Test-Path wp-content/themes/rspku-theme/node_modules` true after `npm ci` locally; not committed.
  - [ ] `npm run build` exits 0.
  - [ ] `public/build/.vite/manifest.json` exists and includes `resources/js/app.js`.
  - [ ] `git status --short` does not include `node_modules`.

  **QA Scenarios**:
  ```
  Scenario: Clean dependency build
    Tool: Bash
    Steps: Workdir `wp-content/themes/rspku-theme`; run `npm ci`; run `npm run build`.
    Expected: both commands exit 0; Vite emits build summary.
    Evidence: .sisyphus/evidence/task-2-build.txt

  Scenario: Generated manifest usable
    Tool: Bash
    Steps: Workdir repo root; run `php -r "$m=json_decode(file_get_contents('wp-content/themes/rspku-theme/public/build/.vite/manifest.json'), true); echo isset($m['resources/js/app.js']) ? 'ok' : 'missing';"`
    Expected: Output exactly `ok`.
    Evidence: .sisyphus/evidence/task-2-manifest.txt
  ```

  **Commit**: YES | Message: `chore(theme): verify reproducible asset build` | Files: [`wp-content/themes/rspku-theme/package-lock.json`, `wp-content/themes/rspku-theme/public/build/**`] only if changed by build.

- [x] 3. Remove Raw Hero Title XSS Sink

  **What to do**: Replace unsafe raw title rendering with a small allowlist. Target `resources/views/pages/front-page.twig:31` and `resources/views/components/hero.twig:45`. Preferred fix: preprocess allowed hero/title HTML in PHP context using `wp_kses_post()` or a narrower `wp_kses()` allowlist for inline formatting tags (`br`, `strong`, `em`, `span` with safe class if already needed). Twig should render sanitized value without `|raw` unless sanitized HTML is explicitly marked safe by Timber pattern. If no formatting required after visual check, remove `|raw` and rely on escaping.
  **Must NOT do**: Do not allow arbitrary HTML. Do not sanitize with regex. Do not change hero layout/classes. Do not break intentional line breaks if current content uses them.

  **Recommended Agent Profile**:
  - Category: `security` - Reason: stored XSS prevention.
  - Skills: [] - WordPress native escaping/kses enough.
  - Omitted: [`senior-frontend`] - no visual redesign.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [8] | Blocked By: []

  **References**:
  - Pattern: `wp-content/themes/rspku-theme/resources/views/pages/front-page.twig:31` - `{{ rspku.hero_title|raw }}`.
  - Pattern: `wp-content/themes/rspku-theme/resources/views/components/hero.twig:45` - `{{ title|raw }}`.
  - Pattern: `wp-content/themes/rspku-theme/scripts/sync-doctor-profiles-from-tsv.php:361` - existing project uses `wp_kses_post($value)`.

  **Acceptance Criteria**:
  - [ ] No `|raw` remains on hero title fields unless immediately documented as sanitized allowlist output.
  - [ ] Injected test value `<script>alert(1)</script><strong>Aman</strong>` renders no `<script>` in homepage HTML.
  - [ ] Allowed formatting chosen by implementation still renders if required by existing content.

  **QA Scenarios**:
  ```
  Scenario: Safe formatted title renders
    Tool: Bash
    Steps: Temporarily set relevant hero title option/content in local DB to `<strong>RS PKU</strong><br>Yogyakarta`; request homepage with `curl.exe -L http://rspkudev.test/`.
    Expected: HTML contains `RS PKU` and no escaped visible tags if allowlist chosen, or visible plain text if escaping chosen; layout still has one `<h1>`.
    Evidence: .sisyphus/evidence/task-3-hero-safe.html

  Scenario: Script stripped
    Tool: Bash
    Steps: Temporarily set title to `<script>alert(1)</script><strong>Aman</strong>`; request homepage HTML.
    Expected: HTML does not contain `<script>alert(1)</script>`; page returns 200.
    Evidence: .sisyphus/evidence/task-3-hero-xss.html
  ```

  **Commit**: YES | Message: `fix(theme): sanitize hero title output` | Files: [`wp-content/themes/rspku-theme/resources/views/pages/front-page.twig`, `wp-content/themes/rspku-theme/resources/views/components/hero.twig`, PHP context file if needed]

- [x] 4. Shared Trusted Client IP Policy

  **What to do**: Create/centralize minimal trusted client IP resolution for RSPKU rate limits. Default: use `REMOTE_ADDR`. Only trust `HTTP_CF_CONNECTING_IP` or `HTTP_X_FORWARDED_FOR` when current `REMOTE_ADDR` is a trusted proxy/CDN address configured by constant/filter. Apply same policy to theme `DoctorSearch` and plugin `rspku-core` helpers, or duplicate minimal helper in each if cross-plugin dependency would be larger.
  **Must NOT do**: Do not trust `HTTP_X_FORWARDED_FOR` from arbitrary clients. Do not add external IP libraries. Do not require Cloudflare unless configured. Do not break localhost development.

  **Recommended Agent Profile**:
  - Category: `security` - Reason: rate-limit bypass prevention.
  - Skills: [] - PHP stdlib `filter_var` and WordPress filters enough.
  - Omitted: [`senior-backend`] - narrow helper, not API redesign.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [5, 6] | Blocked By: []

  **References**:
  - Pattern: `wp-content/themes/rspku-theme/app/Services/DoctorSearch.php:148-170` - current `clientIp()` trusts proxy headers first.
  - Pattern: `wp-content/plugins/rspku-core/rspku-core.php:1410-1429` - current `client_ip()` trusts proxy headers first.
  - Pattern: `wp-content/themes/rspku-theme/app/Services/DoctorSearch.php:129-146` - transient key based on client IP.
  - Pattern: `wp-content/plugins/rspku-core/rspku-core.php:1376-1400` - transient key based on client IP.

  **Acceptance Criteria**:
  - [ ] With `REMOTE_ADDR=1.1.1.1` and spoofed `HTTP_X_FORWARDED_FOR=8.8.8.8`, helper returns `1.1.1.1` unless trusted proxy config says otherwise.
  - [ ] With localhost/no proxy headers, helper returns valid localhost/remote IP.
  - [ ] Existing rate-limit transient key remains deterministic.

  **QA Scenarios**:
  ```
  Scenario: Spoofed forwarded header ignored
    Tool: Bash
    Steps: Run PHP self-check that sets `$_SERVER['REMOTE_ADDR']='1.1.1.1'` and `$_SERVER['HTTP_X_FORWARDED_FOR']='8.8.8.8'`, invokes/reflects helper or route behavior.
    Expected: effective IP is `1.1.1.1` or rate-limit bucket behaves as same client despite spoofed header.
    Evidence: .sisyphus/evidence/task-4-ip-spoof.txt

  Scenario: Trusted proxy path explicit
    Tool: Bash
    Steps: Configure test trusted proxy via chosen constant/filter; set `REMOTE_ADDR` to that proxy and `HTTP_X_FORWARDED_FOR` to client IP; invoke helper.
    Expected: effective IP is forwarded client only when proxy trusted.
    Evidence: .sisyphus/evidence/task-4-ip-trusted.txt
  ```

  **Commit**: YES | Message: `fix(security): ignore untrusted forwarded IPs` | Files: [`wp-content/themes/rspku-theme/app/Services/DoctorSearch.php`, `wp-content/plugins/rspku-core/rspku-core.php`] or smallest helper files chosen.

- [x] 5. Harden Doctor Search API and Frontend Error Handling

  **What to do**: Apply Task 4 IP policy to `DoctorSearch`. Keep AJAX nonce behavior. Keep public REST route unless product owner later says private. Add explicit `response.ok` handling in `resources/js/app.js:236-248`. For `innerHTML`, confirm server HTML from `DoctorSearch::renderResults()` is escaped; if not, escape at template/server before sending. Add user-facing error message for HTTP 403/429/500 while preserving current Indonesian text tone.
  **Must NOT do**: Do not rewrite search UX. Do not switch to client-side templating. Do not remove pagination. Do not change endpoint names.

  **Recommended Agent Profile**:
  - Category: `implementation` - Reason: PHP + JS narrow fix.
  - Skills: [] - existing code enough.
  - Omitted: [`frontend-design`] - no design work.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [8] | Blocked By: [4]

  **References**:
  - Pattern: `wp-content/themes/rspku-theme/app/Services/DoctorSearch.php:21-45` - AJAX endpoint with nonce.
  - Pattern: `wp-content/themes/rspku-theme/app/Services/DoctorSearch.php:47-74` - public REST route.
  - Pattern: `wp-content/themes/rspku-theme/app/Services/DoctorSearch.php:123-146` - rate limit.
  - Pattern: `wp-content/themes/rspku-theme/resources/js/app.js:236-248` - fetch/json/innerHTML flow.

  **Acceptance Criteria**:
  - [ ] AJAX route still returns success HTML for valid nonce.
  - [ ] Invalid nonce returns 403 JSON error and UI shows graceful error.
  - [ ] Rate-limited request returns 429 and UI shows graceful error.
  - [ ] Spoofing `X-Forwarded-For` does not bypass rate limit.
  - [ ] No new raw user input reaches `innerHTML` without server escaping.

  **QA Scenarios**:
  ```
  Scenario: Valid doctor search works
    Tool: Playwright
    Steps: Open `http://rspkudev.test/dokter/`; search known doctor/specialization; click pagination if visible.
    Expected: Results update, no console error, network request 200.
    Evidence: .sisyphus/evidence/task-5-doctor-search.png

  Scenario: Error path visible
    Tool: Bash
    Steps: POST to admin-ajax endpoint with missing/invalid nonce and action `rspku_doctor_search`.
    Expected: HTTP 403 JSON error; frontend-equivalent message is graceful and no uncaught JS exception appears in browser test.
    Evidence: .sisyphus/evidence/task-5-doctor-search-error.txt
  ```

  **Commit**: YES | Message: `fix(search): harden doctor search requests` | Files: [`wp-content/themes/rspku-theme/app/Services/DoctorSearch.php`, `wp-content/themes/rspku-theme/resources/js/app.js`, related templates if escaping needed]

- [x] 6. Harden Public RSPKU Core REST Endpoints

  **What to do**: Keep `rspku-core` endpoints public but production-safe. Apply Task 4 IP policy to `rspku-core` `client_ip()`. Ensure public collection/search endpoints enforce reasonable `per_page` caps and rate limits using existing `enforce_rate_limit()`. Add cache headers or WordPress transient/object cache only where response is public and not user-specific. Verify response schemas for `/site`, `/home`, `/menu/{slug}`, `/search`, collections, and single-by-slug do not expose private/admin fields.
  **Must NOT do**: Do not add auth to public read endpoints blindly. Do not change route names. Do not remove fields without recording compatibility note in acceptance evidence. Do not expose ACF unless existing filters explicitly opt in.

  **Recommended Agent Profile**:
  - Category: `security` - Reason: public API hardening.
  - Skills: [`senior-backend`] - REST/data exposure judgment.
  - Omitted: [`api-design-reviewer`] - no API redesign/versioning.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [8] | Blocked By: [4]

  **References**:
  - Pattern: `wp-content/plugins/rspku-core/rspku-core.php:134-176` - public `/site`, `/home`, `/menu`, `/search` routes.
  - Pattern: `wp-content/plugins/rspku-core/rspku-core.php:219-240` - public collection/single routes.
  - Pattern: `wp-content/plugins/rspku-core/rspku-core.php:251-285` - collection args include `per_page` default 20.
  - Pattern: `wp-content/plugins/rspku-core/rspku-core.php:1376-1400` - existing rate-limit helper.
  - Pattern: `wp-content/plugins/rspku-core/rspku-core.php:87-89` - ACF exposure only by explicit filters.

  **Acceptance Criteria**:
  - [ ] Public endpoints still return 200 for normal anonymous GET.
  - [ ] Search/collection endpoints reject or clamp abusive `per_page` values.
  - [ ] Rate limit returns HTTP 429 with `Retry-After` after threshold.
  - [ ] Spoofed `X-Forwarded-For` cannot bypass rate limit.
  - [ ] Evidence file lists endpoint fields checked and confirms no private fields exposed.

  **QA Scenarios**:
  ```
  Scenario: Anonymous public API still works
    Tool: Bash
    Steps: Curl `/wp-json/rspku/v1/site`, `/home`, `/search?q=dokter`, and one collection endpoint.
    Expected: HTTP 200; JSON valid; no PHP warnings in response.
    Evidence: .sisyphus/evidence/task-6-rest-public.txt

  Scenario: Abusive API request controlled
    Tool: Bash
    Steps: Repeatedly call `/wp-json/rspku/v1/search?q=a&per_page=9999` with spoofed `X-Forwarded-For` variations.
    Expected: per_page clamped/rejected; rate limit eventually returns 429 based on real/trusted IP, not spoofed header.
    Evidence: .sisyphus/evidence/task-6-rest-abuse.txt
  ```

  **Commit**: YES | Message: `fix(api): harden public REST throttling` | Files: [`wp-content/plugins/rspku-core/rspku-core.php`]

- [x] 7. Asset Manifest, Dev URL, and Frontend Production Polish

  **What to do**: Fix three medium risks in smallest diff. In `app/Setup/Assets.php:158-176`, make missing/invalid manifest or missing entry visible in production via `error_log()` and admin notice for admins, while avoiding fatal on public page if possible; if theme cannot render without assets, fail closed only in admin/deploy check, not for visitors. In `resources/views/layouts/base.twig:312`, remove hardcoded `https://e-career.rspkudev.test/`; use `rspku` setting if available, else safe relative/current-site URL or omit link if no production URL exists. In `resources/js/app.js`, keep Task 5 `response.ok` fix if not already included.
  **Must NOT do**: Do not redesign footer. Do not introduce new settings UI unless existing `rspku-settings` already has suitable field pattern and implementing it is smaller than omission/fallback. Do not make public page white-screen only because manifest missing.

  **Recommended Agent Profile**:
  - Category: `implementation` - Reason: PHP/Twig/JS small hardening.
  - Skills: [] - existing code enough.
  - Omitted: [`ui-ux-pro-max`] - no UX redesign.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [8] | Blocked By: [2]

  **References**:
  - Pattern: `wp-content/themes/rspku-theme/app/Setup/Assets.php:158-176` - silent manifest failure.
  - Pattern: `wp-content/themes/rspku-theme/resources/views/layouts/base.twig:312` - hardcoded `https://e-career.rspkudev.test/`.
  - Pattern: `wp-content/themes/rspku-theme/resources/views/layouts/base.twig:290-293` - existing settings-driven footer quick links.
  - Pattern: `wp-content/themes/rspku-theme/resources/js/app.js:236-248` - fetch handling.

  **Acceptance Criteria**:
  - [ ] No `rspkudev.test` URL remains in theme templates/assets except local-only comments/docs.
  - [ ] Missing manifest produces actionable log/admin notice for admin users.
  - [ ] Normal homepage still enqueues built CSS/JS when manifest exists.
  - [ ] Browser console has no uncaught error on doctor search failure path.

  **QA Scenarios**:
  ```
  Scenario: Production footer has no dev URL
    Tool: Bash
    Steps: `curl.exe -L http://rspkudev.test/` and grep output for `rspkudev.test` in external hrefs.
    Expected: No `https://e-career.rspkudev.test/` appears.
    Evidence: .sisyphus/evidence/task-7-dev-url.html

  Scenario: Manifest missing signal
    Tool: Bash
    Steps: Temporarily rename `public/build/.vite/manifest.json`, load admin page as admin or invoke enqueue path; restore file.
    Expected: Admin/log signal names missing manifest path; no permanent source change from temporary rename.
    Evidence: .sisyphus/evidence/task-7-manifest-missing.txt
  ```

  **Commit**: YES | Message: `fix(theme): surface asset build failures` | Files: [`wp-content/themes/rspku-theme/app/Setup/Assets.php`, `wp-content/themes/rspku-theme/resources/views/layouts/base.twig`, `wp-content/themes/rspku-theme/resources/js/app.js` if not covered by Task 5]

- [x] 8. Homepage Timeout Root Cause and Ship-Gate Verification

  **What to do**: After Tasks 1-7, investigate and prove homepage web request health. Re-run curl timing, inspect `wp-content/debug.log`, web server/PHP logs if accessible, and isolate slow component by temporarily disabling non-essential plugins only in local test if needed, restoring immediately. Do not make speculative code changes; only fix if exact root cause is tied to theme/plugin changes from this plan. Produce final production readiness evidence.
  **Must NOT do**: Do not disable plugins permanently. Do not delete cache/data. Do not tune server globally without recording command and rollback.

  **Recommended Agent Profile**:
  - Category: `testing` - Reason: runtime validation across browser/curl/logs.
  - Skills: [`ship-gate`] - production readiness checks.
  - Omitted: [`performance-profiler`] - only needed if timeout persists after basic isolation.

  **Parallelization**: Can Parallel: NO | Wave 3 | Blocks: [F1, F2, F3, F4] | Blocked By: [1, 2, 3, 5, 6, 7]

  **References**:
  - Audit finding: `curl.exe -L --max-time 30 ... http://rspkudev.test/` returned `000 30.002452 0` once.
  - Audit finding: `php -d memory_limit=256M -r "require 'wp-load.php'; echo wp_get_theme()->get('Name'), PHP_EOL;"` returned theme name, so CLI bootstrap works.
  - Pattern: `wp-content/debug.log` had ACF deprecation warnings but no fatal/parse errors in audited tail.

  **Acceptance Criteria**:
  - [ ] `curl.exe -L --max-time 10 -s -o NUL -w "%{http_code} %{time_total}" http://rspkudev.test/` returns `200` under 2 seconds after warmup.
  - [ ] Playwright loads homepage, doctor search page, and one article/page without console fatal errors.
  - [ ] Latest `wp-content/debug.log` tail after tests has no new fatal errors.
  - [ ] Build/lint/config/API evidence files exist for all tasks.

  **QA Scenarios**:
  ```
  Scenario: Homepage fast enough
    Tool: Bash
    Steps: Run curl timing twice; use second run as warm-cache measurement.
    Expected: HTTP 200 and second `time_total` < 2.000000.
    Evidence: .sisyphus/evidence/task-8-homepage-curl.txt

  Scenario: Browser smoke test
    Tool: Playwright
    Steps: Open homepage, `/dokter/`, and one public content URL discovered from sitemap/menu; capture console errors and screenshot.
    Expected: Pages render; no uncaught JS errors; no PHP error text in HTML.
    Evidence: .sisyphus/evidence/task-8-browser-smoke.png
  ```

  **Commit**: YES | Message: `test(theme): verify production readiness gate` | Files: [`.sisyphus/evidence/**`] if evidence committed by workflow; otherwise NO source commit.

## Final Verification Wave (MANDATORY — after ALL implementation tasks)
> 4 review agents run in PARALLEL. ALL must APPROVE. Present consolidated results to user and get explicit "okay" before completing.
> **Do NOT auto-proceed after verification. Wait for user's explicit approval before marking work complete.**
> **Never mark F1-F4 as checked before getting user's okay.** Rejection or user feedback -> fix -> re-run -> present again -> wait for okay.
- [x] F1. Plan Compliance Audit — oracle
- [x] F2. Code Quality Review — unspecified-high
- [x] F3. Real Manual QA — unspecified-high (+ playwright if UI)
- [x] F4. Scope Fidelity Check — deep

## Commit Strategy
- Commit per task when acceptance criteria pass.
- Use small Conventional Commit messages listed per task.
- Do not stage SQL dumps, `node_modules`, logs, local-only env files, or unrelated `.sisyphus/run-continuation` files.
- Before each commit: inspect `git status --short` and `git diff --stat`.

## Success Criteria
- All 8 tasks checked with evidence files.
- Final verification F1-F4 all approve.
- User explicitly says okay after verification summary.
- Production blockers from audit are either fixed or documented as ops-owned with exact command/check.
