<?php
/** 付款统计（原生 shadcn 统计视图） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';

epay_admin_view('admin-stats', [
	'title' => '付款统计',
	'description' => '按日期和付款方式汇总成功付款笔数及金额。',
	'endpoint' => 'ajax_transfer.php?act=stat',
	'columns' => [
		['key'=>'account', 'label'=>'付款账号'],
		['key'=>'username', 'label'=>'姓名'],
		['key'=>'order_count', 'label'=>'付款笔数'],
		['key'=>'money', 'label'=>'付款金额'],
	],
	'fields' => [
		['key'=>'startday', 'label'=>'开始日期', 'type'=>'date', 'value'=>date('Y-m-d'), 'required'=>true],
		['key'=>'endday', 'label'=>'结束日期', 'type'=>'date', 'value'=>date('Y-m-d'), 'required'=>true],
		['key'=>'type', 'label'=>'付款方式', 'type'=>'select', 'value'=>'', 'options'=>[
			['value'=>'', 'label'=>'所有付款方式'],
			['value'=>'alipay', 'label'=>'支付宝'],
			['value'=>'wxpay', 'label'=>'微信'],
			['value'=>'qqpay', 'label'=>'QQ 钱包'],
			['value'=>'bank', 'label'=>'银行卡'],
		]],
	],
]);
