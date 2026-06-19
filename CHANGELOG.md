# Changelog

All notable changes to **EU Cookie Consent Suite** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Breaking changes are summarized here and described in detail in [BREAKING_CHANGES.md](BREAKING_CHANGES.md).

## [Unreleased]

### Fixed

- CI: PHPCS file doc in `polyfills.php` and embedded PHP formatting in `Admin.php`.

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

[Unreleased]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.0.2...HEAD
[1.0.2]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/releases/tag/v1.0.0
