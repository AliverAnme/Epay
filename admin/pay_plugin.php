<?php
/**
 * 支付插件（原生 shadcn 管理视图）
 */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
$pluginName = trim((string)($_GET['plugin'] ?? ''));
if($pluginName !== ''){
	$row = $DB->getRow("SELECT name,showname,author,link FROM pre_plugin WHERE name=:name LIMIT 1", [':name'=>$pluginName]);
	if(!$row) epay_admin_view('admin-form', ['title'=>'插件不存在','description'=>'没有找到对应的支付插件。','notice'=>'请返回插件列表后重新选择。','links'=>[['label'=>'返回插件列表','href'=>'./pay_plugin.php']]]);
	epay_admin_view('admin-form', [
		'title'=>'支付插件详情','description'=>'查看插件标识、说明和维护信息。','notice'=>'插件详情为只读信息，通道密钥请从支付通道列表进入配置。',
		'fields'=>[
			['key'=>'name','label'=>'插件标识','value'=>$row['name'],'readOnly'=>true],
			['key'=>'showname','label'=>'插件名称','value'=>$row['showname'],'readOnly'=>true],
			['key'=>'author','label'=>'作者','value'=>$row['author'],'readOnly'=>true],
			['key'=>'link','label'=>'项目地址','value'=>$row['link'],'readOnly'=>true],
			['key'=>'path','label'=>'插件路径','value'=>'/plugins/'.$row['name'].'/','readOnly'=>true],
		],
		'links'=>[['label'=>'返回插件列表','href'=>'./pay_plugin.php'],['label'=>'查看通道','href'=>'./pay_channel.php?plugin='.rawurlencode($row['name'])]],
	]);
}
$my = (string)($_POST['my'] ?? $_GET['my'] ?? '');
if($my === 'refresh' && $_SERVER['REQUEST_METHOD'] === 'POST'){
	if(!isset($_POST['csrf_token']) || !isset($_SESSION['admin_csrf_token']) || !hash_equals((string)$_SESSION['admin_csrf_token'], (string)$_POST['csrf_token'])) exit(json_encode(['code'=>-403,'msg'=>'CSRF验证失败'], JSON_UNESCAPED_UNICODE));
	\lib\Plugin::updateAll();
	header('Content-Type: application/json; charset=utf-8');
	exit(json_encode(['code'=>0,'msg'=>'插件列表刷新成功'], JSON_UNESCAPED_UNICODE));
}
if($my === 'refresh') epay_admin_view('admin-maintenance', ['title'=>'刷新支付插件','description'=>'扫描 plugins 目录并同步支付插件清单。','actions'=>[['label'=>'刷新插件列表','description'=>'重新读取插件配置并更新 pre_plugin 数据。','endpoint'=>'./pay_plugin.php?my=refresh','method'=>'POST']]]);
$title = '支付插件';
$plugins = $DB->getAll("SELECT name,showname,types,transtypes FROM pre_plugin ORDER BY name ASC");
$epay_ui_view = 'admin-resource';
$epay_ui_config = [
	'resource' => 'plugins',
	'title' => $title,
	'description' => '查看已安装的支付插件及支持的支付类型。',
	'csrf_token' => $_SESSION['admin_csrf_token'],
	'rows' => $plugins,
	'headerActions' => [['label'=>'刷新插件列表','href'=>'./pay_plugin.php?my=refresh']],
];
include './head.php';
exit('</body></html>');
