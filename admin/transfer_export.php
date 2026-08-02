<?php
/** 付款记录导出（原生 shadcn 表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
epay_admin_view('admin-form', [
	'title'=>'导出付款记录', 'description'=>'按提交时间、商户、付款方式和状态生成付款文件。',
	'action'=>['endpoint'=>'./download.php?act=transfer', 'method'=>'GET', 'submitLabel'=>'导出付款记录'],
	'fields'=>[
		['key'=>'starttime','label'=>'开始日期','type'=>'date'],
		['key'=>'endtime','label'=>'结束日期','type'=>'date'],
		['key'=>'uid','label'=>'商户号','placeholder'=>'留空为全部商户'],
		['key'=>'type','label'=>'付款方式','type'=>'select','value'=>'','options'=>[
			['value'=>'','label'=>'所有付款方式'], ['value'=>'alipay','label'=>'支付宝'], ['value'=>'wxpay','label'=>'微信'], ['value'=>'qqpay','label'=>'QQ 钱包'], ['value'=>'bank','label'=>'银行卡'],
		]],
		['key'=>'dstatus','label'=>'状态','type'=>'select','value'=>'','options'=>[
			['value'=>'','label'=>'全部状态'], ['value'=>'3','label'=>'待处理'], ['value'=>'0','label'=>'正在处理'], ['value'=>'1','label'=>'转账成功'], ['value'=>'2','label'=>'转账失败'],
		]],
		['key'=>'sheet','label'=>'导出模板','type'=>'select','value'=>'common','options'=>[
			['value'=>'common','label'=>'通用表格'], ['value'=>'alipay','label'=>'支付宝批量付款'], ['value'=>'mybank','label'=>'网商银行批量付款'], ['value'=>'wxpay','label'=>'微信转账到零钱'],
		]],
	],
]);
