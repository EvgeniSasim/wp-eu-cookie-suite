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
}
