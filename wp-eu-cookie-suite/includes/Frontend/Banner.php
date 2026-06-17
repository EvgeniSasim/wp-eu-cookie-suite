<?php
/**
 * Frontend Banner class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Frontend;

use WPEU\CookieSuite\Consent\Categories;
use WPEU\CookieSuite\Consent\BannerTexts;

/**
 * Handles the cookie consent banner on the frontend.
 */
final class Banner {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_config' ), 20 );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets(): void {
		if ( ( is_admin() && ! defined( 'WPEU_CS_PREVIEW' ) ) || is_login() ) {
			return;
		}

		$script_path = WPEU_CS_PATH . 'assets/js/cookieconsent.bundle.js';
		$style_path  = WPEU_CS_PATH . 'assets/css/cookieconsent.bundle.css';

		if ( ! is_readable( $script_path ) || ! is_readable( $style_path ) ) {
			return;
		}

		wp_enqueue_style(
			'wpeu-cs-cookieconsent',
			WPEU_CS_URL . 'assets/css/cookieconsent.bundle.css',
			array(),
			WPEU_CS_VERSION
		);

		wp_enqueue_style(
			'wpeu-cs-frontend',
			WPEU_CS_URL . 'assets/css/frontend.css',
			array(),
			WPEU_CS_VERSION
		);

		wp_enqueue_script(
			'wpeu-cs-cookieconsent',
			WPEU_CS_URL . 'assets/js/cookieconsent.bundle.js',
			array(),
			WPEU_CS_VERSION,
			true
		);
	}

	/**
	 * Render CookieConsent configuration.
	 */
	public function render_config(): void {
		if ( ( is_admin() && ! defined( 'WPEU_CS_PREVIEW' ) ) || is_login() ) {
			return;
		}

		if ( ! wp_script_is( 'wpeu-cs-cookieconsent', 'enqueued' ) ) {
			return;
		}

		$config    = $this->get_config();
		$settings  = get_option( 'wpeu_cs_settings', array() );
		$banner_ui = $settings['banner_ui'] ?? array();
		$theme     = $banner_ui['theme'] ?? 'light';
		$primary   = $banner_ui['primary_color'] ?? '#30363c';
		$custom_css = $banner_ui['custom_css'] ?? '';

		if ( 'dark' === $theme ) {
			?>
			<script type="text/javascript">
				document.documentElement.classList.add('cc--darkmode');
			</script>
			<?php
		}
		?>
		<style type="text/css">
			:root {
				--cc-btn-primary-bg: <?php echo esc_html( $primary ); ?>;
				--cc-btn-primary-border-color: <?php echo esc_html( $primary ); ?>;
				--cc-toggle-on-bg: <?php echo esc_html( $primary ); ?>;
				--cc-link-color: <?php echo esc_html( $primary ); ?>;
			}
			<?php echo $custom_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>
		<script type="text/javascript">
			window.addEventListener('load', function () {
				const cc = window.CookieConsent;
				if (!cc || typeof cc.run !== 'function') {
					return;
				}

				// WP Consent API Polyfill
				if (typeof window.wp_set_consent !== 'function') {
					window.wp_set_consent = function (category, status) {
						// Polyfill for wp_set_consent if not provided by wp-consent-api
						console.debug('WP Consent API (polyfill):', category, status);
						document.dispatchEvent(new CustomEvent('wp_api_set_consent', {
							detail: {
								category: category,
								status: status
							}
						}));
					};
				}

				cc.run(<?php echo wp_json_encode( $config ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>);

				const syncWpeuCookies = function () {
					const categories = Object.keys(cc.getConfig().categories);
					const consentData = {};
					const mapping = {
						'necessary': 'functional',
						'statistics': 'statistics',
						'marketing': 'marketing',
						'preferences': 'preferences'
					};

					categories.forEach(function (cat) {
						const accepted = cc.acceptedCategory(cat);
						consentData[cat] = accepted;
						document.cookie = 'wpeu_' + cat + '=' + (accepted ? '1' : '0') + '; path=/; max-age=31536000; SameSite=Lax';

						// WP Consent API integration
						if (mapping[cat]) {
							window.wp_set_consent(mapping[cat], accepted ? 'allow' : 'deny');
						}
					});

					document.cookie = 'wpeu_consent=' + encodeURIComponent(JSON.stringify(consentData)) + '; path=/; max-age=31536000; SameSite=Lax';
					document.dispatchEvent(new CustomEvent('wpeu-consent-updated', { detail: consentData }));
				};

				cc.onConsent(syncWpeuCookies);
				cc.onChange(syncWpeuCookies);
			});
		</script>
		<?php
	}

	/**
	 * Build CookieConsent config array.
	 *
	 * @return array<string, mixed>
	 */
	private function get_config(): array {
		$settings           = get_option( 'wpeu_cs_settings', array() );
		$all_categories     = Categories::get_all();
		$enabled_categories = $settings['enabled_categories'] ?? array( 'preferences', 'statistics', 'marketing' );
		$show_reject_all    = $settings['show_reject_all'] ?? true;
		$privacy_url        = $settings['privacy_policy_url'] ?? '';
		$cookie_url         = $settings['cookie_policy_url'] ?? '';
		$eu_mode            = $settings['eu_mode'] ?? true;

		$banner_ui = $settings['banner_ui'] ?? array();
		$layout    = $banner_ui['layout'] ?? 'box';
		$position  = str_replace( '-', ' ', $banner_ui['position'] ?? 'bottom-right' );

		$locale = BannerTexts::get_active_locale();
		$texts  = BannerTexts::get_strings( $locale );

		$categories_config = array();
		$sections          = array(
			array(
				'title'       => $texts['preferences_intro_title'],
				'description' => $texts['preferences_intro_description'],
			),
		);

		foreach ( $all_categories as $id => $category ) {
			if ( 'necessary' !== $id && ! in_array( $id, $enabled_categories, true ) ) {
				continue;
			}

			$categories_config[ $id ] = array(
				'readOnly' => $category['read_only'] ?? false,
				'enabled'  => $category['enabled'] ?? false,
			);

			$sections[] = array(
				'title'          => $texts[ $id . '_label' ] ?? $category['label'],
				'description'    => $texts[ $id . '_description' ] ?? $category['description'],
				'linkedCategory' => $id,
			);
		}

		$footer_links = array();
		if ( ! empty( $privacy_url ) ) {
			$footer_links[] = '<a href="' . esc_url( $privacy_url ) . '">' . __( 'Privacy Policy', 'wp-eu-cookie-suite' ) . '</a>';
		}
		if ( ! empty( $cookie_url ) ) {
			$footer_links[] = '<a href="' . esc_url( $cookie_url ) . '">' . __( 'Cookie Policy', 'wp-eu-cookie-suite' ) . '</a>';
		}

		$footer_html = implode( ' | ', $footer_links );

		return array(
			'mode'       => $eu_mode ? 'opt-in' : 'opt-out',
			'guiOptions' => array(
				'consentModal' => array(
					'layout'             => $layout,
					'position'           => $position,
					'flipButtons'        => false,
					'equalWeightButtons' => true,
				),
				'preferencesModal' => array(
					'layout'             => 'box',
					'position'           => 'right',
					'flipButtons'        => false,
					'equalWeightButtons' => true,
				),
			),
			'categories' => $categories_config,
			'language' => array(
				'default'      => $locale,
				'translations' => array(
					$locale => array(
						'consentModal' => array(
							'title'              => $texts['consent_modal_title'],
							'description'        => $texts['consent_modal_description'],
							'acceptAllBtn'       => $texts['accept_all_btn'],
							'acceptNecessaryBtn' => $show_reject_all ? $texts['accept_necessary_btn'] : '',
							'showPreferencesBtn' => $texts['show_preferences_btn'],
							'footer'             => $footer_html,
						),
						'preferencesModal' => array(
							'title'              => $texts['preferences_modal_title'],
							'acceptAllBtn'       => $texts['accept_all_btn'],
							'acceptNecessaryBtn' => $show_reject_all ? $texts['accept_necessary_btn'] : '',
							'savePreferencesBtn' => $texts['save_preferences_btn'],
							'closeIconLabel'     => $texts['close_icon_label'],
							'sections'           => $sections,
						),
					),
				),
			),
		);
	}
}
