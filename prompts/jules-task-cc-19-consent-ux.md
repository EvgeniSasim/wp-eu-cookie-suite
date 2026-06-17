# CC-19 Consent UX & admin controls

```markdown
Task CC-19 — Re-open banner, admin toggles, GCM preferences mapping.

Depends on CC-18 merged.

## Requirements

### 1. Shortcode `[wpeu_manage_consent]`
- Renders a link/button: "Cookie settings" / translatable via banner texts or attribute `label="..."`
- On click: `CookieConsent.showPreferences()` (or `show()` if API differs — check bundled CC v3)
- Optional attribute: `style="link|button"`

### 2. Footer helper
Document in README: add shortcode to theme footer widget or `wp_footer` hook suggestion (no theme edits in plugin).

### 3. Admin toggles (Integrations or Banner tab)
- **Script blocker enabled** — checkbox bound to `blocker_enabled` (currently no UI, only dashboard readout)
- Save via existing `sanitize_settings` (extend active_tab handling)

### 4. Google Consent Mode
Map `preferences` category to `functionality_storage` in gtag consent update (in addition to existing statistics/marketing mapping).

### 5. Consent revision (optional but recommended)
Setting `consent_revision` int in settings. When incremented, CookieConsent should re-prompt (use CC v3 `revision` config option). Admin button "Reset all consents (bump revision)" on Tools tab with confirm.

### 6. Secure cookies on HTTPS
When `is_ssl()`, set `Secure` flag on `wpeu_*` consent cookies in Banner.js sync (client-side).

One PR.
```
