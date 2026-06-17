<?php
/**
 * Google Site Kit integration.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Integrations;

/**
 * GoogleSiteKit class.
 */
final class GoogleSiteKit {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings = get_option( 'wpeu_cs_settings', array() );
		$enabled  = $settings['google_consent_mode'] ?? true;
		if ( ! $enabled ) {
			return;
		}

		// Site Kit hooks for consent.
		add_filter( 'googlesitekit_analytics_4_tag_blocked', array( $this, 'maybe_block_tag' ) );
		add_filter( 'googlesitekit_tag_gateway_consent_mode', array( $this, 'enable_consent_mode' ) );
	}

	/**
	 * Block Site Kit tag if consent is not granted for statistics.
	 *
	 * @param bool $blocked Whether the tag is already blocked.
	 * @return bool
	 */
	public function maybe_block_tag( bool $blocked ): bool {
		if ( ! function_exists( 'wp_has_consent' ) ) {
			return $blocked;
		}

		if ( ! wp_has_consent( 'statistics' ) ) {
			return true;
		}

		return $blocked;
	}

	/**
	 * Enable Site Kit's native consent mode support if available.
	 *
	 * @param bool $enabled Whether consent mode is enabled.
	 * @return bool
	 */
	public function enable_consent_mode( bool $enabled ): bool {
		// Only enable if Site Kit is active and GCM is enabled in our settings.
		$settings = get_option( 'wpeu_cs_settings', array() );
		if ( empty( $settings['google_consent_mode'] ) ) {
			return $enabled;
		}

		if ( class_exists( '\Google\Site_Kit\Plugin' ) ) {
			return true;
		}

		return $enabled;
	}
}
