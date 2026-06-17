<?php
/**
 * Test WP Consent Bridge and polyfills.
 */

use PHPUnit\Framework\TestCase;

class Test_Wp_Consent_Bridge extends TestCase {

	public function setUp(): void {
		parent::setUp();
		$_COOKIE = array();
	}

	public function test_wp_has_consent_polyfill() {
		// Mock category mapping: statistics -> statistics
		$_COOKIE['wpeu_statistics'] = '1';
		$this->assertTrue( wp_has_consent( 'statistics' ) );

		// Mock category mapping: necessary -> functional
		$_COOKIE['wpeu_necessary'] = '1'; // Actually always true in helper, but let's check polyfill
		$this->assertTrue( wp_has_consent( 'functional' ) );

		$_COOKIE['wpeu_marketing'] = '0';
		$this->assertFalse( wp_has_consent( 'marketing' ) );
	}

	public function test_wp_get_consent_type_polyfill() {
		$this->assertEquals( 'optin', wp_get_consent_type() );
	}
}
