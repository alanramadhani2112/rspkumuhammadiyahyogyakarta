# RSPKU Development Workflow

## Deploy Rule

Do not commit `wp-content/themes/rspku-theme/public/build/`.

Deploy must build frontend assets on the server:

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
