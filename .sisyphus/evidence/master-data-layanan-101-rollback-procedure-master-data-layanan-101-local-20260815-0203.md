# Rollback Procedure, Master Data Layanan 101

Batch ID: `master-data-layanan-101-local-20260815-0203`

Apply manifest: `.sisyphus/evidence/master-data-layanan-101-apply-master-data-layanan-101-local-20260815-0203.json`

Rollback manifest: `.sisyphus/evidence/master-data-layanan-101-rollback-master-data-layanan-101-local-20260815-0203.json`

## Current Batch Status

This rollback is a no-op for the current batch.

The apply manifest records `db_writes=0`, `created=[]`, `updated=[]`, `assigned=[]`, `created_count=0`, `updated_count=0`, `assigned_count=0`, `deleted_existing_count=0`, and `published_count=0`.

The rollback manifest also records `created=[]`, `updated=[]`, `assigned=[]`, and `deleted_existing_count=0`.

No WordPress posts, terms, term assignments, post meta, slugs, routes, or published statuses were changed by this batch.

## Approval Gate

Do not run rollback without explicit user approval for the exact batch ID and exact manifest paths above.

Approval must name the intended action before any draft post deletion, assignment revert, or audit meta revert. Without that approval, rollback remains read-only review only.

## Rollback Scope

Rollback can only touch IDs listed in the apply manifest or rollback manifest for this batch.

Future rollback is limited to these manifest-backed items:

1. Draft posts listed under `created`, if they were created by this batch.
2. Existing posts listed under `updated`, only for audit meta values recorded in the manifest.
3. Term assignments listed under `assigned`, only for assignments added by this batch.

Rollback must not infer IDs from title matches, route matches, CPT counts, taxonomy browsing, or spreadsheet rows.

## Forbidden Actions

Never delete existing posts that were not created by this batch.

Never mutate `kategori-layanan`.

Never delete unrelated terms.

Never change slugs.

Never change publish status for unrelated posts.

Never use DB backup restore as the first rollback action.

Never invent created IDs when `created=[]`.

## Safe Procedure

1. Get explicit user approval for rollback of `master-data-layanan-101-local-20260815-0203`.
2. Back up the WordPress database before any write.
3. Read the apply manifest at `.sisyphus/evidence/master-data-layanan-101-apply-master-data-layanan-101-local-20260815-0203.json`.
4. Read the rollback manifest at `.sisyphus/evidence/master-data-layanan-101-rollback-master-data-layanan-101-local-20260815-0203.json`.
5. Confirm `created`, `updated`, `assigned`, and `deleted_existing_count` match the expected rollback scope.
6. Verify every candidate ID exists, has the expected post status, expected CPT, expected `master-layanan-medis` state, and no `kategori-layanan` mutation is planned.
7. If all arrays are empty, stop. Current batch rollback is complete because there is nothing to revert.
8. If a future approval manifest has items, execute only the scoped rollback actions approved by the user.
9. Verify post counts, term assignment counts, route availability, and manifest counts after rollback.
10. Record rollback evidence with the batch ID, manifest paths, touched IDs, counts before, counts after, and approval reference.

## Current Decision

No live rollback execution is needed for this batch.

The documented rollback result is no-op because `db_writes=0`, `created=[]`, `updated=[]`, and `assigned=[]`.
