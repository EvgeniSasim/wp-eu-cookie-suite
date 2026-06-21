<?php
/**
 * Tests for WPEU\CookieSuite\Settings\SettingsRepository.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Settings\SettingsRepository;

/**
 * SettingsRepository test class.
 */
class Test_SettingsRepository extends WP_UnitTestCase {

	/**
	 * Test single-site behavior.
	 */
	public function test_single_site_returns_local(): void {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'This test is for single-site only.' );
		}

		$settings = array( 'foo' => 'bar' );
		update_option( 'wpeu_cs_settings', $settings );

		$this->assertEquals( $settings, SettingsRepository::get_effective_settings() );
		$this->assertFalse( SettingsRepository::is_using_network_defaults() );
	}

	/**
	 * Test multisite inherit behavior.
	 */
	public function test_multisite_inherit_returns_network(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test is for multisite only.' );
		}

		$local_settings   = array( 'use_network_defaults' => true, 'local' => 'value' );
		$network_settings = array( 'network' => 'value' );

		update_option( 'wpeu_cs_settings', $local_settings );
		update_site_option( 'wpeu_cs_network_settings', $network_settings );

		$this->assertTrue( SettingsRepository::is_using_network_defaults() );
		$this->assertEquals( $network_settings, SettingsRepository::get_effective_settings() );
	}

	/**
	 * Test multisite override behavior.
	 */
	public function test_multisite_override_returns_local(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test is for multisite only.' );
		}

		$local_settings   = array( 'use_network_defaults' => false, 'local' => 'value' );
		$network_settings = array( 'network' => 'value' );

		update_option( 'wpeu_cs_settings', $local_settings );
		update_site_option( 'wpeu_cs_network_settings', $network_settings );

		$this->assertFalse( SettingsRepository::is_using_network_defaults() );
		$this->assertEquals( $local_settings, SettingsRepository::get_effective_settings() );
	}

	/**
	 * Test fallback when network settings are missing.
	 */
	public function test_multisite_fallback_to_local_when_network_missing(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test is for multisite only.' );
		}

		$local_settings = array( 'use_network_defaults' => true, 'local' => 'value' );
		update_option( 'wpeu_cs_settings', $local_settings );
		delete_site_option( 'wpeu_cs_network_settings' );

		$this->assertEquals( $local_settings, SettingsRepository::get_effective_settings() );
	}
}
