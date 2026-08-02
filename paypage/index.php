<?php
$is_defend = true;
include("./inc.php");
if(isset($_GET['ucode'])){
	$code=is_string($_GET['ucode'])?trim($_GET['ucode']):'';
    if(!preg_match('/^[a-zA-Z0-9]{1,32}$/',$code)) showerror('参数错误');
    $uid = $DB->findColumn('onecode', 'uid', ['code' => $code]);
    if(!$uid) showerror('当前码牌未绑定商户<br/>码牌编号：'.$code.'<br/><a href="/user/onecode.php?bind='.$code.'">点此绑定</a>');
}elseif(isset($_GET['merchant'])){
	$merchant=is_string($_GET['merchant'])?trim($_GET['merchant']):'';
	$uid = authcode($merchant, 'DECODE', SYS_KEY);
	if(!$uid || !is_numeric($uid))showerror('参数错误');
}elseif(isset($_SESSION['paypage_uid'])){
	$uid = intval($_SESSION['paypage_uid']);
}else{
	showerror('参数不完整');
}
$userrow = $DB->getRow("SELECT `uid`,`gid`,`money`,`mode`,`pay`,`cert`,`status`,`username`,`channelinfo`,`qq`,`codename`,`deposit` FROM `pre_user` WHERE `uid`=:uid LIMIT 1", [':uid'=>(int)$uid]);
if(!$userrow || $userrow['status']==0 || $userrow['pay']==0)showerror('当前商户不存在或已被封禁');
if($userrow['pay']==2 && $conf['user_review']==1)showerror('商户没通过审核，请联系官方客服进行审核');
$groupconfig = getGroupConfig($userrow['gid']);
$conf = array_merge($conf, $groupconfig);
if($conf['cert_force']==1 && $userrow['cert']==0){
	showerror('当前商户未完成实名认证，无法收款');
}
if($conf['forceqq']==1 && empty($userrow['qq'])){
	showerror('当前商户未填写联系QQ，无法收款');
}
if($conf['user_deposit']==1 && $conf['user_deposit_min'] > 0 && $conf['user_deposit_min'] > $userrow['deposit']){
    showerror('商户保证金不足，请前往支付平台充值保证金后再发起支付');
}
if(!empty($conf['pay_region_block'])){
    $ipregion = get_ip_region($clientip);
    if($ipregion){
        foreach(explode('|',$conf['pay_region_block']) as $rows){
            if(strpos($ipregion, $rows) !== false){
                showerror('您所在的地区无法发起支付，请更换其他支付方式');
            }
        }
    }
}

$_SESSION['paypage_uid'] = $uid;

$direct = '0';
$checktype = check_paytype();
$type = isset($_GET['type']) && is_string($_GET['type'])?trim($_GET['type']):$checktype;
if($type){
    if((isset($_GET['code']) || isset($_GET['auth_code']) || isset($_GET['userAuthCode'])) && $_SESSION['paypage_channel']){
        $submitData = \lib\Channel::info($_SESSION['paypage_channel'], $userrow['gid']);
        if($_SESSION['paypage_subchannel'] > 0) $submitData['subchannel'] = $_SESSION['paypage_subchannel'];
    }else{
        $submitData = \lib\Channel::submit($type, $uid, $userrow['gid']);
        $_SESSION['paypage_subchannel'] = $submitData['subchannel'];
    }
    $_SESSION['paypage_typeid'] = $submitData['typeid'];
	$_SESSION['paypage_channel'] = $submitData['channel'];
	$_SESSION['paypage_rate'] = $submitData['rate'];
	$_SESSION['paypage_paymax'] = $submitData['paymax'];
	$_SESSION['paypage_paymin'] = $submitData['paymin'];
    $_SESSION['paypage_mode'] = $submitData['mode'];

    $channel = $submitData['subchannel'] > 0 ? \lib\Channel::getSub($submitData['subchannel']) : \lib\Channel::get($submitData['channel'], $userrow['channelinfo']);
    if(!$channel)showerror('支付通道不存在');

	$apptype = explode(',',$channel['apptype']);
	if($checktype == 'alipay' && $type == 'alipay' && (
        ($submitData['plugin']=='alipay' || $submitData['plugin']=='alipaysl' || $submitData['plugin']=='alipayd') && in_array('4',$apptype)
        || $submitData['plugin']=='lakala' && in_array('2',$apptype)
        || $submitData['plugin']=='huifu' && in_array('4',$apptype)
        || $submitData['plugin']=='xsy' && in_array('2',$apptype)
        || $submitData['plugin']=='baofu' && in_array('2',$apptype)
        || $submitData['plugin']=='adapay' && in_array('2',$apptype)
        || $submitData['plugin']=='allinpay' && in_array('2',$apptype)
        || $submitData['plugin']=='dinpay' && in_array('3',$apptype)
        || $submitData['plugin']=='duolabao' && in_array('2',$apptype)
        || $submitData['plugin']=='fubei'
        || $submitData['plugin']=='fuiou2' && in_array('2',$apptype)
        || $submitData['plugin']=='haipay' && in_array('2',$apptype)
        || $submitData['plugin']=='hlpay' && in_array('2',$apptype)
        || $submitData['plugin']=='huishouqian' && in_array('2',$apptype)
        || $submitData['plugin']=='jindd' && in_array('2',$apptype)
        || $submitData['plugin']=='jlpay' && in_array('2',$apptype)
        || $submitData['plugin']=='joinpay' && in_array('3',$apptype)
        || $submitData['plugin']=='leshua' && in_array('2',$apptype)
        || $submitData['plugin']=='llianpay' && in_array('2',$apptype)
        || $submitData['plugin']=='sandpay' && in_array('2',$apptype)
        || $submitData['plugin']=='shengpay' && in_array('4',$apptype)
        || $submitData['plugin']=='suixingpay' && in_array('2',$apptype)
        || $submitData['plugin']=='unionpay' && in_array('2',$apptype)
        || $submitData['plugin']=='ysepay' && in_array('3',$apptype)
        || $submitData['plugin']=='yseqt' && in_array('2',$apptype)
        || $submitData['plugin']=='yeepay' && in_array('2',$apptype)
        )){
        if($conf['alipay_web_login_all'] == 1 && $conf['alipay_web_login'] > 0 || $submitData['plugin']!='alipay' && $submitData['plugin']!='alipaysl' && $submitData['plugin']!='alipayd'){
            if(!$conf['alipay_web_login']) showerror('未配置支付宝网页快捷登录通道');
            $channel = \lib\Channel::get($conf['alipay_web_login']);
        }
        $openId = alipayOpenId($channel);
		$direct = '1';
	}elseif($checktype == 'wxpay' && $type == 'wxpay' && $channel['appwxmp']>0 && (
        ($submitData['plugin']=='wxpay' || $submitData['plugin']=='wxpaysl' || $submitData['plugin']=='wxpayn' || $submitData['plugin']=='wxpaynp') && in_array('2',$apptype)
        || $submitData['plugin']=='lakala'
        || $submitData['plugin']=='huifu' && in_array('1',$apptype)
        || $submitData['plugin']=='xsy'
        || $submitData['plugin']=='baofu' && in_array('2',$apptype)
        || $submitData['plugin']=='adapay' && in_array('1',$apptype)
        || $submitData['plugin']=='allinpay' && in_array('2',$apptype)
        || $submitData['plugin']=='dinpay' && in_array('3',$apptype)
        || $submitData['plugin']=='duolabao' && in_array('2',$apptype)
        || $submitData['plugin']=='fubei'
        || $submitData['plugin']=='fuiou2' && in_array('2',$apptype)
        || $submitData['plugin']=='haipay'
        || $submitData['plugin']=='hlpay' && in_array('2',$apptype)
        || $submitData['plugin']=='huishouqian' && in_array('2',$apptype)
        || $submitData['plugin']=='jindd' && in_array('1',$apptype)
        || $submitData['plugin']=='jlpay' && in_array('2',$apptype)
        || $submitData['plugin']=='joinpay' && in_array('3',$apptype)
        || $submitData['plugin']=='leshua' && in_array('2',$apptype)
        || $submitData['plugin']=='llianpay' && in_array('2',$apptype)
        || $submitData['plugin']=='passpay' && in_array('2',$apptype)
        || $submitData['plugin']=='sandpay' && in_array('2',$apptype)
        || $submitData['plugin']=='shengpay' && in_array('1',$apptype)
        || $submitData['plugin']=='suixingpay' && in_array('2',$apptype)
        || $submitData['plugin']=='unionpay' && in_array('2',$apptype)
        || $submitData['plugin']=='ysepay' && in_array('2',$apptype)
        || $submitData['plugin']=='yseqt' && in_array('3',$apptype)
        || $submitData['plugin']=='yeepay' && in_array('2',$apptype)
        )){
		$openId = weixinOpenId($channel);
		$direct = '1';
	}elseif($checktype == 'bank' && $type == 'bank' && (
        $submitData['plugin']=='lakala' && in_array('2',$apptype)
        || $submitData['plugin']=='huifu' && in_array('4',$apptype)
        || $submitData['plugin']=='xsy' && in_array('2',$apptype)
        || $submitData['plugin']=='baofu' && in_array('2',$apptype)
        || $submitData['plugin']=='allinpay' && in_array('2',$apptype)
        || $submitData['plugin']=='jlpay' && in_array('2',$apptype)
        || $submitData['plugin']=='yseqt' && in_array('2',$apptype)
        )){
        $openId = unionpayOpenId($channel);
		$direct = '1';
	}elseif($checktype == 'qqpay' && $type == 'qqpay' && $submitData['plugin']=='qqpay' && in_array('2',$apptype)){
		$direct = '1';
	}
}

$money = isset($_GET['money']) && is_scalar($_GET['money'])?$_GET['money']:null;
if($money<=0 || !is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))$money = null;
$codename = !empty($userrow['codename'])?$userrow['codename']:$userrow['username'];
$csrf_token = bin2hex(random_bytes(16));
$_SESSION['paypage_token'] = $csrf_token;
$epay_paypage_config=[
	'uid'=>$uid,
	'token'=>$csrf_token,
	'paytype'=>$type,
	'direct'=>$direct,
	'payer'=>isset($openId)?$openId:'',
	'money'=>$money,
	'codename'=>$codename,
	'sitename'=>$conf['sitename'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title>向商户付款</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no">
    <link rel="stylesheet" href="/assets/dist/epay-ui.css">
</head>
<body>
<div id="epay-react-root" data-epay-view="pay-page" data-epay-config="<?php echo h(json_encode($epay_paypage_config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE))?>"></div>
<script type="module" src="/assets/dist/epay-ui.js"></script>
<script src="<?php echo $cdnpublic?>jquery/3.4.1/jquery.min.js"></script>
<script src="//open.mobile.qq.com/sdk/qqapi.js?_bid=152"></script>
<script>
(function(){
    var scripts = ['js/hammer.js', 'js/common.js', 'js/pay.js?v=1005'];
    function loadNext(index){
        if(index >= scripts.length) return;
        var script = document.createElement('script');
        script.src = scripts[index];
        script.onload = function(){loadNext(index + 1);};
        document.body.appendChild(script);
    }
    if(window.__epayUiMounted){
        loadNext(0);
    }else{
        document.addEventListener('epay-ui-mounted', function(){loadNext(0);}, {once:true});
    }
})();
</script>
</body>
</html>
