<?php
/**
 * Uninstall file.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$wpeu_cs_settings = get_option( 'wpeu_cs_settings' );

if ( is_array( $wpeu_cs_settings ) && ! empty( $wpeu_cs_settings['keep_data_on_uninstall'] ) ) {
	return;
}

// Remove settings and scan results.
delete_option( 'wpeu_cs_settings' );
delete_option( 'wpeu_cs_scan_results' );
delete_option( 'wpeu_cs_last_scan_time' );
delete_option( 'wpeu_cs_last_log_cleanup' );
delete_option( 'wpeu_cs_ip_hash_secret' );

if ( is_multisite() && is_main_site() ) {
	delete_site_option( 'wpeu_cs_network_settings' );
}

global $wpdb;

// Drop custom tables.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpeu_cookies" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpeu_consent_log" );
