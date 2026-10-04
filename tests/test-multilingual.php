<?php
/**
 * Tests for dynamic multilingual support.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Admin\Admin;
use WPEU\CookieSuite\Consent\BannerTexts;

/**
 * Multilingual test case.
 */
class Test_Multilingual extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		update_option( 'wpeu_cs_settings', array() );
	}

	public function test_get_locales_defaults(): void {
		$locales = BannerTexts::get_locales();
		$this->assertArrayHasKey( 'en', $locales );
		$this->assertArrayHasKey( 'de', $locales );

		$site_locale = substr( get_locale(), 0, 2 );
		$this->assertArrayHasKey( $site_locale, $locales );
	}

	public function test_get_locales_from_settings(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'banner_texts'    => array(
					'fr' => array( 'consent_modal_title' => 'Cookies' ),
				),
				'language_labels' => array(
					'fr' => 'Français',
				),
			)
		);

		$locales = BannerTexts::get_locales();
		$this->assertArrayHasKey( 'fr', $locales );
		$this->assertEquals( 'Français', $locales['fr'] );
	}

	public function test_get_locales_filter(): void {
		add_filter(
			'wpeu_cs_locales',
			function ( $locales ) {
				$locales['it'] = 'Italiano';
				return $locales;
			}
		);

		$locales = BannerTexts::get_locales();
		$this->assertArrayHasKey( 'it', $locales );
		$this->assertEquals( 'Italiano', $locales['it'] );
	}

	public function test_get_strings_fallback(): void {
		// No FR settings saved.
		$strings     = BannerTexts::get_strings( 'fr' );
		$en_defaults = BannerTexts::get_defaults( 'en' );

		$this->assertEquals( $en_defaults['consent_modal_title'], $strings['consent_modal_title'] );
	}

	public function test_get_default_policy_template_fallback(): void {
		$template_fr = BannerTexts::get_default_policy_template( 'fr' );
		$template_en = BannerTexts::get_default_policy_template( 'en' );
		$template_de = BannerTexts::get_default_policy_template( 'de' );

		$this->assertEquals( $template_en, $template_fr );
		$this->assertNotEquals( $template_de, $template_fr );
	}

	/**
	 * Settings API sanitize must not discard programmatic language writes (regression).
	 */
	public function test_write_context_settings_persists_new_language(): void {
		$admin = new Admin();
		$admin->register_settings();

		$write = new ReflectionMethod( Admin::class, 'write_context_settings' );
		$write->setAccessible( true );

		$baseline = array(
			'banner_texts' => array(
				'en' => array( 'consent_modal_title' => 'We use cookies' ),
			),
		);
		$write->invoke( $admin, $baseline );

		$payload                       = $baseline;
		$payload['language_labels']    = array( 'ru' => 'Русский' );
		$payload['banner_texts']['ru'] = BannerTexts::get_defaults( 'en' );
		$payload['policy_texts']       = array(
			'ru' => array(
				'intro'    => '',
				'template' => BannerTexts::get_default_policy_template( 'en' ),
			),
		);

		$write->invoke( $admin, $payload );

		$saved = get_option( 'wpeu_cs_settings', array() );
		$this->assertSame( 'Русский', $saved['language_labels']['ru'] ?? null );
		$this->assertArrayHasKey( 'ru', $saved['banner_texts'] ?? array() );
		$this->assertNotEmpty( $saved['banner_texts']['ru']['consent_modal_title'] ?? '' );

		$locales = BannerTexts::get_locales();
		$this->assertArrayHasKey( 'ru', $locales );
		$this->assertSame( 'Русский', $locales['ru'] );
	}

	/**
	 * Without sanitize bypass, a full update_option payload is treated as a form submit and dropped.
	 */
	public function test_settings_sanitize_without_active_tab_preserves_old_option(): void {
		$admin = new Admin();
		$admin->register_settings();

		$write = new ReflectionMethod( Admin::class, 'write_context_settings' );
		$write->setAccessible( true );
		$write->invoke(
			$admin,
			array(
				'banner_texts' => array(
					'en' => array( 'consent_modal_title' => 'EN' ),
				),
			)
		);

		// Simulate what broke add-language before the fix: sanitize sees a full payload
		// with no active_tab and returns the previous option unchanged.
		$attempt = array(
			'banner_texts'    => array(
				'en' => array( 'consent_modal_title' => 'EN' ),
				'ru' => BannerTexts::get_defaults( 'en' ),
			),
			'language_labels' => array( 'ru' => 'Русский' ),
		);

		$sanitized = $admin->sanitize_settings( $attempt );
		$this->assertArrayNotHasKey( 'language_labels', $sanitized );
		$this->assertArrayNotHasKey( 'ru', $sanitized['banner_texts'] ?? array() );
	}

	public function test_normalize_locale_code_accepts_html_lang_and_wp_locale(): void {
		$this->assertSame( 'ru', BannerTexts::normalize_locale_code( 'ru' ) );
		$this->assertSame( 'ru', BannerTexts::normalize_locale_code( 'RU' ) );
		$this->assertSame( 'ru', BannerTexts::normalize_locale_code( 'ru-RU' ) );
		$this->assertSame( 'ru', BannerTexts::normalize_locale_code( 'ru_RU' ) );
		$this->assertSame( 'pt', BannerTexts::normalize_locale_code( 'pt-BR' ) );
		$this->assertSame( '', BannerTexts::normalize_locale_code( '!!!' ) );
		$this->assertTrue( BannerTexts::is_valid_locale_code( 'ru-RU' ) );
		$this->assertFalse( BannerTexts::is_valid_locale_code( 'x' ) );
	}

	public function test_get_strings_reads_legacy_ru_ru_key(): void {
		$admin = new Admin();
		$admin->register_settings();
		$write = new ReflectionMethod( Admin::class, 'write_context_settings' );
		$write->setAccessible( true );
		$write->invoke(
			$admin,
			array(
				'banner_texts' => array(
					'ru-ru' => array(
						'consent_modal_title' => 'Мы используем cookies',
					),
				),
			)
		);

		$strings = BannerTexts::get_strings( 'ru' );
		$this->assertSame( 'Мы используем cookies', $strings['consent_modal_title'] );

		$locales = BannerTexts::get_locales();
		$this->assertArrayHasKey( 'ru', $locales );
		$this->assertArrayNotHasKey( 'ru-ru', $locales );
	}

	public function test_get_active_locale_uses_determine_locale(): void {
		add_filter(
			'locale',
			static function () {
				return 'ru_RU';
			}
		);

		$this->assertSame( 'ru', BannerTexts::get_active_locale() );
	}

	public function test_get_active_locale_filter_overrides(): void {
		add_filter(
			'wpeu_cs_banner_locale',
			static function () {
				return 'de-DE';
			}
		);

		$this->assertSame( 'de', BannerTexts::get_active_locale() );
	}

	public function test_get_strings_ignores_empty_saved_values(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'banner_texts' => array(
					'en' => array(
						'consent_modal_title' => '',
						'accept_all_btn'      => 'Accept all custom',
					),
				),
			)
		);

		$strings  = BannerTexts::get_strings( 'en' );
		$defaults = BannerTexts::get_defaults( 'en' );

		$this->assertSame( $defaults['consent_modal_title'], $strings['consent_modal_title'] );
		$this->assertSame( 'Accept all custom', $strings['accept_all_btn'] );
	}
}
