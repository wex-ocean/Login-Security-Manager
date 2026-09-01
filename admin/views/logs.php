<?php
/**
 * Logs view for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user permissions.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'login-security-manager' ) );
}

// Include the WP_List_Table class if not already included.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Define the attempts list table class.
class LSM_Attempts_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'attempt',
				'plural'   => 'attempts',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'ip'            => esc_html__( 'IP Address', 'login-security-manager' ),
			'username_tried' => esc_html__( 'Username Attempted', 'login-security-manager' ),
			'attempted_at'  => esc_html__( 'Timestamp', 'login-security-manager' ),
			'blocked'       => esc_html__( 'Status', 'login-security-manager' ),
		);
	}

	public function prepare_items() {
		$per_page = 20;
		$paged = isset( $_REQUEST['paged'] ) ? max( 1, (int) $_REQUEST['paged'] ) : 1;
		$search_ip = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$search_date = isset( $_REQUEST['filter_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_date'] ) ) : '';

		$this->items = LSM_Logger::get_paginated_attempts( $paged, $per_page, $search_ip, $search_date );
		$total_items = LSM_Logger::get_total_attempts_count( $search_ip, $search_date );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'ip':
				return '<code style="background: #f5f5f5; padding: 2px 4px; border-radius: 3px;">' . esc_html( $item->ip ) . '</code>';
			case 'username_tried':
				return esc_html( ! empty( $item->username_tried ) ? $item->username_tried : '—' );
			case 'attempted_at':
				return esc_html( $item->attempted_at );
			case 'blocked':
				if ( $item->blocked ) {
					return '<span style="background: #d32f2f; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">' . esc_html__( 'Blocked', 'login-security-manager' ) . '</span>';
				} else {
					return '<span style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 3px; font-size: 11px;">' . esc_html__( 'Failed', 'login-security-manager' ) . '</span>';
				}
			default:
				return print_r( $item, true );
		}
	}

	public function get_sortable_columns() {
		return array(
			'attempted_at' => array( 'attempted_at', true ),
		);
	}
}

$attempts_table = new LSM_Attempts_List_Table();
$attempts_table->prepare_items();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Login Security Manager - Logs', 'login-security-manager' ); ?></h1>

	<form method="get">
		<input type="hidden" name="page" value="lsm_logs" />
		<div style="display: flex; gap: 10px; margin: 15px 0; flex-wrap: wrap; align-items: center;">
			<input type="text" name="s" placeholder="<?php esc_attr_e( 'Filter by IP...', 'login-security-manager' ); ?>" value="<?php echo esc_attr( isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '' ); ?>" />
			<input type="date" name="filter_date" value="<?php echo esc_attr( isset( $_REQUEST['filter_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_date'] ) ) : '' ); ?>" />
			<?php submit_button( esc_html__( 'Filter', 'login-security-manager' ), 'secondary', 'filter_submit', false ); ?>
		</div>
		<?php $attempts_table->display(); ?>
	</form>
</div>
