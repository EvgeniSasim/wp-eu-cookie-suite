<?php
/**
 * Safe HTML open-tag attribute editor (does not touch element text/JS bodies).
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attribute read/write helpers built on WP_HTML_Tag_Processor (WP 6.2+).
 */
final class HtmlTagEditor {

	/**
	 * Read a single attribute from the first matching tag.
	 *
	 * @param string $html     Tag or fragment HTML.
	 * @param string $tag_name Element name (e.g. script, link).
	 * @param string $attr     Attribute name.
	 * @return string|null Attribute value, empty string for boolean attrs, null if missing.
	 */
	public static function get_attribute( string $html, string $tag_name, string $attr ): ?string {
		if ( ! class_exists( \WP_HTML_Tag_Processor::class ) ) {
			return self::fallback_get_attribute( $html, $tag_name, $attr );
		}

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag( $tag_name ) ) {
			return null;
		}

		$value = $processor->get_attribute( $attr );
		if ( true === $value ) {
			return '';
		}
		if ( null === $value ) {
			return null;
		}

		return (string) $value;
	}

	/**
	 * Mutate attributes on the first matching tag; element text content is preserved.
	 *
	 * @param string   $html     Tag or fragment HTML.
	 * @param string   $tag_name Element name.
	 * @param callable $callback Receives the processor at the tag.
	 * @return string Updated HTML.
	 */
	public static function edit( string $html, string $tag_name, callable $callback ): string {
		if ( ! class_exists( \WP_HTML_Tag_Processor::class ) ) {
			return $html;
		}

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag( $tag_name ) ) {
			return $html;
		}

		$callback( $processor );

		return $processor->get_updated_html();
	}

	/**
	 * Fallback attribute reader when WP HTML API is unavailable (should not happen on WP 6.4+).
	 *
	 * Operates only on the opening tag before `>`, never on the element body.
	 *
	 * @param string $html     Tag HTML.
	 * @param string $tag_name Element name.
	 * @param string $attr     Attribute name.
	 */
	private static function fallback_get_attribute( string $html, string $tag_name, string $attr ): ?string {
		$tag_name = preg_quote( $tag_name, '/' );
		$attr     = preg_quote( $attr, '/' );
		if ( ! preg_match( '/<' . $tag_name . '\b([^>]*)>/i', $html, $m ) ) {
			return null;
		}
		$open = $m[1];

		if ( preg_match( '/(?:^|\s)' . $attr . '\s*=\s*"([^"]*)"/i', $open, $v ) ) {
			return $v[1];
		}
		if ( preg_match( '/(?:^|\s)' . $attr . '\s*=\s*\'([^\']*)\'/i', $open, $v ) ) {
			return $v[1];
		}
		if ( preg_match( '/(?:^|\s)' . $attr . '\s*=\s*([^\s"\'=<>`]+)/i', $open, $v ) ) {
			return $v[1];
		}
		if ( preg_match( '/(?:^|\s)' . $attr . '(?:\s|>|$)/i', $open ) ) {
			return '';
		}

		return null;
	}
}
