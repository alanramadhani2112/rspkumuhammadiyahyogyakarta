# Task 5A Baseline / Incomplete QA

Status: complete for public empty-gallery baseline. Admin QA blocked by missing authenticated session.

HTTP checks:

| URL | Result |
|---|---|
| `http://rspkudev.test/sejarah-kami/?qa=history5a` | 200, length 91326 |
| `http://rspkudev.test/` | 200, length 164830 |
| `http://rspkudev.test/kontak/` | 200, length 90155 |
| `http://rspkudev.test/dokter/` | 200, length 118391 |

`/sejarah-kami` source assertions:

- Title/content present.
- Stats present: `Tahun Melayani`, `Dokter Spesialis`, `Spesialisasi`, `IGD Siaga`.
- Timeline present: `Milestone Perjalanan Kami`, `1923`, `2024`.
- Principles present: `Falsafah, Visi, dan Misi`.
- Values present: `ALMAUN`.
- CTA present: `Menjadi bagian dari perjalanan kami`.
- History `<figure>` count: 0 with empty settings, so no blank archival figures render.
- Existing generic/theme image count: 7; not history-gallery figures.

Blocked next: Task 5B complete-data QA requires official metadata, manual Media Library/settings entry, and/or authenticated admin access.
