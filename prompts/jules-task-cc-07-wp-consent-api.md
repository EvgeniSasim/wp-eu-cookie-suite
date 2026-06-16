# CC-07 WP Consent API

```markdown
Task CC-07 — WordPress Consent API bridge.

Depends on CC-06 merged.

## Requirements
1. `includes/Consent/WpConsentBridge.php`:
   - If plugin `wp-consent-api` not active, register polyfill for `wp_has_consent()`, `wp_set_consent()`, `wp_get_consent_type()`.
   - Map categories: statistics → `statistics`, marketing → `marketing`, preferences → `preferences`, necessary → `functional`.
2. On CookieConsent onConsent/onChange, call `wp_set_consent()` for each category.
3. Consent type: `optin` for EU strict mode.
4. Admin Dashboard card: "WP Consent API: active (native|polyfill)".
5. Optional: declare compliance via `wp_consent_api_registered_{plugin}` filter.

Follow https://github.com/rlankhorst/consent-api conventions.

One PR.
```
