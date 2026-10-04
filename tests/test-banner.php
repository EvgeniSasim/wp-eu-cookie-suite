<?php
/**
 * Banner config tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Frontend\Banner;

/**
 * Banner test case.
 */
class Test_Banner extends WP_UnitTestCase {

	/**
	 * Center admin slug maps to CookieConsent middle center.
	 */
	public function test_map_consent_modal_position_center(): void {
		$method = new ReflectionMethod( Banner::class, 'map_consent_modal_position' );
		$method->setAccessible( true );

		$this->assertSame( 'middle center', $method->invoke( null, 'center' ) );
		$this->assertSame( 'bottom center', $method->invoke( null, 'bottom-center' ) );
		$this->assertSame( 'bottom right', $method->invoke( null, 'invalid' ) );
	}

	/**
	 * CookieConsent must not manage scripts/cookies — plugin handles blocking itself.
	 */
	public function test_get_config_disables_cookieconsent_script_and_cookie_management(): void {
		$banner = new Banner();
		$method = new ReflectionMethod( Banner::class, 'get_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $banner );

		$this->assertFalse( $config['manageScriptTags'] );
		$this->assertFalse( $config['autoClearCookies'] );
		$this->assertSame( 'wpeu_cc', $config['cookie']['name'] );
		$this->assertSame( COOKIEPATH, $config['cookie']['path'] );
	}

	/**
	 * Frontend config must ship all configured locales and autoDetect document lang.
	 */
	public function test_get_config_ships_all_locales_with_document_autodetect(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'banner_texts'    => array(
					'ru' => array(
						'consent_modal_title' => 'Мы используем cookies',
						'accept_all_btn'      => 'Принять все',
					),
					'en' => array(
						'consent_modal_title' => 'We use cookies',
					),
				),
				'language_labels' => array(
					'ru' => 'Русский',
				),
			)
		);

		$banner = new Banner();
		$method = new ReflectionMethod( Banner::class, 'get_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $banner );

		$this->assertSame( 'document', $config['language']['autoDetect'] ?? null );
		$this->assertArrayHasKey( 'ru', $config['language']['translations'] );
		$this->assertArrayHasKey( 'en', $config['language']['translations'] );
		$this->assertSame(
			'Мы используем cookies',
			$config['language']['translations']['ru']['consentModal']['title']
		);
		$this->assertSame(
			'Принять все',
			$config['language']['translations']['ru']['consentModal']['acceptAllBtn']
		);
	}

	/**
	 * Admin Live Preview must not autoDetect document lang (forced by tab filter).
	 */
	public function test_get_config_preview_skips_autodetect(): void {
		if ( ! defined( 'WPEU_CS_PREVIEW' ) ) {
			define( 'WPEU_CS_PREVIEW', true );
		}

		$banner = new Banner();
		$method = new ReflectionMethod( Banner::class, 'get_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $banner );

		$this->assertArrayNotHasKey( 'autoDetect', $config['language'] );
		$this->assertSame( 'wpeu_cs_preview_cc', $config['cookie']['name'] );
	}
}
