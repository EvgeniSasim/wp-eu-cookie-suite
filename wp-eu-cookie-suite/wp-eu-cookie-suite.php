<?php
/**
 * Plugin Name:       WP EU Cookie Suite
 * Plugin URI:        https://github.com/EvgeniSasim/wp-eu-cookie-suite
 * Description:       EU/GDPR cookie consent with CookieConsent UI, script blocking, scanner, and WP Consent API.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            BSB / North IT Group
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-eu-cookie-suite
 *
 * @package WPEU_CookieSuite
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPEU_CS_VERSION', '0.1.0' );
define( 'WPEU_CS_FILE', __FILE__ );
define( 'WPEU_CS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPEU_CS_URL', plugin_dir_url( __FILE__ ) );

require_once WPEU_CS_PATH . 'includes/Plugin.php';

\WPEU\CookieSuite\Plugin::instance()->boot();
