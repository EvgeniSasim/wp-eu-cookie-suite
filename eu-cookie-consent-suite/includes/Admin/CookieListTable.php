<?php
/**
 * Cookie list table class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use WPEU\CookieSuite\Consent\Categories;
use WPEU\CookieSuite\Scanner\CookieRepository;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * CookieListTable class.
 */
class CookieListTable extends \WP_List_Table {

	/**
	 * Repository instance.
	 *
	 * @var CookieRepository
	 */
	private CookieRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'cookie',
				'plural'   => 'cookies',
				'ajax'     => false,
			)
		);
		$this->repository = new CookieRepository();
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return array(
			'cb'          => '<input type="checkbox" />',
			'name'        => __( 'Name', 'eu-cookie-consent-suite' ),
			'domain'      => __( 'Domain', 'eu-cookie-consent-suite' ),
			'category'    => __( 'Category', 'eu-cookie-consent-suite' ),
			'service'     => __( 'Service', 'eu-cookie-consent-suite' ),
			'duration'    => __( 'Duration', 'eu-cookie-consent-suite' ),
			'detected_at' => __( 'Detected', 'eu-cookie-consent-suite' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'name'        => array( 'name', true ),
			'domain'      => array( 'domain', false ),
			'category'    => array( 'category', false ),
			'service'     => array( 'service', false ),
			'detected_at' => array( 'detected_at', false ),
		);
	}

	/**
	 * Default column content.
	 *
	 * @param array  $item        Item data.
	 * @param string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		switch ( $column_name ) {
			case 'name':
				$edit_url   = admin_url( 'admin.php?page=' . Admin::PAGE_SLUG . '&tab=cookies&action=edit&id=' . $item['id'] );
				$delete_url = wp_nonce_url( admin_url( 'admin.php?page=' . Admin::PAGE_SLUG . '&tab=cookies&action=delete&id=' . $item['id'] ), 'wpeu_cs_delete_cookie_' . $item['id'] );

				$actions = array(
					'edit'   => sprintf( '<a href="%s">%s</a>', $edit_url, __( 'Edit', 'eu-cookie-consent-suite' ) ),
					'delete' => sprintf( '<a href="%s" onclick="return confirm(\'%s\')">%s</a>', $delete_url, __( 'Are you sure?', 'eu-cookie-consent-suite' ), __( 'Delete', 'eu-cookie-consent-suite' ) ),
				);

				return sprintf( '<strong><a class="row-title" href="%1$s">%2$s</a></strong> %3$s', $edit_url, esc_html( $item['name'] ), $this->row_actions( $actions ) );

			case 'category':
				$categories = Categories::get_all();
				$label      = $categories[ $item['category'] ]['label'] ?? $item['category'];
				return sprintf( '<span class="wpeu-cs-tag wpeu-cs-tag-%s">%s</span>', esc_attr( $item['category'] ), esc_html( $label ) );

			case 'detected_at':
				return '0000-00-00 00:00:00' === $item['detected_at'] ? __( 'Manual', 'eu-cookie-consent-suite' ) : mysql2date( get_option( 'date_format' ), $item['detected_at'] );

			default:
				return esc_html( (string) ( $item[ $column_name ] ?? '' ) );
		}
	}

	/**
	 * Checkbox column.
	 *
	 * @param array $item Item data.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="bulk-delete[]" value="%d" />', $item['id'] );
	}

	/**
	 * Extra table navigation.
	 *
	 * @param string $which Which side.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$selected_cat = isset( $_GET['category'] ) ? sanitize_key( wp_unslash( (string) $_GET['category'] ) ) : '';
		$categories   = Categories::get_all();
		?>
		<div class="alignleft actions">
			<select name="category" id="filter-by-category">
				<option value=""><?php esc_html_e( 'All Categories', 'eu-cookie-consent-suite' ); ?></option>
				<?php foreach ( $categories as $id => $category ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $selected_cat, $id ); ?>>
						<?php echo esc_html( $category['label'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Filter', 'eu-cookie-consent-suite' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) ); ?>
		</div>
		<?php
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items(): void {
		$per_page = 20;
		$current_page = $this->get_pagenum();

		$args = array(
			'orderby'  => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( (string) $_GET['orderby'] ) ) : 'name',
			'order'    => isset( $_GET['order'] ) ? sanitize_key( wp_unslash( (string) $_GET['order'] ) ) : 'ASC',
			'category' => isset( $_GET['category'] ) ? sanitize_key( wp_unslash( (string) $_GET['category'] ) ) : '',
			'search'   => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['s'] ) ) : '',
		);

		$all_items = $this->repository->all( $args );
		$total_items = count( $all_items );

		$this->items = array_slice( $all_items, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}
