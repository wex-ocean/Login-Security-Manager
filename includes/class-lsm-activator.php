<?php
/**
 * Fired during plugin activation.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class LSM_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Creates necessary database tables and sets default options.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_database_tables();
		self::set_default_settings();
	}

	/**
	 * Create custom database tables.
	 *
	 * @return void
	 */
	private static function create_database_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// Create lsm_attempts table.
		$attempts_table = $wpdb->prefix . 'lsm_attempts';
		$attempts_sql   = "CREATE TABLE IF NOT EXISTS $attempts_table (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			ip VARCHAR(45) NOT NULL,
			username_tried VARCHAR(255),
			user_agent TEXT,
			attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			blocked TINYINT(1) DEFAULT 0,
			PRIMARY KEY (id),
			KEY ip (ip),
			KEY attempted_at (attempted_at)
		) $charset_collate;";

		dbDelta( $attempts_sql );

		// Create lsm_lockouts table.
		$lockouts_table = $wpdb->prefix . 'lsm_lockouts';
		$lockouts_sql   = "CREATE TABLE IF NOT EXISTS $lockouts_table (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			ip VARCHAR(45) NOT NULL,
			reason VARCHAR(255),
			locked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			expires_at DATETIME NOT NULL,
			lifted_by VARCHAR(50),
			PRIMARY KEY (id),
			KEY ip (ip),
			KEY expires_at (expires_at)
		) $charset_collate;";

		dbDelta( $lockouts_sql );
	}

	/**
	 * Set default plugin settings.
	 *
	 * @return void
	 */
	private static function set_default_settings() {
		$existing_settings = get_option( 'lsm_settings' );

		if ( false === $existing_settings ) {
			$default_settings = array(
				'max_attempts'       => 5,
				'time_window'        => 15, // minutes
				'lockout_duration'   => 20, // minutes
				'enable_logging'     => true,
				'log_retention'      => 30, // days
				'behind_proxy'       => false,
				'trusted_proxy_ips'  => array(),
				'enable_enumeration_guard' => true,
				'max_sessions'       => 0, // 0 = unlimited
			);

			update_option( 'lsm_settings', $default_settings );
		}
	}
}
