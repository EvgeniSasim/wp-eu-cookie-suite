<?php
/**
 * WP Consent API Bridge.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Consent;

/**
 * WpConsentBridge class.
 */
final class WpConsentBridge {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'wp_consent_api_registered_wp-eu-cookie-suite', '__return_true' );
		$this->register_polyfills();
	}

	/**
	 * Register PHP polyfills if wp-consent-api is not active.
	 */
	private function register_polyfills(): void {
		if ( ! function_exists( 'wp_has_consent' ) ) {
			require_once __DIR__ . '/polyfills.php';
		}
	}
}
