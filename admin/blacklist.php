<?php
/** 支付黑名单（原生 shadcn 列表与表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
if((string)($_GET['my'] ?? '') === 'add'){
	epay_admin_view('admin-form', ['title'=>'新增黑名单','description'=>'拦截指定支付账号或 IP 地址，可设置自动到期时间。','action'=>['endpoint'=>'ajax_user.php?act=addBlack','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>true,'submitLabel'=>'添加黑名单'],'fields'=>[['key'=>'type','label'=>'类型','type'=>'select','value'=>'0','options'=>[['value'=>'0','label'=>'支付账号'],['value'=>'1','label'=>'IP 地址']]],['key'=>'content','label'=>'内容','required'=>true],['key'=>'days','label'=>'有效天数','type'=>'number','value'=>'0','placeholder'=>'0 表示永久'],['key'=>'remark','label'=>'备注','type'=>'textarea']], 'links'=>[['label'=>'返回黑名单','href'=>'./blacklist.php']]]);
}
epay_admin_view('admin-resource', ['resource'=>'blacklist','title'=>'支付黑名单','description'=>'管理支付账号和 IP 黑名单。','headerActions'=>[['label'=>'新增黑名单','href'=>'./blacklist.php?my=add']]]);
