<?php
/**
 * Consent categories model.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Consent;

/**
 * Categories class.
 */
final class Categories {

	/**
	 * Category constants.
	 */
	const NECESSARY   = 'necessary';
	const PREFERENCES = 'preferences';
	const STATISTICS  = 'statistics';
	const MARKETING   = 'marketing';

	/**
	 * Get all categories.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_all(): array {
		$categories = array(
			self::NECESSARY   => array(
				'label'       => __( 'Strictly Necessary', 'wp-eu-cookie-suite' ),
				'description' => __( 'These cookies are essential for the website to function properly.', 'wp-eu-cookie-suite' ),
				'enabled'     => true,
				'read_only'   => true,
			),
			self::PREFERENCES => array(
				'label'       => __( 'Preferences', 'wp-eu-cookie-suite' ),
				'description' => __( 'These cookies allow the website to remember choices you make.', 'wp-eu-cookie-suite' ),
				'enabled'     => false,
				'read_only'   => false,
			),
			self::STATISTICS  => array(
				'label'       => __( 'Statistics', 'wp-eu-cookie-suite' ),
				'description' => __( 'These cookies help us understand how visitors interact with the website.', 'wp-eu-cookie-suite' ),
				'enabled'     => false,
				'read_only'   => false,
			),
			self::MARKETING   => array(
				'label'       => __( 'Marketing', 'wp-eu-cookie-suite' ),
				'description' => __( 'These cookies are used to track visitors across websites to display relevant ads.', 'wp-eu-cookie-suite' ),
				'enabled'     => false,
				'read_only'   => false,
			),
		);

		/**
		 * Filter the consent categories.
		 *
		 * @param array $categories The consent categories.
		 */
		return apply_filters( 'wpeu_consent_categories', $categories );
	}
}
