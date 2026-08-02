<?php
/**
 * 登录日志（原生 shadcn 管理视图）
 */
include("../includes/common.php");
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
$title = '登录日志';
$epay_ui_view = 'admin-resource';
$epay_ui_config = [
	'resource' => 'logs',
	'title' => $title,
	'description' => '审计管理员和商户的登录、鉴权及安全事件。',
	'csrf_token' => $_SESSION['admin_csrf_token'],
];
include './head.php';
exit('</body></html>');
