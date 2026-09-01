<?php
/**
 * Plugin Name: Login Security Manager
 * Plugin URI: https://example.com/login-security-manager
 * Description: Comprehensive login security suite with failed attempt tracking, IP lockouts, and brute force protection.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: login-security-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'LSM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LSM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LSM_PLUGIN_FILE', __FILE__ );
define( 'LSM_PLUGIN_VERSION', '1.0.0' );
define( 'LSM_PLUGIN_SLUG', 'login-security-manager' );
define( 'LSM_PLUGIN_TEXT_DOMAIN', 'login-security-manager' );

/**
 * The code that runs during plugin activation.
 *
 * @return void
 */
function lsm_activate_plugin() {
	require_once LSM_PLUGIN_DIR . 'includes/class-lsm-activator.php';
	LSM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @return void
 */
function lsm_deactivate_plugin() {
	require_once LSM_PLUGIN_DIR . 'includes/class-lsm-deactivator.php';
	LSM_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'lsm_activate_plugin' );
register_deactivation_hook( __FILE__, 'lsm_deactivate_plugin' );

/**
 * Begin execution of the plugin.
 *
 * @return void
 */
function lsm_run_plugin() {
	require_once LSM_PLUGIN_DIR . 'includes/class-lsm-core.php';
	$plugin = new LSM_Core();
	$plugin->run();
}

// Load plugin translation files.
add_action( 'plugins_loaded', 'lsm_load_textdomain' );

/**
 * Load plugin textdomain.
 *
 * @return void
 */
function lsm_load_textdomain() {
	load_plugin_textdomain(
		LSM_PLUGIN_TEXT_DOMAIN,
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);
}

lsm_run_plugin();
