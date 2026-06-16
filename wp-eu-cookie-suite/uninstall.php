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

delete_option( 'wpeu_cs_settings' );
