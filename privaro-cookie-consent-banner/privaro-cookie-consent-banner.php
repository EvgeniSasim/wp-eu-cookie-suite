<?php
/**
 * Plugin Name:       Privaro Cookie Consent Banner
 * Plugin URI:        https://profiles.wordpress.org/evgenij347/
 * Description:       EU/GDPR cookie consent with CookieConsent UI, script blocking, scanner, and WP Consent API.
 * Version:           1.3.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Evgenii Sasim
 * Author URI:        https://www.instagram.com/evgenii.sasim/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       privaro-cookie-consent-banner
 *
 * @package WPEU_CookieSuite
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer autoload (optional dev); always register includes fallback for production zips.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}
require_once __DIR__ . '/includes/autoload.php';

// Load helper functions.
require_once __DIR__ . '/includes/functions.php';

if ( class_exists( 'WPEU\CookieSuite\Plugin', false ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p>';
			esc_html_e(
				'Privaro Cookie Consent Banner was not loaded because a legacy cookie consent plugin is already active. Deactivate wp-eu-cookie-suite or eu-cookie-consent-suite first, then activate Privaro Cookie Consent Banner.',
				'privaro-cookie-consent-banner'
			);
			echo '</p></div>';
		}
	);
	return;
}

/**
 * Initialize the plugin.
 */
\WPEU\CookieSuite\Plugin::instance()->boot();
