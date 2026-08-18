# Decisions

- 2026-08-16 user explicitly approved committing, pushing, and deploying the empty scaffold before official photo metadata. Scope exception: feature/settings slots ship empty; user will upload and complete metadata later. No WordPress settings, DB, uploads, or source images were mutated during release.

- Task 0 metadata/preprocessing remains blocked until user supplies official year/title/caption/alt for all five slots.
- Tasks 1-4 may proceed safely because defaults are empty and public output remains text-only until slots are complete.
