# CC-10 Cookie inventory

```markdown
Task CC-10 — Cookie inventory database and admin CRUD.

Depends on CC-09 merged.

## Requirements
1. Activation creates table `{$wpdb->prefix}wpeu_cookies` (name, domain, category, description, duration, service, detected_at, source).
2. `includes/Scanner/CookieRepository.php` — CRUD, merge scan results into inventory.
3. Admin Cookies tab: WP_List_Table with sort, filter by category, inline edit, delete, add manual row.
4. Link from Scanner: "Import scan to inventory" button.
5. Export inventory as CSV from Tools tab (basic).

One PR.
```
