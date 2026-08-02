<?php
/**
 * 资金明细（原生 shadcn 管理视图）
 */
include("../includes/common.php");
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
$title = '资金明细';
$epay_ui_view = 'admin-resource';
$epay_ui_config = [
	'resource' => 'records',
	'title' => $title,
	'description' => '查看商户余额变动、订单关联和资金方向。',
	'csrf_token' => $_SESSION['admin_csrf_token'],
	'headerActions' => [['label'=>'导出明细','href'=>'./record_export.php']],
];
include './head.php';
exit('</body></html>');
