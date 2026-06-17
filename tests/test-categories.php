<?php
/**
 * Categories tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Consent\Categories;

/**
 * Categories test case.
 */
class Test_Categories extends WP_UnitTestCase {

	/**
	 * All categories are returned with required keys.
	 */
	public function test_get_all_returns_four_categories(): void {
		$categories = Categories::get_all();

		$this->assertCount( 4, $categories );
		$this->assertArrayHasKey( Categories::NECESSARY, $categories );
		$this->assertArrayHasKey( Categories::PREFERENCES, $categories );
		$this->assertArrayHasKey( Categories::STATISTICS, $categories );
		$this->assertArrayHasKey( Categories::MARKETING, $categories );
	}

	/**
	 * Necessary category is read-only and enabled.
	 */
	public function test_necessary_category_is_read_only(): void {
		$categories = Categories::get_all();
		$necessary  = $categories[ Categories::NECESSARY ];

		$this->assertTrue( $necessary['read_only'] );
		$this->assertTrue( $necessary['enabled'] );
	}

	/**
	 * Consent helper always allows necessary cookies.
	 */
	public function test_user_has_consent_for_necessary(): void {
		$this->assertTrue( wpeu_cs_user_has_consent( 'necessary' ) );
	}

	/**
	 * Consent helper reads per-category cookie.
	 */
	public function test_user_has_consent_from_category_cookie(): void {
		$_COOKIE['wpeu_statistics'] = '1';
		$this->assertTrue( wpeu_cs_user_has_consent( 'statistics' ) );

		unset( $_COOKIE['wpeu_statistics'] );
		$this->assertFalse( wpeu_cs_user_has_consent( 'statistics' ) );
	}

	/**
	 * Consent helper reads JSON consent blob.
	 */
	public function test_user_has_consent_from_json_cookie(): void {
		$_COOKIE['wpeu_consent'] = rawurlencode( wp_json_encode( array( 'marketing' => true ) ) );
		$this->assertTrue( wpeu_cs_user_has_consent( 'marketing' ) );

		unset( $_COOKIE['wpeu_consent'] );
		$this->assertFalse( wpeu_cs_user_has_consent( 'marketing' ) );
	}
}
