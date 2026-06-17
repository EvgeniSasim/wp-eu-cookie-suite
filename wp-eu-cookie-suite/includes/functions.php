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

	$cookie_name = 'cc_cookie';
	if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
		return false;
	}

	$consent_data = json_decode( stripslashes( $_COOKIE[ $cookie_name ] ), true );
	if ( ! is_array( $consent_data ) || ! isset( $consent_data['categories'] ) || ! is_array( $consent_data['categories'] ) ) {
		return false;
	}

	return in_array( $category, $consent_data['categories'], true );
}
