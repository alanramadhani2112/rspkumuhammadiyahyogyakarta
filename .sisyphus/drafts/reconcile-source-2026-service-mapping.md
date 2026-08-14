# Source 2026 Service Mapping Policy

Status: approved structure policy; no taxonomy mutation performed.

## Destination Rules

| Source granularity | WordPress destination | Rule |
| --- | --- | --- |
| Outpatient clinical clinic | `poliklinik` top-level draft | Create only after row-specific approval. |
| Hospital support service, centre, major service product | `layanan` top-level draft | Create only after row-specific approval. |
| Room class and inpatient facility | `rawat-inap` | Keep separate from outpatient and support services. |
| Procedure, equipment, test variant, sub-unit, room inside a centre | Parent post detail | Do not create top-level post by default. |

## Explicit Top-Level Decisions

| Item | Destination | Decision |
| --- | --- | --- |
| Cancer Centre | `layanan` | Approved batch 1 draft parent, post `20754`. |
| Uronephrology Centre | `layanan` | Approved batch 1 draft parent, post `20755`. |
| Emergency and Critical Care | `layanan` | Approved batch 1 draft parent, post `20756`. |
| Klinik Nyeri Terpadu | `poliklinik` | Approved batch 1 draft, post `20749`. |
| Klinik TB-DOTS | `poliklinik` | Approved batch 1 draft, post `20750`. |
| Klinik Berhenti Merokok | `poliklinik` | Approved batch 1 draft, post `20751`. |
| Klinik Keluarga Sakinah | `poliklinik` | Approved batch 1 draft, post `20752`. |
| Klinik Laktasi | `poliklinik` | Approved batch 1 draft, post `20753`. |
| One Day Care | Hold | IA destination remains ambiguous between `rawat-inap` and `layanan`. |
| Bedah Sentral | Hold | Editorial ambiguity must be resolved first. |

## Parent/Detail Mapping

| Parent | Detail candidates, not top-level by default |
| --- | --- |
| Uronephrology Centre | Retrograde Intrarenal Surgery (RIRS), Percutaneous Nephrolithotomy (PCNL), Ureterorenoscopy (URS), Transurethral Resection of Prostate (TURP), Extracorporeal Shock Wave Lithotripsy (ESWL). |
| Cancer Centre | Bedah Onkologi, Ginekologi Onkologi, Hemato-Onkologi, Tumor Paru, Tumor Otak, Patologi Anatomi. Existing clinic records remain canonical and may be linked later; no duplicates. |
| Emergency and Critical Care | IGD, ICU, ICCU, PICU, NICU, HCU. Ambiguous neonatal/PICU source row stays editorial hold. |
| Radiologi | CT Scan, Digital X-ray, mammography, ultrasonography, fluoroscopy, imaging variants. |
| Jantung dan Pembuluh Darah | ECG, echocardiography, treadmill, Holter, cardiac test variants. |
| Ambulans | Ambulance transport variants and medical-event variants. |
| Laboratorium | Test variants, Bank Darah, Patologi Klinik, Patologi Anatomi, Mikrobiologi Klinik unless a separately approved patient-facing top-level service is justified. |

`CT Scan`, `Digital X-ray`, `ECG`, `Treadmill`, `ESWL`, and ambulance variants remain detail candidates. They must not become top-level posts without new explicit approval.

## Taxonomy Guard

- Batch 1 creates no terms and assigns no terms.
- Future term creation requires `allow_term_create=true` on an explicitly approved row.
- Future term assignment requires `allow_term_assign=true` on an explicitly approved row.
- No term deletion or merge is allowed in this reconciliation phase.
- No `spesialisasi-dokter` deduplication is allowed in this reconciliation phase.
- Existing term slugs remain unchanged.

## Content Guard

- Draft parent posts contain title and audit metadata only until clinical/editorial copy is reviewed.
- Existing IDs, slugs, schedules, media, descriptions, and relations remain untouched.
- Publishing requires a separate explicit approval after content and relationship QA.
