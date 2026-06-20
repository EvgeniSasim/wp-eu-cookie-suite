<?php
/**
 * Settings transfer tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Admin\SettingsTransfer;

/**
 * Settings transfer test case.
 */
class Test_Settings_Transfer extends WP_UnitTestCase {

	/**
	 * Export contains required keys.
	 */
	public function test_export_structure(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'eu_mode' => true,
			)
		);

		$payload = SettingsTransfer::export();

		$this->assertSame( 'privaro-cookie-consent-banner', $payload['plugin'] );
		$this->assertArrayHasKey( 'settings', $payload );
		$this->assertArrayHasKey( 'registry', $payload );
		$this->assertTrue( $payload['settings']['eu_mode'] );
	}

	/**
	 * Invalid plugin name is rejected.
	 */
	public function test_validate_rejects_foreign_export(): void {
		$result = SettingsTransfer::validate(
			array(
				'plugin'   => 'other-plugin',
				'settings' => array(),
				'registry' => array(),
			)
		);

		$this->assertWPError( $result );
	}

	/**
	 * Import sanitizes URLs and booleans.
	 */
	public function test_sanitize_imported_settings(): void {
		$clean = SettingsTransfer::sanitize_imported_settings(
			array(
				'eu_mode'              => 1,
				'privacy_policy_url'   => 'https://example.com/privacy',
				'enabled_categories'   => array( 'statistics' ),
				'custom_block_rules'   => "-url-tracking.example\n",
				'banner_texts'         => array(
					'en' => array(
						'consent_modal_title' => 'Cookies',
					),
				),
			)
		);

		$this->assertTrue( $clean['eu_mode'] );
		$this->assertSame( 'https://example.com/privacy', $clean['privacy_policy_url'] );
		$this->assertSame( array( 'statistics' ), $clean['enabled_categories'] );
		$this->assertSame( 'Cookies', $clean['banner_texts']['en']['consent_modal_title'] );
	}
}
