<?php
/** TOTP 二次验证配置（原生 shadcn 表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';

if(isset($_POST['action'])){
	if(!$islogin) exit(json_encode(['code'=>-1, 'msg'=>'未登录']));
	if(!isset($_POST['csrf_token']) || !isset($_SESSION['admin_csrf_token']) || !hash_equals((string)$_SESSION['admin_csrf_token'], (string)$_POST['csrf_token'])) exit(json_encode(['code'=>-403, 'msg'=>'CSRF验证失败'], JSON_UNESCAPED_UNICODE));
	$action = (string)$_POST['action'];
	if($action === 'generate'){
		try{
			$totp = \lib\TOTP::create();
			$totp->setLabel($conf['admin_user']);
			$totp->setIssuer($conf['sitename']);
			echojson(['code'=>0, 'data'=>['secret'=>$totp->getSecret(), 'qrcode'=>$totp->getProvisioningUri()]]);
		}catch(Exception $e){ echojsonmsg($e->getMessage()); }
	}
	if($action === 'bind'){
		$secret = trim((string)($_POST['secret'] ?? ''));
		$code = trim((string)($_POST['code'] ?? ''));
		if($secret === '' || $code === '') echojsonmsg('参数不完整');
		try{
			$totp = \lib\TOTP::create($secret);
			if(!$totp->verify($code)) echojsonmsg('动态口令错误');
		}catch(Exception $e){ echojsonmsg($e->getMessage()); }
		saveSetting('totp_open', 1); saveSetting('totp_secret', $secret); $CACHE->clear();
		echojson(['code'=>0, 'msg'=>'TOTP绑定成功']);
	}
	if($action === 'close'){
		$password = trim((string)($_POST['password'] ?? ''));
		if($password === '' || $password !== $conf['admin_pwd']) echojsonmsg('当前管理员密码错误');
		saveSetting('totp_open', 0); saveSetting('totp_secret', ''); $CACHE->clear();
		echojson(['code'=>0, 'msg'=>'TOTP已关闭']);
	}
	echojsonmsg('参数错误');
}

epay_admin_view('admin-totp', ['title'=>'TOTP 二次验证','description'=>'开启后，管理员登录需要使用支持 TOTP 的认证器完成二次验证。','enabled'=>(int)($conf['totp_open'] ?? 0) === 1]);
