<?php
/**
 * Google Site Kit integration.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Integrations;

/**
 * Ensures Site Kit analytics respects consent.
 */
final class GoogleSiteKit {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! defined( 'GOOGLESITEKIT_VERSION' ) ) {
			return;
		}

		if ( ! $this->is_integration_enabled() ) {
			return;
		}

		add_filter( 'googlesitekit_analytics-4_tag_block_on_consent', array( $this, 'block_analytics_tag' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_without_consent' ), 9999 );
	}

	/**
	 * Whether Site Kit integration is enabled (default on when Site Kit is active).
	 */
	private function is_integration_enabled(): bool {
		$settings     = get_option( 'wpeu_cs_settings', array() );
		$integrations = $settings['enabled_integrations'] ?? array();

		if ( ! array_key_exists( 'google_site_kit', $integrations ) ) {
			return true;
		}

		return ! empty( $integrations['google_site_kit'] );
	}

	/**
	 * Block Site Kit GA4 tag until statistics consent.
	 *
	 * @param bool $blocked Current blocked state.
	 * @return bool
	 */
	public function block_analytics_tag( bool $blocked ): bool {
		if ( $this->should_block_analytics() ) {
			return true;
		}

		return $blocked;
	}

	/**
	 * Dequeue Site Kit gtag scripts without consent.
	 */
	public function dequeue_without_consent(): void {
		if ( ! $this->should_block_analytics() ) {
			return;
		}

		foreach ( array( 'google_gtagjs', 'googlesitekit-consent-mode' ) as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}

	/**
	 * Whether analytics should be blocked now.
	 */
	private function should_block_analytics(): bool {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		return ! wpeu_cs_user_has_consent( 'statistics' );
	}
}
