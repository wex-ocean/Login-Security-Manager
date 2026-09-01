# Login Security Manager — Development Plan

## 1. Plugin Identity

- **Name:** Login Security Manager
- **Slug:** `login-security-manager`
- **Text domain:** `login-security-manager`
- **Min WP:** 6.0 | **Min PHP:** 7.4 (target 8.0+ compat)
- **License:** GPLv2+ (required for wp.org)
- **Prefix:** `lsm_` for functions/hooks, `LSM_` for classes, `lsm_` for DB tables/options (no generic names — avoid collisions)

## 2. File Structure

```
login-security-manager/
├── login-security-manager.php        # main bootstrap file, header, activation hooks
├── uninstall.php                     # cleanup on delete
├── readme.txt                        # wp.org listing (required format)
├── includes/
│   ├── class-lsm-core.php            # plugin init, hooks loader
│   ├── class-lsm-activator.php       # create tables, defaults on activation
│   ├── class-lsm-deactivator.php
│   ├── class-lsm-login-guard.php     # failed attempt tracking + lockout logic
│   ├── class-lsm-ip-utils.php        # IP detection, proxy/CDN handling
│   ├── class-lsm-lockout.php         # lockout state machine
│   ├── class-lsm-error-messages.php  # generic login error override
│   ├── class-lsm-enumeration-guard.php # block ?author=1, REST users, login hints
│   ├── class-lsm-session-manager.php # active session control (WP_Session_Tokens)
│   ├── class-lsm-ip-list.php         # whitelist/blacklist manager
│   ├── class-lsm-logger.php          # logging + cleanup/pruning
│   ├── class-lsm-settings.php        # settings registration + sanitization
│   └── class-lsm-admin.php           # admin menu, dashboard, assets
├── admin/
│   ├── views/
│   │   ├── dashboard.php
│   │   ├── settings.php
│   │   ├── blocked-ips.php
│   │   └── logs.php
│   ├── css/admin.css
│   └── js/admin.js
├── languages/                        # .pot file for i18n
└── assets/                           # wp.org banner/icon (not shipped in plugin zip)
```

## 3. Database Design

Two custom tables (don't abuse wp_options for high-volume data):

**`{prefix}lsm_attempts`** — every failed/blocked attempt
- id, ip, username_tried, user_agent, attempted_at, blocked (bool)

**`{prefix}lsm_lockouts`** — active + historical lockouts
- id, ip, reason, locked_at, expires_at, lifted_by (auto/manual)

Use `dbDelta()` in activator. Add indexes on `ip` and `attempted_at`. Auto-prune rows older than X days via WP-Cron (configurable retention, default 30 days) — this is non-negotiable, unbounded log tables are the #1 reason security plugins get bad reviews for slowing down sites.

Settings live in a single serialized option `lsm_settings` (one row, not 15 separate autoloaded options).

## 4. Core Features — Implementation Notes

**a. Failed login limiting**
- Hook: `wp_login_failed`
- Track per-IP AND per-username to prevent distributed brute force from bypassing IP-only limits
- Configurable: max attempts (default 5), time window (default 15 min)

**b. Temporary IP lockout**
- On threshold breach → insert into `lsm_lockouts`, block via `authenticate` filter (priority 30, before WP's own check) and `login_init`
- Configurable lockout duration (default 20 min), with escalating lockout option (double duration on repeat offenders) — nice-to-have, phase 2

**c. IP detection (critical — most plugins get this wrong)**
- Never trust `X-Forwarded-For` blindly (spoofable)
- Order: `REMOTE_ADDR` as source of truth, only trust proxy headers if admin explicitly enables "behind proxy/CDN" mode and can specify trusted proxy IPs (Cloudflare ranges preset option)
- Validate with `filter_var(..., FILTER_VALIDATE_IP)` always

**d. Hide detailed login errors**
- Filter `login_errors` → return generic "Invalid credentials" regardless of whether username or password was wrong

**e. Username enumeration prevention**
- Block `?author=N` rewrite → redirect or 403
- Filter REST `/wp-json/wp/v2/users` when unauthenticated
- Strip user info from login hints/oEmbed/sitemaps where applicable

**f. Active session control**
- Use core `WP_Session_Tokens` API — list sessions per user, allow admin to destroy individual/all sessions
- Optional: max concurrent sessions per user

**g. IP block/whitelist**
- Manual blacklist (permanent block) + whitelist (bypass all checks, e.g. office IP)
- Support CIDR ranges, not just single IPs

**h. Admin bypass**
- Whitelisted admin IP or a secret bypass mechanism (e.g. emergency disable via wp-config constant `LSM_DISABLE`) so admin never locks themselves out — this is mandatory, not optional

**i. Admin dashboard**
- Widget/page: recent attempts, currently locked IPs, quick stats (attempts today/week)
- Blocked IPs page: table with unblock action
- Logs page: filterable, paginated (use `WP_List_Table` — don't reinvent it)
- Settings page via Settings API, all inputs sanitized + nonced

## 5. Security & Coding Standards (non-negotiable for wp.org approval)

- All DB queries via `$wpdb->prepare()` — zero raw string concatenation
- All output escaped: `esc_html`, `esc_attr`, `esc_url` at point of output, not storage
- All forms: nonce + `current_user_can('manage_options')` capability checks
- Sanitize all settings input via `register_setting` sanitize callbacks
- No direct file access: `if ( ! defined( 'ABSPATH' ) ) exit;` in every PHP file
- Follow WPCS (WordPress Coding Standards) — run `phpcs` with `WordPress-Extra` ruleset before submission
- No eval, no remote code execution, no phoning home without explicit opt-in (wp.org review will reject/flag this)
- i18n: wrap all strings in `__()`/`_e()` with text domain, load via `load_plugin_textdomain`

## 6. Performance Considerations

- No queries on every page load for non-logged-in visitors beyond what's needed at `authenticate`/`login_init`
- Cron-based cleanup, not real-time deletes
- Avoid loading admin CSS/JS on non-plugin pages (`admin_enqueue_scripts` with page check)
- Cache lockout checks where sensible (object cache friendly, avoid pure transients abuse)

## 7. Build Phases

**Phase 1 — MVP (core security)**
Failed login limiting, IP lockout, generic error messages, basic settings page, activation/uninstall, DB tables.

**Phase 2 — Admin visibility**
Dashboard, logs table, blocked IPs page, manual block/whitelist UI.

**Phase 3 — Hardening**
Username enumeration blocking, session manager, admin bypass/emergency disable, CIDR support, proxy/CDN-aware IP detection.

**Phase 4 — Polish for wp.org**
readme.txt (proper wp.org format w/ FAQ, screenshots), .pot file, WPCS pass, uninstall cleanup, tested-up-to tag, banner/icon assets, changelog.

**Phase 5 — Submission**
Zip via SVN structure (`trunk`, `tags`, `assets`), submit for review, expect 1-3 rounds of review feedback (very common, don't panic).

## 8. Testing Checklist Before Submission

- Fresh install/activate/deactivate/uninstall cycle — no leftover tables/options
- Test with a caching plugin active (lockout logic must not be cached)
- Test behind Cloudflare (proxy header handling)
- Test lockout doesn't lock out admin permanently (bypass works)
- Multisite compatibility check (network activate behavior)
- PHP 8.0/8.1 compatibility (no deprecated function warnings)
- No PHP notices/warnings with `WP_DEBUG` on

## 9. Reality Check

This is a full CRUD + auth-hooking plugin, realistically 1,500–3,000 lines for a solid MVP. Budget for iterative builds per phase, not one giant generation — build and test Phase 1 fully before moving to Phase 2.
