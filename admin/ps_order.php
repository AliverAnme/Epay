<?php
/** 分账记录（原生 shadcn 列表） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
epay_admin_view('admin-resource', [
	'resource'=>'ps-orders','title'=>'分账记录','description'=>'查询分账订单、状态和分账金额。',
]);
