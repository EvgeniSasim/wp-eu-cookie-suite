<?php
/**
 * Theme Analytics integration.
 *
 * Blocks ACF theme analytics fields until statistics consent is granted.
 * Themes must output analytics via get_field(); this plugin does not inject raw snippets.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Integrations;

use WPEU\CookieSuite\Settings\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Ensures theme-level analytics (ACF) respects consent.
 */
final class ThemeAnalytics {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings = SettingsRepository::get_effective_settings();
		$enabled  = $settings['enabled_integrations']['theme_analytics'] ?? false;

		if ( ! $enabled ) {
			return;
		}

		$field_name = $settings['theme_analytics_field'] ?? 'analytics';
		add_filter( "acf/format_value/name={$field_name}", array( $this, 'intercept_analytics_field' ), 10, 3 );
	}

	/**
	 * Intercept ACF analytics field.
	 *
	 * @param mixed $value   The field value.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field array.
	 * @return mixed
	 */
	public function intercept_analytics_field( $value, $post_id, array $field ) {
		unset( $post_id, $field );

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $value;
		}

		if ( ! wpeu_cs_user_has_consent( 'statistics' ) ) {
			return '';
		}

		return $value;
	}
}
