# PROJECT KNOWLEDGE BASE

**Generated:** 2026-04-30
**Commit:** 82e27a4
**Branch:** main

## OVERVIEW
彩虹易支付系统 (Rainbow ePayment) — PHP payment aggregation gateway. Multi-entry-point procedural/OOP hybrid, no framework. 65+ payment channel plugins, 11 frontend themes. Requires PHP >= 7.4, MySQL/MariaDB.

## STRUCTURE
```
Epay/
├── includes/               # Core application bootstrap & library
│   ├── common.php          # Bootstrap (autoloader, DB, cache, config, session)
│   ├── functions.php       # ~1550 lines of procedural helpers
│   ├── member.php          # Auth/session for admin & user panels
│   ├── autoloader.php      # Custom PSR-0 autoloader for \lib\ namespace
│   ├── lib/                # Core business classes (\lib namespace) → SEE includes/lib/AGENTS.md
│   ├── pages/              # Payment page fragments (QR codes, H5, error/success)
│   ├── vendor/             # Composer dependencies (alipay/wechat/qqpay SDKs + guomi crypto)
│   ├── qrcodedecoder/      # ZXing QR code decoder (bundled)
│   └── 360safe/            # 360 security SDK integration
├── plugins/                # 65 payment channel plugins → SEE plugins/AGENTS.md
├── admin/                  # Platform admin panel (55 PHP scripts, flat)
├── user/                   # Merchant self-service portal (36 PHP scripts)
├── paypage/                # Customer payment pages (QR code, numeric keypad UI)
├── template/               # 11 frontend themes (index1-index10, default)
├── assets/                 # Static assets (JS/CSS/images/icons, vendored libs)
├── install/                # Web installer + schema DDL (install.sql 578 lines)
├── docker/                 # Docker configs (entrypoint, nginx.conf, php.ini)
├── Dockerfile              # PHP 7.4-fpm-alpine image
├── docker-compose.yml      # Dev setup (local build, 4 services)
├── docker-compose.prod.yml # Prod setup (pre-built ghcr.io image)
├── pay.php                 # Payment plugin handler (URL: /pay/{s})
├── submit.php              # Payment API submit (merchant → create payment)
├── submit2.php             # Payment page step 2 (channel selection)
├── api.php                 # External merchant API (query/settle/refund)
├── mapi.php                # Payment API create (POST)
├── cron.php                # Cron tasks (settlement, reconciliation, cleanup)
├── gateway.php             # Voice/QR code gateway
├── cashier.php             # Cashier/checkout page
├── getshop.php             # Payment status query + captcha
├── gold.php                # WeChat Gold Plan iframe
├── wework.php              # 企业微信 (WeCom) callback
├── index.php               # Public frontend page
└── nginx.txt / IIS.txt     # Web server URL rewrite rules
```

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| Startup/bootstrap logic | `includes/common.php` | Defines constants, inits autoloader→DB→cache→config |
| Database access | `includes/lib/PdoHelper.php` | Custom PDO wrapper, sets charset utf8mb4, timezone +8:00 |
| Configuration (runtime) | DB table `pre_config` | Key-value stored in DB, loaded into `$conf` array via `Cache` |
| Configuration (file) | `config.php` | DB credentials only; gitignored, generated at deploy time |
| Payment processing | `includes/lib/Payment.php` + `includes/lib/Order.php` | Core payment/order lifecycle |
| Payment plugins | `plugins/{name}/{name}_plugin.php` | Each plugin is a directory with a class file |
| Plugin loading | `includes/lib/Plugin.php` | `loadForSubmit()` / `loadForPay()` / `loadForSettle()` |
| Template rendering | `includes/lib/Template.php` | Loads PHP files from `template/{theme}/`, no engine |
| Admin panel login | `admin/login.php` | TOTP 2FA support |
| Merchant API | `includes/lib/ApiHelper.php` + `includes/lib/api/` | Signature verification, query/settle/refund |
| Cache layer | `includes/lib/Cache.php` | DB-backed cache via `pre_cache` table (not file-based) |
| URL rewriting | `nginx.txt` (nginx), `IIS.txt` (IIS) | Maps `/pay/*`→pay.php, `/api/*`→api.php, `*.html`→index.php |
| Schema/setup | `install/install.sql` + `install/index.php` | Web-based installation wizard |
| Docker setup | `Dockerfile`, `docker-compose.yml`, `docker/` | 4-container stack, entrypoint auto-installs DB |
| Docker entrypoint | `docker/docker-entrypoint.sh` | Auto-install/upgrade, config.php generation, code sync from staging |

## CODE MAP
| Symbol | Type | Location | Role |
|--------|------|----------|------|
| `$conf` | global array | loaded in `common.php` | All runtime config from `pre_config` table |
| `$DB` | global object | `includes/lib/PdoHelper.php` | Database connection |
| `$CACHE` | global object | `includes/lib/Cache.php` | DB-backed key-value cache (pre_cache table) |
| `$siteurl` | global string | defined in `common.php` | Base site URL (auto-detected) |
| `VERSION` | constant | `common.php` | Application version (e.g. '3097') |
| `DB_VERSION` | constant | `common.php` | Database schema version (e.g. '2054') |
| `SYSTEM_ROOT` | constant | `common.php` | `includes/` directory path |
| `ROOT` | constant | `common.php` | Project root path |
| `\lib\Order` | class | `includes/lib/Order.php` | Order creation, query, status |
| `\lib\Payment` | class | `includes/lib/Payment.php` | Payment execution orchestration |
| `\lib\Plugin` | class | `includes/lib/Plugin.php` | Plugin discovery, loading, lifecycle |
| `\lib\Channel` | class | `includes/lib/Channel.php` | Payment channel type management |
| `\lib\Transfer` | class | `includes/lib/Transfer.php` | Merchant settlement/transfer |
| `\lib\Template` | class | `includes/lib/Template.php` | Theme/template loading |
| `\lib\ApiHelper` | class | `includes/lib/ApiHelper.php` | External API signature/response helpers |
| `\lib\MsgNotice` | class | `includes/lib/MsgNotice.php` | WeChat/email/SMS notifications |
| `\lib\RiskCheck` | class | `includes/lib/RiskCheck.php` | Risk control (IP, blacklist) |
| `\lib\TOTP` | class | `includes/lib/TOTP.php` | TOTP 2FA implementation |

## CONVENTIONS
- **PHP version**: >= 7.4 (enforced in entry points)
- **Timezone**: `Asia/Shanghai` (set in `common.php`)
- **Error reporting**: `E_ERROR | E_PARSE | E_COMPILE_ERROR` (notices/warnings suppressed)
- **Indentation**: Tabs (×1 per level)
- **Conditional spacing**: NO space between keyword and `(` → `if(...)`, `foreach(...)`
- **Brace style**: Opening brace on same line
- **Class naming**: PascalCase (`Autoloader`, `PdoHelper`, `ApiHelper`)
- **Function naming**: snake_case (`curl_get()`, `real_ip()`, `get_curl()`)
- **File naming**: snake_case `.php` (`common.php`, `functions.php`)
- **Namespaces**: Single-level `lib\ClassName` — all core classes under includes/lib/
- **Constants**: UPPER_SNAKE_CASE (`SYSTEM_ROOT`, `DB_VERSION`, `IN_CRONLITE`)
- **Database**: charset `utf8mb4`, timezone `+8:00`, table prefix `pre_` → `{$dbconfig['dbqz']}_`
- **Entry point pattern**: Every .php that handles a web request must `require` bootstrap from `includes/`
- **Guard constant**: `defined('IN_CRONLITE')` or early `return` prevents direct access to include files
- **No strict typing**: No `declare(strict_types=1)` anywhere
- **Template system**: Raw PHP includes with `<?=$var?>` short tags, no engine (no Twig/Blade)
- **Chinese comments**: All inline documentation is in Chinese
- **Frontend**: jQuery + Bootstrap 3/4, no build pipeline, assets served directly

## ANTI-PATTERNS (THIS PROJECT)
- **DON'T introduce a framework**: This is a flat-file PHP monolith. Do NOT attempt to migrate to Laravel/Symfony or add a front controller.
- **DON'T bypass `includes/common.php` bootstrap**: Every endpoint must go through the standard init chain (autoloader→DB→cache→config). Never duplicate session/DB init.
- **DON'T modify core for payment channels**: New gateways go in `/plugins/`, not hardcoded in `/includes/lib/`.
- **DON'T place vendor code in `includes/lib/`**: Third-party code goes in `includes/vendor/` (PHP) or `assets/vendor/` (JS). PHPMailer in `includes/lib/mail/` is legacy.
- **DON'T add TypeScript/build tooling**: Frontend is raw JS+jQuery. No webpack/vite without explicit approval.
- **DON'T edit vendor minified JS**: Upgrade by replacing entire file, never by patching minified code.
- **DON'T use English comments**: Codebase is Chinese-documented. Keep consistency.

## UNIQUE STYLES
- **DB-as-config**: All app settings (site name, template choice, API keys, feature flags) are in `pre_config` DB table loaded into `$conf`, NOT in config files. Admin panel is the only way to change settings.
- **Multi-entry-point**: No single front controller. 13 separate root-level .php endpoints, each directly addressable by URL. Routing is via web server rewrite rules, not application-level router.
- **Plugin discovery via directory convention**: Plugins in `plugins/{name}/` auto-detected. Each plugin: `{name}_plugin.php` class + optional `inc/config.php`.
- **Version-based DB migration**: Compare `DB_VERSION` constant vs `$conf['version']` in DB. Upgrade via `install/update.php` + `install/updateN.sql`. No migration framework.
- **Cron via HTTP**: `cron.php` is a web-facing endpoint triggered by server cron with a secret key, not a CLI script.
- **Security**: Custom WAF in `includes/txprotect.php`, 360 security SDK, Geetest CAPTCHA, RSA signing for API.
- **Docker staging sync**: Image stores code at `/var/www/html-staging/`. Entrypoint runs `rsync --delete` from staging to `app_data` volume (excluding `config.php`, `plugins/`, `install.lock`) on every container start. Pull new image + restart = code updated, config preserved.

## COMMANDS
```bash
# Docker (dev)
docker compose up -d              # Start 4-container stack
docker compose down               # Stop (keeps volumes)
docker compose logs -f php        # Watch PHP logs
docker compose exec php sh        # Shell into PHP container
docker compose up -d --build      # Rebuild + restart (code updates)

# Docker (prod)
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml pull  # Update pre-built image

# Manual deployment
# 1. Upload all files to PHP 7.4+ web server with MySQL 5.6+
# 2. Edit config.php with DB credentials
# 3. Visit /install/ in browser → 4-step wizard
# 4. Apply rewrite rules from nginx.txt (nginx) or IIS.txt (IIS)
# 5. Delete /install/ directory

# Cron (configure in system crontab)
*/5 * * * * php /path/to/cron.php?key=YOUR_CRON_KEY

# No build/test/lint commands exist. No CI/CD. No phpunit/phpstan/phpcs.
```

## NOTES
- `config.php` is gitignored — generated by Docker entrypoint or manually during setup. Template in repo was removed.
- No automated tests. Manual sandbox testing via `/user/test.php` when `test_open=1` in DB config.
- `includes/` and `plugins/` directories must be blocked from direct web access (`.htaccess` deny all or nginx `deny all`).
- Session started by default in `common.php`. Set `$nosession=true` before including to skip.
- Set `$is_defend=true` to enable WAF (`txprotect.php`) before including `common.php`.
- All frontend vendor libs (three.js, moment.js, flot, bootstrap, etc.) frozen at current versions. Upgrade by replacing entire directory.
- Docker: entrypoint auto-installs DB on first run, upgrades schema on version change. config.php regenerated if host is `localhost`.

## SECURITY HARDENING (2026-04-30 audit)

### Authentication & Sessions
- **Session regeneration**: `session_regenerate_id(true)` on ALL login paths (admin, user, OAuth, WeChat, QQ, Alipay)
- **Cookie flags**: All `setcookie("user_token")` and `setcookie("admin_token")` use httponly=true, secure=dynamic (is_https()), path='/'
- **CSRF tokens**: 
  - Admin panel: `$_SESSION['admin_csrf_token']` auto-injected via `$.ajaxSetup` in `admin/head.php`, validated in all 7 admin AJAX handlers + 8 direct-POST form pages
  - User panel: per-page `$_SESSION['csrf_token']` on login, register, password reset, settlement, transfers
  - Payment page: per-session CSRF token in `paypage/index.php`
- **TOTP 2FA**: Closing TOTP now requires admin password verification (was no-check)
- **Password hashing**: `hashPassword()`/`verifyPassword()` using bcrypt (cost=12) with transparent MD5→bcrypt upgrade on login. Old `getMd5Pwd()` retained for backward compatibility
- **Brute force**: Login retry counter messages removed (no leak of remaining attempts)
- **SSO auditing**: Admin user impersonation now logged to `pre_log` table

### API Security
- **Replay protection**: Timestamp validation (300s window) now enforced on ALL API endpoints (was gated behind `defined('API_INIT')` — now always checked)
- **PID scoping**: `api.php` SYS_KEY-based endpoints (`act=order` with sign, `act=refundapi`) now require PID in signature calculation to prevent cross-merchant access
- **Parameterized queries**: `api.php` all queries converted to `:named` parameterized form

### SQL Injection Prevention
- **Core API**: `api.php` fully parameterized (14 `:uid` bindings)
- **Admin AJAX**: `ajax_order.php`, `ajax_user.php`, `ajax_pay.php`, `ajax_settle.php` fully parameterized with column whitelists for dynamic column names
- **User panel**: `user/download.php` fully parameterized (40 `:kw` bindings)
- **Plugin callbacks**: `stripe_plugin.php`, `alipayrp_plugin.php` notify queries parameterized
- **daddslashes() removal**: Removed from SQL contexts; replaced with PDO prepared statements

### XSS Prevention
- **Centralized escaping**: `h()` function added to `includes/functions.php` — `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`
- **Template**: `$_SERVER['HTTP_HOST']` echoed with `htmlspecialchars()` in all template files
- **Admin forms**: `admin/ps_receiver.php`, `admin/gonggao.php`, `admin/pay_plugin.php` output escaped
- **User panel**: `user/index.php`, `user/groupbuy.php`, `user/domain.php` output escaped with `h()`
- **Payment page**: `paypage/error.php` `$msg` escaped with `h()`
- **Email**: Registration email `HTTP_HOST` escaped

### File & Upload Security
- **Logo upload**: `admin/set.php` now uses `move_uploaded_file()` + MIME type validation (was `copy()` with no validation)
- **Article upload**: `admin/ajax.php` now uses `move_uploaded_file()` (was `copy()`)
- **Dynamic includes**: `includes/lib/Payment.php` page name whitelist added; rejected pages throw error
- **Path traversal**: `plugins/hnapay/hnapay_plugin.php` file write now uses `basename()` sanitization

### Infrastructure
- **nginx**: Blocks `.git`, `.env`, `config.php`, `/install`; security headers added (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy)
- **IIS**: Blocks `.git`, `.env`, `config.php`, `/install`
- **.htaccess**: Apache 2.4 compatible (`Require all denied` added alongside `deny from all`)
- **Docker**: `disable_functions` now blocks exec/passthru/shell_exec/system/proc_open/popen; `allow_url_fopen=Off`; CRON_KEY uses `random_bytes(16)` (was `rand(100000,999999)`); DB_EXPOSE_PORT default disabled; credentials redacted from Docker logs
- **SQL errors**: `PdoHelper` connection failure now shows generic message; `display_errors=Off` enforced in `common.php`
- **Exception handling**: `paypage/inc.php` `showerror()` strips >200 char messages and stack traces

### Business Logic
- **Negative balance guard**: `changeUserMoney()` now checks `$money > $oldmoney` and rolls back (was NO check)
- **Cron dedup**: `do=transfer` now has daily dedup check (`transfer_time` setting) preventing double-settlement
- **Admin refund guard**: `admin/ajax_transfer.php` `refundTransfer` now checks transfer status (was unconditional)
- **Install RCE prevention**: `install/index.php` config.php generation now escapes user input with `addcslashes()`
- **Install auth**: `install/update.php` now requires `install.lock` + IP access control
- **SSRF protection**: `is_public_url()` function added to `includes/functions.php` — blocks private/internal IPs in `do_notify()` callback URLs
- **Open redirect**: `user/wxlogin.php` validates `redirect_url` with regex; `user/douyinoauth.php` validates state parameter
- **Order re-payment**: Status 4 (undocumented) removed from re-payment path in `Payment::processOrder()`

### Configuration & Secrets
- **Dead code removed**: `includes/authcode.php` (hardcoded constant `96973df55c788a72ac6ba29689531b08`, never referenced)
- **Default password**: `install/install.sql` default admin password changed from `123456` to `admin123456`
- **Docker defaults**: `.env.example` weak password placeholders replaced with guidance text
- **fubei plugin**: Hardcoded WeChat AppIDs moved to `plugins/fubei/inc/config.php`

### HTTPS Upgrades (12 endpoints)
- SendCloud mail API, AliCloud verification, 360 CDN, SMSBao, Geetest captcha (demo→production), admin URL default, 6 plugin author links

### Remaining Known Gaps (documented, not yet fixed)
- ~350 admin panel internal SQL queries still use interpolation (low risk: authenticated admin only)
- ~300 template file `$conf[...]` echoes not wrapped with `h()` (medium risk: admin-controlled DB values)
- `$conf['footer']` intentionally allows raw HTML (by design — admin formatting)
- Payment gateway HTTP endpoints (helipay, umfpay, haipay, fuiou2) — requires provider confirmation of HTTPS support
- `iot.solomo-info.com:9306` voice notification on custom port (no HTTPS available)
- `sms.php.gs` default SMS provider (no known HTTPS endpoint)
- Plugin `CURLOPT_SSL_VERIFYPEER=false` in 23 plugins — Chinese payment ecosystem convention, requires per-gateway testing
