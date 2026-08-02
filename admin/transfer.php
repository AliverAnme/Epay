<?php
/**
 * 付款记录（原生 shadcn 管理视图）
 */
include("../includes/common.php");
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
$title = '付款记录';
$epay_ui_view = 'admin-resource';
$epay_ui_config = [
	'resource' => 'transfers',
	'title' => $title,
	'description' => '查看平台代付、红包和转账处理状态。',
	'csrf_token' => $_SESSION['admin_csrf_token'],
	'headerActions' => [['label'=>'新增付款','href'=>'./transfer_add.php'],['label'=>'创建红包','href'=>'./transfer_red.php'],['label'=>'导出记录','href'=>'./transfer_export.php']],
];
include './head.php';
exit('</body></html>');
