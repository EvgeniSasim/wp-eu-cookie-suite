# CC-14 Integrations pack

```markdown
Task CC-14 — Third-party integrations.

Depends on CC-13 merged.

## Requirements
1. `includes/Integrations/ThemeAnalytics.php` — intercept ACF option field `analytics` (common pattern): output via `wp_head` only when statistics consented (configurable field name in settings).
2. `includes/Integrations/IframePlaceholder.php` — YouTube/Vimeo/Google Maps iframes → placeholder until marketing/statistics consent (use registry categories).
3. Contact Form 7: optional checkbox "only load recaptcha after marketing consent" if detectable.
4. Admin Integrations toggles per integration.
5. Filter `wpeu_cs_integrations` for third-party extensions.

One PR. Original code; test with sample iframe embed filter on `the_content`.
```
