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
		$this->load_dependencies();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Includes the following files:
	 * - A simple loader class to handle conditional hook registration
	 *
	 * @return void
	 */
	private function load_dependencies() {
		// For now, we'll expand this to include individual feature classes in Phase 1+.
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		// Future: Initialize feature classes and register hooks.
		// For Phase 1 MVP, we'll add login guard, IP lockout, and error message override.
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
