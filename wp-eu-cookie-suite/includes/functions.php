<?php
/**
 * Helper functions.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

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
		return '1' === $_COOKIE[ $cookie_name ] || 'allow' === $_COOKIE[ $cookie_name ];
	}

	if ( ! isset( $_COOKIE['wpeu_consent'] ) ) {
		return false;
	}

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
