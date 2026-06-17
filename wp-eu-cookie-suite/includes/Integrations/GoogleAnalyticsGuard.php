<?php
/**
 * Google Analytics cookie guard and enqueued-script fallback blocking.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Integrations;

use WPEU\CookieSuite\Consent\Categories;

/**
 * Clears GA cookies without statistics consent and blocks enqueued gtag scripts.
 */
final class GoogleAnalyticsGuard {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		add_action( 'send_headers', array( $this, 'expire_ga_cookies' ) );
		add_filter( 'script_loader_tag', array( $this, 'filter_script_loader_tag' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_blocked_scripts' ), 9999 );
		add_action( 'wp_footer', array( $this, 'inject_cookie_cleanup_js' ), 1 );
	}

	/**
	 * Whether the guard integration is enabled (default on).
	 */
	public function is_enabled(): bool {
		$settings     = get_option( 'wpeu_cs_settings', array() );
		$integrations = $settings['enabled_integrations'] ?? array();

		if ( ! array_key_exists( 'ga_cookie_guard', $integrations ) ) {
			return true;
		}

		return ! empty( $integrations['ga_cookie_guard'] );
	}

	/**
	 * Whether statistics scripts/cookies should be blocked now.
	 */
	public function should_block(): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		return ! wpeu_cs_user_has_consent( Categories::STATISTICS );
	}

	/**
	 * Whether a cookie name belongs to Google Analytics.
	 *
	 * @param string $name Cookie name.
	 */
	public static function is_ga_cookie_name( string $name ): bool {
		return (bool) preg_match( '/^(_ga(_[\w]+)?|_gid|_gat)/', $name );
	}

	/**
	 * Whether a script handle or URL should be blocked without statistics consent.
	 *
	 * @param string $handle Script handle.
	 * @param string $src    Script URL.
	 */
	public static function should_block_script( string $handle, string $src ): bool {
		if ( 'google_gtagjs' === $handle ) {
			return true;
		}

		if ( str_contains( $src, 'googletagmanager.com/gtag/js' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Expire GA cookies server-side when statistics consent is missing.
	 */
	public function expire_ga_cookies(): void {
		if ( ! $this->should_block() || headers_sent() ) {
			return;
		}

		foreach ( array_keys( $_COOKIE ) as $name ) {
			$name = sanitize_key( (string) $name );
			if ( ! self::is_ga_cookie_name( $name ) ) {
				continue;
			}

			setcookie( $name, '', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
			if ( COOKIEPATH !== SITECOOKIEPATH ) {
				setcookie( $name, '', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
			}
		}
	}

	/**
	 * Rewrite enqueued gtag scripts to text/plain when blocked.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script URL.
	 */
	public function filter_script_loader_tag( string $tag, string $handle, string $src ): string {
		if ( ! $this->should_block() || ! self::should_block_script( $handle, $src ) ) {
			return $tag;
		}

		if ( preg_match( '/type=["\']([^"\']+)["\']/i', $tag ) ) {
			$tag = preg_replace( '/type=["\']([^"\']+)["\']/i', 'type="text/plain"', $tag );
		} else {
			$tag = preg_replace( '/^<script/i', '<script type="text/plain"', $tag );
		}

		if ( ! str_contains( $tag, 'data-category=' ) ) {
			$tag = preg_replace(
				'/^<script/i',
				'<script data-category="' . esc_attr( Categories::STATISTICS ) . '"',
				$tag
			);
		}

		return $tag;
	}

	/**
	 * Dequeue gtag scripts that bypass the output buffer.
	 */
	public function dequeue_blocked_scripts(): void {
		if ( ! $this->should_block() ) {
			return;
		}

		foreach ( array( 'google_gtagjs', 'googlesitekit-consent-mode' ) as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}

	/**
	 * Client-side GA cookie cleanup on deny/revoke.
	 */
	public function inject_cookie_cleanup_js(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$has_consent = wpeu_cs_user_has_consent( Categories::STATISTICS ) ? 'true' : 'false';
		?>
		<script id="wpeu-cs-ga-guard">
		(function () {
			var hasStatistics = <?php echo $has_consent; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;

			function isGaCookie(name) {
				return /^(_ga(_[\w]+)?|_gid|_gat)/.test(name);
			}

			function clearGaCookies() {
				var hostParts = location.hostname.split('.');
				var domains = [location.hostname];
				if (hostParts.length > 1) {
					domains.push('.' + hostParts.slice(-2).join('.'));
				}

				document.cookie.split(';').forEach(function (part) {
					var name = part.split('=')[0].trim();
					if (!isGaCookie(name)) {
						return;
					}
					domains.forEach(function (domain) {
						document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + domain;
						document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
					});
				});
			}

			function statisticsGranted(detail) {
				if (!detail || typeof detail !== 'object') {
					return false;
				}
				if (detail.statistics === true || detail.statistics === 'allow' || detail.statistics === 1) {
					return true;
				}
				if (detail.categories && detail.categories.statistics) {
					return true;
				}
				return false;
			}

			if (!hasStatistics) {
				clearGaCookies();
			}

			document.addEventListener('wpeu-consent-updated', function (event) {
				if (!statisticsGranted(event.detail || {})) {
					clearGaCookies();
				}
			});

			document.addEventListener('wpeu-consent-revoked', function () {
				clearGaCookies();
			});
		})();
		</script>
		<?php
	}
}
