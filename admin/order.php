<?php
/**
 * 订单列表
 *
 * 订单管理页由 React/shadcn 渲染，数据与操作仍复用现有受保护的 ajax_order.php 接口。
 */
include("../includes/common.php");
$title = '订单列表';
if($islogin != 1) exit("<script>window.location.href='./login.php';</script>");

$epay_ui_view = 'admin-order';
$payment_types = $DB->getAll("SELECT id,name,showname FROM pre_type ORDER BY id ASC");
$epay_ui_config = [
	'paymentTypes' => array_map(function($row){
		return [
			'id' => (int)$row['id'],
			'name' => (string)$row['name'],
			'showname' => (string)$row['showname'],
		];
	}, $payment_types),
	'csrf_token' => $_SESSION['admin_csrf_token'],
];
include './head.php';
exit('</body></html>');
