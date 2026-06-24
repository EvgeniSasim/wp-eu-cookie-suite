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
	 * @var array<string, mixed>
	 */
	private $original_settings = array();

	/**
	 * Backup settings before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->original_settings = get_option( 'wpeu_cs_settings', array() );
	}

	/**
	 * Restore settings after each test.
	 */
	public function tear_down(): void {
		update_option( 'wpeu_cs_settings', $this->original_settings );
		parent::tear_down();
	}

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
	 * Custom categories merge into get_all().
	 */
	public function test_get_all_includes_custom_categories(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'custom_categories' => array(
					'social' => array(
						'label'           => 'Social Media',
						'description'     => 'Social widgets',
						'integration_map' => Categories::MARKETING,
					),
				),
			)
		);

		$categories = Categories::get_all();
		$this->assertArrayHasKey( 'social', $categories );
		$this->assertSame( Categories::MARKETING, $categories['social']['integration_map'] );
		$this->assertTrue( $categories['social']['custom'] );
	}

	/**
	 * Invalid custom slug is rejected.
	 */
	public function test_is_valid_slug_rejects_builtin_and_invalid(): void {
		$this->assertFalse( Categories::is_valid_slug( Categories::MARKETING ) );
		$this->assertFalse( Categories::is_valid_slug( 'a' ) );
		$this->assertFalse( Categories::is_valid_slug( 'Bad Slug' ) );
		$this->assertTrue( Categories::is_valid_slug( 'social' ) );
	}

	/**
	 * Integration map resolves for custom categories.
	 */
	public function test_get_integration_map_for_custom_category(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'custom_categories' => array(
					'embeds' => array(
						'label'           => 'Embeds',
						'description'     => 'Embedded content',
						'integration_map' => Categories::MARKETING,
					),
				),
			)
		);

		$this->assertSame( Categories::MARKETING, Categories::get_integration_map( 'embeds' ) );
		$this->assertSame( 'functional', Categories::get_wp_consent_category( Categories::NECESSARY ) );
	}

	/**
	 * Enabled banner categories include necessary and selected slugs.
	 */
	public function test_get_enabled_for_banner(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'enabled_categories' => array( 'social', Categories::STATISTICS ),
				'custom_categories'  => array(
					'social' => array(
						'label'           => 'Social',
						'description'     => '',
						'integration_map' => Categories::MARKETING,
					),
				),
			)
		);

		$banner = Categories::get_enabled_for_banner();
		$this->assertArrayHasKey( Categories::NECESSARY, $banner );
		$this->assertArrayHasKey( 'social', $banner );
		$this->assertArrayHasKey( Categories::STATISTICS, $banner );
		$this->assertArrayNotHasKey( Categories::MARKETING, $banner );
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

	/**
	 * Unknown keys in consent JSON are ignored.
	 */
	public function test_user_has_consent_ignores_unknown_json_keys(): void {
		$_COOKIE['wpeu_consent'] = rawurlencode(
			wp_json_encode(
				array(
					'marketing' => true,
					'<script>'  => true,
				)
			)
		);

		$this->assertTrue( wpeu_cs_user_has_consent( 'marketing' ) );
		$this->assertFalse( wpeu_cs_user_has_consent( '<script>' ) );
	}

	/**
	 * wp_has_consent respects custom category integration map.
	 */
	public function test_wp_has_consent_custom_category_mapping(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'custom_categories' => array(
					'social' => array(
						'label'           => 'Social',
						'description'     => '',
						'integration_map' => Categories::MARKETING,
					),
				),
			)
		);

		$_COOKIE['wpeu_social'] = '1';
		$this->assertTrue( wp_has_consent( 'marketing' ) );
		unset( $_COOKIE['wpeu_social'] );
	}
}
