<?php
/**
 * Script Blocker class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


namespace WPEU\CookieSuite\Frontend;

use WPEU\CookieSuite\Consent\Categories;

/**
 * Handles blocking and unblocking of third-party scripts.
 */
final class ScriptBlocker {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_start_buffer' ) );
		add_action( 'wp_head', array( $this, 'inject_bootstrap_js' ), 1 );
	}

	/**
	 * Start output buffer if necessary.
	 */
	public function maybe_start_buffer(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() ) {
			return;
		}

		$settings = get_option( 'wpeu_cs_settings', array() );
		$blocker  = ! empty( $settings['blocker_enabled'] );
		$iframes  = ! empty( $settings['enabled_integrations']['iframe_placeholder'] );

		if ( ! $blocker && ! $iframes ) {
			return;
		}

		ob_start( array( $this, 'process_output' ) );
	}

	/**
	 * Process HTML output and block scripts.
	 *
	 * @param string $output The HTML output.
	 * @return string The processed HTML.
	 */
	public function process_output( string $output ): string {
		if ( empty( $output ) ) {
			return $output;
		}

		$settings = get_option( 'wpeu_cs_settings', array() );
		$blocker  = ! empty( $settings['blocker_enabled'] );

		if ( $blocker && str_contains( $output, '<script' ) ) {
			$output = preg_replace_callback(
				'/<script\b[^>]*>(.*?)<\/script>/is',
				array( $this, 'process_script_tag' ),
				$output
			);
		}

		if ( $blocker && str_contains( $output, '<link' ) ) {
			$output = preg_replace_callback(
				'/<link\b[^>]*>/i',
				array( $this, 'process_link_tag' ),
				$output
			);
		}

		if ( str_contains( $output, '<iframe' ) ) {
			$output = IframeProcessor::process_iframes( $output );
		}

		return $output;
	}

	/**
	 * Process an individual script tag.
	 *
	 * @param array $matches Regex matches.
	 * @return string Modified script tag.
	 */
	private function process_script_tag( array $matches ): string {
		$full_tag = $matches[0];
		$content  = $matches[1];

		$category = $this->get_block_category( $full_tag, $content );

		if ( $category && ! wpeu_cs_user_has_consent( $category ) ) {
			// Change type to text/plain and add data-category.
			if ( preg_match( '/type=["\']([^"\']+)["\']/i', $full_tag ) ) {
				$full_tag = preg_replace( '/type=["\']([^"\']+)["\']/i', 'type="text/plain"', $full_tag );
			} else {
				$full_tag = preg_replace( '/^<script/i', '<script type="text/plain"', $full_tag );
			}

			// Add data-category if not present.
			if ( ! str_contains( $full_tag, 'data-category=' ) ) {
				$full_tag = preg_replace( '/^<script/i', '<script data-category="' . esc_attr( $category ) . '"', $full_tag );
			}
		}

		return $full_tag;
	}

	/**
	 * Process an individual link tag (stylesheets, preconnect, etc.).
	 *
	 * @param array $matches Regex matches.
	 * @return string Modified link tag.
	 */
	private function process_link_tag( array $matches ): string {
		$full_tag = $matches[0];
		$category = $this->get_block_category( $full_tag, '' );

		if ( ! $category || wpeu_cs_user_has_consent( $category ) ) {
			return $full_tag;
		}

		if ( preg_match( '/type=["\']([^"\']+)["\']/i', $full_tag ) ) {
			$full_tag = preg_replace( '/type=["\']([^"\']+)["\']/i', 'type="text/plain"', $full_tag );
		} else {
			$full_tag = preg_replace( '/^<link/i', '<link type="text/plain"', $full_tag );
		}

		if ( ! str_contains( $full_tag, 'data-category=' ) ) {
			$full_tag = preg_replace( '/^<link/i', '<link data-category="' . esc_attr( $category ) . '"', $full_tag );
		}

		return $full_tag;
	}

	/**
	 * Get the consent category for a script if it should be blocked.
	 *
	 * @param string $tag Full script tag.
	 * @param string $content Script content.
	 * @return string|null Category if it should be blocked, null otherwise.
	 */
	private function get_block_category( string $tag, string $content ): ?string {
		$src = '';
		if ( preg_match( '/(?:src|href)=["\']([^"\']+)["\']/i', $tag, $src_matches ) ) {
			$src = $src_matches[1];
		}

		// Whitelist WordPress core scripts.
		if ( ! empty( $src ) ) {
			$local_host = wp_parse_url( home_url(), PHP_URL_HOST );
			$src_host   = wp_parse_url( $src, PHP_URL_HOST );

			// If it's a local script, check if it's core.
			if ( ! $src_host || $src_host === $local_host ) {
				if ( str_contains( $src, '/wp-includes/' ) || str_contains( $src, '/wp-admin/' ) ) {
					return null;
				}
				if ( str_contains( $src, 'jquery' ) ) {
					return null;
				}
			}
		}

		$settings         = get_option( 'wpeu_cs_settings', array() );
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

				// Complianz-style -url- marker.
				if ( str_starts_with( $rule, '-url-' ) ) {
					$pattern = substr( $rule, 5 );
					if ( ! empty( $src ) && str_contains( $src, $pattern ) ) {
						return 'marketing'; // Default to marketing for custom rules.
					}
				} elseif ( str_contains( $src, $rule ) || str_contains( $content, $rule ) ) {
					return 'marketing';
				}
			}
		}

		// Apply filter for known script tags (array of regex or substring rules).
		$known_tags = apply_filters( 'wpeu_known_script_tags', array() );
		foreach ( $known_tags as $key => $value ) {
			$pattern  = is_string( $key ) ? $key : $value;
			$category = is_string( $key ) ? $value : 'marketing';

			// Check if pattern is a regex.
			if ( @preg_match( $pattern, '' ) !== false ) {
				if ( preg_match( $pattern, $src ) || preg_match( $pattern, $content ) ) {
					return $category;
				}
			} elseif ( str_contains( $src, (string) $pattern ) || str_contains( $content, (string) $pattern ) ) {
				return $category;
			}
		}

		return null;
	}

	/**
	 * Inject bootstrap JS to handle script unblocking.
	 */
	public function inject_bootstrap_js(): void {
		$settings = get_option( 'wpeu_cs_settings', array() );
		$blocker  = ! empty( $settings['blocker_enabled'] );
		$iframes  = ! empty( $settings['enabled_integrations']['iframe_placeholder'] );

		if ( ! $blocker && ! $iframes ) {
			return;
		}
		$category_slugs = array_keys( Categories::get_all() );
		?>
		<script type="text/javascript">
			(function() {
				const allCategorySlugs = <?php echo wp_json_encode( array_values( $category_slugs ) ); ?>;
				const activateScripts = function(consentData) {
					const scripts = document.querySelectorAll('script[type="text/plain"][data-category]');
					scripts.forEach(function(script) {
						const category = script.getAttribute('data-category');
						if (consentData[category]) {
							const newScript = document.createElement('script');
							Array.from(script.attributes).forEach(attr => {
								if (attr.name !== 'type' && attr.name !== 'data-category') {
									newScript.setAttribute(attr.name, attr.value);
								}
							});
							newScript.type = 'text/javascript';
							newScript.text = script.text;
							script.parentNode.insertBefore(newScript, script);
							script.parentNode.removeChild(script);
						}
					});

					const links = document.querySelectorAll('link[type="text/plain"][data-category]');
					links.forEach(function(link) {
						const category = link.getAttribute('data-category');
						if (consentData[category]) {
							const newLink = document.createElement('link');
							Array.from(link.attributes).forEach(attr => {
								if (attr.name !== 'type' && attr.name !== 'data-category') {
									newLink.setAttribute(attr.name, attr.value);
								}
							});
							link.parentNode.insertBefore(newLink, link);
							link.parentNode.removeChild(link);
						}
					});

					const placeholders = document.querySelectorAll('.wpeu-cs-iframe-placeholder[data-category]');
					placeholders.forEach(function(placeholder) {
						const category = placeholder.getAttribute('data-category');
						if (consentData[category]) {
							const hiddenIframe = placeholder.querySelector('.wpeu-cs-hidden-iframe');
							if (hiddenIframe) {
								const originalTag = atob(hiddenIframe.textContent);
								const container = document.createElement('div');
								container.innerHTML = originalTag;
								const iframe = container.firstChild;
								placeholder.parentNode.insertBefore(iframe, placeholder);
								placeholder.parentNode.removeChild(placeholder);
							}
						}
					});
				};

				document.addEventListener('wpeu-consent-updated', function(e) {
					activateScripts(e.detail);
				});

				document.addEventListener('click', function(e) {
					if (e.target.classList.contains('wpeu-cs-accept-category')) {
						const category = e.target.getAttribute('data-category');
						if (category && window.CookieConsent && typeof window.CookieConsent.acceptCategory === 'function') {
							window.CookieConsent.acceptCategory(category);
						}
					}
				});

				// Check on initial load if consent is already there.
				window.addEventListener('load', function() {
					if (!window.CookieConsent || typeof window.CookieConsent.acceptedCategory !== 'function') {
						return;
					}
					if (typeof window.CookieConsent.validConsent === 'function' && !window.CookieConsent.validConsent()) {
						return;
					}
					const consentData = {};
					allCategorySlugs.forEach(function (cat) {
						consentData[cat] = window.CookieConsent.acceptedCategory(cat);
					});
					activateScripts(consentData);
				});
			})();
		</script>
		<?php
	}
}
