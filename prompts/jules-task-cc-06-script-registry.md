# CC-06 Script registry

```markdown
Task CC-06 — Script registry and admin UI for custom blocked services.

Depends on CC-05 merged.

## Requirements
1. `includes/Frontend/ScriptRegistry.php` — list of services (google-analytics, gtm, facebook-pixel, hotjar, clarity, google-maps, youtube, vimeo) with URL patterns and category.
2. Filter `wpeu_known_script_tags` (array of regex or substring rules).
3. ScriptBlocker uses registry instead of hardcoded patterns.
4. Admin → Integrations tab: enable/disable each service; textarea for custom block lines (one per line, Complianz-style `-url-` markers supported).
5. Dashboard shows count of active block rules.

One PR.
```
