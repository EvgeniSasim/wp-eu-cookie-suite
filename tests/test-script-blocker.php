<?php
/**
 * Script blocker tests.
 *
 * @package WPEU\CookieSuite
 */

use WPEU\CookieSuite\Frontend\ScriptBlocker;

/**
 * Script blocker test case.
 */
class Test_Script_Blocker extends WP_UnitTestCase {

	/**
	 * Reset cookies between tests.
	 */
	public function setUp(): void {
		parent::setUp();
		foreach ( array_keys( $_COOKIE ) as $key ) {
			if ( str_starts_with( (string) $key, 'wpeu_' ) ) {
				unset( $_COOKIE[ $key ] );
			}
		}
		update_option(
			'wpeu_cs_settings',
			array(
				'blocker_enabled'           => true,
				'block_unknown_third_party' => false,
				'eu_mode'                   => true,
				'consent_revision'          => 0,
				'enabled_services'          => array(
					'google-analytics' => true,
					'gtm'              => true,
				),
			)
		);
	}

	/**
	 * Unknown third-party scripts stay allowed when the opt-in setting is off.
	 */
	public function test_unknown_third_party_not_blocked_by_default(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'blocker_enabled'           => true,
				'block_unknown_third_party' => false,
				'enabled_services'          => array(),
				'eu_mode'                   => true,
			)
		);

		$blocker = new ScriptBlocker();
		$html    = '<script src="https://cdn.unknown-tracker.test/pixel.js"></script>';
		$out     = $blocker->process_output( $html );

		$this->assertStringContainsString( 'cdn.unknown-tracker.test/pixel.js', $out );
		$this->assertStringNotContainsString( 'type="text/plain"', $out );
	}

	/**
	 * Unknown cross-origin scripts are blocked as marketing when enabled.
	 */
	public function test_unknown_third_party_blocked_when_enabled(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'blocker_enabled'           => true,
				'block_unknown_third_party' => true,
				'enabled_services'          => array(),
				'eu_mode'                   => true,
			)
		);

		$blocker = new ScriptBlocker();
		$html    = '<script src="https://cdn.unknown-tracker.test/pixel.js"></script>';
		$out     = $blocker->process_output( $html );

		$this->assertStringContainsString( 'type="text/plain"', $out );
		$this->assertStringContainsString( 'data-category="marketing"', $out );
	}

	/**
	 * Same-origin scripts are never auto-blocked as unknown third-party.
	 */
	public function test_same_origin_not_blocked_as_unknown(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'blocker_enabled'           => true,
				'block_unknown_third_party' => true,
				'enabled_services'          => array(),
				'eu_mode'                   => true,
			)
		);

		$home    = home_url( '/wp-content/themes/test/app.js' );
		$blocker = new ScriptBlocker();
		$html    = '<script src="' . esc_url( $home ) . '"></script>';
		$out     = $blocker->process_output( $html );

		$this->assertStringNotContainsString( 'type="text/plain"', $out );
	}

	/**
	 * Inline JS bodies must not be mutated when rewriting type (P1 attribute bug).
	 */
	public function test_inline_type_string_in_js_body_not_mutated(): void {
		$blocker = new ScriptBlocker();
		$html    = '<script>const type="module";gtag("config","G-TEST");</script>';
		$out     = $blocker->process_output( $html );

		$this->assertStringContainsString( 'const type="module"', $out );
		$this->assertStringNotContainsString( 'const type="text/plain"', $out );
		$this->assertMatchesRegularExpression( '/<script\b[^>]*\btype="text\/plain"/i', $out );
		$this->assertStringContainsString( 'data-category="statistics"', $out );
		$this->assertStringContainsString( 'data-wpeu-original-type="text/javascript"', $out );
	}

	/**
	 * data-type must not be treated as the executable type attribute.
	 */
	public function test_data_type_attribute_not_rewritten_as_type(): void {
		$blocker = new ScriptBlocker();
		$html    = '<script data-type="module" src="https://www.google-analytics.com/analytics.js"></script>';
		$out     = $blocker->process_output( $html );

		$this->assertStringContainsString( 'data-type="module"', $out );
		$this->assertMatchesRegularExpression( '/<script\b[^>]*\btype="text\/plain"/i', $out );
		$this->assertStringContainsString( 'data-category="statistics"', $out );
	}

	/**
	 * Unquoted src attributes must still be blocked.
	 */
	public function test_unquoted_src_is_blocked(): void {
		$blocker = new ScriptBlocker();
		$html    = '<script src=https://www.google-analytics.com/analytics.js></script>';
		$out     = $blocker->process_output( $html );

		$this->assertMatchesRegularExpression( '/<script\b[^>]*\btype="text\/plain"/i', $out );
		$this->assertStringContainsString( 'data-category="statistics"', $out );
	}

	/**
	 * Own Google Consent Mode defaults must stay executable.
	 */
	public function test_own_gcm_default_script_not_blocked(): void {
		$blocker = new ScriptBlocker();
		$html    = '<script id="wpeu-cs-gcm-default-js-after">gtag(\'consent\',\'default\',{analytics_storage:\'denied\'});</script>';
		$out     = $blocker->process_output( $html );

		$this->assertSame( $html, $out );
		$this->assertStringNotContainsString( 'type="text/plain"', $out );
	}

	/**
	 * JSON-LD scripts must not become executable via consent rewriting.
	 */
	public function test_json_ld_not_blocked(): void {
		$blocker = new ScriptBlocker();
		$html    = '<script type="application/ld+json">{"@context":"https://schema.org","gtag(":true}</script>';
		$out     = $blocker->process_output( $html );

		$this->assertStringContainsString( 'type="application/ld+json"', $out );
		$this->assertStringNotContainsString( 'type="text/plain"', $out );
	}

	/**
	 * Re-processing an already blocked tag is idempotent and keeps the JS body intact.
	 */
	public function test_process_output_is_idempotent(): void {
		$blocker = new ScriptBlocker();
		$html    = '<script>const type="module";gtag("config","G-TEST");</script>';
		$once    = $blocker->process_output( $html );
		$twice   = $blocker->process_output( $once );

		$this->assertSame( $once, $twice );
		$this->assertStringContainsString( 'const type="module"', $twice );
	}

	/**
	 * Resource hints must lose href/rel until consent (not just type=text/plain).
	 */
	public function test_preconnect_link_is_made_inert(): void {
		update_option(
			'wpeu_cs_settings',
			array(
				'blocker_enabled'  => true,
				'enabled_services' => array( 'google-fonts' => true ),
				'eu_mode'          => true,
			)
		);

		$blocker = new ScriptBlocker();
		$html    = '<link rel="preconnect" href="https://fonts.googleapis.com">';
		$out     = $blocker->process_output( $html );

		// If google-fonts is not in registry as marketing for fonts.googleapis, force via unknown/custom.
		if ( ! str_contains( $out, 'data-wpeu-href' ) ) {
			update_option(
				'wpeu_cs_settings',
				array(
					'blocker_enabled'           => true,
					'block_unknown_third_party' => true,
					'enabled_services'          => array(),
					'eu_mode'                   => true,
				)
			);
			$out = $blocker->process_output( $html );
		}

		$this->assertStringContainsString( 'data-wpeu-href="https://fonts.googleapis.com"', $out );
		$this->assertStringContainsString( 'rel="wpeu-blocked"', $out );
		$this->assertDoesNotMatchRegularExpression( '/(?:^|\s)href=/i', $out );
	}

	/**
	 * Module scripts store original type for restore.
	 */
	public function test_module_script_preserves_original_type(): void {
		$blocker = new ScriptBlocker();
		$html    = '<script type="module" src="https://www.google-analytics.com/analytics.js"></script>';
		$out     = $blocker->process_output( $html );

		$this->assertStringContainsString( 'data-wpeu-original-type="module"', $out );
		$this->assertMatchesRegularExpression( '/<script\b[^>]*\btype="text\/plain"/i', $out );
	}

	/**
	 * Legacy flush callback processes HTML that nested buffers would have appended.
	 */
	public function test_legacy_buffer_callback_handles_nested_buffer(): void {
		$blocker = new ScriptBlocker();
		$inner   = '<script src="https://www.google-analytics.com/analytics.js"></script>';
		$out     = $blocker->flush_legacy_output_buffer( $inner, PHP_OUTPUT_HANDLER_FINAL );

		$this->assertMatchesRegularExpression( '/type="text\/plain"/i', $out );
		$this->assertStringContainsString( 'data-category="statistics"', $out );
	}

	/**
	 * Progressive flush chunks are left untouched; final phase is processed.
	 */
	public function test_legacy_buffer_skips_progressive_flush_chunks(): void {
		$blocker = new ScriptBlocker();
		$html    = '<script src="https://www.google-analytics.com/analytics.js"></script>';
		$chunk   = $blocker->flush_legacy_output_buffer( $html, PHP_OUTPUT_HANDLER_FLUSH );

		$this->assertSame( $html, $chunk );

		$final = $blocker->flush_legacy_output_buffer( $html, PHP_OUTPUT_HANDLER_FINAL );
		$this->assertMatchesRegularExpression( '/type="text\/plain"/i', $final );
	}

	/**
	 * Modern WP template enhancement filter path uses process_output.
	 */
	public function test_template_enhancement_filter_path(): void {
		$blocker = new ScriptBlocker();
		$html    = '<script src="https://www.google-analytics.com/analytics.js"></script>';
		$out     = $blocker->process_output( $html, $html );

		$this->assertMatchesRegularExpression( '/type="text\/plain"/i', $out );
	}
}
