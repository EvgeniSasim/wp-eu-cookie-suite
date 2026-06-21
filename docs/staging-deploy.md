# Staging deploy — Privaro Cookie Consent Banner

Step-by-step guide for deploying a release build to a staging WordPress site and running a post-deploy cookie audit.

## Prerequisites

- SSH/SFTP or GitLab **WPSITE_*** deployment variable configured for the target site (see your theme `deployment_info.md` pattern: `WPSITE_<site_id>` file variable with `DOMAIN`, `PATH`, `CONNECTION`, `SERVER`, `USER`, `PASSWORD`).
- WP-CLI on the server (optional but recommended).
- Local build: PHP 8.1+, bash.

Never commit credentials, `.env` files, or WPSITE variable contents to this repository.

## 1. Build the release zip

```bash
bash scripts/build-release.sh
```

Output: `build/privaro-cookie-consent-banner.zip`

Verify version in the archive matches `readme.txt` **Stable tag** (currently check `privaro-cookie-consent-banner.php` header).

## 2. Remove legacy plugins

On the staging site, deactivate and delete (if present):

- `wp-eu-cookie-suite`
- `eu-cookie-consent-suite`

Only **Privaro Cookie Consent Banner** should remain active for cookie consent.

Via WP-CLI (on server):

```bash
wp plugin deactivate wp-eu-cookie-suite eu-cookie-consent-suite privaro-cookie-consent-banner 2>/dev/null || true
wp plugin delete wp-eu-cookie-suite eu-cookie-consent-suite 2>/dev/null || true
```

## 3. Install the new version

### Option A — WP Admin

1. **Plugins → Add New → Upload Plugin**
2. Choose `build/privaro-cookie-consent-banner.zip`
3. **Replace current with uploaded** if prompted
4. Activate

### Option B — SFTP + unzip

1. Upload zip to server
2. Extract into `wp-content/plugins/privaro-cookie-consent-banner/`
3. Activate via admin or WP-CLI:

```bash
wp plugin activate privaro-cookie-consent-banner
```

### Option C — GitLab deploy pipeline

1. Add artifact or manual upload step that copies the zip to the server `PATH` from WPSITE config
2. Run unzip/activate on the worker or via SSH job
3. Follow the same legacy-plugin removal step before activate

## 4. Verify in admin

1. **Settings → Privaro Cookie Consent Banner** (or **Privaro Cookie Consent Banner** top-level menu)
2. Confirm **Plugin Version** on Dashboard matches the release (e.g. 1.3.1+)
3. **Integrations → Script blocker enabled** — ON for staging tests
4. Run **Scanner** if cookie inventory is empty

### Multisite staging

- Configure defaults in **Network Admin → Settings → Privaro Cookie Consent Banner**
- On each subsite: Scanner, Cookies, Consent Log are per-site; Banner/Integrations inherit unless override is disabled

## 5. Post-deploy cookie audit

Run the helper script against the staging homepage URL:

```bash
bash scripts/verify-consent-audit.sh https://staging.example.com
```

Manual smoke checklist:

- [ ] Only one cookie consent plugin active
- [ ] Banner visible on first visit (EU mode / no prior consent cookie)
- [ ] **Reject all** — no `_ga` / `_fbp` until consent (check DevTools → Application → Cookies)
- [ ] **Accept all** — statistics/marketing cookies allowed per settings
- [ ] **Manage consent / revoke** — banner returns; optional page reload if enabled
- [ ] Consent log records events when logging enabled (Tools tab)
- [ ] No PHP fatal in `wp-content/debug.log` after browse + admin save

## 6. Production (drjv.org or live)

Repeat steps 2–5 on production during a maintenance window. Keep a DB backup before plugin swap.

## Troubleshooting

| Symptom | Check |
|--------|--------|
| Blocker Status **Inactive** | Integrations → enable **Script blocker enabled**; on multisite save in Network Admin if inheriting |
| Banner missing | Clear cache; check `eu_mode`; bump consent revision if testing re-prompt |
| Legacy plugin conflict | Admin notice about wp-eu-cookie-suite — deactivate legacy slug |
| White screen on activate | PHP 8.1+; remove stale `vendor/` from old install |
