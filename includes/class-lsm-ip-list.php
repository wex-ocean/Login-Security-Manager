<?php
/**
 * Manual IP whitelist and blacklist management.
 *
 * @package LoginSecurityManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LSM_IP_List
 */
class LSM_IP_List {

	/**
	 * Initialize IP enforcement hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'authenticate', array( __CLASS__, 'block_blacklisted_ip' ), 1, 3 );
	}

	/**
	 * Block blacklisted IPs before normal authentication and threshold logic.
	 *
	 * @param WP_User|WP_Error|null $user User authentication result.
	 * @param string               $username Submitted username.
	 * @param string               $password Submitted password.
	 * @return WP_User|WP_Error
	 */
	public static function block_blacklisted_ip( $user, $username, $password ) {
		$ip = LSM_IP_Utils::get_visitor_ip();

		if ( self::is_blacklisted( $ip ) ) {
			return new WP_Error(
				'lsm_ip_blacklisted',
				__( 'This IP address is not permitted to log in.', 'login-security-manager' )
			);
		}

		return $user;
	}

	/**
	 * Determine whether an IP matches a manual rule list.
	 *
	 * @param string $ip   IP address to check.
	 * @param string $type Rule type: whitelist or blacklist.
	 * @return bool True when a rule matches.
	 */
	public static function matches( $ip, $type ) {
		if ( ! in_array( $type, array( 'whitelist', 'blacklist' ), true ) ) {
			return false;
		}

		$settings = get_option( 'lsm_settings', array() );
		$rules    = isset( $settings[ $type ] ) && is_array( $settings[ $type ] ) ? $settings[ $type ] : array();

		foreach ( $rules as $rule ) {
			if ( $ip === $rule || LSM_IP_Utils::ip_in_range( $ip, $rule ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether an IP is manually whitelisted or an admin bypass IP.
	 *
	 * @param string $ip IP address to check.
	 * @return bool True when checks should be bypassed.
	 */
	public static function is_whitelisted( $ip ) {
		$settings = get_option( 'lsm_settings', array() );

		return self::matches( $ip, 'whitelist' ) ||
			( ! empty( $settings['admin_bypass_ip'] ) && $ip === $settings['admin_bypass_ip'] );
	}

	/**
	 * Determine whether an IP is manually blacklisted.
	 *
	 * @param string $ip IP address to check.
	 * @return bool True when a blacklist rule matches.
	 */
	public static function is_blacklisted( $ip ) {
		return self::matches( $ip, 'blacklist' );
	}

	/**
	 * Get normalized rules of one type.
	 *
	 * @param string $type Rule type.
	 * @return array Valid rules.
	 */
	public static function get_rules( $type ) {
		if ( ! in_array( $type, array( 'whitelist', 'blacklist' ), true ) ) {
			return array();
		}

		$settings = get_option( 'lsm_settings', array() );
		return isset( $settings[ $type ] ) && is_array( $settings[ $type ] ) ? $settings[ $type ] : array();
	}

	/**
	 * Add a valid IP or CIDR rule.
	 *
	 * @param string $type Rule type.
	 * @param string $rule IP or CIDR range.
	 * @return bool True when added.
	 */
	public static function add_rule( $type, $rule ) {
		$rule = trim( sanitize_text_field( $rule ) );

		if ( ! in_array( $type, array( 'whitelist', 'blacklist' ), true ) || ! self::is_valid_rule( $rule ) ) {
			return false;
		}

		$rules = self::get_rules( $type );
		if ( in_array( $rule, $rules, true ) ) {
			return false;
		}

		$rules[] = $rule;
		return self::save_rules( $type, $rules );
	}

	/**
	 * Remove a rule.
	 *
	 * @param string $type Rule type.
	 * @param string $rule IP or CIDR range.
	 * @return bool True when the settings option changed.
	 */
	public static function remove_rule( $type, $rule ) {
		$rules = self::get_rules( $type );
		$index = array_search( $rule, $rules, true );

		if ( false === $index ) {
			return false;
		}

		unset( $rules[ $index ] );
		return self::save_rules( $type, array_values( $rules ) );
	}

	/**
	 * Validate an IP or CIDR rule.
	 *
	 * @param string $rule IP or CIDR range.
	 * @return bool True when valid.
	 */
	public static function is_valid_rule( $rule ) {
		return LSM_IP_Utils::is_valid_ip( $rule ) || LSM_IP_Utils::is_valid_cidr( $rule );
	}

	/**
	 * Save one rule list while preserving other settings.
	 *
	 * @param string $type  Rule type.
	 * @param array  $rules Rules to save.
	 * @return bool Whether the option was updated.
	 */
	private static function save_rules( $type, $rules ) {
		$settings          = get_option( 'lsm_settings', array() );
		$settings[ $type ] = $rules;

		return (bool) update_option( 'lsm_settings', $settings );
	}
}