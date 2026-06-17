<?php
/**
 * Script Registry class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Frontend;

use WPEU\CookieSuite\Consent\Categories;

/**
 * Registry of known third-party scripts and their categories.
 */
final class ScriptRegistry {

	/**
	 * Get all known services.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_services(): array {
		return array(
			'google-analytics' => array(
				'label'    => 'Google Analytics',
				'category' => Categories::STATISTICS,
				'patterns' => array(
					'google-analytics.com',
					'analytics.js',
					'ga.js',
					'gtag(',
					'ga(',
				),
			),
			'gtm'              => array(
				'label'    => 'Google Tag Manager',
				'category' => Categories::STATISTICS,
				'patterns' => array(
					'googletagmanager.com',
					'gtm.js',
				),
			),
			'facebook-pixel'   => array(
				'label'    => 'Facebook Pixel',
				'category' => Categories::MARKETING,
				'patterns' => array(
					'connect.facebook.net',
					'fbevents.js',
				),
			),
			'hotjar'           => array(
				'label'    => 'Hotjar',
				'category' => Categories::STATISTICS,
				'patterns' => array(
					'hotjar.com',
					'static.hotjar.com',
					'_hj',
				),
			),
			'clarity'          => array(
				'label'    => 'Microsoft Clarity',
				'category' => Categories::STATISTICS,
				'patterns' => array(
					'clarity.ms',
				),
			),
			'google-maps'      => array(
				'label'    => 'Google Maps',
				'category' => Categories::MARKETING,
				'patterns' => array(
					'maps.google.com',
					'maps.googleapis.com',
				),
			),
			'youtube'          => array(
				'label'    => 'YouTube',
				'category' => Categories::MARKETING,
				'patterns' => array(
					'youtube.com',
					'youtube-nocookie.com',
					'youtu.be',
				),
			),
			'vimeo'            => array(
				'label'    => 'Vimeo',
				'category' => Categories::MARKETING,
				'patterns' => array(
					'vimeo.com',
					'player.vimeo.com',
				),
			),
		);
	}
}
