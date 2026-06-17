<?php
/**
 * Banner texts model for multi-language support.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Consent;

/**
 * BannerTexts class.
 */
final class BannerTexts {

	/**
	 * Get default strings for a locale.
	 *
	 * @param string $locale Locale (e.g., 'en', 'de').
	 * @return array<string, string>
	 */
	public static function get_defaults( string $locale = 'en' ): array {
		$defaults = array(
			'en' => array(
				'consent_modal_title'       => __( 'We use cookies', 'wp-eu-cookie-suite' ),
				'consent_modal_description' => __( 'We use cookies to ensure you get the best experience on our website. You can accept all, reject non-essential cookies, or manage your preferences.', 'wp-eu-cookie-suite' ),
				'preferences_modal_title'   => __( 'Consent preferences', 'wp-eu-cookie-suite' ),
				'preferences_intro_title'   => __( 'Cookie usage', 'wp-eu-cookie-suite' ),
				'preferences_intro_description' => __( 'Choose which cookies you allow. You can change these settings at any time.', 'wp-eu-cookie-suite' ),
				'accept_all_btn'            => __( 'Accept all', 'wp-eu-cookie-suite' ),
				'accept_necessary_btn'      => __( 'Reject all', 'wp-eu-cookie-suite' ),
				'show_preferences_btn'      => __( 'Manage preferences', 'wp-eu-cookie-suite' ),
				'save_preferences_btn'      => __( 'Save preferences', 'wp-eu-cookie-suite' ),
				'close_icon_label'          => __( 'Close', 'wp-eu-cookie-suite' ),
				'necessary_label'           => __( 'Strictly Necessary', 'wp-eu-cookie-suite' ),
				'necessary_description'     => __( 'These cookies are essential for the website to function properly.', 'wp-eu-cookie-suite' ),
				'preferences_label'         => __( 'Preferences', 'wp-eu-cookie-suite' ),
				'preferences_description'   => __( 'These cookies allow the website to remember choices you make.', 'wp-eu-cookie-suite' ),
				'statistics_label'          => __( 'Statistics', 'wp-eu-cookie-suite' ),
				'statistics_description'    => __( 'These cookies help us understand how visitors interact with the website.', 'wp-eu-cookie-suite' ),
				'marketing_label'           => __( 'Marketing', 'wp-eu-cookie-suite' ),
				'marketing_description'     => __( 'These cookies are used to track visitors across websites to display relevant ads.', 'wp-eu-cookie-suite' ),
			),
			'de' => array(
				'consent_modal_title'       => __( 'Wir verwenden Cookies', 'wp-eu-cookie-suite' ),
				'consent_modal_description' => __( 'Wir verwenden Cookies, um sicherzustellen, dass Sie das beste Erlebnis auf unserer Website erhalten. Sie können alle akzeptieren, nicht essenzielle Cookies ablehnen oder Ihre Einstellungen verwalten.', 'wp-eu-cookie-suite' ),
				'preferences_modal_title'   => __( 'Einwilligungspräferenzen', 'wp-eu-cookie-suite' ),
				'preferences_intro_title'   => __( 'Cookie-Nutzung', 'wp-eu-cookie-suite' ),
				'preferences_intro_description' => __( 'Wählen Sie, welche Cookies Sie zulassen. Sie können diese Einstellungen jederzeit ändern.', 'wp-eu-cookie-suite' ),
				'accept_all_btn'            => __( 'Alle akzeptieren', 'wp-eu-cookie-suite' ),
				'accept_necessary_btn'      => __( 'Alle ablehnen', 'wp-eu-cookie-suite' ),
				'show_preferences_btn'      => __( 'Einstellungen verwalten', 'wp-eu-cookie-suite' ),
				'save_preferences_btn'      => __( 'Einstellungen speichern', 'wp-eu-cookie-suite' ),
				'close_icon_label'          => __( 'Schließen', 'wp-eu-cookie-suite' ),
				'necessary_label'           => __( 'Unbedingt erforderlich', 'wp-eu-cookie-suite' ),
				'necessary_description'     => __( 'Diese Cookies sind für das ordnungsgemäße Funktionieren der Website unerlässlich.', 'wp-eu-cookie-suite' ),
				'preferences_label'         => __( 'Präferenzen', 'wp-eu-cookie-suite' ),
				'preferences_description'   => __( 'Diese Cookies ermöglichen es der Website, sich an von Ihnen getroffene Entscheidungen zu erinnern.', 'wp-eu-cookie-suite' ),
				'statistics_label'          => __( 'Statistiken', 'wp-eu-cookie-suite' ),
				'statistics_description'    => __( 'Diese Cookies helfen uns zu verstehen, wie Besucher mit der Website interagieren.', 'wp-eu-cookie-suite' ),
				'marketing_label'           => __( 'Marketing', 'wp-eu-cookie-suite' ),
				'marketing_description'     => __( 'Diese Cookies werden verwendet, um Besucher über Websites hinweg zu verfolgen, um relevante Anzeigen anzuzeigen.', 'wp-eu-cookie-suite' ),
			),
		);

		return $defaults[ $locale ] ?? $defaults['en'];
	}

	/**
	 * Get the active locale for the banner.
	 *
	 * @return string
	 */
	public static function get_active_locale(): string {
		$locale = 'en';

		if ( function_exists( 'pll_current_language' ) ) {
			$locale = pll_current_language() ?: 'en';
		} elseif ( has_filter( 'wpml_current_language' ) ) {
			$locale = apply_filters( 'wpml_current_language', null ) ?: 'en';
		} else {
			$wp_locale = get_locale();
			$locale    = substr( $wp_locale, 0, 2 );
		}

		/**
		 * Filter the detected banner locale.
		 *
		 * @param string $locale The detected locale.
		 */
		return (string) apply_filters( 'wpeu_cs_banner_locale', $locale );
	}

	/**
	 * Get merged strings for a locale.
	 *
	 * @param string $locale Locale.
	 * @return array<string, string>
	 */
	public static function get_strings( string $locale ): array {
		$defaults = self::get_defaults( $locale );
		$settings = get_option( 'wpeu_cs_settings', array() );
		$saved    = $settings['banner_texts'][ $locale ] ?? array();

		$merged = array_merge( $defaults, $saved );

		/**
		 * Filter the banner texts for a locale.
		 *
		 * @param array  $merged The merged texts.
		 * @param string $locale The locale.
		 */
		return (array) apply_filters( 'wpeu_cs_banner_texts', $merged, $locale );
	}

	/**
	 * Get all supported locales.
	 *
	 * @return array<string, string>
	 */
	public static function get_locales(): array {
		return array(
			'en' => __( 'English', 'wp-eu-cookie-suite' ),
			'de' => __( 'German', 'wp-eu-cookie-suite' ),
		);
	}
}
