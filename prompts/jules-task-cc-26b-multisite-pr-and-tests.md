# CC-26b Multisite — submit PR + CI PHPUnit (follow-up)

```markdown
Task CC-26b — Finish WordPress Multisite support and **open the PR**.

## Context

Session `8519204115108289871` (CC-26) marked COMPLETED but **no GitHub PR was created**. Re-implement on `main` and submit one PR. Use the CC-26 spec below; do not stop before PR is open.

## Goal

Same as CC-26: network defaults + per-site override. Single-site unchanged.

## Product behavior

### Single-site
- Keep current behavior (`get_option('wpeu_cs_settings')` via repository returns local only).

### Multisite
- Network storage: `get_site_option('wpeu_cs_network_settings')`.
- Site flag in `wpeu_cs_settings`: `use_network_defaults` (bool, default `true` on multisite; ignored on single-site).
- Effective settings:
  - not multisite → local
  - multisite + `use_network_defaults=true` → network (fallback to local if network empty)
  - multisite + `use_network_defaults=false` → local

### Admin UX
- Network Admin (`network_admin_menu`): settings page, save to `wpeu_cs_network_settings`, cap `manage_network_options`.
- Site Admin: toggle “Use network defaults”; when ON — read-only/disabled form + notice; when OFF — edit/save local as now, cap `manage_options`.

## Implementation

1. `includes/Settings/SettingsRepository.php`:
   - `get_local_settings()`, `get_network_settings()`, `get_effective_settings()`, `is_using_network_defaults()`
2. Replace runtime `get_option('wpeu_cs_settings')` with `get_effective_settings()` in:
   - Frontend (Banner, ScriptBlocker, IframeProcessor, Shortcodes)
   - Consent + Integrations
   - Admin preview/AJAX that reflect frontend config
3. **Keep site-scoped** (no network merge): scanner, scan results, consent log tables, `wpeu_cs_ip_hash_secret`, `wpeu_cs_last_*`.
4. `Plugin::activate()`: on multisite new installs set `use_network_defaults => true` in defaults.
5. Backward compat: existing single-site and multisite local-only installs unchanged until network saved.

## Tests (mandatory — use existing CI bootstrap)

- Add `tests/test-settings-repository.php` using **existing** PHPUnit bootstrap (`tests/bootstrap.php`, same style as `tests/test-admin-sanitize.php`).
- Cover: single-site local; multisite inherit→network; multisite override→local; missing network option fallback.
- **Do NOT** add `test_runner.php`, `test_output.txt`, or custom runners.
- PHPCS + PHPUnit must pass in GitHub Actions.

## Release

- Bump version to **1.2.0** in plugin header, `WPEU_CS_VERSION`, `readme.txt` Stable tag.
- CHANGELOG entry for Multisite support.
- `readme.txt`: Network: Yes (if applicable).

## Non-goals

- No cross-site consent log UI.
- No migration wizard.

## Acceptance

- [ ] PR opened (required — do not end session without PR)
- [ ] CI green
- [ ] No stray debug/test runner files in repo
- [ ] Single-site regression safe

Branch: `jules/cc-26-multisite-settings`
One PR.
```
