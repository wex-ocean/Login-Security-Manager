<?php
/**
 * Logger for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LSM_Logger
 *
 * Provides query helpers for attempt and lockout statistics.
 */
class LSM_Logger {

	/**
	 * Get total failed attempts for today.
	 *
	 * @return int Number of attempts today.
	 */
	public static function get_attempts_today() {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_attempts';
		$today_start = gmdate( 'Y-m-d 00:00:00' );
		$today_end = gmdate( 'Y-m-d 23:59:59' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE attempted_at >= %s AND attempted_at <= %s",
				$today_start,
				$today_end
			)
		);

		return (int) $count;
	}

	/**
	 * Get total failed attempts for this week.
	 *
	 * @return int Number of attempts this week.
	 */
	public static function get_attempts_this_week() {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_attempts';
		$week_start = gmdate( 'Y-m-d 00:00:00', strtotime( 'last Monday' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE attempted_at >= %s",
				$week_start
			)
		);

		return (int) $count;
	}

	/**
	 * Get count of currently active lockouts.
	 *
	 * @return int Number of active lockouts.
	 */
	public static function get_active_lockouts_count() {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_lockouts';
		$now = gmdate( 'Y-m-d H:i:s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE expires_at > %s",
				$now
			)
		);

		return (int) $count;
	}

	/**
	 * Get recent attempt records.
	 *
	 * @param int $limit Number of records to retrieve.
	 * @return array Array of attempt records.
	 */
	public static function get_recent_attempts( $limit = 10 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_attempts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table ORDER BY attempted_at DESC LIMIT %d",
				$limit
			)
		);
	}

	/**
	 * Get paginated attempt records.
	 *
	 * @param int    $page_num The page number (1-indexed).
	 * @param int    $per_page Records per page.
	 * @param string $search_ip Optional IP to filter by.
	 * @param string $search_date Optional date to filter by (YYYY-MM-DD).
	 * @return array Array of attempt records.
	 */
	public static function get_paginated_attempts( $page_num = 1, $per_page = 20, $search_ip = '', $search_date = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_attempts';
		$offset = ( $page_num - 1 ) * $per_page;

		$query = "SELECT * FROM $table WHERE 1=1";
		$params = array();

		if ( ! empty( $search_ip ) ) {
			$search_ip = sanitize_text_field( $search_ip );
			$query .= ' AND ip = %s';
			$params[] = $search_ip;
		}

		if ( ! empty( $search_date ) ) {
			$search_date = sanitize_text_field( $search_date );
			$query .= ' AND DATE(attempted_at) = %s';
			$params[] = $search_date;
		}

		$query .= ' ORDER BY attempted_at DESC LIMIT %d OFFSET %d';
		$params[] = $per_page;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( $query, $params ) );
	}

	/**
	 * Get total count of attempts for pagination.
	 *
	 * @param string $search_ip Optional IP to filter by.
	 * @param string $search_date Optional date to filter by (YYYY-MM-DD).
	 * @return int Total attempt count.
	 */
	public static function get_total_attempts_count( $search_ip = '', $search_date = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_attempts';

		$query = "SELECT COUNT(*) FROM $table WHERE 1=1";
		$params = array();

		if ( ! empty( $search_ip ) ) {
			$search_ip = sanitize_text_field( $search_ip );
			$query .= ' AND ip = %s';
			$params[] = $search_ip;
		}

		if ( ! empty( $search_date ) ) {
			$search_date = sanitize_text_field( $search_date );
			$query .= ' AND DATE(attempted_at) = %s';
			$params[] = $search_date;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $params ) );
	}

	/**
	 * Get paginated lockout records.
	 *
	 * @param int    $page_num The page number (1-indexed).
	 * @param int    $per_page Records per page.
	 * @param string $search_ip Optional IP to filter by.
	 * @return array Array of lockout records.
	 */
	public static function get_paginated_lockouts( $page_num = 1, $per_page = 20, $search_ip = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_lockouts';
		$offset = ( $page_num - 1 ) * $per_page;
		$now = gmdate( 'Y-m-d H:i:s' );

		$query = "SELECT * FROM $table WHERE expires_at > %s";
		$params = array( $now );

		if ( ! empty( $search_ip ) ) {
			$search_ip = sanitize_text_field( $search_ip );
			$query .= ' AND ip = %s';
			$params[] = $search_ip;
		}

		$query .= ' ORDER BY locked_at DESC LIMIT %d OFFSET %d';
		$params[] = $per_page;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( $query, $params ) );
	}

	/**
	 * Get total count of active lockouts for pagination.
	 *
	 * @param string $search_ip Optional IP to filter by.
	 * @return int Total lockout count.
	 */
	public static function get_total_lockouts_count( $search_ip = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lsm_lockouts';
		$now = gmdate( 'Y-m-d H:i:s' );

		$query = "SELECT COUNT(*) FROM $table WHERE expires_at > %s";
		$params = array( $now );

		if ( ! empty( $search_ip ) ) {
			$search_ip = sanitize_text_field( $search_ip );
			$query .= ' AND ip = %s';
			$params[] = $search_ip;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $params ) );
	}
}
