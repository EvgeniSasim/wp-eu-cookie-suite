<?php
/**
 * Consent snapshot tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Consent\ConsentLogger;

/**
 * Consent snapshot test case.
 */
class Test_Consent_Snapshot extends WP_UnitTestCase {

	/**
	 * Snapshot v2 stores banner texts, policy URLs, categories, and content hash.
	 */
	public function test_build_consent_snapshot_v2(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'consent_revision'     => 3,
				'eu_mode'              => true,
				'show_reject_all'      => true,
				'google_consent_mode'  => true,
				'privacy_policy_url'   => 'https://example.com/privacy',
				'cookie_policy_url'    => 'https://example.com/cookies',
				'enabled_categories'   => array( 'statistics', 'marketing' ),
				'banner_ui'            => array(
					'layout'        => 'box',
					'position'      => 'bottom-right',
					'theme'         => 'light',
					'primary_color' => '#112233',
				),
				'banner_texts'         => array(
					'en' => array(
						'consent_modal_title' => 'Custom title',
					),
				),
				'policy_texts'         => array(
					'en' => array(
						'intro'    => 'Policy intro text',
						'template' => '<h2>Cookie Policy</h2>',
					),
				),
			)
		);

		$snapshot = ConsentLogger::build_consent_snapshot( get_option( 'wpeu_cs_settings' ), 'en' );

		$this->assertSame( ConsentLogger::SNAPSHOT_VERSION, $snapshot['snapshot_version'] );
		$this->assertSame( 3, $snapshot['banner_revision'] );
		$this->assertSame( 'https://example.com/privacy', $snapshot['policy_urls']['privacy_policy_url'] );
		$this->assertSame( 'Custom title', $snapshot['banner_texts']['consent_modal_title'] );
		$this->assertSame( 'Policy intro text', $snapshot['policy_texts']['intro'] );
		$this->assertArrayHasKey( 'statistics', $snapshot['categories'] );
		$this->assertTrue( $snapshot['categories']['statistics']['enabled_in_banner'] );
		$this->assertNotEmpty( $snapshot['content_hash'] );
		$this->assertSame( 64, strlen( $snapshot['content_hash'] ) );
	}

	/**
	 * Logged entries persist the v2 snapshot payload.
	 */
	public function test_log_stores_rich_snapshot(): void {
		$settings = get_option( 'wpeu_cs_settings', array() );
		$settings['consent_logging_enabled'] = true;
		$settings['privacy_policy_url']    = 'https://example.com/privacy';
		update_option( 'wpeu_cs_settings', $settings );

		$logger = new ConsentLogger();
		$log_id = $logger->log(
			array(
				'consent_uuid' => '550e8400-e29b-41d4-a716-446655440000',
				'event_type'   => 'accept_all',
				'categories'   => array( 'statistics' => true ),
				'page_url'     => 'https://example.com/',
				'locale'       => 'en',
			)
		);

		global $wpdb;
		$table = ConsentLogger::get_table_name();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT config_snapshot FROM {$table} WHERE id = %d", $log_id ), ARRAY_A );
		$data  = json_decode( (string) $row['config_snapshot'], true );

		$this->assertIsArray( $data );
		$this->assertSame( 2, $data['snapshot_version'] );
		$this->assertArrayHasKey( 'banner_texts', $data );
		$this->assertArrayHasKey( 'content_hash', $data );
	}
}
