<?php
/**
 * Admin UI and settings management.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Admin;

use WPEU\CookieSuite\Consent\Categories;
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
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
		$sanitized = array();

		$sanitized['blocker_enabled']        = isset( $input['blocker_enabled'] );
		$sanitized['eu_mode']                = isset( $input['eu_mode'] );
		$sanitized['keep_data_on_uninstall'] = isset( $input['keep_data_on_uninstall'] );
		$sanitized['show_reject_all']        = isset( $input['show_reject_all'] );

		if ( isset( $input['enabled_categories'] ) && is_array( $input['enabled_categories'] ) ) {
			$sanitized['enabled_categories'] = array_map( 'sanitize_text_field', $input['enabled_categories'] );
		} else {
			$sanitized['enabled_categories'] = array();
		}

		if ( isset( $input['enabled_services'] ) && is_array( $input['enabled_services'] ) ) {
			$sanitized['enabled_services'] = array_map(
				function ( $val ) {
					return (bool) $val;
				},
				$input['enabled_services']
			);
		} else {
			$sanitized['enabled_services'] = array();
		}

		$sanitized['custom_block_rules'] = isset( $input['custom_block_rules'] ) ? sanitize_textarea_field( $input['custom_block_rules'] ) : '';

		$sanitized['privacy_policy_url'] = isset( $input['privacy_policy_url'] ) ? esc_url_raw( $input['privacy_policy_url'] ) : '';
		$sanitized['cookie_policy_url']  = isset( $input['cookie_policy_url'] ) ? esc_url_raw( $input['cookie_policy_url'] ) : '';

		// Preserve version if already set
		$old_settings = get_option( 'wpeu_cs_settings', array() );
		if ( isset( $old_settings['version'] ) ) {
			$sanitized['version'] = $old_settings['version'];
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

		wp_enqueue_style(
			'wpeu-cs-admin',
			WPEU_CS_URL . 'assets/css/admin.css',
			array(),
			WPEU_CS_VERSION
		);

		wp_enqueue_script(
			'wpeu-cs-admin',
			WPEU_CS_URL . 'assets/js/admin.js',
			array( 'jquery' ),
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
						$this->render_placeholder_tab( 'CC-10' );
						break;
					case 'scanner':
						$this->render_placeholder_tab( 'CC-09' );
						break;
					case 'integrations':
						$this->render_integrations_tab();
						break;
					case 'tools':
						$this->render_placeholder_tab( 'CC-15' );
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

		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'wpeu_cs_settings' );
			?>

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
		$settings         = get_option( 'wpeu_cs_settings', array() );
		$services         = ScriptRegistry::get_services();
		$enabled_services = $settings['enabled_services'] ?? array();
		$custom_rules     = $settings['custom_block_rules'] ?? '';

		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'wpeu_cs_settings' );
			?>

			<h2><?php esc_html_e( 'Service Integrations', 'wp-eu-cookie-suite' ); ?></h2>
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
	 * Render placeholder tab.
	 *
	 * @param string $task_id Task ID.
	 */
	private function render_placeholder_tab( string $task_id ): void {
		echo '<p>' . sprintf( esc_html__( 'Implemented in task %s', 'wp-eu-cookie-suite' ), esc_html( $task_id ) ) . '</p>';
	}
}
