<?php
/** 支付通道轮询（原生 shadcn 列表与表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$my = (string)($_GET['my'] ?? '');
$id = intval($_GET['id'] ?? 0);
$types = [];
foreach($DB->getAll("SELECT id,showname FROM pre_type ORDER BY id ASC") as $row) $types[] = ['value'=>(string)$row['id'],'label'=>(string)$row['showname']];
if($my === 'config' && $id > 0){
	epay_admin_view('admin-roll-config', ['title'=>'配置轮询通道','description'=>'维护轮询组的通道顺序和权重。','rollId'=>$id]);
}
if($my === 'add' || $my === 'edit'){
	$row = $my === 'edit' ? $DB->getRow("SELECT * FROM pre_roll WHERE id=:id LIMIT 1", [':id'=>$id]) : [];
	epay_admin_view('admin-form', [
		'title'=>$my === 'edit' ? '编辑轮询组' : '新增轮询组','description'=>'配置支付方式和轮询策略，通道明细可在保存后继续维护。',
		'action'=>['endpoint'=>'ajax_pay.php?act=saveRoll','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>true,'submitLabel'=>'保存轮询组'],
		'values'=>['action'=>$my === 'edit' ? 'edit' : 'add','id'=>$id,'name'=>$row['name'] ?? '','type'=>$row['type'] ?? '0','kind'=>$row['kind'] ?? '0'],
		'fields'=>[
			['key'=>'action','type'=>'hidden','value'=>$my === 'edit' ? 'edit' : 'add'], ['key'=>'id','type'=>'hidden','value'=>(string)$id],
			['key'=>'name','label'=>'显示名称','required'=>true], ['key'=>'type','label'=>'支付方式','type'=>'select','value'=>(string)($row['type'] ?? 0),'options'=>$types,'required'=>true], ['key'=>'kind','label'=>'轮询方式','type'=>'select','value'=>(string)($row['kind'] ?? 0),'options'=>[['value'=>'0','label'=>'按顺序依次轮询'],['value'=>'1','label'=>'按权重随机轮询'],['value'=>'2','label'=>'仅使用首个已启用通道']]],
		],
		'links'=>[['label'=>'返回列表','href'=>'./pay_roll.php']],
	]);
}
$typeNames = [];
foreach($DB->getAll("SELECT id,showname FROM pre_type") as $row) $typeNames[$row['id']] = $row['showname'];
$kindNames = ['按顺序依次轮询','按权重随机轮询','仅使用首个已启用'];
$rows = [];
foreach($DB->getAll("SELECT id,name,type,kind,info,status FROM pre_roll ORDER BY id ASC") as $row) $rows[] = ['id'=>(string)$row['id'],'name'=>(string)$row['name'],'typeText'=>$typeNames[$row['type']] ?? $row['type'],'kindText'=>$kindNames[(int)$row['kind']] ?? $row['kind'],'info'=>(string)$row['info'],'status'=>(string)$row['status']];
epay_admin_view('admin-resource', ['resource'=>'rolls','title'=>'支付通道轮询','description'=>'管理通道轮询组和轮询策略。','rows'=>$rows,'headerActions'=>[['label'=>'新增轮询组','href'=>'./pay_roll.php?my=add']]]);
