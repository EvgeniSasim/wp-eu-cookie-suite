<?php
/**
 * Shared helpers for consent cookie tests.
 *
 * @package WPEU\CookieSuite
 */

/**
 * Install current-revision consent cookies for optional categories.
 *
 * @param array<string, bool> $categories Category => allowed.
 * @param int                 $revision   Consent revision to store.
 */
function wpeu_cs_test_set_consent_cookies( array $categories, int $revision = 0 ): void {
	$settings                     = get_option( 'wpeu_cs_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	$settings['consent_revision'] = $revision;
	if ( ! array_key_exists( 'eu_mode', $settings ) ) {
		$settings['eu_mode'] = true;
	}
	update_option( 'wpeu_cs_settings', $settings );

	$_COOKIE['wpeu_cc']          = wp_json_encode( array( 'revision' => $revision ) );
	$_COOKIE['wpeu_consent_rev'] = (string) $revision;
	$_COOKIE['wpeu_consent']     = rawurlencode( wp_json_encode( $categories ) );

	foreach ( $categories as $slug => $allowed ) {
		$_COOKIE[ 'wpeu_' . sanitize_key( (string) $slug ) ] = $allowed ? '1' : '0';
	}
}
