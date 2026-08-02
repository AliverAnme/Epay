<?php
/** 支付用户统计（原生 shadcn 统计视图） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';

$types = [['value'=>'', 'label'=>'所有支付方式']];
foreach($DB->getAll("SELECT id,showname FROM pre_type ORDER BY id ASC") as $row){
	$types[] = ['value'=>(string)$row['id'], 'label'=>(string)$row['showname']];
}
epay_admin_view('admin-stats', [
	'resource' => 'buyerstat',
	'title' => '支付用户统计',
	'description' => '按时间和支付方式统计支付账号、IP 或手机号的付款笔数。',
	'endpoint' => 'ajax_user.php?act=buyerStat',
	'columns' => [
		['key'=>'user', 'label'=>'支付账号 / IP'],
		['key'=>'order_count', 'label'=>'付款笔数'],
		['key'=>'trade_no', 'label'=>'最近订单号'],
		['key'=>'is_black', 'label'=>'黑名单'],
	],
	'fields' => [
		['key'=>'startday', 'label'=>'开始日期', 'type'=>'date', 'value'=>date('Y-m-d'), 'required'=>true],
		['key'=>'endday', 'label'=>'结束日期', 'type'=>'date', 'value'=>date('Y-m-d'), 'required'=>true],
		['key'=>'type', 'label'=>'支付方式', 'type'=>'select', 'value'=>'', 'options'=>$types],
		['key'=>'method', 'label'=>'统计维度', 'type'=>'select', 'value'=>'0', 'options'=>[
			['value'=>'0', 'label'=>'支付账号'],
			['value'=>'1', 'label'=>'支付 IP'],
			['value'=>'2', 'label'=>'支付手机号'],
		]],
	],
]);
