<?php
/**
 * Test consent helpers.
 */

use PHPUnit\Framework\TestCase;

class Test_Consent_Helpers extends TestCase {

	public function setUp(): void {
		parent::setUp();
		$_COOKIE = array();
		$GLOBALS['mock_options'] = array();
	}

	public function test_wpeu_cs_user_has_consent_necessary() {
		$this->assertTrue( wpeu_cs_user_has_consent( 'necessary' ) );
	}

	public function test_wpeu_cs_user_has_consent_individual_cookie() {
		$_COOKIE['wpeu_statistics'] = '1';
		$this->assertTrue( wpeu_cs_user_has_consent( 'statistics' ) );

		$_COOKIE['wpeu_marketing'] = '0';
		$this->assertFalse( wpeu_cs_user_has_consent( 'marketing' ) );

		$_COOKIE['wpeu_preferences'] = 'allow';
		$this->assertTrue( wpeu_cs_user_has_consent( 'preferences' ) );
	}

	public function test_wpeu_cs_user_has_consent_json_cookie() {
		$consent = array(
			'statistics' => true,
			'marketing'  => false,
		);
		$_COOKIE['wpeu_consent'] = json_encode( $consent );

		$this->assertTrue( wpeu_cs_user_has_consent( 'statistics' ) );
		$this->assertFalse( wpeu_cs_user_has_consent( 'marketing' ) );
		$this->assertFalse( wpeu_cs_user_has_consent( 'preferences' ) );
	}

	public function test_wpeu_cs_user_has_consent_json_cookie_urlencoded() {
		$consent = array(
			'statistics' => true,
		);
		$_COOKIE['wpeu_consent'] = rawurlencode( json_encode( $consent ) );

		$this->assertTrue( wpeu_cs_user_has_consent( 'statistics' ) );
	}
}
