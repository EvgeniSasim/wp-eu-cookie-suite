<?php
/**
 * Settings import/export helpers.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


namespace WPEU\CookieSuite\Admin;

use WPEU\CookieSuite\Consent\Categories;
use WPEU\CookieSuite\Frontend\ScriptRegistry;

/**
 * SettingsTransfer class.
 */
final class SettingsTransfer {

	/**
	 * Export format version.
	 */
	const FORMAT_VERSION = 1;

	/**
	 * Build export payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function export(): array {
		$settings = get_option( 'wpeu_cs_settings', array() );

		return array(
			'plugin'         => 'eu-cookie-consent-suite',
			'format_version' => self::FORMAT_VERSION,
			'plugin_version' => defined( 'WPEU_CS_VERSION' ) ? WPEU_CS_VERSION : '0.0.0',
			'exported_at'    => gmdate( 'c' ),
			'settings'       => is_array( $settings ) ? $settings : array(),
			'registry'       => ScriptRegistry::get_services(),
		);
	}

	/**
	 * Validate import payload.
	 *
	 * @param mixed $data Decoded JSON data.
	 * @return true|\WP_Error
	 */
	public static function validate( $data ) {
		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'wpeu_cs_invalid_json', __( 'Invalid JSON structure.', 'eu-cookie-consent-suite' ) );
		}

		if ( ( $data['plugin'] ?? '' ) !== 'eu-cookie-consent-suite' ) {
			return new \WP_Error( 'wpeu_cs_invalid_plugin', __( 'This file is not an EU Cookie Consent Suite export.', 'eu-cookie-consent-suite' ) );
		}

		if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			return new \WP_Error( 'wpeu_cs_missing_settings', __( 'Export is missing settings data.', 'eu-cookie-consent-suite' ) );
		}

		if ( ! isset( $data['registry'] ) || ! is_array( $data['registry'] ) ) {
			return new \WP_Error( 'wpeu_cs_missing_registry', __( 'Export is missing script registry data.', 'eu-cookie-consent-suite' ) );
		}

		return true;
	}

	/**
	 * Sanitize imported settings before saving.
	 *
	 * @param array<string, mixed> $settings Raw settings from export.
	 * @return array<string, mixed>
	 */
	public static function sanitize_imported_settings( array $settings ): array {
		$current  = get_option( 'wpeu_cs_settings', array() );
		$sanitized = is_array( $current ) ? $current : array();

		$bool_keys = array( 'blocker_enabled', 'eu_mode', 'show_reject_all', 'google_consent_mode', 'keep_data_on_uninstall', 'consent_logging_enabled', 'consent_log_store_ip', 'reload_on_revoke' );
		foreach ( $bool_keys as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$sanitized[ $key ] = (bool) $settings[ $key ];
			}
		}

		$url_keys = array( 'privacy_policy_url', 'cookie_policy_url' );
		foreach ( $url_keys as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$sanitized[ $key ] = esc_url_raw( (string) $settings[ $key ] );
			}
		}

		if ( isset( $settings['enabled_categories'] ) && is_array( $settings['enabled_categories'] ) ) {
			$sanitized['enabled_categories'] = array_map( 'sanitize_text_field', $settings['enabled_categories'] );
		}

		if ( isset( $settings['custom_categories'] ) ) {
			$sanitized['custom_categories'] = Categories::sanitize_custom_categories( $settings['custom_categories'] );
		}

		if ( isset( $settings['enabled_services'] ) && is_array( $settings['enabled_services'] ) ) {
			$sanitized['enabled_services'] = array_map( 'boolval', $settings['enabled_services'] );
		}

		if ( isset( $settings['enabled_integrations'] ) && is_array( $settings['enabled_integrations'] ) ) {
			$sanitized['enabled_integrations'] = array_map( 'boolval', $settings['enabled_integrations'] );
		}

		if ( isset( $settings['theme_analytics_field'] ) ) {
			$sanitized['theme_analytics_field'] = sanitize_text_field( (string) $settings['theme_analytics_field'] );
		}

		if ( isset( $settings['custom_block_rules'] ) ) {
			$sanitized['custom_block_rules'] = sanitize_textarea_field( (string) $settings['custom_block_rules'] );
		}

		if ( array_key_exists( 'consent_revision', $settings ) ) {
			$sanitized['consent_revision'] = max( 0, (int) $settings['consent_revision'] );
		}

		if ( array_key_exists( 'consent_log_retention', $settings ) ) {
			$sanitized['consent_log_retention'] = max( 1, (int) $settings['consent_log_retention'] );
		}

		if ( isset( $settings['language_labels'] ) && is_array( $settings['language_labels'] ) ) {
			$sanitized['language_labels'] = array_map( 'sanitize_text_field', $settings['language_labels'] );
		}

		if ( isset( $settings['banner_ui'] ) && is_array( $settings['banner_ui'] ) ) {
			$sanitized['banner_ui'] = array(
				'layout'        => sanitize_text_field( $settings['banner_ui']['layout'] ?? 'box' ),
				'position'      => sanitize_text_field( $settings['banner_ui']['position'] ?? 'bottom-right' ),
				'theme'         => sanitize_text_field( $settings['banner_ui']['theme'] ?? 'light' ),
				'primary_color' => sanitize_hex_color( $settings['banner_ui']['primary_color'] ?? '#30363c' ) ?: '#30363c',
				'custom_css'    => wp_strip_all_tags( $settings['banner_ui']['custom_css'] ?? '' ),
			);
		}

		if ( isset( $settings['banner_texts'] ) && is_array( $settings['banner_texts'] ) ) {
			$sanitized['banner_texts'] = array();
			foreach ( $settings['banner_texts'] as $locale => $texts ) {
				if ( ! is_array( $texts ) ) {
					continue;
				}
				$sanitized['banner_texts'][ sanitize_key( (string) $locale ) ] = array_map( 'sanitize_text_field', $texts );
			}
		}

		if ( isset( $settings['policy_texts'] ) && is_array( $settings['policy_texts'] ) ) {
			$sanitized['policy_texts'] = array();
			foreach ( $settings['policy_texts'] as $locale => $texts ) {
				if ( ! is_array( $texts ) ) {
					continue;
				}
				$sanitized['policy_texts'][ sanitize_key( (string) $locale ) ] = array(
					'intro'    => sanitize_textarea_field( $texts['intro'] ?? '' ),
					'template' => wp_kses_post( $texts['template'] ?? '' ),
				);
			}
		}

		$sanitized['version'] = defined( 'WPEU_CS_VERSION' ) ? WPEU_CS_VERSION : ( $settings['version'] ?? '0.1.0' );

		return $sanitized;
	}
}
