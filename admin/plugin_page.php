<?php
/** 支付插件配置入口（原生 shadcn 表单外壳） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$channelid = intval($_GET['channel'] ?? 0);
$func = (string)($_GET['func'] ?? '');
$channel = $channelid ? \lib\Channel::get($channelid) : null;
if(!$channel || $func === '') epay_admin_view('admin-form', ['title'=>'插件参数错误','description'=>'缺少有效的支付通道或插件动作。','links'=>[['label'=>'返回支付通道','href'=>'./pay_channel.php']]]);
$plugin = (string)$channel['plugin'];
$csrfOk = !isset($_POST['csrf_token']) || $_POST['csrf_token'] === $_SESSION['admin_csrf_token'];
if($_SERVER['REQUEST_METHOD'] === 'POST' && !$csrfOk) epay_admin_view('admin-form', ['title'=>'插件配置失败','description'=>'CSRF 校验失败，请刷新后重试。','notice'=>'CSRF 校验失败。']);
if($_SERVER['REQUEST_METHOD'] === 'POST'){
	if(!defined('EPAY_PLUGIN_JSON')) define('EPAY_PLUGIN_JSON', true);
	try{ \lib\Plugin::loadForAdmin($func); }catch(Exception $e){ epay_admin_view('admin-form', ['title'=>'插件配置失败','description'=>$e->getMessage(),'links'=>[['label'=>'返回支付通道','href'=>'./pay_channel.php?id='.$channelid]]]); }
	exit;
}
$fields = [];
$description = '插件 '.$plugin.' 的管理配置。';
if($func === 'wxconfig' && $plugin === 'fubei'){
	$fields = [['key'=>'sub_appid','label'=>'公众号 AppID','required'=>true],['key'=>'jsapi_path','label'=>'JSAPI 支付授权目录','value'=>$siteurl,'required'=>true]];
	$description = '付呗微信参数配置：绑定公众号 AppID 与 JSAPI 支付授权目录。';
}elseif($func === 'wxconfig' && $plugin === 'shengpay'){
	$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
	$fields = [['key'=>'conf_type','label'=>'配置类型','type'=>'select','value'=>'1','options'=>[['value'=>'1','label'=>'支付授权目录'],['value'=>'2','label'=>'绑定公众号 AppID']]],['key'=>'appid','label'=>'当前公众号 AppID','value'=>$wxinfo['appid'] ?? ''],['key'=>'conf_value','label'=>'配置值','required'=>true]];
	$description = '盛付通微信参数绑定。';
}else{
	$description = '该插件动作由插件提供专属逻辑。请返回通道列表，通过插件输入项完成基础密钥配置。';
}
if(!$fields) epay_admin_view('admin-form', ['title'=>'插件配置','description'=>$description,'notice'=>'当前插件暂无可被统一表单渲染的配置动作。','links'=>[['label'=>'返回支付通道','href'=>'./pay_channel.php']]]);
epay_admin_view('admin-form', ['title'=>'插件配置：'.$plugin,'description'=>$description,'action'=>['endpoint'=>'./plugin_page.php?channel='.$channelid.'&func='.$func,'method'=>'POST','submitMode'=>'fetch','submitLabel'=>'提交插件配置'],'fields'=>$fields,'links'=>[['label'=>'返回支付通道','href'=>'./pay_channel.php?id='.$channelid]]]);
