<?php
/**
 * Consent logger repository.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Consent;

/**
 * Handles database operations for consent logs.
 */
final class ConsentLogger {

	/**
	 * Get the table name.
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpeu_consent_log';
	}

	/**
	 * Log a consent event.
	 *
	 * @param array $data Event data.
	 * @return int|bool ID on success, false on failure.
	 */
	public function log( array $data ) {
		global $wpdb;

		$settings = get_option( 'wpeu_cs_settings', array() );
		if ( empty( $settings['consent_logging_enabled'] ) ) {
			return false;
		}

		$data = wp_parse_args(
			$data,
			array(
				'consent_uuid'    => '',
				'event_type'      => '',
				'categories'      => array(),
				'consent_mode'    => 'optin',
				'page_url'        => '',
				'locale'          => '',
				'banner_revision' => 0,
				'plugin_version'  => defined( 'WPEU_CS_VERSION' ) ? WPEU_CS_VERSION : '0.1.0',
				'ip_hash'         => null,
				'user_agent'      => null,
				'created_at'      => current_time( 'mysql', true ),
				'wp_user_id'      => get_current_user_id() ?: null,
			)
		);

		if ( is_array( $data['categories'] ) ) {
			$data['categories'] = wp_json_encode( $data['categories'] );
		}

		if ( empty( $data['config_snapshot'] ) ) {
			$data['config_snapshot'] = $this->generate_snapshot( $settings );
		}

		if ( is_array( $data['config_snapshot'] ) ) {
			$data['config_snapshot'] = wp_json_encode( $data['config_snapshot'] );
		}

		$inserted = $wpdb->insert( self::get_table_name(), $data );
		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Generate a configuration snapshot.
	 *
	 * @param array $settings Plugin settings.
	 * @return array
	 */
	private function generate_snapshot( array $settings ): array {
		global $wpdb;

		$cookies_table = $wpdb->prefix . 'wpeu_cookies';
		$inventory_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $cookies_table" );

		return array(
			'banner_revision'    => (int) ( $settings['consent_revision'] ?? 0 ),
			'enabled_categories' => $settings['enabled_categories'] ?? array(),
			'plugin_version'     => defined( 'WPEU_CS_VERSION' ) ? WPEU_CS_VERSION : '0.1.0',
			'cookie_count'       => $inventory_count,
		);
	}

	/**
	 * Get logs with filtering and pagination.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_logs( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'orderby'    => 'created_at',
			'order'      => 'DESC',
			'event_type' => '',
			'search'     => '',
			'per_page'   => 20,
			'paged'      => 1,
			'start_date' => '',
			'end_date'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$table = self::get_table_name();
		$where = array( '1=1' );
		$params = array();

		if ( ! empty( $args['event_type'] ) ) {
			$where[]  = 'event_type = %s';
			$params[] = $args['event_type'];
		}

		if ( ! empty( $args['start_date'] ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = $args['start_date'] . ' 00:00:00';
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where[]  = 'created_at <= %s';
			$params[] = $args['end_date'] . ' 23:59:59';
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(consent_uuid LIKE %s OR page_url LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$orderby   = in_array( $args['orderby'], array( 'created_at', 'event_type', 'consent_uuid' ), true ) ? $args['orderby'] : 'created_at';
		$order     = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$offset = ( $args['paged'] - 1 ) * $args['per_page'];
		$limit  = $wpdb->prepare( 'LIMIT %d, %d', $offset, $args['per_page'] );

		$sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order $limit";

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get total log count for filtering.
	 *
	 * @param array $args Query arguments.
	 * @return int
	 */
	public function get_total_logs( array $args = array() ): int {
		global $wpdb;

		$table = self::get_table_name();
		$where = array( '1=1' );
		$params = array();

		if ( ! empty( $args['event_type'] ) ) {
			$where[]  = 'event_type = %s';
			$params[] = $args['event_type'];
		}

		if ( ! empty( $args['start_date'] ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = $args['start_date'] . ' 00:00:00';
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where[]  = 'created_at <= %s';
			$params[] = $args['end_date'] . ' 23:59:59';
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(consent_uuid LIKE %s OR page_url LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT COUNT(*) FROM $table WHERE $where_sql";

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Cleanup expired logs based on retention setting.
	 *
	 * @return int Number of rows deleted.
	 */
	public function cleanup_expired_logs(): int {
		global $wpdb;

		$settings  = get_option( 'wpeu_cs_settings', array() );
		$retention = (int) ( $settings['consent_log_retention'] ?? 365 );

		if ( $retention <= 0 ) {
			return 0;
		}

		$table = self::get_table_name();
		$date  = gmdate( 'Y-m-d H:i:s', time() - ( $retention * DAY_IN_SECONDS ) );

		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE created_at < %s", $date ) );
	}
}
