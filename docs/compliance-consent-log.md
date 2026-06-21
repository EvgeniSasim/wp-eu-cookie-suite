# Compliance: Consent Records (Audit Trail)

To satisfy **GDPR Article 7(1)** (accountability), the plugin provides a technical audit trail of how and when consent was obtained or withdrawn.

## Technical implementation

### 1. Consent Log (Local Database)

All consent interactions are stored in the `{prefix}wpeu_consent_log` table. Each entry includes:

- **Consent UUID**: A randomly generated v4 UUID stored in a first-party cookie (`wpeu_consent_uuid`). This links multiple events (initial consent, preference changes, revocation) to the same pseudonymous visitor without storing name or email.
- **Event Type**: `accept_all`, `reject_all`, `save_preferences`, `revoke`, or `policy_revision`.
- **Categories**: JSON object of consent status per category (e.g. `{"statistics":true,"marketing":false}`).
- **Consent Mode**: Whether the site used strict EU opt-in or opt-out mode.
- **Context**: Page URL, visitor locale, banner revision number.
- **Environment**: Plugin version and truncated User Agent.
- **WordPress user ID** (if logged in): Stronger link for members/customers only.
- **IP Hash (Optional)**: If enabled in Tools, a salted SHA-256 hash of the visitor IP (pseudonym, not plain IP).

### 2. Proof of Consent (Snapshots v2)

For every log entry, the plugin stores a **Configuration Snapshot** (JSON) in `config_snapshot`. Since v1.3.0 this includes:

| Field | Purpose |
|-------|---------|
| `snapshot_version` | Schema version (currently `2`) |
| `banner_revision` | Policy/banner revision counter |
| `policy_urls` | Privacy and cookie policy URLs shown in settings |
| `banner_texts` | Full banner strings for the visitor locale |
| `policy_texts` | Cookie policy intro + template text for that locale |
| `categories` | Labels, descriptions, and which categories were offered |
| `banner_ui` | Layout, position, theme, primary colour |
| `eu_mode`, `show_reject_all`, `google_consent_mode` | Compliance-relevant UX flags |
| `content_hash` | SHA-256 fingerprint of the evidence payload |

Admin users can download a JSON export from **Consent Log → Download Snapshot**. The export bundles the log record plus `proof_snapshot` for archival or legal review.

**Note:** Snapshots demonstrate what the visitor was shown at consent time. They do not by themselves identify a natural person unless the visitor was logged in or optional IP hash is enabled and your DPO accepts that processing.

### 3. What is not stored (by design)

- Full name, email, or plain IP (unless you add such data outside this plugin)
- Cross-site tracking identifiers beyond the first-party UUID cookie

## Visitor rights

### Article 7(3): Right to withdraw consent

The `[wpeu_revoke_consent]` shortcode places a revoke link/button on policy pages. On revoke:

1. Consent cookies and `wpeu_consent_uuid` are cleared.
2. CookieConsent state resets.
3. A `revoke` event is logged (with snapshot).
4. The banner or preferences modal reappears (or the page reloads, depending on settings).

## Data retention

Default retention: **365 days** (configurable under **Tools**). Expired rows are deleted when an admin visits the plugin settings (daily cleanup task).

## Disclaimer

The Consent Log is a technical accountability tool, not legal advice. Data controllers must ensure their use complies with applicable law and their DPO requirements.
