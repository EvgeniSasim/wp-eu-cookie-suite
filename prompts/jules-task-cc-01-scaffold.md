# CC-01 Plugin scaffold

```markdown
You are implementing task CC-01 for the WordPress plugin **WP EU Cookie Suite** in this repository.

Read first: `docs/product-spec.md`, `docs/architecture.md`, `AGENTS.md`.

## Goal
Replace the minimal stub with a production-ready plugin scaffold.

## Requirements
1. Main file `wp-eu-cookie-suite/wp-eu-cookie-suite.php` with plugin headers (Name: WP EU Cookie Suite, Text Domain: wp-eu-cookie-suite, Requires PHP 8.1, Requires at least WP 6.4).
2. PSR-4 autoload for namespace `WPEU\CookieSuite\` under `wp-eu-cookie-suite/includes/`.
3. `includes/Plugin.php` singleton: hooks on `plugins_loaded`, defines constants `WPEU_CS_VERSION`, `WPEU_CS_PATH`, `WPEU_CS_URL`.
4. Activation/deactivation hooks: set default options array `wpeu_cs_settings` with blocker enabled, EU mode on.
5. `composer.json` with PSR-4 autoload (no heavy deps yet).
6. `package.json` stub for future frontend build (`vanilla-cookieconsent` as devDependency placeholder).
7. Uninstall.php to optionally remove options (respect a `keep_data_on_uninstall` flag in settings).

## Do NOT
- Implement banner, blocker, or admin UI beyond a placeholder "coming soon" notice.
- Add Complianz as dependency.

## Acceptance
- Plugin activates without errors on WordPress.
- Code is organized and ready for CC-02 admin shell.

One PR only. Minimal diff.
```
