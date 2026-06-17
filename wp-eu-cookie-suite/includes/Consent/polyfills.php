<?php
/**
 * Global polyfills for WP Consent API.
 *
 * @package WPEU\CookieSuite
 */

if ( ! function_exists( 'wp_has_consent' ) ) {
	/**
	 * Check if consent has been given for a category.
	 *
	 * @param string $category The category to check.
	 * @return bool
	 */
	function wp_has_consent( string $category ): bool {
		$mapping = array(
			'functional'  => 'necessary',
			'statistics'  => 'statistics',
			'marketing'   => 'marketing',
			'preferences' => 'preferences',
		);

		$wpeu_category = $mapping[ $category ] ?? $category;
		return wpeu_cs_user_has_consent( $wpeu_category );
	}
}

if ( ! function_exists( 'wp_set_consent' ) ) {
	/**
	 * Set consent for a category.
	 *
	 * @param string $category The category.
	 * @param string $status   The status ('allow' or 'deny').
	 */
	function wp_set_consent( string $category, string $status ): void {
		// This is primarily used in JavaScript.
	}
}

if ( ! function_exists( 'wp_get_consent_type' ) ) {
	/**
	 * Get the consent type (optin/optout).
	 *
	 * @return string 'optin' or 'optout'.
	 */
	function wp_get_consent_type(): string {
		$settings = get_option( 'wpeu_cs_settings', array() );
		$eu_mode  = $settings['eu_mode'] ?? true;
		return $eu_mode ? 'optin' : 'optout';
	}
}
