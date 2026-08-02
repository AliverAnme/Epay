<?php
/** 邀请码管理（原生 shadcn 列表与操作） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$my = isset($_GET['my']) ? (string)$_GET['my'] : '';
if($my === 'add'){
	epay_admin_view('admin-form', [
		'title'=>'生成邀请码', 'description'=>'一次最多生成 1000 个邀请码，生成后可在列表中复制使用。',
		'action'=>['endpoint'=>'ajax.php?act=generateInvite', 'method'=>'POST', 'submitMode'=>'fetch', 'submitLabel'=>'生成邀请码'],
		'fields'=>[['key'=>'num','label'=>'生成数量','type'=>'number','value'=>'10','required'=>true,'placeholder'=>'1 - 1000']],
		'links'=>[['label'=>'返回邀请码列表','href'=>'./invitecode.php']],
	]);
}
if($my === 'qk' || $my === 'qkuse'){
	$status = $my === 'qkuse' ? 1 : -1;
	epay_admin_view('admin-maintenance', [
		'title'=>$status === 1 ? '清空已使用邀请码' : '清空全部邀请码',
		'description'=>'清理操作不可恢复，请确认已经完成必要的数据备份。',
		'actions'=>[['label'=>$status === 1 ? '清空已使用邀请码' : '清空全部邀请码','description'=>'确认后立即删除对应邀请码。','endpoint'=>'./ajax.php?act=clearInvite&status='.$status,'method'=>'POST','destructive'=>true]],
	]);
}
$kw = trim((string)($_GET['kw'] ?? ''));
$rows = [];
$sql = $kw === '' ? "SELECT id,code,status,addtime,usetime,uid FROM pre_invitecode ORDER BY id DESC LIMIT 500" : "SELECT id,code,status,addtime,usetime,uid FROM pre_invitecode WHERE code=:kw ORDER BY id DESC LIMIT 500";
$params = $kw === '' ? [] : [':kw'=>$kw];
foreach($DB->getAll($sql, $params) as $row) $rows[] = ['id'=>(string)$row['id'],'code'=>(string)$row['code'],'status'=>(string)$row['status'],'addtime'=>(string)$row['addtime'],'usetime'=>(string)$row['usetime'],'uid'=>(string)$row['uid']];
epay_admin_view('admin-resource', [
	'resource'=>'invitecodes','title'=>'邀请码管理','description'=>'查询、生成和清理注册邀请码。','rows'=>$rows,
	'headerActions'=>[['label'=>'生成邀请码','href'=>'./invitecode.php?my=add'],['label'=>'清空全部','href'=>'./invitecode.php?my=qk'],['label'=>'清空已使用','href'=>'./invitecode.php?my=qkuse']],
]);
