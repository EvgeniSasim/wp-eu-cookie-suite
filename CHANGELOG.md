# Changelog

All notable changes to **Privaro Cookie Consent Banner** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Breaking changes are summarized here and described in detail in [BREAKING_CHANGES.md](BREAKING_CHANGES.md).

## [Unreleased]

## [1.3.5] - 2026-08-31

### Fixed

- Consent cookies no longer flicker or disappear after accept: disable CookieConsent `manageScriptTags` / `autoClearCookies` (handled by plugin ScriptBlocker); write `wpeu_*` and CC cookies with WordPress `COOKIEPATH` / `COOKIE_DOMAIN`.
- GA cookie guard no longer clears `_ga` cookies immediately after statistics consent (integration-map aware check; respect client-side `wpeu_*` cookies before cleanup).

### Added

- Banner admin: editable **Preferences Intro Title/Description** and **Cookie Settings Link Label** per language.

## [1.3.4] - 2026-08-13

### Fixed

- Live Preview: avoid infinite recursion (and admin-ajax 500) when sanitizing `enabled_categories` under the preview settings filter.

### Changed

- `Tested up to` bumped to WordPress 7.1 (no editor/media/Abilities API surface in this plugin).

## [1.3.3] - 2026-06-24

### Fixed

- WordPress.org review: use WP 6.9 template enhancement output buffer when available; legacy buffer opens and closes in one flow.
- WordPress.org review: sanitize admin banner preview POST settings (including banner texts).
- WordPress.org review: validate and sanitize `wpeu_consent` JSON cookie after decode.

## [1.3.2] - 2026-06-21

### Fixed

- WordPress.org review: script registry host patterns are composed locally (blocking match rules only, no remote enqueues).
- WordPress.org review: stop deactivating legacy plugins on activation; show an admin notice instead.
- WordPress.org review: escape iframe placeholder and cookie policy shortcode output.
- WordPress.org review: explicitly close the script blocker output buffer on shutdown.

## [1.3.1] - 2026-06-21

### Fixed

- Multisite: unchecking **Use network defaults** now saves correctly (inherit toggle no longer reverted on save).
- Multisite: only **Banner** and **Integrations** tabs are read-only while inheriting; Cookies, Scanner, Consent Log, and site consent logging stay editable.
- Multisite: consent logging settings can be saved per subsite while still inheriting network banner defaults.
- Network Admin: language selector, add/remove language, and category actions use network URLs and persist to network settings.
- Network Admin: languages and custom categories read from network settings instead of the main site.
- Added missing consent revision bump handler (fatal on Tools tab).

### Added

- Network Admin **Overview** tab explaining network vs per-site responsibilities (languages, scanner, consent log).
- Warning when network defaults are empty or not yet saved.

## [1.3.0] - 2026-06-21

### Added

- Consent log proof snapshots v2: banner texts for visitor locale, policy URLs and intro, category labels/descriptions, banner UI settings, compliance flags, and `content_hash` (SHA-256).
- Snapshot download export bundles `log_record` + `proof_snapshot` for audit archives.
- PHPUnit coverage in `test-consent-snapshot.php`.

### Changed

- Updated `docs/compliance-consent-log.md` with snapshot v2 field reference.

## [1.2.3] - 2026-06-21

### Fixed

- Consent Log admin list table: bind to the plugin screen, set column headers explicitly, and show a clear empty state.
- Consent Log tab no longer inherits read-only multisite styling (`pointer-events: none`) that blocked filters and made the table appear broken.
- Guard empty visitor UUID cells in the log table.

### Changed

- Consent Log table wrapper allows horizontal scroll on narrow admin layouts.

## [1.2.2] - 2026-06-21

### Fixed

- Network Admin submenu title restored to «Privaro Cookie Consent Banner» (regression from v1.2.0 multisite work).
- Legacy-plugin admin notice wording aligned with product name.
- Uninstall removes `wpeu_cs_network_settings` from the main site when data retention is off.

### Changed

- JSON settings export uses `SettingsRepository::get_effective_settings()` so multisite inherit reflects runtime config.
- JSON import accepts exports from legacy plugin slugs (`wp-eu-cookie-suite`, `eu-cookie-consent-suite`).
- Documentation, AGENTS.md, and Jules roadmap updated to Privaro Cookie Consent Banner naming.

## [1.2.1] - 2026-06-21

### Fixed

- Fatal error on activation when a stale `vendor/autoload.php` exists from a previous install; always register `includes/autoload.php`.

## [1.2.0] - 2026-06-21

### Added

- WordPress Multisite support: network-wide default settings (`wpeu_cs_network_settings`) and per-site inherit/override via `use_network_defaults`.
- Network Admin settings page under Settings → Privaro Cookie Consent Banner.
- Site Admin toggle “Use network defaults” with read-only inherited settings view.
- `SettingsRepository` for effective settings resolution with PHPUnit coverage.

## [1.1.3] - 2026-06-11

### Fixed

- Fatal error on activation when `wp-eu-cookie-suite` / `eu-cookie-consent-suite` still active (duplicate `Plugin` class and helper functions).
- Admin notice when a legacy EU cookie plugin is already loaded; auto-deactivate legacy slugs on successful activation.

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

[Unreleased]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.3.5...HEAD
[1.3.5]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.3.4...v1.3.5
[1.3.4]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.3.3...v1.3.4
[1.3.3]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.3.2...v1.3.3
[1.3.2]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.3.1...v1.3.2
[1.3.1]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.3.0...v1.3.1
[1.3.0]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.2.3...v1.3.0
[1.2.3]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.2.2...v1.2.3
[1.2.2]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.2.1...v1.2.2
[1.2.1]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.1.3...v1.2.0
[1.1.3]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.1.2...v1.1.3
[1.1.2]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.0.2...v1.1.0
[1.0.2]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/EvgeniSasim/wp-eu-cookie-suite/releases/tag/v1.0.0
