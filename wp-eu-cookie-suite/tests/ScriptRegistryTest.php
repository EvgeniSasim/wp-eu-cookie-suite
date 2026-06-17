<?php
/**
 * Test script registry and blocker logic.
 */

use PHPUnit\Framework\TestCase;
use WPEU\CookieSuite\Frontend\ScriptBlocker;
use WPEU\CookieSuite\Frontend\ScriptRegistry;

class Test_Script_Registry extends TestCase {

	public function setUp(): void {
		parent::setUp();
		$GLOBALS['mock_options'] = array(
			'wpeu_cs_settings' => array(
				'blocker_enabled' => true,
				'enabled_services' => array(
					'google-analytics' => true,
					'youtube' => true,
				),
			),
		);
		$_COOKIE = array();
	}

	public function test_get_services() {
		$services = ScriptRegistry::get_services();
		$this->assertArrayHasKey( 'google-analytics', $services );
		$this->assertArrayHasKey( 'youtube', $services );
	}

	public function test_script_blocking_ga() {
		$blocker = new ScriptBlocker();
		$html = '<script src="https://www.google-analytics.com/analytics.js"></script>';
		$processed = $blocker->process_output( $html );

		$this->assertStringContainsString( 'type="text/plain"', $processed );
		$this->assertStringContainsString( 'data-category="statistics"', $processed );
	}

	public function test_script_blocking_inline() {
		$blocker = new ScriptBlocker();
		$html = '<script>gtag("config", "UA-12345-1");</script>';
		$processed = $blocker->process_output( $html );

		$this->assertStringContainsString( 'type="text/plain"', $processed );
		$this->assertStringContainsString( 'data-category="statistics"', $processed );
	}

	public function test_script_no_blocking_when_consented() {
		$_COOKIE['wpeu_statistics'] = '1';
		$blocker = new ScriptBlocker();
		$html = '<script src="https://www.google-analytics.com/analytics.js"></script>';
		$processed = $blocker->process_output( $html );

		$this->assertStringNotContainsString( 'type="text/plain"', $processed );
		$this->assertEquals( $html, $processed );
	}

	public function test_custom_block_rules() {
		$GLOBALS['mock_options']['wpeu_cs_settings']['custom_block_rules'] = "custom-script.js\n-url-blocked-url.js";
		$blocker = new ScriptBlocker();

		// Substring match
		$html = '<script src="https://example.com/custom-script.js"></script>';
		$processed = $blocker->process_output( $html );
		$this->assertStringContainsString( 'data-category="marketing"', $processed );

		// -url- prefix match
		$html = '<script src="https://example.com/blocked-url.js"></script>';
		$processed = $blocker->process_output( $html );
		$this->assertStringContainsString( 'data-category="marketing"', $processed );

		// -url- should NOT match content
		$html = '<script>console.log("blocked-url.js");</script>';
		$processed = $blocker->process_output( $html );
		$this->assertStringNotContainsString( 'type="text/plain"', $processed );
	}
}
