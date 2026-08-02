<?php
/**
 * 风控记录（原生 shadcn 管理视图）
 */
include("../includes/common.php");
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
$title = '风控记录';
$epay_ui_view = 'admin-resource';
$epay_ui_config = [
	'resource' => 'risks',
	'title' => $title,
	'description' => '查看风险命中记录和处理状态。',
	'csrf_token' => $_SESSION['admin_csrf_token'],
];
include './head.php';
exit('</body></html>');
