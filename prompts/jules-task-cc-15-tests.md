# CC-15 Tests & export

```markdown
Task CC-15 — Tests, import/export, polish.

Depends on CC-14 merged.

## Requirements
1. PHPUnit bootstrap in `tests/` with at least:
   - Categories/consent helper tests
   - ScriptRegistry pattern matching tests
   - WpConsentBridge polyfill tests
2. `bin/install-wp-tests.sh` or documented WP test install (standard WP plugin pattern).
3. Tools tab: Export settings + banner texts + registry as JSON; Import with validation.
4. README: installation, build, compliance notes, migration hint from Complianz (manual checklist, no auto-migration required).
5. Fix PHPCS-level issues: escaping, text domain, prefixes.

Optional: Playwright smoke test script (skip if too heavy) — at minimum PHPUnit must pass.

One PR.
```
