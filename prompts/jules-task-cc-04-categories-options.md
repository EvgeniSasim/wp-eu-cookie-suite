# CC-04 Categories & options

```markdown
Task CC-04 — Category model and admin-editable consent options.

Depends on CC-03 merged.

## Goal
Centralize consent categories and expose key toggles in admin (Banner tab basics).

## Requirements
1. `includes/Consent/Categories.php` — constants, labels, descriptions, default enabled state.
2. Filter `wpeu_consent_categories` for extensibility.
3. Admin Banner tab fields:
   - Enable/disable categories (statistics, marketing, preferences)
   - Privacy policy URL, Cookie policy URL
   - "Reject all" visibility toggle
   - EU strict mode (block all non-necessary for everyone vs auto-detect — v1: checkbox "Strict EU mode")
4. Frontend banner reads these options into CookieConsent config.
5. Helper `wpeu_cs_user_has_consent( string $category ): bool` in `includes/functions.php`.

## Acceptance
Changing admin options updates banner behavior after save.

One PR.
```
