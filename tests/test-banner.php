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
}
