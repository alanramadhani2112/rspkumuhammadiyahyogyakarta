# Git Hooks

Repository-tracked git hooks untuk enforce quality gate sebelum commit.

## Instalasi (satu kali per clone)

```bash
git config core.hooksPath .github/hooks
```

Perintah ini memberitahu git untuk pakai hooks di folder ini alih-alih `.git/hooks/` (yang tidak di-track).

Verifikasi:

```bash
git config --get core.hooksPath
# Harus print: .github/hooks
```

## Hooks yang tersedia

### `pre-commit`

Dijalankan sebelum setiap commit. Memeriksa:

1. **PHP syntax lint** — `php -l` pada semua file `.php` yang di-stage
2. **PHPStan level 5** — kalau `vendor/bin/phpstan` tersedia
3. **Twig tag balance** — sanity check untuk unbalanced `{% if %}/{% endif %}` dkk
4. **Banned files** — tolak commit `file-manager.php`, `adminer.php`, `shell.php`, dst (referensi: `SECURITY-AUDIT-2026-05-11.md`)
5. **Credentials** — tolak commit `wp-config.php` dan `.env*`

## Bypass (gunakan sangat hati-hati)

```bash
git commit --no-verify
```

Hanya gunakan untuk perbaikan darurat yang tidak bisa menunggu fix PHPStan. Setelah bypass, follow-up commit harus perbaiki root cause.

## Windows + Laragon/XAMPP

Kalau `bash` tidak tersedia di PATH, install Git for Windows (yang menyertakan Bash). Hook akan otomatis jalan via Git Bash.

Cek:

```powershell
bash --version
```

Kalau tidak ada, install dari https://git-scm.com/download/win
