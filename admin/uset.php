<?php
/** 商户信息（原生 shadcn 表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$my = (string)($_GET['my'] ?? 'add');
$uid = intval($_GET['uid'] ?? 0);
$groups = [];
foreach($DB->getAll("SELECT gid,name FROM pre_group ORDER BY gid ASC") as $row) $groups[] = ['value'=>(string)$row['gid'],'label'=>$row['gid'].' - '.$row['name']];
$settles = [];
if(!empty($conf['settle_alipay'])) $settles[] = ['value'=>'1','label'=>'支付宝'];
if(!empty($conf['settle_wxpay'])) $settles[] = ['value'=>'2','label'=>'微信'];
if(!empty($conf['settle_qqpay'])) $settles[] = ['value'=>'3','label'=>'QQ 钱包'];
if(!empty($conf['settle_bank'])) $settles[] = ['value'=>'4','label'=>'银行卡'];
$edit = $my === 'edit';
$row = $edit ? $DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]) : [];
if($edit && !$row) epay_admin_view('admin-form', ['title'=>'商户不存在','description'=>'找不到指定商户。','links'=>[['label'=>'返回商户列表','href'=>'./ulist.php']]]);
$fields = [
	['key'=>'phone','label'=>'手机号','value'=>$row['phone'] ?? '','placeholder'=>'可与邮箱二选一'], ['key'=>'email','label'=>'邮箱','value'=>$row['email'] ?? '','placeholder'=>'可与手机号二选一'], ['key'=>'pwd','label'=>'登录密码','type'=>'password','placeholder'=>$edit ? '留空表示不修改' : '可留空，使用密钥登录'],
	['key'=>'gid','label'=>'用户组','type'=>'select','value'=>(string)($row['gid'] ?? 0),'options'=>$groups], ['key'=>'qq','label'=>'QQ','value'=>$row['qq'] ?? ''], ['key'=>'url','label'=>'网站域名','value'=>$row['url'] ?? ''],
	['key'=>'settle_id','label'=>'结算方式','type'=>'select','value'=>(string)($row['settle_id'] ?? ($settles[0]['value'] ?? 0)),'options'=>$settles], ['key'=>'account','label'=>'结算账号','value'=>$row['account'] ?? ''], ['key'=>'username','label'=>'结算账号姓名','value'=>$row['username'] ?? ''],
	['key'=>'mode','label'=>'手续费模式','type'=>'select','value'=>(string)($row['mode'] ?? 0),'options'=>[['value'=>'0','label'=>'余额扣费'],['value'=>'1','label'=>'订单加费']]], ['key'=>'status','label'=>'商户状态','type'=>'select','value'=>(string)($row['status'] ?? 1),'options'=>[['value'=>'1','label'=>'正常'],['value'=>'0','label'=>'封禁'],['value'=>'2','label'=>'待审核']]],
	['key'=>'pay','label'=>'收款权限','type'=>'select','value'=>(string)($row['pay'] ?? 1),'options'=>[['value'=>'1','label'=>'开启'],['value'=>'0','label'=>'关闭']]], ['key'=>'settle','label'=>'结算权限','type'=>'select','value'=>(string)($row['settle'] ?? 1),'options'=>[['value'=>'1','label'=>'开启'],['value'=>'0','label'=>'关闭']]],
];
if($edit){
	$fields = array_merge($fields, [['key'=>'money','label'=>'余额','value'=>$row['money'] ?? '0.00'],['key'=>'cert','label'=>'实名认证','type'=>'select','value'=>(string)($row['cert'] ?? 0),'options'=>[['value'=>'0','label'=>'未认证'],['value'=>'1','label'=>'已认证']]],['key'=>'certtype','label'=>'认证类型','type'=>'select','value'=>(string)($row['certtype'] ?? 0),'options'=>[['value'=>'0','label'=>'个人'],['value'=>'1','label'=>'企业']]],['key'=>'certno','label'=>'证件号码','value'=>$row['certno'] ?? ''],['key'=>'certname','label'=>'认证姓名','value'=>$row['certname'] ?? ''],['key'=>'ordername','label'=>'订单名称模板','value'=>$row['ordername'] ?? ''],['key'=>'open_code','label'=>'开放代码','type'=>'number','value'=>(string)($row['open_code'] ?? 0)],['key'=>'remain_money','label'=>'余额保留金额','value'=>$row['remain_money'] ?? ''],['key'=>'deposit','label'=>'保证金','value'=>$row['deposit'] ?? '']]);
}
epay_admin_view('admin-form', [
	'title'=>$edit ? '编辑商户 UID:'.$uid : '新增商户', 'description'=>'维护登录信息、用户组、余额权限和结算资料。',
	'action'=>['endpoint'=>$edit ? 'ajax_user.php?act=editUser&uid='.$uid : 'ajax_user.php?act=addUser','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>false,'submitLabel'=>$edit ? '保存商户' : '创建商户'],
	'fields'=>$fields,
	'links'=>[['label'=>'返回商户列表','href'=>'./ulist.php'], ...($edit ? [['label'=>'订单','href'=>'./order.php?uid='.$uid],['label'=>'资金明细','href'=>'./record.php?uid='.$uid]] : [])],
]);
