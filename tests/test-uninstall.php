<?php
/**
 * Uninstall tests.
 *
 * @package WPEU\CookieSuite
 */

/**
 * Uninstall test case.
 */
class Test_Uninstall extends WP_UnitTestCase {

	/**
	 * Test uninstall process.
	 */
	public function test_uninstall() {
		global $wpdb;

		// Set up data.
		update_option( 'wpeu_cs_settings', array( 'keep_data_on_uninstall' => false ) );
		update_option( 'wpeu_cs_scan_results', array( 'test' ) );
		update_option( 'wpeu_cs_last_scan_time', time() );
		update_option( 'wpeu_cs_ip_hash_secret', 'secret123' );

		// Create tables.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpeu_cookies (id INT)" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpeu_consent_log (id INT)" );

		// Define uninstall constant.
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'privaro-cookie-consent-banner/privaro-cookie-consent-banner.php' );
		}

		// Run uninstall.
		include dirname( __DIR__ ) . '/privaro-cookie-consent-banner/uninstall.php';

		// Verify data is removed.
		$this->assertFalse( get_option( 'wpeu_cs_settings' ) );
		$this->assertFalse( get_option( 'wpeu_cs_scan_results' ) );
		$this->assertFalse( get_option( 'wpeu_cs_last_scan_time' ) );
		$this->assertFalse( get_option( 'wpeu_cs_ip_hash_secret' ) );

		// Verify tables are dropped.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->assertNull( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpeu_cookies'" ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->assertNull( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpeu_consent_log'" ) );
	}

	/**
	 * Test uninstall process keeps data when setting is true.
	 */
	public function test_uninstall_keep_data(): void {
		global $wpdb;

		// Set up data.
		update_option( 'wpeu_cs_settings', array( 'keep_data_on_uninstall' => true ) );
		update_option( 'wpeu_cs_ip_hash_secret', 'secret456' );

		// Create tables.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpeu_cookies (id INT)" );

		// Define uninstall constant if not defined (it might be from previous test).
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'privaro-cookie-consent-banner/privaro-cookie-consent-banner.php' );
		}

		// Run uninstall.
		include dirname( __DIR__ ) . '/privaro-cookie-consent-banner/uninstall.php';

		// Verify data is KEPT.
		$this->assertNotEmpty( get_option( 'wpeu_cs_settings' ) );
		$this->assertSame( 'secret456', get_option( 'wpeu_cs_ip_hash_secret' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->assertNotNull( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpeu_cookies'" ) );
	}
}
