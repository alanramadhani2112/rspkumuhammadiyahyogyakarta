# Audit Source 2026 vs Website WordPress Lokal

Tanggal audit: 2026-08-14

## Executive Summary

Audit read-only membandingkan dua file Markdown source-of-truth Profil RS PKU Muhammadiyah Yogyakarta 2026 dengan post published WordPress lokal. Tidak ada mutasi WordPress, import, update post, update taxonomy, deploy, atau commit.

- Source dokter: 126 record. WordPress published `dokter`: 100 record. Klasifikasi source dokter: match 92, possible-match 2, missing 25, editorial-review 7.
- Source layanan ter-audit: 104 record, terdiri dari 92 numbered items dan 12 heading-only service/facility rows. WordPress published layanan gabungan: `layanan` 15, `poliklinik` 37, `rawat-inap` 1. Klasifikasi source layanan: match 37, possible-match 4, missing 57, editorial-review 6.
- Temuan utama: gap terbesar bukan sekadar konten hilang, tetapi perbedaan model. Source 2026 memakai layanan sebagai payung seluruh pelayanan RS; website memecahnya menjadi `layanan`, `poliklinik`, `rawat-inap`, dokter memakai `dokter` plus `spesialisasi-dokter`.
- Banyak possible-match perlu validasi manusia karena judul website memakai gelar, singkatan, ejaan, atau istilah publik berbeda dari source. Report ini sengaja tidak mengklaim mismatch pasti saat bukti belum kuat.

## Methodology and Limitations

- Source dokter dibaca dari `C:\Users\LENOVO\Downloads\data-dokter-rs-pku-muhammadiyah-yogyakarta(1).md`. Source layanan dibaca dari `C:\Users\LENOVO\Downloads\layanan-medis-rs-pku-muhammadiyah-yogyakarta.md`.
- WordPress dibaca read-only melalui `wp-load.php` dari root `C:\laragon\www\rspkudev`, memakai `WP_Query` untuk post status `publish` pada CPT `dokter`, `layanan`, `poliklinik`, `rawat-inap`, plus `get_terms()` untuk `spesialisasi-dokter` dan `kategori-layanan`.
- Matching memakai normalisasi huruf kecil, tanda baca, beberapa sinonim Indonesia/Inggris, ejaan `Syaraf/Saraf`, `Orthopedi/Ortopedi`, dan singkatan umum. Gelar dokter tidak dianggap pembeda mutlak.
- Classification berarti: `match` = key nama/judul sangat kuat; `possible-match` = kemungkinan sama tetapi butuh konfirmasi editorial; `missing` = belum ditemukan padanan kuat; `editorial-review` = source atau padanan mengandung isu ejaan/terminologi/struktur yang harus divalidasi dulu.
- Limitasi: tidak membaca draft/private post, ACF detail field, media, jadwal dokter, halaman statis, menu, atau konten di luar CPT target. Tidak membuka DB credential dalam report. Tidak ada klaim klinis final.

## Counts

| Area | Source count | WordPress published count | Match | Possible-match | Missing | Editorial-review |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Dokter | 126 | 100 `dokter` | 92 | 2 | 25 | 7 |
| Layanan | 104 | 15 `layanan` + 37 `poliklinik` + 1 `rawat-inap` | 37 | 4 | 57 | 6 |
| Taxonomy observations | n/a | 48 `spesialisasi-dokter`, 2 `kategori-layanan` | n/a | n/a | n/a | n/a |

## WordPress Taxonomy Observations

### `spesialisasi-dokter`
- Anak (6)
- Anestesiologi (1)
- Bedah (16)
- Bedah Anak (2)
- Bedah Digestif (1)
- Bedah Mulut (2)
- Bedah Onkologi (1)
- Bedah Saraf (2)
- Bedah Tulang (Orthopedi) (3)
- Bedah Umum (4)
- Bedah Urologi (3)
- Dokter Umum / Instalasi Gawat Darurat (IGD) (20)
- Gigi (8)
- Gigi Endodonsi (2)
- Gigi Ortodonti (1)
- Gigi Pedodonti (1)
- Gigi Periodonti (3)
- Gigi Umum (1)
- Ginekologi Onkologi (1)
- Ginjal Hipertensi (1)
- Hemato Onkologi (1)
- Jantung (3)
- Jiwa (5)
- Kandungan (4)
- Kedokteran Gigi (1)
- Kesehatan Jiwa (2)
- Kulit &amp; Kelamin (3)
- Kulit dan Kelamin (3)
- Mata (1)
- Obsgyn / Kebidanan &amp; Kandungan (3)
- Paru (4)
- Patologi Anatomi (2)
- Patologi Klinik (3)
- Penunjang &amp; Terapi (9)
- Penyakit Anak Imunisasi &amp; Tumbuh Kembang Anak (3)
- Penyakit Dalam (9)
- Penyakit Jantung Anak (1)
- Penyakit Jantung dan Pembuluh Darah (3)
- Penyakit Mata (1)
- Penyakit Paru (4)
- Penyakit Saraf (5)
- Penyakit THT (2)
- Psikolog Klinis (3)
- Radiologi (4)
- Rehabilitasi Medis (2)
- Saraf (5)
- Telinga, Hidung, Tenggorokan (1)
- THT (2)

### `kategori-layanan`
- Layanan Penunjang (6)
- Layanan Unggulan (9)

Observation: `kategori-layanan` remains coarse for a source that separates rawat jalan, rawat inap, centre of excellence, emergency/critical care, penunjang medis, CSSD, and layanan unggulan lainnya. Do not solve this by moving everything into `layanan`; first decide public IA across CPTs.

## Doctor Matrix

| Classification | Source name | Source group/type | Current WordPress CPT | Matched existing title | Canonical display recommendation | Recommended action |
| --- | --- | --- | --- | --- | --- | --- |
| match | dr. Mohammad Komarudin, Sp.A. | Klinik Kesehatan Anak / Dokter Spesialis/Subspesialis | dokter | dr. H. Mohammad Komarudin, Sp.A | dr. Mohammad Komarudin, Sp.A. | No content action; optionally standardize display name. |
| match | dr. Muriana Novariani, M.Kes.,Sp.A. | Klinik Kesehatan Anak / Dokter Spesialis/Subspesialis | dokter | dr. Muriana Novariani, Sp.A, M.Kes. | dr. Muriana Novariani, M.Kes.,Sp.A. | No content action; optionally standardize display name. |
| match | dr. Yanantri Binga Ramsif, M.Med.Sc., Sp.A. | Klinik Kesehatan Anak / Dokter Spesialis/Subspesialis | dokter | dr. Yanantri Binga Ramsif, Sp.A, M.Med.Sc. | dr. Yanantri Binga Ramsif, M.Med.Sc., Sp.A. | No content action; optionally standardize display name. |
| editorial-review | Prof. dr. Djauhar Ismail, MPH,Ph.D,Sp.A.(K). | Klinik Tumbuh Kembang / Dokter Spesialis/Subspesialis |  |  | Prof. dr. Djauhar Ismail, MPH,Ph.D,Sp.A.(K). | Validate spelling/credentials before create or match. |
| match | dr. Nadya Arafuri, Sp.A,Subsp.Kardio(K). | Klinik Jantung Anak / Dokter Spesialis/Subspesialis | dokter | dr. Nadya Arafuri, Sp.A, Subsp. Kardio(K) | dr. Nadya Arafuri, Sp.A,Subsp.Kardio(K). | No content action; optionally standardize display name. |
| editorial-review | Dr.dr. Nurnaningsih, Sp.A(K). | Klinik Intensif Anak / Dokter Spesialis/Subspesialis |  |  | Dr.dr. Nurnaningsih, Sp.A(K). | Validate spelling/credentials before create or match. |
| match | dr. Adi Sihono, Sp.B. | Klinik Bedah Umum / Dokter Spesialis/Subspesialis | dokter | dr. H. Adi Sihono, Sp.B | dr. Adi Sihono, Sp.B. | No content action; optionally standardize display name. |
| match | Dr.dr. Sagiran, Sp.B(K)-KL,M.Kes. FICS. | Klinik Bedah Umum / Dokter Spesialis/Subspesialis | dokter | Dr. dr. H. Sagiran, Sp.B(K) KL., M.Kes. | Dr.dr. Sagiran, Sp.B(K)-KL,M.Kes. FICS. | No content action; optionally standardize display name. |
| match | dr. Taufiek Hikmawan Yuliarto Benni Sambada, Sp.B. | Klinik Bedah Umum / Dokter Spesialis/Subspesialis | dokter | dr. Taufiek Hikmawan Y. B. S., Sp.B | dr. Taufiek Hikmawan Yuliarto Benni Sambada, Sp.B. | No content action; optionally standardize display name. |
| match | dr. Arum Linangkung, Sp.B.Subsp.BD(K). | Klinik Bedah Digesti / Dokter Spesialis/Subspesialis | dokter | dr. Arum Linangkung, Sp.B, Subsp. BD(K)., M.Sc. | dr. Arum Linangkung, Sp.B.Subsp.BD(K). | No content action; optionally standardize display name. |
| match | dr. Helmanu Kurniadi, Sp.B,Subsp.Onk.(K) | Klinik Bedah Onkologi / Dokter Spesialis/Subspesialis | dokter | dr. Helmanu Kurniadi, Sp.B, Subsp. Onk(K) | dr. Helmanu Kurniadi, Sp.B,Subsp.Onk.(K) | No content action; optionally standardize display name. |
| match | dr. Rachmat Andi Hartanto, Sp.BS(K). | Klinik Bedah Saraf / Dokter Spesialis/Subspesialis | dokter | Dr. dr. H. Rahmat Andi Hartanto, Sp.BS ( K ), Subsp. N. Onko | dr. Rachmat Andi Hartanto, Sp.BS(K). | No content action; optionally standardize display name. |
| match | dr. Rakyan Artha Dewi Rachmat, Sp.BS. | Klinik Bedah Saraf / Dokter Spesialis/Subspesialis | dokter | dr. Rakyan Artha Dewi Rachmat, Sp.BS | dr. Rakyan Artha Dewi Rachmat, Sp.BS. | No content action; optionally standardize display name. |
| match | Dr.dr. Akhmad Makhmudi, Sp.B,Sp.BA.Subsp.BD(K). | Klinik Bedah Anak / Dokter Spesialis/Subspesialis | dokter | Dr. dr. Akhmad Makhmudi, Sp.B, Sp.BA, Subsp. D.A(K) | Dr.dr. Akhmad Makhmudi, Sp.B,Sp.BA.Subsp.BD(K). | No content action; optionally standardize display name. |
| match | dr. Hafni Zuchra Noor, Sp.BA. | Klinik Bedah Anak / Dokter Spesialis/Subspesialis | dokter | dr. Hafni Zuchra Noor, MM., Sp.BA | dr. Hafni Zuchra Noor, Sp.BA. | No content action; optionally standardize display name. |
| match | dr. Kuncahyo Kamal Arifin, Sp.OT. | Klinik Orthopedi / Dokter Spesialis/Subspesialis | dokter | dr. H. Kuncahyo Kamal Arifin, Sp.OT | dr. Kuncahyo Kamal Arifin, Sp.OT. | No content action; optionally standardize display name. |
| match | dr. Marda Ade Saputra, Sp.OT. | Klinik Orthopedi / Dokter Spesialis/Subspesialis | dokter | dr. Marda Ade Saputra, Sp.OT | dr. Marda Ade Saputra, Sp.OT. | No content action; optionally standardize display name. |
| missing | dr. Muhammad Ariffudin, Sp.OT. | Klinik Orthopedi / Dokter Spesialis/Subspesialis |  |  | dr. Muhammad Ariffudin, Sp.OT. | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Wahyu Setyawan, Sp.OT(K). | Klinik Orthopedi / Dokter Spesialis/Subspesialis | dokter | dr. Wahyu Setyawan, Sp.OT(K) | dr. Wahyu Setyawan, Sp.OT(K). | No content action; optionally standardize display name. |
| match | dr. Ahmad Zulfan Hendri, Sp.U(K). | Klinik Urologi / Dokter Spesialis/Subspesialis | dokter | Dr. dr. Ahmad Zulfan Hendri, Sp.U(K) | dr. Ahmad Zulfan Hendri, Sp.U(K). | No content action; optionally standardize display name. |
| match | dr. Muhammad Anwar Irzan, Sp.U. | Klinik Urologi / Dokter Spesialis/Subspesialis | dokter | dr. Muhammad Anwar Irzan, Sp.U | dr. Muhammad Anwar Irzan, Sp.U. | No content action; optionally standardize display name. |
| match | dr. Wikan Kurniawan, Sp.U. | Klinik Urologi / Dokter Spesialis/Subspesialis | dokter | dr. Wikan Kurniawan, Sp.U | dr. Wikan Kurniawan, Sp.U. | No content action; optionally standardize display name. |
| match | dr. Dandy Firmansyah, Sp.PD. | Klinik Penyakit Dalam / Dokter Spesialis/Subspesialis | dokter | dr. Dandy Firmansyah, Sp.PD, AIFO-K | dr. Dandy Firmansyah, Sp.PD. | No content action; optionally standardize display name. |
| match | dr. Maya Um Husna, Sp.PD, FINASIM | Klinik Penyakit Dalam / Dokter Spesialis/Subspesialis | dokter | dr. Maya Um Husna, Sp.PD | dr. Maya Um Husna, Sp.PD, FINASIM | No content action; optionally standardize display name. |
| match | dr. Mohamad Wibowo, Sp.PD | Klinik Penyakit Dalam / Dokter Spesialis/Subspesialis | dokter | dr. H. Mohamad Wibowo, Sp.PD | dr. Mohamad Wibowo, Sp.PD | No content action; optionally standardize display name. |
| match | dr. Muhammad Iqbal,Sp.PD,M.Kes. FINASIM. | Klinik Penyakit Dalam / Dokter Spesialis/Subspesialis | dokter | dr. H. Muhammad Iqbal, Sp.PD., M.Kes. | dr. Muhammad Iqbal,Sp.PD,M.Kes. FINASIM. | No content action; optionally standardize display name. |
| match | dr. Niarna Lusi, Sp.PD. | Klinik Penyakit Dalam / Dokter Spesialis/Subspesialis | dokter | dr. Hj. Niarna Lusi, Sp.PD | dr. Niarna Lusi, Sp.PD. | No content action; optionally standardize display name. |
| match | dr. Riska Amalia Ambarwati, Sp.PD,FINASIM. | Klinik Penyakit Dalam / Dokter Spesialis/Subspesialis | dokter | dr. Riska Amalia Ambarwati, Sp.PD | dr. Riska Amalia Ambarwati, Sp.PD,FINASIM. | No content action; optionally standardize display name. |
| match | dr. Sisca Wulandari, Sp.PD. | Klinik Penyakit Dalam / Dokter Spesialis/Subspesialis | dokter | dr. Sisca Wulandari, Sp.PD | dr. Sisca Wulandari, Sp.PD. | No content action; optionally standardize display name. |
| match | dr. Barkah Djaka Purwanto, Sp.PD-KGH, FINASIM. | Klinik Ginjal dan Hipertensi / Dokter Spesialis/Subspesialis | dokter | dr. H. Barkah Djaka Purwanto, SpPD-KGH, SubSp GH (K), FINASIM | dr. Barkah Djaka Purwanto, Sp.PD-KGH, FINASIM. | No content action; optionally standardize display name. |
| editorial-review | dr. Iri Kuswadi, Sp.PD-KGH,FINASIM. | Klinik Ginjal dan Hipertensi / Dokter Spesialis/Subspesialis |  |  | dr. Iri Kuswadi, Sp.PD-KGH,FINASIM. | Validate spelling/credentials before create or match. |
| match | dr. Evita Devi Noor Rahmawati, Sp.JP (K), FIHA. | Klinik Jantung dan Pembuluh Darah / Dokter Spesialis/Subspesialis | dokter | dr. Evita Devi Noor Rahmawati, Sp.JP(K) | dr. Evita Devi Noor Rahmawati, Sp.JP (K), FIHA. | No content action; optionally standardize display name. |
| missing | dr. Mutiara Putri, Sp.JP | Klinik Jantung dan Pembuluh Darah / Dokter Spesialis/Subspesialis |  |  | dr. Mutiara Putri, Sp.JP | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Rano Irmawan, Sp.JP. | Klinik Jantung dan Pembuluh Darah / Dokter Spesialis/Subspesialis | dokter | dr. Rano Irmawan, Sp.JP | dr. Rano Irmawan, Sp.JP. | No content action; optionally standardize display name. |
| match | dr. Mardiah Suci Hardianti, Ph.D, Sp.PD(KHOM). | Klinik Hemato-Onkologi / Dokter Spesialis/Subspesialis | dokter | dr. Mardiah Suci Hardianti, Sp.PD-KHOM, Ph.D. | dr. Mardiah Suci Hardianti, Ph.D, Sp.PD(KHOM). | No content action; optionally standardize display name. |
| match | dr. Brian Prima Artha, Sp.OG, Subsp. Onk. | Klinik Ginekologi Onkologi / Dokter Spesialis/Subspesialis | dokter | dr. Brian Prima Artha, Sp.OG(K)-Onk | dr. Brian Prima Artha, Sp.OG, Subsp. Onk. | No content action; optionally standardize display name. |
| match | dr. Ardorisye Saptaty Fornia, Sp.P., M.Kes,FISR. | Klinik Paru dan Respirasi / Dokter Spesialis/Subspesialis | dokter | dr. Ardorisye Saptati Fornia, Sp.P., M.Kes. | dr. Ardorisye Saptaty Fornia, Sp.P., M.Kes,FISR. | No content action; optionally standardize display name. |
| match | dr. Munawar Gani, Sp. P. | Klinik Paru dan Respirasi / Dokter Spesialis/Subspesialis | dokter | dr. H. Munawar Gani, Sp.P(K) | dr. Munawar Gani, Sp. P. | No content action; optionally standardize display name. |
| match | dr. Ramaniya Kirana, Sp.P. | Klinik Paru dan Respirasi / Dokter Spesialis/Subspesialis | dokter | dr. Ramaniya Kirana, Sp.P | dr. Ramaniya Kirana, Sp.P. | No content action; optionally standardize display name. |
| possible-match | dr. Yusrizal Djam'an Saleh, Sp.P(K). | Klinik Paru dan Respirasi / Dokter Spesialis/Subspesialis | dokter | dr. H. Yusrizal Djam&#8217;an Shaleh, Sp.P(K), FCCP, FISR | dr. Yusrizal Djam'an Saleh, Sp.P(K). | Human confirm same doctor; then keep existing CPT and adjust canonical display if needed. |
| match | dr. Anis Widyasari, Sp.OG. | Klinik Obstetri & Ginekologi / Dokter Spesialis/Subspesialis | dokter | dr. Anis Widyasari, Sp.OG, Subsp. Urogin-RE | dr. Anis Widyasari, Sp.OG. | No content action; optionally standardize display name. |
| match | dr. Khairina Hashifah, Sp.OG. | Klinik Obstetri & Ginekologi / Dokter Spesialis/Subspesialis | dokter | dr. Khairina Hashifah, Sp.OG | dr. Khairina Hashifah, Sp.OG. | No content action; optionally standardize display name. |
| match | dr. Sulistiari Retnowati, Sp.OG. | Klinik Obstetri & Ginekologi / Dokter Spesialis/Subspesialis | dokter | dr. Sulistiari Retnowati, Sp.OG | dr. Sulistiari Retnowati, Sp.OG. | No content action; optionally standardize display name. |
| match | dr. Zamroni, Sp.N. | Klinik Neurologi/Saraf / Dokter Spesialis/Subspesialis | dokter | dr. H. Zamroni, Sp.N | dr. Zamroni, Sp.N. | No content action; optionally standardize display name. |
| match | dr. Andrianto Selohandono, MSc,Sp.N. | Klinik Neurologi/Saraf / Dokter Spesialis/Subspesialis | dokter | dr. Andrianto Selohandono, Sp.N M.Sc. | dr. Andrianto Selohandono, MSc,Sp.N. | No content action; optionally standardize display name. |
| match | dr. Muhammad Arif Budi Prakoso, MMR., Sp.N | Klinik Neurologi/Saraf / Dokter Spesialis/Subspesialis | dokter | dr. Arif Budi Prakoso, Sp.N, MMR | dr. Muhammad Arif Budi Prakoso, MMR., Sp.N | No content action; optionally standardize display name. |
| match | dr. Sekar Satiti, Sp.S. | Klinik Neurologi/Saraf / Dokter Spesialis/Subspesialis | dokter | dr. Hj. Sekar Satiti, Sp.N, Subsp. N.K.I(K) | dr. Sekar Satiti, Sp.S. | No content action; optionally standardize display name. |
| match | Dr.dr. Tri Wahyuliati, Sp.S,M.Kes. | Klinik Neurologi/Saraf / Dokter Spesialis/Subspesialis | dokter | Dr. dr. Hj. Tri Wahyuliati, Sp.N, M.Kes. | Dr.dr. Tri Wahyuliati, Sp.S,M.Kes. | No content action; optionally standardize display name. |
| match | Dr.dr. Budi Pratiti, Sp.KJ. | Klinik Psikiatri/Kesehatan Jiwa / Dokter Spesialis/Subspesialis | dokter | dr. Hj. Budi Pratiti, Sp.KJ | Dr.dr. Budi Pratiti, Sp.KJ. | No content action; optionally standardize display name. |
| match | dr. Windy Aristiani, MMR,Sp.KJ. | Klinik Psikiatri/Kesehatan Jiwa / Dokter Spesialis/Subspesialis | dokter | dr. Windy Aristiani, MMR, Sp.KJ | dr. Windy Aristiani, MMR,Sp.KJ. | No content action; optionally standardize display name. |
| match | dr. Aufaa Shafira Widowati, Sp.M. | Klinik Mata / Dokter Spesialis/Subspesialis | dokter | dr. Aufaa Shafira Widowati, Sp.M | dr. Aufaa Shafira Widowati, Sp.M. | No content action; optionally standardize display name. |
| missing | dr. Yosylina Pramudya Wardhani, Sp. M. | Klinik Mata / Dokter Spesialis/Subspesialis |  |  | dr. Yosylina Pramudya Wardhani, Sp. M. | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Adnan Abdullah, Sp.THT-KL., M.Kes | Klinik Telinga, Hidung, Tenggorokan, dan Kepala Leher / Dokter Spesialis/Subspesialis | dokter | dr. H. Adnan Abdullah, Sp.THT-KL, M.Kes. | dr. Adnan Abdullah, Sp.THT-KL., M.Kes | No content action; optionally standardize display name. |
| match | dr. Aras Amila Husna, Sp.THTBKL | Klinik Telinga, Hidung, Tenggorokan, dan Kepala Leher / Dokter Spesialis/Subspesialis | dokter | dr. Aras Amila Husna, Sp.T.H.T.B.K.L | dr. Aras Amila Husna, Sp.THTBKL | No content action; optionally standardize display name. |
| match | dr. Deoni Daniswara, Sp.THT-KL | Klinik Telinga, Hidung, Tenggorokan, dan Kepala Leher / Dokter Spesialis/Subspesialis | dokter | dr. Deoni Daniswara, Sp.THT-KL | dr. Deoni Daniswara, Sp.THT-KL | No content action; optionally standardize display name. |
| match | dr. Ayu Wikan Sayekti, MSc,Sp.DVE. | Klinik Dermatologi, Venereologi, dan Estetika / Dokter Spesialis/Subspesialis | dokter | dr. Ayu Wikan Sayekti, Sp.DVE, M.Sc. | dr. Ayu Wikan Sayekti, MSc,Sp.DVE. | No content action; optionally standardize display name. |
| possible-match | dr. Nafiah Chusniyati, M.Sc, Sp. DVE, FINSDV, FAADV. | Klinik Dermatologi, Venereologi, dan Estetika / Dokter Spesialis/Subspesialis | dokter | dr. Hj. Nafiah Chusniati, Sp.DVE, M.Sc. | dr. Nafiah Chusniyati, M.Sc, Sp. DVE, FINSDV, FAADV. | Human confirm same doctor; then keep existing CPT and adjust canonical display if needed. |
| match | dr. Siti Aminah Tri Susila Estri, M.Kes.,Sp.DVE. | Klinik Dermatologi, Venereologi, dan Estetika / Dokter Spesialis/Subspesialis | dokter | dr. Siti Aminah, Sp.DVE, M.Kes. | dr. Siti Aminah Tri Susila Estri, M.Kes.,Sp.DVE. | No content action; optionally standardize display name. |
| match | dr. Petrina Theda Philothra , M.Ked.Klin., Sp.KFR. | Klinik Kesehatan Fisik & Rehabilitasi / Dokter Spesialis/Subspesialis | dokter | dr. Petrina Theda Philotra, Sp.KFR, M.Med.Klin. | dr. Petrina Theda Philothra , M.Ked.Klin., Sp.KFR. | No content action; optionally standardize display name. |
| match | dr. Pujiatun, Sp.KFR | Klinik Kesehatan Fisik & Rehabilitasi / Dokter Spesialis/Subspesialis | dokter | dr. Hj. Pujiatun, Sp.KFR | dr. Pujiatun, Sp.KFR | No content action; optionally standardize display name. |
| match | drg. Winda Susra | Klinik Dokter Gigi / Dokter Gigi/Gigi Spesialis | dokter | drg. Winda Susra | drg. Winda Susra | No content action; optionally standardize display name. |
| missing | drg. Shavira Amanda Muhandri, Sp.KGA | Klinik Konservasi Gigi Anak / Dokter Gigi/Gigi Spesialis |  |  | drg. Shavira Amanda Muhandri, Sp.KGA | Create/update published `dokter` after HR/medical secretary validation. |
| match | drg. Siti Rahayu, Sp.KGA. | Klinik Konservasi Gigi Anak / Dokter Gigi/Gigi Spesialis | dokter | drg. Hj. Siti Rahayu, Sp.KGA | drg. Siti Rahayu, Sp.KGA. | No content action; optionally standardize display name. |
| match | drg. Indria Nehriasari, M.Kes., Sp.BM. | Klinik Bedah Mulut / Dokter Gigi/Gigi Spesialis | dokter | drg. Hj. Indria Nehriasari, Sp.BM, M.Kes | drg. Indria Nehriasari, M.Kes., Sp.BM. | No content action; optionally standardize display name. |
| match | drg. Mochammad Agus Artono, Sp.BM | Klinik Bedah Mulut / Dokter Gigi/Gigi Spesialis | dokter | drg. Mochamad Agus Artono, Sp.BM | drg. Mochammad Agus Artono, Sp.BM | No content action; optionally standardize display name. |
| match | drg. Ratih Andini, Sp.KG. | Klinik Konservasi Gigi / Dokter Gigi/Gigi Spesialis | dokter | drg. Ratih Andini, Sp.KG | drg. Ratih Andini, Sp.KG. | No content action; optionally standardize display name. |
| match | drg. Yuninda Lintang Damaranti, Sp.KG. | Klinik Konservasi Gigi / Dokter Gigi/Gigi Spesialis | dokter | drg. Yuninda Lintang, Sp. KG | drg. Yuninda Lintang Damaranti, Sp.KG. | No content action; optionally standardize display name. |
| match | drg. Zarah Himawaty, Sp.KG. | Klinik Konservasi Gigi / Dokter Gigi/Gigi Spesialis | dokter | drg. Hj. Zarah Himawaty, Sp.KG | drg. Zarah Himawaty, Sp.KG. | No content action; optionally standardize display name. |
| match | drg. Amalia Perwitasari, Sp.Perio. | Klinik Periodonsia / Dokter Gigi/Gigi Spesialis | dokter | drg. Amalia Perwitasari, Sp.Perio | drg. Amalia Perwitasari, Sp.Perio. | No content action; optionally standardize display name. |
| match | Dr.drg. Dahlia Herawati,SU,Sp.Per.(K). | Klinik Periodonsia / Dokter Gigi/Gigi Spesialis | dokter | Dr. dr. Hj. Dahlia Herawati, Sp.Perio(K) | Dr.drg. Dahlia Herawati,SU,Sp.Per.(K). | No content action; optionally standardize display name. |
| match | Dr. drg. Ika Andriani, Sp.Perio., MDSc. | Klinik Periodonsia / Dokter Gigi/Gigi Spesialis | dokter | Dr. drg. Hj. Ika Andriani, Sp.Perio., M.DSc. | Dr. drg. Ika Andriani, Sp.Perio., MDSc. | No content action; optionally standardize display name. |
| match | drg. Pipiet Setyaningsih, Sp.Ort.,MPH. | Klinik Ortodonsia / Dokter Gigi/Gigi Spesialis | dokter | drg. Hj. Pipiet Setyaningsih, Sp.Ort., MPH | drg. Pipiet Setyaningsih, Sp.Ort.,MPH. | No content action; optionally standardize display name. |
| editorial-review | drg.Muhammad Sulchan Ardiansyah,Sp.Ort. | Klinik Ortodonsia / Dokter Gigi/Gigi Spesialis |  |  | drg.Muhammad Sulchan Ardiansyah,Sp.Ort. | Validate spelling/credentials before create or match. |
| editorial-review | dr. Akhmad Yun Jufan, Sp.An,MSc(KIC). | Dokter Spesialis Anestesi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Akhmad Yun Jufan, Sp.An,MSc(KIC). | Validate spelling/credentials before create or match. |
| missing | dr. Dhanty Dwita Sari, Sp.An-TI. | Dokter Spesialis Anestesi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Dhanty Dwita Sari, Sp.An-TI. | Create/update published `dokter` after HR/medical secretary validation. |
| missing | dr. Hendi Prihatna, Sp.An. | Dokter Spesialis Anestesi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Hendi Prihatna, Sp.An. | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Joko Murdiyanto, Sp.An.,MPH. | Dokter Spesialis Anestesi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Joko Murdiyanto, sp.An., MPH | dr. Joko Murdiyanto, Sp.An.,MPH. | No content action; optionally standardize display name. |
| editorial-review | dr. Mahmud,Sp, An(KMN), MSc,FIPM. | Dokter Spesialis Anestesi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Mahmud,Sp, An(KMN), MSc,FIPM. | Validate spelling/credentials before create or match. |
| missing | dr. Pandit Sarosa Hadisajoga, Sp.An(K). | Dokter Spesialis Anestesi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Pandit Sarosa Hadisajoga, Sp.An(K). | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Fitria Puspita Dewi, MMR, Sp.PA. | Dokter Spesialis Patologi Anatomi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Fitria Puspita Dewi, MMR, Sp.PA | dr. Fitria Puspita Dewi, MMR, Sp.PA. | No content action; optionally standardize display name. |
| match | dr. Safiqulatif Abdillah, MMR., Sp.PA. | Dokter Spesialis Patologi Anatomi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Safiqulatif Abdillah, MMR, Sp.PA | dr. Safiqulatif Abdillah, MMR., Sp.PA. | No content action; optionally standardize display name. |
| match | dr. Amanatus Solikhah, MSc, Sp.PK. | Dokter Spesialis Laboratorium / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Amanatus Solikhah, M.Sc., Sp.PK | dr. Amanatus Solikhah, MSc, Sp.PK. | No content action; optionally standardize display name. |
| editorial-review | Dr.dr. Andaru Dahesihdewi, Sp.PK(K) M.Kes. | Dokter Spesialis Laboratorium / Dokter Spesialis Penunjang/Lainnya |  |  | Dr.dr. Andaru Dahesihdewi, Sp.PK(K) M.Kes. | Validate spelling/credentials before create or match. |
| match | dr. Suryanto, Sp.PK., Subsp.HK(K) FIHFAA. | Dokter Spesialis Laboratorium / Dokter Spesialis Penunjang/Lainnya | dokter | dr. H. Suryanto, Sp.PK., Subsp.HK(K), FIHFAA | dr. Suryanto, Sp.PK., Subsp.HK(K) FIHFAA. | No content action; optionally standardize display name. |
| match | Dr.dr. Usi Sukorini, Sp.PK, Subsp.HK(K)., Subsp.BDKT(K). | Dokter Spesialis Laboratorium / Dokter Spesialis Penunjang/Lainnya | dokter | Prof. Dr. dr. Usi Sukorini, M.Kes., Sp.PK. Subsp. H.K(K), Subsp. B.D.K.T(K) | Dr.dr. Usi Sukorini, Sp.PK, Subsp.HK(K)., Subsp.BDKT(K). | No content action; optionally standardize display name. |
| missing | dr. Tri Yunanto Arliono, Sp.EM-KDM | Dokter Spesialis Emergency / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Tri Yunanto Arliono, Sp.EM-KDM | Create/update published `dokter` after HR/medical secretary validation. |
| missing | dr. Bombong Nurpagino, Sp.MK. | Dokter Spesialis Mikrobiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Bombong Nurpagino, Sp.MK. | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Ahmad Faesol, Sp.Rad.,MMR. | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. H. Ahmad Faesol, Sp.Rad., M.Kes., MMR | dr. Ahmad Faesol, Sp.Rad.,MMR. | No content action; optionally standardize display name. |
| match | dr. Dewi Ari Mulyani, Sp.Rad. M.Sc | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Dewi Ari Mulyani, Sp.Rad., M.Sc. | dr. Dewi Ari Mulyani, Sp.Rad. M.Sc | No content action; optionally standardize display name. |
| missing | dr. Kunyun Masindro, Sp.Rad. | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Kunyun Masindro, Sp.Rad. | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Muhammad Fandi Ghozali, Sp.Rad. | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Muhammad Fandi Ghozali, Sp.Rad., M.Med.Sc. | dr. Muhammad Fandi Ghozali, Sp.Rad. | No content action; optionally standardize display name. |
| missing | dr. Nur Hayati, Sp.Rad. | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Nur Hayati, Sp.Rad. | Create/update published `dokter` after HR/medical secretary validation. |
| missing | dr. Alita Bossa Rossila | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Alita Bossa Rossila | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Ardiyuga Pratitapraya, AIFO | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Ardiyuga Pratitapraya | dr. Ardiyuga Pratitapraya, AIFO | No content action; optionally standardize display name. |
| missing | dr. Atika Zahro Nirmala | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Atika Zahro Nirmala | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Bella Indah Normalita | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Bella Indah Normalita | dr. Bella Indah Normalita | No content action; optionally standardize display name. |
| match | dr. Desita Dyah Mukti Adityaningrum | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Desita Dyah Mukti Adityaningrum, M.Sc. | dr. Desita Dyah Mukti Adityaningrum | No content action; optionally standardize display name. |
| missing | dr. Dewi Masyithoh Mubarok | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Dewi Masyithoh Mubarok | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Dhia Clarissa Putri | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Dhia Clarissa Putri | dr. Dhia Clarissa Putri | No content action; optionally standardize display name. |
| match | dr. Dwi Ditha Emelia | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Dwi Ditha Emelia | dr. Dwi Ditha Emelia | No content action; optionally standardize display name. |
| missing | dr. Eka Yoga Wiratama | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Eka Yoga Wiratama | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Elmira Apriliani | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Elmira Apriliani, MMR | dr. Elmira Apriliani | No content action; optionally standardize display name. |
| missing | dr. Firman Setyawan, MMR | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Firman Setyawan, MMR | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Fitri Prawitasari | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Fitri Prawaitasari, MMR | dr. Fitri Prawitasari | No content action; optionally standardize display name. |
| missing | dr. Huma Laila Ramadhani | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Huma Laila Ramadhani | Create/update published `dokter` after HR/medical secretary validation. |
| missing | dr. Ihsan Yudhitama | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Ihsan Yudhitama | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Ihsana Khoirun-nisa | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Ihsana Khoirun Nisa | dr. Ihsana Khoirun-nisa | No content action; optionally standardize display name. |
| missing | dr. Ika Resti Afriani, MMR | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Ika Resti Afriani, MMR | Create/update published `dokter` after HR/medical secretary validation. |
| missing | dr. Isna Maulida Hanum | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Isna Maulida Hanum | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Jihan Izzatun Nisa | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Jihan Izzatun Nisa | dr. Jihan Izzatun Nisa | No content action; optionally standardize display name. |
| match | dr. Khansa Maria Salsabila | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Khansa Maria Salsabila | dr. Khansa Maria Salsabila | No content action; optionally standardize display name. |
| match | dr. M. Afif Nadirrafi | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Muhammad Afif Nadirrafi | dr. M. Afif Nadirrafi | No content action; optionally standardize display name. |
| match | dr. Maharani Zulfa Maz Uda | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Maharani Zulfa Maz Uda | dr. Maharani Zulfa Maz Uda | No content action; optionally standardize display name. |
| match | dr. Mahda Adil Aufa | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Mahda Adil Aufa, MMR | dr. Mahda Adil Aufa | No content action; optionally standardize display name. |
| missing | dr. Mega Susanti | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Mega Susanti | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Miftakhul Huda Fadhlullah, AIFO | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Miftakhul Huda Fadhlullah | dr. Miftakhul Huda Fadhlullah, AIFO | No content action; optionally standardize display name. |
| match | dr. Muhammad Ainun Rosydz | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Muhammad Ainun Rosydz | dr. Muhammad Ainun Rosydz | No content action; optionally standardize display name. |
| match | dr. Muhammad Asyam Fawwaz Akbar | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Muhammad Asyam Fawwaz | dr. Muhammad Asyam Fawwaz Akbar | No content action; optionally standardize display name. |
| match | dr. Muhammad Faris Novadityarrahman | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Muhammad Faris Novadityarahman, MMR | dr. Muhammad Faris Novadityarrahman | No content action; optionally standardize display name. |
| match | dr. Muhammad Salsabil Lasarik | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Muhammad Salsabil Lasarik | dr. Muhammad Salsabil Lasarik | No content action; optionally standardize display name. |
| missing | dr. Riski Februaminayanti | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Riski Februaminayanti | Create/update published `dokter` after HR/medical secretary validation. |
| missing | dr. Sugik Nur Irbandini, MARS | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Sugik Nur Irbandini, MARS | Create/update published `dokter` after HR/medical secretary validation. |
| missing | dr. Tiffany Dyah Rinanti | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Tiffany Dyah Rinanti | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Tuti Wardani, MMR | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Tuti Wardani, MMR | dr. Tuti Wardani, MMR | No content action; optionally standardize display name. |
| missing | dr. Wahyuni Hafid | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya |  |  | dr. Wahyuni Hafid | Create/update published `dokter` after HR/medical secretary validation. |
| match | dr. Zarifa Nurinnisa | Dokter Spesialis Radiologi / Dokter Spesialis Penunjang/Lainnya | dokter | dr. Zarifa Nurinnisa | dr. Zarifa Nurinnisa | No content action; optionally standardize display name. |

## Service Matrix

| Classification | Source name | Source group/type | Current WordPress CPT | Matched existing title | Canonical display recommendation | Recommended action |
| --- | --- | --- | --- | --- | --- | --- |
| missing | Klinik Kesehatan Anak | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan |  |  | Klinik Kesehatan Anak | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| possible-match | Klinik Tumbuh Kembang | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Terapi Tumbuh Kembang Anak | Klinik Tumbuh Kembang | Human confirm existing title; then canonicalize title/taxonomy if needed. |
| match | Klinik Jantung Anak | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Jantung Anak | Klinik Jantung Anak | No content action; map menus/search across CPTs. |
| match | Klinik Bedah Umum | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Bedah Umum | Klinik Bedah Umum | No content action; map menus/search across CPTs. |
| match | Klinik Bedah Digesti | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Bedah Digesti | Klinik Bedah Digesti | No content action; map menus/search across CPTs. |
| match | Klinik Bedah Onkologi | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Bedah Onkologi | Klinik Bedah Onkologi | No content action; map menus/search across CPTs. |
| match | Klinik Bedah Saraf | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Bedah Syaraf | Klinik Bedah Saraf | No content action; map menus/search across CPTs. |
| match | Klinik Bedah Anak | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Bedah Anak | Klinik Bedah Anak | No content action; map menus/search across CPTs. |
| missing | Klinik Bedah Kepala Leher | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan |  |  | Klinik Bedah Kepala Leher | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| editorial-review | Klinik Ortopedi | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Bedah Orthopedi | Klinik Ortopedi | Editorial validation first; likely destination `poliklinik`. |
| missing | Klinik Urologi | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan |  |  | Klinik Urologi | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| match | Klinik Penyakit Dalam | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Penyakit Dalam | Klinik Penyakit Dalam | No content action; map menus/search across CPTs. |
| match | Klinik Ginjal dan Hipertensi | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Ginjal dan Hipertensi | Klinik Ginjal dan Hipertensi | No content action; map menus/search across CPTs. |
| match | Klinik Jantung dan Pembuluh Darah | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Jantung dan Pembuluh Darah | Klinik Jantung dan Pembuluh Darah | No content action; map menus/search across CPTs. |
| match | Klinik Hemato-Onkologi | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Hemato Onkologi | Klinik Hemato-Onkologi | No content action; map menus/search across CPTs. |
| match | Klinik Paru dan Respirasi | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Paru | Klinik Paru dan Respirasi | No content action; map menus/search across CPTs. |
| match | Klinik Obstetri dan Ginekologi | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Kandungan (Obsgyn) | Klinik Obstetri dan Ginekologi | No content action; map menus/search across CPTs. |
| match | Klinik Ginekologi Onkologi | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Ginekologi Onkologi | Klinik Ginekologi Onkologi | No content action; map menus/search across CPTs. |
| editorial-review | Klinik Neurologi / Saraf | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan |  |  | Klinik Neurologi / Saraf | Editorial validation first; likely destination `poliklinik`. |
| missing | Klinik Psikiatri / Kesehatan Jiwa | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan |  |  | Klinik Psikiatri / Kesehatan Jiwa | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| match | Klinik Mata | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Mata | Klinik Mata | No content action; map menus/search across CPTs. |
| missing | Klinik Telinga, Hidung, Tenggorokan, dan Kepala Leher | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan |  |  | Klinik Telinga, Hidung, Tenggorokan, dan Kepala Leher | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| missing | Klinik Dermatologi, Venereologi, dan Estetika | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan |  |  | Klinik Dermatologi, Venereologi, dan Estetika | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| missing | Klinik Kedokteran Fisik dan Rehabilitasi | Layanan Spesialis dan Sub Spesialis / Layanan Rawat Jalan |  |  | Klinik Kedokteran Fisik dan Rehabilitasi | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| missing | Klinik Gigi dan Mulut | Layanan Gigi dan Gigi Spesialis / Layanan Rawat Jalan |  |  | Klinik Gigi dan Mulut | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| match | Klinik Bedah Mulut | Layanan Gigi dan Gigi Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Bedah Mulut | Klinik Bedah Mulut | No content action; map menus/search across CPTs. |
| missing | Klinik Konservasi Gigi | Layanan Gigi dan Gigi Spesialis / Layanan Rawat Jalan |  |  | Klinik Konservasi Gigi | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| missing | Klinik Konservasi Gigi Anak | Layanan Gigi dan Gigi Spesialis / Layanan Rawat Jalan |  |  | Klinik Konservasi Gigi Anak | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| possible-match | Klinik Periodonsia | Layanan Gigi dan Gigi Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Gigi Periodonti | Klinik Periodonsia | Human confirm existing title; then canonicalize title/taxonomy if needed. |
| possible-match | Klinik Ortodonsia | Layanan Gigi dan Gigi Spesialis / Layanan Rawat Jalan | poliklinik | Klinik Gigi Ortodonti | Klinik Ortodonsia | Human confirm existing title; then canonicalize title/taxonomy if needed. |
| missing | Klinik Nyeri Terpadu | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan |  |  | Klinik Nyeri Terpadu | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| missing | Klinik Gemphita (VCT-PITC HIV/AIDS) | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan |  |  | Klinik Gemphita (VCT-PITC HIV/AIDS) | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| missing | Klinik TB-DOTS | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan |  |  | Klinik TB-DOTS | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| match | Klinik Psikologi Klinis | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan | poliklinik | Klinik Psikologi Klinis | Klinik Psikologi Klinis | No content action; map menus/search across CPTs. |
| match | Klinik Gizi | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan | layanan | Gizi | Klinik Gizi | No content action; map menus/search across CPTs. |
| possible-match | Klinik Vaksin | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan | layanan | Vaksin Center | Klinik Vaksin | Human confirm existing title; then canonicalize title/taxonomy if needed. |
| match | Klinik Medical Check Up (MCU) | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan | layanan | Medical Check Up (MCU) | Klinik MCU / Medical Check Up (MCU) | No content action; map menus/search across CPTs. |
| missing | Klinik Berhenti Merokok | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan |  |  | Klinik Berhenti Merokok | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| missing | Klinik Keluarga Sakinah | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan |  |  | Klinik Keluarga Sakinah | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| missing | Klinik Laktasi | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan |  |  | Klinik Laktasi | Create/reconcile as `poliklinik` only after owner, scope, and public-facing copy are validated. |
| missing | Ayna Skin Care | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan |  |  | Ayna Skin Care | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | One Day Care | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan |  |  | One Day Care | Create/reconcile as `rawat-inap` only after owner, scope, and public-facing copy are validated. |
| match | Layanan Home Care | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan | layanan | Home Care | Layanan Home Care | No content action; map menus/search across CPTs. |
| match | Layanan Extramural & Medical Event Support | Layanan Rawat Jalan Lainnya / Layanan Rawat Jalan | layanan | Extramural (Layanan Luar Rumah Sakit) | Layanan Extramural & Medical Event Support | No content action; map menus/search across CPTs. |
| editorial-review | Minimal invasive surgery | Bedah Sentral / Layanan Rawat Jalan |  |  | Minimal invasive surgery | Editorial validation first; likely destination `layanan`. |
| editorial-review | C-arm | Bedah Sentral / Layanan Rawat Jalan |  |  | C-arm | Editorial validation first; likely destination `layanan`. |
| editorial-review | Endoscopy | Bedah Sentral / Layanan Rawat Jalan |  |  | Endoscopy | Editorial validation first; likely destination `layanan`. |
| missing | Retrograde Intrarenal Surgery (RIRS) | ### Layanan Uronefrologi / Uronephrology Centre |  |  | Retrograde Intrarenal Surgery (RIRS) | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Ureterorenoscopy (URS) | ### Layanan Uronefrologi / Uronephrology Centre |  |  | Ureterorenoscopy (URS) | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Conventional TURP | ### Layanan Uronefrologi / Uronephrology Centre |  |  | Conventional TURP | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Holmium Laser Enucleation of the Prostate (HoLEP) | ### Layanan Uronefrologi / Uronephrology Centre |  |  | Holmium Laser Enucleation of the Prostate (HoLEP) | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Extracorporeal Shock Wave Lithotripsy (ESWL) | ### Layanan Uronefrologi / Uronephrology Centre |  |  | Extracorporeal Shock Wave Lithotripsy (ESWL) | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| match | Layanan Hemodialisis 24 jam dengan 45 mesin, termasuk layanan infeksi dan VIP | ### Layanan Uronefrologi / Uronephrology Centre | layanan | Hemodialisis (Cuci Darah) | Layanan Hemodialisis 24 jam dengan 45 mesin, termasuk layanan infeksi dan VIP | No content action; map menus/search across CPTs. |
| match | Bedah Onkologi | ### Layanan Pusat Kanker / Cancer Centre | poliklinik | Klinik Bedah Onkologi | Bedah Onkologi | No content action; map menus/search across CPTs. |
| match | Ginekologi Onkologi | ### Layanan Pusat Kanker / Cancer Centre | poliklinik | Klinik Ginekologi Onkologi | Ginekologi Onkologi | No content action; map menus/search across CPTs. |
| match | Hemato Onkologi | ### Layanan Pusat Kanker / Cancer Centre | poliklinik | Klinik Hemato Onkologi | Hemato Onkologi | No content action; map menus/search across CPTs. |
| missing | Layanan Tumor Paru | ### Layanan Pusat Kanker / Cancer Centre |  |  | Layanan Tumor Paru | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Layanan Tumor Otak | ### Layanan Pusat Kanker / Cancer Centre |  |  | Layanan Tumor Otak | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| match | Patologi Anatomi | ### Layanan Pusat Kanker / Cancer Centre | poliklinik | Klinik Patologi Anatomi | Patologi Anatomi | No content action; map menus/search across CPTs. |
| match | Fisioterapi dewasa | Rehabilitasi Medis / Penunjang Medis | layanan | Fisioterapi | Fisioterapi dewasa | No content action; map menus/search across CPTs. |
| match | Fisioterapi anak | Rehabilitasi Medis / Penunjang Medis | layanan | Fisioterapi | Fisioterapi anak | No content action; map menus/search across CPTs. |
| match | Terapi Wicara | Rehabilitasi Medis / Penunjang Medis | poliklinik | Terapi Wicara | Terapi Wicara | No content action; map menus/search across CPTs. |
| missing | Terapi Okupasi | Rehabilitasi Medis / Penunjang Medis |  |  | Terapi Okupasi | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| match | Laboratorium Patologi Klinik | Laboratorium Medis / Penunjang Medis | layanan | Laboratorium | Laboratorium Patologi Klinik | No content action; map menus/search across CPTs. |
| match | Laboratorium Patologi Anatomi | Laboratorium Medis / Penunjang Medis | layanan | Laboratorium | Laboratorium Patologi Anatomi | No content action; map menus/search across CPTs. |
| match | Laboratorium Mikrobiologi Klinik | Laboratorium Medis / Penunjang Medis | layanan | Laboratorium | Laboratorium Mikrobiologi Klinik | No content action; map menus/search across CPTs. |
| missing | Bank Darah | Laboratorium Medis / Penunjang Medis |  |  | Bank Darah | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Multi slice CT scan | Radiologi / Penunjang Medis |  |  | Multi slice CT scan | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Digital X-ray | Radiologi / Penunjang Medis |  |  | Digital X-ray | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Dental X-ray | Radiologi / Penunjang Medis |  |  | Dental X-ray | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | USG 4 dimensi | Radiologi / Penunjang Medis |  |  | USG 4 dimensi | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Echocardiography | Radiologi / Penunjang Medis |  |  | Echocardiography | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Electrocardiography (ECG) | Pemeriksaan Penunjang Lain / Penunjang Medis |  |  | Electrocardiography (ECG) | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Treadmill | Pemeriksaan Penunjang Lain / Penunjang Medis |  |  | Treadmill | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Electroencephalography (EEG) | Pemeriksaan Penunjang Lain / Penunjang Medis |  |  | Electroencephalography (EEG) | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Spirometry | Pemeriksaan Penunjang Lain / Penunjang Medis |  |  | Spirometry | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Audiometry | Pemeriksaan Penunjang Lain / Penunjang Medis |  |  | Audiometry | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Steam prevaccum | Layanan Sterilisasi Suhu Tinggi / Central Sterile Supply Department (CSSD) |  |  | Steam prevaccum | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Autoreader indicator protein dan biologi | Layanan Sterilisasi Suhu Tinggi / Central Sterile Supply Department (CSSD) |  |  | Autoreader indicator protein dan biologi | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Sealer | Layanan Sterilisasi Suhu Tinggi / Central Sterile Supply Department (CSSD) |  |  | Sealer | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Ethylene oxide | Layanan Sterilisasi Suhu Rendah / Central Sterile Supply Department (CSSD) |  |  | Ethylene oxide | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Autoreader indicator protein dan biologi | Layanan Sterilisasi Suhu Rendah / Central Sterile Supply Department (CSSD) |  |  | Autoreader indicator protein dan biologi | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Sealer | Layanan Sterilisasi Suhu Rendah / Central Sterile Supply Department (CSSD) |  |  | Sealer | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| match | Ambulan Emergensi | Ambulan / Layanan Unggulan Lainnya | layanan | Ambulans | Ambulan Emergensi | No content action; map menus/search across CPTs. |
| match | Ambulan Transport | Ambulan / Layanan Unggulan Lainnya | layanan | Ambulans | Ambulan Transport | No content action; map menus/search across CPTs. |
| match | Ambulan Bencana | Ambulan / Layanan Unggulan Lainnya | layanan | Ambulans | Ambulan Bencana | No content action; map menus/search across CPTs. |
| match | AmbulanEvent Support | Ambulan / Layanan Unggulan Lainnya | layanan | Ambulans | Ambulan / Event Support | No content action; map menus/search across CPTs. |
| missing | Antar Jemput Pasien | Ambulan / Layanan Unggulan Lainnya |  |  | Antar Jemput Pasien | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| match | Medical Check Up | Layanan Lainnya / Layanan Unggulan Lainnya | layanan | Medical Check Up (MCU) | MCU / Medical Check Up | No content action; map menus/search across CPTs. |
| missing | Layanan Vaksin | Layanan Lainnya / Layanan Unggulan Lainnya |  |  | Layanan Vaksin | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Layanan Bimbingan Rohani Islam | Layanan Lainnya / Layanan Unggulan Lainnya |  |  | Layanan Bimbingan Rohani Islam | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| match | Taman Makam Husnul Khotimah | Layanan Lainnya / Layanan Unggulan Lainnya | layanan | Husnul Khotimah | Taman Makam Husnul Khotimah | No content action; map menus/search across CPTs. |
| missing | Kelas III | . Layanan Medis RS PKU Muhammadiyah Yogyakarta / Bangsal Rawat Inap |  |  | Kelas III | Create/reconcile as `rawat-inap` only after owner, scope, and public-facing copy are validated. |
| missing | Kelas II | . Layanan Medis RS PKU Muhammadiyah Yogyakarta / Bangsal Rawat Inap |  |  | Kelas II | Create/reconcile as `rawat-inap` only after owner, scope, and public-facing copy are validated. |
| missing | Kelas I | . Layanan Medis RS PKU Muhammadiyah Yogyakarta / Bangsal Rawat Inap |  |  | Kelas I | Create/reconcile as `rawat-inap` only after owner, scope, and public-facing copy are validated. |
| missing | Kelas VIP | . Layanan Medis RS PKU Muhammadiyah Yogyakarta / Bangsal Rawat Inap |  |  | Kelas VIP | Create/reconcile as `rawat-inap` only after owner, scope, and public-facing copy are validated. |
| missing | Kelas VVIP | . Layanan Medis RS PKU Muhammadiyah Yogyakarta / Bangsal Rawat Inap |  |  | Kelas VVIP | Create/reconcile as `rawat-inap` only after owner, scope, and public-facing copy are validated. |
| missing | Emergensi Terpadu | . Centre of Excellence RS PKU Muhammadiyah Yogyakarta / Emergency and Critical Care |  |  | Emergensi Terpadu | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Perawatan Intensif | . Centre of Excellence RS PKU Muhammadiyah Yogyakarta / Emergency and Critical Care |  |  | Perawatan Intensif | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Intensive Care Unit (ICU) | . Centre of Excellence RS PKU Muhammadiyah Yogyakarta / Emergency and Critical Care |  |  | Intensive Care Unit (ICU) | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Intensive Cardiac Care Unit (ICCU) | . Centre of Excellence RS PKU Muhammadiyah Yogyakarta / Emergency and Critical Care |  |  | Intensive Cardiac Care Unit (ICCU) | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| missing | Pediatric Intensive Care Unit (PICU) | . Centre of Excellence RS PKU Muhammadiyah Yogyakarta / Emergency and Critical Care |  |  | Pediatric Intensive Care Unit (PICU) | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |
| editorial-review | Neonatal Intensive Care Unit (PICU) | . Centre of Excellence RS PKU Muhammadiyah Yogyakarta / Emergency and Critical Care |  |  | Neonatal Intensive Care Unit (NICU) | Editorial validation first; likely destination `layanan`. |
| missing | High Care Unit (HCU) | . Centre of Excellence RS PKU Muhammadiyah Yogyakarta / Emergency and Critical Care |  |  | High Care Unit (HCU) | Create/reconcile as `layanan` only after owner, scope, and public-facing copy are validated. |

## Duplicate and Canonical-name Review

- No exact normalized duplicate published titles found across audited CPTs. Remaining duplicate risk is mostly semantic, not exact-title duplication.

### Canonical naming risks
- Syaraf/Saraf: Use `Saraf` or `Neurologi / Saraf` consistently; avoid mixed spellings across taxonomy, CPT, menus.
- Orthopedi/Ortopedi: Use Indonesian `Ortopedi` unless official branding requires `Orthopedi`; current source files differ.
- Obsgyn/Obstetri dan Ginekologi: Use patient-readable `Obstetri dan Ginekologi`; keep `Obsgyn` only as alias/search term.
- PICU/NICU: `Neonatal Intensive Care Unit (PICU)` likely should be `Neonatal Intensive Care Unit (NICU)`; confirm clinically.
- Ambulan/Ambulans/Event Support: Split ambulance transport from event medical support unless RS owns one combined public service.
- Doctor display names: preserve source spelling in audit/source column, but validate credential punctuation before bulk changes because existing WordPress often adds/removes `H.`, subspesialisasi, or alternate degree ordering.

## Editorial-validation Queue

| Priority | Source name | Area | Reason | Recommended validation owner |
| --- | --- | --- | --- | --- |
| High | Prof. dr. Djauhar Ismail, MPH,Ph.D,Sp.A.(K). | Klinik Tumbuh Kembang | Known naming/source anomaly or uncertain title abbreviation. | Medical secretary/content editor |
| High | Dr.dr. Nurnaningsih, Sp.A(K). | Klinik Intensif Anak | Known naming/source anomaly or uncertain title abbreviation. | Medical secretary/content editor |
| High | dr. Iri Kuswadi, Sp.PD-KGH,FINASIM. | Klinik Ginjal dan Hipertensi | Known naming/source anomaly or uncertain title abbreviation. | Medical secretary/content editor |
| High | dr. Akhmad Yun Jufan, Sp.An,MSc(KIC). | Dokter Spesialis Anestesi | Known naming/source anomaly or uncertain title abbreviation. | Medical secretary/content editor |
| High | dr. Mahmud,Sp, An(KMN), MSc,FIPM. | Dokter Spesialis Anestesi | Known naming/source anomaly or uncertain title abbreviation. | Medical secretary/content editor |
| High | Dr.dr. Andaru Dahesihdewi, Sp.PK(K) M.Kes. | Dokter Spesialis Laboratorium | Known naming/source anomaly or uncertain title abbreviation. | Medical secretary/content editor |
| High | Klinik Ortopedi | Layanan Spesialis dan Sub Spesialis | Known naming/source anomaly or uncertain title abbreviation. | Service owner/content editor |
| High | Klinik Neurologi / Saraf | Layanan Spesialis dan Sub Spesialis | Known naming/source anomaly or uncertain title abbreviation. | Service owner/content editor |
| High | Minimal invasive surgery | Bedah Sentral | Known naming/source anomaly or uncertain title abbreviation. | Service owner/content editor |
| High | C-arm | Bedah Sentral | Known naming/source anomaly or uncertain title abbreviation. | Service owner/content editor |
| High | Endoscopy | Bedah Sentral | Known naming/source anomaly or uncertain title abbreviation. | Service owner/content editor |
| High | Neonatal Intensive Care Unit (PICU) | . Centre of Excellence RS PKU Muhammadiyah Yogyakarta | Known naming/source anomaly or uncertain title abbreviation. | Service owner/content editor |
| High | Bank Darah | Laboratorium Medis | High-value missing/reconciliation candidate from source 2026. | Service owner/content editor |
| High | Neonatal Intensive Care Unit (PICU) | Emergency and Critical Care | Source explicitly writes PICU for neonatal unit; likely NICU but requires clinical confirmation. | ICU/PICU/NICU service owner |
| High | AmbulanEvent Support | Layanan Unggulan Lainnya | Known concatenation/naming issue; source matrix has related ambulance/event support naming variations. | Emergency/marketing/content owner |

## Prioritized Actions

1. Freeze imports until editorial queue is resolved for clinical terminology, especially PICU/NICU, ambulance/event support, and spelling variants.
2. Confirm IA: decide whether each source service belongs in `poliklinik`, `layanan`, `rawat-inap`, a landing-page section, or taxonomy only. Avoid stuffing all source items into `layanan`.
3. Reconcile doctors first: verify `possible-match` rows against HR/medical secretary list, then create only true missing `dokter` posts and normalize `spesialisasi-dokter`.
4. Reconcile high-value service gaps: Cancer Centre, Bedah Sentral, Endoscopy, TB-DOTS, Klinik Nyeri Terpadu, Klinik Laktasi, Bank Darah, Mikrobiologi Klinik, ICU/PICU/NICU/HCU, MCU, Hemodialisa/Hemodialisis.
5. Add alias/search metadata only after canonical names are approved, so `Syaraf`, `Orthopedi`, and `Obsgyn` still find the canonical pages without becoming display names.
6. Update dashboard/import tooling later to show this audit classification and destination CPT recommendations. This report does not implement dashboard changes.

## No-mutation Statement

This audit only read local source files and queried published WordPress data. It did not create, update, delete, import, deploy, commit, or mutate WordPress content/taxonomies.
