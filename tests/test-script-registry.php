<?php
/**
 * Script registry tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Consent\Categories;
use WPEU\CookieSuite\Frontend\ScriptRegistry;

/**
 * Script registry test case.
 */
class Test_Script_Registry extends WP_UnitTestCase {

	/**
	 * Google Analytics src is matched as statistics.
	 */
	public function test_matches_google_analytics_src(): void {
		$category = ScriptRegistry::find_category_for_script(
			'https://www.google-analytics.com/analytics.js',
			''
		);

		$this->assertSame( Categories::STATISTICS, $category );
	}

	/**
	 * GTM inline snippet is matched.
	 */
	public function test_matches_gtm_inline_content(): void {
		$category = ScriptRegistry::find_category_for_script(
			'',
			"window.dataLayer = window.dataLayer || []; (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':"
		);

		$this->assertSame( Categories::STATISTICS, $category );
	}

	/**
	 * Facebook pixel is marketing.
	 */
	public function test_matches_facebook_pixel(): void {
		$category = ScriptRegistry::find_category_for_script(
			'https://connect.facebook.net/en_US/fbevents.js',
			''
		);

		$this->assertSame( Categories::MARKETING, $category );
	}

	/**
	 * Disabled services are skipped.
	 */
	public function test_respects_enabled_services(): void {
		$enabled = array( 'google-analytics' => false );

		$category = ScriptRegistry::find_category_for_script(
			'https://www.google-analytics.com/analytics.js',
			'',
			$enabled
		);

		$this->assertNull( $category );
	}

	/**
	 * Unknown scripts return null.
	 */
	public function test_unknown_script_returns_null(): void {
		$category = ScriptRegistry::find_category_for_script(
			'https://example.com/app.js',
			'console.log("hello");'
		);

		$this->assertNull( $category );
	}
}
