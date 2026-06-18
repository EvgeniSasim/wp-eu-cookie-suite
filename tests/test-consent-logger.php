<?php
/**
 * Tests for ConsentLogger.
 *
 * @package WPEU\CookieSuite
 */

class Test_Consent_Logger extends WP_UnitTestCase {

	private $logger;

	public function set_up() {
		parent::set_up();
		$this->logger = new \WPEU\CookieSuite\Consent\ConsentLogger();

		// Enable logging for tests
		$settings = get_option( 'wpeu_cs_settings', array() );
		$settings['consent_logging_enabled'] = true;
		update_option( 'wpeu_cs_settings', $settings );
	}

	public function test_log_insertion() {
		$data = array(
			'consent_uuid' => '550e8400-e29b-41d4-a716-446655440000',
			'event_type'   => 'accept_all',
			'categories'   => array( 'statistics' => true, 'marketing' => true ),
			'page_url'     => 'https://example.com/',
			'locale'       => 'en',
		);

		$log_id = $this->logger->log( $data );
		$this->assertNotFalse( $log_id );

		global $wpdb;
		$table = \WPEU\CookieSuite\Consent\ConsentLogger::get_table_name();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $log_id ), ARRAY_A );

		$this->assertEquals( $data['consent_uuid'], $row['consent_uuid'] );
		$this->assertEquals( 'accept_all', $row['event_type'] );
		$this->assertStringContainsString( 'statistics', $row['categories'] );
		$this->assertNotEmpty( $row['config_snapshot'] );
	}

	public function test_cleanup_expired_logs() {
		global $wpdb;
		$table = \WPEU\CookieSuite\Consent\ConsentLogger::get_table_name();

		// Insert old log
		$wpdb->insert(
			$table,
			array(
				'consent_uuid'   => 'old-uuid',
				'event_type'     => 'revoke',
				'categories'     => '{}',
				'consent_mode'   => 'optin',
				'page_url'       => 'https://example.com/',
				'locale'         => 'en',
				'plugin_version' => '0.1.0',
				'created_at'     => gmdate( 'Y-m-d H:i:s', time() - ( 400 * DAY_IN_SECONDS ) ),
			)
		);

		// Insert fresh log
		$wpdb->insert(
			$table,
			array(
				'consent_uuid'   => 'fresh-uuid',
				'event_type'     => 'accept_all',
				'categories'     => '{}',
				'consent_mode'   => 'optin',
				'page_url'       => 'https://example.com/',
				'locale'         => 'en',
				'plugin_version' => '0.1.0',
				'created_at'     => gmdate( 'Y-m-d H:i:s' ),
			)
		);

		// Default retention is 365
		$deleted = $this->logger->cleanup_expired_logs();
		$this->assertEquals( 1, $deleted );

		$remaining = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
		$this->assertEquals( 1, $remaining );
	}

	public function test_logging_disabled() {
		$settings = get_option( 'wpeu_cs_settings', array() );
		$settings['consent_logging_enabled'] = false;
		update_option( 'wpeu_cs_settings', $settings );

		$data = array(
			'consent_uuid' => '550e8400-e29b-41d4-a716-446655440000',
			'event_type'   => 'accept_all',
		);

		$log_id = $this->logger->log( $data );
		$this->assertFalse( $log_id );
	}
}
