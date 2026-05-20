# Migration Guide — RS PKU Muhammadiyah Yogyakarta

Panduan migrasi dari lokal (Laragon) ke server produksi via **CloudPanel**.

- **Domain produksi:** `https://dash-rspkujogja.muhammadiyah.or.id`
- **DB export:** `db-rspkujogja-20260520-144238.sql` (65.3 MB)
- **Table prefix:** `lyxpx_`

---

## Step 1 — Buat Site di CloudPanel

1. Login ke CloudPanel → **Sites → Add Site**
2. Isi domain: `dash-rspkujogja.muhammadiyah.or.id`
3. Pilih PHP version: **8.3**
4. Catat **Document Root** yang diberikan (biasanya `/home/username/htdocs/dash-rspkujogja.muhammadiyah.or.id`)

---

## Step 2 — Buat Database di CloudPanel

1. Di CloudPanel → **Databases → Add Database**
2. Isi:
   - **Database Name:** `rspkujogja` (atau sesuai preferensi)
   - **Username:** buat user baru
   - **Password:** gunakan password kuat
3. Catat ketiga nilai ini — dibutuhkan di Step 5

---

## Step 3 — Upload Files ke Server

### Opsi A: via SFTP (disarankan untuk file besar)

Di CloudPanel → **Users → Add User** → aktifkan SFTP.

Gunakan FileZilla atau WinSCP:
- **Host:** IP server
- **Port:** 22
- **Username/Password:** dari CloudPanel SFTP user

Upload seluruh isi folder `C:\laragon\www\rspkudev\` ke Document Root, **kecuali:**
- `wp-config.php` (buat baru di server — lihat Step 5)
- `*.sql` (upload terpisah — lihat Step 4)
- `wp-content/uploads/` (upload terpisah karena 1.8 GB)

### Opsi B: via File Manager CloudPanel

Cocok untuk file kecil. Untuk folder `wp-content/uploads/` yang 1.8 GB, tetap gunakan SFTP.

---

## Step 4 — Import Database

### Persiapan: Naikkan batas upload phpMyAdmin

File SQL 65 MB sering gagal karena batas default phpMyAdmin 2–8 MB.

**Di CloudPanel → PHP Settings → php.ini**, tambahkan/ubah:
```ini
upload_max_filesize = 128M
post_max_size = 128M
max_execution_time = 300
max_input_time = 300
```
Simpan dan restart PHP.

### Import via phpMyAdmin

1. CloudPanel → **Databases → phpMyAdmin** (klik ikon di samping nama DB)
2. Pilih database yang sudah dibuat di Step 2
3. Klik tab **Import**
4. Klik **Choose File** → pilih `db-rspkujogja-20260520-144238.sql`
5. Format: **SQL** (default)
6. Klik **Import**

> **Jika muncul error "Table structure for table..."** — itu bukan error, itu komentar SQL biasa. Error asli biasanya berwarna merah dan menyebut `ERROR` atau `Access denied`. Jika import selesai dan muncul pesan hijau "Import has been successfully finished", berarti berhasil.

> **Jika timeout/gagal karena file terlalu besar**, gunakan SSH (Step 4B).

### Step 4B — Import via SSH (alternatif jika phpMyAdmin gagal)

Di CloudPanel → **Users → SSH Users** → aktifkan SSH.

```bash
# Login ke server
ssh username@ip-server

# Import database
mysql -u db_user -p nama_database < /path/to/db-rspkujogja-20260520-144238.sql
```

---

## Step 5 — Buat wp-config.php di Server

**Jangan upload wp-config.php dari lokal** — berisi kredensial lokal dan dev flags.

Buat file baru di Document Root server dengan isi berikut:

```php
<?php
define( 'DB_NAME', 'nama_database' );        // dari Step 2
define( 'DB_USER', 'db_user' );              // dari Step 2
define( 'DB_PASSWORD', 'db_password' );      // dari Step 2
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// Ganti dengan salt baru dari: https://api.wordpress.org/secret-key/1.1/salt/
define( 'AUTH_KEY',         'ganti-dengan-salt-baru' );
define( 'SECURE_AUTH_KEY',  'ganti-dengan-salt-baru' );
define( 'LOGGED_IN_KEY',    'ganti-dengan-salt-baru' );
define( 'NONCE_KEY',        'ganti-dengan-salt-baru' );
define( 'AUTH_SALT',        'ganti-dengan-salt-baru' );
define( 'SECURE_AUTH_SALT', 'ganti-dengan-salt-baru' );
define( 'LOGGED_IN_SALT',   'ganti-dengan-salt-baru' );
define( 'NONCE_SALT',       'ganti-dengan-salt-baru' );

$table_prefix = 'lyxpx_';

// Produksi — semua debug OFF
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', false );

define( 'AUTOSAVE_INTERVAL', 600 );
define( 'WP_POST_REVISIONS', 5 );
define( 'EMPTY_TRASH_DAYS', 21 );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
```

> Generate salt baru di: https://api.wordpress.org/secret-key/1.1/salt/

---

## Step 6 — Ganti URL (WAJIB)

Database masih berisi URL lokal `http://rspkudev.test`. Harus diganti ke domain produksi.

### Via SSH + WP-CLI (disarankan)

```bash
cd /path/to/document-root

# Install WP-CLI jika belum ada
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Ganti URL — --precise wajib untuk handle serialized data (Elementor/ACF)
wp search-replace 'http://rspkudev.test' 'https://dash-rspkujogja.muhammadiyah.or.id' --all-tables --precise

# Jika ada sisa URL lama dari domain sebelumnya
wp search-replace 'https://rspkujogja.com' 'https://dash-rspkujogja.muhammadiyah.or.id' --all-tables --precise
wp search-replace 'http://rspkujogja.com' 'https://dash-rspkujogja.muhammadiyah.or.id' --all-tables --precise

# Flush cache
wp cache flush
wp rewrite flush
```

> **Kenapa `--precise` wajib?** Database ini mengandung 119 baris postmeta dengan URL dalam format serialized (data Elementor/ACF). Tanpa `--precise`, data serialized akan corrupt dan halaman Elementor rusak.

### Via phpMyAdmin (minimal — jika tidak ada SSH)

Ini hanya fix siteurl & home, **tidak** menangani serialized data di postmeta:

```sql
UPDATE lyxpx_options
SET option_value = 'https://dash-rspkujogja.muhammadiyah.or.id'
WHERE option_name IN ('siteurl', 'home');
```

Setelah ini, halaman yang dibangun dengan Elementor mungkin masih ada broken link — harus tetap jalankan WP-CLI `search-replace` untuk fix sempurna.

---

## Step 7 — Install Theme Dependencies

Via SSH:

```bash
cd /path/to/document-root/wp-content/themes/rspku-theme

# Install PHP dependencies (Timber, dll)
composer install --no-dev --optimize-autoloader
```

> **Catatan:** Build assets (CSS/JS) sudah ada di `public/build/` — tidak perlu `npm run build` di server.

---

## Step 8 — Set File Permissions

```bash
cd /path/to/document-root

find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 600 wp-config.php
```

---

## Step 9 — Flush Rewrite Rules

Di WordPress Admin:
- **Settings → Permalinks → Save Changes**

Ini penting agar custom post type (`dokter`, `poliklinik`, `layanan`, `rawat-inap`) dan REST API endpoint berfungsi.

---

## Step 10 — Upload Media (wp-content/uploads)

Folder uploads 1.8 GB — upload via SFTP ke:
```
/path/to/document-root/wp-content/uploads/
```

Gunakan rsync jika ada SSH untuk efisiensi:
```bash
rsync -avz --progress wp-content/uploads/ username@server:/path/to/document-root/wp-content/uploads/
```

---

## Checklist Verifikasi Post-Migrasi

- [ ] Homepage tampil normal di `https://dash-rspkujogja.muhammadiyah.or.id`
- [ ] Tidak ada redirect ke `rspkujogja.com` atau `rspkudev.test`
- [ ] WP Admin bisa login: `/wp-admin`
- [ ] Custom post type bisa diakses: Dokter, Poliklinik, Layanan, Rawat Inap
- [ ] REST API berfungsi: `GET /wp-json/rspku/v1/site`
- [ ] Media/gambar tampil (uploads ter-upload dengan benar)
- [ ] SSL/HTTPS aktif, tidak ada mixed content warning di browser
- [ ] `WP_DEBUG = false` (cek di wp-config.php server)
- [ ] Plugin aktif: rspku-core, rspku-cpt, rspku-schema, rspku-settings

---

## Informasi Teknis Project

| Item | Detail |
|------|--------|
| Platform | WordPress 6.5+ |
| PHP | 8.3+ |
| Theme | rspku-theme (Timber v2 + Twig + TailwindCSS 3 + Alpine.js 3 + Vite 6) |
| Plugin custom | rspku-core, rspku-cpt, rspku-schema, rspku-settings |
| DB table prefix | `lyxpx_` |
| Namespace PHP | `Rspku\` |
| REST API namespace | `rspku/v1` |
| DB export | `db-rspkujogja-20260520-144238.sql` (65.3 MB) |
| URL lokal | `http://rspkudev.test` |
| URL produksi | `https://dash-rspkujogja.muhammadiyah.or.id` |
