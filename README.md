# RS PKU Muhammadiyah Yogyakarta — Website

Website resmi **RS PKU Muhammadiyah Yogyakarta** berbasis WordPress dengan arsitektur theme modular menggunakan Timber, Twig, TailwindCSS, Alpine.js, dan Vite.

> Repositori ini **hanya melacak kode custom** (theme + plugin + spec + dokumentasi). File WordPress core, plugin pihak ketiga, uploads, dan dump database **tidak** disertakan.

---

## Tumpukan Teknologi

- **PHP** 8.3+
- **WordPress** 6.5+
- **Timber v2** + **Twig** — server-rendered templating
- **TailwindCSS 3** + **Alpine.js 3** — styling & interaktivitas
- **Vite 6** — build pipeline
- **Composer** (PSR-4 autoload, namespace `Rspku\`)

## Struktur Repositori

```
wp-content/
├── themes/
│   └── rspku-theme/           # Theme utama
│       ├── app/               # PHP classes (Controllers, PostTypes, Repositories, Services, Setup, …)
│       ├── resources/         # Twig views, CSS, JS, icons
│       ├── scripts/           # Skrip utilitas (sync dokter, lucide icons)
│       ├── functions.php
│       ├── composer.json
│       ├── package.json
│       ├── tailwind.config.js
│       └── vite.config.js
└── plugins/
    └── rspku-core/            # Plugin REST API ternormalisasi (namespace rspku/v1)

.kiro/specs/                   # Spec Kiro (design-first)
.agents/skills/                # Skill agent (img-to-frontend, frontend-design, …)
```

## Setup Development

### 1. Instal di lingkungan lokal

Disarankan menggunakan Laragon/XAMPP dengan:

- WordPress 6.5+
- PHP 8.3+
- MySQL/MariaDB

### 2. Clone repo ke dalam `wp-content/`

```bash
# Misal dari root WordPress:
cd wp-content/themes
git clone git@github.com:alanramadhani2112/rspkumuhammadiyahyogyakarta.git rspku-site
```

Atau jika meng-clone ke root WordPress yang sudah ada, sesuaikan path pengambilan theme/plugin.

### 3. Theme: install dependency & build

```bash
cd wp-content/themes/rspku-theme
composer install
npm install
npm run dev      # development
npm run build    # production build
```

### 4. Aktifkan theme & plugin

Di WordPress admin:

1. Appearance → Themes → aktifkan **RSPKU Muhammadiyah Yogyakarta**
2. Plugins → aktifkan **RSPKU Core**

### 5. Flush rewrite rules

Settings → Permalinks → klik **Save Changes** supaya custom post type & taxonomy rewrite aktif.

## Custom Post Types

| Slug          | Label         |
| ------------- | ------------- |
| `dokter`      | Dokter        |
| `poliklinik`  | Poliklinik    |
| `layanan`     | Layanan       |
| `jurnal`      | E-Journal     |
| `manajemen-rs`| Manajemen RS  |
| `rawat-inap`  | Rawat Inap    |
| `cabang-rs`   | Cabang RS     |

## Taksonomi Custom

- `spesialisasi-dokter` (dokter)
- `jenis-konsultasi` (dokter)
- `kategori-layanan` (layanan)

## REST API (plugin `rspku-core`)

Namespace: `rspku/v1`

Endpoint utama:
- `GET /wp-json/rspku/v1/site`
- `GET /wp-json/rspku/v1/home`
- `GET /wp-json/rspku/v1/menu/{slug}`
- `GET /wp-json/rspku/v1/search`
- `GET /wp-json/rspku/v1/{posts|doctors|services|polyclinics|management|journals|rooms}`

## Kontribusi

- Ikuti konvensi PSR-12 untuk PHP.
- Tailwind utility-first; hindari CSS custom kecuali terpaksa.
- Twig: satu komponen per file, gunakan partial/include.
- Jangan commit `wp-config.php`, dump SQL, `node_modules/`, `vendor/`, atau build output.

## Lisensi

Proprietary — RS PKU Muhammadiyah Yogyakarta.
