<?php
/**
 * Google Consent Mode v2 implementation.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Consent;

/**
 * GoogleConsentMode class.
 */
final class GoogleConsentMode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings = get_option( 'wpeu_cs_settings', array() );
		$enabled  = $settings['google_consent_mode'] ?? true;
		if ( ! $enabled ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'inject_gtag_consent_default' ), 0 );
		add_action( 'wp_head', array( $this, 'inject_gtag_consent_update' ), 9 );
	}

	/**
	 * Inject gtag consent default snippet.
	 */
	public function inject_gtag_consent_default(): void {
		?>
		<!-- Google Consent Mode v2 by WP EU Cookie Suite -->
		<script type="text/javascript">
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('consent', 'default', {
				'ad_storage': 'denied',
				'analytics_storage': 'denied',
				'ad_user_data': 'denied',
				'ad_personalization': 'denied',
				'wait_for_update': 500
			});
		</script>
		<?php
	}

	/**
	 * Inject gtag consent update script.
	 */
	public function inject_gtag_consent_update(): void {
		?>
		<script type="text/javascript">
			document.addEventListener('wpeu-consent-updated', function(e) {
				const consent = e.detail;
				if (typeof gtag === 'function') {
					gtag('consent', 'update', {
						'ad_storage': consent.marketing ? 'granted' : 'denied',
						'analytics_storage': consent.statistics ? 'granted' : 'denied',
						'ad_user_data': consent.marketing ? 'granted' : 'denied',
						'ad_personalization': consent.marketing ? 'granted' : 'denied'
					});
				}
			});
		</script>
		<?php
	}
}
