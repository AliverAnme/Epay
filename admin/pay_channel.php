<?php
/** 支付通道管理（原生 shadcn 列表与表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$id = intval($_GET['id'] ?? 0);
$my = (string)($_GET['my'] ?? ($id ? 'edit' : ''));
if($my === 'config' && $id > 0){
	epay_admin_view('admin-channel-config', ['title'=>'支付通道密钥配置','description'=>'使用当前支付插件提供的配置字段维护支付通道。','channelId'=>$id]);
}
if($my === 'test' && $id > 0){
	$row = $DB->getRow("SELECT id,name FROM pre_channel WHERE id=:id LIMIT 1", [':id'=>$id]);
	if(!$row) epay_admin_view('admin-form', ['title'=>'测试支付失败','description'=>'当前支付通道不存在。','notice'=>'请返回通道列表后重新选择。','links'=>[['label'=>'返回通道列表','href'=>'./pay_channel.php']]]);
	epay_admin_view('admin-channel-test', ['title'=>'测试支付','description'=>'创建一笔测试订单，验证当前通道的收银台跳转与回调链路。','channelId'=>$id,'channelName'=>$row['name']]);
}
if($my === 'add' || $my === 'edit' || $my === 'copy'){
	$row = in_array($my, ['edit','copy'], true) ? $DB->getRow("SELECT * FROM pre_channel WHERE id=:id LIMIT 1", [':id'=>$id]) : [];
	if(in_array($my, ['edit','copy'], true) && !$row) epay_admin_view('admin-form', ['title'=>'支付通道不存在','description'=>'没有找到要编辑或复制的支付通道。','notice'=>'请返回通道列表后重新选择。','links'=>[['label'=>'返回通道列表','href'=>'./pay_channel.php']]]);
	$typeOptions = [];
	foreach($DB->getAll("SELECT id,showname FROM pre_type ORDER BY id ASC") as $type) $typeOptions[] = ['value'=>(string)$type['id'],'label'=>$type['id'].' - '.$type['showname']];
	$pluginOptions = [];
	foreach($DB->getAll("SELECT name,showname FROM pre_plugin ORDER BY name ASC") as $plugin) $pluginOptions[] = ['value'=>(string)$plugin['name'],'label'=>$plugin['showname'].' ('.$plugin['name'].')'];
	$isCopy = $my === 'copy';
	$title = $my === 'edit' ? '编辑支付通道 ID:'.$id : ($isCopy ? '复制支付通道 ID:'.$id : '新增支付通道');
	$name = (string)($row['name'] ?? '');
	if($isCopy) $name .= ' 副本';
	epay_admin_view('admin-form', [
		'title'=>$title,'description'=>'配置支付方式、插件、费率、限额和每日风控。启用前请先完成密钥配置。',
		'action'=>['endpoint'=>'ajax_pay.php?act=saveChannel','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>true,'submitLabel'=>$isCopy ? '创建通道副本' : '保存通道'],
		'fields'=>[
			['key'=>'action','type'=>'hidden','value'=>$isCopy ? 'copy' : ($my === 'edit' ? 'edit' : 'add')], ['key'=>'id','type'=>'hidden','value'=>(string)$id],
			['key'=>'name','label'=>'通道名称','value'=>$name,'required'=>true], ['key'=>'type','label'=>'支付方式','type'=>'select','value'=>(string)($row['type'] ?? 0),'options'=>$typeOptions,'required'=>true], ['key'=>'plugin','label'=>'支付插件','type'=>'select','value'=>$row['plugin'] ?? '','options'=>$pluginOptions,'required'=>true],
			['key'=>'rate','label'=>'分成比例','type'=>'number','value'=>$row['rate'] ?? '100','required'=>true], ['key'=>'costrate','label'=>'通道成本','type'=>'number','value'=>$row['costrate'] ?? ''], ['key'=>'mode','label'=>'通道模式','type'=>'number','value'=>(string)($row['mode'] ?? 0)],
			['key'=>'paymin','label'=>'最小支付金额','type'=>'number','value'=>$row['paymin'] ?? ''], ['key'=>'paymax','label'=>'最大支付金额','type'=>'number','value'=>$row['paymax'] ?? ''], ['key'=>'daytop','label'=>'每日金额上限','type'=>'number','value'=>(string)($row['daytop'] ?? 0)], ['key'=>'daymaxorder','label'=>'每日订单上限','type'=>'number','value'=>(string)($row['daymaxorder'] ?? 0)],
			['key'=>'timestart','label'=>'开放时间','value'=>$row['timestart'] ?? ''], ['key'=>'timestop','label'=>'结束时间','value'=>$row['timestop'] ?? ''],
		],
		'links'=>[['label'=>'返回通道列表','href'=>'./pay_channel.php'], ...($id ? [['label'=>'通道密钥配置','href'=>'./pay_channel.php?my=config&id='.$id]] : [])],
	]);
}
epay_admin_view('admin-resource', ['resource'=>'channels','title'=>'支付通道','description'=>'管理支付方式、支付插件、费率与通道状态。','headerActions'=>[['label'=>'新增支付通道','href'=>'./pay_channel.php?my=add']]]);
