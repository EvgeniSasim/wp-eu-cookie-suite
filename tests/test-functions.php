<?php
/**
 * Helper function tests.
 *
 * @package WPEU\CookieSuite
 */

/**
 * Tests for includes/functions.php helpers.
 */
class Test_Functions extends WP_UnitTestCase {

	/**
	 * IP hash should be deterministic for the same IP and site secret.
	 */
	public function test_hash_ip_is_deterministic(): void {
		delete_option( 'wpeu_cs_ip_hash_secret' );

		$hash_a = wpeu_cs_hash_ip( '203.0.113.10' );
		$hash_b = wpeu_cs_hash_ip( '203.0.113.10' );

		$this->assertSame( $hash_a, $hash_b );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $hash_a );
	}

	/**
	 * Different IPs should produce different hashes.
	 */
	public function test_hash_ip_differs_by_ip(): void {
		delete_option( 'wpeu_cs_ip_hash_secret' );

		$hash_a = wpeu_cs_hash_ip( '203.0.113.10' );
		$hash_b = wpeu_cs_hash_ip( '203.0.113.11' );

		$this->assertNotSame( $hash_a, $hash_b );
	}

	/**
	 * Hash secret is stored in a dedicated option (not auth salts).
	 */
	public function test_hash_ip_uses_dedicated_secret_option(): void {
		delete_option( 'wpeu_cs_ip_hash_secret' );

		wpeu_cs_hash_ip( '198.51.100.1' );

		$secret = get_option( 'wpeu_cs_ip_hash_secret' );
		$this->assertIsString( $secret );
		$this->assertNotEmpty( $secret );
	}

	/**
	 * Necessary category is always consented.
	 */
	public function test_user_has_consent_necessary_always_true(): void {
		$this->assertTrue( wpeu_cs_user_has_consent( 'necessary' ) );
	}

	/**
	 * Opt-in (EU): no cookie → optional categories denied.
	 */
	public function test_user_has_consent_opt_in_denies_without_cookie(): void {
		update_option( 'wpeu_cs_settings', array( 'eu_mode' => true ) );
		unset( $_COOKIE['wpeu_statistics'], $_COOKIE['wpeu_consent'] );

		$this->assertFalse( wpeu_cs_user_has_consent( 'statistics' ) );
	}

	/**
	 * Opt-out: no cookie → optional categories allowed.
	 */
	public function test_user_has_consent_opt_out_allows_without_cookie(): void {
		update_option( 'wpeu_cs_settings', array( 'eu_mode' => false ) );
		unset( $_COOKIE['wpeu_statistics'], $_COOKIE['wpeu_consent'], $_COOKIE['wpeu_cc'], $_COOKIE['wpeu_consent_rev'] );

		$this->assertTrue( wpeu_cs_user_has_consent( 'statistics' ) );
	}

	/**
	 * Opt-in: helper cookies without current revision do not unlock resources.
	 */
	public function test_user_has_consent_rejects_stale_revision(): void {
		update_option( 'wpeu_cs_settings', array( 'eu_mode' => true, 'consent_revision' => 2 ) );
		$_COOKIE['wpeu_cc']          = wp_json_encode( array( 'revision' => 1 ) );
		$_COOKIE['wpeu_consent_rev'] = '1';
		$_COOKIE['wpeu_statistics']  = '1';

		$this->assertFalse( wpeu_cs_user_has_consent( 'statistics' ) );
	}

	/**
	 * Opt-in: helper cookies alone (CookieConsent cookie missing) are ignored.
	 */
	public function test_user_has_consent_rejects_orphaned_helpers(): void {
		update_option( 'wpeu_cs_settings', array( 'eu_mode' => true, 'consent_revision' => 0 ) );
		unset( $_COOKIE['wpeu_cc'], $_COOKIE['wpeu_consent_rev'] );
		$_COOKIE['wpeu_statistics'] = '1';

		$this->assertFalse( wpeu_cs_user_has_consent( 'statistics' ) );
	}

	/**
	 * Opt-in: matching revision + CookieConsent cookie unlocks the category.
	 */
	public function test_user_has_consent_accepts_current_revision(): void {
		update_option( 'wpeu_cs_settings', array( 'eu_mode' => true, 'consent_revision' => 3 ) );
		$_COOKIE['wpeu_cc']          = wp_json_encode( array( 'revision' => 3 ) );
		$_COOKIE['wpeu_consent_rev'] = '3';
		$_COOKIE['wpeu_statistics']  = '1';

		$this->assertTrue( wpeu_cs_user_has_consent( 'statistics' ) );
	}

	/**
	 * Legacy cookies without revision marker are stale under opt-in.
	 */
	public function test_user_has_consent_rejects_legacy_cookies_without_revision(): void {
		update_option( 'wpeu_cs_settings', array( 'eu_mode' => true, 'consent_revision' => 0 ) );
		$_COOKIE['wpeu_cc']         = wp_json_encode( array( 'categories' => array( 'statistics' ) ) );
		$_COOKIE['wpeu_statistics'] = '1';
		unset( $_COOKIE['wpeu_consent_rev'] );

		$this->assertFalse( wpeu_cs_user_has_consent( 'statistics' ) );
	}
}
