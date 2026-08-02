<?php
/** 获取用户标识（原生 shadcn 工具视图） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$channels = ['wechat'=>[], 'applet'=>[], 'alipayuid'=>[], 'apptoken'=>[]];
foreach($DB->getAll("SELECT id,name,type FROM pre_weixin ORDER BY id ASC") as $row){
	$key = (int)$row['type'] === 1 ? 'applet' : 'wechat';
	$channels[$key][] = ['value'=>(string)$row['id'], 'label'=>(string)$row['name']];
}
foreach($DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin IN ('alipay','alipaysl','alipayd','alipayrp') ORDER BY id ASC") as $row) $channels['alipayuid'][] = ['value'=>(string)$row['id'], 'label'=>$row['name'].' ('.$row['plugin'].')'];
foreach($DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin='alipaysl' ORDER BY id ASC") as $row) $channels['apptoken'][] = ['value'=>(string)$row['id'], 'label'=>$row['name'].' ('.$row['plugin'].')'];
epay_admin_view('admin-token', [
	'title'=>'获取用户标识', 'description'=>'生成微信公众号、小程序和支付宝授权链接。', 'siteurl'=>$siteurl,
	'app'=>isset($_GET['app']) && in_array($_GET['app'], ['wechat','applet','alipayuid','apptoken'], true) ? $_GET['app'] : 'wechat',
	'channels'=>$channels,
	'defaults'=>['wechat'=>(string)($conf['login_wx'] ?? ''), 'applet'=>(string)($conf['login_wxa'] ?? ''), 'alipayuid'=>(string)($conf['login_alipay'] ?? ''), 'apptoken'=>(string)($conf['login_alipay'] ?? '')],
]);
