<?php
/**
 * Helper functions.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wpeu_cs_parse_consent_cookie' ) ) {
	/**
	 * Parse and validate the wpeu_consent JSON cookie into category booleans.
	 *
	 * @return array<string, bool>
	 */
	function wpeu_cs_parse_consent_cookie(): array {
		if ( ! isset( $_COOKIE['wpeu_consent'] ) ) {
			return array();
		}

		$raw = wp_unslash( (string) $_COOKIE['wpeu_consent'] );
		$raw = wp_check_invalid_utf8( $raw, true );
		if ( '' === $raw || strlen( $raw ) > 2048 ) {
			return array();
		}

		$decoded_raw  = rawurldecode( $raw );
		$consent_data = json_decode( $decoded_raw, true );
		if ( ! is_array( $consent_data ) ) {
			$consent_data = json_decode( $raw, true );
		}

		if ( ! is_array( $consent_data ) ) {
			return array();
		}

		$allowed_categories = array_keys( \WPEU\CookieSuite\Consent\Categories::get_all() );
		$sanitized          = array();

		foreach ( $consent_data as $category => $value ) {
			$category = sanitize_key( (string) $category );
			if ( ! in_array( $category, $allowed_categories, true ) ) {
				continue;
			}

			$sanitized[ $category ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}

		return $sanitized;
	}
}

if ( ! function_exists( 'wpeu_cs_user_has_consent' ) ) {
	/**
	 * Check if the user has consented to a specific category.
	 *
	 * @param string $category Category ID.
	 * @return bool True if consented, false otherwise.
	 */
	function wpeu_cs_user_has_consent( string $category ): bool {
		if ( 'necessary' === $category ) {
			return true;
		}

		$cookie_name = 'wpeu_' . sanitize_key( $category );
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			$value = sanitize_text_field( wp_unslash( (string) $_COOKIE[ $cookie_name ] ) );
			return '1' === $value || 'allow' === $value;
		}

		$consent_data = wpeu_cs_parse_consent_cookie();
		if ( ! array_key_exists( $category, $consent_data ) ) {
			return false;
		}

		return (bool) $consent_data[ $category ];
	}
}

if ( ! function_exists( 'wpeu_cs_hash_ip' ) ) {
	/**
	 * Hash a visitor IP for optional consent logging (site-specific secret, not auth salts).
	 *
	 * @param string $ip IP address.
	 * @return string SHA-256 HMAC hex digest.
	 */
	function wpeu_cs_hash_ip( string $ip ): string {
		$secret = get_option( 'wpeu_cs_ip_hash_secret' );
		if ( ! is_string( $secret ) || '' === $secret ) {
			$secret = wp_generate_password( 64, true, true );
			update_option( 'wpeu_cs_ip_hash_secret', $secret, false );
		}

		return hash_hmac( 'sha256', $ip, $secret );
	}
}
