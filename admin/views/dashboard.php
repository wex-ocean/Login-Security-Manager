<?php
/**
 * Dashboard view for Login Security Manager.
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

// Get statistics.
$attempts_today = LSM_Logger::get_attempts_today();
$attempts_week = LSM_Logger::get_attempts_this_week();
$active_lockouts = LSM_Logger::get_active_lockouts_count();
$recent_attempts = LSM_Logger::get_recent_attempts( 10 );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Login Security Manager - Dashboard', 'login-security-manager' ); ?></h1>

	<div id="poststuff">
		<!-- Statistics Cards -->
		<div style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
			<!-- Today's Attempts Card -->
			<div style="flex: 1; min-width: 200px; background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 15px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
				<h3 style="margin: 0 0 10px 0; color: #23282d; font-size: 14px;"><?php esc_html_e( 'Failed Attempts Today', 'login-security-manager' ); ?></h3>
				<div style="font-size: 28px; font-weight: bold; color: #0073aa;">
					<?php echo (int) $attempts_today; ?>
				</div>
				<p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">
					<?php echo esc_html( sprintf( _n( '%d attempt', '%d attempts', $attempts_today, 'login-security-manager' ), $attempts_today ) ); ?>
				</p>
			</div>

			<!-- This Week's Attempts Card -->
			<div style="flex: 1; min-width: 200px; background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 15px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
				<h3 style="margin: 0 0 10px 0; color: #23282d; font-size: 14px;"><?php esc_html_e( 'Failed Attempts This Week', 'login-security-manager' ); ?></h3>
				<div style="font-size: 28px; font-weight: bold; color: #0073aa;">
					<?php echo (int) $attempts_week; ?>
				</div>
				<p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">
					<?php echo esc_html( sprintf( _n( '%d attempt', '%d attempts', $attempts_week, 'login-security-manager' ), $attempts_week ) ); ?>
				</p>
			</div>

			<!-- Active Lockouts Card -->
			<div style="flex: 1; min-width: 200px; background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 15px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
				<h3 style="margin: 0 0 10px 0; color: #23282d; font-size: 14px;"><?php esc_html_e( 'Active Lockouts', 'login-security-manager' ); ?></h3>
				<div style="font-size: 28px; font-weight: bold; color: <?php echo (int) $active_lockouts > 0 ? '#d32f2f' : '#28a745'; ?>;">
					<?php echo (int) $active_lockouts; ?>
				</div>
				<p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">
					<?php if ( $active_lockouts > 0 ) { ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lsm_blocked_ips' ) ); ?>" style="color: #0073aa;">
							<?php esc_html_e( 'View Blocked IPs', 'login-security-manager' ); ?>
						</a>
					<?php } else { ?>
						<?php esc_html_e( 'No active lockouts', 'login-security-manager' ); ?>
					<?php } ?>
				</p>
			</div>
		</div>

		<!-- Recent Activity Table -->
		<div style="background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 0; margin-top: 20px;">
			<div style="border-bottom: 1px solid #ddd; padding: 15px;">
				<h2 style="margin: 0; color: #23282d; font-size: 16px;">
					<?php esc_html_e( 'Recent Activity', 'login-security-manager' ); ?>
				</h2>
			</div>

			<?php if ( ! empty( $recent_attempts ) ) { ?>
				<table style="width: 100%; border-collapse: collapse;">
					<thead style="background: #f5f5f5; border-bottom: 1px solid #ddd;">
						<tr>
							<th style="padding: 10px 15px; text-align: left; font-weight: 600; color: #23282d; border-right: 1px solid #ddd;">
								<?php esc_html_e( 'IP Address', 'login-security-manager' ); ?>
							</th>
							<th style="padding: 10px 15px; text-align: left; font-weight: 600; color: #23282d; border-right: 1px solid #ddd;">
								<?php esc_html_e( 'Username', 'login-security-manager' ); ?>
							</th>
							<th style="padding: 10px 15px; text-align: left; font-weight: 600; color: #23282d; border-right: 1px solid #ddd;">
								<?php esc_html_e( 'Attempted At', 'login-security-manager' ); ?>
							</th>
							<th style="padding: 10px 15px; text-align: center; font-weight: 600; color: #23282d;">
								<?php esc_html_e( 'Status', 'login-security-manager' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_attempts as $attempt ) { ?>
							<tr style="border-bottom: 1px solid #eee;">
								<td style="padding: 10px 15px; border-right: 1px solid #eee;">
									<code style="background: #f5f5f5; padding: 2px 4px; border-radius: 3px;">
										<?php echo esc_html( $attempt->ip ); ?>
									</code>
								</td>
								<td style="padding: 10px 15px; border-right: 1px solid #eee;">
									<?php echo esc_html( ! empty( $attempt->username_tried ) ? $attempt->username_tried : '—' ); ?>
								</td>
								<td style="padding: 10px 15px; border-right: 1px solid #eee;">
									<?php echo esc_html( $attempt->attempted_at ); ?>
								</td>
								<td style="padding: 10px 15px; text-align: center;">
									<?php if ( $attempt->blocked ) { ?>
										<span style="background: #d32f2f; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
											<?php esc_html_e( 'Blocked', 'login-security-manager' ); ?>
										</span>
									<?php } else { ?>
										<span style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
											<?php esc_html_e( 'Failed', 'login-security-manager' ); ?>
										</span>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			<?php } else { ?>
				<div style="padding: 20px 15px; text-align: center; color: #666;">
					<p><?php esc_html_e( 'No login attempts recorded yet.', 'login-security-manager' ); ?></p>
				</div>
			<?php } ?>

			<div style="padding: 15px; text-align: center; background: #f9f9f9; border-top: 1px solid #ddd;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lsm_logs' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'View All Logs', 'login-security-manager' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>
