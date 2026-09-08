<?php
/**
 * Settings Handler for Login Security Manager.
 *
 * @package LoginSecurityManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LSM_Settings
 *
 * Registers and sanitizes plugin settings via the WordPress Settings API.
 */
class LSM_Settings {

	/**
	 * Initialize settings registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Register plugin settings and sections.
	 *
	 * @return void
	 */
	public static function register_settings() {
		// Register the main settings option.
		register_setting(
			'lsm_settings_group',
			'lsm_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'show_in_rest'      => false,
			)
		);

		// Add settings section.
		add_settings_section(
			'lsm_main_section',
			esc_html__( 'Failed Login Protection', 'login-security-manager' ),
			array( __CLASS__, 'render_main_section' ),
			'lsm_settings_page'
		);

		// Add max attempts field.
		add_settings_field(
			'lsm_max_attempts',
			esc_html__( 'Max Failed Attempts', 'login-security-manager' ),
			array( __CLASS__, 'render_max_attempts_field' ),
			'lsm_settings_page',
			'lsm_main_section'
		);

		// Add time window field.
		add_settings_field(
			'lsm_time_window',
			esc_html__( 'Time Window (minutes)', 'login-security-manager' ),
			array( __CLASS__, 'render_time_window_field' ),
			'lsm_settings_page',
			'lsm_main_section'
		);

		// Add lockout duration field.
		add_settings_field(
			'lsm_lockout_duration',
			esc_html__( 'Lockout Duration (minutes)', 'login-security-manager' ),
			array( __CLASS__, 'render_lockout_duration_field' ),
			'lsm_settings_page',
			'lsm_main_section'
		);

		// Add proxy settings section.
		add_settings_section(
			'lsm_proxy_section',
			esc_html__( 'Proxy & CDN Settings', 'login-security-manager' ),
			array( __CLASS__, 'render_proxy_section' ),
			'lsm_settings_page'
		);

		// Add behind proxy toggle.
		add_settings_field(
			'lsm_behind_proxy',
			esc_html__( 'Behind Proxy/CDN', 'login-security-manager' ),
			array( __CLASS__, 'render_behind_proxy_field' ),
			'lsm_settings_page',
			'lsm_proxy_section'
		);

		// Add admin bypass IP field.
		add_settings_section(
			'lsm_admin_section',
			esc_html__( 'Admin Settings', 'login-security-manager' ),
			array( __CLASS__, 'render_admin_section' ),
			'lsm_settings_page'
		);

		// Add admin bypass IP field.
		add_settings_field(
			'lsm_admin_bypass_ip',
			esc_html__( 'Admin Bypass IP', 'login-security-manager' ),
			array( __CLASS__, 'render_admin_bypass_ip_field' ),
			'lsm_settings_page',
			'lsm_admin_section'
		);

		// Add session management section.
		add_settings_section(
			'lsm_session_section',
			esc_html__( 'Session Management', 'login-security-manager' ),
			array( __CLASS__, 'render_session_section' ),
			'lsm_settings_page'
		);

		// Add maximum concurrent sessions field.
		add_settings_field(
			'lsm_max_sessions',
			esc_html__( 'Max Concurrent Sessions', 'login-security-manager' ),
			array( __CLASS__, 'render_max_sessions_field' ),
			'lsm_settings_page',
			'lsm_session_section'
		);
	}

	/**
	 * Render main section description.
	 *
	 * @return void
	 */
	public static function render_main_section() {
		echo '<p>' . esc_html__( 'Configure failed login attempt limits and lockout duration.', 'login-security-manager' ) . '</p>';
	}

	/**
	 * Render proxy section description.
	 *
	 * @return void
	 */
	public static function render_proxy_section() {
		echo '<p>' . esc_html__( 'Enable if your site is behind a proxy or CDN like Cloudflare.', 'login-security-manager' ) . '</p>';
	}

	/**
	 * Render admin section description.
	 *
	 * @return void
	 */
	public static function render_admin_section() {
		echo '<p>' . esc_html__( 'Prevent admins from being locked out during attacks.', 'login-security-manager' ) . '</p>';
	}

	/**
	 * Render session section description.
	 *
	 * @return void
	 */
	public static function render_session_section() {
		echo '<p>' . esc_html__( 'Review active login sessions and optionally limit how many sessions each user may have.', 'login-security-manager' ) . '</p>';
	}

	/**
	 * Render max attempts field.
	 *
	 * @return void
	 */
	public static function render_max_attempts_field() {
		$settings = get_option( 'lsm_settings', array() );
		$value = isset( $settings['max_attempts'] ) ? (int) $settings['max_attempts'] : 5;
		?>
		<input type="number" name="lsm_settings[max_attempts]" value="<?php echo esc_attr( $value ); ?>" min="1" max="100" />
		<p class="description"><?php esc_html_e( 'Number of failed attempts before lockout. Default: 5', 'login-security-manager' ); ?></p>
		<?php
	}

	/**
	 * Render time window field.
	 *
	 * @return void
	 */
	public static function render_time_window_field() {
		$settings = get_option( 'lsm_settings', array() );
		$value = isset( $settings['time_window'] ) ? (int) $settings['time_window'] : 15;
		?>
		<input type="number" name="lsm_settings[time_window]" value="<?php echo esc_attr( $value ); ?>" min="1" max="1440" />
		<p class="description"><?php esc_html_e( 'Time window in minutes to count attempts. Default: 15', 'login-security-manager' ); ?></p>
		<?php
	}

	/**
	 * Render lockout duration field.
	 *
	 * @return void
	 */
	public static function render_lockout_duration_field() {
		$settings = get_option( 'lsm_settings', array() );
		$value = isset( $settings['lockout_duration'] ) ? (int) $settings['lockout_duration'] : 20;
		?>
		<input type="number" name="lsm_settings[lockout_duration]" value="<?php echo esc_attr( $value ); ?>" min="1" max="10080" />
		<p class="description"><?php esc_html_e( 'Duration in minutes to lock out an IP. Default: 20', 'login-security-manager' ); ?></p>
		<?php
	}

	/**
	 * Render behind proxy toggle field.
	 *
	 * @return void
	 */
	public static function render_behind_proxy_field() {
		$settings = get_option( 'lsm_settings', array() );
		$checked = isset( $settings['behind_proxy'] ) && $settings['behind_proxy'] ? 'checked' : '';
		?>
		<label>
			<input type="hidden" name="lsm_settings[behind_proxy]" value="0" />
			<input type="checkbox" name="lsm_settings[behind_proxy]" value="1" <?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput ?> />
			<?php esc_html_e( 'Enable if behind proxy/CDN (trusts X-Forwarded-For headers)', 'login-security-manager' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Only enable if you explicitly configure your proxy IP addresses.', 'login-security-manager' ); ?></p>
		<?php
	}

	/**
	 * Render admin bypass IP field.
	 *
	 * @return void
	 */
	public static function render_admin_bypass_ip_field() {
		$settings = get_option( 'lsm_settings', array() );
		$value = isset( $settings['admin_bypass_ip'] ) ? $settings['admin_bypass_ip'] : '';
		?>
		<input type="text" name="lsm_settings[admin_bypass_ip]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'e.g., 192.168.1.100', 'login-security-manager' ); ?>" style="width: 100%; max-width: 300px;" />
		<p class="description"><?php esc_html_e( 'IP address that bypasses all lockout checks (e.g., office IP). Leave empty to disable.', 'login-security-manager' ); ?></p>
		<?php
	}

	/**
	 * Render maximum concurrent sessions field.
	 *
	 * @return void
	 */
	public static function render_max_sessions_field() {
		$settings = get_option( 'lsm_settings', array() );
		$value    = isset( $settings['max_sessions'] ) ? (int) $settings['max_sessions'] : 0;
		?>
		<input type="number" name="lsm_settings[max_sessions]" value="<?php echo esc_attr( $value ); ?>" min="0" max="100" />
		<p class="description"><?php esc_html_e( 'Maximum active sessions per user. Use 0 for unlimited. Default: unlimited.', 'login-security-manager' ); ?></p>
		<?php
	}

	/**
	 * Sanitize and validate settings.
	 *
	 * @param array $input The raw settings input.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $input ) {
		$output = get_option( 'lsm_settings', array() );

		// Validate and sanitize max_attempts.
		if ( isset( $input['max_attempts'] ) ) {
			$max_attempts = (int) $input['max_attempts'];
			if ( $max_attempts >= 1 && $max_attempts <= 100 ) {
				$output['max_attempts'] = $max_attempts;
			}
		}

		// Validate and sanitize time_window.
		if ( isset( $input['time_window'] ) ) {
			$time_window = (int) $input['time_window'];
			if ( $time_window >= 1 && $time_window <= 1440 ) {
				$output['time_window'] = $time_window;
			}
		}

		// Validate and sanitize lockout_duration.
		if ( isset( $input['lockout_duration'] ) ) {
			$lockout_duration = (int) $input['lockout_duration'];
			if ( $lockout_duration >= 1 && $lockout_duration <= 10080 ) {
				$output['lockout_duration'] = $lockout_duration;
			}
		}

		// Sanitize behind_proxy as boolean.
		if ( isset( $input['behind_proxy'] ) ) {
			$output['behind_proxy'] = (bool) $input['behind_proxy'];
		}

		// Sanitize and validate admin_bypass_ip.
		if ( isset( $input['admin_bypass_ip'] ) && ! empty( $input['admin_bypass_ip'] ) ) {
			$admin_ip = sanitize_text_field( $input['admin_bypass_ip'] );
			if ( LSM_IP_Utils::is_valid_ip( $admin_ip ) ) {
				$output['admin_bypass_ip'] = $admin_ip;
			} else {
				$output['admin_bypass_ip'] = '';
				add_settings_error(
					'lsm_settings',
					'lsm_invalid_admin_ip',
					esc_html__( 'Admin bypass IP is invalid. Please enter a valid IP address.', 'login-security-manager' ),
					'error'
				);
			}
		} else {
			$output['admin_bypass_ip'] = '';
		}

		// Sanitize maximum concurrent sessions.
		if ( isset( $input['max_sessions'] ) ) {
			$max_sessions = (int) $input['max_sessions'];
			if ( $max_sessions >= 0 && $max_sessions <= 100 ) {
				$output['max_sessions'] = $max_sessions;
			}
		}

		return $output;
	}
}
