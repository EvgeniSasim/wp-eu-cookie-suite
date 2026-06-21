<?php
/**
 * Google Analytics cookie guard and enqueued-script fallback blocking.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Integrations;

use WPEU\CookieSuite\Settings\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
		add_filter( 'style_loader_tag', array( $this, 'filter_style_loader_tag' ), 10, 4 );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_blocked_scripts' ), 9999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_cookie_cleanup_js' ), 20 );
	}

	/**
	 * Whether the guard integration is enabled (default on).
	 */
	public function is_enabled(): bool {
		$settings     = SettingsRepository::get_effective_settings();
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
	 * Whether marketing-related Google resources should be blocked now.
	 */
	public function should_block_marketing(): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		return ! wpeu_cs_user_has_consent( Categories::MARKETING );
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
	 * Whether a script handle or URL is a statistics/analytics resource.
	 *
	 * @param string $handle Script handle.
	 * @param string $src    Script URL.
	 */
	public static function is_statistics_script( string $handle, string $src ): bool {
		if ( 'google_gtagjs' === $handle ) {
			return true;
		}

		foreach ( array( 'googletagmanager.com', 'google-analytics.com' ) as $pattern ) {
			if ( str_contains( $src, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a script handle or URL is a marketing-related Google resource.
	 *
	 * @param string $handle Script handle.
	 * @param string $src    Script URL.
	 */
	public static function is_marketing_script( string $handle, string $src ): bool {
		unset( $handle );

		foreach ( array(
			'google.com/recaptcha',
			'gstatic.com/recaptcha',
			'fonts.googleapis.com',
			'fonts.gstatic.com',
		) as $pattern ) {
			if ( str_contains( $src, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a script handle or URL should be blocked without statistics consent.
	 *
	 * @param string $handle Script handle.
	 * @param string $src    Script URL.
	 * @deprecated Use is_statistics_script() or is_marketing_script().
	 */
	public static function should_block_script( string $handle, string $src ): bool {
		return self::is_statistics_script( $handle, $src ) || self::is_marketing_script( $handle, $src );
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
		if ( self::is_statistics_script( $handle, $src ) && $this->should_block() ) {
			return $this->rewrite_blocked_tag( $tag, 'script', Categories::STATISTICS );
		}

		if ( self::is_marketing_script( $handle, $src ) && $this->should_block_marketing() ) {
			return $this->rewrite_blocked_tag( $tag, 'script', Categories::MARKETING );
		}

		return $tag;
	}

	/**
	 * Rewrite a script or link tag to text/plain with a consent category.
	 *
	 * @param string $tag      Tag HTML.
	 * @param string $element  Element name (`script` or `link`).
	 * @param string $category Consent category slug.
	 */
	private function rewrite_blocked_tag( string $tag, string $element, string $category ): string {
		if ( preg_match( '/type=["\']([^"\']+)["\']/i', $tag ) ) {
			$tag = preg_replace( '/type=["\']([^"\']+)["\']/i', 'type="text/plain"', $tag );
		} else {
			$tag = preg_replace( '/^<' . $element . '/i', '<' . $element . ' type="text/plain"', $tag );
		}

		if ( ! str_contains( $tag, 'data-category=' ) ) {
			$tag = preg_replace(
				'/^<' . $element . '/i',
				'<' . $element . ' data-category="' . esc_attr( $category ) . '"',
				$tag
			);
		}

		return $tag;
	}

	/**
	 * Rewrite enqueued Google stylesheets to text/plain when blocked.
	 *
	 * @param string $html   Link tag HTML.
	 * @param string $handle Style handle.
	 * @param string $href   Style URL.
	 * @param string $media  Media attribute.
	 */
	public function filter_style_loader_tag( string $html, string $handle, string $href, string $media ): string {
		unset( $handle, $media );

		if ( ! $this->should_block_marketing() ) {
			return $html;
		}

		if ( ! str_contains( $href, 'fonts.googleapis.com' ) && ! str_contains( $href, 'fonts.gstatic.com' ) ) {
			return $html;
		}

		return $this->rewrite_blocked_tag( $html, 'link', Categories::MARKETING );
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
	public function enqueue_cookie_cleanup_js(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$has_consent = wpeu_cs_user_has_consent( Categories::STATISTICS ) ? 'true' : 'false';

		wp_register_script( 'wpeu-cs-ga-guard', false, array(), WPEU_CS_VERSION, true );
		wp_enqueue_script( 'wpeu-cs-ga-guard' );
		wp_add_inline_script(
			'wpeu-cs-ga-guard',
			'(function(){var hasStatistics=' . $has_consent . ';'
			. 'function isGaCookie(name){return/^(_ga(_[\\w]+)?|_gid|_gat)/.test(name);}'
			. 'function clearGaCookies(){var hostParts=location.hostname.split(".");var domains=[location.hostname];'
			. 'if(hostParts.length>1){domains.push("."+hostParts.slice(-2).join("."));}'
			. 'document.cookie.split(";").forEach(function(part){var name=part.split("=")[0].trim();if(!isGaCookie(name)){return;}'
			. 'domains.forEach(function(domain){document.cookie=name+"=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain="+domain;'
			. 'document.cookie=name+"=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";});});}'
			. 'function statisticsGranted(detail){if(!detail||typeof detail!=="object"){return false;}'
			. 'if(detail.statistics===true||detail.statistics==="allow"||detail.statistics===1){return true;}'
			. 'if(detail.categories&&detail.categories.statistics){return true;}return false;}'
			. 'if(!hasStatistics){clearGaCookies();}'
			. 'document.addEventListener("wpeu-consent-updated",function(event){if(!statisticsGranted(event.detail||{})){clearGaCookies();}});'
			. 'document.addEventListener("wpeu-consent-revoked",function(){clearGaCookies();});})();'
		);
	}
}
