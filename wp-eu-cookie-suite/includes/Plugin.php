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

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Boot the plugin.
	 */
	public function boot(): void {
		$this->define_constants();

		register_activation_hook( WPEU_CS_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( WPEU_CS_FILE, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants(): void {
		if ( ! defined( 'WPEU_CS_VERSION' ) ) {
			define( 'WPEU_CS_VERSION', '0.1.0' );
		}
		if ( ! defined( 'WPEU_CS_FILE' ) ) {
			define( 'WPEU_CS_FILE', dirname( __DIR__ ) . '/wp-eu-cookie-suite.php' );
		}
		if ( ! defined( 'WPEU_CS_PATH' ) ) {
			define( 'WPEU_CS_PATH', plugin_dir_path( WPEU_CS_FILE ) );
		}
		if ( ! defined( 'WPEU_CS_URL' ) ) {
			define( 'WPEU_CS_URL', plugin_dir_url( WPEU_CS_FILE ) );
		}
	}

	/**
	 * Activation hook.
	 */
	public function activate(): void {
		if ( false === get_option( 'wpeu_cs_settings' ) ) {
			$services = Frontend\ScriptRegistry::get_services();
			$enabled  = array();
			foreach ( array_keys( $services ) as $service_id ) {
				$enabled[ $service_id ] = true;
			}

			update_option(
				'wpeu_cs_settings',
				array(
					'blocker_enabled'    => true,
					'eu_mode'            => true,
					'enabled_services'   => $enabled,
					'enabled_categories' => array( 'preferences', 'statistics', 'marketing' ),
					'show_reject_all'    => true,
					'version'            => WPEU_CS_VERSION,
				)
			);
		}
	}

	/**
	 * Deactivation hook.
	 */
	public function deactivate(): void {
		// Placeholder for deactivation logic if needed in future.
	}

	/**
	 * Initialize the plugin.
	 */
	public function init(): void {
		load_plugin_textdomain( 'wp-eu-cookie-suite', false, dirname( plugin_basename( WPEU_CS_FILE ) ) . '/languages' );

		if ( is_admin() ) {
			new Admin\Admin();
		} else {
			new Frontend\Banner();
			new Frontend\ScriptBlocker();
		}
	}
}
