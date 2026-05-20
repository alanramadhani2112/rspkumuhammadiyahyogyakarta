# Migration Guide — RS PKU Muhammadiyah Yogyakarta

Dokumen ini mencatat proses persiapan migrasi project dari lokal (Laragon) ke server produksi.

---

## Ringkasan Persiapan (20 Mei 2026)

### 1. Export Database

- **File:** `db-rspkujogja-clean-20260520-141057.sql`
- **Lokasi di project:** root folder (`C:\laragon\www\rspkudev\`)
- **Ukuran:** 65.31 MB (setelah cleanup)
- **DB name lokal:** `db-rspkujogja`
- **Table prefix:** `lyxpx_`
- **Tool:** `mysqldump` dengan flag `--single-transaction --routines --triggers --add-drop-table`
- **URL lokal di DB:** `http://rspkudev.test` → harus diganti ke domain produksi saat migrasi (lihat Step 6)

#### DB Cleanup yang dilakukan sebelum export:

| Item | Jumlah dihapus | Keterangan |
|------|---------------|------------|
| Trash posts | 41 | Dokter, page, customize_changeset |
| Orphaned postmeta | 1075 + 9 | Meta dari post yang sudah dihapus |
| Auto-drafts | 1 | Draft otomatis tidak terpakai |
| Transients | 59 | Cache sementara, tidak perlu di server |
| Revisions | 809 | Riwayat edit, tidak dibutuhkan produksi |
| Localhost options | 1 | `wpsyncmu_source_url` |
| **Total penghematan** | ~5.5 MB | 70.82 MB → 65.31 MB |

> Semua data di DB adalah dari tahun 2023 ke atas — tidak ada data lama yang perlu difilter.

### 2. Backup Project

- **Folder backup:** `C:\Users\LENOVO\AppData\Local\Temp\opencode\rspkudev-backup-20260520-134949`
- **Ukuran backup:** ~2.98 GB
- **Breakdown ukuran:**

| Folder | Ukuran | Keterangan |
|--------|--------|------------|
| `wp-content/uploads/` | 1858 MB | Media files (gambar, dokumen) |
| `archive/` (lama) | 858 MB | 7 SQL dump lama dari operasi thumbnail sync — sudah dihapus dari project |
| `wp-content/plugins/` | 121 MB | Plugin WordPress |
| `wp-includes/` | 92 MB | WordPress core |
| `wp-content/themes/` | 9.9 MB | Theme rspku-theme |
| lainnya | < 20 MB | — |

> **Catatan:** Backup besar bukan karena duplicate. `archive/` berisi SQL dump lama (thumbnail sync Mei 2026) yang sudah tidak relevan dan sudah dihapus dari project aktif. `uploads/` memang besar karena media asli.

### 3. Cleanup Dev Files

File dan folder berikut dihapus dari project (tidak dibutuhkan di server):

**Folder:**
- `node_modules/` (root) — Playwright dev tools, 16.6 MB
- `vendor/` (root) — PHPStan dev tools, 33 MB
- `output/` — Playwright screenshots, 236.8 MB
- `.playwright-cli/`, `.playwright-mcp/` — 1.1 MB
- `frontend/` — kosong (Next.js lama)
- `.vscode/`, `.agents/`, `.kiro/`, `.sisyphus/` — IDE/AI dev tools
- `docs/` — dev documentation
- `archive/` — SQL dump lama
- `wp-content/themes/rspku-theme/node_modules/` — 80.6 MB (build sudah ada di `public/build/`)
- `wp-content/themes/rspku-theme/output/`, `.playwright-cli/`

**File:**
- 39 file `.md` dev notes (CHANGELOG, DESIGN-SYSTEM, HERO-*, dll)
- `phpstan-baseline.neon`, `phpstan.neon.dist`
- `fix-project.ps1`, `skills-lock.json`
- `composer.json`, `composer.lock` (root — dev tooling only)

**Hasil:** Project size berkurang dari ~3308 MB → ~2139 MB (hemat ~1.17 GB)

---

## Langkah Migrasi ke Server

### Step 1 — Siapkan Server

Pastikan server memiliki:
- PHP 8.3+
- MySQL/MariaDB
- Web server (Apache/Nginx)
- Composer (untuk install theme dependencies)
- Node.js + npm (untuk rebuild theme jika diperlukan)

### Step 2 — Upload Files

Upload seluruh project ke server. Opsi yang disarankan:

```bash
# Via rsync (lebih efisien, skip file yang sudah ada)
rsync -avz --progress /path/to/rspkudev/ user@server:/var/www/html/

# Atau via zip + extract di server
zip -r rspkudev.zip . -x "*.git*"
scp rspkudev.zip user@server:/var/www/html/
```

> **Catatan uploads:** Folder `wp-content/uploads/` berisi 1.8 GB media. Pertimbangkan upload terpisah atau gunakan rsync untuk efisiensi.

### Step 3 — Buat Database di Server

```sql
CREATE DATABASE nama_db_server CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'user_db'@'localhost' IDENTIFIED BY 'password_kuat';
GRANT ALL PRIVILEGES ON nama_db_server.* TO 'user_db'@'localhost';
FLUSH PRIVILEGES;
```

### Step 4 — Import Database

```bash
mysql -u user_db -p nama_db_server < db-rspkujogja-clean-20260520-141057.sql
```

### Step 5 — Update wp-config.php

Edit `wp-config.php` di server, sesuaikan:

```php
define( 'DB_NAME', 'nama_db_server' );
define( 'DB_USER', 'user_db' );
define( 'DB_PASSWORD', 'password_kuat' );
define( 'DB_HOST', 'localhost' );

// Matikan semua dev flags di produksi
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', false );
// define( 'CONCATENATE_SCRIPTS', false );  // ← HAPUS atau comment baris ini
// define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );  // ← HAPUS atau comment baris ini
```

> **Penting:** File `wp-config.php` lokal memiliki `WP_DEBUG=true`, `WP_DEBUG_LOG=true`, `CONCATENATE_SCRIPTS=false`, dan `WP_DISABLE_FATAL_ERROR_HANDLER=true`. Semua flag ini **harus dinonaktifkan** di server produksi. Jangan upload wp-config.php lokal langsung — buat ulang di server dengan kredensial dan setting produksi.

### Step 6 — Update URL Siteurl & Home

Ganti URL lokal ke URL produksi. Via WP-CLI (disarankan):

```bash
wp search-replace 'http://rspkudev.test' 'https://domain-produksi.com' --all-tables --precise
```

> **Penting — Serialized Data:** Database ini mengandung **119 baris postmeta** dengan URL `rspkudev.test` dalam format serialized (data Elementor/ACF). Flag `--precise` pada WP-CLI **wajib digunakan** — flag ini secara otomatis menangani unserialize → replace → re-serialize dengan benar. Tanpa `--precise`, data serialized akan corrupt.

Verifikasi setelah search-replace:
```bash
# Pastikan tidak ada sisa URL lama
wp db query "SELECT COUNT(*) FROM lyxpx_postmeta WHERE meta_value LIKE '%rspkudev.test%';"
# Harus mengembalikan 0
```

Atau manual via phpMyAdmin — update tabel `lyxpx_options` (hanya untuk siteurl & home):
```sql
UPDATE lyxpx_options SET option_value = 'https://domain-produksi.com' WHERE option_name IN ('siteurl', 'home');
```
> Catatan: Cara manual tidak menangani serialized data di postmeta. Gunakan WP-CLI untuk hasil lengkap.

### Step 7 — Install Theme Dependencies

```bash
cd wp-content/themes/rspku-theme

# Install PHP dependencies (Timber, dll)
composer install --no-dev --optimize-autoloader

# Jika perlu rebuild assets (opsional — build sudah ada di public/build/)
npm install
npm run build
```

### Step 8 — Set File Permissions

```bash
# WordPress standard permissions
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;
chmod 600 wp-config.php
chown -R www-data:www-data /var/www/html/
```

### Step 9 — Flush Rewrite Rules

Di WordPress admin:
- **Settings → Permalinks → Save Changes**

Ini penting agar custom post type (`dokter`, `poliklinik`, `layanan`, dll) dan REST API endpoint berfungsi.

### Step 10 — Aktifkan Theme & Plugin

Di WordPress admin:
1. **Appearance → Themes** → aktifkan **RSPKU Muhammadiyah Yogyakarta**
2. **Plugins** → aktifkan **RSPKU Core** (dan plugin lain yang diperlukan)

---

## Checklist Verifikasi Post-Migrasi

- [ ] Homepage tampil normal
- [ ] Custom post type: Dokter, Poliklinik, Layanan, Rawat Inap bisa diakses
- [ ] REST API berfungsi: `GET /wp-json/rspku/v1/site`
- [ ] Media/gambar tampil (uploads ter-upload dengan benar)
- [ ] WP Admin bisa login
- [ ] SSL/HTTPS aktif dan tidak ada mixed content
- [ ] Wordfence atau security plugin aktif
- [ ] WP_DEBUG = false di produksi

---

## Informasi Teknis Project

| Item | Detail |
|------|--------|
| Platform | WordPress 6.5+ |
| PHP | 8.3+ |
| Theme | rspku-theme (Timber v2 + Twig + TailwindCSS 3 + Alpine.js 3 + Vite 6) |
| Plugin custom | rspku-core, rspku-schema, rspku-cpt, rspku-settings |
| DB table prefix | `lyxpx_` |
| Namespace PHP | `Rspku\` |
| REST API namespace | `rspku/v1` |
