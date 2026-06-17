<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package WPEU\CookieSuite
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Mock WordPress functions and constants for unit tests if not running in full WP environment.
 */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/mock-abspath/' );
	define( 'WPEU_CS_VERSION', '0.1.0' );
	define( 'WPEU_CS_PATH', dirname( __DIR__ ) . '/' );
	define( 'WPEU_CS_URL', 'https://example.com/wp-content/plugins/wp-eu-cookie-suite/' );

	function add_action() {}
	function add_filter() {}
	function do_action() {}
	function apply_filters( $tag, $value ) { return $value; }
	function __ ( $text, $domain ) { return $text; }
	function esc_attr( $text ) { return $text; }
	function esc_html( $text ) { return $text; }
	function sanitize_key( $key ) { return $key; }
	function wp_unslash( $data ) { return $data; }

	function home_url( $path = '' ) { return 'https://example.com' . $path; }
	function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }

	if ( ! function_exists( 'get_option' ) ) {
		function get_option( $option, $default = false ) {
			global $mock_options;
			return $mock_options[ $option ] ?? $default;
		}
	}

	if ( ! function_exists( 'update_option' ) ) {
		function update_option( $option, $value ) {
			global $mock_options;
			$mock_options[ $option ] = $value;
			return true;
		}
	}
}

// Load polyfills
require_once dirname( __DIR__ ) . '/includes/Consent/polyfills.php';
require_once dirname( __DIR__ ) . '/includes/functions.php';
