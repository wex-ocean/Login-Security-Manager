<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom database tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lsm_attempts" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lsm_lockouts" );

// Delete plugin settings option.
delete_option( 'lsm_settings' );
