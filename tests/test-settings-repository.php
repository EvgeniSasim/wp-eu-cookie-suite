<?php
/**
 * Tests for SettingsRepository class.
 *
 * @package WPEU\CookieSuite
 */

namespace WPEU\CookieSuite\Tests;

use WPEU\CookieSuite\Settings\SettingsRepository;
use WP_UnitTestCase;

class Test_Settings_Repository extends WP_UnitTestCase {

	/**
	 * Setup for tests.
	 */
	public function set_up(): void {
		parent::set_up();
		delete_option( 'wpeu_cs_settings' );
		delete_site_option( 'wpeu_cs_network_settings' );
	}

	/**
	 * Test single-site local settings.
	 */
	public function test_single_site_local_settings(): void {
		$local_data = array( 'test_key' => 'local_value' );
		update_option( 'wpeu_cs_settings', $local_data );

		$this->assertEquals( $local_data, SettingsRepository::get_local_settings() );
		$this->assertEquals( $local_data, SettingsRepository::get_effective_settings() );
		$this->assertFalse( SettingsRepository::is_using_network_defaults() );
	}

	/**
	 * Test multisite inherit from network.
	 */
	public function test_multisite_inherit_network(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite is not enabled.' );
		}

		$network_data = array( 'test_key' => 'network_value' );
		update_site_option( 'wpeu_cs_network_settings', $network_data );

		// On multisite, default is to use network if not explicitly set in local.
		$this->assertTrue( SettingsRepository::is_using_network_defaults() );
		$this->assertEquals( $network_data, SettingsRepository::get_effective_settings() );
	}

	/**
	 * Test multisite override network.
	 */
	public function test_multisite_override_local(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite is not enabled.' );
		}

		$network_data = array( 'test_key' => 'network_value' );
		update_site_option( 'wpeu_cs_network_settings', $network_data );

		$local_data = array(
			'test_key'             => 'local_value',
			'use_network_defaults' => false,
		);
		update_option( 'wpeu_cs_settings', $local_data );

		$this->assertFalse( SettingsRepository::is_using_network_defaults() );
		$this->assertEquals( $local_data, SettingsRepository::get_effective_settings() );
	}

	/**
	 * Test multisite missing network fallback.
	 */
	public function test_multisite_missing_network_fallback(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite is not enabled.' );
		}

		$local_data = array( 'test_key' => 'local_value' );
		update_option( 'wpeu_cs_settings', $local_data );

		// Network is empty, so should fallback to local even if inherit is true.
		$this->assertTrue( SettingsRepository::is_using_network_defaults() );
		$this->assertEquals( $local_data, SettingsRepository::get_effective_settings() );
	}

	/**
	 * Test multisite use_network_defaults explicit true.
	 */
	public function test_multisite_explicit_true(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite is not enabled.' );
		}

		$network_data = array( 'test_key' => 'network_value' );
		update_site_option( 'wpeu_cs_network_settings', $network_data );

		$local_data = array(
			'test_key'             => 'local_value',
			'use_network_defaults' => true,
		);
		update_option( 'wpeu_cs_settings', $local_data );

		$this->assertTrue( SettingsRepository::is_using_network_defaults() );
		$this->assertEquals( $network_data, SettingsRepository::get_effective_settings() );
	}
}
