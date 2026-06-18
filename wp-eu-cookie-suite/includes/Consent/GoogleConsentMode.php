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
				functionality_storage: 'denied',
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

		$integration_map = Categories::get_integration_map_by_slug();
		$banner_slugs    = array_keys( Categories::get_enabled_for_banner() );
		?>
		<script type="text/javascript">
			(function () {
				const integrationMap = <?php echo wp_json_encode( $integration_map ); ?>;
				const bannerSlugs = <?php echo wp_json_encode( array_values( $banner_slugs ) ); ?>;

				function resolveGcmFlags(detail) {
					const flags = {
						statistics: false,
						marketing: false,
						preferences: false
					};

					if (!detail || typeof detail !== 'object') {
						return flags;
					}

					Object.keys(detail).forEach(function (slug) {
						if (!detail[slug]) {
							return;
						}
						const mapped = integrationMap[slug] || slug;
						if (mapped === 'statistics') {
							flags.statistics = true;
						}
						if (mapped === 'marketing') {
							flags.marketing = true;
						}
						if (mapped === 'preferences' || mapped === 'necessary') {
							flags.preferences = true;
						}
					});

					return flags;
				}

				function updateGoogleConsent(detail) {
					if (typeof gtag !== 'function') {
						return;
					}
					const flags = resolveGcmFlags(detail);
					gtag('consent', 'update', {
						analytics_storage: flags.statistics ? 'granted' : 'denied',
						ad_storage: flags.marketing ? 'granted' : 'denied',
						ad_user_data: flags.marketing ? 'granted' : 'denied',
						ad_personalization: flags.marketing ? 'granted' : 'denied',
						functionality_storage: flags.preferences ? 'granted' : 'denied'
					});
				}

				function buildDetailFromCookieConsent() {
					const detail = {};
					if (typeof window.CookieConsent === 'undefined' || typeof window.CookieConsent.acceptedCategory !== 'function') {
						return detail;
					}
					bannerSlugs.forEach(function (slug) {
						detail[slug] = window.CookieConsent.acceptedCategory(slug);
					});
					return detail;
				}

				document.addEventListener('wpeu-consent-updated', function (event) {
					updateGoogleConsent(event.detail || {});
				});

				window.addEventListener('load', function () {
					updateGoogleConsent(buildDetailFromCookieConsent());
				});
			})();
		</script>
		<?php
	}
}
