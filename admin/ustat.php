<?php
/** 商户支付统计（原生 shadcn 统计视图） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';

epay_admin_view('admin-stats', [
	'title' => '商户支付统计',
	'description' => '按商户、支付方式或通道汇总订单金额、支付金额、分成及利润。',
	'endpoint' => 'ajax_user.php?act=userPayStat',
	'response' => 'dynamic',
	'fields' => [
		['key'=>'startday', 'label'=>'开始日期', 'type'=>'date', 'value'=>date('Y-m-d'), 'required'=>true],
		['key'=>'endday', 'label'=>'结束日期', 'type'=>'date', 'value'=>date('Y-m-d'), 'required'=>true],
		['key'=>'method', 'label'=>'汇总维度', 'type'=>'select', 'value'=>'type', 'options'=>[
			['value'=>'type', 'label'=>'按支付方式'],
			['value'=>'channel', 'label'=>'按支付通道'],
		]],
		['key'=>'type', 'label'=>'统计口径', 'type'=>'select', 'value'=>'0', 'options'=>[
			['value'=>'0', 'label'=>'订单金额'],
			['value'=>'1', 'label'=>'支付金额'],
			['value'=>'2', 'label'=>'分成金额'],
			['value'=>'3', 'label'=>'手续费利润'],
			['value'=>'4', 'label'=>'代付金额'],
		]],
	],
]);
