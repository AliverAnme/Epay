<?php
/** H5 跳转微信客服支付设置（原生 shadcn 表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';

$accountOptions = [['value'=>'0', 'label'=>'多客服账号轮询']];
foreach($DB->getAll("SELECT A.id,A.openkfid,A.name FROM pre_wxkfaccount A LEFT JOIN pre_wework B ON A.wid=B.id WHERE B.status=1") as $row){
	$accountOptions[] = ['value'=>(string)$row['id'], 'label'=>$row['openkfid'].' - '.$row['name']];
}
$appletOptions = [['value'=>'0', 'label'=>'关闭']];
foreach($DB->getAll("SELECT id,name FROM pre_weixin WHERE type=1") as $row) $appletOptions[] = ['value'=>(string)$row['id'], 'label'=>(string)$row['name']];

epay_admin_view('admin-form', [
	'title'=>'H5 跳转微信客服支付设置',
	'description'=>'配置企业微信客服与微信小程序客服支付回调。保存后请使用测试入口确认回调可达。',
	'action'=>['endpoint'=>'ajax.php?act=set', 'method'=>'POST', 'submitMode'=>'fetch', 'reloadOnSuccess'=>true, 'submitLabel'=>'保存全部配置'],
	'sections'=>[
		['title'=>'企业微信客服支付', 'description'=>'回调地址：'.$siteurl.'wework.php', 'fields'=>[
			['key'=>'wework_token','label'=>'Token','value'=>$conf['wework_token'] ?? ''],
			['key'=>'wework_aeskey','label'=>'EncodingAESKey','value'=>$conf['wework_aeskey'] ?? ''],
			['key'=>'wework_payopen','label'=>'开启企业微信客服支付','type'=>'select','value'=>(string)($conf['wework_payopen'] ?? 0),'options'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']]],
			['key'=>'wework_paymsgmode','label'=>'消息模式','type'=>'select','value'=>(string)($conf['wework_paymsgmode'] ?? 0),'options'=>[['value'=>'0','label'=>'回复后发送支付链接'],['value'=>'1','label'=>'直接发送支付链接']]],
			['key'=>'wework_paykfid','label'=>'客服账号','type'=>'select','value'=>(string)($conf['wework_paykfid'] ?? 0),'options'=>$accountOptions],
			['key'=>'wework_contact','label'=>'人工客服链接','value'=>$conf['wework_contact'] ?? '', 'placeholder'=>'可留空'],
			['key'=>'wework_remark','label'=>'消息尾部附加内容','value'=>$conf['wework_remark'] ?? '', 'placeholder'=>'支持变量 [qq]'],
		]],
		['title'=>'微信小程序客服支付', 'description'=>'回调地址：'.$siteurl.'paypage/wxapplet.php', 'fields'=>[
			['key'=>'wxappkf_token','label'=>'Token','value'=>$conf['wxappkf_token'] ?? getSid()],
			['key'=>'wxappkf_aeskey','label'=>'EncodingAESKey','value'=>$conf['wxappkf_aeskey'] ?? ''],
			['key'=>'wxappkf_applet','label'=>'微信小程序','type'=>'select','value'=>(string)($conf['wxappkf_applet'] ?? 0),'options'=>$appletOptions],
			['key'=>'wxappkf_payopen','label'=>'开启小程序客服支付','type'=>'select','value'=>(string)($conf['wxappkf_payopen'] ?? 0),'options'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']]],
		]],
	],
	'links'=>[['label'=>'企业微信账号列表','href'=>'./pay_wework.php']],
]);
