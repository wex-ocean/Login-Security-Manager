<?php
/**
 * The core plugin class.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class.
 *
 * Defines internationalization, admin-specific hooks, and public-facing site hooks.
 */
class LSM_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var LSM_Loader
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Loads and defines the internationalization files, and enqueues the admin and
	 * public-facing stylesheets and JavaScript.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->plugin_name = LSM_PLUGIN_SLUG;
		$this->version     = LSM_PLUGIN_VERSION;

		if ( defined( 'LSM_DISABLE' ) && LSM_DISABLE ) {
			return;
		}

		$this->load_dependencies();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Includes the following files:
	 * - IP utilities for visitor IP detection
	 * - Manual IP whitelist and blacklist rules
	 * - Active session management
	 * - Login guard for failed attempt tracking
	 * - Lockout handler for IP blocking
	 * - Username enumeration protection
	 * - Error messages for generic login errors
	 * - Settings registration and sanitization
	 * - Logger with query helpers for stats
	 * - Admin menu and pages
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once LSM_PLUGIN_DIR . 'includes/class-lsm-ip-utils.php';
		require_once LSM_PLUGIN_DIR . 'includes/class-lsm-ip-list.php';
		require_once LSM_PLUGIN_DIR . 'includes/class-lsm-session-manager.php';
		require_once LSM_PLUGIN_DIR . 'includes/class-lsm-enumeration-guard.php';
		require_once LSM_PLUGIN_DIR . 'includes/class-lsm-login-guard.php';
		require_once LSM_PLUGIN_DIR . 'includes/class-lsm-lockout.php';
		require_once LSM_PLUGIN_DIR . 'includes/class-lsm-error-messages.php';
		require_once LSM_PLUGIN_DIR . 'includes/class-lsm-settings.php';
		require_once LSM_PLUGIN_DIR . 'includes/class-lsm-logger.php';
		require_once LSM_PLUGIN_DIR . 'includes/class-lsm-admin.php';
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		if ( defined( 'LSM_DISABLE' ) && LSM_DISABLE ) {
			return;
		}

		// Initialize settings registration (must be before other inits).
		LSM_Settings::init();

		// Initialize generic error messages.
		LSM_Error_Messages::init();

		// Initialize manual IP rules before authentication and attempt tracking.
		LSM_IP_List::init();

		// Initialize active session management.
		LSM_Session_Manager::init();

		// Initialize username enumeration protection.
		LSM_Enumeration_Guard::init();

		// Initialize login guard (tracks failed attempts).
		LSM_Login_Guard::init();

		// Initialize lockout handler (blocks locked-out IPs).
		LSM_Lockout::init();

		// Initialize admin menu and pages.
		LSM_Admin::init();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the version of the plugin.
	 *
	 * @return string The version of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
