<?php
/** 系统数据清理（原生 shadcn 维护视图） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';

$mod = isset($_GET['mod']) ? (string)$_GET['mod'] : '';
if(isset($_GET['format']) && $_GET['format'] === 'json'){
	if($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['admin_csrf_token'])) echojsonmsg('CSRF验证失败');
	$days = max(1, intval($_POST['days'] ?? $_GET['days'] ?? 30));
	$map = [
		'cleancache'=>['msg'=>'清理系统设置缓存成功！'],
		'cleanorder'=>['table'=>'pre_order','column'=>'addtime','msg'=>'删除30天前订单记录成功！'],
		'cleansettle'=>['table'=>'pre_settle','column'=>'addtime','msg'=>'删除30天前结算记录成功！'],
		'cleanrecord'=>['table'=>'pre_record','column'=>'date','msg'=>'删除30天前资金明细成功！'],
		'cleantransfer'=>['table'=>'pre_transfer','column'=>'paytime','msg'=>'删除30天前付款记录成功！'],
		'cleanpsorder'=>['table'=>'pre_psorder','column'=>'addtime','msg'=>'删除30天前分账记录成功！'],
		'cleanlog'=>['table'=>'pre_log','column'=>'date','msg'=>'删除30天前登录记录成功！'],
	];
	$custom = ['cleanorderi'=>'cleanorder','cleansettlei'=>'cleansettle','cleanrecordi'=>'cleanrecord','cleantransferi'=>'cleantransfer','cleanpsorderi'=>'cleanpsorder','cleanlogi'=>'cleanlog'];
	if(isset($custom[$mod])) $mod = $custom[$mod];
	if($mod === 'cleancache'){
		$CACHE->clear(); if(function_exists('opcache_reset')) @opcache_reset();
		echojson(['code'=>0,'msg'=>$map[$mod]['msg']]);
	}
	if(!isset($map[$mod])) echojsonmsg('无效的清理操作');
	$item = $map[$mod];
	$threshold = date('Y-m-d H:i:s', strtotime('-'.$days.' days'));
	$DB->exec("DELETE FROM `{$item['table']}` WHERE `{$item['column']}`<:thtime", [':thtime'=>$threshold]);
	$DB->exec("OPTIMIZE TABLE `{$item['table']}`");
	echojson(['code'=>0,'msg'=>$days === 30 ? $item['msg'] : '清理完成，共处理 '.$days.' 天前的数据。']);
}

$actions = [
	['label'=>'清理设置缓存','description'=>'清理数据库配置缓存并刷新 OPcache。','endpoint'=>'./clean.php?mod=cleancache&format=json','method'=>'POST'],
	['label'=>'删除 30 天前订单','description'=>'删除订单历史记录，操作不可恢复。','endpoint'=>'./clean.php?mod=cleanorder&format=json','method'=>'POST','destructive'=>true],
	['label'=>'删除 30 天前结算','description'=>'删除结算历史记录，操作不可恢复。','endpoint'=>'./clean.php?mod=cleansettle&format=json','method'=>'POST','destructive'=>true],
	['label'=>'删除 30 天前资金明细','description'=>'删除资金变动历史记录，操作不可恢复。','endpoint'=>'./clean.php?mod=cleanrecord&format=json','method'=>'POST','destructive'=>true],
	['label'=>'删除 30 天前付款记录','description'=>'删除平台代付历史记录，操作不可恢复。','endpoint'=>'./clean.php?mod=cleantransfer&format=json','method'=>'POST','destructive'=>true],
	['label'=>'删除 30 天前分账记录','description'=>'删除分账历史记录，操作不可恢复。','endpoint'=>'./clean.php?mod=cleanpsorder&format=json','method'=>'POST','destructive'=>true],
	['label'=>'删除 30 天前登录日志','description'=>'删除管理员和商户登录日志，操作不可恢复。','endpoint'=>'./clean.php?mod=cleanlog&format=json','method'=>'POST','destructive'=>true],
];
epay_admin_view('admin-maintenance', ['title'=>'系统数据清理','description'=>'对缓存和历史数据执行可审计的维护操作。','actions'=>$actions]);
