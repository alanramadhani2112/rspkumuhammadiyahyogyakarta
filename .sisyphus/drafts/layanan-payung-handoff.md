# Handoff Layanan Payung

## Scope Selesai

- `/layanan/` root archive memakai `service_archive.groups` untuk landing payung.
- `kategori-layanan` archive tidak mendapat `groups`, tetap memakai grid/pagination lama.
- `/layanan/{slug}/` tidak disentuh.
- Tidak ada DB write, migrasi, publish konten, merge term, perubahan slug CPT/taxonomy, dependency baru, deploy, commit.

## File Tersentuh

- `wp-content/themes/rspku-theme/app/Controllers/TemplateController.php`
- `wp-content/themes/rspku-theme/resources/views/pages/archive-layanan.twig`
- `.sisyphus/drafts/layanan-missing-services-backlog.md`
- `.sisyphus/drafts/layanan-payung-handoff.md`

## Implementasi

- `TemplateController::serviceArchiveContext()` sekarang menerima `ContentRepository` opsional.
- Root `/layanan/` menambahkan 8 grup publik: Klinik Spesialis, Gigi & Mulut, Pemeriksaan & Konsultasi, Pusat Layanan Unggulan, Tindakan Medis & Bedah, Penunjang Medis, Rawat Inap & Fasilitas, Home Care & Layanan Luar RS.
- Sumber agregasi: published `layanan`, `poliklinik`, `rawat-inap`.
- Card dinormalisasi ke `title`, `url`, `excerpt`, `image`, `source_type`, `badge`.
- Mapping kurasi diberi komentar `ponytail:` sesuai plan.
- Twig menyembunyikan grup kosong.
- Hero copy memakai copy plan.
- CTA tersedia ke `/dokter/`, `/jadwal-dokter/`, `/kontak/`.

## Caveat

- Mapping masih code-curated, bukan taxonomy/admin-managed.
- Grup kosong tidak tampil, jadi tampilan aktual bisa kurang dari 8 heading jika data published belum ada untuk grup tersebut.
- Build dan browser QA belum dijalankan di handoff ini bila belum ada bukti command setelah implementasi.
- `wp-content/themes/rspku-theme/public/build/` tidak boleh masuk commit.

## Deploy Command

Jangan deploy dari task ini. Bila sudah disetujui, server menjalankan:

```bash
git pull
cd wp-content/themes/rspku-theme
npm ci
npm run build
```

Jika server tanpa Node.js, build lokal lalu upload manual `wp-content/themes/rspku-theme/public/build/` tanpa commit folder tersebut.

## Rollback

Rollback source ke commit sebelum perubahan layanan payung, lalu rebuild frontend di server:

```bash
cd wp-content/themes/rspku-theme
npm run build
```

Tidak ada rollback DB karena no DB migration, no content publishing, no taxonomy/CPT slug change.

## Next Optional Cleanup

- Pertimbangkan taxonomy/group admin-managed untuk mapping payung bila tim konten sering mengubah kategori publik.
- Review duplikasi `spesialisasi-dokter` terpisah dari perubahan layanan payung.
- Validasi backlog layanan hilang sebelum membuat atau memindahkan konten.

## Next Verification

```bash
cd wp-content/themes/rspku-theme
npm run build
```

Lalu cek `/layanan/`, archive `kategori-layanan`, dan satu URL `/layanan/{slug}/`.
