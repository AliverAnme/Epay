# includes/lib/ — Core Business Library

## OVERVIEW
All core business logic classes under the `\lib\` namespace. Autoloaded by custom PSR-0 autoloader in `includes/autoloader.php` (NOT Composer). Classes map: `\lib\ClassName` → `includes/lib/ClassName.php`.

## STRUCTURE
```
includes/lib/
├── PdoHelper.php          # Database (PDO wrapper, utf8mb4, +8:00 timezone)
├── Cache.php              # File-based key-value cache (files in includes/cache/)
├── Order.php              # Order lifecycle: create, query, status, settlement
├── Payment.php            # Payment orchestration: submit, verify, execute
├── Plugin.php             # Plugin loader: discovery, loadForSubmit, loadForPay, loadForSettle
├── Channel.php            # Payment channel types (alipay, wxpay, etc.)
├── Transfer.php           # Merchant settlement/transfer processing
├── Template.php           # Theme loader (loads PHP templates from template/{theme}/)
├── ApiHelper.php          # External API helpers: signature verify, response format
├── MsgNotice.php          # Notifications: WeChat template msg, email, SMS
├── RiskCheck.php          # Risk control: IP check, blacklist, region blocking
├── TOTP.php               # TOTP 2FA (RFC 6238)
├── GeetestLib.php         # Geetest CAPTCHA client
├── Ip2Region.php          # IP-to-location lookup
├── AliyunCertify.php      # Aliyun real-name verification
├── AntiDigitalCertify.php # Digital certificate anti-fraud
├── QcloudFaceid.php       # Tencent Cloud face ID verification
├── QC.php                 # QuickConnect/QCloud helper
├── Oauth.php              # OAuth integration
├── Printer.php            # Thermal printer support
├── VerifyCode.php         # Image CAPTCHA generator
├── XdbSearcher.php        # IP location database searcher
├── api/                   # External merchant API controllers (query, settle, refund)
├── ProfitSharing/         # Profit sharing logic
├── wechat/                # WeChat SDK wrappers (wxpay, mini-program)
├── mail/                  # PHPMailer (legacy, should be in vendor/)
├── sms/                   # SMS sending via Aliyun
├── ocr/                   # OCR integration
├── bank.json              # Bank code→name mapping data
└── hieroglyphy.php        # Obscure JS encoding utility
```

## WHERE TO LOOK
| Task | File | Notes |
|------|------|-------|
| Database queries | `PdoHelper.php` | All DB access via this class. Methods: `exec()`, `query()`, `fetch()`, `fetchAll()` |
| Cache get/set | `Cache.php` | File-based. `$CACHE->get('key')`, `$CACHE->save('key', $data)` |
| Create order | `Order.php` | `Order::create($data)` → inserts into `pre_order` |
| Execute payment | `Payment.php` | Handles submit flow, delegates to plugins |
| Load payment plugin | `Plugin.php` | `Plugin::loadForSubmit($channel)`, `Plugin::loadForPay($s)` |
| External API auth | `ApiHelper.php` | Sign/verify merchant API requests (MD5 + RSA) |
| Send notification | `MsgNotice.php` | WeChat template messages, email, SMS |
| Risk screening | `RiskCheck.php` | IP range check, buyer blacklist, region block |

## CONVENTIONS
- **Namespace**: All classes in `namespace lib;` — no sub-namespaces (flat)
- **Class file naming**: `PdoHelper.php` contains `class PdoHelper` — PascalCase, 1:1 mapping
- **No constructor injection**: Dependencies accessed via globals (`$DB`, `$CACHE`, `$conf`) not DI
- **Static methods common**: `Order::create()`, `Plugin::loadSubmit()`, `Template::load()` — procedural-style usage
- **Exception handling**: Uses `try/catch` with `\Exception`, custom error codes via `sysmsg()`
- **Procedural helpers**: `functions.php` (1552 lines) provides `get_curl()`, `real_ip()`, `sysmsg()`, `checkemail()`, etc.
- **Subdirectories for domain grouping**: `api/`, `ProfitSharing/`, `wechat/`, `mail/`, `sms/`, `ocr/`

## ANTI-PATTERNS
- **DON'T use Composer autoloading for `\lib\` classes**: The custom `Autoloader::register()` handles them. Composer is ONLY for `includes/vendor/`.
- **DON'T add DI/container**: The global variable pattern is entrenched. Adding a container creates inconsistency.
- **DON'T modify `PdoHelper`**: It's the single DB access point used by everything. Changes cascade.
- **DON'T move `PHPMailer` further into lib**: It's already mislocated in `includes/lib/mail/`. Future mail libs go in `includes/vendor/`.
