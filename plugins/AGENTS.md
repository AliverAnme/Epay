# plugins/ — Payment Channel Plugins

## OVERVIEW
65 payment channel plugins. Each plugin is a self-contained directory with a class file following strict naming conventions. Plugins are dynamically discovered by `\lib\Plugin::loadForSubmit()` and `\lib\Plugin::loadForPay()`. No registry or DB — directory presence is activation.

## STRUCTURE
```
plugins/
├── {plugin_name}/                    # Plugin directory (e.g. alipay, wxpay, stripe, douyinpay)
│   ├── {plugin_name}_plugin.php      # REQUIRED: Plugin class (e.g. alipay_plugin.php)
│   └── inc/
│       └── config.php               # OPTIONAL: Channel-specific config
├── alipay/          # 支付宝 (native)
├── alipaycode/      # 支付宝当面付
├── alipayd/         # 支付宝当面付 (daifu)
├── alipayg/         # 支付宝国际
├── alipayhk/        # 支付宝香港
├── wxpay/           # 微信支付 (official)
├── wxpayn/          # 微信支付 (non-official, NPD)
├── wxpaysl/         # 微信支付 (扫呗)
├── wxpayng/         # 微信支付 (non-official, NG)
├── wxpaynp/         # 微信支付 (non-official, NP)
├── qqpay/           # QQ钱包
├── unionpay/        # 银联支付
├── stripe/          # Stripe (international)
├── paypal/          # PayPal
├── douyinpay/       # 抖音支付
├── jdpay/           # 京东支付
├── bepusdt/         # USDT (TRC20)
├── xorpay/          # XOR支付
├── epay/ / epayn/   # 彩虹易支付（中转代理）
├── jeepay/          # Jeepay聚合支付
└── ...              # 65 total
```

## PLUGIN CONVENTION
Each plugin must follow this pattern:

**File**: `plugins/{name}/{name}_plugin.php`
**Class**: `{name}_plugin` (lowercase, underscore suffix)
**Optional config**: `plugins/{name}/inc/config.php`

**Plugin lifecycle hooks** (methods the class should implement):
- `submit()` — Called by `loadForSubmit`: returns payment page content
- `pay()` — Called by `loadForPay`: handles payment page display
- `callback()` — Called on payment gateway callback (async notify)
- `return()` — Called on payment gateway return (sync redirect)
- `settle()` — Called for settlement/transfer processing
- `cron()` — Called by `cron.php` for scheduled tasks

**Plugin class is loaded via**:
```php
\lib\Plugin::loadForSubmit($channel_type)  // step 1: submit order
\lib\Plugin::loadForPay($plugin_id)        // step 2: display payment page
\lib\Plugin::loadForSettle($channel_type)  // settlement
```

## WHERE TO LOOK
| Task | Location |
|------|----------|
| Plugin loader logic | `includes/lib/Plugin.php` |
| Channel type definitions | `includes/lib/Channel.php` |
| Plugin config in DB | `pre_channel` table |
| Payment page fragments | `includes/pages/` |
| Example plugin structure | `plugins/alipay/` or `plugins/epay/` |

## CONVENTIONS
- **Directory name = plugin name**: `plugins/alipay/` → class `alipay_plugin`
- **One class per plugin**: All logic in `{name}_plugin.php`, no subclasses outside this file
- **Config in `inc/config.php`**: Channel-specific settings (app ID, secret, gateway URL) — returns PHP array
- **Payment return/callback**: Two URLs — sync return (`return_url`) and async notify (`notify_url`)
- **Error handling**: Use `\lib\Payment` helper methods, throw `\Exception` on failure
- **Logging**: Write to DB via `$DB`, notify via `\lib\MsgNotice`
- **No Composer autoloading for plugins**: Plugin classes are `require`d directly by `Plugin.php`

## ANTI-PATTERNS
- **DON'T modify `Plugin.php` for a single channel**: Plugin loader is generic. Channel-specific logic goes in the plugin class.
- **DON'T create subclasses outside `{name}_plugin.php`**: Keep each plugin self-contained in one file.
- **DON'T hardcode plugin paths**: Always use `PLUGIN_ROOT` constant.
- **DON'T commit API keys/secrets**: Use DB-stored config values, not hardcoded in plugin files.
- **DON'T mix plugin and core code**: Plugin files should only be loaded via `Plugin.php`, never `include`d directly by core.
