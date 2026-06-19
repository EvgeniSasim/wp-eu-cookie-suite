<?php
/**
 * Iframe Placeholder integration.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use WPEU\CookieSuite\Frontend\IframeProcessor;

/**
 * Replaces blocked iframes with placeholders.
 */
final class IframePlaceholder {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings = get_option( 'wpeu_cs_settings', array() );
		$enabled  = $settings['enabled_integrations']['iframe_placeholder'] ?? false;

		if ( ! $enabled ) {
			return;
		}

		add_filter( 'the_content', array( $this, 'process_iframes' ), 99 );
	}

	/**
	 * Process iframes in content.
	 *
	 * @param string $content The content.
	 * @return string
	 */
	public function process_iframes( string $content ): string {
		return IframeProcessor::process_iframes( $content );
	}
}
