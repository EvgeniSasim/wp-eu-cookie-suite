# CC-03 CookieConsent frontend

```markdown
Task CC-03 — Integrate vanilla-cookieconsent v3 on the frontend.

Depends on CC-02 merged.

## Goal
Show a functional GDPR-style consent banner using https://cookieconsent.orestbida.com/ (v3).

## Requirements
1. Add `vanilla-cookieconsent` via npm; build bundle to `assets/js/cookieconsent.bundle.js` and CSS.
2. `includes/Frontend/Banner.php`:
   - Enqueue assets on front (not admin/login).
   - Build CookieConsent config from `wpeu_cs_settings` (default EN texts).
   - Categories: necessary (readOnly), preferences, statistics, marketing.
   - `guiOptions`, `categories`, `language.default`, `onConsent`, `onChange` hooks (JS).
3. Default: opt-in — no non-necessary cookies before consent.
4. Store consent in cookie `wpeu_consent` (JSON) + category cookies `wpeu_statistics`, `wpeu_marketing`, etc.
5. Document build steps in README.

## Do NOT
- Script blocking yet (CC-05).
- Full customizer (CC-12).

## Acceptance
Banner appears on frontend; Accept/Reject saves cookies; necessary always on.

One PR.
```
