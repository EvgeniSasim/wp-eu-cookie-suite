<?php
/**
 * Scanner AJAX tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Scanner\Scanner;

/**
 * Scanner AJAX test case.
 */
class Test_Scanner_Ajax extends WP_Ajax_UnitTestCase {

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Set current user to admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Ensure the scanner is initialized (it might already be by the plugin).
		new Scanner();
	}

	/**
	 * Test wpeu_cs_get_scan_urls: nonce and capability.
	 */
	public function test_get_scan_urls_permissions(): void {
		// No nonce.
		try {
			$this->_handleAjax( 'wpeu_cs_get_scan_urls' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected.
			unset( $e );
		}
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );

		// Non-admin user.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST['nonce'] = wp_create_nonce( 'wpeu-cs-scanner' );
		try {
			$this->_handleAjax( 'wpeu_cs_get_scan_urls' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected.
			unset( $e );
		}
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Insufficient permissions.', $response['data']['message'] );
	}

	/**
	 * Test wpeu_cs_get_scan_urls: rate limiting.
	 */
	public function test_get_scan_urls_rate_limit(): void {
		$_POST['nonce'] = wp_create_nonce( 'wpeu-cs-scanner' );
		update_option( 'wpeu_cs_last_scan_time', time() );

		try {
			$this->_handleAjax( 'wpeu_cs_get_scan_urls' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected.
			unset( $e );
		}
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Please wait at least one minute between scans.', $response['data']['message'] );
	}

	/**
	 * Test wpeu_cs_get_scan_urls: success.
	 */
	public function test_get_scan_urls_success(): void {
		$_POST['nonce'] = wp_create_nonce( 'wpeu-cs-scanner' );
		delete_option( 'wpeu_cs_last_scan_time' );

		// Mock sitemaps if needed, but the fallback should at least return home URL.
		try {
			$this->_handleAjax( 'wpeu_cs_get_scan_urls' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected.
			unset( $e );
		}
		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );
		$this->assertContains( home_url( '/' ), $response['data']['urls'] );
	}

	/**
	 * Test wpeu_cs_scan_url.
	 */
	public function test_scan_url(): void {
		$_POST['nonce'] = wp_create_nonce( 'wpeu-cs-scanner' );
		$_POST['url']   = 'http://example.org/test-page';

		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) {
				if ( 'http://example.org/test-page' === $url ) {
					return array(
						'response' => array(
							'code'    => 200,
							'message' => 'OK',
						),
						'headers'  => array(
							'set-cookie' => array( 'test_cookie=val; path=/', '_ga=GA1.2.3; path=/' ),
						),
						'body'     => '<html><body><script src="https://www.google-analytics.com/analytics.js"></script></body></html>', // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
					);
				}
				return $pre;
			},
			10,
			3
		);

		try {
			$this->_handleAjax( 'wpeu_cs_scan_url' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected.
			unset( $e );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );

		$cookies = $response['data']['results']['cookies'];
		$scripts = $response['data']['results']['scripts'];

		$cookie_names = wp_list_pluck( $cookies, 'name' );
		$this->assertContains( 'test_cookie', $cookie_names );
		$this->assertContains( '_ga', $cookie_names );

		$script_names = wp_list_pluck( $scripts, 'name' );
		$this->assertContains( 'www.google-analytics.com', $script_names );

		// Check if it saved to option.
		$saved = get_option( 'wpeu_cs_scan_results' );
		$this->assertNotEmpty( $saved['cookies'] );
	}

	/**
	 * Test wpeu_cs_import_scan.
	 */
	public function test_import_scan(): void {
		$_POST['nonce'] = wp_create_nonce( 'wpeu-cs-scanner' );

		$scan_results = array(
			'cookies' => array(
				array(
					'name'     => 'imported_cookie',
					'type'     => 'cookie',
					'category' => 'statistics',
				),
			),
			'scripts' => array(),
		);
		update_option( 'wpeu_cs_scan_results', $scan_results );

		try {
			$this->_handleAjax( 'wpeu_cs_import_scan' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected.
			unset( $e );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );
		$this->assertStringContainsString( 'Imported 1 items', $response['data']['message'] );

		// Verify it is in database.
		$repository = new \WPEU\CookieSuite\Scanner\CookieRepository();
		$cookies    = $repository->all();
		$names      = wp_list_pluck( $cookies, 'name' );
		$this->assertContains( 'imported_cookie', $names );
	}
}
