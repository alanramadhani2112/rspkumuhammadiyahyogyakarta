# Security Audit: Suspicious Root-Level Files

**Tanggal:** 2026-05-11
**Scope:** Audit file PHP/script di root WordPress (`C:\laragon\www\rspkudev\`) yang bukan bagian dari WordPress core atau plugin legit.
**Konteks:** Modul M2 dari roadmap pengembangan RSPKU (quick wins & audit).

---

## Findings Summary

| File | Ukuran | Status | Severity | Rekomendasi |
|---|---|---|---|---|
| `file-manager.php` | 30 KB | 🚨 **CRITICAL** | High | **HAPUS segera** dari server live & local |
| `clear-cache.php` | 2.4 KB | ⚠️ **WARNING** | Medium | Hapus atau pindah ke WP-CLI dengan auth guard |
| `fix-project.ps1` | 6 KB | ✅ OK | Low | Biarkan (dev-only script) |
| `.user` (0 B) | 0 B | ⚠️ Janggal | Low | Investigasi / hapus |
| `wordfence-waf.php` | 325 B | ✅ OK | Low | Biarkan (Wordfence plugin) |

---

## 1. `file-manager.php` — 🚨 CRITICAL

### Temuan

File ini adalah **web-based file manager + terminal** dengan login password. Berikut bagian yang dianalisis:

```php
<?php
/**
 * File Manager dengan Terminal untuk CloudPanel
 * Pastikan direktori writable dan PHP memiliki izin exec()
 */

session_start();

// Konfigurasi keamanan
define('PASSWORD', '[REDACTED 20-char string, hardcoded]');
define('ROOT_PATH', dirname(__FILE__));
```

### Risiko

1. **Password hardcoded dalam plaintext** di source code. Siapa pun yang bisa baca file ini (misal source-map leak, backup yang tidak di-secure, disclosure via Google cache, atau akses filesystem) bisa dapat password.
2. **Password sederhana (20 char)** kemungkinan brute-force-able dengan rate limiting yang buruk (file PHP tidak punya rate limit native).
3. **Akses via URL publik** `https://rspkujogja.com/file-manager.php` — siapa pun di internet bisa coba login.
4. **Terminal access** artinya sekali login, attacker bisa eksekusi perintah shell arbitrer di server (RCE class vulnerability).
5. **File di root WP** artinya tidak dilindungi dengan `.htaccess` khusus atau perlu login WP.
6. **Nama file terlalu generik** (`file-manager.php`) — ini pola yang sering dipakai malware campaigns. Google: "wordpress file-manager.php backdoor".
7. **Tidak ada audit log** — serangan sukses tidak terdeteksi.

### Kemungkinan Asal

- **Kemungkinan A:** tool admin legit yang ditinggalkan developer/vendor sebelumnya untuk CloudPanel management (komentar menyebut CloudPanel).
- **Kemungkinan B:** backdoor dari serangan malware (password pattern yang ada tidak khas malware, tapi tetap mungkin).

Either way — **tidak boleh ada di production**.

### Mitigasi Wajib

#### 1. Hapus file dari server live SEGERA
```bash
# SSH ke server production
cd /path/to/wordpress
ls -la file-manager.php     # Catat hash & timestamp untuk audit
mv file-manager.php file-manager.php.quarantine.$(date +%s).bak
# Atau hapus langsung kalau yakin bukan file legit:
# rm file-manager.php
```

#### 2. Cek log akses
```bash
# Cari percobaan akses ke file-manager.php dalam 30 hari terakhir
grep "file-manager.php" /var/log/nginx/access.log* | tail -100
grep "file-manager.php" /var/log/apache2/access.log* | tail -100
```

Kalau ada IP asing yang sukses POST ke endpoint ini → **server berpotensi sudah di-compromise**. Lakukan full security scan.

#### 3. Ganti kredensial semua stakeholder
Kalau file ini pernah diakses attacker, mereka kemungkinan sudah tanam backdoor lain atau exfiltrate:
- Reset password semua user WP admin
- Rotate salt key di `wp-config.php` (semua auth key)
- Rotate database password
- Scan malware dengan Wordfence / Sucuri / ImunifyAV
- Check `wp_options` untuk opsi mencurigakan
- Check file integrity vs WP core checksum

#### 4. Alternatif pengganti (kalau memang butuh file management)
- **WP-CLI** via SSH untuk admin sysadmin
- **Plugin resmi** seperti "File Manager" (Mohsin Rasool) — tapi tetap risky, pertimbangkan hanya aktif saat perlu
- **CloudPanel's built-in file manager** (access via CloudPanel admin URL, bukan via WP URL)
- **SFTP/SSH client** (FileZilla, WinSCP) dengan key-based auth

### Action Item

- [ ] Konfirmasi ke pemilik/admin apakah `file-manager.php` memang ditempatkan oleh mereka (legit tool)
- [ ] Kalau tidak, laporkan sebagai security incident
- [ ] Hapus dari filesystem lokal dan production
- [ ] Scan malware full-site
- [ ] Audit log akses 30-90 hari kebelakang
- [ ] Rotate credentials jika terindikasi akses tidak sah

---

## 2. `clear-cache.php` — ⚠️ WARNING

### Temuan

Script development untuk clear cache WordPress, accessible via URL publik.

```php
// Check if user is admin (optional security)
// if (!current_user_can('manage_options')) {
//     die('Access denied');
// }
```

Auth check **commented out** — siapa pun bisa trigger cache flush dengan mengakses `https://rspkujogja.com/clear-cache.php`.

### Risiko

1. **DoS vector ringan** — attacker bisa loop akses URL ini untuk terus-menerus flush cache → performance degradation.
2. **Exposure script development** di production (bad practice).
3. **OPcache reset** via URL bisa berefek pada performa aplikasi lain di server shared.
4. **Rewrite rules flush** tanpa authorization bisa sementara rusak routing.

### Mitigasi

#### Opsi A — Hapus (rekomendasi)
Flush cache sebaiknya via WP admin atau WP-CLI:
```bash
wp cache flush
wp transient delete --all
wp rewrite flush
```

#### Opsi B — Jadikan aman (jika ingin dipertahankan)
Uncomment auth check + pindahkan ke `/wp-admin/tools.php?action=clear-cache` sebagai admin action.

### Action Item

- [ ] Hapus `clear-cache.php` dari root
- [ ] Kalau perlu, bikin WP-CLI command atau admin page di theme

---

## 3. `fix-project.ps1` — ✅ OK

### Temuan

PowerShell script legit untuk dev workflow: install npm deps, build assets.

```powershell
# RS PKU Project Fixer Script
# Run this script to fix common issues automatically
```

### Risiko

Rendah. Script ini:
- Hanya jalan di mesin developer (Windows PowerShell)
- Tidak mempengaruhi server production
- Tidak ada risiko keamanan

### Action Item

- [x] Sudah di-ignore di `.gitignore`
- [ ] Tidak perlu dihapus, tetap dipakai untuk dev

---

## 4. `.user` (0 bytes) — ⚠️ Janggal

File kosong tanpa ekstensi. Kemungkinan artefact dari Wordfence atau hosting. Tidak berbahaya langsung tapi tidak jelas fungsinya.

### Action Item

- [ ] Investigasi asal-usul; kalau tidak ada dokumentasi, hapus

---

## 5. `wordfence-waf.php` — ✅ OK

File legit dari Wordfence Security plugin. Dibuat Wordfence untuk WAF (Web Application Firewall). Jangan dihapus — akan regenerate otomatis.

---

## Rekomendasi Umum (Hardening WP Root)

Terlepas dari findings di atas, untuk hardening jangka panjang:

### Nginx / Apache
```nginx
# Block direct access to any .php file at root except wp-*.php
location ~ ^/([^/]+\.php)$ {
    if ($1 !~ ^(wp-login|wp-cron|wp-signup|wp-activate|wp-comments-post|wp-trackback|xmlrpc|index)\.php$) {
        return 403;
    }
}
```

### `.htaccess`
```apache
# Block access to suspicious file patterns
<FilesMatch "^(file-manager|adminer|phpmyadmin|shell|webshell)\.php$">
    Require all denied
</FilesMatch>

# Block php execution in uploads
<Directory "wp-content/uploads">
    <FilesMatch "\.php$">
        Require all denied
    </FilesMatch>
</Directory>
```

### Monitoring
- Setup **file integrity monitoring** (Wordfence sudah bisa, aktifkan scan scheduled)
- **Fail2ban** untuk auth attempts
- **404 log review** harian untuk detect reconnaissance

### Compliance
- Dokumentasikan daftar file yang boleh ada di root WP
- Review file baru apapun yang di-upload ke root
- Backup rutin dengan versioning

---

## Sumber Referensi

- [WordPress Hardening](https://developer.wordpress.org/advanced-administration/security/hardening/)
- [OWASP PHP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)
- [Wordfence Threat Intelligence](https://www.wordfence.com/threat-intel/)
- [Sucuri Security Guides](https://sucuri.net/guides/)

---

## Lampiran: Timeline Audit

| Waktu | Aksi |
|---|---|
| 2026-05-11 09:55 | Audit awal root files sebagai bagian dari M2 |
| 2026-05-11 10:50 | Fix deprecated `get_page_by_title` di AdminExperience.php |
| 2026-05-11 10:55 | Laporan ini ditulis |

**Decision pending from user:**
- Apakah `file-manager.php` dan `clear-cache.php` boleh dihapus dari local filesystem?
- Apakah ada akses ke server production untuk audit log & cleanup?
