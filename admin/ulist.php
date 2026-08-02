<?php
/**
 * 商户列表（原生 shadcn 管理视图）
 */
include("../includes/common.php");
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
$title = '商户列表';
$epay_ui_view = 'admin-resource';
$epay_ui_config = [
	'resource' => 'users',
	'title' => $title,
	'description' => '查询商户账户、余额、用户组和最近登录信息。',
	'csrf_token' => $_SESSION['admin_csrf_token'],
	'headerActions' => [['label'=>'新增商户','href'=>'./uset.php?my=add']],
];
include './head.php';
exit('</body></html>');
