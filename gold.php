<?php
/*
 * 微信点金计划iframe页面
*/
$nosession = true;
include("./includes/common.php");

@header('Content-Type: text/html; charset=UTF-8');

$sub_mch_id = $_GET['sub_mch_id'];
$out_trade_no = $_GET['out_trade_no'];
$check_code = $_GET['check_code'];

$errmsg = null;

if($out_trade_no){
	$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no limit 1", [':trade_no'=>$out_trade_no]);
	if(!$order)$order = $DB->getRow("SELECT * FROM pre_order WHERE api_trade_no=:trade_no limit 1", [':trade_no'=>$out_trade_no]);
	if(!$order)$order = $DB->getRow("SELECT * FROM pre_order WHERE bill_mch_trade_no=:trade_no limit 1", [':trade_no'=>$out_trade_no]);
	if($order){
		$trade_no = $order['trade_no'];
		$jump_url = $siteurl.'pay/return/'.$trade_no.'/';
	}else{
		$errmsg = '订单号不存在<br/>out_trade_no='.$out_trade_no;
	}
}else{
	$errmsg = '订单号不能为空';
}

$epay_gold_config = [
    'amount' => isset($order['money']) ? $order['money'] : null,
    'error' => $errmsg,
    'jumpUrl' => isset($jump_url) ? $jump_url : null,
    'sitename' => $conf['sitename'],
];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>支付结果页面</title>
    <link rel="stylesheet" href="/assets/dist/epay-ui.css">
    <script type="text/javascript" charset="UTF-8" src="https://wx.gtimg.com/pay_h5/goldplan/js/jgoldplan-1.0.0.js"></script>
</head>
<body>
<div id="epay-react-root" data-epay-view="gold-plan" data-epay-config="<?php echo h(json_encode($epay_gold_config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE))?>"></div>
<script type="module" src="/assets/dist/epay-ui.js"></script>
</body>
</html>
