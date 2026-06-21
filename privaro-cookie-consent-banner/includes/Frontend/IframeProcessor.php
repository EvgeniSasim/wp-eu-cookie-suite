<?php
/**
 * Iframe Processor class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handles detection and replacement of iframes with placeholders.
 */
final class IframeProcessor {

	/**
	 * Process iframes in content.
	 *
	 * @param string $content The content.
	 * @return string
	 */
	public static function process_iframes( string $content ): string {
		if ( empty( $content ) || ! str_contains( $content, '<iframe' ) ) {
			return $content;
		}

		$settings = \WPEU\CookieSuite\Settings\SettingsRepository::get_effective_settings();
		$enabled  = $settings['enabled_integrations']['iframe_placeholder'] ?? false;

		if ( ! $enabled ) {
			return $content;
		}

		return preg_replace_callback(
			'/<iframe\b[^>]*>.*?<\/iframe>/is',
			function ( $matches ) use ( $settings ) {
				return self::handle_iframe_tag( $matches[0], $settings );
			},
			$content
		);
	}

	/**
	 * Handle an individual iframe tag.
	 *
	 * @param string $tag      The iframe tag.
	 * @param array  $settings Plugin settings.
	 * @return string
	 */
	public static function handle_iframe_tag( string $tag, array $settings ): string {
		$src = '';

		if ( preg_match( '/src=["\']([^"\']+)["\']/i', $tag, $src_matches ) ) {
			$src = $src_matches[1];
		}

		if ( empty( $src ) ) {
			return $tag;
		}

		$services         = ScriptRegistry::get_services();
		$enabled_services = $settings['enabled_services'] ?? array();
		$service_id       = null;
		$category         = null;

		foreach ( array( 'youtube', 'vimeo', 'google-maps' ) as $id ) {
			if ( ! isset( $services[ $id ] ) ) {
				continue;
			}

			// Respect per-service toggles.
			if ( isset( $enabled_services[ $id ] ) && ! $enabled_services[ $id ] ) {
				continue;
			}

			foreach ( $services[ $id ]['patterns'] as $pattern ) {
				if ( str_contains( $src, $pattern ) ) {
					$service_id = $id;
					$category   = $services[ $id ]['category'];
					break 2;
				}
			}
		}

		if ( $category && ! wpeu_cs_user_has_consent( $category ) ) {
			return self::get_placeholder( $tag, $service_id, $category );
		}

		return $tag;
	}

	/**
	 * Get placeholder HTML.
	 *
	 * @param string $original_tag Original iframe tag.
	 * @param string $service_id   Service ID.
	 * @param string $category     Consent category.
	 * @return string
	 */
	public static function get_placeholder( string $original_tag, string $service_id, string $category ): string {
		$services = ScriptRegistry::get_services();
		$label    = $services[ $service_id ]['label'] ?? ucfirst( $service_id );

		// Extract dimensions if possible.
		$width  = '100%';
		$height = '350px';
		if ( preg_match( '/width=["\']([^"\']+)["\']/i', $original_tag, $w_matches ) ) {
			$width = $w_matches[1];
			if ( is_numeric( $width ) ) {
				$width .= 'px';
			}
		}
		if ( preg_match( '/height=["\']([^"\']+)["\']/i', $original_tag, $h_matches ) ) {
			$height = $h_matches[1];
			if ( is_numeric( $height ) ) {
				$height .= 'px';
			}
		}

		$style = "width: {$width}; height: {$height};";

		$text = sprintf(
			/* translators: %s: Service label (e.g. YouTube) */
			__( 'Please accept %s cookies to view this content.', 'privaro-cookie-consent-banner' ),
			'<strong>' . esc_html( $label ) . '</strong>'
		);

		$button_text = __( 'Accept Cookies', 'privaro-cookie-consent-banner' );

		return sprintf(
			'<div class="wpeu-cs-iframe-placeholder" style="%s" data-category="%s" data-service="%s">
				<div class="wpeu-cs-iframe-placeholder-content">
					<p>%s</p>
					<button type="button" class="wpeu-cs-accept-category button" data-category="%s">%s</button>
				</div>
				<div class="wpeu-cs-hidden-iframe" style="display:none;">%s</div>
			</div>',
			esc_attr( $style ),
			esc_attr( $category ),
			esc_attr( $service_id ),
			$text,
			esc_attr( $category ),
			esc_html( $button_text ),
			base64_encode( $original_tag )
		);
	}
}
