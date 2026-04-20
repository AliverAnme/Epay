<?php
include("../includes/common.php");

if(isset($_POST['action'])){
	if(!$islogin) exit(json_encode(['code'=>-1, 'msg'=>'未登录']));
	if($_POST['action'] == 'generate'){
		try {
			$totp = \lib\TOTP::create();
			$totp->setLabel($conf['admin_user']);
			$totp->setIssuer($conf['sitename']);
			echojson(['code' => 0, 'data' => ['secret' => $totp->getSecret(), 'qrcode' => $totp->getProvisioningUri()]]);
		} catch (Exception $e) {
			echojsonmsg($e->getMessage());
		}
	}elseif($_POST['action'] == 'bind'){
		$secret = trim($_POST['secret']);
		$code = trim($_POST['code']);
		if(empty($secret) || empty($code)){
			echojsonmsg('参数不完整');
		}
		try {
			$totp = \lib\TOTP::create($secret);
			if (!$totp->verify($code)) {
				echojsonmsg('动态口令错误');
			}
		} catch (Exception $e) {
			echojsonmsg($e->getMessage());
		}
		saveSetting('totp_open', 1);
		saveSetting('totp_secret', $secret);
		$CACHE->clear();
		echojson(['code' => 0, 'msg' => 'TOTP绑定成功']);
	}elseif($_POST['action'] == 'close'){
		saveSetting('totp_open', 0);
		saveSetting('totp_secret', '');
		$CACHE->clear();
		echojson(['code' => 0, 'msg' => 'TOTP已关闭']);
	}else{
		echojsonmsg('参数错误');
	}
}

$title='TOTP二次验证配置';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$callback_url = $siteurl.'wework.php';

$errmsg = $CACHE->read('wxkferrmsg');
if($errmsg){
	$arr = unserialize($errmsg);
	$errmsg = $arr['time'].' - '.$arr['errmsg'];
}

$account_list = $DB->getAll("SELECT A.* FROM pre_wxkfaccount A LEFT JOIN pre_wework B ON A.wid=B.id WHERE B.status=1");
$account_select = '<option value="0">多客服账号轮询</option>';
foreach($account_list as $row){
	$account_select .= '<option value="'.$row['id'].'">'.$row['openkfid'].' - '.$row['name'].'</option>';
}
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">TOTP二次验证</h3></div>
<div class="panel-body">
  <form onsubmit="return saveAccount(this)" method="post" class="form" role="form">
	<div class="form-group">
		<div class="input-group">
			<?php if($conf['totp_open'] == 1){ ?>
			<input type="text" name="totp_status" value="已开启" style="color:green" class="form-control" readonly/>
			<div class="input-group-btn"><button type="button" class="btn btn-info" onclick="open_totp()">重置</button></div>
			<div class="input-group-btn"><button type="button" class="btn btn-danger" onclick="close_totp()">关闭</button></div>
			<?php }else{ ?>
			<input type="text" name="totp_status" value="未开启" style="color:blue" class="form-control" readonly/>
			<div class="input-group-btn"><button type="button" class="btn btn-info" onclick="open_totp()">开启</button></div>
			<?php } ?>
		</div>
	</div>
  </form>
</div>
<div class="panel-footer">
<p><span class="glyphicon glyphicon-info-sign"></span> 开启后，登录时需使用支持TOTP的认证软件进行二次验证，提高账号安全性。开启前需确保服务器时间正确。</p>
<p>支持TOTP的认证软件：<a href="https://sj.qq.com/appdetail/com.tencent.authenticator" target="_blank" rel="noreferrer">腾讯身份验证器</a>、<a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" rel="noreferrer">谷歌身份验证器</a>、<a href="https://www.microsoft.com/zh-cn/security/mobile-authenticator-app" target="_blank" rel="noreferrer">微软身份验证器</a>、<a href="https://github.com/freeotp" target="_blank" rel="noreferrer">FreeOTP</a></p>
</div>
</div>
<div class="modal" id="modal-totp" data-backdrop="static" data-keyboard="false" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title">TOTP绑定</h4>
			</div>
			<div class="modal-body text-center">
				<p>使用支持TOTP的认证软件扫描以下二维码</p>
				<div class="qr-image mt-4" id="qrcode"></div>
				<p><a href="javascript:;" data-clipboard-text="" id="copy-btn">复制密钥</a></p>
				<form id="form-totp" style="text-align: left;" onsubmit="return bind_totp()">
					<div class="form-group mt-4">
						<div class="input-group"><input type="number" class="form-control input-lg" name="code" id="code" value="" placeholder="填写动态口令" autocomplete="off" required><div class="input-group-btn"><input type="submit" name="submit" value="完成绑定" class="btn btn-success btn-lg btn-block"/></div></div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="<?php echo $cdnpublic?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script>
var commonData = {secret:null,qrcode:null};
function open_totp(){
	if(!commonData.qrcode || !commonData.secret){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.post('?', {action:'generate'}, function(res){
			layer.close(ii);
			if(res.code == 0){
				commonData.secret = res.data.secret;
				commonData.qrcode = res.data.qrcode;
				$('#qrcode').qrcode({
					text: commonData.qrcode,
					width: 150,
					height: 150,
					foreground: "#000000",
					background: "#ffffff",
					typeNumber: -1
				});
				$("#copy-btn").attr('data-clipboard-text', commonData.secret);
				$('#modal-totp').modal('show');
				$("#code").focus();
			}else{
				layer.alert(res.msg, {icon: 2});
			}
		});
	}else{
		$('#modal-totp').modal('show');
		$("#code").focus();
	}
}
function bind_totp(){
	var code = $("#code").val();
	if(code.length != 6){
		layer.msg('动态口令格式错误', {icon: 2});
		return false;
	}
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.post('?', {action:'bind', secret:commonData.secret, code:code}, function(res){
		layer.close(ii);
		if(res.code == 0){
			layer.alert('TOTP绑定成功', {icon: 1}, function(){
				window.location.reload();
			});
		}else{
			layer.alert(res.msg, {icon: 2});
		}
	});
	return false;
}
function close_totp(){
	layer.confirm('确定要关闭TOTP二次验证吗？', {
		btn: ['确定','取消']
	}, function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.post('?', {action: 'close'}, function(res){
			layer.close(ii);
			if(res.code == 0){
				layer.alert('TOTP已关闭', {icon: 1}, function(){
					window.location.reload();
				});
			}else{
				layer.alert(res.msg, {icon: 2});
			}
		});
	});
}
$(document).ready(function(){
	var clipboard = new Clipboard('#copy-btn');
	clipboard.on('success', function (e) {
		layer.msg('复制成功！', {icon: 1, time: 600});
	});
	clipboard.on('error', function (e) {
		layer.msg('复制失败', {icon: 2});
	});
	$("#code").keyup(function(){
		var code = $(this).val();
		if(code.length == 6){
			$("#form-totp").submit();
		}
	});
});
</script>