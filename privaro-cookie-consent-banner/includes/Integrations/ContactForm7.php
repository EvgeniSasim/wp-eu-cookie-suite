<?php
/**
 * Contact Form 7 integration.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEU\CookieSuite\Settings\SettingsRepository;

/**
 * Ensures CF7 reCAPTCHA respects consent.
 */
final class ContactForm7 {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings = SettingsRepository::instance()->get_effective_settings();
		$enabled  = $settings['enabled_integrations']['cf7_recaptcha'] ?? false;

		if ( ! $enabled ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_block_recaptcha' ), 9999 );
	}

	/**
	 * Dequeue reCAPTCHA if consent is not granted.
	 */
	public function maybe_block_recaptcha(): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( ! wpeu_cs_user_has_consent( 'marketing' ) ) {
			wp_dequeue_script( 'google-recaptcha' );
			wp_dequeue_script( 'wpcf7-recaptcha' );
			wp_dequeue_style( 'wpcf7-recaptcha' );
		}
	}
}
