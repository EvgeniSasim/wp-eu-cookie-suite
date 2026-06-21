<?php
/**
 * Consent log list table.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use WPEU\CookieSuite\Consent\ConsentLogger;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * ConsentLogTable class.
 */
final class ConsentLogTable extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'consent_log',
				'plural'   => 'consent_logs',
				'ajax'     => false,
				'screen'   => 'toplevel_page_' . Admin::PAGE_SLUG,
			)
		);
	}

	/**
	 * Define columns.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return array(
			'created_at'      => __( 'Date', 'privaro-cookie-consent-banner' ),
			'event_type'      => __( 'Event', 'privaro-cookie-consent-banner' ),
			'categories'      => __( 'Categories', 'privaro-cookie-consent-banner' ),
			'locale'          => __( 'Locale', 'privaro-cookie-consent-banner' ),
			'page_url'        => __( 'Page URL', 'privaro-cookie-consent-banner' ),
			'consent_uuid'    => __( 'Visitor ID', 'privaro-cookie-consent-banner' ),
			'ip_hash'         => __( 'IP Hash', 'privaro-cookie-consent-banner' ),
			'banner_revision' => __( 'Revision', 'privaro-cookie-consent-banner' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'created_at'   => array( 'created_at', true ),
			'event_type'   => array( 'event_type', false ),
			'consent_uuid' => array( 'consent_uuid', false ),
		);
	}

	/**
	 * Render columns.
	 *
	 * @param array  $item        Log item.
	 * @param string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'created_at':
				$actions = array(
					'snapshot' => sprintf(
						'<a href="%s">%s</a>',
						wp_nonce_url( admin_url( 'admin.php?page=wpeu-cs-settings&tab=consent_log&action=wpeu_cs_download_snapshot&id=' . $item['id'] ), 'wpeu_cs_download_snapshot_' . $item['id'] ),
						__( 'Download Snapshot', 'privaro-cookie-consent-banner' )
					),
				);
				return sprintf( '%s %s', esc_html( $item['created_at'] ), $this->row_actions( $actions ) );

			case 'event_type':
				return '<code>' . esc_html( $item['event_type'] ) . '</code>';

			case 'categories':
				$cats = json_decode( $item['categories'], true );
				if ( ! is_array( $cats ) || empty( $cats ) ) {
					return '—';
				}
				$labels = array();
				foreach ( $cats as $slug => $status ) {
					if ( $status ) {
						$labels[] = $slug;
					}
				}
				return empty( $labels ) ? '—' : esc_html( implode( ', ', $labels ) );

			case 'page_url':
				$url = $item['page_url'];
				return sprintf( '<a href="%1$s" target="_blank" title="%1$s">%2$s</a>', esc_url( $url ), esc_html( wp_parse_url( $url, PHP_URL_PATH ) ?: '/' ) );

			case 'consent_uuid':
				$uuid = (string) ( $item['consent_uuid'] ?? '' );
				if ( '' === $uuid ) {
					return '—';
				}
				return '<code>' . esc_html( substr( $uuid, 0, 8 ) ) . '...</code>';

			case 'ip_hash':
				return $item['ip_hash'] ? '<code>' . esc_html( substr( $item['ip_hash'], 0, 8 ) ) . '...</code>' : '—';

			case 'locale':
			case 'banner_revision':
				return esc_html( $item[ $column_name ] );

			default:
				return isset( $item[ $column_name ] ) ? esc_html( (string) $item[ $column_name ] ) : '';
		}
	}

	/**
	 * Add bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions(): array {
		return array();
	}

	/**
	 * Message when no log rows match the current filters.
	 */
	public function no_items(): void {
		esc_html_e( 'No consent log entries found.', 'privaro-cookie-consent-banner' );
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items(): void {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable, $this->get_primary_column_name() );

		$logger   = new ConsentLogger();
		$per_page = 20;

		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( (string) $_GET['orderby'] ) ) : 'created_at';
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( (string) $_GET['order'] ) ) : 'DESC';
		$paged   = $this->get_pagenum();

		$args = array(
			'orderby'    => $orderby,
			'order'      => $order,
			'per_page'   => $per_page,
			'paged'      => $paged,
			'event_type' => isset( $_GET['event_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['event_type'] ) ) : '',
			'search'     => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '',
			'start_date' => isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['start_date'] ) ) : '',
			'end_date'   => isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['end_date'] ) ) : '',
		);

		$this->items = $logger->get_logs( $args );
		$total_items = $logger->get_total_logs( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which Which side (top/bottom).
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$event_type = isset( $_GET['event_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['event_type'] ) ) : '';
		$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '';
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['start_date'] ) ) : '';
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['end_date'] ) ) : '';

		$export_args = array(
			'page'   => 'wpeu-cs-settings',
			'tab'    => 'consent_log',
			'action' => 'wpeu_cs_export_logs_csv',
		);
		if ( $event_type ) {
			$export_args['event_type'] = $event_type;
		}
		if ( $search ) {
			$export_args['s'] = $search;
		}
		?>
		<div class="alignleft actions">
			<select name="event_type">
				<option value=""><?php esc_html_e( 'All Events', 'privaro-cookie-consent-banner' ); ?></option>
				<option value="accept_all" <?php selected( $event_type, 'accept_all' ); ?>>accept_all</option>
				<option value="reject_all" <?php selected( $event_type, 'reject_all' ); ?>>reject_all</option>
				<option value="save_preferences" <?php selected( $event_type, 'save_preferences' ); ?>>save_preferences</option>
				<option value="revoke" <?php selected( $event_type, 'revoke' ); ?>>revoke</option>
				<option value="policy_revision" <?php selected( $event_type, 'policy_revision' ); ?>>policy_revision</option>
			</select>
			<input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" placeholder="Start Date">
			<input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" placeholder="End Date">
			<?php submit_button( __( 'Filter', 'privaro-cookie-consent-banner' ), 'button', 'filter_action', false ); ?>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( $export_args, admin_url( 'admin.php' ) ), 'wpeu_cs_export_logs_csv' ) ); ?>" class="button secondary"><?php esc_html_e( 'Export CSV', 'privaro-cookie-consent-banner' ); ?></a>
		</div>
		<?php
	}
}
