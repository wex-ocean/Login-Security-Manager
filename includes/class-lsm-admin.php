<?php
/**
 * Admin Handler for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LSM_Admin
 *
 * Handles admin menu registration and asset enqueuing.
 */
class LSM_Admin {

	/**
	 * Initialize admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register admin menu and pages.
	 *
	 * @return void
	 */
	public static function register_menu() {
		// Add top-level menu.
		add_menu_page(
			esc_html__( 'Login Security Manager', 'login-security-manager' ),
			esc_html__( 'Login Security', 'login-security-manager' ),
			'manage_options',
			'lsm_dashboard',
			array( __CLASS__, 'render_dashboard_page' ),
			'dashicons-shield',
			30
		);

		// Add Dashboard submenu (duplicates top-level for consistency).
		add_submenu_page(
			'lsm_dashboard',
			esc_html__( 'Dashboard', 'login-security-manager' ),
			esc_html__( 'Dashboard', 'login-security-manager' ),
			'manage_options',
			'lsm_dashboard',
			array( __CLASS__, 'render_dashboard_page' )
		);

		// Add Logs submenu.
		add_submenu_page(
			'lsm_dashboard',
			esc_html__( 'Login Attempt Logs', 'login-security-manager' ),
			esc_html__( 'Logs', 'login-security-manager' ),
			'manage_options',
			'lsm_logs',
			array( __CLASS__, 'render_logs_page' )
		);

		// Add Blocked IPs submenu.
		add_submenu_page(
			'lsm_dashboard',
			esc_html__( 'Blocked IP Addresses', 'login-security-manager' ),
			esc_html__( 'Blocked IPs', 'login-security-manager' ),
			'manage_options',
			'lsm_blocked_ips',
			array( __CLASS__, 'render_blocked_ips_page' )
		);

		// Add Settings submenu (moved under Login Security).
		add_submenu_page(
			'lsm_dashboard',
			esc_html__( 'Login Security Settings', 'login-security-manager' ),
			esc_html__( 'Settings', 'login-security-manager' ),
			'manage_options',
			'lsm_settings_page',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Render the dashboard page.
	 *
	 * @return void
	 */
	public static function render_dashboard_page() {
		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'login-security-manager' ) );
		}

		// Include the dashboard view.
		require_once LSM_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render the logs page.
	 *
	 * @return void
	 */
	public static function render_logs_page() {
		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'login-security-manager' ) );
		}

		// Include the logs view.
		require_once LSM_PLUGIN_DIR . 'admin/views/logs.php';
	}

	/**
	 * Render the blocked IPs page.
	 *
	 * @return void
	 */
	public static function render_blocked_ips_page() {
		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'login-security-manager' ) );
		}

		// Include the blocked IPs view.
		require_once LSM_PLUGIN_DIR . 'admin/views/blocked-ips.php';
	}

	/**
	 * Enqueue admin CSS and JavaScript.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		// Only load assets on Login Security Manager pages.
		$lsm_pages = array(
			'toplevel_page_lsm_dashboard',
			'login-security_page_lsm_logs',
			'login-security_page_lsm_blocked_ips',
			'login-security_page_lsm_settings_page',
		);

		if ( ! in_array( $hook_suffix, $lsm_pages, true ) ) {
			return;
		}

		// Enqueue admin CSS.
		wp_enqueue_style(
			'lsm_admin_css',
			LSM_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			LSM_PLUGIN_VERSION
		);

		// Enqueue admin JavaScript.
		wp_enqueue_script(
			'lsm_admin_js',
			LSM_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			LSM_PLUGIN_VERSION,
			true
		);
	}
}
