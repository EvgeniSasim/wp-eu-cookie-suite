<?php
/**
 * Banner texts model for multi-language support.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
				'consent_modal_title'           => __( 'We use cookies', 'eu-cookie-consent-suite' ),
				'consent_modal_description'     => __( 'We use cookies to ensure you get the best experience on our website. You can accept all, reject non-essential cookies, or manage your preferences.', 'eu-cookie-consent-suite' ),
				'preferences_modal_title'       => __( 'Consent preferences', 'eu-cookie-consent-suite' ),
				'preferences_intro_title'       => __( 'Cookie usage', 'eu-cookie-consent-suite' ),
				'preferences_intro_description' => __( 'Choose which cookies you allow. You can change these settings at any time.', 'eu-cookie-consent-suite' ),
				'accept_all_btn'                => __( 'Accept all', 'eu-cookie-consent-suite' ),
				'accept_necessary_btn'          => __( 'Reject all', 'eu-cookie-consent-suite' ),
				'show_preferences_btn'          => __( 'Manage preferences', 'eu-cookie-consent-suite' ),
				'manage_consent_label'          => __( 'Cookie settings', 'eu-cookie-consent-suite' ),
				'revoke_consent_label'          => __( 'Revoke cookie consent', 'eu-cookie-consent-suite' ),
				'save_preferences_btn'          => __( 'Save preferences', 'eu-cookie-consent-suite' ),
				'close_icon_label'              => __( 'Close', 'eu-cookie-consent-suite' ),
				'necessary_label'               => __( 'Strictly Necessary', 'eu-cookie-consent-suite' ),
				'necessary_description'         => __( 'These cookies are essential for the website to function properly.', 'eu-cookie-consent-suite' ),
				'preferences_label'             => __( 'Preferences', 'eu-cookie-consent-suite' ),
				'preferences_description'       => __( 'These cookies allow the website to remember choices you make.', 'eu-cookie-consent-suite' ),
				'statistics_label'              => __( 'Statistics', 'eu-cookie-consent-suite' ),
				'statistics_description'        => __( 'These cookies help us understand how visitors interact with the website.', 'eu-cookie-consent-suite' ),
				'marketing_label'               => __( 'Marketing', 'eu-cookie-consent-suite' ),
				'marketing_description'         => __( 'These cookies are used to track visitors across websites to display relevant ads.', 'eu-cookie-consent-suite' ),
			),
			'de' => array(
				'consent_modal_title'           => __( 'Wir verwenden Cookies', 'eu-cookie-consent-suite' ),
				'consent_modal_description'     => __( 'Wir verwenden Cookies, um sicherzustellen, dass Sie das beste Erlebnis auf unserer Website erhalten. Sie können alle akzeptieren, nicht essenzielle Cookies ablehnen oder Ihre Einstellungen verwalten.', 'eu-cookie-consent-suite' ),
				'preferences_modal_title'       => __( 'Einwilligungspräferenzen', 'eu-cookie-consent-suite' ),
				'preferences_intro_title'       => __( 'Cookie-Nutzung', 'eu-cookie-consent-suite' ),
				'preferences_intro_description' => __( 'Wählen Sie, welche Cookies Sie zulassen. Sie können diese Einstellungen jederzeit ändern.', 'eu-cookie-consent-suite' ),
				'accept_all_btn'                => __( 'Alle akzeptieren', 'eu-cookie-consent-suite' ),
				'accept_necessary_btn'          => __( 'Alle ablehnen', 'eu-cookie-consent-suite' ),
				'show_preferences_btn'          => __( 'Einstellungen verwalten', 'eu-cookie-consent-suite' ),
				'manage_consent_label'          => __( 'Cookie-Einstellungen', 'eu-cookie-consent-suite' ),
				'revoke_consent_label'          => __( 'Cookie-Einwilligung widerrufen', 'eu-cookie-consent-suite' ),
				'save_preferences_btn'          => __( 'Einstellungen speichern', 'eu-cookie-consent-suite' ),
				'close_icon_label'              => __( 'Schließen', 'eu-cookie-consent-suite' ),
				'necessary_label'               => __( 'Unbedingt erforderlich', 'eu-cookie-consent-suite' ),
				'necessary_description'         => __( 'Diese Cookies sind für das ordnungsgemäße Funktionieren der Website unerlässlich.', 'eu-cookie-consent-suite' ),
				'preferences_label'             => __( 'Präferenzen', 'eu-cookie-consent-suite' ),
				'preferences_description'       => __( 'Diese Cookies ermöglichen es der Website, sich an von Ihnen getroffene Entscheidungen zu erinnern.', 'eu-cookie-consent-suite' ),
				'statistics_label'              => __( 'Statistiken', 'eu-cookie-consent-suite' ),
				'statistics_description'        => __( 'Diese Cookies helfen uns zu verstehen, wie Besucher mit der Website interagieren.', 'eu-cookie-consent-suite' ),
				'marketing_label'               => __( 'Marketing', 'eu-cookie-consent-suite' ),
				'marketing_description'         => __( 'Diese Cookies werden verwendet, um Besucher über Websites hinweg zu verfolgen, um relevante Anzeigen anzuzeigen.', 'eu-cookie-consent-suite' ),
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
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML integration hook.
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
		$settings = get_option( 'wpeu_cs_settings', array() );
		$locales  = array(
			'en' => __( 'English', 'eu-cookie-consent-suite' ),
			'de' => __( 'German', 'eu-cookie-consent-suite' ),
		);

		// 1. Site locale
		$site_locale = substr( get_locale(), 0, 2 );
		if ( ! isset( $locales[ $site_locale ] ) ) {
			$locales[ $site_locale ] = strtoupper( $site_locale );
		}

		// 2. Polylang
		if ( function_exists( 'pll_languages_list' ) ) {
			$pll_locales = pll_languages_list();
			if ( is_array( $pll_locales ) ) {
				foreach ( $pll_locales as $code ) {
					if ( ! isset( $locales[ $code ] ) ) {
						$locales[ $code ] = strtoupper( $code );
					}
				}
			}
		}

		// 3. WPML
		if ( function_exists( 'icl_get_languages' ) ) {
			$wpml_locales = icl_get_languages();
			if ( is_array( $wpml_locales ) ) {
				foreach ( $wpml_locales as $lang ) {
					$code = $lang['language_code'] ?? '';
					if ( $code && ! isset( $locales[ $code ] ) ) {
						$locales[ $code ] = $lang['native_name'] ?? strtoupper( $code );
					}
				}
			}
		}

		// 4. Saved in settings
		$saved_banner_locales = array_keys( $settings['banner_texts'] ?? array() );
		$saved_policy_locales = array_keys( $settings['policy_texts'] ?? array() );
		$all_saved            = array_unique( array_merge( $saved_banner_locales, $saved_policy_locales ) );

		foreach ( $all_saved as $code ) {
			if ( ! isset( $locales[ $code ] ) ) {
				$locales[ $code ] = strtoupper( $code );
			}
		}

		// 5. Custom labels from settings
		if ( isset( $settings['language_labels'] ) && is_array( $settings['language_labels'] ) ) {
			foreach ( $settings['language_labels'] as $code => $label ) {
				$locales[ $code ] = $label;
			}
		}

		ksort( $locales );

		/**
		 * Filter the final list of locales.
		 *
		 * @param array<string, string> $locales Array of locale code => label.
		 */
		return (array) apply_filters( 'wpeu_cs_locales', $locales );
	}

	/**
	 * Get default policy template.
	 *
	 * @param string $locale Locale.
	 * @return string
	 */
	public static function get_default_policy_template( string $locale ): string {
		if ( 'de' === $locale ) {
			return "<h2>Cookie-Richtlinie</h2>\n{{intro}}\n<h3>Verwendete Cookies</h3>\n{{table}}\n{{content}}";
		}
		return "<h2>Cookie Policy</h2>\n{{intro}}\n<h3>Cookies used on our website</h3>\n{{table}}\n{{content}}";
	}
}
