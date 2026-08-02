<?php
/** 批量付款（原生 shadcn JSON 导入表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$type = isset($_GET['type']) && is_scalar($_GET['type']) ? intval($_GET['type']) : 1;
$map = [1=>['name'=>'支付宝','app'=>'alipay'],2=>['name'=>'微信','app'=>'wxpay'],3=>['name'=>'QQ 钱包','app'=>'qqpay'],4=>['name'=>'银行卡','app'=>'bank']];
$meta = $map[$type] ?? $map[1];
$channels = [];
foreach($DB->getAll("SELECT id,name FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE FIND_IN_SET(:type,transtypes)>0) ORDER BY id ASC", [':type'=>$meta['app']]) as $row) $channels[] = ['value'=>(string)$row['id'],'label'=>$row['id'].' - '.$row['name']];
epay_admin_view('admin-form', [
	'title'=>$meta['name'].'批量付款','description'=>'粘贴 JSON 收款列表后批量提交。格式：[{"account":"账号","name":"姓名","money":"10.00","desc":"备注"}]，最多 500 条。',
	'action'=>['endpoint'=>'ajax_transfer.php?act=batch_many','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>false,'submitLabel'=>'开始批量付款'],
	'fields'=>[
		['key'=>'type','type'=>'hidden','value'=>$meta['app']], ['key'=>'channel','label'=>'付款通道','type'=>'select','value'=>(string)($channels[0]['value'] ?? ''),'options'=>$channels], ['key'=>'items','label'=>'收款列表 JSON','type'=>'textarea','required'=>true,'placeholder'=>'[{"account":"alipay@example.com","name":"张三","money":"10.00"}]'], ['key'=>'paypwd','label'=>'支付密码','type'=>'password','required'=>true],
	],
	'links'=>[['label'=>'付款记录','href'=>'./transfer.php'],['label'=>'单笔付款','href'=>'./transfer_add.php?app='.$meta['app']]],
]);
