=== EU Cookie Consent Suite ===
Contributors: evgenij347
Tags: cookies, gdpr, cookie-consent, privacy, eu
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GDPR and EU cookie consent banner with script blocking, cookie scanner, and Google Consent Mode v2.

== Description ==

EU Cookie Consent Suite is a comprehensive solution for managing cookie consent on your WordPress site, designed for compliance with GDPR, ePrivacy, and other privacy regulations. It features a modern, accessible banner powered by vanilla-cookieconsent v3, automatic script blocking, and a built-in cookie scanner.

Key features include:
* **Customizable Cookie Banner:** Fully responsive UI with light/dark modes and multiple layouts.
* **Consent Categories:** Necessary, Preferences, Statistics, Marketing, plus optional custom categories.
* **Automatic Script Blocking:** Blocks third-party scripts (GA, Pixel, etc.) and iframes (YouTube, Vimeo, Google Maps) until consent is granted.
* **Google Consent Mode v2:** Native support for GCM v2, including integration with Google Site Kit.
* **WP Consent API Support:** Fully compatible with the WordPress Consent API.
* **Cookie Scanner & Inventory:** Automatically discover cookies used on your site and maintain a categorized inventory.
* **Multilingual Support:** Ready for translation and supports Polylang/WPML.
* **Consent Logging:** Optional local logging of consent events for audit purposes.

= Privacy & Data Collection =
By default, the plugin does not collect or transmit any personal data to third-party servers. If the "Consent Logging" feature is enabled in settings, the plugin stores anonymized records of consent decisions in your local database. These records include a random UUID, the selected categories, the page URL, and (optional) an anonymized IP hash. This data is used solely for compliance accountability and is not shared with any third parties.

== Installation ==

1. Upload the `eu-cookie-consent-suite` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to 'EU Cookie Consent' in the WordPress admin to configure your banner and settings.
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

= Can I use my own CSS for the banner? =
Absolutely. The 'Banner' tab in settings includes a 'Custom CSS' field where you can override any styles.

== Screenshots ==

1. The customizable cookie consent banner.
2. Cookie inventory management in the admin dashboard.
3. Consent logging for audit and compliance.
4. Comprehensive integration settings including Google Consent Mode v2.

== Changelog ==

= 1.0.0 =
* Initial release on WordPress.org.
* Features: CookieConsent v3 UI, script blocking, cookie scanner, WP Consent API, Google Consent Mode v2.
