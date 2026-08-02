<?php
/** 平台管理首页（原生 shadcn 仪表盘） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
epay_admin_view('admin-dashboard', ['title'=>'支付管理中心','description'=>'订单、商户、结算和通道运营概览。']);
