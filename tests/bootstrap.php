<?php
/**
 * PHPUnit bootstrap.
 *
 * @package WPEU\CookieSuite
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php\n";
	echo "Run: bin/install-wp-tests.sh <db-name> <db-user> <db-pass>\n";
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin for tests.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/wp-eu-cookie-suite/wp-eu-cookie-suite.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
