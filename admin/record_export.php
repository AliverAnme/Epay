<?php
/** 资金明细导出（原生 shadcn 表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
epay_admin_view('admin-form', [
	'title'=>'导出资金明细', 'description'=>'按时间、商户和操作类型导出资金变动记录。',
	'action'=>['endpoint'=>'./download.php?act=record', 'method'=>'GET', 'submitLabel'=>'导出明细'],
	'fields'=>[
		['key'=>'starttime','label'=>'开始日期','type'=>'date','required'=>true],
		['key'=>'endtime','label'=>'结束日期','type'=>'date','required'=>true],
		['key'=>'uid','label'=>'商户号','placeholder'=>'留空为全部商户'],
		['key'=>'type','label'=>'操作类型','placeholder'=>'留空为全部类型'],
	],
]);
