# WP EU Cookie Suite

WordPress-плагин cookie consent для EU/DE (GDPR, ePrivacy, TTDSG): баннер на [vanilla-cookieconsent](https://cookieconsent.orestbida.com/), блокировка скриптов, сканер cookies, мультиязычность, WP Consent API.

## Requirements

- PHP 8.1+
- WordPress 6.4+

## Installation

1. Download or clone the repository.
2. Copy the `wp-eu-cookie-suite/` directory to your `wp-content/plugins/` folder.
3. Activate the plugin via the WordPress Admin dashboard.
4. Go to **EU Cookie Suite** in the sidebar to configure settings.

## Development & Build

The plugin uses `esbuild` to bundle vanilla-cookieconsent assets.

```bash
cd wp-eu-cookie-suite
npm install
npm run build
```

Built assets (`cookieconsent.bundle.js`, `cookieconsent.bundle.css`) are committed for production use.

## Testing

PHPUnit tests are included in the `tests/` directory.

1. Install dependencies:
   ```bash
   cd wp-eu-cookie-suite
   composer install
   ```
2. Run tests:
   ```bash
   ./vendor/bin/phpunit
   ```

## Compliance Notes

- **Opt-in by default**: When "Strict EU Mode" is enabled, all non-necessary categories are blocked until user consent.
- **Script Blocker**: Automatically intercepts and blocks third-party scripts (GA, Facebook, etc.) using an output buffer.
- **Google Consent Mode v2**: Fully supports GCM by injecting default `denied` status and updating via `gtag`.
- **WP Consent API**: Compliant with the [WordPress Consent API](https://github.com/rlorenzo/wp-consent-api).

## Migration from Complianz

If you are migrating from Complianz, follow this manual checklist:

1. **Settings**: Manually copy your banner texts and UI preferences.
2. **Cookie Inventory**: Export your cookies from Complianz (if possible) or use the built-in scanner in WP EU Cookie Suite to re-detect them. You can also use the JSON import/export in the **Tools** tab.
3. **Integrations**: Enable the corresponding integrations (Google Site Kit, ACF, etc.) in the **Integrations** tab.
4. **Shortcodes**: Replace any Complianz shortcodes with WP EU Cookie Suite equivalents:
   - `[wpeu_cookie_table]` for the inventory.
   - `[wpeu_cookie_policy]` for the policy page.

## License

GPL-2.0-or-later
