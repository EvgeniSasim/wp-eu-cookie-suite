<?php
/**
 * Cookie repository class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


namespace WPEU\CookieSuite\Scanner;

/**
 * Handles database operations for cookies.
 */
final class CookieRepository {

	/**
	 * Get the table name.
	 *
	 * @return string
	 */
	private function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpeu_cookies';
	}

	/**
	 * Get all cookies.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function all( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'orderby'  => 'name',
			'order'    => 'ASC',
			'category' => '',
			'search'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$table = $this->get_table_name();
		list( $where_sql, $params ) = $this->build_where( $args );

		$orderby = in_array( $args['orderby'], array( 'name', 'domain', 'category', 'service', 'detected_at' ), true ) ? $args['orderby'] : 'name';
		$order   = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';

		if ( empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$results = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order}", ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order}",
					...$params
				),
				ARRAY_A
			);
		}

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Build WHERE clause fragments for cookie queries.
	 *
	 * @param array $args Query arguments.
	 * @return array{0:string,1:array<int,mixed>}
	 */
	private function build_where( array $args ): array {
		global $wpdb;

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['category'] ) ) {
			$where[]  = 'category = %s';
			$params[] = $args['category'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(name LIKE %s OR domain LIKE %s OR service LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		return array( implode( ' AND ', $where ), $params );
	}

	/**
	 * Get a single cookie by ID.
	 *
	 * @param int $id Cookie ID.
	 * @return array|null
	 */
	public function get( int $id ): ?array {
		global $wpdb;
		$table = $this->get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table name.
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * Upsert a cookie.
	 *
	 * @param array $data Cookie data.
	 * @return int|bool ID on success, false on failure.
	 */
	public function upsert( array $data ) {
		global $wpdb;
		$table = $this->get_table_name();

		$data = wp_parse_args(
			$data,
			array(
				'name'        => '',
				'domain'      => '',
				'category'    => 'necessary',
				'description' => '',
				'duration'    => '',
				'service'     => '',
				'detected_at' => '0000-00-00 00:00:00',
				'source'      => '',
			)
		);

		if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
			$id = (int) $data['id'];
			unset( $data['id'] );
			$updated = $wpdb->update( $table, $data, array( 'id' => $id ) );
			return false !== $updated ? $id : false;
		}

		$inserted = $wpdb->insert( $table, $data );
		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Delete a cookie.
	 *
	 * @param int $id Cookie ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		$table = $this->get_table_name();
		return (bool) $wpdb->delete( $table, array( 'id' => $id ) );
	}

	/**
	 * Import results from scan.
	 *
	 * @param array $results Scan results.
	 * @return int Number of items imported/updated.
	 */
	public function import_from_scan( array $results ): int {
		global $wpdb;
		$table = $this->get_table_name();
		$count = 0;

		$items = array_merge( $results['cookies'] ?? array(), $results['scripts'] ?? array() );

		foreach ( $items as $item ) {
			$name   = sanitize_text_field( $item['name'] );
			$domain = isset( $item['domain'] ) ? sanitize_text_field( $item['domain'] ) : '';
			$type   = $item['type'] ?? '';

			// Scripts might use 'name' for domain in scan results
			if ( 'script' === $type && empty( $domain ) ) {
				$domain = $name;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table name.
			$existing_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE name = %s AND domain = %s",
					$name,
					$domain
				)
			);

			$data = array(
				'name'        => $name,
				'domain'      => $domain,
				'category'    => sanitize_text_field( $item['category'] ),
				'detected_at' => current_time( 'mysql' ),
				'source'      => 'scan',
			);

			if ( $existing_id ) {
				// Don't overwrite category if it's already set to something else than necessary?
				// For now, let's just update detected_at and source if it's new.
				// Actually, the requirement says "merge scan results into inventory".
				$wpdb->update( $table, $data, array( 'id' => $existing_id ) );
			} else {
				$wpdb->insert( $table, $data );
			}
			$count++;
		}

		return $count;
	}
}
