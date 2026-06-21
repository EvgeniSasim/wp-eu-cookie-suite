<?php
/**
 * Settings repository for resolving local vs network settings.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SettingsRepository class.
 */
final class SettingsRepository {

	/**
	 * Get local site settings.
	 *
	 * @return array
	 */
	public static function get_local_settings(): array {
		$settings = get_option( 'wpeu_cs_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Get network-wide default settings.
	 *
	 * @return array
	 */
	public static function get_network_settings(): array {
		if ( ! is_multisite() ) {
			return array();
		}
		$settings = get_site_option( 'wpeu_cs_network_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Check if the current site is using network defaults.
	 *
	 * @return bool
	 */
	public static function is_using_network_defaults(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		$local = self::get_local_settings();

		// Default to true on multisite if not explicitly set.
		return (bool) ( $local['use_network_defaults'] ?? true );
	}

	/**
	 * Get effective settings based on multisite status and site preference.
	 *
	 * @return array
	 */
	public static function get_effective_settings(): array {
		if ( ! is_multisite() ) {
			return get_option( 'wpeu_cs_settings', array() );
		}

		if ( self::is_using_network_defaults() ) {
			$network = self::get_network_settings();
			// Fallback to local if network settings are empty (not yet saved).
			if ( empty( $network ) ) {
				return self::get_local_settings();
			}
			return $network;
		}

		return self::get_local_settings();
	}
}
