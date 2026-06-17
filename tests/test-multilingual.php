<?php
/**
 * Tests for dynamic multilingual support.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Consent\BannerTexts;

/**
 * Multilingual test case.
 */
class Test_Multilingual extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		update_option( 'wpeu_cs_settings', array() );
	}

	public function test_get_locales_defaults(): void {
		$locales = BannerTexts::get_locales();
		$this->assertArrayHasKey( 'en', $locales );
		$this->assertArrayHasKey( 'de', $locales );

		$site_locale = substr( get_locale(), 0, 2 );
		$this->assertArrayHasKey( $site_locale, $locales );
	}

	public function test_get_locales_from_settings(): void {
		update_option( 'wpeu_cs_settings', array(
			'banner_texts' => array(
				'fr' => array( 'consent_modal_title' => 'Cookies' ),
			),
			'language_labels' => array(
				'fr' => 'Français',
			),
		) );

		$locales = BannerTexts::get_locales();
		$this->assertArrayHasKey( 'fr', $locales );
		$this->assertEquals( 'Français', $locales['fr'] );
	}

	public function test_get_locales_filter(): void {
		add_filter( 'wpeu_cs_locales', function( $locales ) {
			$locales['it'] = 'Italiano';
			return $locales;
		} );

		$locales = BannerTexts::get_locales();
		$this->assertArrayHasKey( 'it', $locales );
		$this->assertEquals( 'Italiano', $locales['it'] );
	}

	public function test_get_strings_fallback(): void {
		// No FR settings saved
		$strings = BannerTexts::get_strings( 'fr' );
		$en_defaults = BannerTexts::get_defaults( 'en' );

		$this->assertEquals( $en_defaults['consent_modal_title'], $strings['consent_modal_title'] );
	}

	public function test_get_default_policy_template_fallback(): void {
		$template_fr = BannerTexts::get_default_policy_template( 'fr' );
		$template_en = BannerTexts::get_default_policy_template( 'en' );
		$template_de = BannerTexts::get_default_policy_template( 'de' );

		$this->assertEquals( $template_en, $template_fr );
		$this->assertNotEquals( $template_de, $template_fr );
	}
}
