<?php
/**
 * Fired during plugin deactivation.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class LSM_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Clears scheduled cron jobs and cleans up temporary data.
	 *
	 * @return void
	 */
	public static function deactivate() {
		self::clear_scheduled_crons();
	}

	/**
	 * Clear any scheduled WP-Cron jobs.
	 *
	 * @return void
	 */
	private static function clear_scheduled_crons() {
		wp_clear_scheduled_hook( 'lsm_log_cleanup' );
		wp_clear_scheduled_hook( 'lsm_lockout_expiration_check' );
	}
}
