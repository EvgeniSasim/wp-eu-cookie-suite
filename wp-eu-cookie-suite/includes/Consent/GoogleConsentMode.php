<?php
/**
 * Google Consent Mode v2.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Consent;

/**
 * Injects gtag consent defaults and updates after banner choices.
 */
final class GoogleConsentMode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'print_default_consent' ), 0 );
		add_action( 'wp_footer', array( $this, 'print_consent_listener' ), 5 );
	}

	/**
	 * Whether Google Consent Mode is enabled.
	 */
	private function is_enabled(): bool {
		$settings = get_option( 'wpeu_cs_settings', array() );
		return ! isset( $settings['google_consent_mode'] ) || ! empty( $settings['google_consent_mode'] );
	}

	/**
	 * Print default denied consent before any Google tags.
	 */
	public function print_default_consent(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		?>
		<script type="text/javascript">
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('consent', 'default', {
				ad_storage: 'denied',
				analytics_storage: 'denied',
				ad_user_data: 'denied',
				ad_personalization: 'denied',
				wait_for_update: 500
			});
		</script>
		<?php
	}

	/**
	 * Update consent when the user changes banner preferences.
	 */
	public function print_consent_listener(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		?>
		<script type="text/javascript">
			(function () {
				function updateGoogleConsent(detail) {
					if (typeof gtag !== 'function' || !detail) {
						return;
					}
					gtag('consent', 'update', {
						analytics_storage: detail.statistics ? 'granted' : 'denied',
						ad_storage: detail.marketing ? 'granted' : 'denied',
						ad_user_data: detail.marketing ? 'granted' : 'denied',
						ad_personalization: detail.marketing ? 'granted' : 'denied'
					});
				}

				document.addEventListener('wpeu-consent-updated', function (event) {
					updateGoogleConsent(event.detail || {});
				});

				window.addEventListener('load', function () {
					if (typeof window.CookieConsent !== 'undefined' && typeof window.CookieConsent.acceptedCategory === 'function') {
						updateGoogleConsent({
							statistics: window.CookieConsent.acceptedCategory('statistics'),
							marketing: window.CookieConsent.acceptedCategory('marketing')
						});
					}
				});
			})();
		</script>
		<?php
	}
}
