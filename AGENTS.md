# RSPKU Development Workflow

## Deploy Rule

Do not commit `wp-content/themes/rspku-theme/public/build/`.

When user says `deploy`, execute:

```powershell
pwsh -NoProfile -File scripts/deploy.ps1
```

Do not ask credentials again unless the PuTTY key is missing or invalid. Never expose password, private key, or credential content. Use `pwsh -NoProfile -File scripts/deploy.ps1 -DryRun` to validate prerequisites and print the exact plan without remote mutation.

Deploy packages `origin/audit-review-fixes`, builds frontend assets locally because production has no Node.js, uploads the archive with PuTTY PSCP, backs up the production DB plus deployed custom theme/plugins outside document root under `/home/pkujogja/deploy-backups/<stamp>` with private permissions, installs Composer dependencies in the staged theme as the site owner, installs only custom code, preserves ownership, verifies the Vite manifest, then runs HTTP smoke checks.

Legacy deploy guidance if running manually:

```bash
git pull
cd wp-content/themes/rspku-theme
npm ci
npm run build
```

If the server has no Node.js, build locally and upload this directory manually:

```text
wp-content/themes/rspku-theme/public/build/
```

## Frontend Asset Rule

After changing any theme frontend source, run a production build:

```text
wp-content/themes/rspku-theme/resources/js/
wp-content/themes/rspku-theme/resources/css/
wp-content/themes/rspku-theme/resources/views/
```

```bash
cd wp-content/themes/rspku-theme
npm run build
```

## Verification Rule

Before saying frontend work is done, verify:

```bash
cd wp-content/themes/rspku-theme
npm run build
```

Then confirm the manifest points to the latest generated app assets:

```text
wp-content/themes/rspku-theme/public/build/.vite/manifest.json
```

## Server Symptom

If a feature works locally but not on the server, check frontend assets first. The usual cause is server still using an old `public/build/` bundle.
