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
}
