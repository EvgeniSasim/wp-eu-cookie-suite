<?php
/**
 * Banner texts model for multi-language support.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEU\CookieSuite\Settings\SettingsRepository;
/**
 * BannerTexts class.
 */
final class BannerTexts {

	/**
	 * Normalize a locale/language code to the primary language subtag.
	 *
	 * Accepts `ru`, `ru-RU`, `ru_RU`, `RU` → `ru`. Matches WordPress/Polylang/WPML
	 * language codes used by get_active_locale().
	 *
	 * @param string $code Raw locale code.
	 * @return string Lowercase primary subtag, or empty string if invalid.
	 */
	public static function normalize_locale_code( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = str_replace( '_', '-', $code );

		if ( preg_match( '/^([a-z]{2,3})(?:-[a-z0-9]+)*$/', $code, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Whether a raw or normalized locale code is usable for banner texts.
	 *
	 * @param string $code Locale code.
	 */
	public static function is_valid_locale_code( string $code ): bool {
		$normalized = self::normalize_locale_code( $code );
		return (bool) preg_match( '/^[a-z]{2,3}$/', $normalized );
	}

	/**
	 * Resolve saved banner text map for a locale, including legacy keys (ru-ru, ru_ru).
	 *
	 * @param array<string, mixed> $settings Effective settings.
	 * @param string               $locale   Normalized locale.
	 * @return array<string, string>
	 */
	public static function find_banner_texts_for_locale( array $settings, string $locale ): array {
		$locale = self::normalize_locale_code( $locale );
		$all    = $settings['banner_texts'] ?? array();
		if ( ! is_array( $all ) || '' === $locale ) {
			return array();
		}

		if ( isset( $all[ $locale ] ) && is_array( $all[ $locale ] ) ) {
			return $all[ $locale ];
		}

		foreach ( $all as $key => $texts ) {
			if ( ! is_array( $texts ) ) {
				continue;
			}
			if ( self::normalize_locale_code( (string) $key ) === $locale ) {
				return $texts;
			}
		}

		return array();
	}

	/**
	 * Get default strings for a locale.
	 *
	 * @param string $locale Locale (e.g., 'en', 'de').
	 * @return array<string, string>
	 */
	public static function get_defaults( string $locale = 'en' ): array {
		$locale = self::normalize_locale_code( $locale ) ?: 'en';
		// Literal per-locale packs — do NOT wrap in __(). Request-locale gettext would
		// contaminate EN defaults when the admin UI is Russian/German (and ship-all packs).
		$defaults = array(
			'en' => array(
				'consent_modal_title'           => 'We use cookies',
				'consent_modal_description'     => 'We use cookies to ensure you get the best experience on our website. You can accept all, reject non-essential cookies, or manage your preferences.',
				'preferences_modal_title'       => 'Consent preferences',
				'preferences_intro_title'       => 'Cookie usage',
				'preferences_intro_description' => 'Choose which cookies you allow. You can change these settings at any time.',
				'accept_all_btn'                => 'Accept all',
				'accept_necessary_btn'          => 'Reject all',
				'show_preferences_btn'          => 'Manage preferences',
				'manage_consent_label'          => 'Cookie settings',
				'revoke_consent_label'          => 'Revoke cookie consent',
				'save_preferences_btn'          => 'Save preferences',
				'close_icon_label'              => 'Close',
				'privacy_policy_link'           => 'Privacy Policy',
				'cookie_policy_link'            => 'Cookie Policy',
				'necessary_label'               => 'Strictly Necessary',
				'necessary_description'         => 'These cookies are essential for the website to function properly.',
				'preferences_label'             => 'Preferences',
				'preferences_description'       => 'These cookies allow the website to remember choices you make.',
				'statistics_label'              => 'Statistics',
				'statistics_description'        => 'These cookies help us understand how visitors interact with the website.',
				'marketing_label'               => 'Marketing',
				'marketing_description'         => 'These cookies are used to track visitors across websites to display relevant ads.',
			),
			'de' => array(
				'consent_modal_title'           => 'Wir verwenden Cookies',
				'consent_modal_description'     => 'Wir verwenden Cookies, um sicherzustellen, dass Sie das beste Erlebnis auf unserer Website erhalten. Sie können alle akzeptieren, nicht essenzielle Cookies ablehnen oder Ihre Einstellungen verwalten.',
				'preferences_modal_title'       => 'Einwilligungspräferenzen',
				'preferences_intro_title'       => 'Cookie-Nutzung',
				'preferences_intro_description' => 'Wählen Sie, welche Cookies Sie zulassen. Sie können diese Einstellungen jederzeit ändern.',
				'accept_all_btn'                => 'Alle akzeptieren',
				'accept_necessary_btn'          => 'Alle ablehnen',
				'show_preferences_btn'          => 'Einstellungen verwalten',
				'manage_consent_label'          => 'Cookie-Einstellungen',
				'revoke_consent_label'          => 'Cookie-Einwilligung widerrufen',
				'save_preferences_btn'          => 'Einstellungen speichern',
				'close_icon_label'              => 'Schließen',
				'privacy_policy_link'           => 'Datenschutzerklärung',
				'cookie_policy_link'            => 'Cookie-Richtlinie',
				'necessary_label'               => 'Unbedingt erforderlich',
				'necessary_description'         => 'Diese Cookies sind für das ordnungsgemäße Funktionieren der Website unerlässlich.',
				'preferences_label'             => 'Präferenzen',
				'preferences_description'       => 'Diese Cookies ermöglichen es der Website, sich an von Ihnen getroffene Entscheidungen zu erinnern.',
				'statistics_label'              => 'Statistiken',
				'statistics_description'        => 'Diese Cookies helfen uns zu verstehen, wie Besucher mit der Website interagieren.',
				'marketing_label'               => 'Marketing',
				'marketing_description'         => 'Diese Cookies werden verwendet, um Besucher über Websites hinweg zu verfolgen, um relevante Anzeigen anzuzeigen.',
			),
		);

		return $defaults[ $locale ] ?? $defaults['en'];
	}

	/**
	 * Get the active locale for the banner.
	 *
	 * Prefers Polylang / WPML when available; otherwise uses determine_locale()
	 * (request/user-aware) rather than the site default from get_locale().
	 *
	 * CookieConsent also auto-detects from document.documentElement.lang when
	 * multiple translations are shipped — this PHP locale is the fallback default.
	 *
	 * @return string
	 */
	public static function get_active_locale(): string {
		$locale = '';

		if ( function_exists( 'pll_current_language' ) ) {
			$pll = pll_current_language();
			if ( is_string( $pll ) && '' !== $pll ) {
				$locale = $pll;
			}
		}

		if ( '' === $locale && has_filter( 'wpml_current_language' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML integration hook.
			$wpml = apply_filters( 'wpml_current_language', null );
			if ( is_string( $wpml ) && '' !== $wpml && 'all' !== $wpml ) {
				$locale = $wpml;
			}
		}

		if ( '' === $locale ) {
			$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		}

		$locale = self::normalize_locale_code( (string) $locale ) ?: 'en';

		/**
		 * Filter the detected banner locale.
		 *
		 * @param string $locale The detected locale.
		 */
		return self::normalize_locale_code( (string) apply_filters( 'wpeu_cs_banner_locale', $locale ) ) ?: 'en';
	}

	/**
	 * Get merged strings for a locale.
	 *
	 * @param string $locale Locale.
	 * @return array<string, string>
	 */
	public static function get_strings( string $locale ): array {
		$locale   = self::normalize_locale_code( $locale ) ?: 'en';
		$defaults = self::get_defaults( $locale );
		$settings = SettingsRepository::instance()->get_effective_settings();
		$saved    = self::find_banner_texts_for_locale( $settings, $locale );

		// Ignore empty saved values so blanks do not wipe defaults.
		$saved_nonempty = array();
		foreach ( $saved as $key => $value ) {
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				$saved_nonempty[ $key ] = $value;
			}
		}

		$merged = array_merge( $defaults, $saved_nonempty );

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
		$repository = SettingsRepository::instance();
		$settings   = is_multisite() && is_network_admin()
			? $repository->get_network_settings()
			: $repository->get_effective_settings();
		$locales    = array(
			'en' => __( 'English', 'privaro-cookie-consent-banner' ),
			'de' => __( 'German', 'privaro-cookie-consent-banner' ),
		);

		$add_locale = static function ( string $code, string $label = '' ) use ( &$locales ): void {
			$code = self::normalize_locale_code( $code );
			if ( '' === $code ) {
				return;
			}
			if ( '' !== $label ) {
				$locales[ $code ] = $label;
				return;
			}
			if ( ! isset( $locales[ $code ] ) ) {
				$locales[ $code ] = strtoupper( $code );
			}
		};

		// 1. Site locale (ru_RU → ru).
		$add_locale( get_locale() );

		// 2. Polylang.
		if ( function_exists( 'pll_languages_list' ) ) {
			$pll_locales = pll_languages_list();
			if ( is_array( $pll_locales ) ) {
				foreach ( $pll_locales as $code ) {
					$add_locale( (string) $code );
				}
			}
		}

		// 3. WPML.
		if ( function_exists( 'icl_get_languages' ) ) {
			$wpml_locales = icl_get_languages();
			if ( is_array( $wpml_locales ) ) {
				foreach ( $wpml_locales as $lang ) {
					$code = (string) ( $lang['language_code'] ?? '' );
					$add_locale( $code, (string) ( $lang['native_name'] ?? '' ) );
				}
			}
		}

		// 4. Saved in settings (normalize legacy ru-RU / ru-ru keys).
		$saved_banner_locales = array_keys( $settings['banner_texts'] ?? array() );
		$saved_policy_locales = array_keys( $settings['policy_texts'] ?? array() );
		$all_saved            = array_unique( array_merge( $saved_banner_locales, $saved_policy_locales ) );

		foreach ( $all_saved as $code ) {
			$add_locale( (string) $code );
		}

		// 5. Custom labels from settings.
		if ( isset( $settings['language_labels'] ) && is_array( $settings['language_labels'] ) ) {
			foreach ( $settings['language_labels'] as $code => $label ) {
				$add_locale( (string) $code, sanitize_text_field( (string) $label ) );
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
