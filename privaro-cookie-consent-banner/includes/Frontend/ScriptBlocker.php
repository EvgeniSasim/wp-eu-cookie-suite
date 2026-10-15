<?php
/**
 * Script Blocker class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEU\CookieSuite\Consent\Categories;
use WPEU\CookieSuite\Settings\SettingsRepository;

/**
 * Handles blocking and unblocking of third-party scripts.
 */
final class ScriptBlocker {

	/**
	 * Plugin script id prefixes that must never be consent-blocked.
	 *
	 * @var list<string>
	 */
	private const OWN_SCRIPT_ID_PREFIXES = array(
		'wpeu-cs-gcm-',
		'wpeu-cs-blocker',
		'wpeu-cs-cookieconsent',
		'wpeu-cs-ga-guard',
	);

	/**
	 * Non-executable script types left untouched.
	 *
	 * @var list<string>
	 */
	private const SKIP_SCRIPT_TYPES = array(
		'application/ld+json',
		'application/json',
		'importmap',
		'speculationrules',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( function_exists( 'wp_should_output_buffer_template_for_enhancement' ) ) {
			add_filter( 'wp_should_output_buffer_template_for_enhancement', array( $this, 'should_buffer_template' ) );
			add_filter( 'wp_template_enhancement_output_buffer', array( $this, 'process_output' ), 10, 2 );
		} else {
			add_action( 'template_redirect', array( $this, 'start_legacy_output_buffer' ), 0 );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_bootstrap_js' ), 5 );
	}

	/**
	 * Opt in to the WordPress 6.9+ template enhancement output buffer when blocking is active.
	 *
	 * @param bool $should Whether a template enhancement buffer should start.
	 * @return bool
	 */
	public function should_buffer_template( $should = false ): bool {
		if ( ! $this->should_process_frontend_output() ) {
			return (bool) $should;
		}

		return true;
	}

	/**
	 * Start a legacy output buffer with a flush callback (nested-buffer safe).
	 *
	 * Nested buffers flush into this one; process_output runs when this buffer ends,
	 * without requiring an exact ob_get_level() match on shutdown.
	 */
	public function start_legacy_output_buffer(): void {
		if ( ! $this->should_process_frontend_output() ) {
			return;
		}

		ob_start( array( $this, 'flush_legacy_output_buffer' ) );
	}

	/**
	 * Output-buffer callback for the legacy path.
	 *
	 * @param string $buffer Buffer contents.
	 * @param int    $phase  PHP output handler phase flags.
	 * @return string
	 */
	public function flush_legacy_output_buffer( string $buffer, int $phase = 0 ): string {
		// Skip progressive flush chunks; still process final flush and ob_get_clean()/end.
		if ( ( $phase & PHP_OUTPUT_HANDLER_FLUSH ) && ! ( $phase & PHP_OUTPUT_HANDLER_FINAL ) ) {
			return $buffer;
		}

		return $this->process_output( $buffer );
	}

	/**
	 * Whether script/iframe processing should run on the current frontend request.
	 *
	 * @return bool
	 */
	private function should_process_frontend_output(): bool {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() ) {
			return false;
		}

		$settings = SettingsRepository::instance()->get_effective_settings();
		$blocker  = ! empty( $settings['blocker_enabled'] );
		$iframes  = ! empty( $settings['enabled_integrations']['iframe_placeholder'] );

		return $blocker || $iframes;
	}

	/**
	 * Process HTML output and block scripts.
	 *
	 * @param string $output          The HTML output.
	 * @param string $original_output Unfiltered buffer HTML (WP 6.9+ only).
	 * @return string The processed HTML.
	 */
	public function process_output( string $output, string $original_output = '' ): string {
		unset( $original_output );

		if ( empty( $output ) ) {
			return $output;
		}

		$settings = SettingsRepository::instance()->get_effective_settings();
		$blocker  = ! empty( $settings['blocker_enabled'] );

		if ( $blocker && str_contains( strtolower( $output ), '<script' ) ) {
			$output = preg_replace_callback(
				'/<script\b[^>]*>.*?<\/script>/is',
				array( $this, 'process_script_tag' ),
				$output
			);
		}

		if ( $blocker && str_contains( strtolower( $output ), '<link' ) ) {
			$output = preg_replace_callback(
				'/<link\b[^>]*>/i',
				array( $this, 'process_link_tag' ),
				$output
			);
		}

		if ( str_contains( strtolower( $output ), '<iframe' ) ) {
			$output = IframeProcessor::process_iframes( $output );
		}

		return is_string( $output ) ? $output : '';
	}

	/**
	 * Process an individual script tag (attributes only — body never mutated).
	 *
	 * @param array<int, string> $matches Regex matches.
	 * @return string Modified script tag.
	 */
	private function process_script_tag( array $matches ): string {
		$full_tag = $matches[0];
		$content  = '';
		if ( preg_match( '/<script\b[^>]*>(.*)<\/script>/is', $full_tag, $parts ) ) {
			$content = $parts[1];
		}

		// Idempotent: already inert for consent.
		$existing_type = strtolower( (string) ( HtmlTagEditor::get_attribute( $full_tag, 'script', 'type' ) ?? '' ) );
		$existing_cat  = HtmlTagEditor::get_attribute( $full_tag, 'script', 'data-category' );
		if ( 'text/plain' === $existing_type && null !== $existing_cat && '' !== $existing_cat ) {
			return $full_tag;
		}

		$category = $this->get_block_category( $full_tag, $content );

		if ( ! $category || wpeu_cs_user_has_consent( $category ) ) {
			return $full_tag;
		}

		return HtmlTagEditor::edit(
			$full_tag,
			'script',
			static function ( $processor ) use ( $category ): void {
				$original_type = $processor->get_attribute( 'type' );
				if ( null === $original_type || true === $original_type || '' === $original_type ) {
					$processor->set_attribute( 'data-wpeu-original-type', 'text/javascript' );
				} else {
					$processor->set_attribute( 'data-wpeu-original-type', (string) $original_type );
				}
				$processor->set_attribute( 'type', 'text/plain' );
				if ( null === $processor->get_attribute( 'data-category' ) ) {
					$processor->set_attribute( 'data-category', $category );
				}
			}
		);
	}

	/**
	 * Process an individual link tag into an inert consent-gated placeholder.
	 *
	 * @param array<int, string> $matches Regex matches.
	 * @return string Modified link tag.
	 */
	private function process_link_tag( array $matches ): string {
		$full_tag = $matches[0];

		$existing_href = HtmlTagEditor::get_attribute( $full_tag, 'link', 'data-wpeu-href' );
		$existing_cat  = HtmlTagEditor::get_attribute( $full_tag, 'link', 'data-category' );
		if ( null !== $existing_href && null !== $existing_cat && '' !== $existing_cat ) {
			return $full_tag;
		}

		$category = $this->get_block_category( $full_tag, '' );

		if ( ! $category || wpeu_cs_user_has_consent( $category ) ) {
			return $full_tag;
		}

		return HtmlTagEditor::edit(
			$full_tag,
			'link',
			static function ( $processor ) use ( $category ): void {
				$href = $processor->get_attribute( 'href' );
				$rel  = $processor->get_attribute( 'rel' );
				$type = $processor->get_attribute( 'type' );

				if ( is_string( $href ) && '' !== $href ) {
					$processor->set_attribute( 'data-wpeu-href', $href );
				}
				if ( is_string( $rel ) && '' !== $rel ) {
					$processor->set_attribute( 'data-wpeu-original-rel', $rel );
				}
				if ( is_string( $type ) && '' !== $type ) {
					$processor->set_attribute( 'data-wpeu-original-type', $type );
				} elseif ( null === $type || true === $type ) {
					$processor->set_attribute( 'data-wpeu-original-type', '' );
				}

				$processor->set_attribute( 'type', 'text/plain' );
				$processor->set_attribute( 'rel', 'wpeu-blocked' );
				$processor->remove_attribute( 'href' );
				if ( null === $processor->get_attribute( 'data-category' ) ) {
					$processor->set_attribute( 'data-category', $category );
				}
			}
		);
	}

	/**
	 * Get the consent category for a script if it should be blocked.
	 *
	 * @param string $tag     Full script/link tag.
	 * @param string $content Script content (empty for links).
	 * @return string|null Category if it should be blocked, null otherwise.
	 */
	private function get_block_category( string $tag, string $content ): ?string {
		$tag_name = str_starts_with( strtolower( ltrim( $tag ) ), '<link' ) ? 'link' : 'script';

		if ( 'script' === $tag_name && $this->is_own_plugin_script( $tag ) ) {
			return null;
		}

		if ( 'script' === $tag_name && $this->is_non_executable_script_type( $tag ) ) {
			return null;
		}

		$src = (string) ( HtmlTagEditor::get_attribute( $tag, $tag_name, 'script' === $tag_name ? 'src' : 'href' ) ?? '' );

		// Whitelist WordPress core scripts.
		if ( '' !== $src ) {
			$local_host = wp_parse_url( home_url(), PHP_URL_HOST );
			$src_host   = wp_parse_url( $src, PHP_URL_HOST );

			if ( ! $src_host || $src_host === $local_host ) {
				if ( str_contains( $src, '/wp-includes/' ) || str_contains( $src, '/wp-admin/' ) ) {
					return null;
				}
				if ( str_contains( $src, 'jquery' ) ) {
					return null;
				}
			}
		}

		$settings         = SettingsRepository::instance()->get_effective_settings();
		$enabled_services = $settings['enabled_services'] ?? null;

		$category = ScriptRegistry::find_category_for_script( $src, $content, $enabled_services );
		if ( null !== $category ) {
			return $category;
		}

		// Handle custom block rules.
		if ( ! empty( $settings['custom_block_rules'] ) ) {
			$rules = explode( "\n", str_replace( "\r", '', $settings['custom_block_rules'] ) );
			foreach ( $rules as $rule ) {
				$rule = trim( $rule );
				if ( empty( $rule ) ) {
					continue;
				}

				if ( str_starts_with( $rule, '-url-' ) ) {
					$pattern = substr( $rule, 5 );
					if ( '' !== $src && str_contains( $src, $pattern ) ) {
						return 'marketing';
					}
				} elseif ( str_contains( $src, $rule ) || str_contains( $content, $rule ) ) {
					return 'marketing';
				}
			}
		}

		$known_tags = apply_filters( 'wpeu_known_script_tags', array() );
		foreach ( $known_tags as $key => $value ) {
			$pattern  = is_string( $key ) ? $key : $value;
			$category = is_string( $key ) ? $value : 'marketing';

			if ( @preg_match( $pattern, '' ) !== false ) {
				if ( preg_match( $pattern, $src ) || preg_match( $pattern, $content ) ) {
					return $category;
				}
			} elseif ( str_contains( $src, (string) $pattern ) || str_contains( $content, (string) $pattern ) ) {
				return $category;
			}
		}

		if ( ! empty( $settings['block_unknown_third_party'] ) && $this->is_third_party_src( $src ) ) {
			return 'marketing';
		}

		return null;
	}

	/**
	 * Whether the script is a Privaro-owned handle/inline that must stay executable.
	 *
	 * Matches WP script tag ids such as `wpeu-cs-gcm-default-js-after`.
	 *
	 * @param string $tag Script tag HTML.
	 */
	private function is_own_plugin_script( string $tag ): bool {
		$id = (string) ( HtmlTagEditor::get_attribute( $tag, 'script', 'id' ) ?? '' );
		if ( '' === $id ) {
			return false;
		}

		foreach ( self::OWN_SCRIPT_ID_PREFIXES as $prefix ) {
			if ( str_starts_with( $id, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the script type is a data/document type that must not be blocked.
	 *
	 * @param string $tag Script tag HTML.
	 */
	private function is_non_executable_script_type( string $tag ): bool {
		$type = strtolower( (string) ( HtmlTagEditor::get_attribute( $tag, 'script', 'type' ) ?? '' ) );
		if ( '' === $type ) {
			return false;
		}

		foreach ( self::SKIP_SCRIPT_TYPES as $skip ) {
			if ( $type === $skip || str_contains( $type, $skip ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a script/link src points at a different host than this site.
	 *
	 * @param string $src Script or stylesheet URL.
	 */
	private function is_third_party_src( string $src ): bool {
		$src = trim( $src );
		if ( '' === $src ) {
			return false;
		}

		$lower = strtolower( $src );
		if ( str_starts_with( $lower, 'data:' ) || str_starts_with( $lower, 'blob:' ) ) {
			return false;
		}

		$host = wp_parse_url( $src, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! is_string( $site_host ) || '' === $site_host ) {
			return false;
		}

		return strtolower( $host ) !== strtolower( $site_host );
	}

	/**
	 * Enqueue bootstrap JS to handle script unblocking.
	 */
	public function enqueue_bootstrap_js(): void {
		$settings = SettingsRepository::instance()->get_effective_settings();
		$blocker  = ! empty( $settings['blocker_enabled'] );
		$iframes  = ! empty( $settings['enabled_integrations']['iframe_placeholder'] );

		if ( ! $blocker && ! $iframes ) {
			return;
		}

		$category_slugs = array_keys( Categories::get_all() );
		$reload         = ! empty( $settings['reload_on_revoke'] );

		wp_enqueue_script(
			'wpeu-cs-blocker',
			WPEU_CS_URL . 'assets/js/blocker-bootstrap.js',
			array(),
			WPEU_CS_VERSION,
			true
		);

		wp_localize_script(
			'wpeu-cs-blocker',
			'wpeuCsBlocker',
			array(
				'categories'       => array_values( $category_slugs ),
				'reloadOnWithdraw' => $reload,
			)
		);
	}
}
