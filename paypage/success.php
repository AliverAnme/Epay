<?php
$is_defend = true;
include("./inc.php");
@header('Content-Type: text/html; charset=UTF-8');
$trade_no=is_scalar($_GET['trade_no'] ?? null)?daddslashes($_GET['trade_no']):'';
$row=$DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no limit 1", [':trade_no'=>$trade_no]);
if(!$row)showerror('订单号不存在');
if($row['status']!=1)showerror('订单未完成支付');
if(!isset($_SESSION['paypage_trade_no']) || $_SESSION['paypage_trade_no']!=$trade_no)showerror('订单校验失败');
$userrow=$DB->getRow("select codename,username from pre_user where uid=:uid limit 1", [':uid'=>(int)$row['uid']]);
$codename = !empty($userrow['codename'])?$userrow['codename']:$userrow['username'];
$epay_status_config=[
	'status'=>'success',
	'sitename'=>$conf['sitename'],
	'amount'=>$row['money'],
	'recipient'=>$codename,
	'tradeNo'=>$trade_no,
	'createdAt'=>$row['endtime'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>支付成功页面</title>
    <link rel="stylesheet" href="/assets/dist/epay-ui.css">
</head>
<body>
<div id="epay-react-root" data-epay-view="payment-status" data-epay-config="<?php echo h(json_encode($epay_status_config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE))?>"></div>
<script type="module" src="/assets/dist/epay-ui.js"></script>
</body>
</html>
