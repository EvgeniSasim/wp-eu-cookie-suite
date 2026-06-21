# CC-26 Multisite network settings + per-site override

```markdown
Task CC-26 — Add WordPress Multisite support for Privaro Cookie Consent Banner.

## Goal

Implement a clear configuration model for multisite:
1) **Network-wide default settings** managed by Super Admin.
2) **Per-site override option** so each site can either inherit network defaults or use its own local settings.

No regressions for single-site installs.

## Product behavior

### Single-site
- Keep current behavior unchanged (`get_option('wpeu_cs_settings')`).

### Multisite
- Add network settings storage in `get_site_option('wpeu_cs_network_settings')`.
- Add site-level flag in `wpeu_cs_settings`:
  - `use_network_defaults` (bool, default `true` on multisite; ignored on single-site).
- Effective settings resolution:
  - If not multisite: local settings only.
  - If multisite + `use_network_defaults=true`: use network settings.
  - If multisite + `use_network_defaults=false`: use local site settings.

### Admin UX
- In network admin (`/wp-admin/network/`):
  - Add plugin settings page for network defaults (same tabs/fields where practical).
  - Save to `wpeu_cs_network_settings`.
- In site admin:
  - Keep current settings page.
  - Add top control: “Use network defaults” toggle.
  - If enabled: show read-only notice (or disabled form controls) that settings are inherited from network.
  - If disabled: allow local editing/saving as now.

## Implementation requirements

1. Introduce a small settings resolver/service (e.g. `includes/Settings/SettingsRepository.php`) with methods:
   - `get_local_settings()`
   - `get_network_settings()`
   - `get_effective_settings()`
   - `is_using_network_defaults()`
2. Replace direct `get_option('wpeu_cs_settings')` reads in runtime paths with repository `get_effective_settings()`:
   - frontend banner/blocker/integrations
   - admin pages that should show effective values
   - AJAX handlers using settings
3. Keep scanner/cookie inventory/consent logs site-scoped (no network table merge).
4. Backward compatibility:
   - Existing installs keep working with current local settings.
   - On multisite, if no network settings yet, fallback safely to local until network saved.
5. Add capability checks:
   - network page: `manage_network_options`
   - site page: `manage_options`

## Tests

Add PHPUnit coverage for resolver logic:
- single-site returns local
- multisite + inherit returns network
- multisite + override returns local
- safe fallback when network option missing

If existing test bootstrap makes multisite hard, add focused unit tests for pure resolver methods.

## Non-goals (for this PR)
- No cross-site global consent log aggregation UI.
- No migration wizard.

## Acceptance checklist

- [ ] Single-site behavior unchanged.
- [ ] Super Admin can configure network defaults.
- [ ] Site admin can switch inherit/override.
- [ ] Frontend uses effective settings correctly.
- [ ] CI green (PHPCS + PHPUnit).

Branch: `jules/cc-26-multisite-settings`
One PR.
```

