# CC-20 CI & banner preview hardening

```markdown
Task CC-20 — GitHub Actions CI and admin banner preview tests.

Depends on CC-19 merged.

## Context
Banner live preview was broken because `admin.js` returned early when scanner button absent on Banner tab. Cursor hotfixed — verify and add regression coverage.

## Requirements

### 1. GitHub Actions workflow `.github/workflows/ci.yml`
- Trigger: push/PR to `main`
- PHP 8.1, 8.2 matrix
- Steps: `composer install`, `vendor/bin/phpunit` (with WP test libs installed via script or skip integration tests if no DB — document)
- Optional: PHPCS WordPress ruleset if `phpcs.xml.dist` added

### 2. Banner preview regression
- Ensure `admin.js` scanner and preview blocks are independent (no shared early return)
- `ajax_preview` merges: banner_ui, banner_texts, enabled_categories, show_reject_all, eu_mode
- Preview iframe must show CookieConsent modal with submitted texts/colors
- Add PHPUnit or JS-less test: assert `ajax_preview` response contains `CookieConsent.run` and `#cc-main` or `.cm`

### 3. Admin.js improvements (if not complete)
- Preview updates on layout/position/theme change
- `dataType: 'html'` on preview AJAX
- Graceful error message if preview AJAX fails

One PR.
```
