<?php
/**
 * Script blocker tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Frontend\ScriptBlocker;

/**
 * Script blocker test case.
 */
class Test_Script_Blocker extends WP_UnitTestCase {

	/**
	 * Unknown third-party scripts stay allowed when the opt-in setting is off.
	 */
	public function test_unknown_third_party_not_blocked_by_default(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'blocker_enabled'           => true,
				'block_unknown_third_party' => false,
				'enabled_services'          => array(),
			)
		);

		$blocker = new ScriptBlocker();
		$html    = '<script src="https://cdn.unknown-tracker.test/pixel.js"></script>';
		$out     = $blocker->process_output( $html );

		$this->assertStringContainsString( 'cdn.unknown-tracker.test/pixel.js', $out );
		$this->assertStringNotContainsString( 'type="text/plain"', $out );
	}

	/**
	 * Unknown cross-origin scripts are blocked as marketing when enabled.
	 */
	public function test_unknown_third_party_blocked_when_enabled(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'blocker_enabled'           => true,
				'block_unknown_third_party' => true,
				'enabled_services'          => array(),
			)
		);

		$blocker = new ScriptBlocker();
		$html    = '<script src="https://cdn.unknown-tracker.test/pixel.js"></script>';
		$out     = $blocker->process_output( $html );

		$this->assertStringContainsString( 'type="text/plain"', $out );
		$this->assertStringContainsString( 'data-category="marketing"', $out );
	}

	/**
	 * Same-origin scripts are never auto-blocked as unknown third-party.
	 */
	public function test_same_origin_not_blocked_as_unknown(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'blocker_enabled'           => true,
				'block_unknown_third_party' => true,
				'enabled_services'          => array(),
			)
		);

		$home    = home_url( '/wp-content/themes/test/app.js' );
		$blocker = new ScriptBlocker();
		$html    = '<script src="' . esc_url( $home ) . '"></script>';
		$out     = $blocker->process_output( $html );

		$this->assertStringNotContainsString( 'type="text/plain"', $out );
	}
}
