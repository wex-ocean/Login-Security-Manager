<?php
/**
 * Settings view for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user permissions.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'login-security-manager' ) );
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Login Security Manager - Settings', 'login-security-manager' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		// Security nonce and settings group.
		settings_fields( 'lsm_settings_group' );
		
		// Output settings sections and fields.
		do_settings_sections( 'lsm_settings_page' );
		
		// Submit button.
		submit_button();
		?>
	</form>

	<hr style="margin: 30px 0; border: 0; border-top: 1px solid #ddd;" />

	<h2><?php esc_html_e( 'Information', 'login-security-manager' ); ?></h2>
	<div style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa; margin-top: 10px;">
		<p>
			<strong><?php esc_html_e( 'Max Failed Attempts:', 'login-security-manager' ); ?></strong>
			<?php esc_html_e( 'How many failed login attempts trigger a lockout.', 'login-security-manager' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Time Window:', 'login-security-manager' ); ?></strong>
			<?php esc_html_e( 'The period (in minutes) over which failed attempts are counted.', 'login-security-manager' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Lockout Duration:', 'login-security-manager' ); ?></strong>
			<?php esc_html_e( 'How long an IP remains locked out after exceeding the attempt limit.', 'login-security-manager' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Behind Proxy/CDN:', 'login-security-manager' ); ?></strong>
			<?php esc_html_e( 'Enable only if you use a proxy or CDN and have configured trusted proxy IPs.', 'login-security-manager' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Admin Bypass IP:', 'login-security-manager' ); ?></strong>
			<?php esc_html_e( 'Specify your office/admin IP to prevent account lockout during attacks.', 'login-security-manager' ); ?>
		</p>
	</div>
</div>
