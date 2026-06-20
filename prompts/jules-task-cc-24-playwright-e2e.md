# CC-24 Playwright E2E regression suite

```markdown
Task CC-24 — Playwright E2E tests for Privaro Cookie Consent Banner admin + frontend flows.

Depends on v1.1.2 merged (revoke banner fix).

## Goal

Automate the manual QA matrix from v1.1.1 audit so regressions are caught in CI or a documented local script. No product feature changes unless a test reveals a real bug.

## Deliverables

1. **`e2e/` directory** (or `tests/e2e/`) with Playwright config targeting local WP (`http://localhost:8000` by default, env `WP_BASE_URL`).

2. **Auth helper** — login via `wp-login.php` using env `WP_ADMIN_USER` / `WP_ADMIN_PASS` (never commit credentials).

3. **Admin tests** (one spec file or describe blocks):
   - All 7 tabs load (dashboard, banner, cookies, scanner, consent_log, integrations, tools)
   - Banner tab: change title, save, verify `settings-updated` notice
   - Banner preview iframe: AJAX response contains `CookieConsent` and primary color CSS var
   - Integrations: toggle blocker, save, reload page, assert persisted
   - Tools: toggle consent logging, save, assert persisted

4. **Frontend tests** (fresh browser context, no admin cookies):
   - Before consent: no `wpeu_statistics` / `wpeu_marketing` cookies
   - Reject all: category cookies set to 0
   - Accept all: category cookies set to 1
   - Revoke via `window.dispatchEvent(new CustomEvent('wpeu-cs-revoke'))`: cookies cleared AND `#cc-main` visible without reload (v1.1.2 regression)

5. **`package.json` scripts**: `npm run test:e2e` from repo root or `e2e/`.

6. **`.github/workflows/e2e.yml`** (optional if too heavy): run on `workflow_dispatch` only; document in `e2e/README.md` how to run locally against BSB `wp/BSB_`.

## Constraints

- Plugin slug: `privaro-cookie-consent-banner`
- Deactivate conflicting cookie plugins in test setup doc (only one consent plugin active)
- Do NOT bump version or CHANGELOG
- PHPCS/PHPUnit must remain green

## Acceptance

- `npm run test:e2e` passes against local WP with plugin active
- README lists env vars and setup steps
- PR includes pass/fail table mapping to QA scenarios 6–19

Branch: `jules/cc-24-playwright-e2e`
```
