<?php
if(!defined('IN_CRONLITE'))exit();
$epay_status_config=[
	'status'=>'red-success',
	'sitename'=>$conf['sitename'],
	'amount'=>$trans['money'],
	'recipient'=>$trans['type']=='alipay'?'支付宝账户':'微信账户',
	'createdAt'=>$trans['addtime'],
	'paidAt'=>$trans['paytime'],
	'receiveAction'=>$receive_action,
	'receiveName'=>$receive_name,
];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>红包领取成功</title>
    <link rel="stylesheet" href="/assets/dist/epay-ui.css">
</head>
<body>
<div id="epay-react-root" data-epay-view="payment-status" data-epay-config="<?php echo h(json_encode($epay_status_config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE))?>"></div>
<script type="module" src="/assets/dist/epay-ui.js"></script>
</body>
</html>
