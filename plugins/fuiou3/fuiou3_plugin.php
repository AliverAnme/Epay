<?php
/**
 * http://180.168.100.158:13318/fuiouWposApipay/
 */
class fuiou3_plugin
{
	static public $info = [
		'name'        => 'fuiou3', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '富友支付(互联网)', //支付插件显示名称
		'author'      => '富友', //支付插件作者
		'link'        => 'https://www.fuiou.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户号',
				'type' => 'input',
				'note' => '',
			],
			'appsecret' => [
				'name' => '商户私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appkey' => [
				'name' => '富友公钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appswitch' => [
				'name' => '环境选择',
				'type' => 'select',
				'options' => [0=>'生产环境',1=>'测试环境'],
			],
		],
		'select' => null,
		'select_wxpay' => [
			'1' => '扫码支付',
			'2' => '公众号/小程序支付',
			'3' => '跳转富友小程序',
		],
		'note' => '', //支付密钥填写说明
		'bindwxmp' => true, //是否支持绑定微信公众号
		'bindwxa' => true, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			if(checkalipay() && in_array('2',$channel['apptype'])){
				return ['type'=>'jump','url'=>'/pay/alipayjs/'.TRADE_NO.'/?d=1'];
			}else{
				return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
			}
		}elseif($order['typename']=='wxpay'){
			if(checkwechat() && in_array('2',$channel['apptype']) && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif(checkmobile() && (in_array('2',$channel['apptype']) && $channel['appwxa']>0 || in_array('3',$channel['apptype']))){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
			}
		}elseif($order['typename']=='bank'){
			return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $device, $mdevice, $method;

        if($method=='jsapi'){
			if($order['typename']=='alipay'){
				return self::alipayjs();
			}elseif($order['typename']=='wxpay'){
				return self::wxjspay();
			}
		}elseif($order['typename']=='alipay'){
			if($mdevice=='alipay' && in_array('2',$channel['apptype'])){
				return ['type'=>'jump','url'=>$siteurl.'pay/alipayjs/'.TRADE_NO.'/?d=1'];
			}else{
				return self::alipay();
			}
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat' && in_array('2',$channel['apptype']) && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif($device=='mobile' && (in_array('2',$channel['apptype']) && $channel['appwxa']>0 || in_array('3',$channel['apptype']))){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//通用下单
	static private function addOrder($pay_type, $appid = null, $openid = null){
		global $siteurl, $channel, $order, $ordername, $clientip, $conf;

		require(PAY_ROOT."inc/PayService.class.php");

		if($channel['appswitch'] == 1){
			$apiurl = 'https://hlwnets-test.fuioupay.com/aggpos/order.fuiou';
		}else{
			$apiurl = 'https://hlwnets.fuioupay.com/aggpos/order.fuiou';
		}
		$param = [
			'order_date' => date('Ymd'),
			'order_id' => TRADE_NO,
			'order_amt' => strval($order['realmoney']*100),
			'order_pay_type' => $pay_type,
			'back_notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'goods_name' => $ordername,
			'goods_detail' => $ordername,
		];
		if($appid) $param['appid'] = $appid;
		if($openid) $param['openid'] = $openid;

		$client = new PayService($channel['appid'],$channel['appkey'],$channel['appsecret']);

		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $apiurl, $param) {
			$result = $client->submit($apiurl, $param);
			return $result['order_info'];
		});
	}

	//支付宝扫码支付
	static public function alipay(){
		global $channel, $device, $mdevice, $siteurl;
		if(in_array('2',$channel['apptype']) && !in_array('1',$channel['apptype'])){
			$code_url = $siteurl.'pay/alipayjs/'.TRADE_NO.'/';
		}else{
			try{
				$code_url = self::addOrder('ALIPAY');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'支付宝下单失败！'.$ex->getMessage()];
			}
		}

		if(checkalipay() || $mdevice=='alipay'){
			return ['type'=>'jump','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
		}
	}

	static public function alipayjs(){
		global $method, $order;
		if(!empty($order['sub_openid'])){
			$user_id = $order['sub_openid'];
		}else{
			[$user_type, $user_id] = alipay_oauth();
		}

		$blocks = checkBlockUser($user_id, TRADE_NO);
		if($blocks) return $blocks;

		if($user_type == 'openid'){
			return ['type'=>'error','msg'=>'支付宝快捷登录获取uid失败，需将用户标识切换到uid模式'];
		}

		try{
			$payinfo = self::addOrder('FWC', null, $user_id);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝下单失败！'.$ex->getMessage()];
		}
		$payinfo = json_decode($payinfo, true);
		if($method == 'jsapi'){
			return ['type'=>'jsapi','data'=>$payinfo['tradeNO']];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'alipay_jspay','data'=>['alipay_trade_no'=>$payinfo['tradeNO'], 'redirect_url'=>$redirect_url]];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $siteurl, $device, $mdevice;
		if(in_array('1',$channel['apptype'])){
			try{
				$code_url = self::addOrder('WECHAT');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
		}elseif(in_array('2',$channel['apptype'])){
			if($channel['appwxmp']>0){
				$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			}else{
				$code_url = $siteurl.'pay/wxwappay/'.TRADE_NO.'/';
			}
		}elseif(in_array('3',$channel['apptype'])){
			$code_url = $siteurl.'pay/wxwappay/'.TRADE_NO.'/';
		}

		if($mdevice == 'wechat' || checkwechat()){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile()) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信公众号支付
	static public function wxjspay(){
		global $siteurl, $channel, $order, $ordername, $method;

		//①、获取用户openid
		if(!empty($order['sub_openid'])){
			if(!empty($order['sub_appid'])){
				$wxinfo['appid'] = $order['sub_appid'];
			}else{
				if($order['is_applet'] == 1){
					$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
					if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信小程序不存在'];
				}else{
					$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
					if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
				}
			}
			$openid = $order['sub_openid'];
		}else{
			$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
			if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
			$openid = wechat_oauth($wxinfo);
		}
		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks) return $blocks;

		//②、统一下单
		try{
			$payinfo = self::addOrder($order['is_applet'] == 1 ? 'LETPAY' : 'JSAPI', $wxinfo['appid'], $openid);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}
		if($method == 'jsapi'){
			return ['type'=>'jsapi','data'=>$payinfo];
		}
		
		if($_GET['d']==1){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$payinfo, 'redirect_url'=>$redirect_url]];
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		if(in_array('3',$channel['apptype'])){
			try{
				$token = self::addOrder('TAPPLET');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
			$code_url = 'weixin://dl/business/?appid=wx2877080d739b734a&path=pages/qrPay/qrPay&query=t%3D'.$token;
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
		}elseif($channel['appwxa']>0){
			$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
			if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信小程序不存在'];
			try{
				$code_url = wxminipay_jump_scheme($wxinfo['id'], TRADE_NO);
			}catch(Exception $e){
				return ['type'=>'error','msg'=>$e->getMessage()];
			}
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
		}else{
			return self::wxpay();
		}
	}
	
	//微信小程序支付
	static public function wxminipay(){
		global $siteurl, $channel, $order, $ordername, $conf;

		$code = isset($_GET['code'])?trim($_GET['code']):exit('{"code":-1,"msg":"code不能为空"}');
		
		//①、获取用户openid
		$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
		if(!$wxinfo)exit('{"code":-1,"msg":"支付通道绑定的微信小程序不存在"}');
		try{
			$openid = wechat_applet_oauth($code, $wxinfo);
		}catch(Exception $e){
			exit('{"code":-1,"msg":"'.$e->getMessage().'"}');
		}
		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks)exit('{"code":-1,"msg":"'.$blocks['msg'].'"}');

		//②、统一下单
		try{
			$jsApiParameters = self::addOrder('LETPAY', $wxinfo['appid'], $openid);
		}catch(Exception $ex){
			exit('{"code":-1,"msg":"'.$ex->getMessage().'"}');
		}

		exit(json_encode(['code'=>0, 'data'=>json_decode($jsApiParameters, true)]));
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::addOrder('UNIONPAY');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'银联云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$json = file_get_contents('php://input');
		$data = json_decode($json,true);
		if(!$data) return ['type'=>'html','data'=>'no data'];

		require(PAY_ROOT."inc/PayService.class.php");
		
		$client = new PayService($channel['appid'],$channel['appkey'],$channel['appsecret']);
		$arr = $client->decryptNotify($data['message']);

        if ($arr) {
			if($arr['order_st'] == 1){
				$out_trade_no = $arr['order_id'];
				$trade_no = $arr['order_fas_ssn'];
				$money = $arr['order_amt'];
				$buyer = $arr['openid'];
				$bill_trade_no = $arr['pay_ssn'];
				$bill_mch_trade_no = $arr['fy_order_id'];
				if($out_trade_no == TRADE_NO){
					processNotify($order, $trade_no, $buyer, $bill_trade_no, $bill_mch_trade_no);
				}
				return ['type'=>'html','data'=>'success'];
			}
			return ['type'=>'html','data'=>'status_fail'];
        }else{
			return ['type'=>'html','data'=>'decrypt_fail'];
		}

	}

	//同步回调
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT."inc/PayService.class.php");

		if($channel['appswitch'] == 1){
			$apiurl = 'https://refund-transfer-test.fuioupay.com/refund_transfer/aggposRefund.fuiou';
		}else{
			$apiurl = 'https://refund-transfer.fuioupay.com/refund_transfer/aggposRefund.fuiou';
		}

		$param = [
			'refund_order_date' => date('Ymd'),
			'refund_order_id' => $order['refund_no'],
			'pay_order_date' => date('Ymd', strtotime($order['addtime'])),
			'pay_order_id' => $order['trade_no'],
			'refund_amt' => strval($order['refundmoney']*100),
		];

		$client = new PayService($channel['appid'],$channel['appkey'],$channel['appsecret']);

		try{
			$result = $client->submit($apiurl, $param);

			return ['code'=>0, 'trade_no'=>$result['pay_order_id'], 'refund_fee'=>$result['refund_amt']];
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}

}