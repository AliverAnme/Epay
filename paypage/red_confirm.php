<?php
if(!defined('IN_CRONLITE'))exit();
$epay_transfer_config=[
	'kind'=>'red',
	'sitename'=>$conf['sitename'],
	'amount'=>$trans['money'],
	'createdAt'=>$trans['addtime'],
	'payload'=>[
		'n'=>$biz_no,
		't'=>$time,
		's'=>$sign,
		'openid'=>$openid,
	],
];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>红包领取确认</title>
    <link href="/assets/dist/epay-ui.css" rel="stylesheet">
</head>
<body>
<div id="epay-react-root" data-epay-view="transfer-confirm" data-epay-config="<?php echo h(json_encode($epay_transfer_config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE))?>"></div>
<script type="module" src="/assets/dist/epay-ui.js"></script>
</body>
</html>
