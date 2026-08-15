## Build Applicability Evidence

Batch: `master-data-layanan-101-local-20260815-0203`

Changed-file check:

- `wp-content/themes/rspku-theme/public/build`: no tracked/untracked git status output before build.
- Theme source files are dirty in worktree from existing unrelated work, so production build was run conservatively.

Command:

```bash
cd wp-content/themes/rspku-theme
npm run build
```

Result:

```text
vite v6.4.2 building for production...
✓ built in 15.31s
```

Generated app assets reported by Vite:

- `assets/app-BPIe1vVJ.css`
- `assets/app-CtXqzQoY.js`

Post-build git check:

```text
git status --short -- wp-content/themes/rspku-theme/public/build
```

Result: no output.

DB writes: `0`
