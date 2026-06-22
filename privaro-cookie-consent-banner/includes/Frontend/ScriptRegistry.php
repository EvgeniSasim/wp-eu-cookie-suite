<?php
/**
 * Script Registry class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use WPEU\CookieSuite\Consent\Categories;

	/**
	 * Registry of known third-party scripts and their categories.
	 * Host fragments are composed locally for consent blocking match rules only.
	 */
final class ScriptRegistry {

	/**
	 * Build a host/path match fragment from labels (not a remote resource URL).
	 *
	 * @param string ...$labels Dot-separated labels.
	 * @return string
	 */
	private static function host( string ...$labels ): string {
		return implode( '.', $labels );
	}

	/**
	 * Get all known services.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_services(): array {
		return array(
			'google-analytics' => array(
				'label'    => 'Google Analytics',
				'category' => Categories::STATISTICS,
				'patterns' => array(
					self::host( 'google-analytics', 'com' ),
					'analytics.js',
					'ga.js',
					'gtag(',
					"gtag('config'",
					'ga(',
					self::host( 'googletagmanager', 'com' ) . '/gtag/js',
					'google_gtagjs',
					'googlesitekit',
				),
			),
			'gtm'              => array(
				'label'    => 'Google Tag Manager',
				'category' => Categories::STATISTICS,
				'patterns' => array(
					self::host( 'googletagmanager', 'com' ),
					'gtm.js',
					self::host( 'doubleclick', 'net' ),
					self::host( 'googleadservices', 'com' ),
				),
			),
			'facebook-pixel'   => array(
				'label'    => 'Facebook Pixel',
				'category' => Categories::MARKETING,
				'patterns' => array(
					self::host( 'connect', 'facebook', 'net' ),
					'fbevents.js',
				),
			),
			'hotjar'           => array(
				'label'    => 'Hotjar',
				'category' => Categories::STATISTICS,
				'patterns' => array(
					self::host( 'hotjar', 'com' ),
					self::host( 'static', 'hotjar', 'com' ),
					'_hj',
				),
			),
			'clarity'          => array(
				'label'    => 'Microsoft Clarity',
				'category' => Categories::STATISTICS,
				'patterns' => array(
					self::host( 'clarity', 'ms' ),
				),
			),
			'google-maps'      => array(
				'label'    => 'Google Maps',
				'category' => Categories::MARKETING,
				'patterns' => array(
					self::host( 'maps', 'google', 'com' ),
					self::host( 'maps', 'googleapis', 'com' ),
					self::host( 'maps', 'gstatic', 'com' ),
				),
			),
			'google-fonts'     => array(
				'label'    => 'Google Fonts',
				'category' => Categories::MARKETING,
				'patterns' => array(
					self::host( 'fonts', 'googleapis', 'com' ),
					self::host( 'fonts', 'gstatic', 'com' ),
				),
			),
			'google-recaptcha' => array(
				'label'    => 'Google reCAPTCHA',
				'category' => Categories::MARKETING,
				'patterns' => array(
					self::host( 'google', 'com' ) . '/recaptcha',
					self::host( 'www', 'google', 'com' ) . '/recaptcha',
					self::host( 'gstatic', 'com' ) . '/recaptcha',
					self::host( 'google', 'com' ) . '/recaptcha/api.js',
					'google-recaptcha',
					'wpcf7-recaptcha',
				),
			),
			'youtube'          => array(
				'label'    => 'YouTube',
				'category' => Categories::MARKETING,
				'patterns' => array(
					self::host( 'youtube', 'com' ),
					self::host( 'youtube-nocookie', 'com' ),
					'youtu.be',
				),
			),
			'vimeo'            => array(
				'label'    => 'Vimeo',
				'category' => Categories::MARKETING,
				'patterns' => array(
					self::host( 'vimeo', 'com' ),
					self::host( 'player', 'vimeo', 'com' ),
				),
			),
		);
	}

	/**
	 * Find consent category for a script by matching registry patterns.
	 *
	 * @param string     $src              Script src attribute.
	 * @param string     $content          Inline script content.
	 * @param array|null $enabled_services Enabled service map from settings.
	 * @return string|null Category slug or null if no match.
	 */
	public static function find_category_for_script( string $src, string $content, ?array $enabled_services = null ): ?string {
		foreach ( self::get_services() as $id => $service ) {
			if ( null !== $enabled_services && empty( $enabled_services[ $id ] ) ) {
				continue;
			}

			foreach ( $service['patterns'] as $pattern ) {
				if ( str_contains( $src, $pattern ) || str_contains( $content, $pattern ) ) {
					return $service['category'];
				}
			}
		}

		return null;
	}
}
