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
	 * Register admin menu and settings page.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_options_page(
			esc_html__( 'Login Security Manager', 'login-security-manager' ),
			esc_html__( 'Login Security', 'login-security-manager' ),
			'manage_options',
			'lsm_settings_page',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'login-security-manager' ) );
		}

		// Include the settings view.
		require_once LSM_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Enqueue admin CSS and JavaScript.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		// Only load assets on the Login Security Manager settings page.
		if ( 'settings_page_lsm_settings_page' !== $hook_suffix ) {
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
