<?php
/**
 * Consent UX tests (CC-19).
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Frontend\Shortcodes;

/**
 * Consent UX test case.
 */
class Test_Consent_Ux extends WP_UnitTestCase {

	/**
	 * Manage consent shortcode renders a link by default.
	 */
	public function test_manage_consent_shortcode_link(): void {
		$shortcodes = new Shortcodes();
		$html       = $shortcodes->render_manage_consent( array() );

		$this->assertStringContainsString( 'wpeu-manage-consent--link', $html );
		$this->assertStringContainsString( 'showPreferences', $html );
	}

	/**
	 * Manage consent shortcode supports button style.
	 */
	public function test_manage_consent_shortcode_button(): void {
		$shortcodes = new Shortcodes();
		$html       = $shortcodes->render_manage_consent(
			array(
				'style' => 'button',
				'label' => 'Test settings',
			)
		);

		$this->assertStringContainsString( 'wpeu-manage-consent--button', $html );
		$this->assertStringContainsString( 'Test settings', $html );
	}

	/**
	 * Bumping revision increments the version and redirects.
	 */
	public function test_handle_bump_consent_revision(): void {
		update_option( 'wpeu_cs_settings', array( 'consent_revision' => 5 ) );

		// We need to mock current_user_can and check_admin_referer or call the private method directly
		$admin = new \WPEU\CookieSuite\Admin\Admin();
		$method = new ReflectionMethod( $admin, 'handle_bump_consent_revision' );
		$method->setAccessible( true );

		try {
			$method->invoke( $admin );
		} catch ( Exception $e ) {
			// Redirect will throw an exception in test environment usually, or we just check the option
		}

		$settings = get_option( 'wpeu_cs_settings' );
		$this->assertEquals( 6, $settings['consent_revision'] );
	}
}
