<?php
/** 订单导出（原生 shadcn 表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$types = [['value'=>'0', 'label'=>'所有支付方式']];
foreach($DB->getAll("SELECT id,showname FROM pre_type ORDER BY id ASC") as $row) $types[] = ['value'=>(string)$row['id'], 'label'=>(string)$row['showname']];
epay_admin_view('admin-form', [
	'title'=>'导出订单', 'description'=>'按时间、商户、支付方式和订单状态生成订单文件。',
	'action'=>['endpoint'=>'./download.php?act=order', 'method'=>'GET', 'submitLabel'=>'导出订单'],
	'fields'=>[
		['key'=>'starttime','label'=>'开始日期','type'=>'date','required'=>true],
		['key'=>'endtime','label'=>'结束日期','type'=>'date','required'=>true],
		['key'=>'uid','label'=>'商户号','placeholder'=>'留空为全部商户'],
		['key'=>'type','label'=>'支付方式','type'=>'select','value'=>'0','options'=>$types],
		['key'=>'channel','label'=>'通道 ID','placeholder'=>'留空为全部通道'],
		['key'=>'dstatus','label'=>'订单状态','type'=>'select','value'=>'-1','options'=>[
			['value'=>'-1','label'=>'全部状态'], ['value'=>'0','label'=>'未支付'], ['value'=>'1','label'=>'已支付'], ['value'=>'2','label'=>'已退款'], ['value'=>'3','label'=>'已冻结'],
		]],
	],
]);
