<?php
/** 后台统一 shadcn 应用壳。所有后台入口通过 data-epay-view 交给 React 渲染。 */
@header('Content-Type: text/html; charset=UTF-8');
if(!isset($_SESSION['admin_csrf_token'])) $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
$admin_csrf_token = $_SESSION['admin_csrf_token'];
$epay_ui_view = isset($epay_ui_view) ? (string)$epay_ui_view : 'admin-dashboard';
$epay_ui_config = isset($epay_ui_config) && is_array($epay_ui_config) ? $epay_ui_config : [];
$epay_ui_config['csrf_token'] = $admin_csrf_token;
if(!isset($epay_ui_config['title'])) $epay_ui_config['title'] = isset($title) ? $title : '平台运营';
if(!isset($epay_ui_config['sitename'])) $epay_ui_config['sitename'] = $conf['sitename'] ?? 'Rainbow Pay';
$epay_ui_config['features'] = array_merge([
	'domain' => (int)($conf['pay_domain_forbid'] ?? 0) === 1 || (int)($conf['pay_domain_open'] ?? 0) === 1,
	'invitecode' => (int)($conf['reg_open'] ?? 0) === 2,
	'satf' => class_exists('\\lib\\AlipaySATF\\AlipaySATF') && file_exists(__DIR__.'/satf_transfer.php'),
	'applyments' => class_exists('\\lib\\Applyments\\CommUtil') && file_exists(__DIR__.'/applyments_channel.php') && file_exists(__DIR__.'/applyments_merchant.php'),
	'complain' => class_exists('\\lib\\Complain\\CommUtil') && file_exists(__DIR__.'/complain.php'),
	'mchrisk' => class_exists('\\lib\\WxMchRisk') && file_exists(__DIR__.'/mchrisk.php'),
], isset($epay_ui_config['features']) && is_array($epay_ui_config['features']) ? $epay_ui_config['features'] : []);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light dark" />
  <title><?php echo htmlspecialchars((string)$epay_ui_config['title'], ENT_QUOTES, 'UTF-8'); ?></title>
  <link href="../assets/dist/epay-ui.css" rel="stylesheet" />
  <script type="module" src="../assets/dist/epay-ui.js"></script>
</head>
<body>
  <div id="epay-react-root" data-epay-view="<?php echo htmlspecialchars($epay_ui_view, ENT_QUOTES, 'UTF-8'); ?>" data-epay-config="<?php echo htmlspecialchars(json_encode($epay_ui_config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"></div>
