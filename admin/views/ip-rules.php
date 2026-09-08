<?php
/**
 * Manual IP whitelist and blacklist view.
 *
 * @package LoginSecurityManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'login-security-manager' ) );
}

$notice       = '';
$notice_class = 'notice-success';

if ( isset( $_POST['lsm_ip_rule_action'] ) ) {
	if ( ! isset( $_POST['lsm_ip_rules_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['lsm_ip_rules_nonce'] ), 'lsm_manage_ip_rules' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'login-security-manager' ) );
	}

	$type = isset( $_POST['lsm_ip_rule_type'] ) ? sanitize_key( wp_unslash( $_POST['lsm_ip_rule_type'] ) ) : '';
	$rule = isset( $_POST['lsm_ip_rule'] ) ? sanitize_text_field( wp_unslash( $_POST['lsm_ip_rule'] ) ) : '';

	if ( 'add' === $_POST['lsm_ip_rule_action'] ) {
		$success      = LSM_IP_List::add_rule( $type, $rule );
		$notice       = $success ? __( 'IP rule added.', 'login-security-manager' ) : __( 'Enter a valid, new IP address or CIDR range.', 'login-security-manager' );
		$notice_class = $success ? 'notice-success' : 'notice-error';
	} elseif ( 'remove' === $_POST['lsm_ip_rule_action'] ) {
		$success      = LSM_IP_List::remove_rule( $type, $rule );
		$notice       = $success ? __( 'IP rule removed.', 'login-security-manager' ) : __( 'That IP rule was not found.', 'login-security-manager' );
		$notice_class = $success ? 'notice-success' : 'notice-error';
	}
}

if ( $notice ) {
	printf( '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $notice_class ), esc_html( $notice ) );
}

$whitelist = LSM_IP_List::get_rules( 'whitelist' );
$blacklist = LSM_IP_List::get_rules( 'blacklist' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Login Security Manager - Manual IP Rules', 'login-security-manager' ); ?></h1>
	<p><?php esc_html_e( 'Whitelisted addresses bypass failed-login tracking and lockouts. Blacklisted addresses are blocked before authentication.', 'login-security-manager' ); ?></p>

	<form method="post">
		<?php wp_nonce_field( 'lsm_manage_ip_rules', 'lsm_ip_rules_nonce' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="lsm_ip_rule"><?php esc_html_e( 'IP address or CIDR range', 'login-security-manager' ); ?></label></th>
				<td><input name="lsm_ip_rule" id="lsm_ip_rule" type="text" class="regular-text" placeholder="192.168.1.0/24" required /></td>
			</tr>
			<tr>
				<th scope="row"><label for="lsm_ip_rule_type"><?php esc_html_e( 'Rule type', 'login-security-manager' ); ?></label></th>
				<td>
					<select name="lsm_ip_rule_type" id="lsm_ip_rule_type">
						<option value="whitelist"><?php esc_html_e( 'Whitelist', 'login-security-manager' ); ?></option>
						<option value="blacklist"><?php esc_html_e( 'Blacklist', 'login-security-manager' ); ?></option>
					</select>
					<?php submit_button( esc_html__( 'Add Rule', 'login-security-manager' ), 'primary', 'lsm_ip_rule_action', false, array( 'value' => 'add' ) ); ?>
				</td>
			</tr>
		</table>
	</form>

	<?php foreach ( array( 'whitelist' => $whitelist, 'blacklist' => $blacklist ) as $type => $rules ) { ?>
		<h2><?php echo esc_html( 'whitelist' === $type ? __( 'Whitelist', 'login-security-manager' ) : __( 'Blacklist', 'login-security-manager' ) ); ?></h2>
		<?php if ( empty( $rules ) ) { ?>
			<p><?php esc_html_e( 'No rules configured.', 'login-security-manager' ); ?></p>
		<?php } else { ?>
			<table class="widefat striped" style="max-width: 700px;">
				<thead><tr><th><?php esc_html_e( 'Address or range', 'login-security-manager' ); ?></th><th><?php esc_html_e( 'Action', 'login-security-manager' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $rules as $rule ) { ?>
						<tr>
							<td><code><?php echo esc_html( $rule ); ?></code></td>
							<td>
								<form method="post">
									<?php wp_nonce_field( 'lsm_manage_ip_rules', 'lsm_ip_rules_nonce' ); ?>
									<input type="hidden" name="lsm_ip_rule_action" value="remove" />
									<input type="hidden" name="lsm_ip_rule_type" value="<?php echo esc_attr( $type ); ?>" />
									<input type="hidden" name="lsm_ip_rule" value="<?php echo esc_attr( $rule ); ?>" />
									<?php submit_button( esc_html__( 'Remove', 'login-security-manager' ), 'secondary small', 'submit', false ); ?>
								</form>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } ?>
	<?php } ?>
</div>