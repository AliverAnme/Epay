<?php
/** 新增付款（原生 shadcn 表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$app = isset($_GET['app']) && is_string($_GET['app']) && in_array($_GET['app'], ['alipay','wxpay','qqpay','bank'], true) ? $_GET['app'] : 'alipay';
$channels = [];
foreach($DB->getAll("SELECT id,name FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE FIND_IN_SET(:type,transtypes)>0) ORDER BY id ASC", [':type'=>$app]) as $row) $channels[] = ['value'=>(string)$row['id'],'label'=>$row['id'].' - '.$row['name']];
$copy = [];
if(isset($_GET['copy'])) $copy = $DB->find('transfer', '*', ['biz_no'=>trim($_GET['copy'])]) ?: [];
if(isset($_GET['account'])) $copy['account'] = trim((string)$_GET['account']);
if(isset($_GET['username'])) $copy['username'] = trim((string)$_GET['username']);
epay_admin_view('admin-form', [
	'title'=>'新增付款','description'=>'向支付宝、微信、QQ 钱包或银行卡账户发起一笔平台付款。',
	'action'=>['endpoint'=>'ajax_transfer.php?act=batch_submit','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>false,'submitLabel'=>'提交付款'],
	'fields'=>[
		['key'=>'type','type'=>'hidden','value'=>$app], ['key'=>'channel','label'=>'付款通道','type'=>'select','value'=>(string)($copy['channel'] ?? ($channels[0]['value'] ?? '')),'options'=>$channels],
		['key'=>'account','label'=>'收款账号','value'=>$copy['account'] ?? '','required'=>true], ['key'=>'name','label'=>'收款人姓名','value'=>$copy['username'] ?? ''], ['key'=>'money','label'=>'付款金额','type'=>'number','required'=>true,'placeholder'=>'RMB / 元'], ['key'=>'desc','label'=>'付款备注','type'=>'textarea','placeholder'=>'最多 32 个字'], ['key'=>'paypwd','label'=>'支付密码','type'=>'password','required'=>true],
	],
	'links'=>[['label'=>'付款记录','href'=>'./transfer.php'],['label'=>'创建红包','href'=>'./transfer_red.php']],
]);
