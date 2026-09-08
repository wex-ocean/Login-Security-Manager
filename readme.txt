=== Login Security Manager ===
Contributors: your-wordpress-org-username
Tags: login security, brute force protection, ip blacklist, ip whitelist, username enumeration
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protect WordPress login forms with failed-login limits, IP rules, lockouts, enumeration protection, and session controls.

== Description ==

Login Security Manager adds practical protection around WordPress authentication without replacing the standard login flow.

Features include:

* Failed-login tracking with configurable thresholds and time windows.
* Temporary IP lockouts after repeated failed attempts.
* Manual IP whitelist and blacklist rules for individual addresses and CIDR ranges.
* Whitelisted IPs bypass failed-attempt and lockout checks.
* Username enumeration protection for author queries, REST users requests, and lost-password hints.
* Active session viewing and revocation from user profile screens.
* Optional maximum concurrent sessions per user.
* Proxy-aware visitor IP detection with trusted proxy settings.
* An emergency `LSM_DISABLE` constant for temporarily disabling guard hooks.
* Generic login error messages that do not disclose whether a username exists.

Login Security Manager stores its configuration in the `lsm_settings` option and removes its custom tables and settings when uninstalled.

== Installation ==

1. Upload the `login-security-manager` folder to `/wp-content/plugins/`.
2. Activate Login Security Manager from the **Plugins** screen in WordPress.
3. Open **Login Security** in the WordPress admin menu.
4. Configure failed-login protection and proxy settings under **Settings**.
5. Add manual whitelist or blacklist entries under **IP Rules** when needed.
6. Review and revoke active sessions from a user's profile or the **Users** edit-user screen.

== Frequently Asked Questions ==

= Why was a legitimate visitor locked out? =

Review the lockout duration, maximum failed attempts, and time window under **Login Security > Settings**. Add a trusted office or administrator address under **IP Rules > Whitelist** to bypass failed-attempt tracking and lockouts. The existing temporary lockout can also be lifted from **Blocked IPs**.

= How do I configure the plugin behind a proxy or CDN? =

Enable **Behind Proxy/CDN** only when WordPress receives connections from a trusted proxy. Configure the proxy addresses in the plugin settings before relying on forwarded client IP headers. Do not enable this option when untrusted clients can connect directly to the site.

= How can I recover if the plugin blocks administrators? =

Add an administrator or office IP to the whitelist before testing restrictive rules. For an emergency, define `LSM_DISABLE` as `true` in `wp-config.php` before WordPress loads the plugin. This skips Login Security Manager guard and lockout hooks; remove the constant after correcting the settings.

= What does the concurrent session limit do? =

Set **Max Concurrent Sessions** under **Login Security > Settings**. A value of `0` means unlimited. When a user logs in above the configured limit, the oldest active session is revoked automatically.

= Does uninstalling remove plugin data? =

Yes. WordPress uninstall removes the `lsm_attempts` and `lsm_lockouts` tables using the site's database prefix and deletes the `lsm_settings` option. Deactivation alone does not remove data.

== Screenshots ==

1. Login Security Manager dashboard with failed-attempt and lockout statistics.
2. Manual IP Rules screen with whitelist and blacklist management.
3. Login Security Manager settings, including proxy and session controls.
4. Active Sessions section on a WordPress user profile.
5. Login attempt logs and blocked IP management.

== Changelog ==

= 1.0.0 =
* Added failed-login tracking and temporary IP lockouts.
* Added configurable proxy-aware IP detection and generic login errors.
* Added username enumeration protection for author queries, REST users, and lost-password hints.
* Added manual IP whitelist and blacklist rules with IPv4 and IPv6 CIDR support.
* Added active session viewing, revocation, and optional concurrent-session limits.
* Added an emergency `LSM_DISABLE` switch for guard hooks.
* Added admin dashboard, logs, blocked-IP management, and settings screens.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
