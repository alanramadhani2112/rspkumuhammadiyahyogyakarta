# Layanan Payung RS PKU Muhammadiyah Yogyakarta

## TL;DR
> **Summary**: Ubah `/layanan/` dari archive sempit CPT `layanan` menjadi landing page payung semua layanan RS. Pakai agregasi frontend dari `layanan`, `poliklinik`, `rawat-inap`; tanpa migrasi data besar.
> **Deliverables**: halaman `/layanan/` berkelompok, mapping kategori publik, copy pasien-friendly, backlog layanan hilang, build + QA.
> **Effort**: Medium
> **Parallel**: YES - 3 waves
> **Critical Path**: Task 1 → 2 → 4 → 6 → Final Verification

## Context

### Original Request
User: “Susun plannya, agar ini bisa menjadi rumahnya atau payung”

### Interview Summary
- Profil RS: `C:\Users\LENOVO\Downloads\Profil RS PKU Muhammadiyah Yogyakarta (Final).md`.
- Existing WordPress: CPT `layanan`, `poliklinik`, `dokter`, `rawat-inap`; taxonomy `kategori-layanan`, `spesialisasi-dokter`, `jenis-konsultasi`.
- `layanan` existing hanya 15 item dalam 2 kategori: `Layanan Unggulan`, `Layanan Penunjang`.
- Banyak layanan profil sudah ada sebagai `poliklinik`, bukan `layanan`.
- Tujuan: `/layanan/` jadi rumah/payung semua layanan pasien.

### Metis Review (gaps addressed)
- Preserve `/layanan/{slug}/`.
- Preserve taxonomy archive `kategori-layanan`.
- Do not migrate `poliklinik` into `layanan`.
- Do not delete/merge posts/terms.
- Do not rename CPT/taxonomy slugs.
- Defer duplicate `spesialisasi-dokter` cleanup.
- Build required after Twig/PHP frontend changes.
- Do not commit `wp-content/themes/rspku-theme/public/build/`.

## Work Objectives

### Core Objective
Make `/layanan/` a patient-facing umbrella page for all major RS services while preserving current data model, URLs, and admin workflows.

### Deliverables
- Aggregated service groups for `/layanan/`.
- Updated `archive-layanan.twig` umbrella layout.
- Copy and labels aligned with profile RS.
- Missing service backlog.
- Compatibility checks for single service/category routes.
- Build and browser QA evidence.

### Definition of Done
- `/layanan/` renders these 8 groups: Klinik Spesialis, Gigi & Mulut, Pemeriksaan & Konsultasi, Pusat Layanan Unggulan, Tindakan Medis & Bedah, Penunjang Medis, Rawat Inap & Fasilitas, Home Care & Layanan Luar RS.
- Existing `/layanan/{slug}/` still renders single service.
- Existing service category archive still renders.
- No DB migration, no post deletion, no taxonomy merge.
- Command passes:
  ```bash
  cd wp-content/themes/rspku-theme
  npm run build
  ```
- `wp-content/themes/rspku-theme/public/build/` not committed.

### Must Have
- Use `layanan`, `poliklinik`, `rawat-inap` as sources.
- Keep `layanan` CPT as current service detail content.
- Add CTAs: `/dokter/`, `/jadwal-dokter/`, `/kontak/`.
- Hide empty groups.
- Mark profile-derived missing services as review backlog, not auto-published.

### Must NOT Have
- No mass migration.
- No slug rename.
- No destructive taxonomy cleanup.
- No booking feature.
- No new plugin/dependency.
- No broad SEO rewrite.

## Verification Strategy
> ZERO HUMAN INTERVENTION - all verification is agent-executed.
- Test decision: tests-after via PHP inspection, build, browser/curl.
- QA policy: Every task includes happy + failure/compat scenario.
- Evidence path: `.sisyphus/evidence/task-{N}-{slug}.{ext}`

## Execution Strategy

### Parallel Execution Waves
Wave 1: Task 1 mapping, Task 2 context builder, Task 3 copy rules.
Wave 2: Task 4 template, Task 5 missing-service backlog, Task 6 compatibility guards.
Wave 3: Task 7 build/browser QA, Task 8 deploy handoff.

### Dependency Matrix
| Task | Blocks | Blocked By |
| --- | --- | --- |
| 1 | 2,4,5 | none |
| 2 | 4,6,7 | 1 |
| 3 | 4,5 | none |
| 4 | 6,7 | 1,2,3 |
| 5 | 8 | 1,3 |
| 6 | 7 | 2,4 |
| 7 | 8 | 4,6 |
| 8 | none | 5,7 |

### Agent Dispatch Summary
| Wave | Count | Categories |
| --- | ---: | --- |
| 1 | 3 | implementation, writing |
| 2 | 3 | visual-engineering, writing, testing |
| 3 | 2 | testing, writing |

## TODOs

- [ ] 1. Finalize Umbrella Mapping

  **What to do**: Define curated mapping for 8 public groups. Map `poliklinik` by explicit slug/title list. Map `layanan` by `kategori-layanan` plus duplicate overrides. Map `rawat-inap` to Rawat Inap & Fasilitas if published. Add `ponytail:` comment: curated code list is ceiling; upgrade to admin-managed grouping taxonomy when content team needs frequent edits.

  **Must NOT do**: No DB write. No taxonomy creation. No term merge.

  **Recommended Agent Profile**:
  - Category: `implementation` - PHP mapping.
  - Skills: []
  - Omitted: `database-designer` - no schema migration.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: 2,4,5 | Blocked By: none

  **References**:
  - `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php:571` - `serviceArchiveContext()`.
  - `wp-content/themes/rspku-theme/app/Repositories/ContentRepository.php:632` - `normalizeService()`.
  - `.sisyphus/drafts/report-audit-data-layanan-rspku.md` - existing data + gaps.

  **Acceptance Criteria**:
  - [ ] All 8 groups defined.
  - [ ] Duplicate-prone items have explicit placement: Dental Clinic, Fisioterapi, Home Care, Radiologi, Gizi.
  - [ ] Evidence lists group counts.

  **QA Scenarios**:
  ```
  Scenario: All groups generated
    Tool: Bash
    Steps: Run read-only PHP inspection via `wp-load.php` to print group labels/counts.
    Expected: 8 group labels output; no fatal error.
    Evidence: .sisyphus/evidence/task-1-umbrella-mapping.txt

  Scenario: Duplicate-prone items controlled
    Tool: Bash
    Steps: Inspect group output for Dental Clinic, Fisioterapi, Home Care, Radiologi, Gizi.
    Expected: No accidental duplicate cards in same group output.
    Evidence: .sisyphus/evidence/task-1-duplicate-check.txt
  ```

  **Commit**: YES | Message: `feat(layanan): map umbrella service groups` | Files: theme PHP mapping files

- [ ] 2. Build Aggregated Archive Context

  **What to do**: Add `service_archive.groups` for root `/layanan/` only. Query published `layanan`, `poliklinik`, `rawat-inap`. Normalize cards to `title`, `url`, `excerpt`, `image`, `source_type`, `badge`. Keep `items` for taxonomy archive compatibility. Set umbrella total to unique aggregated cards.

  **Must NOT do**: Do not change REST API contract. Do not alter `single-layanan.twig` behavior. Do not add dependency.

  **Recommended Agent Profile**:
  - Category: `implementation` - PHP/Twig context.
  - Skills: []
  - Omitted: `api-design-reviewer` - no API change.

  **Parallelization**: Can Parallel: NO | Wave 1 | Blocks: 4,6,7 | Blocked By: 1

  **References**:
  - `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php:249` - archive context injection.
  - `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php:571` - current context.
  - `wp-content/themes/rspku-theme/app/Repositories/ContentRepository.php:650` - polyclinic normalizer.

  **Acceptance Criteria**:
  - [ ] Root `/layanan/` context has `groups`.
  - [ ] `is_tax('kategori-layanan')` keeps list/grid context.
  - [ ] No fatal if `rawat-inap` count is zero.

  **QA Scenarios**:
  ```
  Scenario: Root archive has groups
    Tool: Bash
    Steps: Fetch/render `/layanan/` locally.
    Expected: HTML contains group labels; no PHP fatal.
    Evidence: .sisyphus/evidence/task-2-context-groups.txt

  Scenario: Taxonomy archive remains list
    Tool: Bash
    Steps: Fetch `/layanan-medis/layanan-unggulan/` or existing category URL.
    Expected: Existing list/grid renders; no umbrella-only break.
    Evidence: .sisyphus/evidence/task-2-taxonomy-compat.txt
  ```

  **Commit**: YES | Message: `feat(layanan): aggregate service archive context` | Files: `TemplateController.php`, possible repository helper

- [ ] 3. Define Patient-Facing Copy

  **What to do**: Use profile values: amanah, lengkap, mutu, universal, nyaman. Set hero title to `Semua Layanan RS PKU Muhammadiyah Yogyakarta`. Hero description: `Temukan layanan sesuai kebutuhan Anda, mulai dari klinik spesialis, pemeriksaan penunjang, rawat inap, hingga layanan pendukung pasien dan keluarga.` Define short descriptions for all 8 groups. Use CTAs: `Cari Dokter`, `Lihat Jadwal Dokter`, `Hubungi Kami`.

  **Must NOT do**: No “terbaik/terdepan/nomor satu”. No `internal`, `taxonomy`, `modul`, `tersinkron`, repeated `resmi` in public copy.

  **Recommended Agent Profile**:
  - Category: `writing` - UX copy.
  - Skills: []
  - Omitted: `ocs-technical-copy-seo` - not SEO rewrite.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: 4,5 | Blocked By: none

  **References**:
  - `C:\Users\LENOVO\Downloads\Profil RS PKU Muhammadiyah Yogyakarta (Final).md:86` - history/mission.
  - `C:\Users\LENOVO\Downloads\Profil RS PKU Muhammadiyah Yogyakarta (Final).md:141` - ALMAUN.
  - `.sisyphus/drafts/report-audit-data-layanan-rspku.md` - label recommendations.

  **Acceptance Criteria**:
  - [ ] Hero and all group copy present.
  - [ ] Technical/internal terms absent from public copy.
  - [ ] CTA labels concrete.

  **QA Scenarios**:
  ```
  Scenario: Copy blacklist absent
    Tool: Bash
    Steps: Grep changed public Twig/PHP copy for `internal|taxonomy|modul|tersinkron`.
    Expected: No public-facing matches.
    Evidence: .sisyphus/evidence/task-3-copy-blacklist.txt

  Scenario: Category descriptions present
    Tool: Bash
    Steps: Grep rendered `/layanan/` HTML for 8 labels/descriptions.
    Expected: All present.
    Evidence: .sisyphus/evidence/task-3-category-copy.txt
  ```

  **Commit**: YES | Message: `copy(layanan): clarify umbrella service labels` | Files: copy-bearing theme files

- [ ] 4. Rebuild `/layanan/` Template

  **What to do**: Update `archive-layanan.twig`. If `service_archive.groups` exists, render umbrella landing: hero, quick stats, grouped sections, cards, badges, CTAs. If `groups` absent, keep current grid/pagination for category archive. Hide empty groups. Reuse existing card/button/breadcrumb patterns.

  **Must NOT do**: No new CSS unless necessary. No JS search/filter. No redesign outside `/layanan/`.

  **Recommended Agent Profile**:
  - Category: `visual-engineering` - Twig/Tailwind layout.
  - Skills: [`frontend-ui-ux`]
  - Omitted: `epic-design` - no cinematic animation.

  **Parallelization**: Can Parallel: NO | Wave 2 | Blocks: 6,7 | Blocked By: 1,2,3

  **References**:
  - `wp-content/themes/rspku-theme/resources/views/pages/archive-layanan.twig:16` - hero.
  - `wp-content/themes/rspku-theme/resources/views/pages/archive-layanan.twig:78` - current grid.
  - `wp-content/themes/rspku-theme/resources/views/components/button.twig` - buttons.

  **Acceptance Criteria**:
  - [ ] `/layanan/` renders umbrella layout.
  - [ ] Category archives render existing grid.
  - [ ] Cards have visible accessible titles.
  - [ ] CTA URLs are `/dokter/`, `/jadwal-dokter/`, `/kontak/`.

  **QA Scenarios**:
  ```
  Scenario: Umbrella page visible
    Tool: Playwright
    Steps: Open `/layanan/`; inspect hero, 8 headings, cards.
    Expected: Umbrella layout visible; cards link to valid URLs.
    Evidence: .sisyphus/evidence/task-4-umbrella-page.png

  Scenario: Category archive still grid
    Tool: Playwright
    Steps: Open `/layanan-medis/layanan-unggulan/`.
    Expected: Service grid visible; no root-only umbrella sections.
    Evidence: .sisyphus/evidence/task-4-taxonomy-page.png
  ```

  **Commit**: YES | Message: `feat(layanan): render umbrella landing page` | Files: `archive-layanan.twig`

- [ ] 5. Create Missing Services Backlog

  **What to do**: Create/update `.sisyphus/drafts/layanan-missing-services-backlog.md`. List profile-derived missing/unclear items with destination (`layanan`, `poliklinik`, `rawat-inap`, content section only), priority, and `review-needed` status. High priority: Cancer Centre, Bedah Sentral, Endoscopy, TB-DOTS, Klinik Nyeri Terpadu, Klinik Laktasi, Bank Darah, Mikrobiologi Klinik, One Day Care.

  **Must NOT do**: Do not publish WordPress posts. Do not treat OCR text as final copy.

  **Recommended Agent Profile**:
  - Category: `writing` - content backlog.
  - Skills: []
  - Omitted: `database-designer` - no schema work.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: 8 | Blocked By: 1,3

  **References**:
  - `.sisyphus/drafts/report-audit-data-layanan-rspku.md` - gap table.
  - `C:\Users\LENOVO\Downloads\Profil RS PKU Muhammadiyah Yogyakarta (Final).md:237` - layanan medis.
  - `C:\Users\LENOVO\Downloads\Profil RS PKU Muhammadiyah Yogyakarta (Final).md:355` - Cancer Centre.

  **Acceptance Criteria**:
  - [ ] All high-priority missing items listed.
  - [ ] Each item has destination and `review-needed`.
  - [ ] No DB publish performed.

  **QA Scenarios**:
  ```
  Scenario: Backlog complete
    Tool: Bash
    Steps: Grep backlog for high-priority names.
    Expected: All names present with destination + review-needed.
    Evidence: .sisyphus/evidence/task-5-backlog-check.txt

  Scenario: Counts unchanged
    Tool: Bash
    Steps: Read-only PHP count for `layanan` and `poliklinik` before/after.
    Expected: Counts unchanged unless user explicitly approved content creation.
    Evidence: .sisyphus/evidence/task-5-no-publish.txt
  ```

  **Commit**: NO | Message: `docs(layanan): add missing service backlog` | Files: `.sisyphus/drafts/*`

- [ ] 6. Preserve Route Compatibility

  **What to do**: Verify `/layanan/{slug}/`, service category archives, legacy category redirect, breadcrumbs. Add guard if umbrella context leaks into taxonomy archive.

  **Must NOT do**: Do not change rewrite rules. Do not flush permalinks in code.

  **Recommended Agent Profile**:
  - Category: `testing` - route compatibility.
  - Skills: []
  - Omitted: `migration-architect` - no migration.

  **Parallelization**: Can Parallel: NO | Wave 2 | Blocks: 7 | Blocked By: 2,4

  **References**:
  - `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php:50` - legacy rewrite rules.
  - `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php:312` - taxonomy template selection.
  - `wp-content/themes/rspku-theme/resources/views/pages/single-layanan.twig:7` - breadcrumbs.

  **Acceptance Criteria**:
  - [ ] `/layanan/` root uses groups.
  - [ ] `/layanan/ambulans/` loads single page.
  - [ ] Category archive loads list/grid.
  - [ ] No 404 introduced.

  **QA Scenarios**:
  ```
  Scenario: Single service route survives
    Tool: Bash curl or Playwright
    Steps: Open `/layanan/ambulans/`.
    Expected: HTTP 200; title `Ambulans` visible.
    Evidence: .sisyphus/evidence/task-6-single-service.txt

  Scenario: Category route survives
    Tool: Bash curl or Playwright
    Steps: Open `/layanan-medis/layanan-penunjang/` and legacy `/kategori-layanan/layanan-penunjang/` if available.
    Expected: Render or redirect as before; no fatal error.
    Evidence: .sisyphus/evidence/task-6-category-route.txt
  ```

  **Commit**: YES | Message: `fix(layanan): preserve archive compatibility` | Files: only touched guards/tests

- [ ] 7. Build and Browser Verification

  **What to do**: Run production build. Verify Vite manifest. Verify no build artifacts intended for commit. Browser/curl smoke `/layanan/`, `/layanan/ambulans/`, category archive, `/dokter/`, `/jadwal-dokter/`.

  **Must NOT do**: Do not commit `public/build/`. Do not deploy.

  **Recommended Agent Profile**:
  - Category: `testing` - build/browser QA.
  - Skills: [`playwright`]
  - Omitted: `performance-profiler` - not perf task.

  **Parallelization**: Can Parallel: NO | Wave 3 | Blocks: 8 | Blocked By: 4,6

  **References**:
  - `AGENTS.md` - frontend build/deploy rule.
  - `wp-content/themes/rspku-theme/public/build/.vite/manifest.json` - manifest.

  **Acceptance Criteria**:
  - [ ] `npm run build` exits 0.
  - [ ] Manifest exists.
  - [ ] Browser smoke passes.
  - [ ] `public/build/` not committed.

  **QA Scenarios**:
  ```
  Scenario: Production build passes
    Tool: Bash
    Steps: Run `cd wp-content/themes/rspku-theme; npm run build`.
    Expected: Exit 0; manifest exists.
    Evidence: .sisyphus/evidence/task-7-build.txt

  Scenario: Browser smoke passes
    Tool: Playwright
    Steps: Open `/layanan/`; click `Cari Dokter`; back; click `Lihat Jadwal Dokter`.
    Expected: `/layanan/`, `/dokter/`, `/jadwal-dokter/` load with expected headings.
    Evidence: .sisyphus/evidence/task-7-browser-smoke.png
  ```

  **Commit**: YES | Message: `test(layanan): verify umbrella archive` | Files: source only

- [ ] 8. Deployment Handoff Notes

  **What to do**: Write concise handoff: changed files, no DB migration, build command, deploy command, rollback path, next optional taxonomy cleanup plan.

  **Must NOT do**: Do not deploy. Do not include secrets.

  **Recommended Agent Profile**:
  - Category: `writing` - handoff.
  - Skills: []
  - Omitted: `release-manager` - no release cut.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: none | Blocked By: 5,7

  **References**:
  - `AGENTS.md` - deploy rule.
  - `.sisyphus/drafts/report-audit-data-layanan-rspku.md` - report.

  **Acceptance Criteria**:
  - [ ] Handoff includes changed files.
  - [ ] Handoff includes `npm run build`.
  - [ ] Handoff includes rollback.
  - [ ] Handoff states no DB migration.

  **QA Scenarios**:
  ```
  Scenario: Handoff deploy-safe
    Tool: Bash
    Steps: Grep handoff for `npm run build`, `public/build`, `rollback`, `no DB migration`.
    Expected: All present; no credential strings.
    Evidence: .sisyphus/evidence/task-8-handoff-check.txt

  Scenario: Git status excludes build artifacts
    Tool: Bash
    Steps: Run `git status --short`.
    Expected: No intended commit under `wp-content/themes/rspku-theme/public/build/`.
    Evidence: .sisyphus/evidence/task-8-git-status.txt
  ```

  **Commit**: YES | Message: `docs(layanan): add deploy handoff` | Files: handoff docs only

## Final Verification Wave (MANDATORY — after ALL implementation tasks)
> 4 review agents run in PARALLEL. ALL must APPROVE. Present consolidated results to user and get explicit "okay" before completing.
> **Do NOT auto-proceed after verification. Wait for user's explicit approval before marking work complete.**
> **Never mark F1-F4 as checked before getting user's okay.** Rejection or user feedback -> fix -> re-run -> present again -> wait for okay.
- [ ] F1. Plan Compliance Audit — oracle
- [ ] F2. Code Quality Review — unspecified-high
- [ ] F3. Real Manual QA — unspecified-high (+ playwright)
- [ ] F4. Scope Fidelity Check — deep

## Commit Strategy
- Use small commits per task if user asks to commit.
- Do not commit `wp-content/themes/rspku-theme/public/build/`.
- Expected source files: `TemplateController.php`, `archive-layanan.twig`, optional helper in repository/controller, draft backlog/handoff.

## Success Criteria
- `/layanan/` functions as umbrella service home.
- Existing service archive/detail routes preserved.
- Users can navigate from layanan umbrella to doctors, schedules, contact.
- Missing profile-derived services documented for content review.
- Build passes and evidence exists.
