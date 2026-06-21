=== Privaro Cookie Consent Banner ===
Contributors: evgenij347
Tags: cookies, gdpr, cookie-consent, privacy, eu
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.2.0
Network: Yes
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GDPR and EU cookie consent banner with script blocking, cookie scanner, and Google Consent Mode v2.

== Description ==

Privaro Cookie Consent Banner is a comprehensive solution for managing cookie consent on your WordPress site, designed for compliance with GDPR, ePrivacy, and other privacy regulations. It features a modern, accessible banner powered by vanilla-cookieconsent v3, automatic script blocking, and a built-in cookie scanner.

Key features include:
* **Customizable Cookie Banner:** Fully responsive UI with light/dark modes and multiple layouts.
* **Consent Categories:** Necessary, Preferences, Statistics, Marketing, plus optional custom categories.
* **Automatic Script Blocking:** Blocks third-party scripts (GA, Pixel, etc.) and iframes (YouTube, Vimeo, Google Maps) until consent is granted.
* **Google Consent Mode v2:** Native support for GCM v2, including integration with Google Site Kit.
* **WP Consent API Support:** Fully compatible with the WordPress Consent API.
* **Cookie Scanner & Inventory:** Automatically discover cookies used on your site and maintain a categorized inventory.
* **Multilingual Support:** Ready for translation and supports Polylang/WPML.
* **Multisite Support:** Network-wide default settings with per-site inherit or override.
* **Consent Logging:** Optional local logging of consent events for audit purposes.

= Privacy & Data Collection =
By default, the plugin does not collect or transmit any personal data to third-party servers. If the "Consent Logging" feature is enabled in settings, the plugin stores anonymized records of consent decisions in your local database. These records include a random UUID, the selected categories, the page URL, and (optional) an anonymized IP hash. This data is used solely for compliance accountability and is not shared with any third parties.

== Installation ==

1. Upload the `privaro-cookie-consent-banner` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to 'Privaro Cookie Consent Banner' in the WordPress admin to configure your banner and settings.
4. Run the Cookie Scanner to populate your cookie inventory.

== Credits ==

This plugin bundles vanilla-cookieconsent v3 (MIT). See the plugin source headers and bundled assets for license details.

== FAQ ==

= How do I enable Google Consent Mode v2? =
Go to the 'Integrations' tab in the plugin settings and ensure 'Google Consent Mode v2' is enabled. The plugin will automatically inject the necessary default consent states.

= Does this plugin block YouTube videos? =
Yes, the plugin includes an Iframe Processor that automatically replaces YouTube, Vimeo, and Google Maps iframes with a placeholder until the user grants 'Marketing' consent.

= Where is the consent log stored? =
The consent log is stored entirely within your WordPress database in a custom table (`wp_wpeu_consent_log`). No data is sent to external servers. You can manage the retention period in the settings.

= Can I customize banner colors? =
Yes. Use the Primary Color picker on the Banner settings tab. Light and dark themes are supported.

== External services ==

This plugin **does not** load third-party scripts by itself. It blocks or allows scripts that **your theme or other plugins** add to the page, based on the visitor's consent.

When a visitor grants consent, the following **may** be loaded by your site configuration (not by this plugin directly):

**Google Tag Manager / Google Analytics (Google LLC)**
Used when your site enqueues gtag or GTM after statistics consent.
Data sent: page URL, browser data, and analytics events as configured on your site.
Terms: https://policies.google.com/terms
Privacy: https://policies.google.com/privacy

**Google Fonts (Google LLC)**
Used when your theme loads fonts from fonts.googleapis.com after marketing consent.
Data sent: IP address and browser user-agent to Google font servers.
Terms: https://policies.google.com/terms
Privacy: https://policies.google.com/privacy

**Facebook Pixel (Meta Platforms, Inc.)**
Used when your site loads connect.facebook.net after marketing consent.
Data sent: browsing events as configured in your Pixel setup.
Terms: https://www.facebook.com/legal/terms
Privacy: https://www.facebook.com/privacy/policy

**YouTube / Vimeo / Google Maps embeds**
Used when marketing consent enables blocked iframes.
Data sent: per the embed provider when the iframe loads.
YouTube terms: https://www.youtube.com/t/terms — privacy: https://policies.google.com/privacy
Vimeo terms: https://vimeo.com/terms — privacy: https://vimeo.com/privacy

The built-in cookie scanner fetches your own site URLs via WordPress HTTP API to detect cookies; no off-site scanner service is used.

Script blocking patterns in `ScriptRegistry` match known third-party domains; the plugin does not fetch those URLs until consent is granted.

== Screenshots ==

1. The customizable cookie consent banner.
2. Cookie inventory management in the admin dashboard.
3. Consent logging for audit and compliance.
4. Comprehensive integration settings including Google Consent Mode v2.

== Changelog ==

= 1.2.0 =
* WordPress Multisite: network default settings and per-site inherit/override toggle.
* SettingsRepository resolves effective settings for frontend and consent flows.

= 1.1.3 =
* Prevent fatal error when legacy cookie plugins are still active during migration.
* Guard helper functions with function_exists for safe coexistence during upgrades.

= 1.1.2 =
* Fix banner not reappearing after revoke when reload on revoke is disabled.
* Add PHPUnit tests for scanner AJAX, consent log AJAX, admin sanitize, and uninstall.

= 1.1.1 =
* Fix CI PHPCS docblock on deprecated Banner method.
* Align admin menu title with plugin name.
* Update admin preview PHPUnit after custom CSS removal.
* Remove obsolete custom-category limit notice.
* Clear IP hash secret on uninstall when data is removed.

= 1.1.0 =
* Rename to Privaro Cookie Consent Banner (distinctive slug for WordPress.org).
* Remove custom CSS field and raw analytics injection; use primary color and ACF filter only.
* Remove artificial cap on custom consent categories.
* Use site-specific secret for optional consent log IP hashing (not AUTH_SALT).
* Move inline scripts/styles to wp_enqueue / wp_add_inline_script API.
* Fix namespace order fatal error on activation.

= 1.0.2 =
* Fix ConsentLogger.php namespace order missed in 1.0.1.

= 1.0.1 =
* Fix fatal error on activation (ABSPATH guard must come after namespace).
* Fix CookieConsent v3 callbacks (onConsent/onChange via cc.run config).
* Defer consent UUID and sync cookies until valid user choice.
* Harden Google resource blocking before consent.
* BREAKING: plugin folder/file/text domain renamed to privaro-cookie-consent-banner — see BREAKING_CHANGES.md in the GitHub repo.

= 1.0.0 =
* Initial release on WordPress.org.
* Features: CookieConsent v3 UI, script blocking, cookie scanner, WP Consent API, Google Consent Mode v2.
