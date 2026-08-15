## Mapping Count Assertion Evidence

Batch: `master-data-layanan-101-local-20260815-0203`

Artifact checked:

- `.sisyphus/evidence/master-data-layanan-101-mapping-master-data-layanan-101-local-20260815-0203.json`

Assertions:

```text
mapping_count === 104
mapping_counts.poliklinik === 40
mapping_counts.layanan === 59
mapping_counts.rawat-inap === 5
source_identities === 101
hub_identities === 3
db_writes === 0
```

Result:

```text
ASSERT_OK
{"mapping_count":104,"mapping_counts":{"poliklinik":40,"layanan":59,"rawat-inap":5},"source_identities":101,"hub_identities":3,"db_writes":0,"match_counts":{"matched-by-identity":0,"possible-existing-title":20,"missing":84,"collision":0}}
```
