<?php
/**
 * WP Consent API bridge tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Consent\WpConsentBridge;

/**
 * WP Consent bridge test case.
 */
class Test_Wp_Consent_Bridge extends WP_UnitTestCase {

	/**
	 * Bridge instance.
	 *
	 * @var WpConsentBridge
	 */
	private WpConsentBridge $bridge;

	/**
	 * Set up test case.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->bridge = new WpConsentBridge();
	}

	/**
	 * Polyfills are registered when wp-consent-api is absent.
	 */
	public function test_polyfills_are_registered(): void {
		$this->assertTrue( function_exists( 'wp_has_consent' ) );
		$this->assertTrue( function_exists( 'wp_set_consent' ) );
		$this->assertTrue( function_exists( 'wp_get_consent_type' ) );
	}

	/**
	 * wp_has_consent maps statistics category.
	 */
	public function test_wp_has_consent_statistics(): void {
		$_COOKIE['wpeu_statistics'] = 'allow';
		$this->assertTrue( wp_has_consent( 'statistics' ) );
		unset( $_COOKIE['wpeu_statistics'] );
	}

	/**
	 * wp_has_consent maps functional to necessary.
	 */
	public function test_wp_has_consent_functional_maps_to_necessary(): void {
		$this->assertTrue( wp_has_consent( 'functional' ) );
	}

	/**
	 * Default consent type is optin when EU mode is on.
	 */
	public function test_wp_get_consent_type_optin(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'eu_mode' => true,
			)
		);

		$this->assertSame( 'optin', wp_get_consent_type() );
	}

	/**
	 * Consent type is optout when EU mode is off.
	 */
	public function test_wp_get_consent_type_optout(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'eu_mode' => false,
			)
		);

		$this->assertSame( 'optout', wp_get_consent_type() );
	}
}
