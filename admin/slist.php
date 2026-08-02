<?php
/**
 * 结算记录（原生 shadcn 管理视图）
 */
include("../includes/common.php");
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
$title = '结算管理';
$epay_ui_view = 'admin-resource';
$epay_ui_config = [
	'resource' => 'settles',
	'title' => $title,
	'description' => '查看商户结算申请、状态和收款账户。',
	'csrf_token' => $_SESSION['admin_csrf_token'],
	'headerActions' => [['label'=>'批量结算与导出','href'=>'./settle.php']],
];
include './head.php';
exit('</body></html>');
