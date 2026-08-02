<?php
/** 系统设置（原生 shadcn 配置表单） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$mod = (string)($_GET['mod'] ?? 'site');
$allowed = ['site','pay','risk','settle','transfer','oauth','notice','certificate','template','gonggao','mail','iptype','cron','proxy','account','upimg'];
if(!in_array($mod, $allowed, true) && !in_array($mod, ['account_n','paypwd_n'], true)) $mod = 'site';

if($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($mod, ['account_n','paypwd_n'], true)){
	header('Content-Type: application/json; charset=utf-8');
	$respond = function($code, $msg){ exit(json_encode(['code'=>$code,'msg'=>$msg], JSON_UNESCAPED_UNICODE)); };
	if(!isset($_POST['csrf_token']) || !isset($_SESSION['admin_csrf_token']) || !hash_equals((string)$_SESSION['admin_csrf_token'], (string)$_POST['csrf_token'])) $respond(-403, 'CSRF 校验失败，请刷新页面后重试。');
	if($mod === 'account_n'){
		$user = trim((string)($_POST['user'] ?? '')); $old = trim((string)($_POST['oldpwd'] ?? '')); $new = trim((string)($_POST['newpwd'] ?? '')); $new2 = trim((string)($_POST['newpwd2'] ?? ''));
		if($user === '' || $old !== (string)$conf['admin_pwd'] || $new === '' || $new !== $new2 || strlen($new) < 6) $respond(-1, '用户名、旧密码或新密码校验未通过。');
		saveSetting('admin_user', $user); saveSetting('admin_pwd', getMd5Pwd($new)); $CACHE->clear(); $respond(0, '登录账号和密码已更新。');
	}
	$old = trim((string)($_POST['oldpwd'] ?? '')); $new = trim((string)($_POST['newpwd'] ?? '')); $new2 = trim((string)($_POST['newpwd2'] ?? ''));
	if($old !== (string)$conf['admin_paypwd'] || $new === '' || $new !== $new2 || strlen($new) < 6) $respond(-1, '旧支付密码或新密码校验未通过。');
	saveSetting('admin_paypwd', $new); $CACHE->clear(); $respond(0, '支付密码已更新。');
}

$labels = [
	'sitename'=>'网站名称','title'=>'首页标题','keywords'=>'关键字','description'=>'网站描述','orgname'=>'公司/组织名称','localurl'=>'回调专用网址','apiurl'=>'用户对接网址','email'=>'联系邮箱','kfqq'=>'客服 QQ','qqqun'=>'加群链接','appurl'=>'APP 下载链接','verifytype'=>'用户验证方式','reg_open'=>'开放注册','reg_input_settle'=>'注册后可不填结算账户','user_review'=>'开启注册审核','reg_pay'=>'注册付费','reg_pay_price'=>'注册付费金额','user_settings_edit'=>'用户编辑接口信息','test_open'=>'测试支付','test_pay_uid'=>'测试支付收款商户 ID','captcha_id'=>'极验验证码 ID','captcha_key'=>'极验验证码密钥','captcha_version'=>'极验版本','captcha_open_login'=>'登录验证码','close_keylogin'=>'密钥登录','user_style'=>'用户中心风格','cdnpublic'=>'公共静态资源 CDN','homepage'=>'首页显示模式','homepage_url'=>'显示网址 URL',
	'pay_open'=>'开启支付','pay_verify'=>'支付验证','pay_minmoney'=>'最小支付金额','pay_maxmoney'=>'最大支付金额','pay_daymoney'=>'每日金额上限','pay_domain_open'=>'开放域名','pay_domain_forbid'=>'禁止未授权域名','pay_iplimit'=>'支付 IP 限制','pay_userlimit'=>'支付用户限制','ordername'=>'订单名称模板','notifyordername'=>'通知订单名称',
	'settle_open'=>'开启结算','settle_type'=>'结算方式','settle_transfer'=>'自动转账','settle_money'=>'最低结算金额','settle_rate'=>'结算费率','settle_fee_min'=>'最低手续费','settle_fee_max'=>'最高手续费','settle_maxlimit'=>'结算限额',
	'transfer_alipay'=>'支付宝付款通道','transfer_wxpay'=>'微信付款通道','transfer_qqpay'=>'QQ 付款通道','transfer_bank'=>'银行卡付款通道','transfer_rate'=>'付款费率','transfer_minmoney'=>'最低付款金额','transfer_maxmoney'=>'最高付款金额','transfer_maxlimit'=>'付款限额','transfer_name'=>'红包名称','transfer_desc'=>'红包备注',
	'login_apiurl'=>'快捷登录 API','login_wx'=>'微信登录公众号','login_wxa'=>'微信登录小程序','login_alipay'=>'支付宝登录通道','login_qq'=>'QQ 登录','login_appid'=>'应用 ID','login_appkey'=>'应用密钥','login_qq_appid'=>'QQ 应用 ID','login_qq_appkey'=>'QQ 应用密钥',
	'wxnotice'=>'微信通知','mailnotice'=>'邮件通知','robotnotice'=>'机器人通知','msgrobot_url'=>'机器人地址','msgrobot_phone'=>'机器人手机号','cronkey'=>'计划任务密钥','ip_type'=>'IP 获取方式','proxy_server'=>'代理服务器','proxy_port'=>'代理端口','proxy_user'=>'代理用户','proxy_pwd'=>'代理密码','proxy_type'=>'代理类型',
	'cert_open'=>'实名认证','cert_force'=>'强制认证','cert_money'=>'认证金额','cert_channel'=>'认证通道','cert_aliyunid'=>'阿里云 ID','cert_aliyunkey'=>'阿里云密钥','cert_qcloudid'=>'腾讯云 ID','cert_qcloudkey'=>'腾讯云密钥','template'=>'首页模板','footer'=>'页脚内容','mail_smtp'=>'SMTP 服务器','mail_port'=>'SMTP 端口','mail_user'=>'SMTP 用户名','mail_pwd'=>'SMTP 密码','mail_name'=>'发件人名称','mail_recv'=>'接收邮箱','sms_api'=>'短信接口','sms_appid'=>'短信应用 ID','sms_appkey'=>'短信应用密钥','account'=>'管理员账户','cron'=>'计划任务配置','proxy'=>'中转代理配置','iptype'=>'IP 获取方式','gonggao'=>'网站公告配置','upimg'=>'首页 LOGO 设置',
];
$modKeys = [
	'site'=>['sitename','title','keywords','description','orgname','localurl','apiurl','email','kfqq','qqqun','appurl','verifytype','reg_open','reg_input_settle','user_review','reg_pay','reg_pay_price','user_settings_edit','test_open','test_pay_uid','captcha_id','captcha_key','captcha_version','captcha_open_login','close_keylogin','user_style','cdnpublic','homepage','homepage_url'],
	'pay'=>['pay_open','pay_verify','pay_minmoney','pay_maxmoney','pay_daymoney','pay_domain_open','pay_domain_forbid','pay_iplimit','pay_userlimit','ordername','notifyordername','pay_payaddstart','pay_payaddmin','pay_payaddmax','check_pay_regoin'],
	'risk'=>['check_channel_failcount','check_channel_second','check_notify_count','check_payip_count','check_payip_second','check_payspeed_count','check_payspeed_days','check_payspeed_second','check_sucrate_count','check_sucrate_second','check_sucrate_value','blockalert','blockname','black_payact'],
	'settle'=>['settle_open','settle_type','settle_transfer','settle_money','settle_rate','settle_fee_min','settle_fee_max','settle_maxlimit','settle_alipay','settle_wxpay','settle_qqpay','settle_bank'],
	'transfer'=>['transfer_alipay','transfer_wxpay','transfer_qqpay','transfer_bank','transfer_rate','transfer_minmoney','transfer_maxmoney','transfer_maxlimit','transfer_name','transfer_desc'],
	'oauth'=>['login_apiurl','login_wx','login_wxa','login_alipay','login_qq','login_appid','login_appkey','login_qq_appid','login_qq_appkey'],
	'notice'=>['wxnotice','mailnotice','robotnotice','msgrobot_url','msgrobot_phone','msgconfig_order','msgconfig_settle','msgconfig_balance','msgconfig_domain','msgconfig_group','msgconfig_risk'],
	'certificate'=>['cert_open','cert_force','cert_money','cert_channel','cert_aliyunid','cert_aliyunkey','cert_qcloudid','cert_qcloudkey'],
	'template'=>['template','homepage','footer','ordername','pageordername'],
	'gonggao'=>['footer','profits_desc','notifyordername'],
	'mail'=>['mail_smtp','mail_port','mail_user','mail_pwd','mail_name','mail_recv','mail_apikey','sms_api','sms_appid','sms_appkey','sms_sign'],
	'iptype'=>['ip_type'], 'cron'=>['cronkey','auto_check_channel','auto_check_notify','auto_check_complain','auto_settle_money'], 'proxy'=>['proxy','proxy_server','proxy_port','proxy_user','proxy_pwd','proxy_type','proxy_apiurl','proxy_apikey'],
];
$modKeys['site'] = array_values(array_unique(array_merge($modKeys['site'], ['captcha_open_test'])));
$modKeys['pay'] = array_values(array_unique(array_merge($modKeys['pay'], ['pay_maxmoney','pay_minmoney','blockname','blockalert','pageordername','forceqq','localurl_alipay','localurl_wxpay','wxminipay_path','alipay_paymode','direct_settle_time','alipay_qrcode_url','wxpay_qrcode_url','black_payact','profits_desc','profits_failretry','api_timestamp_check','alipay_web_login','alipay_web_login_all','alipay_mini_login','alipay_wappaylogin','alipay_qrpaylogin','alipay_getmobile','alipay_aes_key','wxpay_qrpaylogin','wxpay_web_login','recharge','reg_pay_uid','onecode','user_refund','refund_fee_type','payfee_lessthan','payfee_mincost','applyments_open','user_profitsharing','invite_open','invite_order_type','invite_rate','invite_order_fee','invite_mode','invite_groupbuy_rate','invite_apply_rate','mchrisk_open','user_deposit','user_deposit_min','user_deposit_day','complain_range','complain_open','complain_freeze_order','complain_auto_reply','complain_auto_reply_repeat','complain_auto_reply_con','complain_auto_black','complain_auto_ipblack','complain_auto_refund','complain_auto_refund_money','wxcombine_open','alicombine_open','wxcombine_minmoney','wxcombine_submoney'])));
$modKeys['risk'] = array_values(array_unique(array_merge($modKeys['risk'], ['pay_iplimit','pay_iplimit_white','pay_region_block','pay_verify_check_second','pay_verify_check_count','pay_verify_check_rate','pay_verify_check_ip','pay_verify_check_uid','pay_verify_type','auto_check_channel','check_channel_ids','check_channel_notice','auto_check_sucrate','check_sucrate_notice','auto_check_notify','check_notify_notice','auto_check_complain','check_complain_rate','check_complain_notice','auto_check_payip','auto_check_payspeed','check_paymsg','check_paymsg_notice','check_paymsg_retry','alipay_settle_check','alipay_settle_notice'])));
$modKeys['settle'] = array_values(array_unique(array_merge($modKeys['settle'], ['settle_transfermax','auto_settle_money'])));
$modKeys['transfer'] = array_values(array_unique(array_merge($modKeys['transfer'], ['transfer_alipay_scene_name','transfer_alipay_info_type','transfer_alipay_info_content','alipay_satf','alipay_satf_fee_type','alipay_satf_fee_account','transfer_wxpay_type','transfer_wxpay_scene_id','transfer_wxpay_info_type','transfer_wxpay_info_content','user_transfer','user_transfer_red'])));
$modKeys['oauth'] = array_values(array_unique(array_merge($modKeys['oauth'], ['wx_open_url'])));
$modKeys['notice'] = array_values(array_unique(array_merge($modKeys['notice'], ['wxnotice_tpl_order','wxnotice_tpl_order_no','wxnotice_tpl_order_name','wxnotice_tpl_order_money','wxnotice_tpl_order_time','wxnotice_tpl_order_outno','wxnotice_tpl_settle','wxnotice_tpl_settle_type','wxnotice_tpl_settle_account','wxnotice_tpl_settle_money','wxnotice_tpl_settle_realmoney','wxnotice_tpl_settle_time','wxnotice_tpl_login','wxnotice_tpl_login_user','wxnotice_tpl_login_time','wxnotice_tpl_login_name','wxnotice_tpl_login_ip','wxnotice_tpl_login_iploc','wxnotice_tpl_complain','wxnotice_tpl_complain_order_no','wxnotice_tpl_complain_time','wxnotice_tpl_complain_reason','wxnotice_tpl_complain_type','wxnotice_tpl_complain_name','wxnotice_tpl_balance','wxnotice_tpl_balance_user','wxnotice_tpl_balance_time','wxnotice_tpl_balance_money','msgconfig_regaudit','msgconfig_apply','msgconfig_complain_all','msgconfig_mchrisk_all','msgconfig_complain','msgconfig_mchrisk','voicenotice','voice_username','voice_apikey','orderprint','print_appid','print_appsecret'])));
$modKeys['certificate'] = array_values(array_unique(array_merge($modKeys['certificate'], ['cert_appcode','cert_aliyunsceneid','cert_antiid','cert_antikey','cert_antisceneid','cert_corpopen','cert_appcode2','ocr_type','ocr_aliyunid','ocr_aliyunkey','ocr_baiduid','ocr_baidukey'])));
$modKeys['mail'] = array_values(array_unique(array_merge($modKeys['mail'], ['mail_cloud','mail_apiuser','mail_name2','sms_tpl_reg','sms_tpl_find','sms_tpl_edit','sms_tpl_balance','sms_tpl_group','sms_tpl_complain'])));
$modKeys['gonggao'] = ['modal','zhuce','footer'];
$options = [
	'verifytype'=>[['value'=>'0','label'=>'邮箱验证'],['value'=>'1','label'=>'手机验证']], 'reg_open'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开放注册'],['value'=>'2','label'=>'仅邀请注册']], 'user_review'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'reg_pay'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'test_open'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'captcha_open_login'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'close_keylogin'=>[['value'=>'0','label'=>'开启'],['value'=>'1','label'=>'关闭']], 'pay_open'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'pay_verify'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'settle_open'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'settle_transfer'=>[['value'=>'0','label'=>'手动结算'],['value'=>'1','label'=>'自动转账']], 'wxnotice'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'mailnotice'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'robotnotice'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'proxy'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'cert_open'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']], 'cert_force'=>[['value'=>'0','label'=>'关闭'],['value'=>'1','label'=>'开启']],
];
$fields = [];
foreach($modKeys[$mod] ?? $modKeys['site'] as $key){
	$field = ['key'=>$key,'label'=>$labels[$key] ?? $key,'value'=>(string)($conf[$key] ?? '')];
	if(isset($options[$key])){ $field['type']='select'; $field['options']=$options[$key]; }
	elseif($key === 'footer' || strpos($key, 'desc') !== false) $field['type']='textarea';
	elseif(strpos($key, 'pwd') !== false || strpos($key, 'key') !== false) $field['type']='password';
	$fields[] = $field;
}
$uploadNotice = isset($_GET['uploaded']) && $_GET['uploaded'] === '1' ? 'LOGO 上传成功。浏览器可能需要强制刷新才能看到新图片。' : '';
if($mod === 'upimg'){
	if($_SERVER['REQUEST_METHOD'] === 'POST'){
		if(!isset($_POST['csrf_token']) || !isset($_SESSION['admin_csrf_token']) || !hash_equals((string)$_SESSION['admin_csrf_token'], (string)$_POST['csrf_token'])) epay_admin_view('admin-form', ['title'=>'LOGO 上传失败','description'=>'CSRF 校验失败，请刷新页面后重试。','notice'=>'CSRF 校验失败。','action'=>['endpoint'=>'./set.php?mod=upimg','method'=>'POST'],'fields'=>[['key'=>'file','label'=>'首页 LOGO','type'=>'file','accept'=>'image/png,image/jpeg,image/gif,image/webp','required'=>true]]]);
		$tmp = (string)($_FILES['file']['tmp_name'] ?? '');
		if($tmp === '' || !is_uploaded_file($tmp)) epay_admin_view('admin-form', ['title'=>'LOGO 上传失败','description'=>'没有收到有效的图片文件。','notice'=>'请选择 PNG、JPEG、GIF 或 WebP 图片。','action'=>['endpoint'=>'./set.php?mod=upimg','method'=>'POST'],'fields'=>[['key'=>'file','label'=>'首页 LOGO','type'=>'file','accept'=>'image/png,image/jpeg,image/gif,image/webp','required'=>true]]]);
		$allowedTypes = ['image/png','image/jpeg','image/gif','image/webp'];
		$finfo = finfo_open(FILEINFO_MIME_TYPE); $mime = $finfo ? finfo_file($finfo, $tmp) : ''; if($finfo) finfo_close($finfo);
		if(!in_array($mime, $allowedTypes, true)) epay_admin_view('admin-form', ['title'=>'LOGO 上传失败','description'=>'图片类型不受支持。','notice'=>'仅支持 PNG、JPEG、GIF 或 WebP。','action'=>['endpoint'=>'./set.php?mod=upimg','method'=>'POST'],'fields'=>[['key'=>'file','label'=>'首页 LOGO','type'=>'file','accept'=>'image/png,image/jpeg,image/gif,image/webp','required'=>true]]]);
		if(!move_uploaded_file($tmp, ROOT.'assets/img/logo.png')) epay_admin_view('admin-form', ['title'=>'LOGO 上传失败','description'=>'服务器无法写入 LOGO 文件。','notice'=>'请检查 assets/img 目录写入权限。','action'=>['endpoint'=>'./set.php?mod=upimg','method'=>'POST'],'fields'=>[['key'=>'file','label'=>'首页 LOGO','type'=>'file','accept'=>'image/png,image/jpeg,image/gif,image/webp','required'=>true]]]);
		header('Location: ./set.php?mod=upimg&uploaded=1'); exit;
	}
	epay_admin_view('admin-form', ['title'=>'首页 LOGO 设置','description'=>'上传后将覆盖 assets/img/logo.png。','notice'=>$uploadNotice,'action'=>['endpoint'=>'./set.php?mod=upimg','method'=>'POST','submitLabel'=>'确认上传'],'fields'=>[['key'=>'file','label'=>'首页 LOGO','type'=>'file','accept'=>'image/png,image/jpeg,image/gif,image/webp','required'=>true]]]);
}
$links = [];
foreach(['site','pay','risk','settle','transfer','oauth','notice','certificate','template','mail','cron','proxy','iptype','upimg','account'] as $key) $links[] = ['label'=>$labels[$key] ?? $key, 'href'=>'./set.php?mod='.$key];
if($mod === 'account'){
	epay_admin_view('admin-account', ['title'=>'管理员账户设置','description'=>'分别更新后台登录凭据和支付密码。','username'=>(string)($conf['admin_user'] ?? ''),'accountEndpoint'=>'./set.php?mod=account_n','payEndpoint'=>'./set.php?mod=paypwd_n','links'=>$links]);
}
$settingTitle = $labels[$mod] ?? '系统设置';
epay_admin_view('admin-form', ['title'=>$settingTitle,'description'=>'使用统一的 shadcn 表单维护 '.$settingTitle.'。','action'=>['endpoint'=>'ajax.php?act=set','method'=>'POST','submitMode'=>'fetch','reloadOnSuccess'=>true,'submitLabel'=>'保存设置'],'fields'=>$fields,'links'=>$links]);
