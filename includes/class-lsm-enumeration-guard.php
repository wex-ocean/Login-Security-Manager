<?php
/**
 * Username enumeration protection for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LSM_Enumeration_Guard
 */
class LSM_Enumeration_Guard {

	/**
	 * Initialize enumeration protection hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'block_author_query' ), 1 );
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'block_rest_users' ), 10, 3 );
		add_filter( 'login_errors', array( __CLASS__, 'strip_lostpassword_hints' ), 110 );
	}

	/**
	 * Redirect author queries to the site home.
	 *
	 * @return void
	 */
	public static function block_author_query() {
		if ( is_admin() || ! isset( $_GET['author'] ) ) {
			return;
		}

		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}

	/**
	 * Block the REST users endpoint for unauthenticated visitors.
	 *
	 * @param mixed           $result  Existing REST result.
	 * @param WP_REST_Server  $server  REST server instance.
	 * @param WP_REST_Request $request Current REST request.
	 * @return mixed Existing result or a REST error.
	 */
	public static function block_rest_users( $result, $server, $request ) {
		$route = $request->get_route();

		if ( ! is_user_logged_in() && preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {
			return new WP_Error(
				'lsm_rest_users_forbidden',
				__( 'User enumeration is not available.', 'login-security-manager' ),
				array( 'status' => 401 )
			);
		}

		return $result;
	}

	/**
	 * Remove account-existence hints from lost password errors.
	 *
	 * @param string $errors Current login errors.
	 * @return string Generic error output for lost password requests.
	 */
	public static function strip_lostpassword_hints( $errors ) {
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

		if ( 'lostpassword' !== $action || empty( $errors ) ) {
			return $errors;
		}

		return '<div id="login_error">' . esc_html__( 'If the account exists, you will receive an email with instructions.', 'login-security-manager' ) . '</div>';
	}
}