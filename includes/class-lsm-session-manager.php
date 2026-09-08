<?php
/**
 * Active session management for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LSM_Session_Manager
 *
 * Uses WordPress session tokens to display and revoke active sessions.
 */
class LSM_Session_Manager {

	/**
	 * Initialize session management hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'show_user_profile', array( __CLASS__, 'render_sessions_section' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'render_sessions_section' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_session_action' ) );
		add_action( 'wp_login', array( __CLASS__, 'enforce_session_limit' ), 10, 2 );
	}

	/**
	 * Render the active sessions section on profile screens.
	 *
	 * @param WP_User $user User whose sessions are being displayed.
	 * @return void
	 */
	public static function render_sessions_section( $user ) {
		if ( ! self::can_manage_user_sessions( $user->ID ) ) {
			return;
		}

		$session_manager = WP_Session_Tokens::get_instance( $user->ID );
		$sessions        = self::get_active_sessions( $session_manager->get_all() );
		?>
		<h2><?php esc_html_e( 'Active Sessions', 'login-security-manager' ); ?></h2>
		<?php wp_nonce_field( 'lsm_destroy_session_' . $user->ID, 'lsm_session_nonce' ); ?>
		<input type="hidden" name="lsm_session_user_id" value="<?php echo esc_attr( $user->ID ); ?>" />
		<table class="widefat striped" style="max-width: 900px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Login time', 'login-security-manager' ); ?></th>
					<th><?php esc_html_e( 'IP address', 'login-security-manager' ); ?></th>
					<th><?php esc_html_e( 'Expires', 'login-security-manager' ); ?></th>
					<th><?php esc_html_e( 'Action', 'login-security-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $sessions ) ) { ?>
					<tr><td colspan="4"><?php esc_html_e( 'No active sessions found.', 'login-security-manager' ); ?></td></tr>
				<?php } else { ?>
					<?php foreach ( $sessions as $token => $session ) { ?>
						<tr>
							<td><?php echo esc_html( self::format_session_time( $session['login'] ) ); ?></td>
							<td><?php echo esc_html( ! empty( $session['ip'] ) ? $session['ip'] : __( 'Not available', 'login-security-manager' ) ); ?></td>
							<td><?php echo esc_html( self::format_session_time( $session['expiration'] ) ); ?></td>
							<td>
								<button type="submit" name="lsm_session_token" value="<?php echo esc_attr( $token ); ?>" class="button button-secondary button-small">
									<?php esc_html_e( 'Log out this session', 'login-security-manager' ); ?>
								</button>
							</td>
						</tr>
					<?php } ?>
				<?php } ?>
			</tbody>
		</table>
		<p><button type="submit" name="lsm_session_action" value="destroy_all" class="button button-secondary">
			<?php esc_html_e( 'Log out everywhere', 'login-security-manager' ); ?>
		</button></p>
		<?php
	}

	/**
	 * Process a session destruction request from a profile screen.
	 *
	 * @return void
	 */
	public static function handle_session_action() {
		if ( ! isset( $_POST['lsm_session_user_id'] ) || ( ! isset( $_POST['lsm_session_action'] ) && ! isset( $_POST['lsm_session_token'] ) ) ) {
			return;
		}

		$user_id = absint( $_POST['lsm_session_user_id'] );
		$action  = isset( $_POST['lsm_session_action'] ) ? sanitize_key( wp_unslash( $_POST['lsm_session_action'] ) ) : '';
		$token   = isset( $_POST['lsm_session_token'] ) ? sanitize_text_field( wp_unslash( $_POST['lsm_session_token'] ) ) : '';

		if ( ! $user_id || ( 'destroy_all' !== $action && empty( $token ) ) ) {
			return;
		}

		if ( ! isset( $_POST['lsm_session_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['lsm_session_nonce'] ), 'lsm_destroy_session_' . $user_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'login-security-manager' ) );
		}

		if ( ! self::can_manage_user_sessions( $user_id ) ) {
			wp_die( esc_html__( 'You do not have permission to manage these sessions.', 'login-security-manager' ) );
		}

		$session_manager = WP_Session_Tokens::get_instance( $user_id );
		if ( 'destroy_all' === $action ) {
			$session_manager->destroy_all();
		} elseif ( $token ) {
			$session_manager->destroy( $token );
		}

		$redirect_url = get_edit_user_link( $user_id );
		if ( ! $redirect_url ) {
			$redirect_url = admin_url( 'profile.php' );
		}

		wp_safe_redirect( add_query_arg( 'lsm_sessions_updated', '1', $redirect_url ) );
		exit;
	}

	/**
	 * Enforce the configured maximum after a successful login.
	 *
	 * @param string  $user_login Login username.
	 * @param WP_User $user       Logged-in user.
	 * @return void
	 */
	public static function enforce_session_limit( $user_login, $user ) {
		$settings    = get_option( 'lsm_settings', array() );
		$max_sessions = isset( $settings['max_sessions'] ) ? (int) $settings['max_sessions'] : 0;

		if ( $max_sessions < 1 ) {
			return;
		}

		$session_manager = WP_Session_Tokens::get_instance( $user->ID );
		$sessions        = self::get_active_sessions( $session_manager->get_all() );

		if ( count( $sessions ) <= $max_sessions ) {
			return;
		}

		uasort(
			$sessions,
			function ( $first, $second ) {
				return (int) $first['login'] <=> (int) $second['login'];
			}
		);

		while ( count( $sessions ) > $max_sessions ) {
			$oldest_token = key( $sessions );
			$session_manager->destroy( $oldest_token );
			unset( $sessions[ $oldest_token ] );
		}
	}

	/**
	 * Get active sessions and discard expired token records.
	 *
	 * @param array $sessions Session token data keyed by token hash.
	 * @return array Active sessions.
	 */
	private static function get_active_sessions( $sessions ) {
		$active_sessions = array();
		$now             = time();

		foreach ( $sessions as $token => $session ) {
			if ( empty( $session['expiration'] ) || (int) $session['expiration'] <= $now ) {
				continue;
			}

			$active_sessions[ $token ] = $session;
		}

		return $active_sessions;
	}

	/**
	 * Check whether the current administrator may manage a user's sessions.
	 *
	 * @param int $user_id User ID.
	 * @return bool True when permitted.
	 */
	private static function can_manage_user_sessions( $user_id ) {
		$current_user_id = get_current_user_id();

		if ( $current_user_id === (int) $user_id ) {
			return current_user_can( 'edit_user', $user_id );
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Format a session timestamp using the site's date and time settings.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string Formatted timestamp.
	 */
	private static function format_session_time( $timestamp ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $timestamp );
	}
}