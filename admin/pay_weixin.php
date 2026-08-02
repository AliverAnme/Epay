<?php
/** 公众号 / 小程序管理（原生 shadcn 列表与表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$my = (string)($_GET['my'] ?? '');
$id = intval($_GET['id'] ?? 0);
if($my === 'add' || $my === 'edit'){
	$row = $my === 'edit' ? $DB->getRow("SELECT * FROM pre_weixin WHERE id=:id LIMIT 1", [':id'=>$id]) : [];
	epay_admin_view('admin-form', [
		'title'=>$my === 'edit' ? '编辑公众号 / 小程序' : '新增公众号 / 小程序',
		'description'=>'配置微信服务号或小程序的 AppID 与密钥。',
		'action'=>['endpoint'=>'ajax_pay.php?act=saveWeixin','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>true,'submitLabel'=>'保存配置'],
		'values'=>['action'=>$my === 'edit' ? 'edit' : 'add','id'=>$id,'type'=>$row['type'] ?? '0','name'=>$row['name'] ?? '','appid'=>$row['appid'] ?? '','appsecret'=>$row['appsecret'] ?? ''],
		'fields'=>[
			['key'=>'action','type'=>'hidden','value'=>$my === 'edit' ? 'edit' : 'add'], ['key'=>'id','type'=>'hidden','value'=>(string)$id],
			['key'=>'type','label'=>'类别','type'=>'select','value'=>(string)($row['type'] ?? 0),'options'=>[['value'=>'0','label'=>'微信服务号'],['value'=>'1','label'=>'微信小程序']]],
			['key'=>'name','label'=>'名称','required'=>true,'placeholder'=>'仅用于显示'], ['key'=>'appid','label'=>'APPID','required'=>true], ['key'=>'appsecret','label'=>'APPSECRET','type'=>'password','required'=>true],
		],
		'links'=>[['label'=>'返回列表','href'=>'./pay_weixin.php']],
	]);
}
$rows = [];
foreach($DB->getAll("SELECT id,type,name,appid FROM pre_weixin ORDER BY id ASC") as $row) $rows[] = ['id'=>(string)$row['id'],'typeText'=>(int)$row['type'] === 1 ? '微信小程序' : '微信服务号','name'=>(string)$row['name'],'appid'=>(string)$row['appid']];
epay_admin_view('admin-resource', ['resource'=>'weixin','title'=>'公众号 / 小程序','description'=>'管理用于 OAuth、JSAPI 与小程序支付的微信应用。','rows'=>$rows,'headerActions'=>[['label'=>'新增应用','href'=>'./pay_weixin.php?my=add']]]);
