<?php
/** 分账规则（原生 shadcn 列表与表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$my = (string)($_GET['my'] ?? '');
if($my === 'add'){
	$channels = [];
	foreach($DB->getAll("SELECT id,name FROM pre_channel ORDER BY id ASC") as $row) $channels[] = ['value'=>(string)$row['id'],'label'=>$row['id'].' - '.$row['name']];
	epay_admin_view('admin-form', [
		'title'=>'新增分账规则','description'=>'为支付通道配置一个或多个接收方。接收方 JSON 格式为 [{"account":"账号","name":"姓名","rate":"比例"}]。',
		'action'=>['endpoint'=>'ajax_profitsharing.php?act=add_receiver','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>true,'submitLabel'=>'保存规则'],
		'fields'=>[
			['key'=>'channel','label'=>'支付通道','type'=>'select','value'=>'','options'=>$channels,'required'=>true],
			['key'=>'uid','label'=>'商户 ID','type'=>'number','placeholder'=>'留空表示全平台'],
			['key'=>'subchannel','label'=>'子通道 ID','type'=>'number','placeholder'=>'可留空'],
			['key'=>'minmoney','label'=>'最低订单金额','type'=>'number','placeholder'=>'可留空'],
			['key'=>'mode','label'=>'分账模式','type'=>'select','value'=>'0','options'=>[['value'=>'0','label'=>'实时分账'],['value'=>'1','label'=>'延迟分账']]],
			['key'=>'info','label'=>'接收方 JSON','type'=>'textarea','required'=>true,'placeholder'=>'[{"account":"...","name":"...","rate":"10"}]'],
		],
		'links'=>[['label'=>'返回分账规则','href'=>'./ps_receiver.php']],
	]);
}
epay_admin_view('admin-resource', [
	'resource'=>'ps-receivers','title'=>'分账规则','description'=>'配置支付通道的分账接收方和比例。',
	'headerActions'=>[['label'=>'新增分账规则','href'=>'./ps_receiver.php?my=add']],
]);
