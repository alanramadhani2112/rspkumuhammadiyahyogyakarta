# Panduan Review Approval Rekonsiliasi Source 2026

File approval: `.sisyphus/drafts/reconcile-source-2026-approvals.review.json`

## Prinsip

- Default semua row aman: `skip` atau `editorial-hold`.
- Tidak ada apply sebelum manusia mengisi `decision`, `approved_by`, `approved_at`, dan `reason`.
- `editorial-review` jangan diubah dari `editorial-hold` sebelum RS memvalidasi source.
- `create-draft` hanya untuk dokter/layanan utama yang benar-benar belum ada.
- `add-child-detail` untuk prosedur/fasilitas seperti CT Scan, ECG, Treadmill, varian Ambulans.
- Jangan set `allow_slug_change=true` kecuali ada keputusan eksplisit.

## Ringkasan Review

- Rows requiring review: 101
- Editorial hold default: 13
- Skip default: 88

## Urutan Review Disarankan

1. Validasi semua `editorial-review`.
2. Konfirmasi semua `possible-match`.
3. Pecah `missing` layanan menjadi `create-draft` atau `add-child-detail`.
4. Untuk dokter missing, cek jadwal/relasi dulu sebelum `create-draft`.
5. Jalankan apply hanya setelah approval package bersih.
