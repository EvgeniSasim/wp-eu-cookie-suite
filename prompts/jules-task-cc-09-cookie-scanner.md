# CC-09 Cookie scanner

```markdown
Task CC-09 — Cookie scanner (admin).

Depends on CC-08 merged.

## Requirements
1. `includes/Scanner/Scanner.php` — AJAX/REST endpoint to scan URLs.
2. Default URL list: home_url(), plus pages from `wp_sitemaps_get_server()` or parse `sitemap.xml` (fallback: last 20 published pages).
3. For each URL: `wp_remote_get`, collect `Set-Cookie` headers; detect script src domains from HTML.
4. Merge with known cookie name heuristics (`_ga`, `_gid`, `_fbp`, `IDE`, etc.).
5. Admin Scanner tab: Start scan button, progress bar, results preview table.
6. Rate limit: max 1 scan per minute; capability `manage_options`; nonce.

Store results in option `wpeu_cs_scan_results` temporarily (DB table in CC-10).

One PR.
```
