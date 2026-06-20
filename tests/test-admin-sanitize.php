<?php
/**
 * Admin settings sanitization tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Admin\Admin;

/**
 * Admin sanitization test case.
 */
class Test_Admin_Sanitize extends WP_UnitTestCase {

	/**
	 * Admin instance.
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->admin = new Admin();
	}

	/**
	 * Test sanitize_settings for banner tab.
	 */
	public function test_sanitize_banner_tab(): void {
		$input = array(
			'active_tab'         => 'banner',
			'enabled_categories' => array( 'statistics', 'invalid_cat' ),
			'privacy_policy_url' => 'https://example.com/privacy',
			'show_reject_all'    => '1',
			'eu_mode'            => '1',
			'banner_ui'          => array(
				'layout'        => 'bar',
				'position'      => 'top-center',
				'theme'         => 'dark',
				'primary_color' => '#zz0000', // Invalid hex.
			),
		);

		$sanitized = $this->admin->sanitize_settings( $input );

		$this->assertContains( 'statistics', $sanitized['enabled_categories'] );
		$this->assertNotContains( 'invalid_cat', $sanitized['enabled_categories'] );
		$this->assertSame( 'https://example.com/privacy', $sanitized['privacy_policy_url'] );
		$this->assertTrue( $sanitized['show_reject_all'] );
		$this->assertTrue( $sanitized['eu_mode'] );
		$this->assertSame( 'bar', $sanitized['banner_ui']['layout'] );
		$this->assertSame( 'dark', $sanitized['banner_ui']['theme'] );
		$this->assertSame( '#30363c', $sanitized['banner_ui']['primary_color'] ); // Fallback.
	}

	/**
	 * Test sanitize_settings for integrations tab.
	 */
	public function test_sanitize_integrations_tab(): void {
		$input = array(
			'active_tab'            => 'integrations',
			'blocker_enabled'       => '1',
			'google_consent_mode'   => '1',
			'enabled_services'      => array(
				'youtube' => '1',
				'vimeo'   => '0',
			),
			'enabled_integrations'  => array( 'google_site_kit' => '1' ),
			'theme_analytics_field' => 'custom_analytics',
			'custom_block_rules'    => "rule1\nrule2",
		);

		$sanitized = $this->admin->sanitize_settings( $input );

		$this->assertTrue( $sanitized['blocker_enabled'] );
		$this->assertTrue( $sanitized['google_consent_mode'] );
		$this->assertTrue( $sanitized['enabled_services']['youtube'] );
		$this->assertFalse( $sanitized['enabled_services']['vimeo'] );
		$this->assertTrue( $sanitized['enabled_integrations']['google_site_kit'] );
		$this->assertSame( 'custom_analytics', $sanitized['theme_analytics_field'] );
		$this->assertSame( "rule1\nrule2", $sanitized['custom_block_rules'] );
	}

	/**
	 * Test sanitize_settings for tools tab.
	 */
	public function test_sanitize_tools_tab(): void {
		$input = array(
			'active_tab'              => 'tools',
			'consent_logging_enabled' => '1',
			'consent_log_retention'   => '180',
			'consent_log_store_ip'    => '1',
			'policy_texts'            => array(
				'en' => array(
					'intro'    => 'Intro text',
					'template' => '<p>Template {{table}}</p><script>alert(1)</script>',
				),
			),
		);

		$sanitized = $this->admin->sanitize_settings( $input );

		$this->assertTrue( $sanitized['consent_logging_enabled'] );
		$this->assertSame( 180, $sanitized['consent_log_retention'] );
		$this->assertTrue( $sanitized['consent_log_store_ip'] );
		$this->assertSame( 'Intro text', $sanitized['policy_texts']['en']['intro'] );
		$this->assertStringContainsString( '<p>Template {{table}}</p>', $sanitized['policy_texts']['en']['template'] );
		$this->assertStringNotContainsString( '<script>', $sanitized['policy_texts']['en']['template'] );
	}
}
