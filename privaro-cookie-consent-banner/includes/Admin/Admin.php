<?php
/**
 * Admin UI and settings management.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use WPEU\CookieSuite\Consent\Categories;
use WPEU\CookieSuite\Consent\BannerTexts;
use WPEU\CookieSuite\Frontend\ScriptRegistry;
use WPEU\CookieSuite\Admin\SettingsTransfer;
use WPEU\CookieSuite\Settings\SettingsRepository;

/**
 * Admin class.
 */
final class Admin {

	/**
	 * Settings page slug.
	 */
	const PAGE_SLUG = 'wpeu-cs-settings';

	/**
	 * Network settings page slug.
	 */
	const NETWORK_PAGE_SLUG = 'wpeu-cs-network-settings';

	/**
	 * Admin settings context: site or network.
	 *
	 * @var string
	 */
	private string $settings_context = 'site';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_init', array( $this, 'maybe_cleanup_logs' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_wpeu_cs_preview', array( $this, 'ajax_preview' ) );
		add_action( 'wp_ajax_wpeu_cs_log_consent', array( $this, 'ajax_log_consent' ) );
		add_action( 'wp_ajax_nopriv_wpeu_cs_log_consent', array( $this, 'ajax_log_consent' ) );

		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'add_network_menu_page' ) );
			add_action( 'network_admin_edit_wpeu_cs_update_network_settings', array( $this, 'save_network_settings' ) );
		}
	}

	/**
	 * Handle admin actions (CRUD, export, etc).
	 */
	public function handle_actions(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) ) : '';
		if ( ! $action ) {
			return;
		}

		$repository = new \WPEU\CookieSuite\Scanner\CookieRepository();

		switch ( $action ) {
			case 'wpeu_cs_save_cookie':
				check_admin_referer( 'wpeu_cs_save_cookie', 'wpeu_cs_cookie_nonce' );
				$cookie_data = isset( $_POST['cookie'] ) ? wp_unslash( $_POST['cookie'] ) : array();
				if ( is_array( $cookie_data ) && ! empty( $cookie_data['name'] ) ) {
					$repository->upsert( array_map( 'sanitize_text_field', $cookie_data ) );
					wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=cookies&message=saved' ) );
					exit;
				}
				break;

			case 'delete':
				$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
				check_admin_referer( 'wpeu_cs_delete_cookie_' . $id );
				$repository->delete( $id );
				wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=cookies&message=deleted' ) );
				exit;

			case 'wpeu_cs_export_csv':
				check_admin_referer( 'wpeu_cs_export_csv', 'wpeu_cs_export_nonce' );
				$this->handle_csv_export();
				break;

			case 'wpeu_cs_export_json':
				check_admin_referer( 'wpeu_cs_export_json', 'wpeu_cs_export_json_nonce' );
				$this->handle_json_export();
				break;

			case 'wpeu_cs_import_json':
				check_admin_referer( 'wpeu_cs_import_json', 'wpeu_cs_import_json_nonce' );
				$this->handle_json_import();
				break;

			case 'wpeu_cs_add_language':
				check_admin_referer( 'wpeu_cs_add_language', 'wpeu_cs_add_lang_nonce' );
				$this->handle_add_language();
				break;

			case 'wpeu_cs_remove_language':
				$code = sanitize_key( wp_unslash( (string) ( $_GET['lang'] ?? '' ) ) );
				check_admin_referer( 'wpeu_cs_remove_language_' . $code );
				$this->handle_remove_language( $code );
				break;

			case 'wpeu_cs_bump_consent_revision':
				check_admin_referer( 'wpeu_cs_bump_consent_revision', 'wpeu_cs_bump_revision_nonce' );
				$this->handle_bump_consent_revision();
				break;

			case 'wpeu_cs_add_category':
				check_admin_referer( 'wpeu_cs_add_category', 'wpeu_cs_add_category_nonce' );
				$this->handle_add_category();
				break;

			case 'wpeu_cs_remove_category':
				$slug = sanitize_key( wp_unslash( (string) ( $_GET['category'] ?? '' ) ) );
				check_admin_referer( 'wpeu_cs_remove_category_' . $slug );
				$this->handle_remove_category( $slug );
				break;

			case 'wpeu_cs_export_logs_csv':
				check_admin_referer( 'wpeu_cs_export_logs_csv' );
				$this->handle_logs_csv_export();
				break;

			case 'wpeu_cs_download_snapshot':
				$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
				check_admin_referer( 'wpeu_cs_download_snapshot_' . $id );
				$this->handle_download_snapshot( $id );
				break;
		}
	}

	/**
	 * Handle adding a new language.
	 */
	private function handle_add_language(): void {
		if ( is_multisite() && SettingsRepository::instance()->is_using_network_defaults() ) {
			wp_die( esc_html__( 'Cannot modify languages while using network defaults.', 'privaro-cookie-consent-banner' ) );
		}

		$code  = sanitize_key( wp_unslash( (string) ( $_POST['wpeu_cs_new_lang_code'] ?? '' ) ) );
		$label = sanitize_text_field( wp_unslash( (string) ( $_POST['wpeu_cs_new_lang_label'] ?? '' ) ) );

		if ( strlen( $code ) < 2 || strlen( $code ) > 5 ) {
			$tab = sanitize_key( (string) ( $_POST['active_tab'] ?? 'banner' ) );
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab . '&message=invalid_code' ) );
			exit;
		}

		$settings = get_option( 'wpeu_cs_settings', array() );
		if ( ! isset( $settings['language_labels'] ) ) {
			$settings['language_labels'] = array();
		}

		$settings['language_labels'][ $code ] = $label ?: strtoupper( $code );

		// Prefill banner texts and policy texts from English defaults if not exists
		if ( ! isset( $settings['banner_texts'][ $code ] ) ) {
			$settings['banner_texts'][ $code ] = BannerTexts::get_defaults( 'en' );
		}
		if ( ! isset( $settings['policy_texts'][ $code ] ) ) {
			$settings['policy_texts'][ $code ] = array(
				'intro'    => '',
				'template' => BannerTexts::get_default_policy_template( 'en' ),
			);
		}

		update_option( 'wpeu_cs_settings', $settings );

		$tab = sanitize_key( (string) ( $_POST['active_tab'] ?? 'banner' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab . '&lang=' . $code . '&message=lang_added' ) );
		exit;
	}

	/**
	 * Handle removing a language.
	 *
	 * @param string $code Language code.
	 */
	private function handle_remove_language( string $code ): void {
		if ( is_multisite() && SettingsRepository::instance()->is_using_network_defaults() ) {
			wp_die( esc_html__( 'Cannot modify languages while using network defaults.', 'privaro-cookie-consent-banner' ) );
		}

		$settings = get_option( 'wpeu_cs_settings', array() );

		unset( $settings['language_labels'][ $code ] );
		unset( $settings['banner_texts'][ $code ] );
		unset( $settings['policy_texts'][ $code ] );

		update_option( 'wpeu_cs_settings', $settings );

		$tab = sanitize_key( wp_unslash( (string) ( $_GET['tab'] ?? 'banner' ) ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab . '&message=lang_removed' ) );
		exit;
	}

	/**
	 * Handle adding a custom consent category.
	 */
	private function handle_add_category(): void {
		if ( is_multisite() && SettingsRepository::instance()->is_using_network_defaults() ) {
			wp_die( esc_html__( 'Cannot modify categories while using network defaults.', 'privaro-cookie-consent-banner' ) );
		}

		$slug            = sanitize_key( wp_unslash( (string) ( $_POST['wpeu_cs_new_category_slug'] ?? '' ) ) );
		$label           = sanitize_text_field( wp_unslash( (string) ( $_POST['wpeu_cs_new_category_label'] ?? '' ) ) );
		$description     = sanitize_textarea_field( wp_unslash( (string) ( $_POST['wpeu_cs_new_category_description'] ?? '' ) ) );
		$integration_map = sanitize_key( wp_unslash( (string) ( $_POST['wpeu_cs_new_category_integration_map'] ?? Categories::MARKETING ) ) );

		$redirect_base = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=banner' );

		if ( ! Categories::is_valid_slug( $slug ) ) {
			wp_safe_redirect( $redirect_base . '&message=invalid_category_slug' );
			exit;
		}

		if ( ! Categories::is_valid_integration_map( $integration_map ) ) {
			wp_safe_redirect( $redirect_base . '&message=invalid_integration_map' );
			exit;
		}

		$settings = get_option( 'wpeu_cs_settings', array() );
		if ( ! isset( $settings['custom_categories'] ) || ! is_array( $settings['custom_categories'] ) ) {
			$settings['custom_categories'] = array();
		}

		if ( isset( $settings['custom_categories'][ $slug ] ) ) {
			wp_safe_redirect( $redirect_base . '&message=category_exists' );
			exit;
		}

		$settings['custom_categories'][ $slug ] = array(
			'label'           => $label ?: $slug,
			'description'     => $description,
			'integration_map' => $integration_map,
		);

		$locales = BannerTexts::get_locales();
		if ( ! isset( $settings['banner_texts'] ) || ! is_array( $settings['banner_texts'] ) ) {
			$settings['banner_texts'] = array();
		}
		foreach ( array_keys( $locales ) as $locale ) {
			if ( ! isset( $settings['banner_texts'][ $locale ] ) || ! is_array( $settings['banner_texts'][ $locale ] ) ) {
				$settings['banner_texts'][ $locale ] = array();
			}
			$settings['banner_texts'][ $locale ][ $slug . '_label' ]       = $label ?: $slug;
			$settings['banner_texts'][ $locale ][ $slug . '_description' ] = $description;
		}

		update_option( 'wpeu_cs_settings', $settings );

		wp_safe_redirect( $redirect_base . '&message=category_added' );
		exit;
	}

	/**
	 * Handle removing a custom consent category.
	 *
	 * @param string $slug Category slug.
	 */
	private function handle_remove_category( string $slug ): void {
		if ( is_multisite() && SettingsRepository::instance()->is_using_network_defaults() ) {
			wp_die( esc_html__( 'Cannot modify categories while using network defaults.', 'privaro-cookie-consent-banner' ) );
		}

		if ( ! Categories::is_valid_slug( $slug ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=banner&message=invalid_category_slug' ) );
			exit;
		}

		$settings = get_option( 'wpeu_cs_settings', array() );
		unset( $settings['custom_categories'][ $slug ] );

		if ( isset( $settings['enabled_categories'] ) && is_array( $settings['enabled_categories'] ) ) {
			$settings['enabled_categories'] = array_values(
				array_filter(
					$settings['enabled_categories'],
					static function ( $item ) use ( $slug ) {
						return $slug !== $item;
					}
				)
			);
		}

		if ( isset( $settings['banner_texts'] ) && is_array( $settings['banner_texts'] ) ) {
			foreach ( $settings['banner_texts'] as $locale => $texts ) {
				if ( ! is_array( $texts ) ) {
					continue;
				}
				unset( $settings['banner_texts'][ $locale ][ $slug . '_label' ], $settings['banner_texts'][ $locale ][ $slug . '_description' ] );
			}
		}

		update_option( 'wpeu_cs_settings', $settings );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=banner&message=category_removed' ) );
		exit;
	}

	/**
	 * Handle CSV export.
	 */
	private function handle_csv_export(): void {
		$repository = new \WPEU\CookieSuite\Scanner\CookieRepository();
		$cookies    = $repository->all();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wpeu-cookies-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv( $output, array( 'Name', 'Domain', 'Category', 'Service', 'Duration', 'Description' ) );

		foreach ( $cookies as $cookie ) {
			fputcsv(
				$output,
				array(
					$cookie['name'],
					$cookie['domain'],
					$cookie['category'],
					$cookie['service'],
					$cookie['duration'],
					$cookie['description'],
				)
			);
		}

		exit;
	}

	/**
	 * Handle JSON settings export.
	 */
	private function handle_json_export(): void {
		$payload = SettingsTransfer::export();
		$json    = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			wp_die( esc_html__( 'Could not encode export data.', 'privaro-cookie-consent-banner' ) );
		}

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=privaro-cookie-consent-banner-' . gmdate( 'Y-m-d' ) . '.json' );
		header( 'Content-Length: ' . strlen( $json ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
		echo $json;
		exit;
	}

	/**
	 * Handle CSV export of consent logs.
	 */
	private function handle_logs_csv_export(): void {
		$logger = new \WPEU\CookieSuite\Consent\ConsentLogger();
		$args   = array(
			'event_type' => isset( $_GET['event_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['event_type'] ) ) : '',
			'search'     => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '',
			'start_date' => isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['start_date'] ) ) : '',
			'end_date'   => isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['end_date'] ) ) : '',
			'per_page'   => 5000,
		);
		$logs   = $logger->get_logs( $args );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wpeu-consent-logs-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv( $output, array( 'Date', 'Visitor UUID', 'Event', 'Categories', 'Mode', 'Locale', 'Revision', 'Page URL', 'IP Hash', 'User Agent' ) );

		foreach ( $logs as $log ) {
			fputcsv(
				$output,
				array(
					$log['created_at'],
					$log['consent_uuid'],
					$log['event_type'],
					$log['categories'],
					$log['consent_mode'],
					$log['locale'],
					$log['banner_revision'],
					$log['page_url'],
					$log['ip_hash'],
					$log['user_agent'],
				)
			);
		}

		exit;
	}

	/**
	 * Handle individual log snapshot download.
	 *
	 * @param int $id Log ID.
	 */
	private function handle_download_snapshot( int $id ): void {
		global $wpdb;
		$table = \WPEU\CookieSuite\Consent\ConsentLogger::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table name.
		$log   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );

		if ( ! $log || empty( $log['config_snapshot'] ) ) {
			wp_die( esc_html__( 'Snapshot not found.', 'privaro-cookie-consent-banner' ) );
		}

		$filename = 'wpeu-consent-snapshot-' . $log['consent_uuid'] . '-' . gmdate( 'Ymd-His', strtotime( $log['created_at'] ) ) . '.json';
		$data     = json_decode( $log['config_snapshot'], true );

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Handle JSON settings import.
	 */
	private function handle_json_import(): void {
		if ( empty( $_FILES['wpeu_cs_import_file']['tmp_name'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools&message=import_error' ) );
			exit;
		}

		$raw = file_get_contents( wp_unslash( $_FILES['wpeu_cs_import_file']['tmp_name'] ) );
		if ( false === $raw ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools&message=import_error' ) );
			exit;
		}

		$data = json_decode( $raw, true );
		$valid = SettingsTransfer::validate( $data );
		if ( is_wp_error( $valid ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools&message=import_invalid' ) );
			exit;
		}

		$sanitized = SettingsTransfer::sanitize_imported_settings( $data['settings'] );
		update_option( 'wpeu_cs_settings', $sanitized );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools&message=imported' ) );
		exit;
	}

	/**
	 * Add menu page.
	 */
	public function add_menu_page(): void {
		add_menu_page(
			__( 'Privaro Cookie Consent Banner', 'privaro-cookie-consent-banner' ),
			__( 'Privaro Cookie Consent Banner', 'privaro-cookie-consent-banner' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-shield'
		);
	}

	/**
	 * Add network admin settings page.
	 */
	public function add_network_menu_page(): void {
		add_submenu_page(
			'settings.php',
			__( 'Privaro Cookie Consent Banner', 'privaro-cookie-consent-banner' ),
			__( 'Privaro Cookie Consent Banner', 'privaro-cookie-consent-banner' ),
			'manage_network_options',
			self::NETWORK_PAGE_SLUG,
			array( $this, 'render_network_settings_page' )
		);
	}

	/**
	 * Save network-wide default settings.
	 */
	public function save_network_settings(): void {
		check_admin_referer( 'wpeu_cs_network_settings' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage network options.', 'privaro-cookie-consent-banner' ) );
		}

		$input = isset( $_POST['wpeu_cs_settings'] ) ? wp_unslash( $_POST['wpeu_cs_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$old       = get_site_option( SettingsRepository::NETWORK_OPTION, array() );
		$old       = is_array( $old ) ? $old : array();
		$sanitized = $this->sanitize_settings_payload( $input, $old );

		update_site_option( SettingsRepository::NETWORK_OPTION, $sanitized );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::NETWORK_PAGE_SLUG,
					'updated' => 'true',
					'tab'     => sanitize_key( (string) ( $input['active_tab'] ?? 'banner' ) ),
				),
				network_admin_url( 'settings.php' )
			)
		);
		exit;
	}

	/**
	 * Register settings.
	 */
	public function register_settings(): void {
		register_setting(
			'wpeu_cs_settings',
			'wpeu_cs_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( array $input ): array {
		$old_settings = get_option( 'wpeu_cs_settings', array() );
		$old_settings = is_array( $old_settings ) ? $old_settings : array();

		if ( is_multisite() ) {
			$sanitized = $old_settings;

			if ( isset( $input['use_network_defaults'] ) ) {
				$sanitized['use_network_defaults'] = ! empty( $input['use_network_defaults'] );
			} elseif ( isset( $_POST['wpeu_cs_use_network_defaults_present'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$sanitized['use_network_defaults'] = false;
			}

			if ( ! empty( $sanitized['use_network_defaults'] ) ) {
				return $sanitized;
			}
		}

		return $this->sanitize_settings_payload( $input, $old_settings );
	}

	/**
	 * Sanitize settings payload (site local or network defaults).
	 *
	 * @param array $input        Submitted settings.
	 * @param array $old_settings Previous stored values.
	 * @return array
	 */
	private function sanitize_settings_payload( array $input, array $old_settings ): array {
		$sanitized = $old_settings;

		$active_tab = $input['active_tab'] ?? '';

		if ( 'banner' === $active_tab ) {
			$sanitized['enabled_categories'] = array();
			if ( isset( $input['enabled_categories'] ) && is_array( $input['enabled_categories'] ) ) {
				$valid_slugs = array_keys( Categories::get_all() );
				$sanitized['enabled_categories'] = array_values(
					array_intersect(
						array_map( 'sanitize_text_field', $input['enabled_categories'] ),
						$valid_slugs
					)
				);
			}

			$sanitized['privacy_policy_url'] = isset( $input['privacy_policy_url'] ) ? esc_url_raw( $input['privacy_policy_url'] ) : '';
			$sanitized['cookie_policy_url']  = isset( $input['cookie_policy_url'] ) ? esc_url_raw( $input['cookie_policy_url'] ) : '';
			$sanitized['show_reject_all']    = isset( $input['show_reject_all'] );
			$sanitized['eu_mode']            = isset( $input['eu_mode'] );

			if ( isset( $input['banner_texts'] ) && is_array( $input['banner_texts'] ) ) {
				if ( ! isset( $sanitized['banner_texts'] ) ) {
					$sanitized['banner_texts'] = array();
				}
				foreach ( $input['banner_texts'] as $locale => $texts ) {
					if ( ! is_array( $texts ) ) {
						continue;
					}
					$sanitized['banner_texts'][ sanitize_key( $locale ) ] = array_map( 'sanitize_text_field', $texts );
				}
			}

			if ( isset( $input['banner_ui'] ) && is_array( $input['banner_ui'] ) ) {
				$sanitized['banner_ui'] = array(
					'layout'        => sanitize_text_field( $input['banner_ui']['layout'] ?? 'box' ),
					'position'      => sanitize_text_field( $input['banner_ui']['position'] ?? 'bottom-right' ),
					'theme'         => sanitize_text_field( $input['banner_ui']['theme'] ?? 'light' ),
					'primary_color' => sanitize_hex_color( $input['banner_ui']['primary_color'] ?? '#30363c' ) ?: '#30363c',
				);
			}
		} elseif ( 'integrations' === $active_tab ) {
			$sanitized['blocker_enabled']     = isset( $input['blocker_enabled'] );
			$sanitized['google_consent_mode'] = isset( $input['google_consent_mode'] );

			$sanitized['enabled_services'] = array();
			if ( isset( $input['enabled_services'] ) && is_array( $input['enabled_services'] ) ) {
				$sanitized['enabled_services'] = array_map(
					function ( $val ) {
						return (bool) $val;
					},
					$input['enabled_services']
				);
			}

			$sanitized['enabled_integrations'] = array();
			if ( isset( $input['enabled_integrations'] ) && is_array( $input['enabled_integrations'] ) ) {
				$sanitized['enabled_integrations'] = array_map(
					function ( $val ) {
						return (bool) $val;
					},
					$input['enabled_integrations']
				);
			}

			$sanitized['theme_analytics_field'] = isset( $input['theme_analytics_field'] ) ? sanitize_text_field( $input['theme_analytics_field'] ) : 'analytics';

			$sanitized['custom_block_rules'] = isset( $input['custom_block_rules'] ) ? sanitize_textarea_field( $input['custom_block_rules'] ) : '';

			$sanitized['reload_on_revoke'] = isset( $input['reload_on_revoke'] );
		} elseif ( 'tools' === $active_tab ) {
			$sanitized['consent_logging_enabled'] = isset( $input['consent_logging_enabled'] );
			$sanitized['consent_log_retention']   = isset( $input['consent_log_retention'] ) ? max( 1, (int) $input['consent_log_retention'] ) : 365;
			$sanitized['consent_log_store_ip']    = isset( $input['consent_log_store_ip'] );

			if ( isset( $input['policy_texts'] ) && is_array( $input['policy_texts'] ) ) {
				if ( ! isset( $sanitized['policy_texts'] ) ) {
					$sanitized['policy_texts'] = array();
				}
				foreach ( $input['policy_texts'] as $locale => $texts ) {
					if ( ! is_array( $texts ) ) {
						continue;
					}
					$sanitized['policy_texts'][ sanitize_key( $locale ) ] = array(
						'intro'    => sanitize_textarea_field( $texts['intro'] ?? '' ),
						'template' => wp_kses_post( $texts['template'] ?? '' ),
					);
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Enqueue assets.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		$allowed_hooks = array(
			'toplevel_page_' . self::PAGE_SLUG,
			'settings_page_' . self::NETWORK_PAGE_SLUG,
		);
		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style(
			'wpeu-cs-admin',
			WPEU_CS_URL . 'assets/css/admin.css',
			array( 'wp-color-picker' ),
			WPEU_CS_VERSION
		);

		wp_enqueue_script(
			'wpeu-cs-admin',
			WPEU_CS_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			WPEU_CS_VERSION,
			true
		);
	}

	/**
	 * Render site settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->settings_context = 'site';
		$this->render_admin_settings_page();
	}

	/**
	 * Render network default settings page.
	 */
	public function render_network_settings_page(): void {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}

		$this->settings_context = 'network';
		$this->render_admin_settings_page();
	}

	/**
	 * Shared settings UI for site and network contexts.
	 */
	private function render_admin_settings_page(): void {
		$is_network = 'network' === $this->settings_context;

		$tabs = $is_network
			? array(
				'banner'       => __( 'Banner', 'privaro-cookie-consent-banner' ),
				'integrations' => __( 'Integrations', 'privaro-cookie-consent-banner' ),
				'tools'        => __( 'Tools', 'privaro-cookie-consent-banner' ),
			)
			: array(
				'dashboard'    => __( 'Dashboard', 'privaro-cookie-consent-banner' ),
				'banner'       => __( 'Banner', 'privaro-cookie-consent-banner' ),
				'cookies'      => __( 'Cookies', 'privaro-cookie-consent-banner' ),
				'scanner'      => __( 'Scanner', 'privaro-cookie-consent-banner' ),
				'consent_log'  => __( 'Consent Log', 'privaro-cookie-consent-banner' ),
				'integrations' => __( 'Integrations', 'privaro-cookie-consent-banner' ),
				'tools'        => __( 'Tools', 'privaro-cookie-consent-banner' ),
			);

		$active_tab_input = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : '';
		$active_tab       = array_key_exists( $active_tab_input, $tabs ) ? $active_tab_input : ( $is_network ? 'banner' : 'dashboard' );

		$base_url = $is_network
			? network_admin_url( 'settings.php?page=' . self::NETWORK_PAGE_SLUG )
			: admin_url( 'admin.php?page=' . self::PAGE_SLUG );

		$content_class = 'wpeu-cs-content';
		if ( $this->is_settings_readonly() && 'consent_log' !== $active_tab ) {
			$content_class .= ' wpeu-cs-inherited';
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php
			if ( $is_network && isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Network default settings saved.', 'privaro-cookie-consent-banner' ) . '</p></div>';
			}

			if ( ! $is_network && is_multisite() ) {
				$this->render_multisite_toggle();
			}
			?>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
					<a href="<?php echo esc_url( $base_url . '&tab=' . $tab_id ); ?>" class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="<?php echo esc_attr( $content_class ); ?>">
				<?php
				switch ( $active_tab ) {
					case 'dashboard':
						$this->render_dashboard_tab();
						break;
					case 'banner':
						$this->render_banner_tab();
						break;
					case 'cookies':
						$this->render_cookies_tab();
						break;
					case 'scanner':
						$this->render_scanner_tab();
						break;
					case 'integrations':
						$this->render_integrations_tab();
						break;
					case 'consent_log':
						$this->render_consent_log_tab();
						break;
					case 'tools':
						$this->render_tools_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render multisite inherit toggle (site admin only).
	 */
	private function render_multisite_toggle(): void {
		$using_network = SettingsRepository::instance()->is_using_network_defaults();
		?>
		<div class="wpeu-cs-network-toggle">
			<form id="wpeu-cs-multisite-form" method="post" action="options.php">
				<?php settings_fields( 'wpeu_cs_settings' ); ?>
				<input type="hidden" name="wpeu_cs_use_network_defaults_present" value="1">
				<input type="hidden" name="wpeu_cs_settings[active_tab]" value="dashboard">
				<label>
					<input type="checkbox" name="wpeu_cs_settings[use_network_defaults]" value="1" <?php checked( $using_network ); ?>>
					<?php esc_html_e( 'Use network defaults', 'privaro-cookie-consent-banner' ); ?>
				</label>
				<?php submit_button( __( 'Save inheritance setting', 'privaro-cookie-consent-banner' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php if ( $using_network ) : ?>
				<div class="notice notice-info inline">
					<p><?php esc_html_e( 'Banner and integration settings below are inherited from the network administrator. Uncheck the option above to configure this site individually.', 'privaro-cookie-consent-banner' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Settings array for admin forms (effective when inherited).
	 *
	 * @return array<string, mixed>
	 */
	private function get_admin_settings(): array {
		if ( 'network' === $this->settings_context ) {
			return SettingsRepository::instance()->get_network_settings();
		}

		$repository = SettingsRepository::instance();
		if ( $repository->is_using_network_defaults() ) {
			return $repository->get_effective_settings();
		}

		return $repository->get_local_settings();
	}

	/**
	 * Whether site-level configuration forms are read-only.
	 *
	 * @return bool
	 */
	private function is_settings_readonly(): bool {
		return 'site' === $this->settings_context && SettingsRepository::instance()->is_using_network_defaults();
	}

	/**
	 * Form action URL for settings tabs.
	 *
	 * @return string
	 */
	private function get_settings_form_action(): string {
		if ( 'network' === $this->settings_context ) {
			return network_admin_url( 'edit.php?action=wpeu_cs_update_network_settings' );
		}

		return 'options.php';
	}

	/**
	 * Output settings form opening fields (nonce / options group).
	 */
	private function render_settings_form_header(): void {
		if ( 'network' === $this->settings_context ) {
			wp_nonce_field( 'wpeu_cs_network_settings' );
			return;
		}

		settings_fields( 'wpeu_cs_settings' );
	}

	/**
	 * Render language selector.
	 *
	 * @param string $active_tab   Active tab.
	 * @param array  $locales      All locales.
	 * @param string $current_lang Current language.
	 */
	private function render_language_selector( string $active_tab, array $locales, string $current_lang ): void {
		?>
		<div class="wpeu-cs-lang-selector">
			<ul class="subsubsub">
				<?php
				$i = 0;
				foreach ( $locales as $code => $label ) :
					$url     = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $active_tab . '&lang=' . $code );
					$current = $current_lang === $code ? 'current' : '';
					$remove_url = wp_nonce_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $active_tab . '&action=wpeu_cs_remove_language&lang=' . $code ), 'wpeu_cs_remove_language_' . $code );

					echo '<li>';
					echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $current ) . '">' . esc_html( $label ) . '</a>';

					// Allow removal if not a core language and not currently detected site locale
					$is_core = in_array( $code, array( 'en', 'de' ), true );
					if ( ! $is_core ) {
						echo ' <a href="' . esc_url( $remove_url ) . '" class="wpeu-cs-remove-lang" onclick="return confirm(\'' . esc_js( __( 'Are you sure you want to remove this language? Settings for this language will be deleted.', 'privaro-cookie-consent-banner' ) ) . '\');" title="' . esc_attr__( 'Remove language', 'privaro-cookie-consent-banner' ) . '"><span class="dashicons dashicons-no-alt" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span></a>';
					}

					echo ( $i < count( $locales ) - 1 ? ' | ' : '' );
					echo '</li>';
					$i++;
				endforeach;
				?>
			</ul>
			<br class="clear">
		</div>

		<div class="wpeu-cs-add-lang-form card">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>">
				<?php wp_nonce_field( 'wpeu_cs_add_language', 'wpeu_cs_add_lang_nonce' ); ?>
				<input type="hidden" name="action" value="wpeu_cs_add_language">
				<input type="hidden" name="active_tab" value="<?php echo esc_attr( $active_tab ); ?>">

				<h4 style="margin-top: 0;"><?php esc_html_e( 'Add Language', 'privaro-cookie-consent-banner' ); ?></h4>
				<div style="display: flex; gap: 10px; align-items: flex-end;">
					<div>
						<label for="new_lang_code" style="display: block; font-size: 11px;"><?php esc_html_e( 'Code (e.g. fr)', 'privaro-cookie-consent-banner' ); ?></label>
						<input type="text" name="wpeu_cs_new_lang_code" id="new_lang_code" value="" class="small-text" required maxlength="5">
					</div>
					<div>
						<label for="new_lang_label" style="display: block; font-size: 11px;"><?php esc_html_e( 'Display Name', 'privaro-cookie-consent-banner' ); ?></label>
						<input type="text" name="wpeu_cs_new_lang_label" id="new_lang_label" value="" class="regular-text" required placeholder="Français">
					</div>
					<?php submit_button( __( 'Add', 'privaro-cookie-consent-banner' ), 'secondary', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render custom category management (Banner tab).
	 */
	private function render_categories_management(): void {
		$builtin         = Categories::get_builtin();
		$custom          = Categories::get_custom();
		$integration_opts = array(
			Categories::PREFERENCES => __( 'Preferences', 'privaro-cookie-consent-banner' ),
			Categories::STATISTICS  => __( 'Statistics', 'privaro-cookie-consent-banner' ),
			Categories::MARKETING   => __( 'Marketing', 'privaro-cookie-consent-banner' ),
		);
		?>
		<h3><?php esc_html_e( 'Categories', 'privaro-cookie-consent-banner' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Built-in categories are always available. Add up to 5 custom categories for site-specific consent groups.', 'privaro-cookie-consent-banner' ); ?></p>

		<table class="widefat striped" style="max-width: 900px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Slug', 'privaro-cookie-consent-banner' ); ?></th>
					<th><?php esc_html_e( 'Label', 'privaro-cookie-consent-banner' ); ?></th>
					<th><?php esc_html_e( 'Counts as', 'privaro-cookie-consent-banner' ); ?></th>
					<th><?php esc_html_e( 'Type', 'privaro-cookie-consent-banner' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $builtin as $slug => $category ) : ?>
					<tr>
						<td><code><?php echo esc_html( $slug ); ?></code></td>
						<td><?php echo esc_html( (string) $category['label'] ); ?></td>
						<td><?php echo esc_html( (string) ( $category['integration_map'] ?? $slug ) ); ?></td>
						<td><?php esc_html_e( 'Built-in', 'privaro-cookie-consent-banner' ); ?></td>
						<td></td>
					</tr>
				<?php endforeach; ?>
				<?php foreach ( $custom as $slug => $category ) : ?>
					<?php
					$remove_url = wp_nonce_url(
						admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=banner&action=wpeu_cs_remove_category&category=' . $slug ),
						'wpeu_cs_remove_category_' . $slug
					);
					?>
					<tr>
						<td><code><?php echo esc_html( $slug ); ?></code></td>
						<td><?php echo esc_html( $category['label'] ); ?></td>
						<td><?php echo esc_html( $category['integration_map'] ); ?></td>
						<td><?php esc_html_e( 'Custom', 'privaro-cookie-consent-banner' ); ?></td>
						<td>
							<a href="<?php echo esc_url( $remove_url ); ?>" class="submitdelete" onclick="return confirm('<?php echo esc_js( __( 'Remove this custom category?', 'privaro-cookie-consent-banner' ) ); ?>');"><?php esc_html_e( 'Remove', 'privaro-cookie-consent-banner' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>

				<tr>
					<th scope="row"><?php esc_html_e( 'Reload on revoke', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="wpeu_cs_settings[reload_on_revoke]" value="1" <?php checked( ! empty( $settings['reload_on_revoke'] ) ); ?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Reload the page immediately after consent is revoked.', 'privaro-cookie-consent-banner' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<div class="wpeu-cs-add-category-form card" style="margin-top: 12px; max-width: 900px;">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>">
				<?php wp_nonce_field( 'wpeu_cs_add_category', 'wpeu_cs_add_category_nonce' ); ?>
				<input type="hidden" name="action" value="wpeu_cs_add_category">
				<h4 style="margin-top: 0;"><?php esc_html_e( 'Add Custom Category', 'privaro-cookie-consent-banner' ); ?></h4>
				<table class="form-table" role="presentation" style="margin-top: 0;">
					<tr>
						<th scope="row"><label for="wpeu_cs_new_category_slug"><?php esc_html_e( 'Slug', 'privaro-cookie-consent-banner' ); ?></label></th>
						<td><input type="text" name="wpeu_cs_new_category_slug" id="wpeu_cs_new_category_slug" class="regular-text" required pattern="[a-z0-9_-]{2,32}" maxlength="32" placeholder="social"></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpeu_cs_new_category_label"><?php esc_html_e( 'Label', 'privaro-cookie-consent-banner' ); ?></label></th>
						<td><input type="text" name="wpeu_cs_new_category_label" id="wpeu_cs_new_category_label" class="regular-text" required placeholder="<?php esc_attr_e( 'Social Media', 'privaro-cookie-consent-banner' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpeu_cs_new_category_description"><?php esc_html_e( 'Description', 'privaro-cookie-consent-banner' ); ?></label></th>
						<td><textarea name="wpeu_cs_new_category_description" id="wpeu_cs_new_category_description" rows="2" class="large-text"></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpeu_cs_new_category_integration_map"><?php esc_html_e( 'Counts as (for blocking integrations)', 'privaro-cookie-consent-banner' ); ?></label></th>
						<td>
							<select name="wpeu_cs_new_category_integration_map" id="wpeu_cs_new_category_integration_map">
								<?php foreach ( $integration_opts as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Add Category', 'privaro-cookie-consent-banner' ), 'secondary' ); ?>
			</form>
		</div>
		<hr>
		<?php
	}

	/**
	 * Render banner tab.
	 */
	private function render_banner_tab(): void {
		$settings           = $this->get_admin_settings();
		$all_categories     = Categories::get_all();
		$enabled_categories = $settings['enabled_categories'] ?? array( 'preferences', 'statistics', 'marketing' );
		$privacy_url        = $settings['privacy_policy_url'] ?? '';
		$cookie_url         = $settings['cookie_policy_url'] ?? '';
		$show_reject_all    = $settings['show_reject_all'] ?? true;
		$eu_mode            = $settings['eu_mode'] ?? true;

		$banner_ui          = $settings['banner_ui'] ?? array();
		$layout             = $banner_ui['layout'] ?? 'box';
		$position           = $banner_ui['position'] ?? 'bottom-right';
		$theme              = $banner_ui['theme'] ?? 'light';
		$primary_color      = $banner_ui['primary_color'] ?? '#30363c';

		$locales        = BannerTexts::get_locales();
		$lang_input     = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( (string) $_GET['lang'] ) ) : '';
		$current_lang   = array_key_exists( $lang_input, $locales ) ? $lang_input : 'en';
		$texts          = BannerTexts::get_strings( $current_lang );

		$message = isset( $_GET['message'] ) ? sanitize_key( wp_unslash( (string) $_GET['message'] ) ) : '';
		if ( 'lang_added' === $message ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Language added successfully.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		} elseif ( 'lang_removed' === $message ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Language removed.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		} elseif ( 'invalid_code' === $message ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid language code. Use 2-5 characters.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		} elseif ( 'category_added' === $message ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Custom category added.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		} elseif ( 'category_removed' === $message ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Custom category removed.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		} elseif ( 'invalid_category_slug' === $message ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid category slug. Use 2-32 lowercase letters, numbers, hyphens or underscores. Built-in slugs are reserved.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		} elseif ( 'category_exists' === $message ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'A category with this slug already exists.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		} elseif ( 'invalid_integration_map' === $message ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid integration map value.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		}

		$this->render_language_selector( 'banner', $locales, $current_lang );
		$this->render_categories_management();
		?>

		<form method="post" action="<?php echo esc_url( $this->get_settings_form_action() ); ?>">
			<?php
			$this->render_settings_form_header();
			?>
			<input type="hidden" name="wpeu_cs_settings[active_tab]" value="banner">

			<h3>
			<?php
			/* translators: %s: language label */
			printf( esc_html__( 'Banner Settings (%s)', 'privaro-cookie-consent-banner' ), esc_html( $locales[ $current_lang ] ) );
			?>
			</h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled Categories', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<?php foreach ( $all_categories as $id => $category ) : ?>
							<?php
							if ( 'necessary' === $id ) {
								continue;
							}
							?>
							<label>
								<input type="checkbox" name="wpeu_cs_settings[enabled_categories][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $enabled_categories, true ) ); ?>>
								<?php echo esc_html( $category['label'] ); ?>
							</label><br>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Select which consent categories to display in the banner.', 'privaro-cookie-consent-banner' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Privacy Policy URL', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="url" name="wpeu_cs_settings[privacy_policy_url]" value="<?php echo esc_url( $privacy_url ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cookie Policy URL', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="url" name="wpeu_cs_settings[cookie_policy_url]" value="<?php echo esc_url( $cookie_url ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show "Reject All"', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpeu_cs_settings[show_reject_all]" value="1" <?php checked( $show_reject_all ); ?>>
							<?php esc_html_e( 'Show the "Reject All" button in the banner.', 'privaro-cookie-consent-banner' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Strict EU Mode', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpeu_cs_settings[eu_mode]" value="1" <?php checked( $eu_mode ); ?>>
							<?php esc_html_e( 'Enable strict EU mode (block all non-necessary cookies until consent).', 'privaro-cookie-consent-banner' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<hr>
			<h3><?php esc_html_e( 'Localized Texts', 'privaro-cookie-consent-banner' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Consent Modal Title', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][consent_modal_title]" value="<?php echo esc_attr( $texts['consent_modal_title'] ?? '' ); ?>" class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Consent Modal Description', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<textarea name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][consent_modal_description]" rows="3" class="large-text"><?php echo esc_textarea( $texts['consent_modal_description'] ?? '' ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Preferences Modal Title', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][preferences_modal_title]" value="<?php echo esc_attr( $texts['preferences_modal_title'] ?? '' ); ?>" class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Accept All Button', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][accept_all_btn]" value="<?php echo esc_attr( $texts['accept_all_btn'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Reject All Button', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][accept_necessary_btn]" value="<?php echo esc_attr( $texts['accept_necessary_btn'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Manage Preferences Button', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][show_preferences_btn]" value="<?php echo esc_attr( $texts['show_preferences_btn'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Save Preferences Button', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][save_preferences_btn]" value="<?php echo esc_attr( $texts['save_preferences_btn'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Revoke Consent Label', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][revoke_consent_label]" value="<?php echo esc_attr( $texts['revoke_consent_label'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Close Icon Label', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][close_icon_label]" value="<?php echo esc_attr( $texts['close_icon_label'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<?php foreach ( $all_categories as $id => $category ) : ?>
					<?php
					if ( 'necessary' !== $id && ! in_array( $id, $enabled_categories, true ) ) {
						continue;
					}
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $category['label'] ); ?> (<?php esc_html_e( 'Label', 'privaro-cookie-consent-banner' ); ?>)</th>
						<td>
							<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][<?php echo esc_attr( $id ); ?>_label]" value="<?php echo esc_attr( $texts[ $id . '_label' ] ?? '' ); ?>" class="large-text">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( $category['label'] ); ?> (<?php esc_html_e( 'Description', 'privaro-cookie-consent-banner' ); ?>)</th>
						<td>
							<textarea name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][<?php echo esc_attr( $id ); ?>_description]" rows="2" class="large-text"><?php echo esc_textarea( $texts[ $id . '_description' ] ?? '' ); ?></textarea>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<hr>
			<h3><?php esc_html_e( 'Appearance', 'privaro-cookie-consent-banner' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Layout', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<select name="wpeu_cs_settings[banner_ui][layout]" id="wpeu-cs-banner-layout">
							<option value="box" <?php selected( $layout, 'box' ); ?>><?php esc_html_e( 'Box', 'privaro-cookie-consent-banner' ); ?></option>
							<option value="bar" <?php selected( $layout, 'bar' ); ?>><?php esc_html_e( 'Bar', 'privaro-cookie-consent-banner' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Position', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<select name="wpeu_cs_settings[banner_ui][position]" id="wpeu-cs-banner-position">
							<option value="bottom-left" <?php selected( $position, 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'privaro-cookie-consent-banner' ); ?></option>
							<option value="bottom-center" <?php selected( $position, 'bottom-center' ); ?>><?php esc_html_e( 'Bottom Center', 'privaro-cookie-consent-banner' ); ?></option>
							<option value="bottom-right" <?php selected( $position, 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'privaro-cookie-consent-banner' ); ?></option>
							<option value="center" <?php selected( $position, 'center' ); ?>><?php esc_html_e( 'Center', 'privaro-cookie-consent-banner' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Theme', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<select name="wpeu_cs_settings[banner_ui][theme]" id="wpeu-cs-banner-theme">
							<option value="light" <?php selected( $theme, 'light' ); ?>><?php esc_html_e( 'Light', 'privaro-cookie-consent-banner' ); ?></option>
							<option value="dark" <?php selected( $theme, 'dark' ); ?>><?php esc_html_e( 'Dark', 'privaro-cookie-consent-banner' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Primary Color', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="text" id="wpeu-cs-banner-primary-color" name="wpeu_cs_settings[banner_ui][primary_color]" value="<?php echo esc_attr( $primary_color ); ?>" class="wpeu-cs-color-picker">
					</td>
				</tr>
			</table>

			<div class="wpeu-cs-preview-container">
				<h3><?php esc_html_e( 'Live Preview', 'privaro-cookie-consent-banner' ); ?></h3>
				<div class="wpeu-cs-preview-frame-wrapper" style="border: 1px solid #ccd0d4; background: #f6f7f7;">
					<iframe id="wpeu-cs-banner-preview" src="about:blank" width="100%" height="400" frameborder="0"></iframe>
				</div>
				<p>
					<button type="button" id="wpeu-cs-refresh-preview" class="button button-secondary"><?php esc_html_e( 'Refresh Preview', 'privaro-cookie-consent-banner' ); ?></button>
				</p>
			</div>

			<input type="hidden" id="wpeu_cs_preview_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpeu-cs-preview' ) ); ?>">

			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Render consent log tab.
	 */
	private function render_consent_log_tab(): void {
		$settings = SettingsRepository::instance()->get_effective_settings();
		$logging  = ! array_key_exists( 'consent_logging_enabled', $settings ) || ! empty( $settings['consent_logging_enabled'] );

		$table = new ConsentLogTable();
		$table->prepare_items();

		?>
		<div class="wpeu-cs-consent-log-header">
			<h2><?php esc_html_e( 'Consent Log', 'privaro-cookie-consent-banner' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Audit trail of visitor consent interactions. This is a technical aid for Art. 7(1) GDPR accountability.', 'privaro-cookie-consent-banner' ); ?></p>
		</div>

		<?php if ( ! $logging ) : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'Consent logging is disabled in Tools → Consent Logging. Existing entries are shown below; new visits will not be recorded until logging is enabled.', 'privaro-cookie-consent-banner' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="wpeu-cs-consent-log-table-wrap">
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
				<input type="hidden" name="tab" value="consent_log">
				<?php $table->search_box( __( 'Search Logs', 'privaro-cookie-consent-banner' ), 'consent-log' ); ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render dashboard tab.
	 */
	private function render_dashboard_tab(): void {
		$settings     = $this->get_admin_settings();
		$version      = $settings['version'] ?? WPEU_CS_VERSION;
		$blocker      = $settings['blocker_enabled'] ?? false;
		$consent_api  = defined( 'WP_CONSENT_API_VERSION' );

		$logger = new \WPEU\CookieSuite\Consent\ConsentLogger();
		$logs_30_days = $logger->get_total_logs( array( 'start_date' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ) ) );

		$services_count = 0;
		if ( ! empty( $settings['enabled_services'] ) ) {
			$services_count = count( array_filter( $settings['enabled_services'] ) );
		}

		$custom_rules_count = 0;
		if ( ! empty( $settings['custom_block_rules'] ) ) {
			$rules              = explode( "\n", str_replace( "\r", '', $settings['custom_block_rules'] ) );
			$custom_rules_count = count( array_filter( array_map( 'trim', $rules ) ) );
		}

		$total_rules = $services_count + $custom_rules_count;

		?>
		<div class="wpeu-cs-dashboard-cards">
			<div class="wpeu-cs-card">
				<h3><?php esc_html_e( 'Plugin Version', 'privaro-cookie-consent-banner' ); ?></h3>
				<p><?php echo esc_html( $version ); ?></p>
			</div>

			<div class="wpeu-cs-card">
				<h3><?php esc_html_e( 'Blocker Status', 'privaro-cookie-consent-banner' ); ?></h3>
				<p>
					<span class="status <?php echo $blocker ? 'status-active' : 'status-inactive'; ?>">
						<?php echo $blocker ? esc_html__( 'Active', 'privaro-cookie-consent-banner' ) : esc_html__( 'Inactive', 'privaro-cookie-consent-banner' ); ?>
					</span>
				</p>
			</div>

			<div class="wpeu-cs-card">
				<h3><?php esc_html_e( 'Consent API Status', 'privaro-cookie-consent-banner' ); ?></h3>
				<p>
					<span class="status status-active">
						<?php
						if ( defined( 'WP_CONSENT_API_VERSION' ) ) {
							esc_html_e( 'Active (native)', 'privaro-cookie-consent-banner' );
						} else {
							esc_html_e( 'Active (polyfill)', 'privaro-cookie-consent-banner' );
						}
						?>
					</span>
				</p>
			</div>

			<div class="wpeu-cs-card">
				<h3><?php esc_html_e( 'Active Block Rules', 'privaro-cookie-consent-banner' ); ?></h3>
				<p><?php echo (int) $total_rules; ?></p>
			</div>

			<div class="wpeu-cs-card">
				<h3><?php esc_html_e( 'Logs (Last 30 days)', 'privaro-cookie-consent-banner' ); ?></h3>
				<p><?php echo (int) $logs_30_days; ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render integrations tab.
	 */
	private function render_integrations_tab(): void {
		$settings             = $this->get_admin_settings();
		$services             = ScriptRegistry::get_services();
		$enabled_services     = $settings['enabled_services'] ?? array();
		$enabled_integrations = $settings['enabled_integrations'] ?? array();
		$custom_rules         = $settings['custom_block_rules'] ?? '';
		$google_gcm           = $settings['google_consent_mode'] ?? true;
		$blocker_enabled      = ! empty( $settings['blocker_enabled'] );
		$analytics_field      = $settings['theme_analytics_field'] ?? 'analytics';

		?>
		<form method="post" action="<?php echo esc_url( $this->get_settings_form_action() ); ?>">
			<?php
			$this->render_settings_form_header();
			?>
			<input type="hidden" name="wpeu_cs_settings[active_tab]" value="integrations">

			<h2><?php esc_html_e( 'Google', 'privaro-cookie-consent-banner' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Google Consent Mode v2', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpeu_cs_settings[google_consent_mode]" value="1" <?php checked( $google_gcm ); ?>>
							<?php esc_html_e( 'Send default denied consent to gtag before analytics/marketing tags load.', 'privaro-cookie-consent-banner' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Google Analytics cookie guard', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="wpeu_cs_settings[enabled_integrations][ga_cookie_guard]" value="1" <?php checked( ! isset( $enabled_integrations['ga_cookie_guard'] ) || ! empty( $enabled_integrations['ga_cookie_guard'] ) ); ?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Clears _ga cookies and blocks enqueued gtag scripts when statistics consent is not granted.', 'privaro-cookie-consent-banner' ); ?></p>
					</td>
				</tr>
				<?php if ( defined( 'GOOGLESITEKIT_VERSION' ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Google Site Kit', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="wpeu_cs_settings[enabled_integrations][google_site_kit]" value="1" <?php checked( ! isset( $enabled_integrations['google_site_kit'] ) || ! empty( $enabled_integrations['google_site_kit'] ) ); ?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Block Site Kit analytics tags until statistics consent (works with Consent Mode v2 above).', 'privaro-cookie-consent-banner' ); ?></p>
					</td>
				</tr>
				<?php endif; ?>
			</table>

			<h2><?php esc_html_e( 'Third-Party Integrations', 'privaro-cookie-consent-banner' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Theme Analytics (ACF)', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="wpeu_cs_settings[enabled_integrations][theme_analytics]" value="1" <?php checked( ! empty( $enabled_integrations['theme_analytics'] ) ); ?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Intercept ACF option field for analytics.', 'privaro-cookie-consent-banner' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'ACF Analytics Field Name', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[theme_analytics_field]" value="<?php echo esc_attr( $analytics_field ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'The name of the ACF field used for analytics/tracking code.', 'privaro-cookie-consent-banner' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Iframe Placeholders', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="wpeu_cs_settings[enabled_integrations][iframe_placeholder]" value="1" <?php checked( ! empty( $enabled_integrations['iframe_placeholder'] ) ); ?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Replace YouTube, Vimeo, and Google Maps iframes with placeholders.', 'privaro-cookie-consent-banner' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Contact Form 7 reCAPTCHA', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="wpeu_cs_settings[enabled_integrations][cf7_recaptcha]" value="1" <?php checked( ! empty( $enabled_integrations['cf7_recaptcha'] ) ); ?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Only load reCAPTCHA scripts after marketing consent.', 'privaro-cookie-consent-banner' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Service Blocker', 'privaro-cookie-consent-banner' ); ?></h2>
			<p><?php esc_html_e( 'Enable automatic script blocking for these popular services.', 'privaro-cookie-consent-banner' ); ?></p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Script blocker enabled', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="wpeu_cs_settings[blocker_enabled]" value="1" <?php checked( $blocker_enabled ); ?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Output-buffer blocking for third-party scripts matched by the registry and custom rules.', 'privaro-cookie-consent-banner' ); ?></p>
					</td>
				</tr>
				<?php foreach ( $services as $id => $service ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $service['label'] ); ?></th>
						<td>
							<label class="switch">
								<input type="checkbox" name="wpeu_cs_settings[enabled_services][<?php echo esc_attr( $id ); ?>]" value="1" <?php checked( ! empty( $enabled_services[ $id ] ) ); ?>>
								<span class="slider round"></span>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>

				<tr>
					<th scope="row"><?php esc_html_e( 'Custom Block Rules', 'privaro-cookie-consent-banner' ); ?></th>
					<td>
						<textarea name="wpeu_cs_settings[custom_block_rules]" rows="10" cols="50" class="large-text code"><?php echo esc_textarea( $custom_rules ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Enter one pattern per line to block custom scripts. Use -url- prefix to match only the src attribute.', 'privaro-cookie-consent-banner' ); ?><br>
							<?php esc_html_e( 'Example: analytics.js or -url-my-script.js', 'privaro-cookie-consent-banner' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Render scanner tab.
	 */
	private function render_scanner_tab(): void {
		$results = get_option( 'wpeu_cs_scan_results', array() );
		$categories = Categories::get_all();

		?>
		<div class="wpeu-cs-scanner-container">
			<h2><?php esc_html_e( 'Cookie Scanner', 'privaro-cookie-consent-banner' ); ?></h2>
			<p><?php esc_html_e( 'Scan your website to detect cookies and third-party scripts.', 'privaro-cookie-consent-banner' ); ?></p>

			<div class="wpeu-cs-scanner-actions">
				<button type="button" id="wpeu-cs-start-scan" class="button button-primary">
					<?php esc_html_e( 'Start Scan', 'privaro-cookie-consent-banner' ); ?>
				</button>
				<span class="spinner" style="float: none; margin-top: 0;"></span>
			</div>

			<div id="wpeu-cs-scan-progress" class="wpeu-cs-scan-progress" style="display: none;">
				<div class="wpeu-cs-progress-bar">
					<div class="wpeu-cs-progress-fill" style="width: 0%;"></div>
				</div>
				<p class="wpeu-cs-progress-status"></p>
			</div>

			<div id="wpeu-cs-scan-results-wrapper">
				<?php if ( ! empty( $results['cookies'] ) || ! empty( $results['scripts'] ) ) : ?>
					<div class="wpeu-cs-scan-actions-secondary">
						<button type="button" id="wpeu-cs-import-scan" class="button button-secondary">
							<?php esc_html_e( 'Import to Inventory', 'privaro-cookie-consent-banner' ); ?>
						</button>
						<span class="spinner" style="float: none; margin-top: 0;"></span>
					</div>
				<?php endif; ?>
				<?php $this->render_scan_results_table( $results, $categories ); ?>
			</div>

			<input type="hidden" id="wpeu_cs_scanner_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpeu-cs-scanner' ) ); ?>">
		</div>
		<?php
	}

	/**
	 * Render scan results table.
	 *
	 * @param array $results    Scan results.
	 * @param array $categories All categories.
	 */
	public function render_scan_results_table( array $results, array $categories ): void {
		if ( empty( $results['cookies'] ) && empty( $results['scripts'] ) ) {
			echo '<p class="wpeu-cs-no-results">' . esc_html__( 'No scan results found. Start a scan to find cookies and scripts.', 'privaro-cookie-consent-banner' ) . '</p>';
			return;
		}

		?>
		<h3><?php esc_html_e( 'Detected Items', 'privaro-cookie-consent-banner' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name / Domain', 'privaro-cookie-consent-banner' ); ?></th>
					<th><?php esc_html_e( 'Type', 'privaro-cookie-consent-banner' ); ?></th>
					<th><?php esc_html_e( 'Detected Category', 'privaro-cookie-consent-banner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( array_merge( $results['cookies'] ?? array(), $results['scripts'] ?? array() ) as $item ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $item['name'] ); ?></strong></td>
						<td><?php echo esc_html( ucfirst( $item['type'] ) ); ?></td>
						<td>
							<span class="wpeu-cs-tag wpeu-cs-tag-<?php echo esc_attr( $item['category'] ); ?>">
								<?php echo esc_html( $categories[ $item['category'] ]['label'] ?? $item['category'] ); ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render cookies tab.
	 */
	private function render_cookies_tab(): void {
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( (string) $_GET['action'] ) ) : '';
		$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

		if ( 'edit' === $action || 'add' === $action ) {
			$this->render_cookie_form( $id );
			return;
		}

		$table = new CookieListTable();
		$table->prepare_items();

		?>
		<div class="wpeu-cs-cookies-header">
			<h2><?php esc_html_e( 'Cookie Inventory', 'privaro-cookie-consent-banner' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=cookies&action=add' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Add New', 'privaro-cookie-consent-banner' ); ?>
			</a>
		</div>

		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
			<input type="hidden" name="tab" value="cookies">
			<?php $table->search_box( __( 'Search Cookies', 'privaro-cookie-consent-banner' ), 'search-id' ); ?>
			<?php $table->display(); ?>
		</form>
		<?php
	}

	/**
	 * Render cookie form.
	 *
	 * @param int $id Cookie ID for editing.
	 */
	private function render_cookie_form( int $id = 0 ): void {
		$repository = new \WPEU\CookieSuite\Scanner\CookieRepository();
		$cookie     = $id ? $repository->get( $id ) : null;
		$categories = Categories::get_all();

		$title = $id ? __( 'Edit Cookie', 'privaro-cookie-consent-banner' ) : __( 'Add New Cookie', 'privaro-cookie-consent-banner' );
		?>
		<h2><?php echo esc_html( $title ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=cookies' ) ); ?>">
			<?php wp_nonce_field( 'wpeu_cs_save_cookie', 'wpeu_cs_cookie_nonce' ); ?>
			<input type="hidden" name="action" value="wpeu_cs_save_cookie">
			<input type="hidden" name="cookie[id]" value="<?php echo (int) $id; ?>">

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="cookie_name"><?php esc_html_e( 'Name', 'privaro-cookie-consent-banner' ); ?></label></th>
					<td><input type="text" name="cookie[name]" id="cookie_name" value="<?php echo esc_attr( $cookie['name'] ?? '' ); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_domain"><?php esc_html_e( 'Domain', 'privaro-cookie-consent-banner' ); ?></label></th>
					<td><input type="text" name="cookie[domain]" id="cookie_domain" value="<?php echo esc_attr( $cookie['domain'] ?? '' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_category"><?php esc_html_e( 'Category', 'privaro-cookie-consent-banner' ); ?></label></th>
					<td>
						<select name="cookie[category]" id="cookie_category">
							<?php foreach ( $categories as $cat_id => $category ) : ?>
								<option value="<?php echo esc_attr( $cat_id ); ?>" <?php selected( $cookie['category'] ?? 'necessary', $cat_id ); ?>>
									<?php echo esc_html( $category['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_service"><?php esc_html_e( 'Service', 'privaro-cookie-consent-banner' ); ?></label></th>
					<td><input type="text" name="cookie[service]" id="cookie_service" value="<?php echo esc_attr( $cookie['service'] ?? '' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_duration"><?php esc_html_e( 'Duration', 'privaro-cookie-consent-banner' ); ?></label></th>
					<td><input type="text" name="cookie[duration]" id="cookie_duration" value="<?php echo esc_attr( $cookie['duration'] ?? '' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_description"><?php esc_html_e( 'Description', 'privaro-cookie-consent-banner' ); ?></label></th>
					<td><textarea name="cookie[description]" id="cookie_description" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $cookie['description'] ?? '' ); ?></textarea></td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=cookies' ) ); ?>">
				&larr; <?php esc_html_e( 'Back to Inventory', 'privaro-cookie-consent-banner' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render tools tab.
	 */
	private function render_tools_tab(): void {
		$settings     = $this->get_admin_settings();
		$locales      = BannerTexts::get_locales();
		$lang_input   = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( (string) $_GET['lang'] ) ) : '';
		$current_lang = array_key_exists( $lang_input, $locales ) ? $lang_input : BannerTexts::get_active_locale();

		$policy_texts = $settings['policy_texts'][ $current_lang ] ?? array();
		$intro        = $policy_texts['intro'] ?? '';
		$template     = $policy_texts['template'] ?? BannerTexts::get_default_policy_template( $current_lang );

		?>
		<h2><?php esc_html_e( 'Tools', 'privaro-cookie-consent-banner' ); ?></h2>

		<?php
		$message = isset( $_GET['message'] ) ? sanitize_key( wp_unslash( (string) $_GET['message'] ) ) : '';
		if ( 'lang_added' === $message ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Language added successfully.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		} elseif ( 'lang_removed' === $message ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Language removed.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		} elseif ( 'invalid_code' === $message ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid language code. Use 2-5 characters.', 'privaro-cookie-consent-banner' ) . '</p></div>';
		}
		?>

		<?php
		if ( 'imported' === $message ) :
			?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings imported successfully.', 'privaro-cookie-consent-banner' ); ?></p></div>
			<?php
		elseif ( 'import_invalid' === $message ) :
			?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Import failed: invalid or incompatible export file.', 'privaro-cookie-consent-banner' ); ?></p></div>
			<?php
		elseif ( 'import_error' === $message ) :
			?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Import failed: could not read the uploaded file.', 'privaro-cookie-consent-banner' ); ?></p></div>
			<?php
		elseif ( 'revision_bumped' === $message ) :
			?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Consent revision updated. Visitors will be prompted again.', 'privaro-cookie-consent-banner' ); ?></p></div>
			<?php
		endif;
		?>

		<div class="notice notice-info inline">
			<p><strong><?php esc_html_e( 'Disclaimer:', 'privaro-cookie-consent-banner' ); ?></strong> <?php esc_html_e( 'This plugin provides tools for cookie compliance but does not constitute legal advice.', 'privaro-cookie-consent-banner' ); ?></p>
		</div>

		<?php
		$this->render_language_selector( 'tools', $locales, $current_lang );
		?>

		<form method="post" action="<?php echo esc_url( $this->get_settings_form_action() ); ?>">
			<?php $this->render_settings_form_header(); ?>
			<input type="hidden" name="wpeu_cs_settings[active_tab]" value="tools">

			<div class="card">
				<h3>
				<?php
				/* translators: %s: language label */
				printf( esc_html__( 'Cookie Policy Settings (%s)', 'privaro-cookie-consent-banner' ), esc_html( $locales[ $current_lang ] ) );
				?>
				</h3>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="policy_intro"><?php esc_html_e( 'Policy Intro Text', 'privaro-cookie-consent-banner' ); ?></label></th>
						<td>
							<textarea name="wpeu_cs_settings[policy_texts][<?php echo esc_attr( $current_lang ); ?>][intro]" id="policy_intro" rows="5" class="large-text"><?php echo esc_textarea( $intro ); ?></textarea>
							<p class="description"><?php esc_html_e( 'This text is displayed at the beginning of your cookie policy.', 'privaro-cookie-consent-banner' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="policy_template"><?php esc_html_e( 'Policy Template', 'privaro-cookie-consent-banner' ); ?></label></th>
						<td>
							<textarea name="wpeu_cs_settings[policy_texts][<?php echo esc_attr( $current_lang ); ?>][template]" id="policy_template" rows="10" class="large-text code"><?php echo esc_textarea( $template ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'The template for the [wpeu_cookie_policy] shortcode.', 'privaro-cookie-consent-banner' ); ?><br>
								<?php esc_html_e( 'Available placeholders: {{intro}}, {{table}}, {{content}}', 'privaro-cookie-consent-banner' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</div>
		</form>


		<div class="card">
			<h3><?php esc_html_e( 'Consent Logging', 'privaro-cookie-consent-banner' ); ?></h3>
			<form method="post" action="<?php echo esc_url( $this->get_settings_form_action() ); ?>">
				<?php $this->render_settings_form_header(); ?>
				<input type="hidden" name="wpeu_cs_settings[active_tab]" value="tools">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Logging', 'privaro-cookie-consent-banner' ); ?></th>
						<td>
							<label class="switch">
								<input type="checkbox" name="wpeu_cs_settings[consent_logging_enabled]" value="1" <?php checked( ! empty( $settings['consent_logging_enabled'] ) ); ?>>
								<span class="slider round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Store an audit trail of consent events in the local database.', 'privaro-cookie-consent-banner' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Retention (days)', 'privaro-cookie-consent-banner' ); ?></th>
						<td>
							<input type="number" name="wpeu_cs_settings[consent_log_retention]" value="<?php echo (int) ( $settings['consent_log_retention'] ?? 365 ); ?>" min="1" step="1" class="small-text">
							<p class="description"><?php esc_html_e( 'Automatically delete logs older than this many days.', 'privaro-cookie-consent-banner' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Store IP Hash', 'privaro-cookie-consent-banner' ); ?></th>
						<td>
							<label class="switch">
								<input type="checkbox" name="wpeu_cs_settings[consent_log_store_ip]" value="1" <?php checked( ! empty( $settings['consent_log_store_ip'] ) ); ?>>
								<span class="slider round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Store a salted SHA-256 hash of the visitor IP address for better accountability (Art. 7 GDPR).', 'privaro-cookie-consent-banner' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>

		<div class="card">
			<h3><?php esc_html_e( 'Consent revision', 'privaro-cookie-consent-banner' ); ?></h3>
			<p><?php esc_html_e( 'Increment the consent revision to re-prompt all visitors (existing consent cookies become outdated).', 'privaro-cookie-consent-banner' ); ?></p>
			<p><strong><?php esc_html_e( 'Current revision:', 'privaro-cookie-consent-banner' ); ?></strong> <?php echo (int) ( $settings['consent_revision'] ?? 0 ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Reset all visitor consents and show the banner again?', 'privaro-cookie-consent-banner' ) ); ?>');">
				<?php wp_nonce_field( 'wpeu_cs_bump_consent_revision', 'wpeu_cs_bump_revision_nonce' ); ?>
				<input type="hidden" name="action" value="wpeu_cs_bump_consent_revision">
				<?php submit_button( __( 'Reset all consents (bump revision)', 'privaro-cookie-consent-banner' ), 'delete', 'submit', false ); ?>
			</form>
		</div>

		<div class="card">
			<h3><?php esc_html_e( 'Export Cookie Inventory', 'privaro-cookie-consent-banner' ); ?></h3>
			<p><?php esc_html_e( 'Download your cookie inventory as a CSV file.', 'privaro-cookie-consent-banner' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools' ) ); ?>">
				<?php wp_nonce_field( 'wpeu_cs_export_csv', 'wpeu_cs_export_nonce' ); ?>
				<input type="hidden" name="action" value="wpeu_cs_export_csv">
				<?php submit_button( __( 'Download CSV', 'privaro-cookie-consent-banner' ), 'primary', 'submit', false ); ?>
			</form>
		</div>

		<div class="card">
			<h3><?php esc_html_e( 'Export Settings', 'privaro-cookie-consent-banner' ); ?></h3>
			<p><?php esc_html_e( 'Download banner texts, integrations, script registry, and all plugin settings as JSON for backup or migration to another site.', 'privaro-cookie-consent-banner' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools' ) ); ?>">
				<?php wp_nonce_field( 'wpeu_cs_export_json', 'wpeu_cs_export_json_nonce' ); ?>
				<input type="hidden" name="action" value="wpeu_cs_export_json">
				<?php submit_button( __( 'Download JSON', 'privaro-cookie-consent-banner' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>

		<div class="card">
			<h3><?php esc_html_e( 'Import Settings', 'privaro-cookie-consent-banner' ); ?></h3>
			<p><?php esc_html_e( 'Upload a JSON export from another site. Cookie inventory is not replaced — only plugin settings are updated.', 'privaro-cookie-consent-banner' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'wpeu_cs_import_json', 'wpeu_cs_import_json_nonce' ); ?>
				<input type="hidden" name="action" value="wpeu_cs_import_json">
				<input type="file" name="wpeu_cs_import_file" accept="application/json,.json" required>
				<?php submit_button( __( 'Import JSON', 'privaro-cookie-consent-banner' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}


	/**
	 * Run log cleanup if retention period is reached.
	 */
	public function maybe_cleanup_logs(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		$last_cleanup = get_option( 'wpeu_cs_last_log_cleanup', 0 );
		if ( time() - $last_cleanup < DAY_IN_SECONDS ) {
			return;
		}

		$logger = new \WPEU\CookieSuite\Consent\ConsentLogger();
		$logger->cleanup_expired_logs();

		update_option( 'wpeu_cs_last_log_cleanup', time() );
	}

	/**
	 * Ajax log consent action.
	 */
	public function ajax_log_consent(): void {
		check_ajax_referer( 'wpeu-cs-log', 'nonce' );

		$settings = SettingsRepository::instance()->get_effective_settings();
		if ( array_key_exists( 'consent_logging_enabled', $settings ) && empty( $settings['consent_logging_enabled'] ) ) {
			wp_send_json_error( 'logging_disabled' );
		}

		// Skip bots (empty user agent)
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( empty( $ua ) ) {
			wp_send_json_error( 'bot_skipped' );
		}

		// Rate limit: max 10 requests/minute per IP
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$transient  = 'wpeu_cs_log_rl_' . md5( $ip );
		$count      = (int) get_transient( $transient );

		if ( $count >= 10 ) {
			wp_send_json_error( 'rate_limited', 429 );
		}
		set_transient( $transient, $count + 1, 60 );

		$raw_data = wp_unslash( $_POST );
		$event_type = sanitize_key( $raw_data['event_type'] ?? '' );
		$allowed_events = array( 'accept_all', 'reject_all', 'save_preferences', 'revoke', 'policy_revision' );

		if ( ! in_array( $event_type, $allowed_events, true ) ) {
			wp_send_json_error( 'invalid_event' );
		}

		$consent_uuid = sanitize_text_field( $raw_data['consent_uuid'] ?? '' );
		if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $consent_uuid ) ) {
			wp_send_json_error( 'invalid_uuid' );
		}

		$categories = array();
		if ( isset( $raw_data['categories'] ) && is_array( $raw_data['categories'] ) ) {
			foreach ( $raw_data['categories'] as $cat => $status ) {
				$categories[ sanitize_key( $cat ) ] = (bool) $status;
			}
		}

		$ip_hash = null;
		if ( ! empty( $settings['consent_log_store_ip'] ) ) {
			$ip_hash = wpeu_cs_hash_ip( $ip );
		}

		$logger = new \WPEU\CookieSuite\Consent\ConsentLogger();
		$success = $logger->log(
			array(
				'consent_uuid'    => $consent_uuid,
				'event_type'      => $event_type,
				'categories'      => $categories,
				'consent_mode'    => ! empty( $settings['eu_mode'] ) ? 'optin' : 'optout',
				'page_url'        => esc_url_raw( $raw_data['page_url'] ?? '' ),
				'locale'          => sanitize_text_field( $raw_data['locale'] ?? 'en' ),
				'banner_revision' => (int) ( $settings['consent_revision'] ?? 0 ),
				'ip_hash'         => $ip_hash,
				'user_agent'      => substr( sanitize_text_field( $ua ), 0, 255 ),
			)
		);

		if ( $success ) {
			wp_send_json_success( array( 'log_id' => $success ) );
		}

		wp_send_json_error( 'log_failed' );
	}

	/**
	 * Ajax preview action.
	 */
	public function ajax_preview(): void {
		check_ajax_referer( 'wpeu-cs-preview', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		if ( ! defined( 'WPEU_CS_PREVIEW' ) ) {
			define( 'WPEU_CS_PREVIEW', true );
		}

		$preview_locale = '';
		if ( isset( $_POST['settings']['preview_locale'] ) ) {
			$preview_locale = sanitize_key( wp_unslash( (string) $_POST['settings']['preview_locale'] ) );
		}

		if ( $preview_locale ) {
			add_filter(
				'wpeu_cs_banner_locale',
				static function () use ( $preview_locale ) {
					return $preview_locale;
				}
			);
		}

		// Mock settings for preview
		add_filter(
			'option_wpeu_cs_settings',
			function ( $settings ) {
				if ( ! isset( $_POST['settings'] ) || ! is_array( $_POST['settings'] ) ) {
					return $settings;
				}

				$post = wp_unslash( $_POST['settings'] );

				if ( isset( $post['banner_ui'] ) && is_array( $post['banner_ui'] ) ) {
					$ui                      = $post['banner_ui'];
					$settings['banner_ui'] = array(
						'layout'        => in_array( $ui['layout'] ?? 'box', array( 'box', 'bar' ), true ) ? $ui['layout'] : 'box',
						'position'      => sanitize_text_field( $ui['position'] ?? 'bottom-right' ),
						'theme'         => in_array( $ui['theme'] ?? 'light', array( 'light', 'dark' ), true ) ? $ui['theme'] : 'light',
						'primary_color' => sanitize_hex_color( $ui['primary_color'] ?? '' ) ?: '#30363c',
					);
				}
				if ( isset( $post['banner_texts'] ) && is_array( $post['banner_texts'] ) ) {
					$settings['banner_texts'] = $post['banner_texts'];
				}
				if ( isset( $post['enabled_categories'] ) && is_array( $post['enabled_categories'] ) ) {
					$settings['enabled_categories'] = array_map( 'sanitize_text_field', $post['enabled_categories'] );
				}
				if ( array_key_exists( 'show_reject_all', $post ) ) {
					$settings['show_reject_all'] = filter_var( $post['show_reject_all'], FILTER_VALIDATE_BOOLEAN );
				}
				if ( array_key_exists( 'eu_mode', $post ) ) {
					$settings['eu_mode'] = filter_var( $post['eu_mode'], FILTER_VALIDATE_BOOLEAN );
				}

				return $settings;
			}
		);

		new \WPEU\CookieSuite\Frontend\Banner();

		wp_register_style( 'wpeu-cs-preview-layout', false, array(), WPEU_CS_VERSION );
		wp_enqueue_style( 'wpeu-cs-preview-layout' );
		wp_add_inline_style(
			'wpeu-cs-preview-layout',
			'body { background: #f0f0f1 !important; margin: 0; padding: 0; min-height: 400px; overflow: visible; }'
			. '#cc-main { position: relative !important; z-index: 1 !important; }'
			. '#cc-main .cm { position: relative !important; }'
			. '.cc--resizer { display: none !important; }'
		);

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<?php wp_head(); ?>
		</head>
		<body>
			<div id="wpeu-cs-preview-content"></div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Render placeholder tab.
	 *
	 * @param string $task_id Task ID.
	 */
	private function render_placeholder_tab( string $task_id ): void {
		echo '<p>' . esc_html__( 'This section is not available yet.', 'privaro-cookie-consent-banner' ) . '</p>';
	}
}
