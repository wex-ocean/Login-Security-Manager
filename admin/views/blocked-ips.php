<?php
/**
 * Blocked IPs view for Login Security Manager.
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

// Handle unblock action.
if ( isset( $_GET['action'] ) && 'unblock' === $_GET['action'] && isset( $_GET['ip'] ) ) {
	// Check nonce.
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'lsm_unblock_ip' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'login-security-manager' ) );
	}

	$ip_to_unblock = sanitize_text_field( wp_unslash( $_GET['ip'] ) );

	if ( LSM_IP_Utils::is_valid_ip( $ip_to_unblock ) ) {
		LSM_Lockout::lift_lockout( $ip_to_unblock, 'manual_admin' );
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( sprintf( __( 'IP %s has been unblocked.', 'login-security-manager' ), $ip_to_unblock ) ); ?></p>
		</div>
		<?php
	}
}

// Include the WP_List_Table class if not already included.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Define the lockouts list table class.
class LSM_Lockouts_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'lockout',
				'plural'   => 'lockouts',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'ip'          => esc_html__( 'IP Address', 'login-security-manager' ),
			'locked_at'   => esc_html__( 'Locked At', 'login-security-manager' ),
			'expires_at'  => esc_html__( 'Expires At', 'login-security-manager' ),
			'reason'      => esc_html__( 'Reason', 'login-security-manager' ),
			'actions'     => esc_html__( 'Actions', 'login-security-manager' ),
		);
	}

	public function prepare_items() {
		$per_page = 20;
		$paged = isset( $_REQUEST['paged'] ) ? max( 1, (int) $_REQUEST['paged'] ) : 1;
		$search_ip = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		$this->items = LSM_Logger::get_paginated_lockouts( $paged, $per_page, $search_ip );
		$total_items = LSM_Logger::get_total_lockouts_count( $search_ip );

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
				return '<code style="background: #f5f5f5; padding: 2px 4px; border-radius: 3px; font-weight: bold;">' . esc_html( $item->ip ) . '</code>';
			case 'locked_at':
				return esc_html( $item->locked_at );
			case 'expires_at':
				$remaining = LSM_Lockout::get_lockout_remaining_minutes( $item->ip );
				return esc_html( $item->expires_at ) . '<br><small style="color: #666;">(' . esc_html( sprintf( _n( '%d minute', '%d minutes', $remaining, 'login-security-manager' ), $remaining ) ) . ' ' . esc_html__( 'remaining', 'login-security-manager' ) . ')</small>';
			case 'reason':
				return esc_html( $item->reason );
			case 'actions':
				$unblock_url = wp_nonce_url(
					add_query_arg(
						array(
							'page'   => 'lsm_blocked_ips',
							'action' => 'unblock',
							'ip'     => $item->ip,
						),
						admin_url( 'admin.php' )
					),
					'lsm_unblock_ip'
				);
				return '<a href="' . esc_url( $unblock_url ) . '" class="button button-small" onclick="return confirm(\'' . esc_attr__( 'Are you sure?', 'login-security-manager' ) . '\');">' . esc_html__( 'Unblock', 'login-security-manager' ) . '</a>';
			default:
				return print_r( $item, true );
		}
	}

	public function get_sortable_columns() {
		return array(
			'locked_at'  => array( 'locked_at', true ),
			'expires_at' => array( 'expires_at', false ),
		);
	}
}

$lockouts_table = new LSM_Lockouts_List_Table();
$lockouts_table->prepare_items();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Login Security Manager - Blocked IPs', 'login-security-manager' ); ?></h1>

	<form method="get">
		<input type="hidden" name="page" value="lsm_blocked_ips" />
		<div style="margin: 15px 0;">
			<input type="text" name="s" placeholder="<?php esc_attr_e( 'Filter by IP...', 'login-security-manager' ); ?>" value="<?php echo esc_attr( isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '' ); ?>" />
			<?php submit_button( esc_html__( 'Filter', 'login-security-manager' ), 'secondary', 'filter_submit', false ); ?>
		</div>
		<?php $lockouts_table->display(); ?>
	</form>
</div>
