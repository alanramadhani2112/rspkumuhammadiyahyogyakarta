# RSPKU Dev Testing Plan

## TL;DR
> **Summary**: Jalankan testing berlapis untuk RSPKU dev: environment/DB, build/lint, REST/AJAX, UI browser smoke, security regressions, dan performance isolation.
> **Deliverables**:
> - Evidence folder `.sisyphus/evidence/rspku-testing/` berisi output command, HTML snapshots, browser console logs, dan performance matrix.
> - Test matrix untuk route utama, REST endpoints, doctor search, settings/admin CSS, build, lint, dan DB readiness.
> - Performance diagnosis yang memisahkan RSPKU source vs plugin pihak ketiga.
> **Effort**: Medium
> **Parallel**: YES - 3 waves
> **Critical Path**: T1 -> T2 -> T5 -> T8 -> Final Verification Wave

## Context

### Original Request
- User: “Coba kamu susun testing plan untuk rspku dev ini”

### Current Project Facts
- Local URL: `http://rspkudev.test/`
- WordPress root: `C:\laragon\www\rspkudev`
- Theme: `wp-content/themes/rspku-theme`
- RSPKU plugins: `wp-content/plugins/rspku-core`, `rspku-settings`, `rspku-schema`, `rspku-cpt`
- CI: `.github/workflows/ci.yml` runs Composer/PHP lint/PHPStan and theme `npm ci && npm run build`.
- Local hook docs: `.github/hooks/pre-commit`, `.github/hooks/README.md`.
- Existing JS test: `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`.
- No project-level Jest/Vitest/Playwright/Cypress config found.
- Known blocker from prior readiness work: homepage no timeout, but warm-cache with all plugins around `2.95s`; RSPKU-only/no-plugin isolation under `2s`.

### Metis Review (gaps addressed)
- Performance metric must be explicit: use logged-out HTTP document timing from `curl`, warm-cache second request; browser smoke is separate.
- Plugin isolation must restore plugin state after every probe.
- No new testing frameworks in first plan.
- Evidence must be raw and reproducible, not only summary text.
- Speed failure from non-RSPKU plugin is “external blocker documented”, not hidden pass.

## Work Objectives

### Core Objective
Produce agent-executable QA coverage for local RSPKU dev without adding new frameworks or changing source code.

### Deliverables
- `.sisyphus/evidence/rspku-testing/00-environment.txt`
- `.sisyphus/evidence/rspku-testing/01-build-lint.txt`
- `.sisyphus/evidence/rspku-testing/02-db-wordpress.txt`
- `.sisyphus/evidence/rspku-testing/03-rest-api.txt`
- `.sisyphus/evidence/rspku-testing/04-doctor-search.txt`
- `.sisyphus/evidence/rspku-testing/05-browser-smoke.txt`
- `.sisyphus/evidence/rspku-testing/06-admin-settings-css.txt`
- `.sisyphus/evidence/rspku-testing/07-security-regression.txt`
- `.sisyphus/evidence/rspku-testing/08-performance-matrix.txt`
- `.sisyphus/evidence/rspku-testing/final-summary.txt`

### Definition of Done
- All commands executed from `C:\laragon\www\rspkudev` unless task says otherwise.
- All evidence files exist and include command, timestamp, result, and pass/fail.
- Browser routes load with HTTP 200 and no console fatal/error.
- REST/AJAX endpoints pass happy and failure cases.
- Debug log tail has no new Fatal/Parse errors after tests.
- Performance blocker, if still present, is attributed by plugin isolation matrix.

### Must Have
- No source code edits during testing execution.
- No permanent plugin disabling.
- No DB mutation unless explicit export/restore command is included.
- Use existing stdlib/native tools first: `curl.exe`, `php`, `mysql.exe`, `npm`, `composer`, browser Playwright if available.

### Must NOT Have
- No new Jest/Vitest/Playwright/Cypress install.
- No redesign, refactor, CI rewrite, or performance fix inside testing task.
- No staging/commit.
- No claiming production-ready if speed blocker remains unresolved.

## Verification Strategy
> ZERO HUMAN INTERVENTION - all verification is agent-executed.
- Test decision: tests-after / QA-first using existing tooling; no new framework.
- QA policy: every task writes raw evidence under `.sisyphus/evidence/rspku-testing/`.
- Failure policy: failures become explicit blockers in final summary; if out-of-scope, record exact owner/next isolation step.

## Execution Strategy

### Parallel Execution Waves
- Wave 1: environment, build/lint, DB/WordPress bootstrap.
- Wave 2: REST/API, doctor search, browser smoke, admin CSS.
- Wave 3: security regression, performance matrix, final summary.

### Dependency Matrix
- T1 blocks all browser/API tests.
- T2 blocks asset/UI tests.
- T3 blocks REST/AJAX tests.
- T4 and T5 can run after T1/T3.
- T8 performance matrix runs after T1-T5.
- T9 final summary runs after T1-T8.

### Agent Dispatch Summary
- Wave 1: 3 tasks, categories `testing`, `implementation` (read-only build), `testing`.
- Wave 2: 4 tasks, categories `testing`, `security`, `visual-engineering`.
- Wave 3: 2 tasks, categories `security`, `performance-profiler`/`testing`, then `review`.

## TODOs

- [x] 1. Environment and Service Readiness

  **What to do**: Verify Laragon runtime services and local URL before deeper QA.
  **Must NOT do**: Do not restart services unless user explicitly approves; just report status.

  **Recommended Agent Profile**:
  - Category: `testing` - Reason: runtime readiness checks.
  - Skills: [] - native commands enough.
  - Omitted: [`senior-devops`] - no infra changes.

  **Parallelization**: Can Parallel: NO | Wave 1 | Blocks: [2,3,4,5,8] | Blocked By: []

  **References**:
  - Runtime: `http://rspkudev.test/`
  - Config: `wp-config.php:23-29` - DB constants.
  - Prior issue: browser showed `ERR_CONNECTION_REFUSED` when Laragon not started.

  **Acceptance Criteria**:
  - [ ] `curl.exe -L --max-time 10 -s -o NUL -w "%{http_code} %{time_total} %{errormsg}" http://rspkudev.test/` returns HTTP 200.
  - [ ] `Test-NetConnection -ComputerName 127.0.0.1 -Port 3306` returns `TcpTestSucceeded : True`.
  - [ ] MySQL process and Apache/Nginx process status recorded.
  - [ ] Evidence written to `.sisyphus/evidence/rspku-testing/00-environment.txt`.

  **QA Scenarios**:
  ```
  Scenario: Local services are available
    Tool: Bash
    Steps: Run curl, Test-NetConnection 3306, Get-Process for httpd/nginx/mysqld/laragon
    Expected: HTTP 200, DB port true, web and DB processes present
    Evidence: .sisyphus/evidence/rspku-testing/00-environment.txt

  Scenario: Browser DB error suspected
    Tool: Bash
    Steps: Compare curl body for `Database Connection Error` vs `RS PKU`
    Expected: Site HTML contains `RS PKU` and not DB error
    Evidence: .sisyphus/evidence/rspku-testing/00-environment.txt
  ```

  **Commit**: NO | Message: n/a | Files: [`.sisyphus/evidence/rspku-testing/00-environment.txt`]

- [x] 2. Build, Lint, and Static QA Gate

  **What to do**: Run current build/lint gates matching CI where local tools exist.
  **Must NOT do**: Do not upgrade packages; do not modify package files; do not commit `node_modules`.

  **Recommended Agent Profile**:
  - Category: `testing` - Reason: build/lint execution and evidence.
  - Skills: [`ship-gate`] - production readiness QA framing.
  - Omitted: [`ci-cd-pipeline-builder`] - CI already exists; no pipeline rewrite.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [5] | Blocked By: [1]

  **References**:
  - CI: `.github/workflows/ci.yml:54-68` - PHP lint/PHPStan.
  - CI: `.github/workflows/ci.yml:70-90` - Node 20 build.
  - Theme scripts: `wp-content/themes/rspku-theme/package.json:6-10`.

  **Acceptance Criteria**:
  - [ ] `php -l` passes for changed/critical PHP files in theme and RSPKU plugins.
  - [ ] `composer install --prefer-dist --no-progress --no-interaction` in theme succeeds or prior vendor state is documented.
  - [ ] `npm ci` and `npm run build` in theme succeed.
  - [ ] `git status --short` does not show `node_modules`.
  - [ ] Evidence written to `.sisyphus/evidence/rspku-testing/01-build-lint.txt`.

  **QA Scenarios**:
  ```
  Scenario: Theme build is reproducible
    Tool: Bash
    Steps: cd wp-content/themes/rspku-theme; run npm ci; run npm run build; check public/build/.vite/manifest.json
    Expected: exit 0 and manifest contains resources/js/app.js
    Evidence: .sisyphus/evidence/rspku-testing/01-build-lint.txt

  Scenario: PHP syntax stays clean
    Tool: Bash
    Steps: run php -l over wp-content/themes/rspku-theme/app, functions.php, wp-content/plugins/rspku-*
    Expected: no syntax errors
    Evidence: .sisyphus/evidence/rspku-testing/01-build-lint.txt
  ```

  **Commit**: NO | Message: n/a | Files: [`.sisyphus/evidence/rspku-testing/01-build-lint.txt`]

- [x] 3. DB and WordPress Bootstrap Gate

  **What to do**: Verify database connectivity, table content, and WordPress bootstrap.
  **Must NOT do**: Do not import SQL or mutate DB during testing.

  **Recommended Agent Profile**:
  - Category: `testing` - Reason: DB/runtime verification.
  - Skills: [`db-verifier`] - if available for DB checks.
  - Omitted: [`database-designer`] - no schema design.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [4,5] | Blocked By: [1]

  **References**:
  - DB config: `wp-config.php:23-29`.
  - Table prefix: `wp-config.php:61`.
  - Prior restored DB: `lyxpx_options` had hundreds of rows after import.

  **Acceptance Criteria**:
  - [ ] MySQL login using `DB_USER` succeeds.
  - [ ] `lyxpx_options` count > 0.
  - [ ] `home` and `siteurl` equal `http://rspkudev.test`.
  - [ ] `php -r "require 'wp-load.php'; echo get_option('home'), PHP_EOL, get_bloginfo('name'), PHP_EOL;"` succeeds.
  - [ ] Evidence written to `.sisyphus/evidence/rspku-testing/02-db-wordpress.txt`.

  **QA Scenarios**:
  ```
  Scenario: DB credentials work
    Tool: Bash
    Steps: mysql.exe query DATABASE(), lyxpx_options count, home/siteurl
    Expected: database db-rspkujogja, count > 0, URLs match local host
    Evidence: .sisyphus/evidence/rspku-testing/02-db-wordpress.txt

  Scenario: WordPress bootstrap works
    Tool: Bash
    Steps: php -r require wp-load.php and echo home/site name
    Expected: http://rspkudev.test and RS PKU Muhammadiyah Yogyakarta
    Evidence: .sisyphus/evidence/rspku-testing/02-db-wordpress.txt
  ```

  **Commit**: NO | Message: n/a | Files: [`.sisyphus/evidence/rspku-testing/02-db-wordpress.txt`]

- [x] 4. Public REST API Regression Matrix

  **What to do**: Test public RSPKU REST routes and failure/abuse behavior.
  **Must NOT do**: Do not add auth; endpoints are public by existing plan.

  **Recommended Agent Profile**:
  - Category: `testing` - Reason: API smoke and abuse checks.
  - Skills: [`api-test-suite-builder`] - only for matrix thinking; no framework generation.
  - Omitted: [`senior-backend`] - no backend edits.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [9] | Blocked By: [1,3]

  **References**:
  - REST registrations: `wp-content/plugins/rspku-core/rspku-core.php:138`, `:148`, `:158`, `:174`, `:223`, `:236`.
  - Prior evidence: `.sisyphus/evidence/task-6-core-rest.txt`.

  **Acceptance Criteria**:
  - [ ] `/wp-json/rspku/v1/site` returns HTTP 200 and expected public JSON fields.
  - [ ] `/wp-json/rspku/v1/home` returns HTTP 200.
  - [ ] `/wp-json/rspku/v1/search?q=rs&per_page=999` clamps result count/per_page to 50.
  - [ ] Collection endpoint `/wp-json/rspku/v1/posts?per_page=999` returns <= 50 items.
  - [ ] Rate-limit poison test returns HTTP 429 and cleanup restores normal behavior.
  - [ ] Evidence written to `.sisyphus/evidence/rspku-testing/03-rest-api.txt`.

  **QA Scenarios**:
  ```
  Scenario: Public endpoints remain public
    Tool: Bash
    Steps: curl /site, /home, /search, /posts as anonymous
    Expected: HTTP 200 for normal requests; response shape documented
    Evidence: .sisyphus/evidence/rspku-testing/03-rest-api.txt

  Scenario: REST abuse path throttles
    Tool: Bash
    Steps: Set exact transient for current REMOTE_ADDR search/collection bucket, curl endpoint with spoofed X-Forwarded-For, delete transient
    Expected: HTTP 429 with Retry-After; spoofed forwarded IP ignored
    Evidence: .sisyphus/evidence/rspku-testing/03-rest-api.txt
  ```

  **Commit**: NO | Message: n/a | Files: [`.sisyphus/evidence/rspku-testing/03-rest-api.txt`]

- [x] 5. Doctor Search AJAX and UI Regression

  **What to do**: Test doctor search happy path, invalid nonce, rate-limit, and frontend error rendering.
  **Must NOT do**: Do not rewrite doctor search or change DB.

  **Recommended Agent Profile**:
  - Category: `testing` - Reason: AJAX + UI behavior.
  - Skills: [`playwright`, `senior-qa`] - browser and error-path validation.
  - Omitted: [`senior-frontend`] - no UI implementation.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [9] | Blocked By: [1,2,3]

  **References**:
  - JS: `wp-content/themes/rspku-theme/resources/js/app.js:154` doctorSearch component.
  - Fetch: `wp-content/themes/rspku-theme/resources/js/app.js:236`.
  - Safe error rendering: `wp-content/themes/rspku-theme/resources/js/app.js:253-263`.
  - Server: `wp-content/themes/rspku-theme/app/Services/DoctorSearch.php`.

  **Acceptance Criteria**:
  - [ ] Valid doctor search returns HTTP 200 JSON success and escaped HTML.
  - [ ] Invalid nonce returns HTTP 403 JSON message `Sesi pencarian tidak valid.`.
  - [ ] Rate-limit path returns HTTP 429 with JSON error.
  - [ ] Browser UI displays error through text, not raw HTML execution.
  - [ ] Evidence written to `.sisyphus/evidence/rspku-testing/04-doctor-search.txt`.

  **QA Scenarios**:
  ```
  Scenario: Doctor search happy path
    Tool: Bash + Playwright
    Steps: Generate nonce from WP, POST to admin-ajax.php; load /dokter/ and perform search if selector exists
    Expected: HTTP 200 success JSON; browser results render; console error count 0
    Evidence: .sisyphus/evidence/rspku-testing/04-doctor-search.txt

  Scenario: Doctor search failure path
    Tool: Bash + Playwright
    Steps: POST invalid nonce and poisoned rate-limit transient; verify frontend shows message
    Expected: 403/429; no script execution; no uncaught JS error
    Evidence: .sisyphus/evidence/rspku-testing/04-doctor-search.txt
  ```

  **Commit**: NO | Message: n/a | Files: [`.sisyphus/evidence/rspku-testing/04-doctor-search.txt`]

- [x] 6. Browser Smoke and Content Route Matrix

  **What to do**: Load key public routes in browser and assert visible content/console health.
  **Must NOT do**: Do not accept HTTP 200 alone; inspect title/content and console.

  **Recommended Agent Profile**:
  - Category: `testing` - Reason: browser QA.
  - Skills: [`playwright`] - required browser checks.
  - Omitted: [`ui-ux-pro-max`] - no visual redesign.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [9] | Blocked By: [1,2]

  **References**:
  - Layout: `wp-content/themes/rspku-theme/resources/views/layouts/base.twig`.
  - Front page: `wp-content/themes/rspku-theme/resources/views/pages/front-page.twig`.
  - Previous smoke: `/`, `/dokter/`, `/berita-artikel/` loaded 200 and console fatal/error count 0.

  **Acceptance Criteria**:
  - [ ] `/` loads HTTP 200, title contains `RS PKU`, no DB error page.
  - [ ] `/dokter/` loads HTTP 200 and doctor/search UI is present or absence documented.
  - [ ] `/berita-artikel/` loads HTTP 200.
  - [ ] One single article/page route loads HTTP 200 if discoverable from links.
  - [ ] Console error/fatal count is 0.
  - [ ] Evidence written to `.sisyphus/evidence/rspku-testing/05-browser-smoke.txt` and screenshot if browser tool supports it.

  **QA Scenarios**:
  ```
  Scenario: Main public routes load
    Tool: Playwright
    Steps: Navigate /, /dokter/, /berita-artikel/, one discovered content URL; record title and status
    Expected: HTTP 200, expected title/content, no PHP error text
    Evidence: .sisyphus/evidence/rspku-testing/05-browser-smoke.txt

  Scenario: Console remains clean
    Tool: Playwright
    Steps: Capture console messages after route visits
    Expected: fatal/error count 0
    Evidence: .sisyphus/evidence/rspku-testing/05-browser-smoke.txt
  ```

  **Commit**: NO | Message: n/a | Files: [`.sisyphus/evidence/rspku-testing/05-browser-smoke.txt`]

- [x] 7. Admin Settings CSS and Settings Save Smoke

  **What to do**: Run existing admin CSS test and smoke settings admin only if authenticated session/tooling exists.
  **Must NOT do**: Do not change settings values unless exported/restored or run dry-only checks.

  **Recommended Agent Profile**:
  - Category: `testing` - Reason: existing test execution and admin smoke.
  - Skills: [`senior-qa`] - test evidence discipline.
  - Omitted: [`frontend-design`] - no UI design changes.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [9] | Blocked By: [2]

  **References**:
  - Existing test: `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`.
  - Settings admin save has capability/nonce from prior audit.

  **Acceptance Criteria**:
  - [ ] `node tests/admin-css.test.mjs` from `wp-content/plugins/rspku-settings` exits 0 or missing dependency is documented.
  - [ ] Admin CSS file parses and required selectors/tokens pass.
  - [ ] If authenticated admin browser session exists, settings page loads without console fatal/error; otherwise mark admin browser smoke blocked by auth.
  - [ ] Evidence written to `.sisyphus/evidence/rspku-testing/06-admin-settings-css.txt`.

  **QA Scenarios**:
  ```
  Scenario: Existing admin CSS test passes
    Tool: Bash
    Steps: cd wp-content/plugins/rspku-settings; node tests/admin-css.test.mjs
    Expected: exit 0; selectors/tokens pass
    Evidence: .sisyphus/evidence/rspku-testing/06-admin-settings-css.txt

  Scenario: Admin page availability
    Tool: Playwright
    Steps: If logged-in admin exists, visit settings page and capture console; else record blocked by auth
    Expected: page loads or auth blocker documented, no source change
    Evidence: .sisyphus/evidence/rspku-testing/06-admin-settings-css.txt
  ```

  **Commit**: NO | Message: n/a | Files: [`.sisyphus/evidence/rspku-testing/06-admin-settings-css.txt`]

- [x] 8. Security Regression Gate

  **What to do**: Re-check critical regressions from production-readiness work.
  **Must NOT do**: Do not run destructive scanners or change credentials.

  **Recommended Agent Profile**:
  - Category: `security` - Reason: XSS, dev URL, debug, rate-limit, exposed errors.
  - Skills: [`senior-security`, `ship-gate`] - focused security QA.
  - Omitted: [`security-pen-testing`] - no broad pentest.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: [9] | Blocked By: [2,4,5]

  **References**:
  - Debug constants: `wp-config.php:74-83`.
  - Hero title escape: `front-page.twig` and `hero.twig` use `wp_kses_post`.
  - Dev URL fix: `base.twig` uses `{{ site.url }}/karir/`.
  - Prior evidence: `.sisyphus/evidence/task-3-hero-checks.txt`, `task-4-*`, `task-6-core-rest.txt`.

  **Acceptance Criteria**:
  - [ ] `WP_DEBUG=false`, `WP_DEBUG_DISPLAY=false`, `WP_DEBUG_LOG=false`, `WP_DISABLE_FATAL_ERROR_HANDLER=false` verified.
  - [ ] No `rspkudev.test` in theme templates/assets except docs/comments.
  - [ ] No target hero title `|raw` sinks remain.
  - [ ] Spoofed `X-Forwarded-For` ignored when proxy untrusted.
  - [ ] Evidence written to `.sisyphus/evidence/rspku-testing/07-security-regression.txt`.

  **QA Scenarios**:
  ```
  Scenario: Static security regressions remain fixed
    Tool: Grep + Bash
    Steps: grep debug constants, rspkudev.test, hero title raw sinks; run bootstrap check
    Expected: production-safe constants; no dev URL in theme; hero title uses wp_kses_post
    Evidence: .sisyphus/evidence/rspku-testing/07-security-regression.txt

  Scenario: Rate-limit IP spoof stays blocked
    Tool: Bash
    Steps: poison current REMOTE_ADDR transient and curl with fake X-Forwarded-For
    Expected: 429 still keyed to REMOTE_ADDR
    Evidence: .sisyphus/evidence/rspku-testing/07-security-regression.txt
  ```

  **Commit**: NO | Message: n/a | Files: [`.sisyphus/evidence/rspku-testing/07-security-regression.txt`]

- [x] 9. Performance Matrix and Third-Party Plugin Isolation

  **What to do**: Measure explicit performance matrix and isolate plugin/runtime blocker without permanent state changes.
  **Must NOT do**: Do not leave plugins disabled; do not optimize/fix plugins in this task.

  **Recommended Agent Profile**:
  - Category: `testing` - Reason: controlled performance evidence.
  - Skills: [`performance-profiler`, `ship-gate`] - timing and release gate framing.
  - Omitted: [`migration-architect`] - no infrastructure migration.

  **Parallelization**: Can Parallel: NO | Wave 3 | Blocks: [10] | Blocked By: [1,6]

  **References**:
  - Known issue: `.sisyphus/notepads/production-readiness-rspku-theme/issues.md:8-11`.
  - Prior performance evidence: `.sisyphus/evidence/task-8-homepage-curl.txt`.

  **Acceptance Criteria**:
  - [ ] Define timing metric in evidence: logged-out HTTP document `curl` `time_total`, warm-cache second request.
  - [ ] Measure `/`, `/dokter/`, `/berita-artikel/`, `/wp-json/rspku/v1/site`, `/wp-json/rspku/v1/home`.
  - [ ] Record all-plugins baseline.
  - [ ] Record RSPKU-only baseline using temporary mu-plugin or WP-CLI, then restore state.
  - [ ] Record no-plugin baseline if safe locally, then restore state.
  - [ ] If all-plugins >2s and RSPKU-only <2s, list next plugin group isolation target; classify as external blocker.
  - [ ] Evidence written to `.sisyphus/evidence/rspku-testing/08-performance-matrix.txt`.

  **QA Scenarios**:
  ```
  Scenario: Runtime timing matrix
    Tool: Bash
    Steps: warm each URL once, record second curl time_total; repeat for key URLs
    Expected: exact timings recorded; failures classified
    Evidence: .sisyphus/evidence/rspku-testing/08-performance-matrix.txt

  Scenario: Plugin isolation restores state
    Tool: Bash
    Steps: capture active plugin list before isolation; run temporary local-only isolation; verify original plugin list restored
    Expected: restored list equals original; no persistent plugin state change
    Evidence: .sisyphus/evidence/rspku-testing/08-performance-matrix.txt
  ```

  **Commit**: NO | Message: n/a | Files: [`.sisyphus/evidence/rspku-testing/08-performance-matrix.txt`]

- [x] 10. Final Testing Report and Release Decision

  **What to do**: Consolidate all test evidence into one pass/fail summary with next actions.
  **Must NOT do**: Do not hide failures; do not mark go-live ready if performance blocker remains.

  **Recommended Agent Profile**:
  - Category: `review` - Reason: synthesize QA evidence.
  - Skills: [`ship-gate`, `code-reviewer`] - release gate framing.
  - Omitted: [`release-manager`] - no release execution.

  **Parallelization**: Can Parallel: NO | Wave 3 | Blocks: [] | Blocked By: [1,2,3,4,5,6,7,8,9]

  **References**:
  - All `.sisyphus/evidence/rspku-testing/*.txt`.
  - This plan: `.sisyphus/plans/rspku-testing-plan.md`.

  **Acceptance Criteria**:
  - [ ] Final summary lists PASS/FAIL/BLOCKED for T1-T9.
  - [ ] Any failure includes exact command, observed output, and owner.
  - [ ] Go-live recommendation uses one of: `READY_FOR_LOCAL_QA`, `BLOCKED_BY_PERFORMANCE`, `BLOCKED_BY_RUNTIME`, `BLOCKED_BY_SECURITY`, `BLOCKED_BY_BUILD`.
  - [ ] Evidence written to `.sisyphus/evidence/rspku-testing/final-summary.txt`.

  **QA Scenarios**:
  ```
  Scenario: Evidence completeness
    Tool: Bash
    Steps: list required evidence files and assert each exists and non-empty
    Expected: every file present; missing file makes final report BLOCKED_BY_RUNTIME
    Evidence: .sisyphus/evidence/rspku-testing/final-summary.txt

  Scenario: Honest release decision
    Tool: Read
    Steps: read all evidence and classify final state
    Expected: decision names unresolved blockers; no false production-ready claim
    Evidence: .sisyphus/evidence/rspku-testing/final-summary.txt
  ```

  **Commit**: NO | Message: n/a | Files: [`.sisyphus/evidence/rspku-testing/final-summary.txt`]

## Final Verification Wave
> 3 reviewers run after T1-T10. Do not mark complete until all approve.
- [x] F1. Testing Evidence Audit — oracle
- [x] F2. Runtime QA Review — testing
- [x] F3. Scope Fidelity Check — review

## Commit Strategy
- No source commit for testing-only execution.
- Evidence may remain untracked unless user wants audit trail committed.
- Never stage SQL dumps, logs with secrets, `node_modules`, or local runtime files.

## Success Criteria
- T1-T10 complete with evidence.
- Final verdict names blockers honestly.
- If performance stays >2s with all plugins, next work is separate plugin-performance isolation/fix plan.
