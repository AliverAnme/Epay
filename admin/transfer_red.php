<?php
/** 创建红包（原生 shadcn 表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$app = isset($_GET['app']) && is_string($_GET['app']) && in_array($_GET['app'], ['alipay','wxpay'], true) ? $_GET['app'] : 'alipay';
$channels = [];
foreach($DB->getAll("SELECT id,name FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE FIND_IN_SET(:type,transtypes)>0) ORDER BY id ASC", [':type'=>$app]) as $row) $channels[] = ['value'=>(string)$row['id'],'label'=>$row['id'].' - '.$row['name']];
epay_admin_view('admin-form', [
	'title'=>'创建红包','description'=>'创建支付宝或微信红包，创建后在付款记录中查看二维码或领取状态。',
	'action'=>['endpoint'=>'ajax_transfer.php?act=red_submit','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>false,'submitLabel'=>'创建红包'],
	'fields'=>[
		['key'=>'type','label'=>'红包类型','type'=>'select','value'=>$app,'options'=>[['value'=>'alipay','label'=>'支付宝'],['value'=>'wxpay','label'=>'微信']],], ['key'=>'channel','label'=>'付款通道','type'=>'select','value'=>(string)($channels[0]['value'] ?? ''),'options'=>$channels], ['key'=>'money','label'=>'红包金额','type'=>'number','required'=>true,'placeholder'=>'RMB / 元'], ['key'=>'desc','label'=>'红包备注','type'=>'textarea','placeholder'=>'可留空'], ['key'=>'paypwd','label'=>'支付密码','type'=>'password','required'=>true],
	],
	'links'=>[['label'=>'付款记录','href'=>'./transfer.php'],['label'=>'新增付款','href'=>'./transfer_add.php']],
]);
