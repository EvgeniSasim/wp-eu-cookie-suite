=== Privaro Cookie Consent Banner ===
Contributors: evgenij347
Tags: cookies, gdpr, cookie-consent, privacy, eu
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.3.3
Network: Yes
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional GDPR cookie consent for WordPress: opt-in banner, script blocking, scanner, consent log, and Google Consent Mode v2.

== Description ==

**Privaro Cookie Consent Banner** helps you run a privacy-conscious WordPress site in the EU and beyond. Visitors get a clear, accessible cookie banner (powered by CookieConsent v3). Third-party scripts and embeds stay blocked until consent is granted — no “accept-only” dark patterns.

= Why site owners choose Privaro =

* **Opt-in by default** — Statistics and marketing are off until the visitor accepts. *Reject all* is always available.
* **Real script blocking** — GA4, GTM, Meta Pixel, Hotjar, Microsoft Clarity, and custom rules via output buffering.
* **Embed placeholders** — YouTube, Vimeo, and Google Maps load only after marketing consent.
* **Cookie scanner** — Discover cookies on your own site (no external SaaS scanner).
* **Policy helpers** — Shortcodes for cookie policy, declaration table, and “Cookie settings” footer link.
* **Google Consent Mode v2** — Default `denied`, updates on banner choice; works with Google Site Kit.
* **WP Consent API** — Compatible bridge (includes polyfill when the API plugin is not installed).
* **Multisite** — Network defaults with per-site inherit or override.
* **Consent log (optional)** — Local audit trail with proof snapshots; data never leaves your server.

= Key features =

* **Customizable banner** — Light/dark themes, bar or box layout, primary color, live admin preview.
* **Consent categories** — Necessary, Preferences, Statistics, Marketing, plus custom categories.
* **Cookie inventory** — Manual edits, scanner import, CSV export.
* **Integrations** — Google Site Kit, Contact Form 7 (reCAPTCHA deferred), iframe placeholders.
* **Tools** — JSON export/import of settings between sites.
* **Languages** — Editable EN/DE strings; Polylang/WPML-friendly.

= Quick setup =

1. Activate the plugin and open **Privaro Cookie Consent Banner** in wp-admin.
2. Configure the **Banner** tab (texts, colors, policy URLs).
3. Enable **Integrations** → Google Consent Mode v2 and Script blocker if you use analytics/ads.
4. Run the **Scanner** and save cookies to your inventory.
5. Add `[wpeu_cookie_policy]` / `[wpeu_cookie_declaration]` to your legal pages and `[wpeu_manage_consent]` to the footer.
6. Test in a private browser window — analytics tags should stay blocked until consent.

**Legal note:** This plugin provides technical compliance tools, not legal advice. You are responsible for policy texts and regulatory compliance.

= Privacy & Data Collection =
By default, the plugin does not collect or transmit any personal data to third-party servers. If the "Consent Logging" feature is enabled in settings, the plugin stores anonymized records of consent decisions in your local database. These records include a random UUID, the selected categories, the page URL, and (optional) an anonymized IP hash. This data is used solely for compliance accountability and is not shared with any third parties.

== Installation ==

= From WordPress.org (recommended) =

1. Go to **Plugins → Add New** in your WordPress admin.
2. Search for **Privaro Cookie Consent Banner**.
3. Click **Install Now**, then **Activate**.

= Manual upload =

1. Upload the `privaro-cookie-consent-banner` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **Privaro Cookie Consent Banner** in the admin sidebar.

= After activation =

1. Configure the **Banner** tab (appearance, texts, privacy/cookie policy URLs).
2. Enable **Integrations** (Consent Mode v2, script blocker) if you use Google Analytics, GTM, or ads.
3. Run the **Scanner** and review cookies in the **Cookies** tab.
4. Add shortcodes to your site: `[wpeu_cookie_policy]`, `[wpeu_cookie_declaration]`, `[wpeu_manage_consent]`.

= Multisite =

Network-activate the plugin, then set defaults under **Network Admin → Privaro Cookie Consent Banner**. Each site may inherit or override network settings.

== Credits ==

This plugin bundles vanilla-cookieconsent v3 (MIT). See the plugin source headers and bundled assets for license details.

== FAQ ==

= Is this plugin free and open source? =
Yes. Privaro Cookie Consent Banner is GPL-licensed. Core compliance features (banner, blocking, scanner, Consent Mode) are included without a premium tier.

= Does it use opt-in consent for EU visitors? =
Yes. Statistics and marketing categories require explicit consent. Reject all must remain available alongside accept options.

= How do I enable Google Consent Mode v2? =
Open **Integrations** in the plugin settings and enable **Google Consent Mode v2**. The plugin sets default consent to denied and updates Google tags after the visitor chooses in the banner.

= Does this plugin block YouTube videos? =
Yes. An iframe processor replaces YouTube, Vimeo, and Google Maps embeds with a placeholder until the visitor grants **Marketing** consent.

= What scripts are blocked before consent? =
Known patterns include Google Analytics (GA4), Google Tag Manager, Meta Pixel, Hotjar, Microsoft Clarity, and custom URLs you add under Integrations. Blocking uses output buffering on your site's HTML.

= Where is the consent log stored? =
Entirely in your WordPress database (`wp_wpeu_consent_log`). No data is sent to external servers. Retention is configurable in settings.

= Can I customize banner colors and layout? =
Yes. Use the Primary Color picker, layout (bar/box), and light/dark theme on the **Banner** tab. A live preview is available in the admin.

= How do visitors reopen cookie settings later? =
Add the `[wpeu_manage_consent]` shortcode to your footer or template. Optional attributes: `style="button"` and `label="Cookie settings"`.

= Does it work with Google Site Kit? =
Yes. The plugin integrates with Site Kit so analytics tags respect consent state.

= Multilingual sites? =
Edit banner strings per language in settings. The plugin supports Polylang/WPML workflows via standard WordPress translation hooks.

= Multisite support? =
Yes. Network-wide defaults with per-site inherit or override. Scanner, cookie list, and consent log remain editable on subsites when inheriting.

= Does the scanner send data to a third party? =
No. The scanner fetches your own site URLs via the WordPress HTTP API and parses responses locally.

= Can I migrate from Complianz? =
There is no automatic importer. Map categories manually, copy custom block rules, replace shortcodes, run the scanner, and test on staging before deactivating Complianz. See the GitHub README migration checklist.

= What are the system requirements? =
PHP 8.1 or newer and WordPress 6.4 or newer.

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

1. Cookie consent banner with live admin preview, primary color, and reject-all support.
2. Cookie inventory — scanner results, categories, and blocking status.
3. Consent log — local audit trail with proof snapshots.
4. Integrations — Google Consent Mode v2, script blocker, Site Kit, and iframe placeholders.

== Changelog ==

= 1.3.3 =
* WordPress.org review fixes: safer output buffering, sanitized preview settings, validated consent JSON cookie.

= 1.3.2 =
* WordPress.org review fixes: local-only blocking patterns, no silent plugin deactivation, escaped shortcode/iframe output, explicit output buffer close.

= 1.3.1 =
* Fix multisite inherit toggle so subsites can disable network defaults.
* Limit read-only inherited UI to Banner and Integrations; keep Scanner, Cookies, and Consent Log editable.
* Network Admin overview tab, network language/category management, and per-site consent logging while inheriting.
* Fix missing consent revision bump handler on Tools tab.

= 1.3.0 =
* Consent log snapshots v2: banner texts, policy URLs/intro, category labels, UI settings, and SHA-256 content_hash for accountability.
* Snapshot download JSON includes log record plus proof_snapshot bundle.

= 1.2.3 =
* Fix Consent Log list table not rendering rows reliably (explicit WP_List_Table columns and admin screen binding).
* Keep Consent Log tab interactive when site inherits multisite network defaults.
* Horizontal scroll for wide consent log table in admin.

= 1.2.2 =
* Restore full plugin name in Network Admin menu (Privaro Cookie Consent Banner).
* JSON export uses effective settings (respects multisite network inherit).
* Accept legacy export files from wp-eu-cookie-suite / eu-cookie-consent-suite slugs.
* Remove network default settings on uninstall from the main site.
* Documentation and dev docs aligned with Privaro Cookie Consent Banner branding.

= 1.2.1 =
* Fix fatal error on activation when a stale `vendor/autoload.php` exists from a previous install; always register the includes autoloader.

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
