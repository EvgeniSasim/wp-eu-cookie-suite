# CC-21 Consent records & revoke (EU compliance)

```markdown
Task CC-21 — Local consent logging, revoke flow, and admin audit trail.

Inspired by Complianz (do NOT copy GPL code). Reference behaviour only:
- Free: Proof of Consent snapshots, REST track endpoint, revoke link shortcode, revoke event
- Premium: Records of Consent (DB) + CSV export — implement lite version in this task

Depends on CC-18 merged (GA guard reacts to consent changes) and **CC-23 merged** (log arbitrary category keys from `Categories::get_all()`).

Read first: `docs/product-spec.md`, `AGENTS.md`.

## Legal / product goals (not legal advice)

EU GDPR Art. 7(1) accountability: site owner should be able to demonstrate consent was given.
Users must be able to withdraw consent as easily as giving it (Art. 7(3)).

Plugin provides **technical tooling**; DPO decides retention period and whether IP storage is needed.

## Requirements

### 1. Database — consent log table

On activation / dbDelta (same pattern as `wp_wpeu_cookies`):

Table `{prefix}wpeu_consent_log`:
- `id` bigint PK
- `consent_uuid` varchar(36) — anonymous visitor id (see §2)
- `event_type` varchar(32) — `accept_all`, `reject_all`, `save_preferences`, `revoke`, `policy_revision`
- `categories` text — JSON object with keys from `Categories::get_all()` slugs
- `consent_mode` varchar(16) — `optin` / `optout`
- `page_url` varchar(512)
- `locale` varchar(10)
- `banner_revision` int unsigned default 0
- `plugin_version` varchar(20)
- `ip_hash` varchar(64) nullable — SHA-256 of IP + site salt (optional, setting-controlled)
- `user_agent` varchar(255) nullable — truncated
- `created_at` datetime

Indexes: `consent_uuid`, `created_at`, `event_type`.

Optional `wp_user_id` bigint nullable for logged-in users.

### 2. Anonymous consent UUID (frontend)

- On first banner interaction, generate UUID v4 if missing; cookie `wpeu_consent_uuid` (Secure on HTTPS, SameSite=Lax, max 13 months).
- Send with every log request.
- Do NOT use UUID for cross-site tracking.

### 3. Server logging endpoint

`POST admin-ajax.php?action=wpeu_cs_log_consent` (or REST `wpeu-cs/v1/consent-log`):

- Public (nopriv + priv) with nonce localized in Banner assets
- Validate event_type whitelist, categories array, consent_uuid format
- Rate limit: max 10 requests/minute per IP (transient)
- Skip bots (empty user agent)
- Setting: `consent_logging_enabled` (default **true**)

Hook from `Banner.php` after consent sync — POST categories + inferred event type from CookieConsent callbacks.

### 4. Revoke consent (withdrawal)

**Frontend:**
- Clear all `wpeu_*` consent cookies + CookieConsent cookie
- `CookieConsent.reset(true)` then show banner/preferences
- Dispatch `wpeu-consent-revoked` (GA guard, GCM, script blocker listen)
- Log event `revoke` via §3
- Setting `reload_on_revoke` (default false)

**Shortcodes** (extend `Shortcodes.php`):
- `[wpeu_manage_consent]` — already exists; verify opens preferences
- `[wpeu_revoke_consent label="…"]` — full revoke flow

Labels editable via banner texts per locale.

### 5. Policy / banner revision

`consent_revision` and Tools tab bump already exist (CC-19). Wire CookieConsent `revision` from settings if not already. Do not duplicate admin bump UI.

### 6. Admin UI — new tab "Consent Log"

- WP_List_Table: date, event, categories summary, locale, page URL, consent_uuid (truncated), IP hash if enabled
- Filters: date range, event type
- Export CSV
- Settings: retention days (default 365), store IP hash (default off)
- Dashboard card: logs last 30 days
- Disclaimer: audit aid only

### 7. Proof snapshot (lite)

Optional column `config_snapshot` text (JSON): banner_revision, enabled_categories, plugin_version, cookie inventory count.
Admin download snapshot as JSON per row. No PDF.

### 8. Import/export

Extend `SettingsTransfer` for log-related settings only (not log rows).

### 9. Tests (PHPUnit)

- Repository insert / retention cleanup
- Event validation
- Revoke clears consent state (mock cookies)

### 10. Documentation

Add `docs/compliance-consent-log.md`.

## Out of scope

- GeoIP, IAB TCF, cloud storage, PDF proof, WordPress.org release (CC-22)

Branch: `jules/jules-task-cc-21-consent-records`
One PR.
```
