<?php
/**
 * Frontend Banner class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Frontend;

use WPEU\CookieSuite\Consent\Categories;

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
		if ( is_admin() || is_login() ) {
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
		if ( is_admin() || is_login() ) {
			return;
		}

		if ( ! wp_script_is( 'wpeu-cs-cookieconsent', 'enqueued' ) ) {
			return;
		}

		$config = $this->get_config();
		?>
		<script type="text/javascript">
			window.addEventListener('load', function () {
				const cc = window.CookieConsent;
				if (!cc || typeof cc.run !== 'function') {
					return;
				}

				cc.run(<?php echo wp_json_encode( $config ); ?>);

				const syncWpeuCookies = function () {
					const categories = Object.keys(cc.getConfig().categories);
					const consentData = {};

					categories.forEach(function (cat) {
						const accepted = cc.acceptedCategory(cat);
						consentData[cat] = accepted;
						document.cookie = 'wpeu_' + cat + '=' + (accepted ? '1' : '0') + '; path=/; max-age=31536000; SameSite=Lax';
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

		$categories_config = array();
		$sections          = array(
			array(
				'title'       => __( 'Cookie usage', 'wp-eu-cookie-suite' ),
				'description' => __( 'Choose which cookies you allow. You can change these settings at any time.', 'wp-eu-cookie-suite' ),
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
				'title'          => $category['label'],
				'description'    => $category['description'],
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
					'layout'             => 'box',
					'position'           => 'bottom right',
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
				'default'      => 'en',
				'translations' => array(
					'en' => array(
						'consentModal' => array(
							'title'              => __( 'We use cookies', 'wp-eu-cookie-suite' ),
							'description'        => __( 'We use cookies to ensure you get the best experience on our website. You can accept all, reject non-essential cookies, or manage your preferences.', 'wp-eu-cookie-suite' ),
							'acceptAllBtn'       => __( 'Accept all', 'wp-eu-cookie-suite' ),
							'acceptNecessaryBtn' => $show_reject_all ? __( 'Reject all', 'wp-eu-cookie-suite' ) : '',
							'showPreferencesBtn' => __( 'Manage preferences', 'wp-eu-cookie-suite' ),
							'footer'             => $footer_html,
						),
						'preferencesModal' => array(
							'title'              => __( 'Consent preferences', 'wp-eu-cookie-suite' ),
							'acceptAllBtn'       => __( 'Accept all', 'wp-eu-cookie-suite' ),
							'acceptNecessaryBtn' => $show_reject_all ? __( 'Reject all', 'wp-eu-cookie-suite' ) : '',
							'savePreferencesBtn' => __( 'Save preferences', 'wp-eu-cookie-suite' ),
							'closeIconLabel'     => __( 'Close', 'wp-eu-cookie-suite' ),
							'sections'           => $sections,
						),
					),
				),
			),
		);
	}
}
