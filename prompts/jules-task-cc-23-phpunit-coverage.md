# CC-23 PHPUnit coverage expansion

```markdown
Task CC-23 — Expand PHPUnit coverage after v1.1.1 QA audit.

## Goal

Add automated tests for gaps identified in QA. No new product features — tests only.

## Deliverables

1. `tests/test-scanner-ajax.php` — WP_Ajax_UnitTestCase:
   - `wpeu_cs_get_scan_urls` (nonce, capability, URL list)
   - `wpeu_cs_scan_url` (use filters/mocks where HTTP is involved)
   - `wpeu_cs_import_scan`

2. `tests/test-ajax-log-consent.php` — `wpeu_cs_log_consent`:
   - Invalid nonce rejected
   - Row inserted when logging enabled
   - No-op when logging disabled
   - `wpeu_cs_hash_ip()` when IP logging enabled

3. `tests/test-admin-sanitize.php` — settings sanitize:
   - Banner: primary_color, enabled_categories, eu_mode
   - Integrations toggles
   - Tools consent logging flags

4. `tests/test-uninstall.php` — when keep_data_on_uninstall is false:
   - `wpeu_cs_ip_hash_secret` removed
   - Custom tables dropped

## Constraints

- Match style in existing `tests/test-*.php`
- PHPCS clean; CI must pass
- Do NOT bump version or CHANGELOG
- Text domain: `privaro-cookie-consent-banner`
- Plugin path: `privaro-cookie-consent-banner/`

One PR on branch `jules/cc-23-phpunit`.
```
