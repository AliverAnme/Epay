<?php
/** 新增 / 编辑用户组（原生 shadcn 表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$act = (string)($_GET['act'] ?? '');
$gid = intval($_GET['gid'] ?? 0);
$group = $act === 'edit' ? $DB->getRow("SELECT * FROM pre_group WHERE gid=:gid LIMIT 1", [':gid'=>$gid]) : [];
if($act === 'edit' && !$group) epay_admin_view('admin-form', ['title'=>'用户组不存在','description'=>'找不到指定用户组。','links'=>[['label'=>'返回用户组','href'=>'./glist.php']]]);
$info = $group && !empty($group['info']) ? (json_decode($group['info'], true) ?: []) : [];
$gconfig = $group && !empty($group['config']) ? (json_decode($group['config'], true) ?: []) : [];
$types = $DB->getAll("SELECT id,showname FROM pre_type WHERE status=1 ORDER BY id ASC");
$fields = [
	['key'=>'action','type'=>'hidden','value'=>$act === 'edit' ? 'edit' : 'add'], ['key'=>'gid','type'=>'hidden','value'=>(string)$gid],
	['key'=>'name','label'=>'显示名称','value'=>$group['name'] ?? '','required'=>true,'placeholder'=>'不要与其他用户组名称重复'],
];
foreach($types as $type){
	$key = (string)$type['id']; $current = $info[$key] ?? [];
	$options = [['value'=>'0','label'=>'关闭'],['value'=>'-1','label'=>'随机可用通道'],['value'=>'-4','label'=>'顺序可用通道'],['value'=>'-5','label'=>'首个可用通道']];
	foreach($DB->getAll("SELECT id,name FROM pre_channel WHERE type=:type AND status=1 ORDER BY id ASC", [':type'=>$type['id']]) as $row) $options[] = ['value'=>(string)$row['id'],'label'=>'通道 '.$row['id'].' - '.$row['name']];
	$options[] = ['value'=>'-2','label'=>'用户自定义子通道']; $options[] = ['value'=>'-3','label'=>'随机可用轮询组'];
	$fields[] = ['key'=>'info['.$key.'][type]','type'=>'hidden','value'=>(string)($current['type'] ?? '')];
	$fields[] = ['key'=>'info['.$key.'][channel]','label'=>$type['showname'].' 通道','type'=>'select','value'=>(string)($current['channel'] ?? 0),'options'=>$options];
	$fields[] = ['key'=>'info['.$key.'][rate]','label'=>$type['showname'].' 分成比例','value'=>(string)($current['rate'] ?? ''),'placeholder'=>'百分数，可留空'];
}
$configKeys = ['transfer_alipay','transfer_wxpay','transfer_qqpay','transfer_bank','settle_open','settle_type','settle_transfer','settle_money','settle_rate','settle_alipay','settle_wxpay','settle_qqpay','settle_bank','recharge','onecode','user_transfer','user_transfer_red','transfer_rate','alipay_satf','voicenotice','orderprint','invite_open','invite_rate','user_deposit','user_deposit_min','user_deposit_day','pay_domain_forbid','direct_settle_time','alipay_getmobile','pay_payaddstart','pay_payaddmin','pay_payaddmax','pay_maxmoney','pay_minmoney','pay_daymax','check_pay_regoin','pay_verify','ordername','complain_open','complain_freeze_order','complain_auto_refund','applyments_open','user_profitsharing','applyments_free','wxcombine_open','alicombine_open'];
foreach($configKeys as $key) $fields[] = ['key'=>'config['.$key.']','label'=>'配置 '.$key,'value'=>(string)($gconfig[$key] ?? ''),'placeholder'=>'留空则沿用系统设置'];
$fields[] = ['key'=>'settings','label'=>'用户变量映射','value'=>$group['settings'] ?? '','placeholder'=>'变量名1:显示名1,变量名2:显示名2'];
epay_admin_view('admin-form', [
	'title'=>$act === 'edit' ? '编辑用户组 GID:'.$gid : '新增用户组',
	'description'=>'为用户组配置支付通道、费率、结算和转账策略。',
	'action'=>['endpoint'=>'ajax_user.php?act=saveGroup','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>true,'submitLabel'=>'保存用户组'],
	'values'=>array_merge(['action'=>$act === 'edit' ? 'edit' : 'add','gid'=>(string)$gid,'name'=>$group['name'] ?? '','settings'=>$group['settings'] ?? ''], array_reduce($fields, function($carry, $field){ if(isset($field['value'])) $carry[$field['key']] = $field['value']; return $carry; }, [])),
	'fields'=>$fields,
	'links'=>[['label'=>'返回用户组','href'=>'./glist.php']],
]);
