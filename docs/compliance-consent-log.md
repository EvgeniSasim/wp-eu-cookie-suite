# Compliance: Consent Records (Audit Trail)

To satisfy **GDPR Article 7(1)** (accountability), the plugin provides a technical audit trail of how and when consent was obtained or withdrawn.

## Technical implementation

### 1. Consent Log (Local Database)

All consent interactions are stored in the `{prefix}wpeu_consent_log` table. Each entry includes:

- **Consent UUID**: A randomly generated v4 UUID stored in a first-party cookie (`wpeu_consent_uuid`). This allows linking multiple events (e.g., initial consent and later revocation) to the same anonymous visitor without identifying them.
- **Event Type**: `accept_all`, `reject_all`, `save_preferences`, `revoke`, or `policy_revision`.
- **Categories**: A JSON object of the consent status for each category (e.g., `{"statistics":true,"marketing":false}`).
- **Consent Mode**: Whether the plugin was in "Strict EU Mode" (opt-in) or "Opt-out" mode.
- **Context**: Page URL, visitor locale, and banner revision number.
- **Environment**: Plugin version and truncated User Agent.
- **IP Hash (Optional)**: If enabled, a salted SHA-256 hash of the visitor's IP address is stored. This can be used as additional evidence of the visitor's identity in a legal dispute without storing the plain IP.

### 2. Proof of Consent (Snapshots)

For every log entry, the plugin stores a **Configuration Snapshot** (JSON). This snapshot contains the banner settings at that exact moment (enabled categories, banner revision, etc.).

Admin users can download this snapshot from the **Consent Log** tab to demonstrate exactly what the visitor saw when they provided consent.

## Visitor rights

### Article 7(3): Right to withdraw consent

The plugin ensures that withdrawing consent is as easy as giving it.
The `[wpeu_revoke_consent]` shortcode can be used to place a "Revoke Consent" link or button on the Privacy Policy or Cookie Policy page.

When clicked:
1. All `wpeu_*` consent cookies are cleared.
2. The `wpeu_consent_uuid` is cleared.
3. The CookieConsent v3 state is reset.
4. A `revoke` event is logged to the database.
5. The banner or preferences modal is shown again (or the page reloads).

## Data retention

By default, consent logs are kept for **365 days**. This can be configured in the **Tools** tab. A background task (triggered when an admin visits the plugin settings) automatically deletes logs older than the retention period.

## Disclaimer

The Consent Log is a technical tool to aid accountability. It does not constitute legal advice. Site owners (Data Controllers) are responsible for ensuring their use of the plugin and the data collected complies with local laws and their own DPO requirements.
