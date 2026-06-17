<?php
/**
 * Admin UI and settings management.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Admin;

use WPEU\CookieSuite\Consent\Categories;
use WPEU\CookieSuite\Consent\BannerTexts;
use WPEU\CookieSuite\Frontend\ScriptRegistry;

/**
 * Admin class.
 */
final class Admin {

	/**
	 * Settings page slug.
	 */
	const PAGE_SLUG = 'wpeu-cs-settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_wpeu_cs_preview', array( $this, 'ajax_preview' ) );
	}

	/**
	 * Handle admin actions (CRUD, export, etc).
	 */
	public function handle_actions(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = $_GET['page'] ?? '';
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		$action = $_REQUEST['action'] ?? '';
		if ( ! $action ) {
			return;
		}

		$repository = new \WPEU\CookieSuite\Scanner\CookieRepository();

		switch ( $action ) {
			case 'wpeu_cs_save_cookie':
				check_admin_referer( 'wpeu_cs_save_cookie', 'wpeu_cs_cookie_nonce' );
				$cookie_data = $_POST['cookie'] ?? array();
				if ( ! empty( $cookie_data['name'] ) ) {
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
		}
	}

	/**
	 * Handle CSV export.
	 */
	private function handle_csv_export(): void {
		$repository = new \WPEU\CookieSuite\Scanner\CookieRepository();
		$cookies    = $repository->all();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wpeu-cookies-' . date( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
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

		fclose( $output );
		exit;
	}

	/**
	 * Add menu page.
	 */
	public function add_menu_page(): void {
		add_menu_page(
			__( 'EU Cookie Suite', 'wp-eu-cookie-suite' ),
			__( 'EU Cookie Suite', 'wp-eu-cookie-suite' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-shield'
		);
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
		$sanitized    = $old_settings;

		$active_tab = $input['active_tab'] ?? '';

		if ( 'banner' === $active_tab ) {
			$sanitized['enabled_categories'] = array();
			if ( isset( $input['enabled_categories'] ) && is_array( $input['enabled_categories'] ) ) {
				$sanitized['enabled_categories'] = array_map( 'sanitize_text_field', $input['enabled_categories'] );
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
					$sanitized['banner_texts'][ $locale ] = array_map( 'sanitize_text_field', $texts );
				}
			}

			if ( isset( $input['banner_ui'] ) && is_array( $input['banner_ui'] ) ) {
				$sanitized['banner_ui'] = array(
					'layout'        => sanitize_text_field( $input['banner_ui']['layout'] ?? 'box' ),
					'position'      => sanitize_text_field( $input['banner_ui']['position'] ?? 'bottom-right' ),
					'theme'         => sanitize_text_field( $input['banner_ui']['theme'] ?? 'light' ),
					'primary_color' => sanitize_hex_color( $input['banner_ui']['primary_color'] ?? '#30363c' ) ?: '#30363c',
					'custom_css'    => wp_strip_all_tags( $input['banner_ui']['custom_css'] ?? '' ),
				);
			}
		} elseif ( 'integrations' === $active_tab ) {
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
		} elseif ( 'tools' === $active_tab ) {
			if ( isset( $input['policy_texts'] ) && is_array( $input['policy_texts'] ) ) {
				if ( ! isset( $sanitized['policy_texts'] ) ) {
					$sanitized['policy_texts'] = array();
				}
				foreach ( $input['policy_texts'] as $locale => $texts ) {
					if ( ! is_array( $texts ) ) {
						continue;
					}
					$sanitized['policy_texts'][ $locale ] = array(
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
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
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
	 * Render settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = array(
			'dashboard'    => __( 'Dashboard', 'wp-eu-cookie-suite' ),
			'banner'       => __( 'Banner', 'wp-eu-cookie-suite' ),
			'cookies'      => __( 'Cookies', 'wp-eu-cookie-suite' ),
			'scanner'      => __( 'Scanner', 'wp-eu-cookie-suite' ),
			'integrations' => __( 'Integrations', 'wp-eu-cookie-suite' ),
			'tools'        => __( 'Tools', 'wp-eu-cookie-suite' ),
		);

		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? $_GET['tab'] : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab_id ) ); ?>" class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="wpeu-cs-content">
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
	 * Render banner tab.
	 */
	private function render_banner_tab(): void {
		$settings           = get_option( 'wpeu_cs_settings', array() );
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
		$custom_css         = $banner_ui['custom_css'] ?? '';

		$locales        = BannerTexts::get_locales();
		$current_lang   = isset( $_GET['lang'] ) && array_key_exists( $_GET['lang'], $locales ) ? $_GET['lang'] : 'en';
		$texts          = BannerTexts::get_strings( $current_lang );

		?>
		<div class="wpeu-cs-lang-selector">
			<ul class="subsubsub">
				<?php
				$i = 0;
				foreach ( $locales as $code => $label ) :
					$url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=banner&lang=' . $code );
					$current = $current_lang === $code ? 'current' : '';
					echo '<li><a href="' . esc_url( $url ) . '" class="' . esc_attr( $current ) . '">' . esc_html( $label ) . '</a>' . ( $i < count( $locales ) - 1 ? ' | ' : '' ) . '</li>';
					$i++;
				endforeach;
				?>
			</ul>
			<br class="clear">
		</div>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'wpeu_cs_settings' );
			?>
			<input type="hidden" name="wpeu_cs_settings[active_tab]" value="banner">

			<h3><?php printf( esc_html__( 'Banner Settings (%s)', 'wp-eu-cookie-suite' ), esc_html( $locales[ $current_lang ] ) ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled Categories', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<?php foreach ( $all_categories as $id => $category ) : ?>
							<?php if ( 'necessary' === $id ) continue; ?>
							<label>
								<input type="checkbox" name="wpeu_cs_settings[enabled_categories][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $enabled_categories, true ) ); ?>>
								<?php echo esc_html( $category['label'] ); ?>
							</label><br>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Select which consent categories to display in the banner.', 'wp-eu-cookie-suite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Privacy Policy URL', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<input type="url" name="wpeu_cs_settings[privacy_policy_url]" value="<?php echo esc_url( $privacy_url ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cookie Policy URL', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<input type="url" name="wpeu_cs_settings[cookie_policy_url]" value="<?php echo esc_url( $cookie_url ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show "Reject All"', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpeu_cs_settings[show_reject_all]" value="1" <?php checked( $show_reject_all ); ?>>
							<?php esc_html_e( 'Show the "Reject All" button in the banner.', 'wp-eu-cookie-suite' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Strict EU Mode', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpeu_cs_settings[eu_mode]" value="1" <?php checked( $eu_mode ); ?>>
							<?php esc_html_e( 'Enable strict EU mode (block all non-necessary cookies until consent).', 'wp-eu-cookie-suite' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<hr>
			<h3><?php esc_html_e( 'Localized Texts', 'wp-eu-cookie-suite' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Consent Modal Title', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][consent_modal_title]" value="<?php echo esc_attr( $texts['consent_modal_title'] ?? '' ); ?>" class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Consent Modal Description', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<textarea name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][consent_modal_description]" rows="3" class="large-text"><?php echo esc_textarea( $texts['consent_modal_description'] ?? '' ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Preferences Modal Title', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][preferences_modal_title]" value="<?php echo esc_attr( $texts['preferences_modal_title'] ?? '' ); ?>" class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Accept All Button', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][accept_all_btn]" value="<?php echo esc_attr( $texts['accept_all_btn'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Reject All Button', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][accept_necessary_btn]" value="<?php echo esc_attr( $texts['accept_necessary_btn'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Manage Preferences Button', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][show_preferences_btn]" value="<?php echo esc_attr( $texts['show_preferences_btn'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Save Preferences Button', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][save_preferences_btn]" value="<?php echo esc_attr( $texts['save_preferences_btn'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Close Icon Label', 'wp-eu-cookie-suite' ); ?></th>
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
						<th scope="row"><?php echo esc_html( $category['label'] ); ?> (<?php esc_html_e( 'Label', 'wp-eu-cookie-suite' ); ?>)</th>
						<td>
							<input type="text" name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][<?php echo esc_attr( $id ); ?>_label]" value="<?php echo esc_attr( $texts[ $id . '_label' ] ?? '' ); ?>" class="large-text">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( $category['label'] ); ?> (<?php esc_html_e( 'Description', 'wp-eu-cookie-suite' ); ?>)</th>
						<td>
							<textarea name="wpeu_cs_settings[banner_texts][<?php echo esc_attr( $current_lang ); ?>][<?php echo esc_attr( $id ); ?>_description]" rows="2" class="large-text"><?php echo esc_textarea( $texts[ $id . '_description' ] ?? '' ); ?></textarea>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<hr>
			<h3><?php esc_html_e( 'Appearance', 'wp-eu-cookie-suite' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Layout', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<select name="wpeu_cs_settings[banner_ui][layout]" id="wpeu-cs-banner-layout">
							<option value="box" <?php selected( $layout, 'box' ); ?>><?php esc_html_e( 'Box', 'wp-eu-cookie-suite' ); ?></option>
							<option value="bar" <?php selected( $layout, 'bar' ); ?>><?php esc_html_e( 'Bar', 'wp-eu-cookie-suite' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Position', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<select name="wpeu_cs_settings[banner_ui][position]" id="wpeu-cs-banner-position">
							<option value="bottom-left" <?php selected( $position, 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'wp-eu-cookie-suite' ); ?></option>
							<option value="bottom-center" <?php selected( $position, 'bottom-center' ); ?>><?php esc_html_e( 'Bottom Center', 'wp-eu-cookie-suite' ); ?></option>
							<option value="bottom-right" <?php selected( $position, 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'wp-eu-cookie-suite' ); ?></option>
							<option value="center" <?php selected( $position, 'center' ); ?>><?php esc_html_e( 'Center', 'wp-eu-cookie-suite' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Theme', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<select name="wpeu_cs_settings[banner_ui][theme]" id="wpeu-cs-banner-theme">
							<option value="light" <?php selected( $theme, 'light' ); ?>><?php esc_html_e( 'Light', 'wp-eu-cookie-suite' ); ?></option>
							<option value="dark" <?php selected( $theme, 'dark' ); ?>><?php esc_html_e( 'Dark', 'wp-eu-cookie-suite' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Primary Color', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[banner_ui][primary_color]" value="<?php echo esc_attr( $primary_color ); ?>" class="wpeu-cs-color-picker">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Custom CSS', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<textarea name="wpeu_cs_settings[banner_ui][custom_css]" rows="5" class="large-text code"><?php echo esc_textarea( $custom_css ); ?></textarea>
					</td>
				</tr>
			</table>

			<div class="wpeu-cs-preview-container">
				<h3><?php esc_html_e( 'Live Preview', 'wp-eu-cookie-suite' ); ?></h3>
				<div class="wpeu-cs-preview-frame-wrapper" style="border: 1px solid #ccd0d4; background: #f6f7f7;">
					<iframe id="wpeu-cs-banner-preview" src="about:blank" width="100%" height="400" frameborder="0"></iframe>
				</div>
				<p>
					<button type="button" id="wpeu-cs-refresh-preview" class="button button-secondary"><?php esc_html_e( 'Refresh Preview', 'wp-eu-cookie-suite' ); ?></button>
				</p>
			</div>

			<input type="hidden" id="wpeu_cs_preview_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpeu-cs-preview' ) ); ?>">

			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Render dashboard tab.
	 */
	private function render_dashboard_tab(): void {
		$settings     = get_option( 'wpeu_cs_settings', array() );
		$version      = $settings['version'] ?? WPEU_CS_VERSION;
		$blocker      = $settings['blocker_enabled'] ?? false;
		$consent_api  = defined( 'WP_CONSENT_API_VERSION' );

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
				<h3><?php esc_html_e( 'Plugin Version', 'wp-eu-cookie-suite' ); ?></h3>
				<p><?php echo esc_html( $version ); ?></p>
			</div>

			<div class="wpeu-cs-card">
				<h3><?php esc_html_e( 'Blocker Status', 'wp-eu-cookie-suite' ); ?></h3>
				<p>
					<span class="status <?php echo $blocker ? 'status-active' : 'status-inactive'; ?>">
						<?php echo $blocker ? esc_html__( 'Active', 'wp-eu-cookie-suite' ) : esc_html__( 'Inactive', 'wp-eu-cookie-suite' ); ?>
					</span>
				</p>
			</div>

			<div class="wpeu-cs-card">
				<h3><?php esc_html_e( 'Consent API Status', 'wp-eu-cookie-suite' ); ?></h3>
				<p>
					<span class="status status-active">
						<?php
						if ( defined( 'WP_CONSENT_API_VERSION' ) ) {
							esc_html_e( 'Active (native)', 'wp-eu-cookie-suite' );
						} else {
							esc_html_e( 'Active (polyfill)', 'wp-eu-cookie-suite' );
						}
						?>
					</span>
				</p>
			</div>

			<div class="wpeu-cs-card">
				<h3><?php esc_html_e( 'Active Block Rules', 'wp-eu-cookie-suite' ); ?></h3>
				<p><?php echo (int) $total_rules; ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render integrations tab.
	 */
	private function render_integrations_tab(): void {
		$settings             = get_option( 'wpeu_cs_settings', array() );
		$services             = ScriptRegistry::get_services();
		$enabled_services     = $settings['enabled_services'] ?? array();
		$enabled_integrations = $settings['enabled_integrations'] ?? array();
		$custom_rules         = $settings['custom_block_rules'] ?? '';
		$google_gcm           = $settings['google_consent_mode'] ?? true;
		$analytics_field      = $settings['theme_analytics_field'] ?? 'analytics';

		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'wpeu_cs_settings' );
			?>
			<input type="hidden" name="wpeu_cs_settings[active_tab]" value="integrations">

			<h2><?php esc_html_e( 'Google', 'wp-eu-cookie-suite' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Google Consent Mode v2', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpeu_cs_settings[google_consent_mode]" value="1" <?php checked( $google_gcm ); ?>>
							<?php esc_html_e( 'Send default denied consent to gtag before analytics/marketing tags load.', 'wp-eu-cookie-suite' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Third-Party Integrations', 'wp-eu-cookie-suite' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Theme Analytics (ACF)', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="wpeu_cs_settings[enabled_integrations][theme_analytics]" value="1" <?php checked( ! empty( $enabled_integrations['theme_analytics'] ) ); ?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Intercept ACF option field for analytics.', 'wp-eu-cookie-suite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'ACF Analytics Field Name', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<input type="text" name="wpeu_cs_settings[theme_analytics_field]" value="<?php echo esc_attr( $analytics_field ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'The name of the ACF field used for analytics/tracking code.', 'wp-eu-cookie-suite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Iframe Placeholders', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="wpeu_cs_settings[enabled_integrations][iframe_placeholder]" value="1" <?php checked( ! empty( $enabled_integrations['iframe_placeholder'] ) ); ?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Replace YouTube, Vimeo, and Google Maps iframes with placeholders.', 'wp-eu-cookie-suite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Contact Form 7 reCAPTCHA', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="wpeu_cs_settings[enabled_integrations][cf7_recaptcha]" value="1" <?php checked( ! empty( $enabled_integrations['cf7_recaptcha'] ) ); ?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Only load reCAPTCHA scripts after marketing consent.', 'wp-eu-cookie-suite' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Service Blocker', 'wp-eu-cookie-suite' ); ?></h2>
			<p><?php esc_html_e( 'Enable automatic script blocking for these popular services.', 'wp-eu-cookie-suite' ); ?></p>

			<table class="form-table" role="presentation">
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
					<th scope="row"><?php esc_html_e( 'Custom Block Rules', 'wp-eu-cookie-suite' ); ?></th>
					<td>
						<textarea name="wpeu_cs_settings[custom_block_rules]" rows="10" cols="50" class="large-text code"><?php echo esc_textarea( $custom_rules ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Enter one pattern per line to block custom scripts. Use -url- prefix to match only the src attribute.', 'wp-eu-cookie-suite' ); ?><br>
							<?php esc_html_e( 'Example: analytics.js or -url-my-script.js', 'wp-eu-cookie-suite' ); ?>
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
			<h2><?php esc_html_e( 'Cookie Scanner', 'wp-eu-cookie-suite' ); ?></h2>
			<p><?php esc_html_e( 'Scan your website to detect cookies and third-party scripts.', 'wp-eu-cookie-suite' ); ?></p>

			<div class="wpeu-cs-scanner-actions">
				<button type="button" id="wpeu-cs-start-scan" class="button button-primary">
					<?php esc_html_e( 'Start Scan', 'wp-eu-cookie-suite' ); ?>
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
							<?php esc_html_e( 'Import to Inventory', 'wp-eu-cookie-suite' ); ?>
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
			echo '<p class="wpeu-cs-no-results">' . esc_html__( 'No scan results found. Start a scan to find cookies and scripts.', 'wp-eu-cookie-suite' ) . '</p>';
			return;
		}

		?>
		<h3><?php esc_html_e( 'Detected Items', 'wp-eu-cookie-suite' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name / Domain', 'wp-eu-cookie-suite' ); ?></th>
					<th><?php esc_html_e( 'Type', 'wp-eu-cookie-suite' ); ?></th>
					<th><?php esc_html_e( 'Detected Category', 'wp-eu-cookie-suite' ); ?></th>
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
		$action = $_GET['action'] ?? '';
		$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

		if ( 'edit' === $action || 'add' === $action ) {
			$this->render_cookie_form( $id );
			return;
		}

		$table = new CookieListTable();
		$table->prepare_items();

		?>
		<div class="wpeu-cs-cookies-header">
			<h2><?php esc_html_e( 'Cookie Inventory', 'wp-eu-cookie-suite' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=cookies&action=add' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'wp-eu-cookie-suite' ); ?>
			</a>
		</div>

		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
			<input type="hidden" name="tab" value="cookies">
			<?php $table->search_box( __( 'Search Cookies', 'wp-eu-cookie-suite' ), 'search-id' ); ?>
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

		$title = $id ? __( 'Edit Cookie', 'wp-eu-cookie-suite' ) : __( 'Add New Cookie', 'wp-eu-cookie-suite' );
		?>
		<h2><?php echo esc_html( $title ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=cookies' ) ); ?>">
			<?php wp_nonce_field( 'wpeu_cs_save_cookie', 'wpeu_cs_cookie_nonce' ); ?>
			<input type="hidden" name="action" value="wpeu_cs_save_cookie">
			<input type="hidden" name="cookie[id]" value="<?php echo (int) $id; ?>">

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="cookie_name"><?php esc_html_e( 'Name', 'wp-eu-cookie-suite' ); ?></label></th>
					<td><input type="text" name="cookie[name]" id="cookie_name" value="<?php echo esc_attr( $cookie['name'] ?? '' ); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_domain"><?php esc_html_e( 'Domain', 'wp-eu-cookie-suite' ); ?></label></th>
					<td><input type="text" name="cookie[domain]" id="cookie_domain" value="<?php echo esc_attr( $cookie['domain'] ?? '' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_category"><?php esc_html_e( 'Category', 'wp-eu-cookie-suite' ); ?></label></th>
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
					<th scope="row"><label for="cookie_service"><?php esc_html_e( 'Service', 'wp-eu-cookie-suite' ); ?></label></th>
					<td><input type="text" name="cookie[service]" id="cookie_service" value="<?php echo esc_attr( $cookie['service'] ?? '' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_duration"><?php esc_html_e( 'Duration', 'wp-eu-cookie-suite' ); ?></label></th>
					<td><input type="text" name="cookie[duration]" id="cookie_duration" value="<?php echo esc_attr( $cookie['duration'] ?? '' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_description"><?php esc_html_e( 'Description', 'wp-eu-cookie-suite' ); ?></label></th>
					<td><textarea name="cookie[description]" id="cookie_description" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $cookie['description'] ?? '' ); ?></textarea></td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=cookies' ) ); ?>">
				&larr; <?php esc_html_e( 'Back to Inventory', 'wp-eu-cookie-suite' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render tools tab.
	 */
	private function render_tools_tab(): void {
		$settings     = get_option( 'wpeu_cs_settings', array() );
		$locales      = BannerTexts::get_locales();
		$current_lang = isset( $_GET['lang'] ) && array_key_exists( $_GET['lang'], $locales ) ? $_GET['lang'] : BannerTexts::get_active_locale();

		$policy_texts = $settings['policy_texts'][ $current_lang ] ?? array();
		$intro        = $policy_texts['intro'] ?? '';
		$template     = $policy_texts['template'] ?? BannerTexts::get_default_policy_template( $current_lang );

		?>
		<h2><?php esc_html_e( 'Tools', 'wp-eu-cookie-suite' ); ?></h2>

		<div class="notice notice-info inline">
			<p><strong><?php esc_html_e( 'Disclaimer:', 'wp-eu-cookie-suite' ); ?></strong> <?php esc_html_e( 'This plugin provides tools for cookie compliance but does not constitute legal advice.', 'wp-eu-cookie-suite' ); ?></p>
		</div>

		<div class="wpeu-cs-lang-selector">
			<ul class="subsubsub">
				<?php
				$i = 0;
				foreach ( $locales as $code => $label ) :
					$url     = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools&lang=' . $code );
					$current = $current_lang === $code ? 'current' : '';
					echo '<li><a href="' . esc_url( $url ) . '" class="' . esc_attr( $current ) . '">' . esc_html( $label ) . '</a>' . ( $i < count( $locales ) - 1 ? ' | ' : '' ) . '</li>';
					$i++;
				endforeach;
				?>
			</ul>
			<br class="clear">
		</div>

		<form method="post" action="options.php">
			<?php settings_fields( 'wpeu_cs_settings' ); ?>
			<input type="hidden" name="wpeu_cs_settings[active_tab]" value="tools">

			<div class="card">
				<h3><?php printf( esc_html__( 'Cookie Policy Settings (%s)', 'wp-eu-cookie-suite' ), esc_html( $locales[ $current_lang ] ) ); ?></h3>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="policy_intro"><?php esc_html_e( 'Policy Intro Text', 'wp-eu-cookie-suite' ); ?></label></th>
						<td>
							<textarea name="wpeu_cs_settings[policy_texts][<?php echo esc_attr( $current_lang ); ?>][intro]" id="policy_intro" rows="5" class="large-text"><?php echo esc_textarea( $intro ); ?></textarea>
							<p class="description"><?php esc_html_e( 'This text is displayed at the beginning of your cookie policy.', 'wp-eu-cookie-suite' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="policy_template"><?php esc_html_e( 'Policy Template', 'wp-eu-cookie-suite' ); ?></label></th>
						<td>
							<textarea name="wpeu_cs_settings[policy_texts][<?php echo esc_attr( $current_lang ); ?>][template]" id="policy_template" rows="10" class="large-text code"><?php echo esc_textarea( $template ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'The template for the [wpeu_cookie_policy] shortcode.', 'wp-eu-cookie-suite' ); ?><br>
								<?php esc_html_e( 'Available placeholders: {{intro}}, {{table}}, {{content}}', 'wp-eu-cookie-suite' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</div>
		</form>

		<div class="card">
			<h3><?php esc_html_e( 'Export Cookie Inventory', 'wp-eu-cookie-suite' ); ?></h3>
			<p><?php esc_html_e( 'Download your cookie inventory as a CSV file.', 'wp-eu-cookie-suite' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools' ) ); ?>">
				<?php wp_nonce_field( 'wpeu_cs_export_csv', 'wpeu_cs_export_nonce' ); ?>
				<input type="hidden" name="action" value="wpeu_cs_export_csv">
				<?php submit_button( __( 'Download CSV', 'wp-eu-cookie-suite' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
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

		// Mock settings for preview
		add_filter( 'option_wpeu_cs_settings', function ( $settings ) {
			if ( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ) {
				if ( isset( $_POST['settings']['banner_ui'] ) ) {
					$settings['banner_ui'] = $_POST['settings']['banner_ui'];
				}
				if ( isset( $_POST['settings']['banner_texts'] ) ) {
					$settings['banner_texts'] = $_POST['settings']['banner_texts'];
				}
			}
			return $settings;
		} );

		$banner = new \WPEU\CookieSuite\Frontend\Banner();

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<?php
			wp_head();
			?>
			<style>
				body { background: #f0f0f1 !important; margin: 0; padding: 0; min-height: 400px; }
				#cc-main { position: relative !important; z-index: 1 !important; }
				.cc--resizer { display: none !important; }
			</style>
		</head>
		<body>
			<div id="wpeu-cs-preview-content">
				<?php
				$banner->render_config();
				?>
			</div>
			<?php wp_footer(); ?>
			<script>
				window.addEventListener('load', function() {
					if (window.CookieConsent) {
						window.CookieConsent.show(true);
					}
				});
			</script>
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
		echo '<p>' . sprintf( esc_html__( 'Implemented in task %s', 'wp-eu-cookie-suite' ), esc_html( $task_id ) ) . '</p>';
	}
}
