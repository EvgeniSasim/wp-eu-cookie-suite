<?php
/**
 * Global polyfills for WP Consent API.
 *
 * @package WPEU\CookieSuite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEU\CookieSuite\Consent\Categories;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- WP Consent API compatibility functions.

if ( ! function_exists( 'wp_has_consent' ) ) {
	/**
	 * Check if consent has been given for a category.
	 *
	 * @param string $category The category to check.
	 * @return bool
	 */
	function wp_has_consent( string $category ): bool {
		$wp_categories = array( 'functional', 'statistics', 'marketing', 'preferences' );
		if ( ! in_array( $category, $wp_categories, true ) ) {
			return wpeu_cs_user_has_consent( $category );
		}

		if ( 'functional' === $category ) {
			return wpeu_cs_user_has_consent( Categories::NECESSARY );
		}

		if ( wpeu_cs_user_has_consent( $category ) ) {
			return true;
		}

		foreach ( Categories::get_custom() as $slug => $data ) {
			$map = $data['integration_map'] ?? Categories::MARKETING;
			if ( $map === $category && wpeu_cs_user_has_consent( $slug ) ) {
				return true;
			}
		}

		return false;
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
		$settings = \WPEU\CookieSuite\Settings\SettingsRepository::get_effective_settings();
		$eu_mode  = $settings['eu_mode'] ?? true;
		return $eu_mode ? 'optin' : 'optout';
	}
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
