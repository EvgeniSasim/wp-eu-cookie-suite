# CC-27 Multisite E2E smoke (after CC-26 merge)

```markdown
Task CC-27 — Playwright smoke tests for Multisite inherit/override (optional, after CC-26b merged).

## Goal

Add minimal E2E coverage documenting multisite admin flows. Skip if no multisite env in CI — document manual steps in test comments.

## Deliverables

1. Extend `tests/e2e/` OR add `tests/e2e/specs/multisite.spec.ts` with:
   - Site admin: “Use network defaults” toggle visible when `MULTISITE=1` (env-gated skip otherwise)
   - Network admin: settings page loads (env-gated)
2. Update `tests/e2e/.env.example` with optional `WP_MULTISITE=1` note.
3. README section in `tests/e2e/README.md` for local multisite docker/manual setup.

## Constraints

- Do not block CI on multisite unless runner supports it — use `test.skip()` when env missing.
- No product logic changes unless fixing test hooks.
- Do not bump plugin version.

Branch: `jules/cc-27-multisite-e2e`
One PR.
```
