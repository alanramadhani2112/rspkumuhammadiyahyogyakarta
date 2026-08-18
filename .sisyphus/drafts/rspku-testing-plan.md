# Draft: RSPKU Testing Plan

## Requirements (confirmed)
- User asked: "Coba kamu susun testing plan untuk rspku dev ini"

## Technical Decisions
- Plan only; no source implementation during planning.
- Testing plan must cover local WordPress runtime, theme `wp-content/themes/rspku-theme`, plugins `wp-content/plugins/rspku-*`, REST/AJAX, frontend smoke, build/lint, DB readiness, and known performance blocker.

## Research Findings
- Test infra scan `bg_fc398fc4`: CI exists in `.github/workflows/ci.yml`; theme has `npm run build`; existing test `wp-content/plugins/rspku-settings/tests/admin-css.test.mjs`; no Jest/Vitest/Playwright/Cypress config found.
- App surface scan and production QA scan aborted; filled gaps via direct repo inspection and prior evidence.
- Key app surfaces: public routes `/`, `/dokter/`, `/berita-artikel/`; REST endpoints in `rspku-core.php`; doctor search in `DoctorSearch.php` + `resources/js/app.js`; settings CSS/admin plugin.
- Known blocker: homepage warm-cache with all plugins ~2.95s; RSPKU-only/no-plugin under 2s, so performance plan must isolate third-party plugin/runtime.

## Open Questions
- Default chosen: lightweight agent-executed QA plan using existing tools; no new framework.
- Plugin-performance isolation included as diagnostic testing task, not source fix.

## Scope Boundaries
- INCLUDE: testing plan, commands, route/API matrix, evidence checklist.
- EXCLUDE: implementing tests or changing source code unless user later asks execution.
