<?php
/**
 * Plugin bootstrap.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
			define( 'WPEU_CS_VERSION', '1.2.0' );
		}
		if ( ! defined( 'WPEU_CS_FILE' ) ) {
			define( 'WPEU_CS_FILE', dirname( __DIR__ ) . '/privaro-cookie-consent-banner.php' );
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
		$this->deactivate_legacy_plugins();
		$this->create_tables();

		if ( false === get_option( 'wpeu_cs_ip_hash_secret' ) ) {
			update_option( 'wpeu_cs_ip_hash_secret', wp_generate_password( 64, true, true ), false );
		}

		if ( false === get_option( 'wpeu_cs_settings' ) ) {
			$services = Frontend\ScriptRegistry::get_services();
			$enabled  = array();
			foreach ( array_keys( $services ) as $service_id ) {
				$enabled[ $service_id ] = true;
			}

			update_option(
				'wpeu_cs_settings',
				array(
					'blocker_enabled'         => true,
					'eu_mode'                 => true,
					'enabled_services'        => $enabled,
					'enabled_categories'      => array( 'preferences', 'statistics', 'marketing' ),
					'show_reject_all'         => true,
					'google_consent_mode'     => true,
					'consent_logging_enabled' => true,
					'consent_log_retention'   => 365,
					'consent_log_store_ip'    => false,
					'consent_revision'        => 0,
					'use_network_defaults'    => is_multisite(),
					'version'                 => WPEU_CS_VERSION,
				)
			);
		}
	}

	/**
	 * Deactivate legacy plugin slugs from before the rename.
	 */
	private function deactivate_legacy_plugins(): void {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$legacy = array(
			'wp-eu-cookie-suite/wp-eu-cookie-suite.php',
			'eu-cookie-consent-suite/eu-cookie-consent-suite.php',
		);

		foreach ( $legacy as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				deactivate_plugins( $plugin, true );
			}
		}
	}

	/**
	 * Create database tables.
	 */
	private function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$cookies_table = $wpdb->prefix . 'wpeu_cookies';
		$sql_cookies   = "CREATE TABLE $cookies_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			domain varchar(255) NOT NULL,
			category varchar(50) NOT NULL,
			description text,
			duration varchar(100) DEFAULT '',
			service varchar(100) DEFAULT '',
			detected_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			source varchar(255) DEFAULT '',
			PRIMARY KEY  (id),
			KEY name (name),
			KEY category (category)
		) $charset_collate;";

		dbDelta( $sql_cookies );

		$log_table = $wpdb->prefix . 'wpeu_consent_log';
		$sql_log   = "CREATE TABLE $log_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			consent_uuid varchar(36) NOT NULL,
			event_type varchar(32) NOT NULL,
			categories text NOT NULL,
			consent_mode varchar(16) NOT NULL,
			page_url varchar(512) NOT NULL,
			locale varchar(10) NOT NULL,
			banner_revision int(10) unsigned DEFAULT 0 NOT NULL,
			plugin_version varchar(20) NOT NULL,
			ip_hash varchar(64) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL,
			wp_user_id bigint(20) DEFAULT NULL,
			config_snapshot text DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY consent_uuid (consent_uuid),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql_log );
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
		$this->create_tables();

		new Consent\WpConsentBridge();
		new Consent\GoogleConsentMode();

		$integrations = apply_filters(
			'wpeu_cs_integrations',
			array(
				Integrations\GoogleAnalyticsGuard::class,
				Integrations\GoogleSiteKit::class,
				Integrations\ThemeAnalytics::class,
				Integrations\IframePlaceholder::class,
				Integrations\ContactForm7::class,
			)
		);

		foreach ( $integrations as $integration ) {
			if ( class_exists( $integration ) ) {
				new $integration();
			}
		}

		if ( is_admin() ) {
			new Admin\Admin();
			new Scanner\Scanner();
		} else {
			new Frontend\Banner();
			new Frontend\ScriptBlocker();
		}

		new Frontend\Shortcodes();
	}
}
