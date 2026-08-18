
## Task 8 - Homepage Speed Gate Not Met (2026-07-31)
- Warm-cache homepage curl returned 200 in 2.787795s, above the <2s acceptance target.
- Diagnostic curl showed /wp-json/rspku/v1/site at 7.193595s for 2182 bytes and /wp-json/rspku/v1/home at 7.528623s for 84364 bytes, suggesting WordPress/bootstrap or REST data assembly latency rather than raw HTML error output.
- Recommended next smallest step: profile anonymous /wp-json/rspku/v1/site and /wp-json/rspku/v1/home handlers/query paths under local runtime; do not tune server globally until endpoint-level cost is isolated.


## Task 8 continuation - speed gate blocked outside RSPKU source scope (2026-07-31)
- Final restored warm-cache homepage curl is 200 2.950299 173412, so <2s acceptance still fails.
- Root cause is active third-party plugin/WordPress runtime baseline: same homepage drops to 1.373526s with only RSPKU plugins and 1.330806s with no active plugins during temporary restored isolation.
- Recommended next smallest step: identify which active third-party plugin(s) add ~1.6s by testing one plugin group at a time in a local-only restored isolation pass, then configure/replace/optimize that plugin outside this RSPKU theme/plugin plan.

