<?php
/**
 * Plugin Name:       Privaro Cookie Consent Banner
 * Plugin URI:        https://profiles.wordpress.org/evgenij347/
 * Description:       EU/GDPR cookie consent with CookieConsent UI, script blocking, scanner, and WP Consent API.
 * Version:           1.1.2
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

// Load autoloader (Composer preferred; lightweight fallback for production installs).
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	require_once __DIR__ . '/includes/autoload.php';
}

// Load helper functions.
require_once __DIR__ . '/includes/functions.php';

/**
 * Initialize the plugin.
 */
\WPEU\CookieSuite\Plugin::instance()->boot();
