<?php
/**
 * 用户组设置（原生 shadcn 管理视图）
 */
include("../includes/common.php");
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
$title = '用户组设置';
$payment_types = [];
foreach($DB->getAll("SELECT id,showname FROM pre_type WHERE status=1 ORDER BY id ASC") as $type){
	$payment_types[$type['id']] = $type['showname'];
}
$groups = [];
foreach($DB->getAll("SELECT gid,name,info,isbuy FROM pre_group ORDER BY gid ASC") as $group){
	$info = json_decode($group['info'], true);
	$items = [];
	if(is_array($info)) foreach($info as $type_id=>$item){
		if(!is_array($item) || empty($item['channel'])) continue;
		$items[] = ($payment_types[$type_id] ?? $type_id).'('.$item['channel'].'):'.$item['rate'];
	}
	$group['infoText'] = implode(', ', $items);
	unset($group['info']);
	$groups[] = $group;
}
$epay_ui_view = 'admin-resource';
$epay_ui_config = [
	'resource' => 'groups',
	'title' => $title,
	'description' => '查看用户组、费率策略和上架状态。',
	'csrf_token' => $_SESSION['admin_csrf_token'],
	'rows' => $groups,
];
include './head.php';
exit('</body></html>');
