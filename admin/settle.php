<?php
/**
 * 批量结算（原生 shadcn 管理视图）
 */
include("../includes/common.php");
if($islogin!=1) exit("<script>window.location.href='./login.php';</script>");
$title = '批量结算';
$batches = $DB->getAll("SELECT batch,allmoney,count,time,status FROM pre_batch ORDER BY time DESC");
$epay_ui_view = 'admin-batches';
$epay_ui_config = [
	'title' => $title,
	'description' => '生成、导出并处理结算批次。',
	'csrf_token' => $_SESSION['admin_csrf_token'],
	'rows' => $batches,
];
include './head.php';
exit('</body></html>');
