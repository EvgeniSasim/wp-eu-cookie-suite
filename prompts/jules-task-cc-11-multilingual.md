# CC-11 Multilingual

```markdown
Task CC-11 — Multilingual banner strings.

Depends on CC-10 merged.

## Requirements
1. Settings structure: `banner_texts[locale][key]` for title, description, accept, reject, manage, per-category descriptions.
2. Default locales: `en`, `de` with sensible GDPR texts.
3. Detect locale: `get_locale()`, Polylang `pll_current_language()`, WPML `apply_filters('wpml_current_language')`.
4. Admin Banner tab: language selector to edit strings per locale.
5. CookieConsent `language` config with `translations` object; auto switch on front.

Filters: `wpeu_cs_banner_locale`, `wpeu_cs_banner_texts`.

One PR.
```
