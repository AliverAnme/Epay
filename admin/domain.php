<?php
/** 授权支付域名（原生 shadcn 列表与表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
if((string)($_GET['my'] ?? '') === 'add'){
	epay_admin_view('admin-form', ['title'=>'新增授权域名','description'=>'为指定商户添加可用于支付的域名。','action'=>['endpoint'=>'ajax_user.php?act=addDomain','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>true,'submitLabel'=>'添加域名'],'fields'=>[['key'=>'uid','label'=>'商户 ID','type'=>'number','required'=>true],['key'=>'domain','label'=>'域名','required'=>true,'placeholder'=>'example.com']], 'links'=>[['label'=>'返回域名列表','href'=>'./domain.php']]]);
}
epay_admin_view('admin-resource', ['resource'=>'domains','title'=>'授权域名','description'=>'审核商户支付域名并维护授权状态。','headerActions'=>[['label'=>'新增授权域名','href'=>'./domain.php?my=add']]]);
