<?php
/**
 * Consent logger repository.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		if ( array_key_exists( 'consent_logging_enabled', $settings ) && empty( $settings['consent_logging_enabled'] ) ) {
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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table name.
		$inventory_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cookies_table}" );

		return array(
			'banner_revision'    => (int) ( $settings['consent_revision'] ?? 0 ),
			'enabled_categories' => $settings['enabled_categories'] ?? array(),
			'plugin_version'     => defined( 'WPEU_CS_VERSION' ) ? WPEU_CS_VERSION : '0.1.0',
			'cookie_count'       => $inventory_count,
		);
	}

	/**
	 * Build WHERE clause fragments for log queries.
	 *
	 * @param array $args Query arguments.
	 * @return array{0:string,1:array<int,mixed>}
	 */
	private function build_log_where( array $args ): array {
		global $wpdb;

		$where  = array( '1=1' );
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

		return array( implode( ' AND ', $where ), $params );
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
		list( $where_sql, $params ) = $this->build_log_where( $args );

		$orderby = in_array( $args['orderby'], array( 'created_at', 'event_type', 'consent_uuid' ), true ) ? $args['orderby'] : 'created_at';
		$order   = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$offset  = ( max( 1, (int) $args['paged'] ) - 1 ) * (int) $args['per_page'];
		$limit   = (int) $args['per_page'];

		if ( empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d, %d",
					$offset,
					$limit
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d, %d",
					...array_merge( $params, array( $offset, $limit ) )
				),
				ARRAY_A
			);
		}

		return is_array( $results ) ? $results : array();
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
		list( $where_sql, $params ) = $this->build_log_where( $args );

		if ( empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", ...$params ) );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table name.
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $date ) );
	}
}
