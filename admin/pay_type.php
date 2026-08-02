<?php
/** 支付方式管理（原生 shadcn 列表与表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$id = intval($_GET['id'] ?? 0);
$my = (string)($_GET['my'] ?? ($id ? 'edit' : ''));
if($my === 'add' || $my === 'edit'){
	$row = $my === 'edit' ? $DB->getRow("SELECT * FROM pre_type WHERE id=:id LIMIT 1", [':id'=>$id]) : [];
	epay_admin_view('admin-form', [
		'title'=>$my === 'edit' ? '编辑支付方式 ID:'.$id : '新增支付方式','description'=>'维护支付方式调用值、显示名称和支持设备。',
		'action'=>['endpoint'=>'ajax_pay.php?act=savePayType','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>true,'submitLabel'=>'保存支付方式'],
		'fields'=>[
			['key'=>'action','type'=>'hidden','value'=>$my === 'edit' ? 'edit' : 'add'], ['key'=>'id','type'=>'hidden','value'=>(string)$id],
			['key'=>'name','label'=>'调用值','value'=>$row['name'] ?? '','required'=>true,'placeholder'=>'仅允许字母、数字、下划线、点和短横线'], ['key'=>'showname','label'=>'显示名称','value'=>$row['showname'] ?? '','required'=>true],
			['key'=>'device','label'=>'支持设备','type'=>'select','value'=>(string)($row['device'] ?? 0),'options'=>[['value'=>'0','label'=>'电脑端'],['value'=>'1','label'=>'移动端'],['value'=>'2','label'=>'电脑 + 移动端']]],
		],
		'links'=>[['label'=>'返回支付方式','href'=>'./pay_type.php']],
	]);
}
$paymentTypes = $DB->getAll("SELECT id,name,showname,device,status FROM pre_type ORDER BY id ASC");
epay_admin_view('admin-resource', ['resource'=>'types','title'=>'支付方式','description'=>'管理系统可用的支付方式与设备类型。','rows'=>$paymentTypes,'headerActions'=>[['label'=>'新增支付方式','href'=>'./pay_type.php?my=add']]]);
