<?php
/**
 * Lockout Handler for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LSM_Lockout
 *
 * Manages IP lockout state and authentication blocking.
 */
class LSM_Lockout {

	/**
	 * Initialize the lockout hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'authenticate', array( __CLASS__, 'block_locked_out_users' ), 30, 3 );
	}

	/**
	 * Block login attempts from locked-out IPs.
	 *
	 * Hooked into 'authenticate' at priority 30, before WordPress's own authentication
	 * checks. If the IP is locked out, return a generic error message.
	 *
	 * @param WP_User|WP_Error|null $user The user object or error.
	 * @param string                 $username The username.
	 * @param string                 $password The password.
	 * @return WP_User|WP_Error The user object, or an error if locked out.
	 */
	public static function block_locked_out_users( $user, $username, $password ) {
		$ip = LSM_IP_Utils::get_visitor_ip();

		if ( self::is_locked_out( $ip ) ) {
			return new WP_Error(
				'lsm_ip_locked_out',
				__( 'Too many failed login attempts. Please try again later.', 'login-security-manager' )
			);
		}

		return $user;
	}

	/**
	 * Check if an IP is currently locked out.
	 *
	 * @param string $ip The IP address to check.
	 * @return bool True if locked out, false otherwise.
	 */
	public static function is_locked_out( $ip ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_lockouts';
		$now = gmdate( 'Y-m-d H:i:s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$lockout = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE ip = %s AND expires_at > %s ORDER BY expires_at DESC LIMIT 1",
				$ip,
				$now
			)
		);

		return null !== $lockout;
	}

	/**
	 * Create a lockout for an IP address.
	 *
	 * @param string $ip The IP to lock out.
	 * @param string $reason The reason for lockout (e.g., 'failed_attempts').
	 * @return int|false The lockout record ID, or false on failure.
	 */
	public static function create_lockout( $ip, $reason = 'failed_attempts' ) {
		global $wpdb;

		// Check if already locked out.
		if ( self::is_locked_out( $ip ) ) {
			return false;
		}

		$settings = get_option( 'lsm_settings', array() );
		$lockout_duration = isset( $settings['lockout_duration'] ) ? (int) $settings['lockout_duration'] : 20;

		$table = $wpdb->prefix . 'lsm_lockouts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->insert(
			$table,
			array(
				'ip'         => $ip,
				'reason'     => $reason,
				'locked_at'  => current_time( 'mysql' ),
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() + ( $lockout_duration * 60 ) ),
				'lifted_by'  => 'auto',
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Manually lift a lockout for an IP.
	 *
	 * @param string $ip The IP to unlock.
	 * @param string $lifted_by The user/method that lifted the lockout.
	 * @return int|false The number of rows updated, or false on failure.
	 */
	public static function lift_lockout( $ip, $lifted_by = 'manual' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_lockouts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->update(
			$table,
			array(
				'expires_at' => current_time( 'mysql' ),
				'lifted_by'  => $lifted_by,
			),
			array( 'ip' => $ip ),
			array( '%s', '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Get active lockouts.
	 *
	 * @param int $limit Number of records to retrieve.
	 * @return array Array of lockout records.
	 */
	public static function get_active_lockouts( $limit = 20 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_lockouts';
		$now = gmdate( 'Y-m-d H:i:s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE expires_at > %s ORDER BY expires_at DESC LIMIT %d",
				$now,
				$limit
			)
		);
	}

	/**
	 * Get time remaining for a lockout in minutes.
	 *
	 * @param string $ip The IP address.
	 * @return int Minutes remaining, or 0 if not locked out.
	 */
	public static function get_lockout_remaining_minutes( $ip ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_lockouts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$lockout = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT expires_at FROM $table WHERE ip = %s AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1",
				$ip
			)
		);

		if ( ! $lockout ) {
			return 0;
		}

		$expires = strtotime( $lockout->expires_at );
		$now = time();
		$remaining = (int) ceil( ( $expires - $now ) / 60 );

		return max( 0, $remaining );
	}
}
