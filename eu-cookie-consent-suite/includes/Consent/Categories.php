<?php
/**
 * Consent categories model.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Categories class.
 */
final class Categories {

	/**
	 * Category constants.
	 */
	const NECESSARY   = 'necessary';
	const PREFERENCES = 'preferences';
	const STATISTICS  = 'statistics';
	const MARKETING   = 'marketing';

	/**
	 * Maximum custom categories per site.
	 */
	const MAX_CUSTOM = 5;

	/**
	 * Built-in category slugs.
	 *
	 * @return list<string>
	 */
	public static function get_builtin_slugs(): array {
		return array(
			self::NECESSARY,
			self::PREFERENCES,
			self::STATISTICS,
			self::MARKETING,
		);
	}

	/**
	 * Whether a slug is a built-in category.
	 */
	public static function is_builtin_slug( string $slug ): bool {
		return in_array( $slug, self::get_builtin_slugs(), true );
	}

	/**
	 * Validate a custom category slug.
	 */
	public static function is_valid_slug( string $slug ): bool {
		$slug = sanitize_key( $slug );
		if ( self::is_builtin_slug( $slug ) ) {
			return false;
		}
		if ( strlen( $slug ) < 2 || strlen( $slug ) > 32 ) {
			return false;
		}

		return (bool) preg_match( '/^[a-z0-9_-]+$/', $slug );
	}

	/**
	 * Validate integration map value.
	 */
	public static function is_valid_integration_map( string $map ): bool {
		return in_array( $map, array( self::PREFERENCES, self::STATISTICS, self::MARKETING ), true );
	}

	/**
	 * Built-in categories (immutable slugs).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_builtin(): array {
		return array(
			self::NECESSARY   => array(
				'label'           => __( 'Strictly Necessary', 'eu-cookie-consent-suite' ),
				'description'     => __( 'These cookies are essential for the website to function properly.', 'eu-cookie-consent-suite' ),
				'enabled'         => true,
				'read_only'       => true,
				'custom'          => false,
				'integration_map' => self::NECESSARY,
			),
			self::PREFERENCES => array(
				'label'           => __( 'Preferences', 'eu-cookie-consent-suite' ),
				'description'     => __( 'These cookies allow the website to remember choices you make.', 'eu-cookie-consent-suite' ),
				'enabled'         => false,
				'read_only'       => false,
				'custom'          => false,
				'integration_map' => self::PREFERENCES,
			),
			self::STATISTICS  => array(
				'label'           => __( 'Statistics', 'eu-cookie-consent-suite' ),
				'description'     => __( 'These cookies help us understand how visitors interact with the website.', 'eu-cookie-consent-suite' ),
				'enabled'         => false,
				'read_only'       => false,
				'custom'          => false,
				'integration_map' => self::STATISTICS,
			),
			self::MARKETING   => array(
				'label'           => __( 'Marketing', 'eu-cookie-consent-suite' ),
				'description'     => __( 'These cookies are used to track visitors across websites to display relevant ads.', 'eu-cookie-consent-suite' ),
				'enabled'         => false,
				'read_only'       => false,
				'custom'          => false,
				'integration_map' => self::MARKETING,
			),
		);
	}

	/**
	 * Owner-defined categories from settings (raw storage shape).
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_custom(): array {
		$settings = get_option( 'wpeu_cs_settings', array() );
		$stored   = $settings['custom_categories'] ?? array();

		if ( ! is_array( $stored ) ) {
			return array();
		}

		$custom = array();
		foreach ( $stored as $slug => $data ) {
			$slug = sanitize_key( (string) $slug );
			if ( ! self::is_valid_slug( $slug ) || ! is_array( $data ) ) {
				continue;
			}

			$integration_map = sanitize_key( (string) ( $data['integration_map'] ?? self::MARKETING ) );
			if ( ! self::is_valid_integration_map( $integration_map ) ) {
				$integration_map = self::MARKETING;
			}

			$custom[ $slug ] = array(
				'label'           => sanitize_text_field( (string) ( $data['label'] ?? $slug ) ),
				'description'     => sanitize_textarea_field( (string) ( $data['description'] ?? '' ) ),
				'integration_map' => $integration_map,
			);
		}

		return $custom;
	}

	/**
	 * All categories (built-in + custom).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_all(): array {
		$categories = self::get_builtin();

		foreach ( self::get_custom() as $slug => $data ) {
			$categories[ $slug ] = array(
				'label'           => $data['label'],
				'description'     => $data['description'],
				'enabled'         => false,
				'read_only'       => false,
				'custom'          => true,
				'integration_map' => $data['integration_map'],
			);
		}

		/**
		 * Filter the consent categories.
		 *
		 * @param array $categories The consent categories.
		 */
		return apply_filters( 'wpeu_consent_categories', $categories );
	}

	/**
	 * Categories shown in the banner (necessary + enabled slugs).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_enabled_for_banner(): array {
		$settings = get_option( 'wpeu_cs_settings', array() );
		$enabled  = $settings['enabled_categories'] ?? array( self::PREFERENCES, self::STATISTICS, self::MARKETING );

		if ( ! is_array( $enabled ) ) {
			$enabled = array();
		}

		$banner = array();
		foreach ( self::get_all() as $slug => $category ) {
			if ( self::NECESSARY === $slug || in_array( $slug, $enabled, true ) ) {
				$banner[ $slug ] = $category;
			}
		}

		return $banner;
	}

	/**
	 * Integration map for a category slug (for GCM / WP Consent grouping).
	 */
	public static function get_integration_map( string $slug ): string {
		$slug = sanitize_key( $slug );
		$all  = self::get_all();

		if ( isset( $all[ $slug ]['integration_map'] ) ) {
			return (string) $all[ $slug ]['integration_map'];
		}

		return self::MARKETING;
	}

	/**
	 * WP Consent API category for a plugin slug.
	 */
	public static function get_wp_consent_category( string $slug ): string {
		if ( self::NECESSARY === sanitize_key( $slug ) ) {
			return 'functional';
		}

		return self::get_integration_map( $slug );
	}

	/**
	 * Map plugin category slugs to WP Consent API categories (for frontend JS).
	 *
	 * @return array<string, string>
	 */
	public static function get_wp_consent_map(): array {
		$map = array();
		foreach ( array_keys( self::get_all() ) as $slug ) {
			$map[ $slug ] = self::get_wp_consent_category( $slug );
		}

		return $map;
	}

	/**
	 * Map plugin category slugs to integration_map values (for GCM JS).
	 *
	 * @return array<string, string>
	 */
	public static function get_integration_map_by_slug(): array {
		$map = array();
		foreach ( self::get_all() as $slug => $category ) {
			$map[ $slug ] = (string) ( $category['integration_map'] ?? self::MARKETING );
		}

		return $map;
	}

	/**
	 * Sanitize custom categories array for storage.
	 *
	 * @param mixed $input Raw custom categories.
	 * @return array<string, array<string, string>>
	 */
	public static function sanitize_custom_categories( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $input as $slug => $data ) {
			if ( count( $sanitized ) >= self::MAX_CUSTOM ) {
				break;
			}

			$slug = sanitize_key( (string) $slug );
			if ( ! self::is_valid_slug( $slug ) || ! is_array( $data ) ) {
				continue;
			}

			$integration_map = sanitize_key( (string) ( $data['integration_map'] ?? self::MARKETING ) );
			if ( ! self::is_valid_integration_map( $integration_map ) ) {
				continue;
			}

			$sanitized[ $slug ] = array(
				'label'           => sanitize_text_field( (string) ( $data['label'] ?? $slug ) ),
				'description'     => sanitize_textarea_field( (string) ( $data['description'] ?? '' ) ),
				'integration_map' => $integration_map,
			);
		}

		return $sanitized;
	}
}
