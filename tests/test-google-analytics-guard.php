<?php
/**
 * Google Analytics guard tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Frontend\ScriptRegistry;
use WPEU\CookieSuite\Integrations\GoogleAnalyticsGuard;

/**
 * GA guard test case.
 */
class Test_Google_Analytics_Guard extends WP_UnitTestCase {

	/**
	 * GA cookie name matcher covers common variants.
	 */
	public function test_is_ga_cookie_name(): void {
		$this->assertTrue( GoogleAnalyticsGuard::is_ga_cookie_name( '_ga' ) );
		$this->assertTrue( GoogleAnalyticsGuard::is_ga_cookie_name( '_gid' ) );
		$this->assertTrue( GoogleAnalyticsGuard::is_ga_cookie_name( '_ga_XXXXX' ) );
		$this->assertTrue( GoogleAnalyticsGuard::is_ga_cookie_name( '_gat' ) );
		$this->assertTrue( GoogleAnalyticsGuard::is_ga_cookie_name( '_gat_UA-123-1' ) );
		$this->assertFalse( GoogleAnalyticsGuard::is_ga_cookie_name( 'wpeu_statistics' ) );
	}

	/**
	 * gtag handle and URL are blocked without consent.
	 */
	public function test_should_block_script(): void {
		$this->assertTrue(
			GoogleAnalyticsGuard::should_block_script(
				'google_gtagjs',
				'https://www.googletagmanager.com/gtag/js?id=G-TEST'
			)
		);
		$this->assertTrue(
			GoogleAnalyticsGuard::should_block_script(
				'custom-handle',
				'https://www.googletagmanager.com/gtag/js?id=G-TEST'
			)
		);
		$this->assertFalse(
			GoogleAnalyticsGuard::should_block_script(
				'jquery',
				'https://example.com/wp-includes/js/jquery/jquery.min.js'
			)
		);
	}

	/**
	 * Registry includes hardened gtag URL pattern.
	 */
	public function test_registry_includes_gtag_url_pattern(): void {
		$category = ScriptRegistry::find_category_for_script(
			'https://www.googletagmanager.com/gtag/js?id=G-TEST',
			''
		);

		$this->assertSame( 'statistics', $category );
	}

	/**
	 * Guard blocks when statistics consent is missing.
	 */
	public function test_should_block_without_statistics_consent(): void {
		$_COOKIE = array();
		$guard   = new GoogleAnalyticsGuard();

		$this->assertTrue( $guard->should_block() );
	}

	/**
	 * Guard allows when statistics consent is granted.
	 */
	public function test_should_not_block_with_statistics_consent(): void {
		$_COOKIE['wpeu_statistics'] = '1';
		$guard                        = new GoogleAnalyticsGuard();

		$this->assertFalse( $guard->should_block() );
	}
}
