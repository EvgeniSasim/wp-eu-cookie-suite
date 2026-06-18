# CC-22 WordPress.org release pack & author branding

```markdown
Task CC-22 — Prepare plugin for submission to the WordPress.org Plugin Directory as an original GPL plugin by **Evgenii Sasim**.

Depends on CC-21 merged (readme must describe consent log privacy behaviour).

Read first: `docs/product-spec.md`, `AGENTS.md`.

## Author branding (mandatory)

Update plugin metadata everywhere it appears:

| Field | Value |
|-------|-------|
| **Author** | `Evgenii Sasim` |
| **Author URI** | `https://www.instagram.com/evgenii.sasim/` |
| **Plugin URI** (until .org approval) | `https://github.com/EvgeniSasim/wp-eu-cookie-suite` |
| **License** | `GPL-2.0-or-later` |
| **License URI** | `https://www.gnu.org/licenses/gpl-2.0.html` |

Files to update:
- `wp-eu-cookie-suite/wp-eu-cookie-suite.php` plugin header
- Root `composer.json` — add `"authors"` array with name + homepage Instagram URL
- Root `README.md` — add **Author** section (no client-specific branding)

Do **not** mention Complianz in plugin name or description as if affiliated. OK: "alternative to commercial CMP plugins" in docs only.

## Version for first public release

Bump plugin version from `0.1.0` → **`1.0.0`** in:
- `wp-eu-cookie-suite.php` header `Version:`
- `WPEU_CS_VERSION` constant (if defined separately)
- `readme.txt` `Stable tag: 1.0.0`
- Changelog section in readme

SemVer: first wordpress.org release = 1.0.0.

## readme.txt (WordPress.org format)

Create `wp-eu-cookie-suite/readme.txt` (inside plugin folder, sibling to main PHP file).

Use [official readme standard](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/). Required header:

    === WP EU Cookie Suite ===
    Contributors: evgeniisasim
    Tags: cookies, gdpr, cookie-consent, privacy, eu
    Requires at least: 6.4
    Tested up to: 6.8
    Requires PHP: 8.1
    Stable tag: 1.0.0
    License: GPLv2 or later
    License URI: https://www.gnu.org/licenses/gpl-2.0.html

**Contributors:** use wordpress.org username `evgeniisasim`. Add note in `docs/wordpress-org-submission.md` that author must register matching WP.org account before submit.

**Short description** (max ~150 chars):
EU/GDPR cookie consent banner, script blocking, cookie scanner, WP Consent API and Google Consent Mode v2.

**Sections:** Description, Installation, FAQ (min 3), Screenshots list, Changelog 1.0.0.

**Privacy / data collection** (CC-21): FAQ + Description paragraph on optional local consent log (anonymous UUID, categories, URL, optional IP hash), retention, no third-party phone-home.

## Third-party credits

Create `wp-eu-cookie-suite/THIRD-PARTY.md`:
- **vanilla-cookieconsent** v3 — MIT — bundled in `assets/js/cookieconsent.bundle.js` + CSS
- https://github.com/orestbida/cookieconsent

## Release artifact

### `.distignore`

Create `wp-eu-cookie-suite/.distignore` — exclude tests, bin, composer files, node_modules, etc.

### `scripts/build-release.sh`

- Validate readme stable tag matches plugin Version
- Create `build/wp-eu-cookie-suite.zip`
- Exit non-zero on mismatch

## PHPUnit

Add `tests/test-readme.php`: stable tag matches version; required readme headers present.

## WordPress.org marketing assets

Create `wordpress-org/assets/` at repo root:
- icon-128x128.png, icon-256x256.png
- banner-772x250.png, banner-1544x500.png
- screenshot-1..4.png (mock UI OK if no runtime)
- `wordpress-org/assets/README.md` with SVN upload instructions

Design: EU blue (#003399), cookie/shield icon, tagline "GDPR-ready cookie consent for WordPress".

## Submission checklist

Create `docs/wordpress-org-submission.md` (Russian OK): account, slug, zip submit, SVN trunk/tag/assets, guideline reminders.

## Code quality gates

- No node_modules/vendor in release zip
- Text domain `wp-eu-cookie-suite`
- Verify/fix `uninstall.php` removes options + custom tables
- No secrets or client-specific URLs in production code

## Out of scope

- Actually submitting to wordpress.org (human step)
- SVN credentials
- Premium monetization

Branch: `jules/jules-task-cc-22-wordpress-org-release`
One PR.
```
