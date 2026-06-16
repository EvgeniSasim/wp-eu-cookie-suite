# CC-13 Legal shortcodes

```markdown
Task CC-13 — Legal helpers and shortcodes.

Depends on CC-12 merged.

## Requirements
1. Shortcode `[wpeu_cookie_table]` — HTML table from cookie inventory (grouped by category). Attributes: `category`, `show_description`.
2. Shortcode `[wpeu_cookie_policy]` — wrapper with editable template from settings (default DE/EN paragraphs placeholders).
3. Admin Tools tab: editable policy intro text per locale.
4. Blocks (optional): register Gutenberg block wrapping cookie table if `@wordpress/scripts` already in project; else shortcodes only.

Disclaimer notice in admin: not legal advice.

One PR.
```
