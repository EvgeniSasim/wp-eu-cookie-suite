# CC-08 Google Consent Mode v2

```markdown
Task CC-08 — Google Consent Mode v2 + Site Kit hooks.

Depends on CC-07 merged.

## Requirements
1. `includes/Consent/GoogleConsentMode.php`:
   - Inject gtag consent default `denied` for ad_storage, analytics_storage, ad_user_data, ad_personalization before any GA/GTM.
   - Update consent on `wpeu-consent-updated`.
2. `includes/Integrations/GoogleSiteKit.php`:
   - Prevent Site Kit from loading analytics tags before consent (remove/disable early gtag output when statistics not allowed).
3. Admin Integrations toggle for Google Consent Mode.
4. Document in README how this interacts with GTM.

One PR. Test with inline gtag test snippet if Site Kit absent.
```
