<?php
/**
 * Login Guard for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LSM_Login_Guard
 *
 * Tracks failed login attempts and coordinates with lockout logic.
 */
class LSM_Login_Guard {

	/**
	 * Initialize the login guard hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_login_failed', array( __CLASS__, 'log_failed_attempt' ), 10, 1 );
	}

	/**
	 * Log a failed login attempt.
	 *
	 * Called via wp_login_failed hook. Logs attempt to database and checks if
	 * threshold is exceeded for lockout.
	 *
	 * @param string $username The username that failed authentication.
	 * @return void
	 */
	public static function log_failed_attempt( $username ) {
		$ip        = LSM_IP_Utils::get_visitor_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		// Insert attempt into database.
		self::insert_attempt( $ip, $username, $user_agent );

		// Check if lockout threshold is reached.
		if ( self::check_lockout_threshold( $ip ) ) {
			LSM_Lockout::create_lockout( $ip, 'failed_attempts' );
		}
	}

	/**
	 * Insert a failed attempt record into the database.
	 *
	 * @param string $ip The IP address.
	 * @param string $username The username attempted.
	 * @param string $user_agent The user agent string.
	 * @return int|false The ID of the inserted row, or false on failure.
	 */
	private static function insert_attempt( $ip, $username, $user_agent ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_attempts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->insert(
			$table,
			array(
				'ip'              => $ip,
				'username_tried'  => $username,
				'user_agent'      => $user_agent,
				'attempted_at'    => current_time( 'mysql' ),
				'blocked'         => 0,
			),
			array( '%s', '%s', '%s', '%s', '%d' )
		);
	}

	/**
	 * Check if the lockout threshold has been exceeded for an IP.
	 *
	 * Checks both IP-based and username-based attempt counts within the
	 * configured time window.
	 *
	 * @param string $ip The IP address to check.
	 * @return bool True if threshold exceeded, false otherwise.
	 */
	private static function check_lockout_threshold( $ip ) {
		global $wpdb;

		$settings = get_option( 'lsm_settings', array() );
		$max_attempts = isset( $settings['max_attempts'] ) ? (int) $settings['max_attempts'] : 5;
		$time_window = isset( $settings['time_window'] ) ? (int) $settings['time_window'] : 15;

		$table = $wpdb->prefix . 'lsm_attempts';
		$cutoff_time = gmdate( 'Y-m-d H:i:s', time() - ( $time_window * 60 ) );

		// Count attempts from this IP within the time window.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ip_attempts = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE ip = %s AND attempted_at > %s",
				$ip,
				$cutoff_time
			)
		);

		return (int) $ip_attempts >= $max_attempts;
	}

	/**
	 * Get recent failed attempts for a specific IP.
	 *
	 * @param string $ip The IP address.
	 * @param int    $limit Number of records to retrieve.
	 * @return array Array of attempt records.
	 */
	public static function get_recent_attempts( $ip, $limit = 10 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_attempts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE ip = %s ORDER BY attempted_at DESC LIMIT %d",
				$ip,
				$limit
			)
		);
	}

	/**
	 * Get attempt count for an IP within a time window.
	 *
	 * @param string $ip The IP address.
	 * @param int    $minutes The time window in minutes.
	 * @return int The number of attempts.
	 */
	public static function get_attempt_count( $ip, $minutes = 15 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_attempts';
		$cutoff_time = gmdate( 'Y-m-d H:i:s', time() - ( $minutes * 60 ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE ip = %s AND attempted_at > %s",
				$ip,
				$cutoff_time
			)
		);

		return (int) $count;
	}
}
