<?php
/**
 * Settings resolution for single-site and multisite.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves local, network, and effective plugin settings.
 */
final class SettingsRepository {

	public const LOCAL_OPTION           = 'wpeu_cs_settings';
	public const NETWORK_OPTION         = 'wpeu_cs_network_settings';
	public const USE_NETWORK_DEFAULTS_KEY = 'use_network_defaults';

	/**
	 * Site-level keys that stay editable when inheriting network banner defaults.
	 *
	 * @var list<string>
	 */
	public const SITE_LOCAL_KEYS = array(
		'consent_logging_enabled',
		'consent_log_retention',
		'consent_log_store_ip',
	);

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Site-level stored settings (includes inherit flag).
	 *
	 * @return array<string, mixed>
	 */
	public function get_local_settings(): array {
		$settings = get_option( self::LOCAL_OPTION, array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Network-wide default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_network_settings(): array {
		if ( ! is_multisite() ) {
			return array();
		}

		$settings = get_site_option( self::NETWORK_OPTION, array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Whether the current site inherits network defaults.
	 *
	 * @return bool
	 */
	public function is_using_network_defaults(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		$local = $this->get_local_settings();
		if ( ! array_key_exists( self::USE_NETWORK_DEFAULTS_KEY, $local ) ) {
			return true;
		}

		return (bool) $local[ self::USE_NETWORK_DEFAULTS_KEY ];
	}

	/**
	 * Settings used at runtime (frontend, consent, integrations).
	 *
	 * @return array<string, mixed>
	 */
	public function get_effective_settings(): array {
		$local = $this->get_local_settings();
		$effective = self::resolve_effective_settings(
			is_multisite(),
			$local,
			$this->get_network_settings(),
			$this->resolve_use_network_defaults_flag( $local, is_multisite() )
		);

		if ( is_multisite() && $this->is_using_network_defaults() ) {
			$effective = self::apply_site_local_overrides( $effective, $local );
		}

		return $effective;
	}

	/**
	 * Whether network-wide defaults have been saved at least once.
	 *
	 * @return bool
	 */
	public function has_network_defaults(): bool {
		return ! empty( $this->get_network_settings() );
	}

	/**
	 * Overlay per-site settings onto inherited network defaults.
	 *
	 * @param array $effective Resolved network (or fallback) settings.
	 * @param array $local     Site option payload.
	 * @return array<string, mixed>
	 */
	public static function apply_site_local_overrides( array $effective, array $local ): array {
		foreach ( self::SITE_LOCAL_KEYS as $key ) {
			if ( array_key_exists( $key, $local ) ) {
				$effective[ $key ] = $local[ $key ];
			}
		}

		return $effective;
	}

	/**
	 * Pure resolver for unit tests.
	 *
	 * @param bool      $is_multisite         Whether WordPress multisite is active.
	 * @param array     $local                Site option payload.
	 * @param array     $network              Network option payload.
	 * @param bool|null $use_network_defaults Inherit flag; null means default true on multisite.
	 * @return array<string, mixed>
	 */
	public static function resolve_effective_settings(
		bool $is_multisite,
		array $local,
		array $network,
		?bool $use_network_defaults
	): array {
		if ( ! $is_multisite ) {
			return $local;
		}

		$use_network = $use_network_defaults ?? true;
		if ( $use_network ) {
			return ! empty( $network ) ? $network : $local;
		}

		return $local;
	}

	/**
	 * Resolve inherit flag from local settings.
	 *
	 * @param array $local        Local settings.
	 * @param bool  $is_multisite Whether multisite is active.
	 * @return bool|null
	 */
	private function resolve_use_network_defaults_flag( array $local, bool $is_multisite ): ?bool {
		if ( ! $is_multisite ) {
			return null;
		}

		if ( ! array_key_exists( self::USE_NETWORK_DEFAULTS_KEY, $local ) ) {
			return true;
		}

		return (bool) $local[ self::USE_NETWORK_DEFAULTS_KEY ];
	}
}
