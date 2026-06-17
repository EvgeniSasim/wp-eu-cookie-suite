<?php
/**
 * Iframe Processor tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Consent\Categories;
use WPEU\CookieSuite\Frontend\IframeProcessor;

/**
 * Iframe processor test case.
 */
class Test_Iframe_Processor extends WP_UnitTestCase {

	/**
	 * Setup.
	 */
	public function set_up(): void {
		parent::set_up();
		update_option( 'wpeu_cs_settings', array(
			'enabled_integrations' => array(
				'iframe_placeholder' => true,
			),
			'enabled_services' => array(
				'youtube' => true,
				'vimeo' => true,
				'google-maps' => true,
			),
		) );

		// Clear cookies for each test.
		$_COOKIE = array();
	}

	/**
	 * YouTube iframe is replaced with placeholder when no consent.
	 */
	public function test_replaces_youtube_iframe_without_consent(): void {
		$iframe = '<iframe width="560" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ" frameborder="0" allowfullscreen></iframe>';
		$processed = IframeProcessor::process_iframes( $iframe );

		$this->assertStringContainsString( 'wpeu-cs-iframe-placeholder', $processed );
		$this->assertStringContainsString( 'data-category="marketing"', $processed );
		$this->assertStringContainsString( 'data-service="youtube"', $processed );
		$this->assertStringContainsString( base64_encode( $iframe ), $processed );
	}

	/**
	 * Google Maps iframe (BSB pattern) is replaced.
	 */
	public function test_replaces_google_maps_iframe_without_consent(): void {
		$iframe = '<iframe src="https://maps.google.com/maps?q=Berlin&t=&z=13&ie=UTF8&iwloc=&output=embed"></iframe>';
		$processed = IframeProcessor::process_iframes( $iframe );

		$this->assertStringContainsString( 'wpeu-cs-iframe-placeholder', $processed );
		$this->assertStringContainsString( 'data-category="marketing"', $processed );
		$this->assertStringContainsString( 'data-service="google-maps"', $processed );
	}

	/**
	 * Iframe is NOT replaced if user has consent.
	 */
	public function test_does_not_replace_with_consent(): void {
		$_COOKIE['wpeu_marketing'] = '1';

		$iframe = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>';
		$processed = IframeProcessor::process_iframes( $iframe );

		$this->assertSame( $iframe, $processed );
	}

	/**
	 * Iframe is NOT replaced if service is disabled.
	 */
	public function test_does_not_replace_if_service_disabled(): void {
		update_option( 'wpeu_cs_settings', array(
			'enabled_integrations' => array(
				'iframe_placeholder' => true,
			),
			'enabled_services' => array(
				'youtube' => false,
			),
		) );

		$iframe = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>';
		$processed = IframeProcessor::process_iframes( $iframe );

		$this->assertSame( $iframe, $processed );
	}

	/**
	 * Iframe is NOT replaced if integration is disabled.
	 */
	public function test_does_not_replace_if_integration_disabled(): void {
		update_option( 'wpeu_cs_settings', array(
			'enabled_integrations' => array(
				'iframe_placeholder' => false,
			),
		) );

		$iframe = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>';
		$processed = IframeProcessor::process_iframes( $iframe );

		$this->assertSame( $iframe, $processed );
	}

	/**
	 * Idempotency test: already replaced content is not processed again.
	 */
	public function test_idempotency(): void {
		$iframe = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>';
		$first_pass = IframeProcessor::process_iframes( $iframe );
		$second_pass = IframeProcessor::process_iframes( $first_pass );

		$this->assertSame( $first_pass, $second_pass );
		$this->assertStringContainsString( 'wpeu-cs-iframe-placeholder', $second_pass );
		$this->assertStringNotContainsString( '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>', $second_pass );
	}
}
