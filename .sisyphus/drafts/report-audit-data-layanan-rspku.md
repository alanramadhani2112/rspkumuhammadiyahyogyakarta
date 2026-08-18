# Report Audit Data Layanan RS PKU Muhammadiyah Yogyakarta

## Ringkasan Eksekutif

Audit ini membandingkan struktur layanan dari dokumen profil RS PKU Muhammadiyah Yogyakarta 2026 dengan data existing website WordPress lokal.

Temuan utama: masalah bukan hanya UX writing. Ada perbedaan konsep antara dokumen profil dan model data website. Dokumen profil memakai istilah "layanan" sebagai payung besar seluruh aktivitas pelayanan RS. Website memakai CPT `layanan` secara lebih sempit untuk item unggulan dan penunjang tertentu, sementara layanan klinik/spesialis berada di CPT `poliklinik` dan data dokter berada di CPT `dokter` + taxonomy `spesialisasi-dokter`.

Dampak: jika halaman/menu "Layanan" ditampilkan sebagai representasi semua layanan RS, data yang muncul terasa tidak lengkap. Banyak layanan medis yang sebenarnya sudah ada di website, tetapi tersimpan sebagai `poliklinik`, bukan `layanan`.

## Sumber Data

### 1. Profil RS PKU Muhammadiyah Yogyakarta 2026

File sumber:

`C:\Users\LENOVO\Downloads\Profil RS PKU Muhammadiyah Yogyakarta (Final).md`

Bagian yang dipakai:

- Kata Pengantar dan sejarah RS.
- Visi, misi, nilai ALMAUN.
- Layanan Medis RS PKU Muhammadiyah Yogyakarta.
- Centre of Excellence.
- Dokter Spesialis dan Subspesialis.
- Dokter Umum.
- Fasilitas Umum.
- Akreditasi dan prestasi.
- Informasi dan layanan umum.

Catatan kualitas sumber: file Markdown berasal dari ekstraksi/OCR PDF. Beberapa bagian visual tidak terbaca sempurna, tetapi daftar layanan utama masih cukup terbaca untuk audit awal.

### 2. Data Existing Website WordPress

Sumber repo:

- `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-post-types.php`
- `wp-content/plugins/rspku-cpt/includes/class-rspku-cpt-taxonomies.php`
- `wp-content/plugins/rspku-core/rspku-core.php`
- `wp-content/themes/rspku-theme/app/Repositories/ContentRepository.php`
- `wp-content/themes/rspku-theme/resources/views/pages/archive-layanan.twig`
- `wp-content/themes/rspku-theme/resources/views/pages/single-layanan.twig`
- Database lokal WordPress via `wp-load.php`.

## Struktur Data Website Saat Ini

### Custom Post Types

Website mendaftarkan CPT berikut:

| CPT | Fungsi |
| --- | --- |
| `dokter` | Profil dokter |
| `poliklinik` | Klinik/poli/spesialisasi layanan rawat jalan |
| `layanan` | Layanan RS, saat ini berisi unggulan dan penunjang |
| `jurnal` | E-Jurnal |
| `manajemen-rs` | Manajemen RS |
| `rawat-inap` | Data kamar/rawat inap |

### Taxonomies

Website mendaftarkan taxonomy berikut:

| Taxonomy | Terhubung ke | Fungsi |
| --- | --- | --- |
| `spesialisasi-dokter` | `dokter` | Filter/spesialisasi dokter |
| `kategori-layanan` | `layanan` | Kategori layanan |
| `jenis-konsultasi` | `dokter` | Jenis konsultasi dokter |

## Data Existing: CPT `layanan`

Jumlah data publish: 15 item.

### Kategori: Layanan Unggulan

- Ambulans
- Dental Clinic
- Extramural (Layanan Luar Rumah Sakit)
- Hemodialisis (Cuci Darah)
- Home Care
- Husnul Khotimah
- Klinik Kecantikan Ayna
- Medical Check Up (MCU)
- Vaksin Center

### Kategori: Layanan Penunjang

- CSSD (Central Sterile Supply Department)
- Farmasi
- Fisioterapi
- Gizi
- Laboratorium
- Radiologi

### Kategori yang tersedia

| Kategori | Count |
| --- | ---: |
| Layanan Penunjang | 6 |
| Layanan Unggulan | 9 |

Kesimpulan: CPT `layanan` tidak mewakili seluruh layanan rumah sakit. Ia hanya memuat subset layanan unggulan dan penunjang.

## Data Existing: CPT `poliklinik`

Data `poliklinik` memuat sebagian besar layanan rawat jalan/spesialis dari profil RS.

Daftar publish:

- Anestesi
- Klinik Anak Imuniasasi & Tumbuh Kembang Anak
- Klinik Bedah Anak
- Klinik Bedah Digesti
- Klinik Bedah Mulut
- Klinik Bedah Onkologi
- Klinik Bedah Orthopedi
- Klinik Bedah Syaraf
- Klinik Bedah Umum
- Klinik Bedah Urologi
- Klinik Gigi Endodonsi
- Klinik Gigi Ortodonti
- Klinik Gigi Pedodonti
- Klinik Gigi Periodonti
- Klinik Gigi Umum
- Klinik Ginekologi Onkologi
- Klinik Ginjal dan Hipertensi
- Klinik Hemato Onkologi
- Klinik Home Care
- Klinik Jantung Anak
- Klinik Jantung dan Pembuluh Darah
- Klinik Jiwa
- Klinik Kandungan (Obsgyn)
- Klinik Kulit dan Kelamin
- Klinik Mata
- Klinik Paru
- Klinik Patologi
- Klinik Patologi Anatomi
- Klinik Penyakit Dalam
- Klinik Psikologi Klinis
- Klinik Syaraf
- Klinik THT
- Klinik USG dan Radiologi
- Rehabilitasi Medis
- Terapi Tumbuh Kembang Anak
- Terapi Wicara
- Umum / Instalasi Gawat Darurat (IGD)

Kesimpulan: banyak "layanan medis" dari profil sudah ada, tetapi berada di `poliklinik`, bukan `layanan`.

## Data Existing: Taxonomy `spesialisasi-dokter`

Taxonomy ini berisi spesialisasi dokter. Data cukup kaya, tetapi ditemukan duplikasi/near-duplicate istilah.

Contoh duplikasi:

| Istilah A | Istilah B | Risiko |
| --- | --- | --- |
| Paru | Penyakit Paru | Filter dokter/jadwal terpecah |
| Saraf | Penyakit Saraf | Filter dokter/jadwal terpecah |
| THT | Penyakit THT | Filter dokter/jadwal terpecah |
| Mata | Penyakit Mata | Filter dokter/jadwal terpecah |
| Jiwa | Kesehatan Jiwa | Filter dokter/jadwal terpecah |
| Kulit & Kelamin | Kulit dan Kelamin | Duplikasi label publik |
| Kandungan | Obsgyn / Kebidanan & Kandungan | Istilah tidak konsisten |
| Anak | Penyakit Anak Imunisasi & Tumbuh Kembang Anak | Scope bercampur antara spesialisasi dan klinik |

Kesimpulan: taxonomy dokter perlu audit sebelum dipakai sebagai dasar navigasi layanan utama.

## Struktur Layanan Menurut Profil RS

Berdasarkan profil RS, layanan dikelompokkan lebih luas:

### 1. Layanan Rawat Jalan Spesialis dan Subspesialis

Contoh:

- Klinik Kesehatan Anak
- Klinik Tumbuh Kembang
- Klinik Jantung Anak
- Klinik Bedah Umum
- Klinik Bedah Digesti
- Klinik Bedah Onkologi
- Klinik Bedah Saraf
- Klinik Bedah Anak
- Klinik Bedah Kepala Leher
- Klinik Ortopedi
- Klinik Urologi
- Klinik Penyakit Dalam
- Klinik Ginjal dan Hipertensi
- Klinik Jantung dan Pembuluh Darah
- Klinik Hemato-Onkologi
- Klinik Paru dan Respirasi
- Klinik Obstetri dan Ginekologi
- Klinik Ginekologi Onkologi
- Klinik Neurologi/Saraf
- Klinik Psikiatri/Kesehatan Jiwa
- Klinik Mata
- Klinik THT-KL
- Klinik Dermatologi, Venereologi, dan Estetika
- Klinik Kedokteran Fisik dan Rehabilitasi

### 2. Layanan Gigi dan Gigi Spesialis

- Klinik Gigi dan Mulut
- Klinik Bedah Mulut
- Klinik Konservasi Gigi
- Klinik Konservasi Gigi Anak
- Klinik Periodonsia
- Klinik Ortodonsia

### 3. Layanan Rawat Jalan Lainnya

- Klinik Nyeri Terpadu
- Klinik Gemphita / VCT-PITC HIV/AIDS
- Klinik TB-DOTS
- Klinik Psikologi Klinis
- Klinik Gizi
- Klinik Vaksin
- Medical Check Up (MCU)
- Klinik Berhenti Merokok
- Klinik Keluarga Sakinah
- Klinik Laktasi
- Ayna Skin Care
- One Day Care
- Home Care
- Extramural & Medical Event Support

### 4. Bedah Sentral

- Minimal invasive surgery
- C-Arm
- Endoscopy

### 5. Centre of Excellence

- Cancer Centre
  - Bedah Onkologi
  - Ginekologi Onkologi
  - Hemato-Onkologi
  - Layanan Tumor Paru
  - Layanan Tumor Otak
  - Patologi Anatomi

### 6. Penunjang Medis

- Rehabilitasi Medis
  - Fisioterapi dewasa
  - Fisioterapi anak
  - Terapi Wicara
  - Terapi Okupasi
- Laboratorium Medis
  - Patologi Klinik
  - Patologi Anatomi
  - Mikrobiologi Klinik
  - Bank Darah

### 7. Dokter

- Dokter Spesialis dan Subspesialis
- Dokter Gigi dan Gigi Spesialis
- Dokter Umum

### 8. Fasilitas Umum dan Rawat Inap

- Kelas VIP
- Kelas VVIP
- Ambulans
- Taman
- Fasilitas pendukung pasien dan keluarga

## Gap Analysis

### Gap 1: Makna "Layanan" berbeda antara profil dan website

Di profil, "layanan" berarti seluruh pelayanan RS. Di website, `layanan` hanya berisi 15 item unggulan/penunjang.

Dampak:

- Halaman layanan terlihat tidak lengkap.
- Pengunjung bisa tidak menemukan klinik spesialis dari halaman layanan.
- Copy seperti "layanan tersedia" berpotensi misleading jika angka hanya menghitung CPT `layanan`.

### Gap 2: Data medis utama tersebar di `poliklinik`, bukan `layanan`

Klinik spesialis, gigi, psikologi, radiologi, patologi, rehabilitasi, dan terapi ada di `poliklinik`.

Dampak:

- Perlu desain navigasi lintas CPT.
- Tidak cukup memperbaiki copy di archive `layanan`.
- Perlu definisi: `layanan` sebagai data konten, atau "Layanan" sebagai landing page agregasi.

### Gap 3: Kategori layanan terlalu sempit

Taxonomy `kategori-layanan` hanya memiliki:

- Layanan Unggulan
- Layanan Penunjang

Dampak:

- Tidak bisa merepresentasikan kategori profil seperti Klinik Spesialis, Gigi & Mulut, Pemeriksaan & Konsultasi, Bedah Sentral, Rawat Inap & Fasilitas.
- Frontend harus membuat grouping manual kalau tidak ada perubahan taxonomy.

### Gap 4: Duplikasi taxonomy spesialisasi dokter

Istilah spesialisasi dokter tidak konsisten.

Dampak:

- Filter dokter bisa membingungkan.
- Jadwal dokter bisa tersebar di label yang mirip.
- SEO halaman taxonomy bisa dobel dan lemah.

### Gap 5: Beberapa item profil belum jelas padanan datanya

Perlu validasi manual untuk item berikut:

| Item profil | Status di existing | Catatan |
| --- | --- | --- |
| Cancer Centre | Tidak terlihat sebagai `layanan` publish | Ada komponen layanannya sebagai poliklinik, tetapi pusat layanan belum eksplisit |
| Bedah Sentral | Tidak terlihat sebagai `layanan` publish | Perlu cek apakah masuk konten halaman lain |
| Minimal invasive surgery | Tidak terlihat sebagai item sendiri | Bisa menjadi detail Bedah Sentral |
| C-Arm | Tidak terlihat sebagai item sendiri | Bisa menjadi fasilitas/tindakan, bukan layanan publik |
| Endoscopy | Tidak terlihat sebagai item sendiri | Perlu validasi apakah perlu halaman layanan |
| Bank Darah | Tidak terlihat sebagai `layanan` publish | Profil memasukkan di Laboratorium Medis |
| Mikrobiologi Klinik | Tidak terlihat sebagai item layanan publish | Ada taxonomy spesialisasi/dokter kemungkinan terkait |
| Klinik Nyeri Terpadu | Tidak terlihat jelas | Perlu cek data admin/live |
| TB-DOTS | Tidak terlihat jelas | Perlu cek data admin/live |
| Klinik Berhenti Merokok | Tidak terlihat jelas | Perlu cek data admin/live |
| Klinik Keluarga Sakinah | Tidak terlihat jelas | Perlu cek data admin/live |
| Klinik Laktasi | Tidak terlihat jelas | Perlu cek data admin/live |
| One Day Care | Tidak terlihat jelas | Perlu cek data admin/live |

## Rekomendasi Model Informasi

### Prinsip

Jangan migrasi data besar dulu. Risiko tinggi karena `dokter`, `spesialisasi-dokter`, dan jadwal dokter saling terkait. Solusi paling aman: jadikan halaman "Layanan" sebagai landing page agregasi yang mengambil data dari beberapa sumber existing.

### Struktur publik yang direkomendasikan

Menu/halaman "Layanan" sebaiknya menjadi payung:

1. Klinik Spesialis
2. Gigi & Mulut
3. Pemeriksaan & Konsultasi
4. Layanan Unggulan
5. Penunjang Medis
6. Rawat Inap & Fasilitas
7. Home Care & Layanan Luar RS

### Mapping data source

| Kategori publik | Sumber data utama | Catatan |
| --- | --- | --- |
| Klinik Spesialis | `poliklinik` | Ambil klinik medis umum dan subspesialis |
| Gigi & Mulut | `poliklinik` + `layanan` Dental Clinic | Hindari dobel; tentukan satu canonical page |
| Pemeriksaan & Konsultasi | `poliklinik` + `layanan` | Psikologi, Gizi, MCU, Vaksin, TB-DOTS, dll. |
| Layanan Unggulan | `layanan` kategori Layanan Unggulan | Existing sudah ada |
| Penunjang Medis | `layanan` kategori Layanan Penunjang + sebagian `poliklinik` | Radiologi, Lab, Farmasi, Rehabilitasi |
| Rawat Inap & Fasilitas | `rawat-inap` + halaman fasilitas | Jangan campur ke layanan medis |
| Home Care & Layanan Luar RS | `layanan` + `poliklinik` Klinik Home Care | Perlu pilih canonical |

## Rekomendasi Data Cleanup

### Prioritas 1: Definisikan ulang halaman "Layanan"

Keputusan yang disarankan:

- Label publik "Layanan" = umbrella page/agregasi.
- CPT `layanan` tetap dipakai sebagai item unggulan/penunjang.
- Jangan ubah slug CPT dulu.

Alasan:

- Minim risiko.
- Tidak merusak URL existing `/layanan/...`.
- Lebih cocok dengan profil RS.

### Prioritas 2: Buat grouping frontend tanpa migrasi data besar

Kelompokkan data berdasarkan pola nama dan/atau curated list:

- `Klinik Bedah*`, `Klinik Penyakit*`, `Klinik Jantung*`, dll. masuk Klinik Spesialis.
- `Klinik Gigi*`, `Klinik Bedah Mulut` masuk Gigi & Mulut.
- `Klinik Psikologi Klinis`, `Gizi`, `MCU`, `Vaksin Center` masuk Pemeriksaan & Konsultasi.
- Kategori `Layanan Unggulan` tetap ditampilkan, tetapi hilangkan item dobel kalau sudah muncul di kategori lain.

### Prioritas 3: Audit canonical page untuk item dobel

Item butuh keputusan canonical:

| Item | Kandidat sumber | Rekomendasi |
| --- | --- | --- |
| Dental Clinic | `layanan` + banyak `poliklinik` gigi | Jadikan `poliklinik` gigi untuk detail dokter; `Dental Clinic` sebagai landing/overview jika kontennya kuat |
| Fisioterapi | `layanan` + `poliklinik` Rehabilitasi Medis/Terapi | Jadikan Rehabilitasi Medis canonical; Fisioterapi sebagai layanan penunjang/detail |
| Home Care | `layanan` + `poliklinik` Klinik Home Care | Pilih satu canonical, redirect/CTA dari lainnya |
| Gizi | `layanan` + profil Klinik Gizi | Jika ada dokter/jadwal, masuk Klinik/Pemeriksaan; jika edukasi/support, masuk Penunjang |
| Radiologi | `layanan` + `poliklinik` Klinik USG dan Radiologi | Radiologi sebagai Penunjang; Klinik USG/Radiologi sebagai booking/doctor route jika ada |

### Prioritas 4: Bersihkan taxonomy `spesialisasi-dokter`

Lakukan setelah backup relasi dokter dan jadwal.

Usulan canonical:

| Canonical | Merge dari |
| --- | --- |
| Paru | Penyakit Paru |
| Saraf | Penyakit Saraf |
| THT | Penyakit THT, Telinga Hidung Tenggorokan |
| Mata | Penyakit Mata |
| Kesehatan Jiwa | Jiwa |
| Kulit dan Kelamin | Kulit & Kelamin |
| Obstetri dan Ginekologi | Kandungan, Obsgyn / Kebidanan & Kandungan |
| Anak | Penyakit Anak Imunisasi & Tumbuh Kembang Anak jika diputuskan bukan kategori klinik |

Catatan: merge taxonomy berisiko mempengaruhi filter dokter/jadwal. Perlu backup dan verifikasi jumlah dokter per term sebelum dan sesudah.

## Rekomendasi UX Writing Setelah Data Dibenahi

### Hero Layanan

Usulan copy:

> Temukan layanan RS PKU Muhammadiyah Yogyakarta sesuai kebutuhan Anda, mulai dari klinik spesialis, pemeriksaan penunjang, rawat inap, hingga layanan pendukung pasien dan keluarga.

### Label kategori

Gunakan label publik berikut:

- Klinik Spesialis
- Gigi & Mulut
- Pemeriksaan & Konsultasi
- Layanan Unggulan
- Penunjang Medis
- Rawat Inap & Fasilitas
- Home Care & Layanan Luar RS

### Microcopy pencarian/filter

> Cari layanan, klinik, atau pemeriksaan yang Anda butuhkan.

> Beberapa layanan memerlukan jadwal dokter atau konfirmasi terlebih dahulu. Silakan cek detail layanan sebelum berkunjung.

## Risiko Jika Hanya Copy Diperbaiki

Jika hanya mengganti teks tanpa memperbaiki struktur data/navigasi:

- Halaman tetap terlihat tidak lengkap.
- Pasien tetap sulit menemukan klinik spesialis.
- Jumlah layanan tetap misleading.
- Item ganda tetap membingungkan.
- Filter dokter dan jadwal tetap terdampak duplikasi spesialisasi.

## Rekomendasi Keputusan Produk

### Keputusan 1

Apakah "Layanan" di website akan menjadi:

A. Archive CPT `layanan` saja.
B. Landing page agregasi semua layanan RS.

Rekomendasi: **B. Landing page agregasi semua layanan RS.**

### Keputusan 2

Apakah perlu migrasi data sekarang?

Rekomendasi: **Tidak untuk fase awal.** Gunakan agregasi frontend + curated grouping dulu.

### Keputusan 3

Apakah duplikasi spesialisasi dokter dibersihkan sekarang?

Rekomendasi: **Ya, tetapi sebagai task terpisah dengan backup dan verifikasi.** Jangan digabung dengan redesign halaman layanan.

## Roadmap Perbaikan Singkat

### Fase 1: Audit dan desain informasi

- Tetapkan definisi "Layanan" sebagai umbrella.
- Finalisasi kategori publik.
- Buat mapping `poliklinik`/`layanan`/`rawat-inap` ke kategori publik.
- Tentukan canonical page untuk item dobel.

### Fase 2: Implementasi halaman layanan agregasi

- Ubah halaman `/layanan/` menjadi landing page agregasi.
- Tampilkan kategori publik.
- Gabungkan data dari `poliklinik`, `layanan`, dan `rawat-inap` sesuai mapping.
- Hindari menampilkan item dobel.

### Fase 3: Cleanup taxonomy dokter

- Backup relasi term dokter.
- Merge duplicate terms.
- Verifikasi filter dokter.
- Verifikasi jadwal dokter.

### Fase 4: Tambah data yang belum ada

- Cancer Centre
- Bedah Sentral
- Endoscopy
- Bank Darah
- Mikrobiologi Klinik
- TB-DOTS
- Klinik Nyeri Terpadu
- Klinik Laktasi
- One Day Care
- Layanan lain yang dikonfirmasi tim RS.

## Kesimpulan

Masalah layanan di website adalah gabungan data architecture, information architecture, dan UX writing. Struktur paling aman adalah menjadikan "Layanan" sebagai halaman payung yang mengagregasi beberapa tipe data existing, bukan memaksa semua hal masuk ke CPT `layanan`.

Pendekatan ini mengurangi risiko migrasi, mempertahankan URL existing, dan lebih selaras dengan profil resmi RS yang menampilkan layanan sebagai ekosistem besar: klinik, dokter, penunjang, rawat inap, layanan luar RS, dan fasilitas pasien.
