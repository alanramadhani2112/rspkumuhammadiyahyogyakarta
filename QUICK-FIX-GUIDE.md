# ⚡ Quick Fix Guide - RS PKU Project

**Tanggal**: 10 Mei 2026

---

## 🚨 CRITICAL - Harus Diperbaiki Sekarang!

### 1️⃣ **Build Theme Assets** (PALING PENTING!)

Website tidak akan tampil dengan benar tanpa ini.

```powershell
cd wp-content/themes/rspku-theme
npm install
npm run build
```

**Waktu**: ~2-3 menit  
**Prioritas**: 🔴 CRITICAL

---

### 2️⃣ **Cleanup Files**

Jalankan script otomatis:

```powershell
.\fix-project.ps1
```

Atau manual:

```powershell
# Pindahkan file test
Move-Item diagnose.php archive/
Move-Item fix-database.php archive/
Move-Item test-db.php archive/
Move-Item test-wp-db.php archive/
Move-Item clear-all-cache.php archive/

# Hapus folder Next.js (tutup editor dulu!)
Remove-Item frontend -Recurse -Force
Remove-Item _archived-frontend -Recurse -Force
```

**Waktu**: ~1 menit  
**Prioritas**: 🟡 HIGH

---

### 3️⃣ **Test Website**

```
http://rspkudev.test
```

- Clear browser cache (Ctrl+Shift+Delete)
- Refresh (Ctrl+F5)
- Cek apakah styling muncul

**Waktu**: ~1 menit  
**Prioritas**: 🔴 CRITICAL

---

## 🔧 MEDIUM - Perbaiki Minggu Ini

### 4️⃣ **Disable Unused Plugins**

Di WordPress Admin → Plugins:
- ❌ Elementor
- ❌ Elementor Pro
- ❌ Advanced Custom Fields (free version)

Keep: ACF Pro, rspku-core, Yoast SEO

**Waktu**: ~2 menit  
**Prioritas**: 🟡 MEDIUM

---

### 5️⃣ **Fix Debug Mode**

Edit `wp-config.php`:

```php
// Untuk PRODUCTION, set ke false
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );

// Untuk LOCAL DEVELOPMENT, biarkan true
```

**Waktu**: ~1 menit  
**Prioritas**: 🟡 MEDIUM

---

## 📝 LOW - Nice to Have

### 6️⃣ **Create Documentation**

Buat file:
- `wp-content/themes/rspku-theme/README.md`
- `CONTRIBUTING.md`
- `CHANGELOG.md`

**Waktu**: ~30 menit  
**Prioritas**: 🟢 LOW

---

### 7️⃣ **Image Optimization**

- Convert ke WebP
- Implement lazy loading
- Cleanup unused media (1.8 GB!)

**Waktu**: ~2-3 jam  
**Prioritas**: 🟢 LOW

---

## ✅ CHECKLIST

Centang setelah selesai:

- [ ] Build theme assets (`npm run build`)
- [ ] Test website di browser
- [ ] Cleanup test files
- [ ] Remove Next.js folders
- [ ] Disable unused plugins
- [ ] Fix debug mode (production)
- [ ] Clear browser cache
- [ ] Test semua halaman

---

## 🎯 EXPECTED RESULTS

Setelah fix:
- ✅ Website tampil dengan styling yang benar
- ✅ Tailwind CSS berfungsi
- ✅ Alpine.js interactivity berjalan
- ✅ No console errors
- ✅ Fast loading time

---

## 🆘 TROUBLESHOOTING

### Website masih error?

1. **Cek build assets**:
   ```powershell
   Test-Path wp-content/themes/rspku-theme/public/build/manifest.json
   ```
   Harus return `True`

2. **Cek database**:
   ```powershell
   php -r "$m = new mysqli('127.0.0.1', 'rspkujogja', 'i6F0LJ3GyDJ2lTIHe1ah', 'db-rspkujogja', 3306); echo $m->connect_error ? 'ERROR' : 'OK';"
   ```
   Harus return `OK`

3. **Cek error log**:
   ```powershell
   Get-Content wp-content/debug.log -Tail 20
   ```

4. **Restart Laragon**:
   - Stop All
   - Wait 5 seconds
   - Start All

---

## 📞 NEED HELP?

Baca laporan lengkap: `PROJECT-AUDIT-REPORT.md`

---

**Last Updated**: 10 Mei 2026  
**Status**: Ready to Fix! 🚀
