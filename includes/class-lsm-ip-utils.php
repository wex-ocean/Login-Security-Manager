<?php
/**
 * IP Utilities for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LSM_IP_Utils
 *
 * Handles IP detection with proper proxy/CDN support.
 */
class LSM_IP_Utils {

	/**
	 * Get the real visitor IP address.
	 *
	 * Defaults to REMOTE_ADDR, only trusts proxy headers if admin enables "behind proxy"
	 * setting and specifies trusted proxy IPs. Always validates with filter_var().
	 *
	 * @return string The validated IP address, or '0.0.0.0' if unable to determine.
	 */
	public static function get_visitor_ip() {
		$ip = self::get_raw_visitor_ip();

		// Validate the IP address.
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return '0.0.0.0';
	}

	/**
	 * Get raw visitor IP before validation.
	 *
	 * @return string
	 */
	private static function get_raw_visitor_ip() {
		// REMOTE_ADDR is the most reliable source - direct connection or proxy pass-through.
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

			// If behind trusted proxy, check proxy headers.
			$settings = get_option( 'lsm_settings', array() );
			$behind_proxy = isset( $settings['behind_proxy'] ) ? (bool) $settings['behind_proxy'] : false;

			if ( $behind_proxy ) {
				$proxy_ip = self::get_proxy_forwarded_ip();
				if ( $proxy_ip && self::is_trusted_proxy( $ip ) ) {
					return $proxy_ip;
				}
			}

			return $ip;
		}

		return '0.0.0.0';
	}

	/**
	 * Get IP from proxy headers (X-Forwarded-For, CF-Connecting-IP, etc.).
	 *
	 * @return string|false The forwarded IP, or false if not found.
	 */
	private static function get_proxy_forwarded_ip() {
		// Check Cloudflare header first.
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		}

		// Check X-Forwarded-For (take first IP in chain).
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			if ( ! empty( $ips[0] ) ) {
				return trim( $ips[0] );
			}
		}

		// Check X-Forwarded header (some proxies).
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED'] ) );
		}

		// Check Client-IP header.
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		}

		return false;
	}

	/**
	 * Check if the given IP is a trusted proxy.
	 *
	 * @param string $ip The IP to check.
	 * @return bool True if trusted, false otherwise.
	 */
	private static function is_trusted_proxy( $ip ) {
		$settings = get_option( 'lsm_settings', array() );
		$trusted_ips = isset( $settings['trusted_proxy_ips'] ) ? (array) $settings['trusted_proxy_ips'] : array();

		// Add common known CDN IPs as defaults (Cloudflare example).
		$trusted_ips = array_merge(
			$trusted_ips,
			array(
				'127.0.0.1',
				'::1',
			)
		);

		return in_array( $ip, $trusted_ips, true );
	}

	/**
	 * Validate IP address format.
	 *
	 * @param string $ip The IP address to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_ip( $ip ) {
		return (bool) filter_var( $ip, FILTER_VALIDATE_IP );
	}

	/**
	 * Validate CIDR notation.
	 *
	 * @param string $cidr CIDR notation (e.g., "192.168.1.0/24").
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_cidr( $cidr ) {
		if ( ! is_string( $cidr ) || 1 !== substr_count( $cidr, '/' ) ) {
			return false;
		}

		list( $ip, $prefix ) = explode( '/', $cidr );

		if ( ! self::is_valid_ip( $ip ) || '' === $prefix || ! ctype_digit( $prefix ) ) {
			return false;
		}

		$prefix = (int) $prefix;
		if ( false === strpos( $ip, ':' ) ) {
			// IPv4.
			return $prefix >= 0 && $prefix <= 32;
		} else {
			// IPv6.
			return $prefix >= 0 && $prefix <= 128;
		}
	}

	/**
	 * Check if an IP is within a CIDR range.
	 *
	 * @param string $ip The IP to check.
	 * @param string $cidr The CIDR range (e.g., "192.168.1.0/24").
	 * @return bool True if IP is in range, false otherwise.
	 */
	public static function ip_in_range( $ip, $cidr ) {
		if ( ! self::is_valid_ip( $ip ) || ! self::is_valid_cidr( $cidr ) ) {
			return false;
		}

		list( $subnet, $bits ) = explode( '/', $cidr );
		$ip       = inet_pton( $ip );
		$subnet   = inet_pton( $subnet );
		if ( false === $ip || false === $subnet || strlen( $ip ) !== strlen( $subnet ) ) {
			return false;
		}

		$bytes    = strlen( $ip );
		$full_bytes = (int) floor( (int) $bits / 8 );
		$remaining  = (int) $bits % 8;
		$mask       = str_repeat( "\xff", $full_bytes );
		if ( $remaining > 0 ) {
			$mask .= chr( 0xff << ( 8 - $remaining ) );
		}
		$mask         = str_pad( $mask, $bytes, "\0" );
		$ipmasked     = $ip & $mask;
		$subnetmasked = $subnet & $mask;

		return $ipmasked === $subnetmasked;
	}
}
