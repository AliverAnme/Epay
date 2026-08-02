<?php
/** 管理员登录 API 与原生 shadcn 登录入口 */
$verifycode = (function_exists('imagecreate') && file_exists('code.php')) ? 1 : 0;
$login_limit_count = 5;
include("../includes/common.php");
$login_limit_file = sys_get_temp_dir() . '/epay_login_' . substr(md5(SYS_KEY), 0, 16) . '.lock';
if(isset($_GET['act']) && $_GET['act'] === 'login'){
	if(!checkRefererHost()) exit('{"code":403}');
	$username = trim((string)($_POST['username'] ?? '')); $password = trim((string)($_POST['password'] ?? '')); $code = trim((string)($_POST['code'] ?? '')); $enc_type = (string)($_POST['enc'] ?? '0');
	if($username === '' || $password === '') exit(json_encode(['code'=>-1,'msg'=>'用户名或密码不能为空']));
	if($verifycode === 1 && (!$code || strtolower($code) !== ($_SESSION['vc_code'] ?? ''))) exit(json_encode(['code'=>-1,'msg'=>'验证码错误']));
	$errcount = $DB->getColumn("SELECT count(*) FROM `pre_log` WHERE `ip`=:ip AND `date`>DATE_SUB(NOW(),INTERVAL 1 DAY) AND `uid`=0 AND `type`='登录失败'", [':ip'=>$clientip]);
	if($errcount >= $login_limit_count && file_exists($login_limit_file)) exit(json_encode(['code'=>-1,'msg'=>'多次登录失败，暂时禁止登录']));
	if($enc_type === '1'){
		$plain = ''; $private_key = base64ToPem($conf['private_key'], 'PRIVATE KEY'); $pkey = openssl_pkey_get_private($private_key);
		if(!openssl_private_decrypt(base64_decode($password), $plain, $pkey, OPENSSL_PKCS1_PADDING)) exit(json_encode(['code'=>-1,'msg'=>'密码解密失败']));
		$password = $plain;
	}
	if($username === $conf['admin_user'] && $password === $conf['admin_pwd']){
		if($conf['totp_open'] == 1 && !empty($conf['totp_secret'])) exit(json_encode(['code'=>-1,'msg'=>'需要验证动态口令','vcode'=>2]));
		$DB->insert('log', ['uid'=>0,'type'=>'登录后台','date'=>'NOW()','ip'=>$clientip]); if(file_exists($login_limit_file)) unlink($login_limit_file);
		$session = md5($username.$password.$password_hash); $expiretime = time()+2592000; $token = authcode("{$username}\t{$session}\t{$expiretime}",'ENCODE',SYS_KEY); $secure = is_https();
		setcookie('admin_token',$token,$expiretime,'/','',$secure,true); session_regenerate_id(true); unset($_SESSION['vc_code']); exit(json_encode(['code'=>0]));
	}
	$DB->insert('log', ['uid'=>0,'type'=>'登录失败','date'=>'NOW()','ip'=>$clientip]); unset($_SESSION['vc_code']); $errcount++;
	if($errcount >= $login_limit_count){ file_put_contents($login_limit_file,'1'); exit(json_encode(['code'=>-1,'msg'=>'多次登录失败，暂时禁止登录','vcode'=>1])); }
	exit(json_encode(['code'=>-1,'msg'=>'用户名或密码错误','vcode'=>1]));
}
if(isset($_GET['act']) && $_GET['act'] === 'totp'){
	if(!checkRefererHost()) exit('{"code":403}');
	$code = trim((string)($_POST['code'] ?? ''));
	if($code === '') exit(json_encode(['code'=>-1,'msg'=>'请输入动态口令']));
	if($conf['totp_open'] != 1 || empty($conf['totp_secret'])) exit(json_encode(['code'=>-1,'msg'=>'未启用TOTP二次验证']));
	try{ $totp = \lib\TOTP::create($conf['totp_secret']); if(!$totp->verify($code)) exit(json_encode(['code'=>-1,'msg'=>'动态口令错误'])); }catch(Exception $e){ exit(json_encode(['code'=>-1,'msg'=>$e->getMessage()])); }
	$DB->insert('log', ['uid'=>0,'type'=>'登录后台','date'=>'NOW()','ip'=>$clientip]); $session = md5($conf['admin_user'].$conf['admin_pwd'].$password_hash); $expiretime = time()+2592000; $token = authcode("{$conf['admin_user']}\t{$session}\t{$expiretime}",'ENCODE',SYS_KEY); $secure = is_https();
	setcookie('admin_token',$token,$expiretime,'/','',$secure,true); session_regenerate_id(true); exit(json_encode(['code'=>0]));
}
if(isset($_GET['logout'])){ if(!checkRefererHost()) exit(); $secure = is_https(); setcookie('admin_token','',time()-2592000,'/','',$secure,true); exit("<script>window.location.href='./login.php';</script>"); }
if($islogin == 1) exit("<script>window.location.href='./';</script>");
$title = '用户登录';
$epay_ui_view = 'admin-login';
$epay_ui_config = ['sitename'=>$conf['sitename'],'verifycode'=>$verifycode];
include './head.php';
?>
<script>
/* 登录动作由 React/shadcn 表单触发；这里仅保留后端 API 适配。 */
window.submitlogin = async function(){
  var user = document.querySelector('#user')?.value.trim() || '';
  var pass = document.querySelector('#pass')?.value || '';
  var code = document.querySelector('#code')?.value.trim() || '';
  if(!user || !pass){ window.alert('用户名或密码不能为空'); return false; }
  var submit = document.querySelector('#submit');
  if(submit) submit.disabled = true;
  try{
    var response = await fetch('./login.php?act=login', {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'}, body:new URLSearchParams({username:user, password:pass, code:code, enc:'0'})});
    var data = await response.json();
    if(Number(data.code) === 0){ window.location.href = './'; return false; }
    if(Number(data.vcode) === 1){ var image = document.querySelector('#verifycode'); if(image) image.src = './code.php?r='+Math.random(); }
    if(Number(data.vcode) === 2){ var loginForm = document.querySelector('#login-form'); var totpForm = document.querySelector('#totp-form'); if(loginForm) loginForm.classList.add('hidden'); if(totpForm){ totpForm.classList.remove('hidden'); totpForm.classList.add('flex'); } document.querySelector('#totp_code')?.focus(); return false; }
    window.alert(data.msg || '登录失败');
  }catch(error){ window.alert('服务器错误，请稍后重试'); }
  finally{ if(submit) submit.disabled = false; }
  return false;
};
window.doTotp = async function(){
  var code = document.querySelector('#totp_code')?.value.trim() || '';
  if(!/^\d{6}$/.test(code)){ window.alert('动态口令格式错误'); return false; }
  var submit = document.querySelector('#totp-form button[type="submit"]');
  if(submit) submit.disabled = true;
  try{
    var response = await fetch('./login.php?act=totp', {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'}, body:new URLSearchParams({code:code})});
    var data = await response.json();
    if(Number(data.code) === 0){ window.location.href = './'; return false; }
    window.alert(data.msg || '动态口令验证失败');
  }catch(error){ window.alert('服务器错误，请稍后重试'); }
  finally{ if(submit) submit.disabled = false; }
  return false;
};
window.findpwd = function(){ window.alert('请进入数据库管理器修改 pre_config 表中的 admin_pwd；如已开启 TOTP，请同时清理 totp_open 和 totp_secret。'); };
</script>
</body></html>
<?php exit;
