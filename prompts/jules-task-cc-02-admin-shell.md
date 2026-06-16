# CC-02 Admin shell

```markdown
Task CC-02 — Admin settings shell for WP EU Cookie Suite.

Depends on CC-01 merged to main.

## Goal
WordPress admin UI entry point with tabs structure.

## Requirements
1. `includes/Admin/Admin.php` — menu **Settings → EU Cookie Suite** (or top-level menu with dashicon `shield`).
2. Tabs: Dashboard | Banner | Cookies | Scanner | Integrations | Tools.
3. Dashboard tab: status cards (plugin version, blocker status, consent API status) reading from options.
4. Register settings via Settings API: group `wpeu_cs_settings`, sanitize callback.
5. Enqueue admin CSS/JS only on plugin pages (`assets/css/admin.css`, `assets/js/admin.js`).
6. Capability `manage_options`, nonces on forms.
7. Placeholder content on non-Dashboard tabs ("Implemented in task CC-XX").

## Style
Match WordPress admin patterns; clean tables and notices.

One PR. No frontend banner yet.
```
