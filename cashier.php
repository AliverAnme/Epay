<?php
$is_defend = true;
$nosession = true;
require './includes/common.php';

@header('Content-Type: text/html; charset=UTF-8');

$other=isset($_GET['other'])?true:false;
$trade_no=is_scalar($_GET['trade_no'] ?? null)?daddslashes($_GET['trade_no']):'';
$sitename=is_scalar($_GET['sitename'] ?? null)?base64_decode(daddslashes($_GET['sitename'])):'';
$row=$DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no limit 1", [':trade_no'=>$trade_no]);
if(!$row)sysmsg('该订单号不存在，请返回来源地重新发起请求！');
if($row['status']==1)sysmsg('该订单已完成支付，请勿重复支付');
$gid = $DB->getColumn("SELECT gid FROM pre_user WHERE uid=:uid limit 1", [':uid'=>$row['uid']]);
$paytype = \lib\Channel::getTypes($row['uid'], $gid);

if(checkwechat()){
	$paytype = array_values($paytype);
	foreach($paytype as $i=>$s){
		if($s['name']=='wxpay'){
			$temp = $paytype[$i];
			$paytype[$i] = $paytype[0];
			$paytype[0] = $temp;
		}
	}
}

$epay_cashier_config = [
	'tradeNo' => $trade_no,
	'sitename' => $sitename ? $sitename : $conf['sitename'],
	'other' => $other,
	'order' => [
		'name' => $row['name'],
		'addtime' => $row['addtime'],
		'money' => $row['money'],
		'realmoney' => $row['realmoney'] ? $row['realmoney'] : $row['money'],
	],
	'paytype' => array_map(function($channel){
		return [
			'id' => $channel['id'],
			'name' => $channel['name'],
			'showname' => $channel['showname'],
		];
	}, $paytype),
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>收银台 | <?php echo h($sitename?$sitename:$conf['sitename'])?></title>
<link rel="stylesheet" href="/assets/dist/epay-ui.css">
</head>
<body>
<div id="epay-react-root" data-epay-view="cashier" data-epay-config="<?php echo h(json_encode($epay_cashier_config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE))?>"></div>
<script type="module" src="/assets/dist/epay-ui.js"></script>
</body>
</html>
