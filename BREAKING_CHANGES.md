# Breaking Changes

This file lists **breaking changes** between releases of Privaro Cookie Consent Banner.
For the full history see [CHANGELOG.md](CHANGELOG.md).

When upgrading across a listed version, read the matching section and follow the migration steps.

---

## 1.0.1 — Plugin rename (WP.org slug)

**Affects:** sites that installed builds named `wp-eu-cookie-suite` before the rename.

| Before | After |
|--------|--------|
| Folder `wp-eu-cookie-suite/` | Folder `privaro-cookie-consent-banner/` |
| Main file `wp-eu-cookie-suite.php` | Main file `privaro-cookie-consent-banner.php` |
| Text domain `wp-eu-cookie-suite` | Text domain `privaro-cookie-consent-banner` |
| WP.org slug (target) `wp-eu-cookie-suite` | WP.org slug `privaro-cookie-consent-banner` |

**Unchanged (no migration needed):**

- Database options: `wpeu_cs_settings`, `wpeu_cs_scan_results`, etc.
- Cookie names: `wpeu_consent`, `wpeu_statistics`, `wpeu_consent_uuid`, …
- Table prefix: `{prefix}_wpeu_cookies`, `{prefix}_wpeu_consent_log`
- PHP hooks/filters: `wpeu_cs_*`, `wpeu_known_script_tags`, …

**Migration:**

1. Deactivate the old plugin (do **not** delete data if prompted — options stay in `wp_options`).
2. Delete the old `wp-eu-cookie-suite` folder.
3. Install `privaro-cookie-consent-banner` (zip or git) and activate.
4. Re-save settings once if custom translations used the old text domain (`.po`/`.mo` files must use `privaro-cookie-consent-banner`).

**Do not deploy** legacy `build/wp-eu-cookie-suite.zip`; use `build/privaro-cookie-consent-banner.zip` only.

---

## 1.0.1 — Consent cookies timing

**Affects:** custom JS or server logic that assumed consent cookies exist on first page view.

| Before | After |
|--------|--------|
| `wpeu_consent_uuid` set on every page load | Set only after first Accept / Reject / Save preferences |
| `wpeu_*` / `wpeu_consent` synced via `onConsent` even without valid consent | Sync only when `CookieConsent.validConsent()` is true |

**Migration:**

- If you read `wpeu_consent_uuid` before banner interaction, use `CookieConsent.validConsent()` or listen for `wpeu-consent-updated`.
- Clear site/page cache after upgrade so old inline banner script is not served.

---

## 1.0.1 — CookieConsent v3 JavaScript API

**Affects:** themes or snippets that patched the banner after our script ran.

| Removed / invalid | Use instead |
|-------------------|-------------|
| `cc.onConsent(fn)` | Pass `onConsent` in config to `cc.run(config)` |
| `cc.onChange(fn)` | Pass `onChange` in config to `cc.run(config)` |

**Migration:** Remove any custom calls to `cc.onConsent` / `cc.onChange`. Hook `wpeu-consent-updated` or WP Consent API (`wp_has_consent`) instead.

---

## Template for future releases

```markdown
## X.Y.Z — Short title

**Affects:** …

| Before | After |
|--------|--------|
| … | … |

**Migration:**

1. …
```

When adding a breaking change, also add a **Changed** or **Removed** bullet under `[X.Y.Z]` in `CHANGELOG.md` and a short note in `readme.txt` `== Changelog ==`.
