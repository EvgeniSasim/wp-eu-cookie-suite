<?php
/**
 * Settings repository.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles settings retrieval with multisite support.
 */
final class SettingsRepository {

	/**
	 * Get local settings.
	 *
	 * @return array
	 */
	public static function get_local_settings(): array {
		return (array) get_option( 'wpeu_cs_settings', array() );
	}

	/**
	 * Get network-wide settings.
	 *
	 * @return array
	 */
	public static function get_network_settings(): array {
		if ( ! is_multisite() ) {
			return array();
		}
		return (array) get_site_option( 'wpeu_cs_network_settings', array() );
	}

	/**
	 * Check if the site is using network defaults.
	 *
	 * @return bool
	 */
	public static function is_using_network_defaults(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		$local = self::get_local_settings();

		// If 'use_network_defaults' is not set, default to true on multisite.
		return (bool) ( $local['use_network_defaults'] ?? true );
	}

	/**
	 * Get effective settings based on environment and local override.
	 *
	 * @return array
	 */
	public static function get_effective_settings(): array {
		if ( ! is_multisite() ) {
			return self::get_local_settings();
		}

		if ( self::is_using_network_defaults() ) {
			$network = self::get_network_settings();
			if ( ! empty( $network ) ) {
				return $network;
			}
		}

		return self::get_local_settings();
	}
}
