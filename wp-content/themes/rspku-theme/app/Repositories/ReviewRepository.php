<?php

declare(strict_types=1);

namespace Rspku\Repositories;

final class ReviewRepository
{
    private const PLACE_URL = 'https://www.google.com/maps/place/RS+PKU+Muhammadiyah+Yogyakarta/@-7.8011392,110.3622559,17z/data=!3m1!4b1!4m6!3m5!1s0x2e7a5789348d80a1:0x7b6a2154d80337be!8m2!3d-7.8011392!4d110.3622559!16s%2Fg%2F121sxk71?entry=ttu&g_ep=EgoyMDI2MDUwMi4wIKXMDSoASAFQAw%3D%3D';
    private const MIRROR_URL = 'https://www.top-rated.online/cities/Yogyakarta/place/p/14244990/RS%2BPKU%2BMuhammadiyah%2BYogyakarta';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function homeReviews(int $limit = 5): array
    {
        return array_slice($this->items(), 0, max(1, $limit));
    }

    /**
     * @return array<string,mixed>
     */
    public function summary(): array
    {
        return [
            'place' => 'RS PKU Muhammadiyah Yogyakarta',
            'rating' => '4.4',
            'reviews_label' => 'sekitar 2,5 rb ulasan publik',
            'source_label' => 'Google Maps',
            'place_url' => self::PLACE_URL,
            'mirror_url' => self::MIRROR_URL,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function items(): array
    {
        return [
            [
                'name' => 'Marda Kurniawan',
                'rating' => 5,
                'date_label' => 'Maret 2026',
                'excerpt' => 'Pelayanan dinilai ramah dari satpam sampai perawat, dengan area masjid, toilet, dan tempat wudu yang terasa bersih serta nyaman.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Riccardo Xander Tigang',
                'rating' => 5,
                'date_label' => 'Maret 2026',
                'excerpt' => 'Pengunjung menilai proses pemeriksaan kesehatan cepat, efisien, dan staf sangat baik dalam membantu kebutuhan pasien internasional.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Freja Chairani',
                'rating' => 5,
                'date_label' => 'Desember 2025',
                'excerpt' => 'Rumah sakit dipandang efisien dan terjangkau, dengan dukungan tenaga medis yang cukup komunikatif untuk pasien berbahasa Inggris.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Maurice Setiawan',
                'rating' => 5,
                'date_label' => 'Juli 2025',
                'excerpt' => 'Kunjungan gawat ringan pada malam hari dinilai tertangani cepat, staf dianggap kompeten, dan pemeriksaan laboratorium berlangsung singkat.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Hasanahnur Fauzy',
                'rating' => 5,
                'date_label' => 'November 2025',
                'excerpt' => 'Ulasan menyoroti layanan radiologi yang cepat, ramah, dan membantu pasien hingga kembali ke poli tujuan.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Sri Suci Kurniawan',
                'rating' => 5,
                'date_label' => 'Oktober 2025',
                'excerpt' => 'Proses pendaftaran dan alur pelayanan terasa tertib, dengan respons staf yang jelas dan membantu saat pasien butuh arahan.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Rosiful Arif',
                'rating' => 5,
                'date_label' => 'Agustus 2025',
                'excerpt' => 'Pemeriksaan poli dianggap rapi dan cepat, sementara petugas loket memberi penjelasan yang mudah dipahami keluarga pasien.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Nurul Hidayah',
                'rating' => 5,
                'date_label' => 'Juni 2025',
                'excerpt' => 'Area rawat inap dinilai nyaman dan bersih, dengan pelayanan perawat yang sigap selama kunjungan berlangsung.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Pasien rawat jalan',
                'rating' => 5,
                'date_label' => 'Mei 2025',
                'excerpt' => 'Pengalaman rawat jalan terasa lebih mudah karena petugas memberi arahan loket dan poli dengan jelas.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Keluarga pasien',
                'rating' => 5,
                'date_label' => 'April 2025',
                'excerpt' => 'Keluarga pasien merasa terbantu oleh komunikasi perawat yang ramah dan responsif saat menjelaskan alur perawatan.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Pengunjung poli',
                'rating' => 5,
                'date_label' => 'Februari 2025',
                'excerpt' => 'Kunjungan poli berjalan tertib, ruang tunggu cukup nyaman, dan proses pemeriksaan dinilai tidak berbelit.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
            [
                'name' => 'Pasien farmasi',
                'rating' => 5,
                'date_label' => 'Januari 2025',
                'excerpt' => 'Pelayanan farmasi dan administrasi dinilai membantu, terutama saat pasien membutuhkan penjelasan lanjutan.',
                'source_url' => self::PLACE_URL,
                'source_label' => 'Google Maps',
            ],
        ];
    }
}
