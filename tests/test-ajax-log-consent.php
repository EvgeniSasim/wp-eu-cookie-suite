<?php
/**
 * Consent Logging AJAX tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Admin\Admin;

/**
 * Consent Logging AJAX test case.
 */
class Test_Ajax_Log_Consent extends WP_Ajax_UnitTestCase {

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();
		new Admin();
	}

	/**
	 * Run AJAX action and return decoded JSON when available.
	 *
	 * @param string $action Action name.
	 * @return array<string, mixed>|string|null
	 */
	private function run_ajax( string $action ) {
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException | WPAjaxDieStopException $e ) {
			$message = $e->getMessage();
			if ( '-1' === (string) $message || -1 === $message ) {
				return -1;
			}
			unset( $e );
		}

		$decoded = json_decode( $this->_last_response, true );
		return is_array( $decoded ) ? $decoded : $this->_last_response;
	}

	/**
	 * Test wpeu_cs_log_consent: invalid nonce.
	 */
	public function test_log_consent_invalid_nonce(): void {
		$_POST['nonce'] = 'invalid';
		$result         = $this->run_ajax( 'wpeu_cs_log_consent' );
		$this->assertSame( -1, $result );
	}

	/**
	 * Test wpeu_cs_log_consent: logging disabled.
	 */
	public function test_log_consent_disabled(): void {
		$_POST['nonce'] = wp_create_nonce( 'wpeu-cs-log' );
		update_option( 'wpeu_cs_settings', array( 'consent_logging_enabled' => false ) );

		$response = $this->run_ajax( 'wpeu_cs_log_consent' );
		$this->assertIsArray( $response );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'logging_disabled', $response['data'] );
	}

	/**
	 * Test wpeu_cs_log_consent: success and row insertion.
	 */
	public function test_log_consent_success(): void {
		$_POST['nonce']        = wp_create_nonce( 'wpeu-cs-log' );
		$_POST['event_type']   = 'accept_all';
		$_POST['consent_uuid'] = '550e8400-e29b-41d4-a716-446655440000';
		$_POST['page_url']     = 'http://example.org/';
		$_POST['locale']       = 'en';
		$_POST['categories']   = array(
			'statistics' => 1,
			'marketing'  => 1,
		);

		$_SERVER['HTTP_USER_AGENT'] = 'Test Agent';
		$_SERVER['REMOTE_ADDR']     = '127.0.0.1';

		update_option(
			'wpeu_cs_settings',
			array(
				'consent_logging_enabled' => true,
				'consent_log_store_ip'    => true,
			)
		);

		$response = $this->run_ajax( 'wpeu_cs_log_consent' );
		$this->assertIsArray( $response );
		$this->assertTrue( $response['success'] );
		$this->assertArrayHasKey( 'log_id', $response['data'] );

		global $wpdb;
		$table = $wpdb->prefix . 'wpeu_consent_log';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $response['data']['log_id'] ), ARRAY_A );

		$this->assertNotNull( $row );
		$this->assertSame( '550e8400-e29b-41d4-a716-446655440000', $row['consent_uuid'] );
		$this->assertSame( 'accept_all', $row['event_type'] );
		$this->assertNotEmpty( $row['ip_hash'] );
		$this->assertSame( wpeu_cs_hash_ip( '127.0.0.1' ), $row['ip_hash'] );
	}

	/**
	 * Test wpeu_cs_log_consent: No IP hash when disabled.
	 */
	public function test_log_consent_no_ip_hash(): void {
		$_POST['nonce']        = wp_create_nonce( 'wpeu-cs-log' );
		$_POST['event_type']   = 'reject_all';
		$_POST['consent_uuid'] = '550e8400-e29b-41d4-a716-446655440001';

		$_SERVER['HTTP_USER_AGENT'] = 'Test Agent';
		$_SERVER['REMOTE_ADDR']     = '127.0.0.1';

		update_option(
			'wpeu_cs_settings',
			array(
				'consent_logging_enabled' => true,
				'consent_log_store_ip'    => false,
			)
		);

		$response = $this->run_ajax( 'wpeu_cs_log_consent' );
		$this->assertIsArray( $response );
		$this->assertTrue( $response['success'] );

		global $wpdb;
		$table = $wpdb->prefix . 'wpeu_consent_log';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $response['data']['log_id'] ), ARRAY_A );
		$this->assertNull( $row['ip_hash'] );
	}
}
