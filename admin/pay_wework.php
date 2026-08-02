<?php
/** 企业微信账号管理（原生 shadcn 列表与表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$my = (string)($_GET['my'] ?? '');
$id = intval($_GET['id'] ?? 0);
if($my === 'add' || $my === 'edit'){
	$row = $my === 'edit' ? $DB->getRow("SELECT * FROM pre_wework WHERE id=:id LIMIT 1", [':id'=>$id]) : [];
	epay_admin_view('admin-form', [
		'title'=>$my === 'edit' ? '编辑企业微信' : '新增企业微信','description'=>'配置企业微信企业 ID 与 Secret，保存后可在列表测试连通性。',
		'action'=>['endpoint'=>'ajax_pay.php?act=saveWework','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>true,'submitLabel'=>'保存账号'],
		'values'=>['action'=>$my === 'edit' ? 'edit' : 'add','id'=>$id,'name'=>$row['name'] ?? '','appid'=>$row['appid'] ?? '','appsecret'=>$row['appsecret'] ?? ''],
		'fields'=>[
			['key'=>'action','type'=>'hidden','value'=>$my === 'edit' ? 'edit' : 'add'], ['key'=>'id','type'=>'hidden','value'=>(string)$id],
			['key'=>'name','label'=>'名称','required'=>true], ['key'=>'appid','label'=>'企业 ID','required'=>true], ['key'=>'appsecret','label'=>'Secret','type'=>'password','required'=>true],
		],
		'links'=>[['label'=>'返回列表','href'=>'./pay_wework.php']],
	]);
}
$rows = [];
foreach($DB->getAll("SELECT id,name,appid,status FROM pre_wework ORDER BY id ASC") as $row){
	$kfnum = $DB->getColumn("SELECT COUNT(*) FROM pre_wxkfaccount WHERE wid=:wid", [':wid'=>$row['id']]);
	$rows[] = ['id'=>(string)$row['id'],'name'=>(string)$row['name'],'appid'=>(string)$row['appid'],'kfnum'=>(string)$kfnum,'status'=>(string)$row['status']];
}
epay_admin_view('admin-resource', ['resource'=>'wework','title'=>'企业微信账号','description'=>'管理企业微信客服支付账号和启用状态。','rows'=>$rows,'headerActions'=>[['label'=>'新增账号','href'=>'./pay_wework.php?my=add'],['label'=>'客服支付设置','href'=>'./set_wxkf.php']]]);
