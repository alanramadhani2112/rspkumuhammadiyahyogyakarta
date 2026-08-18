# Learnings

- Task 1 registry can use existing `image`, `text`, and `textarea` field types; no admin renderer change needed for settings contract/defaults.
- Sejarah gallery defaults are empty/zero, so `/sejarah-kami` remains text-only until Task 2 validates complete slots and Task 3 renders them.

- Task 2 keeps REST public payload unchanged; history image URL siblings are available only through Timber settings context and gallery is built in TemplateController from validated spku_setting() values.

- Task 3 maps history_page.gallery|default([]) into stable Twig slot variables and gates every editorial figure with its slot, so empty gallery emits no figures.

- Task 4 added a read-only static PHP contract guard at wp-content/themes/rspku-theme/scripts/check-history-gallery-contract.php; it avoids wp-load.php and validates defaults, admin sanitization/image keys, controller complete-only slots, REST non-expansion, and Twig figure rendering.

- 2026-08-16 13:49:16 WIB Task 5A public QA passed: /sejarah-kami empty-gallery baseline emitted 0 figures at 1440x900, 768x1024, 360x800; no broken images, no overflow, no console warnings/errors. Regression /, /kontak/, /dokter/ loaded clean at desktop. Admin settings QA blocked because wp-admin settings URL redirected to /404/ with no authenticated session.

- 2026-08-16 scaffold release completed after explicit user approval. Commits `3584521`, `f8242ce`, `04ef04c` pushed to `main`; CI run `31940630711` succeeded. Six source files plus clean full build deployed. Backup: `/home/dev-rspkujogja/htdocs/dev-rspkujogja.com/deploy-backups/history-gallery-04ef04c-20260816-170753`; previous build: `public/build.previous-04ef04c-20260816-170753`. Production `/sejarah-kami/` passed desktop/mobile with zero history figures while settings remain empty; active assets `app-BwMJJ5pk.js` and `app-DP37Rm0C.css`.

- 2026-08-16 release prep: clean detached worktree C:\Users\LENOVO\AppData\Local\Temp\opencode\rspku-history-release-20260816 based on origin/main 5fec504c872dfbd5a9d50fc43cec0057a7d2707d; created three scaffold-only commits 35845216bd47c0628fb31b0b25c88442cedf72e5, f8242ce8173b9aa5a36146429a5c4cde90737e2b, 04ef04c3bda9c3a5675baa307605c2c5cd9f029a. Verification passed: php -l scoped PHP files, history gallery contract script, npm ci, npm run build. public/build remains ignored/uncommitted.

- 2026-08-16 deployment: scaffold commit 04ef04c deployed from clean worktree to /home/dev-rspkujogja/htdocs/dev-rspkujogja.com using saved metadata. Backup: /home/dev-rspkujogja/htdocs/dev-rspkujogja.com/deploy-backups/history-gallery-04ef04c-20260816-170753. Prior build retained: wp-content/themes/rspku-theme/public/build.previous-04ef04c-20260816-170753. Remote PHP lint, contract script, source/manifest SHA256, active assets, and HTTP checks passed; /sejarah-kami/ remained text-only with 0 figures.

- 2026-08-16 public empty-state release worktree update: Twig now keeps all five semantic history slots visible with data-history-slot markers and a reusable no-img placeholder; contract guard now asserts five slot markers, five placeholder states, exact five responsive-image includes, complete-only controller behavior, and no hardcoded history image paths.


## 2026-08-16 deployment
- Commit deployed: 467e000a6e596d9c6faa999a8cd61403c6cbab5a
- Target root: /home/dev-rspkujogja/htdocs/dev-rspkujogja.com
- Backup path: /home/dev-rspkujogja/htdocs/dev-rspkujogja.com/deploy-backups/history-20260816-235635
- Prior build path: /home/dev-rspkujogja/htdocs/dev-rspkujogja.com/wp-content/themes/rspku-theme/public/build.previous-20260816-235635
- Active assets: assets/app-BwMJJ5pk.js, assets/app-DP37Rm0C.css
- Verification: php -l passed, contract passed, local/remote SHA256 matched, HTTP 200 for /sejarah-kami/, manifest, app JS, app CSS.

## 2026-08-17 deployment
- Commit deployed: bca54cd9cb9fe3221d333299785198cdfd0ddeec
- Worktree: C:\Users\LENOVO\AppData\Local\Temp\opencode\rspku-history-release-20260816
- Target root: /home/dev-rspkujogja/htdocs/dev-rspkujogja.com
- Installed scope: wp-content/themes/rspku-theme/resources/views/pages/page-sejarah-kami.twig plus full clean wp-content/themes/rspku-theme/public/build
- Backup path: /home/dev-rspkujogja/htdocs/dev-rspkujogja.com/deploy-backups/history-spacing-bca54cd-20260817-014235
- Prior build paths: wp-content/themes/rspku-theme/public/build.previous-bca54cd-20260817-014235 and wp-content/themes/rspku-theme/public/build.previous-bca54cd-20260817-014235-swap
- Active assets: assets/app-5yIHAs9E.js, assets/app-Cqrcs6PO.css
- Verification: clean tracked status, manifest asset names matched, local/remote SHA256 matched template 21714a3ab681b4e7be730a2fd87d165e89a39ef479ff7dc73c4f5dad33b7a70b and manifest 35e3aced20da66dab13ae81962536d6f07a759286054a999b7fde62b0f8709a8, HTTP 200 for /sejarah-kami/, app JS, app CSS; no DB/settings/uploads mutation performed.
