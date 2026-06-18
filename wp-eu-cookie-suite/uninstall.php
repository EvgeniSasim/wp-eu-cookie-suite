<?php
/**
 * Uninstall file.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'wpeu_cs_settings' );

if ( is_array( $settings ) && ! empty( $settings['keep_data_on_uninstall'] ) ) {
	return;
}

// Remove settings and scan results.
delete_option( 'wpeu_cs_settings' );
delete_option( 'wpeu_cs_scan_results' );
delete_option( 'wpeu_cs_last_scan_time' );

global $wpdb;

// Drop custom tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpeu_cookies" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpeu_consent_log" );
