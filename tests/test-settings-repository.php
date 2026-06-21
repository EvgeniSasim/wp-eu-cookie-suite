<?php
/**
 * SettingsRepository tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Settings\SettingsRepository;

/**
 * Settings repository test case.
 */
class Test_Settings_Repository extends WP_UnitTestCase {

	/**
	 * Single-site returns local settings only.
	 */
	public function test_single_site_returns_local(): void {
		$local = array(
			'eu_mode' => true,
			'blocker_enabled' => false,
		);

		$result = SettingsRepository::resolve_effective_settings(
			false,
			$local,
			array( 'eu_mode' => false ),
			null
		);

		$this->assertSame( $local, $result );
	}

	/**
	 * Multisite inherit returns network settings when present.
	 */
	public function test_multisite_inherit_returns_network(): void {
		$local   = array( 'use_network_defaults' => true, 'eu_mode' => false );
		$network = array( 'eu_mode' => true, 'blocker_enabled' => true );

		$result = SettingsRepository::resolve_effective_settings(
			true,
			$local,
			$network,
			true
		);

		$this->assertSame( $network, $result );
	}

	/**
	 * Multisite override returns local settings.
	 */
	public function test_multisite_override_returns_local(): void {
		$local   = array( 'use_network_defaults' => false, 'eu_mode' => false );
		$network = array( 'eu_mode' => true );

		$result = SettingsRepository::resolve_effective_settings(
			true,
			$local,
			$network,
			false
		);

		$this->assertSame( $local, $result );
	}

	/**
	 * Missing network option falls back to local on inherit.
	 */
	public function test_missing_network_fallback_to_local(): void {
		$local = array( 'eu_mode' => true, 'blocker_enabled' => true );

		$result = SettingsRepository::resolve_effective_settings(
			true,
			$local,
			array(),
			true
		);

		$this->assertSame( $local, $result );
	}

	/**
	 * Default inherit flag on multisite when not set.
	 */
	public function test_multisite_default_inherit_uses_network(): void {
		$local   = array( 'eu_mode' => false );
		$network = array( 'eu_mode' => true );

		$result = SettingsRepository::resolve_effective_settings(
			true,
			$local,
			$network,
			null
		);

		$this->assertSame( $network, $result );
	}
}
