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
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Login Security Manager - Settings', 'login-security-manager' ); ?></h1>
	<form method="post" action="options.php">
		<?php
		settings_fields( 'lsm_settings' );
		do_settings_sections( 'lsm_settings' );
		submit_button();
		?>
	</form>
</div>
