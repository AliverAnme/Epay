<?php
/** 网站公告（原生 shadcn 列表与表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$my = isset($_GET['my']) ? (string)$_GET['my'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['admin_csrf_token']) epay_admin_view('admin-form', ['title'=>'公告保存失败','description'=>'CSRF 校验失败，请刷新后重试。','notice'=>'CSRF 校验失败。']);
	$content = trim((string)($_POST['content'] ?? ''));
	$sort = intval($_POST['sort'] ?? 0);
	$color = trim((string)($_POST['color'] ?? ''));
	if($content === '' || $sort <= 0) epay_admin_view('admin-form', ['title'=>'公告保存失败','description'=>'公告内容和排序不能为空。','notice'=>'请填写公告内容和大于 0 的排序值。','fields'=>[['key'=>'content','label'=>'公告内容','type'=>'textarea','value'=>$content,'required'=>true],['key'=>'sort','label'=>'排序','type'=>'number','value'=>(string)$sort,'required'=>true],['key'=>'color','label'=>'文字颜色','value'=>$color]],'action'=>['endpoint'=>'./gonggao.php?my='.($my==='edit_submit' ? 'edit_submit&id='.$id : 'add_submit'),'method'=>'POST']]);
	if($my === 'edit_submit'){
		$ok = $DB->exec("UPDATE `pre_anounce` SET `content`=:content,`sort`=:sort,`color`=:color WHERE `id`=:id", [':content'=>$content, ':sort'=>$sort, ':color'=>$color, ':id'=>$id]);
		if($ok === false) epay_admin_view('admin-form', ['title'=>'公告保存失败','description'=>'数据库写入失败。','notice'=>$DB->error()]);
	}else{
		$ok = $DB->exec("INSERT INTO `pre_anounce` (`content`,`color`,`sort`,`addtime`,`status`) VALUES (:content,:color,:sort,:addtime,1)", [':content'=>$content, ':color'=>$color, ':sort'=>$sort, ':addtime'=>$date]);
		if(!$ok) epay_admin_view('admin-form', ['title'=>'公告保存失败','description'=>'数据库写入失败。','notice'=>$DB->error()]);
	}
	header('Location: ./gonggao.php');
	exit;
}

if($my === 'edit'){
	$row = $DB->getRow("SELECT * FROM pre_anounce WHERE id=:id LIMIT 1", [':id'=>$id]);
	if(!$row) epay_admin_view('admin-form', ['title'=>'公告不存在','description'=>'找不到对应公告。','links'=>[['label'=>'返回公告列表','href'=>'./gonggao.php']]]);
	epay_admin_view('admin-form', [
		'title'=>'编辑公告', 'description'=>'修改公告内容、排序和文字颜色。',
		'action'=>['endpoint'=>'./gonggao.php?my=edit_submit&id='.$id, 'method'=>'POST', 'submitLabel'=>'保存公告'],
		'links'=>[['label'=>'返回公告列表','href'=>'./gonggao.php']],
		'values'=>['content'=>$row['content'], 'sort'=>$row['sort'], 'color'=>$row['color']],
		'fields'=>[
			['key'=>'content','label'=>'公告内容','type'=>'textarea','required'=>true],
			['key'=>'sort','label'=>'排序','type'=>'number','required'=>true],
			['key'=>'color','label'=>'文字颜色','placeholder'=>'例如 #111111'],
		],
	], '编辑公告');
}

if($my === 'add'){
	epay_admin_view('admin-form', [
		'title'=>'新增公告', 'description'=>'发布新的平台公告。',
		'action'=>['endpoint'=>'./gonggao.php?my=add_submit', 'method'=>'POST', 'submitLabel'=>'发布公告'],
		'links'=>[['label'=>'返回公告列表','href'=>'./gonggao.php']],
		'values'=>['sort'=>'50'],
		'fields'=>[
			['key'=>'content','label'=>'公告内容','type'=>'textarea','required'=>true],
			['key'=>'sort','label'=>'排序','type'=>'number','value'=>'50','required'=>true],
			['key'=>'color','label'=>'文字颜色','placeholder'=>'例如 #111111'],
		],
	], '新增公告');
}

$rows = [];
foreach($DB->getAll("SELECT id,content,color,sort,addtime,status FROM pre_anounce ORDER BY sort ASC,id DESC") as $row){
	$rows[] = [
		'id'=>(string)$row['id'], 'content'=>(string)$row['content'], 'color'=>(string)$row['color'],
		'sort'=>(string)$row['sort'], 'addtime'=>(string)$row['addtime'], 'status'=>(string)$row['status'],
	];
}
epay_admin_view('admin-resource', [
	'resource'=>'announcements', 'title'=>'网站公告',
	'description'=>'发布、排序和控制首页公告显示状态。', 'rows'=>$rows,
	'headerActions'=>[['label'=>'新增公告','href'=>'./gonggao.php?my=add']],
]);
