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

		if ( ! isset( $_COOKIE['wpeu_consent'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON consent payload is decoded immediately.
		$raw          = wp_unslash( $_COOKIE['wpeu_consent'] );
		$consent_data = json_decode( rawurldecode( $raw ), true );
		if ( ! is_array( $consent_data ) ) {
			$consent_data = json_decode( $raw, true );
		}

		if ( ! is_array( $consent_data ) || ! array_key_exists( $category, $consent_data ) ) {
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
