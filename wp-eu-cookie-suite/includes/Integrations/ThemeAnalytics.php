<?php
/**
 * Theme Analytics integration.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Integrations;

/**
 * Ensures theme-level analytics (ACF) respects consent.
 */
final class ThemeAnalytics {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings = get_option( 'wpeu_cs_settings', array() );
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
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $value;
		}

		if ( ! wpeu_cs_user_has_consent( 'statistics' ) ) {
			return '';
		}

		return $value;
	}
}
