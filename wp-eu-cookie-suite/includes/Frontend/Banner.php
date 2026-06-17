<?php
/**
 * Frontend Banner class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Frontend;

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
					const categories = ['necessary', 'preferences', 'statistics', 'marketing'];
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
		return array(
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
			'categories' => array(
				'necessary'   => array(
					'readOnly' => true,
					'enabled'  => true,
				),
				'preferences' => array(),
				'statistics'  => array(),
				'marketing'   => array(),
			),
			'language' => array(
				'default'      => 'en',
				'translations' => array(
					'en' => array(
						'consentModal' => array(
							'title'              => __( 'We use cookies', 'wp-eu-cookie-suite' ),
							'description'        => __( 'We use cookies to ensure you get the best experience on our website. You can accept all, reject non-essential cookies, or manage your preferences.', 'wp-eu-cookie-suite' ),
							'acceptAllBtn'       => __( 'Accept all', 'wp-eu-cookie-suite' ),
							'acceptNecessaryBtn' => __( 'Reject all', 'wp-eu-cookie-suite' ),
							'showPreferencesBtn' => __( 'Manage preferences', 'wp-eu-cookie-suite' ),
							'footer'             => '',
						),
						'preferencesModal' => array(
							'title'              => __( 'Consent preferences', 'wp-eu-cookie-suite' ),
							'acceptAllBtn'       => __( 'Accept all', 'wp-eu-cookie-suite' ),
							'acceptNecessaryBtn' => __( 'Reject all', 'wp-eu-cookie-suite' ),
							'savePreferencesBtn' => __( 'Save preferences', 'wp-eu-cookie-suite' ),
							'closeIconLabel'     => __( 'Close', 'wp-eu-cookie-suite' ),
							'sections'           => array(
								array(
									'title'       => __( 'Cookie usage', 'wp-eu-cookie-suite' ),
									'description' => __( 'Choose which cookies you allow. You can change these settings at any time.', 'wp-eu-cookie-suite' ),
								),
								array(
									'title'          => __( 'Strictly necessary', 'wp-eu-cookie-suite' ),
									'description'    => __( 'Required for the website to function. These cannot be disabled.', 'wp-eu-cookie-suite' ),
									'linkedCategory' => 'necessary',
								),
								array(
									'title'          => __( 'Preferences', 'wp-eu-cookie-suite' ),
									'description'    => __( 'Remember your settings and choices.', 'wp-eu-cookie-suite' ),
									'linkedCategory' => 'preferences',
								),
								array(
									'title'          => __( 'Statistics', 'wp-eu-cookie-suite' ),
									'description'    => __( 'Help us understand how visitors use our website.', 'wp-eu-cookie-suite' ),
									'linkedCategory' => 'statistics',
								),
								array(
									'title'          => __( 'Marketing', 'wp-eu-cookie-suite' ),
									'description'    => __( 'Used to deliver relevant ads and measure campaign performance.', 'wp-eu-cookie-suite' ),
									'linkedCategory' => 'marketing',
								),
							),
						),
					),
				),
			),
		);
	}
}
