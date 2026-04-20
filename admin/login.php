<?php
/**
 * 登录
**/
$verifycode = 1;//验证码开关
$login_limit_count = 5;//登录失败次数
$login_limit_file = '@login.lock';

if(!function_exists("imagecreate") || !file_exists('code.php'))$verifycode=0;
include("../includes/common.php");

if(isset($_GET['act']) && $_GET['act']=='login'){
  if(!checkRefererHost())exit('{"code":403}');
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $code = trim($_POST['code']);
  $enc_type = isset($_POST['enc']) ? $_POST['enc'] : '0';
  if(empty($username) || empty($password)){
    exit(json_encode(['code'=>-1,'msg'=>'用户名或密码不能为空']));
  }
  if($verifycode==1 && (!$code || strtolower($code) != $_SESSION['vc_code'])){
    exit(json_encode(['code'=>-1,'msg'=>'验证码错误']));
  }
  $errcount = $DB->getColumn("SELECT count(*) FROM `pre_log` WHERE `ip`=:ip AND `date`>DATE_SUB(NOW(),INTERVAL 1 DAY) AND `uid`=0 AND `type`='登录失败'", [':ip'=>$clientip]);
  if($errcount >= $login_limit_count && file_exists($login_limit_file) && !$conf['totp_open']){
    exit(json_encode(['code'=>-1,'msg'=>'多次登录失败，暂时禁止登录。可删除@login.lock文件解除限制']));
  }
  if($enc_type == '1'){
    $plain = '';
    $private_key = base64ToPem($conf['private_key'], 'PRIVATE KEY');
    $pkey = openssl_pkey_get_private($private_key);
    if(!openssl_private_decrypt(base64_decode($password), $plain, $pkey, OPENSSL_PKCS1_PADDING)){
      exit(json_encode(['code'=>-1,'msg'=>'密码解密失败']));
    }
    $password = $plain;
  }
  if($username == $conf['admin_user'] && $password == $conf['admin_pwd']){
    if ($conf['totp_open'] == 1 && !empty($conf['totp_secret'])) {
      if (file_exists($login_limit_file)) {
          unlink($login_limit_file);
      }
      exit(json_encode(['code'=>-1, 'msg'=>'需要验证动态口令', 'vcode' => 2]));
    }
    $DB->insert('log', ['uid'=>0, 'type'=>'登录后台', 'date'=>'NOW()', 'ip'=>$clientip]);
    if (file_exists($login_limit_file)) {
      unlink($login_limit_file);
    }
		$session=md5($username.$password.$password_hash);
		$expiretime=time() + 2592000;
		$token=authcode("{$username}\t{$session}\t{$expiretime}", 'ENCODE', SYS_KEY);
		setcookie("admin_token", $token, $expiretime, null, null, null, true);
    unset($_SESSION['vc_code']);
    exit(json_encode(['code'=>0]));
  }else{
    $DB->insert('log', ['uid'=>0, 'type'=>'登录失败', 'date'=>'NOW()', 'ip'=>$clientip]);
    unset($_SESSION['vc_code']);
    $errcount++;
    $retry_times = $login_limit_count - $errcount;
    if($retry_times < 0) $retry_times = 0;
    if($retry_times <= 0){
      file_put_contents($login_limit_file, '1');
      exit(json_encode(['code'=>-1,'msg'=>'多次登录失败，暂时禁止登录。可删除@login.lock文件解除限制','vcode'=>1]));
    }else{
      exit(json_encode(['code'=>-1,'msg'=>'用户名或密码错误，你还可以尝试'.$retry_times.'次','vcode'=>1]));
    }
  }
}elseif(isset($_GET['act']) && $_GET['act']=='totp'){
  if(!checkRefererHost())exit('{"code":403}');
  $code = trim($_POST['code']);
  if (empty($code)) exit(json_encode(['code'=>-1,'msg'=>'请输入动态口令']));
  if ($conf['totp_open'] != 1 || empty($conf['totp_secret'])) {
    exit(json_encode(['code'=>-1,'msg'=>'未启用TOTP二次验证']));
  }
  try {
    $totp = \lib\TOTP::create($conf['totp_secret']);
    if (!$totp->verify($code)) {
      exit(json_encode(['code'=>-1,'msg'=>'动态口令错误']));
    }
  } catch (Exception $e) {
    exit(json_encode(['code'=>-1,'msg'=>$e->getMessage()]));
  }
  $DB->insert('log', ['uid'=>0, 'type'=>'登录后台', 'date'=>'NOW()', 'ip'=>$clientip]);
  $session=md5($conf['admin_user'].$conf['admin_pwd'].$password_hash);
  $expiretime=time() + 2592000;
  $token=authcode("{$conf['admin_user']}\t{$session}\t{$expiretime}", 'ENCODE', SYS_KEY);
  setcookie("admin_token", $token, $expiretime, null, null, null, true);
  exit(json_encode(['code'=>0]));
}elseif(isset($_GET['logout'])){
	if(!checkRefererHost())exit();
	setcookie("admin_token", "", time() - 2592000);
	exit("<script language='javascript'>window.location.href='./login.php';</script>");
}elseif($islogin==1){
	exit("<script language='javascript'>alert('您已登录！');window.location.href='./';</script>");
}
$title='用户登录';
include './head.php';
?>
  <nav class="navbar navbar-fixed-top navbar-default">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
          <span class="sr-only">导航按钮</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="./">支付管理中心</a>
      </div><!-- /.navbar-header -->
      <div id="navbar" class="collapse navbar-collapse">
        <ul class="nav navbar-nav navbar-right">
          <li class="active">
            <a href="./login.php"><span class="glyphicon glyphicon-user"></span> 登录</a>
          </li>
        </ul>
      </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
  </nav><!-- /.navbar -->
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
      <div class="panel panel-primary">
        <div class="panel-heading"><h3 class="panel-title">管理员登录</h3></div>
        <div class="panel-body">
          <form class="form-horizontal" id="login-form" role="form" onsubmit="return submitlogin()">
            <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
              <input type="text" name="user" value="" class="form-control input-lg" placeholder="用户名" required="required"/>
            </div><br/>
            <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
              <input type="password" name="pass" class="form-control input-lg" placeholder="密码" required="required"/>
            </div><br/>
            <?php if($verifycode==1){?>
            <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-adjust"></span></span>
              <input type="text" class="form-control input-lg" name="code" placeholder="输入验证码" autocomplete="off" required>
              <span class="input-group-addon" style="padding: 0">
                <img id="verifycode" src="./code.php?r=<?php echo time();?>"height="45"onclick="this.src='./code.php?r='+Math.random();" title="点击更换验证码">
              </span>
            </div><br/>
            <?php }?>
            <div class="form-group">
              <div class="col-xs-12"><button type="submit" class="btn btn-primary btn-lg btn-block" id="submit">立即登录</button></div>
            </div>
            <div class="form-group">
              <div class="col-xs-12 text-center"><a href="javascript:void(0);" onclick="findpwd()">忘记密码</a></div>
            </div>
          </form>
          <form class="form-horizontal" id="totp-form" onsubmit="return doTotp()" style="display:none;">
            <div class="alert alert-info" role="alert">TOTP二次验证</div>
            <div class="input-group">
              <div class="input-group-addon"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span></div>
              <input type="number" class="form-control input-lg" placeholder="输入动态口令" name="totp_code" id="totp_code" autocomplete="off" required="required"/>
            </div><br/>
            <div class="form-group">
              <div class="col-xs-12"><button type="submit" class="btn btn-primary btn-lg btn-block" id="submit">立即登录</button></div>
            </div>
            <div class="form-group">
              <div class="col-xs-12 text-center"><a href="javascript:void(0);" onclick="findpwd()">忘记密码</a></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
<div class="modal fade" id="modal-findpwd" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title">找回管理员密码方法</h4>
			</div>
			<div class="modal-body">
				<p>进入数据库管理器（phpMyAdmin），点击进入当前网站所在数据库，然后查看pay_config表即可找回管理员密码。</p>
        <?php if($conf['totp_open'] == 1){?>如需关闭TOTP二次验证，请执行以下SQL：UPDATE pay_config SET v='0' WHERE k='totp_open';UPDATE pay_cache SET v='' WHERE k='config';<?php }?>
			</div>
		</div>
	</div>
</div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jsencrypt/3.5.4/jsencrypt.min.js"></script>
<script>
const PUBLIC_KEY_PEM = `<?php echo base64ToPem($conf['public_key'], 'PUBLIC KEY')?>`;
function submitlogin(){
  var enc_type = '0';
  var user = $("input[name='user']").val();
  var pass = $("input[name='pass']").val();
  var code = $("input[name='code']").val();
  if(user=='' || pass==''){layer.alert('用户名或密码不能为空！');return false;}
  if(PUBLIC_KEY_PEM != ''){
    const enc = new JSEncrypt();
    enc.setPublicKey(PUBLIC_KEY_PEM);
    pass = enc.encrypt(pass);
    if(pass) enc_type = '1';
  }
  var ii = layer.load(2);
  $.ajax({
    type : 'POST',
    url : '?act=login',
    data: {username:user, password:pass, code:code, enc:enc_type},
    dataType : 'json',
    success : function(data) {
      layer.close(ii);
      if(data.code == 0){
        layer.msg('登录成功，正在跳转', {icon: 1,shade: 0.01,time: 15000});
        window.location.href='./';
      }else{
        if(data.vcode==1){
          $("#verifycode").attr('src', './code.php?r='+Math.random())
        }else if(data.vcode==2){
          $("#totp-form").show();
          $("#login-form").hide();
          $("#totp_code").focus();
          return false;
        }
        layer.alert(data.msg, {icon: 2});
      }
    },
    error:function(data){
      layer.close(ii);
      layer.msg('服务器错误');
    }
  });
  return false;
}
function doTotp(){
  var code = $("#totp_code").val();
  if(code.length != 6){
		layer.msg('动态口令格式错误', {icon: 2});
		return false;
	}
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.post('?act=totp', {code:code}, function(res){
		layer.close(ii);
		if(res.code == 0){
			layer.msg('登录成功，正在跳转', {icon: 1,shade: 0.01,time: 15000});
      window.location.href = './';
		}else{
			layer.alert(res.msg, {icon: 2});
		}
	}, 'json');
	return false;
}
function findpwd(){
  $('#modal-findpwd').modal('show');
}
$(document).ready(function(){
	$("#totp_code").keyup(function(){
		var code = $(this).val();
		if(code.length == 6){
			$("#totp-form").submit();
		}
	});
});
</script>
</body>
</html>