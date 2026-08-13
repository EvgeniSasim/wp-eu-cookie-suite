<?php
/**
 * Admin AJAX preview tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Admin\Admin;

/**
 * Admin AJAX test case.
 */
class Test_Admin_Ajax extends WP_Ajax_UnitTestCase {

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

		// Set current user to admin
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		if ( ! defined( 'WPEU_CS_VERSION' ) ) {
			define( 'WPEU_CS_VERSION', '0.1.0' );
		}
		if ( ! defined( 'WPEU_CS_URL' ) ) {
			define( 'WPEU_CS_URL', 'http://example.org/wp-content/plugins/privaro-cookie-consent-banner/' );
		}
		if ( ! defined( 'WPEU_CS_PATH' ) ) {
			define( 'WPEU_CS_PATH', '/tmp/' );
		}
	}

	/**
	 * Test ajax_preview returns expected HTML and config.
	 */
	public function test_ajax_preview(): void {
		$nonce = wp_create_nonce( 'wpeu-cs-preview' );

		$_POST['nonce']    = $nonce;
		$_POST['settings'] = array(
			'banner_ui' => array(
				'layout'        => 'bar',
				'position'      => 'bottom-center',
				'theme'         => 'dark',
				'primary_color' => '#ff0000',
			),
			'banner_texts' => array(
				'en' => array(
					'consent_modal_title' => 'Custom Preview Title',
				),
			),
			'enabled_categories' => array( 'statistics' ),
			'show_reject_all'    => 'false',
			'eu_mode'            => 'true',
		);

		try {
			$this->_handleAjax( 'wpeu_cs_preview' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected for exit.
		}

		$response = $this->_last_response;

		$this->assertStringContainsString( '<!DOCTYPE html>', $response );
		$this->assertStringContainsString( 'Custom Preview Title', $response );
		$this->assertStringContainsString( '"layout":"bar"', $response );
		$this->assertStringContainsString( '"position":"bottom center"', $response );
		$this->assertStringContainsString( 'cc--darkmode', $response );
		$this->assertStringContainsString( '--cc-btn-primary-bg:#ff0000;', $response );
		$this->assertStringContainsString( '"mode":"opt-in"', $response ); // eu_mode: true
		$this->assertStringContainsString( '"acceptNecessaryBtn":""', $response ); // show_reject_all: false
		$this->assertStringContainsString( 'window.CookieConsent', $response );
		$this->assertStringContainsString( 'cc.run(', $response );
	}

	/**
	 * Preview must not recurse when validating enabled_categories (regression v1.3.3).
	 */
	public function test_ajax_preview_with_enabled_categories_does_not_recurse(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'custom_categories' => array(
					'analytics' => array(
						'label'           => 'Analytics',
						'description'     => 'Custom analytics cookies',
						'integration_map' => 'statistics',
					),
				),
			)
		);

		$nonce = wp_create_nonce( 'wpeu-cs-preview' );

		$_POST['nonce']    = $nonce;
		$_POST['settings'] = array(
			'enabled_categories' => array( 'statistics', 'analytics' ),
		);

		try {
			$this->_handleAjax( 'wpeu_cs_preview' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected for exit.
		}

		$response = $this->_last_response;

		$this->assertStringContainsString( '<!DOCTYPE html>', $response );
		$this->assertStringContainsString( 'window.CookieConsent', $response );
	}
}
