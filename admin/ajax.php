<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');
if($_SERVER['REQUEST_METHOD']==='POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token']!==$_SESSION['admin_csrf_token'])) exit('{"code":403,"msg":"CSRF验证失败"}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
case 'getcount':
	$thtime=date("Y-m-d").' 00:00:00';
	$count1=$DB->getColumn("SELECT count(*) from pre_order");
	$count2=$DB->getColumn("SELECT count(*) from pre_user");
	$plugincount=$DB->getColumn("SELECT count(*) from pre_plugin");
	if($plugincount<1){
		\lib\Plugin::updateAll();
	}
	$isConvert = $DB->getRow("SELECT * FROM pre_channel WHERE status=1 AND config IS NULL LIMIT 1");
	if($isConvert){
		convert_channel_data();
		\lib\Plugin::updateAll();
	}

	$orderrow=$DB->getRow("SELECT COUNT(*) allnum,COUNT(IF(status>0, 1, NULL)) sucnum FROM pre_order WHERE addtime>='$thtime'");
	$success_rate = 100;
	if($orderrow){
		if($orderrow['allnum'] > 0){
			$success_rate = round($orderrow['sucnum']/$orderrow['allnum']*100,2);
		}
	}

	$paytype = [];
	$rs = $DB->getAll("SELECT id,name,showname FROM pre_type WHERE status=1");
	foreach($rs as $row){
		$paytype[$row['id']] = $row['showname'];
	}
	unset($rs);

	$channel = [];
	$rs = $DB->getAll("SELECT id,name FROM pre_channel WHERE status=1");
	foreach($rs as $row){
		$channel[$row['id']] = $row['name'];
	}
	unset($rs);

	$tongji_cachetime=getSetting('tongji_cachetime', true);
	$tongji_cache = $CACHE->read('tongji');
	if($tongji_cachetime+3600>=time() && $tongji_cache && !isset($_GET['getnew'])){
		$array = json_decode($tongji_cache, true);
		if($array === null) $array = @unserialize($tongji_cache);
		$result=["code"=>0,"type"=>"cache","paytype"=>$paytype,"channel"=>$channel,"count1"=>$count1,"count2"=>$count2,"usermoney"=>round($array['usermoney'],2),"settlemoney"=>round($array['settlemoney'],2),"success_rate"=>$success_rate,"order_today"=>$array['order_today'],"order"=>[]];
	}else{
		$usermoney=$DB->getColumn("SELECT SUM(money) FROM pre_user WHERE money!='0.00'");
		$settlemoney=$DB->getColumn("SELECT SUM(money) FROM pre_settle");

		$today=date("Y-m-d");
		$rs=$DB->query("SELECT type,channel,realmoney,profitmoney from pre_order where (status=1 OR status=3) and date>='$today'");
		foreach($paytype as $id=>$type){
			$order_paytype[$id]=0;
			$profit_paytype[$id]=0;
		}
		foreach($channel as $id=>$type){
			$order_channel[$id]=0;
		}
		while($row = $rs->fetch())
		{
			$order_paytype[$row['type']]+=$row['realmoney'];
			$order_channel[$row['channel']]+=$row['realmoney'];
			if(!empty($row['profitmoney'])){
				$profit_paytype[$row['type']]+=$row['profitmoney'];
			}
		}
		foreach($order_paytype as $k=>$v){
			$order_paytype[$k] = round($v,2);
		}
		foreach($order_channel as $k=>$v){
			$order_channel[$k] = round($v,2);
		}
		foreach($profit_paytype as $k=>$v){
			$profit_paytype[$k] = round($v,2);
		}
		$allmoney=0;
		foreach($order_paytype as $order){
			$allmoney+=$order;
		}
		$allprofit=0;
		foreach($profit_paytype as $money){
			$allprofit+=$money;
		}
	
		$order_today['all']=round($allmoney,2);
		$order_today['profit_all']=round($allprofit,2);
		$order_today['paytype']=$order_paytype;
		$order_today['channel']=$order_channel;
		$order_today['profit_paytype']=$profit_paytype;

		saveSetting('tongji_cachetime',time());
		$CACHE->save('tongji',json_encode(["usermoney"=>$usermoney,"settlemoney"=>$settlemoney,"order_today"=>$order_today], JSON_UNESCAPED_UNICODE));

		$result=["code"=>0,"type"=>"online","paytype"=>$paytype,"channel"=>$channel,"count1"=>$count1,"count2"=>$count2,"usermoney"=>round($usermoney,2),"settlemoney"=>round($settlemoney,2),"success_rate"=>$success_rate,"order_today"=>$order_today,"order"=>[]];
	}
	for($i=1;$i<7;$i++){
		$day = date("Ymd", strtotime("-{$i} day"));
		if($order_tongji = $CACHE->read('order_'.$day)){
			$tmp = json_decode($order_tongji, true);
			if($tmp === null) $tmp = @unserialize($order_tongji);
			$result["order"][$day] = $tmp;
		}else{
			break;
		}
	}
	exit(json_encode($result));
break;

case 'set':
	if(isset($_POST['localurl'])){
		if(!empty($_POST['localurl']) && (substr($_POST['localurl'],0,4)!='http' || substr($_POST['localurl'],-1)!='/'))exit('{"code":-1,"msg":"回调专用网址格式错误"}');
	}
	if(isset($_POST['apiurl'])){
		if(!empty($_POST['apiurl']) && (substr($_POST['apiurl'],0,4)!='http' || substr($_POST['apiurl'],-1)!='/'))exit('{"code":-1,"msg":"用户对接网址格式错误"}');
	}
	if(isset($_POST['login_apiurl'])){
		if(!empty($_POST['login_apiurl']) && (substr($_POST['login_apiurl'],0,4)!='http' || substr($_POST['login_apiurl'],-1)!='/'))exit('{"code":-1,"msg":"聚合登录API接口地址格式错误"}');
	}
	$deny_keys = ['admin_user','admin_pwd','admin_paypwd','version'];
	foreach($_POST as $k=>$v){
		if(!preg_match('/^[a-zA-Z0-9_]+$/', $k)) continue;
		if(in_array($k, $deny_keys, true)) continue;
		saveSetting($k, $v);
	}
	$ad=$CACHE->clear();
	if($ad)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"修改设置失败['.$DB->error().']"}');
break;
case 'setGonggao':
	$id=intval($_GET['id']);
	$status=intval($_GET['status']);
	if($DB->update('anounce', ['status'=>$status], ['id'=>$id]))exit('{"code":0,"msg":"修改状态成功！"}');
	else exit('{"code":-1,"msg":"修改状态失败['.$DB->error().']"}');
break;
case 'delGonggao':
	$id=intval($_GET['id']);
	if($DB->delete('anounce', ['id'=>$id]))exit('{"code":0,"msg":"删除公告成功！"}');
	else exit('{"code":-1,"msg":"删除公告失败['.$DB->error().']"}');
break;
case 'iptype':
	$result = [
	['name'=>'0_X_FORWARDED_FOR', 'ip'=>real_ip(0), 'city'=>get_ip_city(real_ip(0))],
	['name'=>'1_X_REAL_IP', 'ip'=>real_ip(1), 'city'=>get_ip_city(real_ip(1))],
	['name'=>'2_REMOTE_ADDR', 'ip'=>real_ip(2), 'city'=>get_ip_city(real_ip(2))]
	];
	exit(json_encode($result));
break;

case 'setArticle': //文章状态
	$id=intval($_GET['id']);
	$active=intval($_GET['active']);
	$DB->update('article', ['active'=>$active], ['id'=>$id]);
	exit('{"code":0,"msg":"succ"}');
break;
case 'article_upload':
	$file_name = $_FILES['imgFile']['name'];
	$tmp_name = $_FILES['imgFile']['tmp_name'];
	//获得文件扩展名
	$temp_arr = explode(".", $file_name);
	$file_ext = array_pop($temp_arr);
	$file_ext = strtolower(trim($file_ext));
	if (in_array($file_ext, array('gif', 'jpg', 'jpeg', 'png', 'bmp', 'webp')) === false) {
		exit('{"error":1,"message":"上传文件扩展名是不允许的扩展名。"}');
	}
	$filename = md5_file($tmp_name).'.'.$file_ext;
	$fileurl = '/assets/img/article/'.$filename;
	if(move_uploaded_file($tmp_name, ROOT.'assets/img/article/'.$filename)){
		exit('{"error":0,"url":"'.$fileurl.'"}');
	}else{
		exit('{"error":1,"message":"上传失败，请确保有本地写入权限"}');
	}
break;

case 'testproxy':
	$conf['proxy_server'] = trim($_POST['proxy_server']);
	$conf['proxy_port'] = $_POST['proxy_port'];
	$conf['proxy_user'] = trim($_POST['proxy_user']);
	$conf['proxy_pwd'] = trim($_POST['proxy_pwd']);
	$conf['proxy_type'] = $_POST['proxy_type'];
	try{
		check_proxy('https://dl.amh.sh/ip.htm');
	}catch(Exception $e){
		try{
			check_proxy('https://myip.ipip.net/');
		}catch(Exception $e){
			exit('{"code":-1,"msg":"'.$e->getMessage().'"}');
		}
	}
	exit('{"code":0}');
break;

case 'generate_wxa_link':
	$wechatid = intval($_POST['channel']);
	$wxinfo = \lib\Channel::getWeixin($wechatid);
	if(!$wxinfo)exit('{"code":-1,"msg":"该微信小程序不存在"}');
	$path = 'pages/getopenid/getopenid';
	$query = 'url='.$siteurl.'&wechatid='.$wechatid;
	$wechat = new \lib\wechat\WechatAPI($wechatid);
	try{
		$url_link = $wechat->generate_link($path, $query, 3600);
	}catch(Exception $e){
		exit('{"code":-1,"msg":"'.$e->getMessage().'"}');
	}
	exit('{"code":0,"url":"'.$url_link.'"}');
break;
case 'delInvite':
	$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
	if($id <= 0) exit('{"code":-1,"msg":"邀请码参数错误"}');
	if($DB->delete('invitecode', ['id'=>$id])) exit('{"code":0,"msg":"删除邀请码成功"}');
	exit('{"code":-1,"msg":"删除邀请码失败"}');
break;
case 'clearInvite':
	$status = isset($_POST['status']) ? intval($_POST['status']) : (isset($_GET['status']) ? intval($_GET['status']) : -1);
	if($status === 1) $ok = $DB->exec("DELETE FROM pre_invitecode WHERE status=1");
	else $ok = $DB->exec("DELETE FROM pre_invitecode");
	if($ok !== false) exit('{"code":0,"msg":"清理邀请码成功"}');
	exit('{"code":-1,"msg":"清理邀请码失败"}');
break;
case 'generateInvite':
	$num = min(1000, max(1, intval($_POST['num'] ?? 0)));
	$codes = [];
	for($i=0; $i<$num; $i++){
		$code = function_exists('random') ? random(8) : bin2hex(random_bytes(4));
		if($DB->exec("INSERT INTO pre_invitecode (`code`,`addtime`,`status`) VALUES (:code,NOW(),0)", [':code'=>$code])) $codes[] = $code;
	}
	exit(json_encode(['code'=>0,'msg'=>'成功生成 '.count($codes).' 个邀请码','codes'=>$codes], JSON_UNESCAPED_UNICODE));
break;
default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}
