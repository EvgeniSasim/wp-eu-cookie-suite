<?php
/**
 * Plugin bootstrap.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite;

/**
 * Main plugin class.
 */
final class Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function boot(): void {
		register_activation_hook( WPEU_CS_FILE, array( $this, 'activate' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function activate(): void {
		if ( false === get_option( 'wpeu_cs_settings' ) ) {
			add_option(
				'wpeu_cs_settings',
				array(
					'blocker_enabled' => true,
					'eu_strict_mode'  => true,
					'version'         => WPEU_CS_VERSION,
				)
			);
		}
	}

	public function init(): void {
		load_plugin_textdomain( 'wp-eu-cookie-suite', false, dirname( plugin_basename( WPEU_CS_FILE ) ) . '/languages' );

		if ( is_admin() ) {
			add_action(
				'admin_notices',
				static function (): void {
					if ( ! current_user_can( 'manage_options' ) ) {
						return;
					}
					$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
					if ( $screen && 'plugins' === $screen->id ) {
						echo '<div class="notice notice-info"><p>';
						echo esc_html__( 'WP EU Cookie Suite: development scaffold — Jules tasks CC-01+ will implement full features.', 'wp-eu-cookie-suite' );
						echo '</p></div>';
					}
				}
			);
		}
	}
}
