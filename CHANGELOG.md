# Changelog

All notable changes to **Privaro Cookie Consent Banner** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Breaking changes are summarized here and described in detail in [BREAKING_CHANGES.md](BREAKING_CHANGES.md).

## [Unreleased]

## [1.1.2] - 2026-06-11

### Fixed

- Consent banner reappears after `[wpeu_revoke_consent]` / `wpeu-cs-revoke` without a full page reload when `reload_on_revoke` is off (`cc.run` after `reset`).

### Added

- PHPUnit: scanner AJAX (`test-scanner-ajax.php`), consent log AJAX (`test-ajax-log-consent.php`), admin settings sanitize (`test-admin-sanitize.php`), uninstall cleanup (`test-uninstall.php`).

## [1.1.1] - 2026-06-11

### Fixed

- PHPCS: short description on deprecated `Banner::render_config()` docblock (CI).
- Admin menu title now matches plugin name «Privaro Cookie Consent Banner».
- PHPUnit admin preview test aligned with v1.1.0 (no `custom_css`, minified CSS variables).
- Remove dead `category_limit` admin notice after custom-category cap removal.
- Delete `wpeu_cs_ip_hash_secret` on uninstall when plugin data is not retained.

### Added

- PHPUnit coverage for `wpeu_cs_hash_ip()` helper.

## [1.1.0] - 2026-06-20

### Changed

- Rename to **Privaro Cookie Consent Banner** (`privaro-cookie-consent-banner`) for WordPress.org distinctiveness and search.
- Remove custom CSS field; banner styling via primary color and bundled CSS variables only.
- Remove artificial cap on custom consent categories.
- Move frontend inline scripts/styles to `wp_enqueue_script` / `wp_add_inline_script` API.
- Document third-party services in `readme.txt`; fix Plugin URI.

### Fixed

- Fatal error on activation (namespace before ABSPATH guard).
- CookieConsent v3 callbacks via `cc.run(config)`.
- Defer consent UUID and sync cookies until valid user choice.
- Consent log IP hashing uses site-specific secret instead of `AUTH_SALT`.
- Theme analytics integration blocks ACF output only (no raw snippet injection).
- PHPCS: `polyfills.php` file doc and embedded PHP in `Admin.php`.

## [1.0.2] - 2026-06-19

### Fixed

- `ConsentLogger.php`: ABSPATH guard order (same fatal-error class as 1.0.1 fixes).

## [1.0.1] - 2026-06-19

### Fixed

- Fatal error on plugin activation: move `ABSPATH` guard after `namespace` in all namespaced PHP files.
- CookieConsent v3: register `onConsent` / `onFirstConsent` / `onChange` via `cc.run(config)` instead of non-existent `cc.onConsent()` methods.
- Do not set `wpeu_consent_uuid` or sync `wpeu_*` consent cookies until the user has valid consent.
- Block Google Fonts, reCAPTCHA, and `<link>` tags before consent; tighten Google Analytics guard categories.

### Changed

- Plugin directory and main file renamed to `eu-cookie-consent-suite` (see [BREAKING_CHANGES.md](BREAKING_CHANGES.md)).

## [1.0.0] - 2026-06-18

### Added

- Initial WordPress.org release.
- CookieConsent v3 banner (opt-in EU mode, preferences modal, reject all).
- Automatic script and iframe blocking with category unblocking after consent.
- Cookie scanner and inventory (`wp_wpeu_cookies`).
- WP Consent API polyfill and bridge.
- Google Consent Mode v2 defaults and updates.
- Integrations: Google Site Kit, GA cookie guard, Contact Form 7 reCAPTCHA, theme ACF analytics, iframe placeholders.
- Consent logging (local DB), revoke flow, admin audit tab.
- Settings JSON import/export, multilingual banner texts (EN/DE).
- Admin live banner preview (CC-16.1).

[Unreleased]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.1.2...HEAD
[1.1.2]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.0.2...v1.1.0
[1.0.2]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/releases/tag/v1.0.0
