<?php
/**
 * Generic Login Error Messages for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LSM_Error_Messages
 *
 * Overrides default WordPress login error messages with generic messages.
 */
class LSM_Error_Messages {

	/**
	 * Initialize error message filtering.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'login_errors', array( __CLASS__, 'override_login_errors' ), 100 );
	}

	/**
	 * Override login error messages with a generic message.
	 *
	 * Replaces WordPress's default "invalid username" or "incorrect password" messages
	 * with a single generic message to prevent username enumeration attacks.
	 *
	 * @param string $errors The current login error message.
	 * @return string The generic error message.
	 */
	public static function override_login_errors( $errors ) {
		// Only override if there are actual errors to suppress.
		if ( empty( $errors ) ) {
			return $errors;
		}

		// Return generic error message regardless of the actual error.
		return '<div id="login_error">' . esc_html__( 'Invalid login credentials.', 'login-security-manager' ) . '</div>';
	}
}
