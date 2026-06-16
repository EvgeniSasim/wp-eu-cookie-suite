# CC-05 Script blocker

```markdown
Task CC-05 — Output-buffer script blocker (Complianz-like behavior, original code).

Depends on CC-04 merged.

## Goal
Block third-party scripts until user consents to the matching category.

## Requirements
1. `includes/Frontend/ScriptBlocker.php`:
   - Start output buffer on `template_redirect` (skip admin, ajax, cron, feeds).
   - Parse HTML for `<script>` (src and inline markers) and block/replace when category not allowed.
   - Use `type="text/plain"` + `data-category="statistics"` pattern compatible with CookieConsent unblock.
2. Respect `wpeu_cs_settings['blocker_enabled']`.
3. Hook early `wp_head` priority to inject blocker bootstrap JS that activates scripts on consent event `wpeu-consent-updated`.
4. Do not break WordPress core scripts (jquery, wp-* from same host).

## Reference behavior
Block GA/gtag/GTM until statistics consent. Original implementation only.

## Acceptance
With test GA snippet in theme, `_ga` not set until statistics accepted.

One PR. Registry of URLs is CC-06 — use minimal hardcoded GA/GTM patterns here.
```
