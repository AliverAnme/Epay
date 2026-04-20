<?php

class lakalamoss_plugin
{
	static public $info = [
		'name'        => 'lakalamoss', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '拉卡拉MOSS', //支付插件显示名称
		'author'      => '拉卡拉', //支付插件作者
		'link'        => 'https://moss.lakala.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'transtypes'  => ['bank'], //支付插件支持的转账方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'APPID',
				'type' => 'input',
				'note' => 'reqId',
			],
			'appsecret' => [
				'name' => '客户私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appmchid' => [
				'name' => '商户ID',
				'type' => 'input',
				'note' => '',
			],
			'splitmchid' => [
				'name' => '合单支付商户ID',
				'type' => 'input',
				'note' => '不使用合单支付请留空',
			],
		],
		'select' => null,
		'select_alipay' => [
			'1' => '主扫支付',
			'2' => 'JSAPI支付',
		],
		'select_wxpay' => [
			'1' => '主扫支付',
			'2' => 'JSAPI支付',
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
			if(checkwechat() && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif(checkmobile() && $channel['appwxa']>0){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
			}
		}elseif($order['typename']=='bank'){
			return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice, $method;

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
			if($mdevice=='wechat' && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif($device=='mobile' && $channel['appwxa']>0){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//订单支付
	static private function addOrder($pay_scene, $account_type, $trans_type = null, $openid = null, $appid = null){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require_once(PAY_ROOT."inc/MossClient.php");

		$params = [
			'mer_no' => $channel['appmchid'],
			'order_no' => TRADE_NO,
			'total_amount' => strval(round($order['realmoney']*100)),
			'pay_scene' => $pay_scene,
			'account_type' => $account_type,
			'subject' => $ordername,
			'notify_url' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
		];
		if($pay_scene == '0'){
			$params['callback_url'] = $siteurl . 'pay/return/' . TRADE_NO . '/';
		}else{
			$params['trans_type'] = $trans_type;
			$params['location_info'] = [
				'request_ip' => $clientip
			];
			if($openid) $params['user_id'] = $openid;
			if($appid) $params['acc_busi_fields']['sub_appid'] = $appid;
		}
		if(!empty($channel['splitmchid'])){
			$params['split_info'] = [[
				'mer_no' => $channel['splitmchid'],
				'amount' => strval(round(($order['realmoney']-0.01)*100)),
			],[
				'mer_no' => $channel['appmchid'],
				'amount' => strval(round(0.01*100)),
			]];
		}

		$client = new MossClient($channel['appid'],$channel['appsecret']);
		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $params) {
			$result = $client->execute('lfops.moss.order.pay', $params);
			\lib\Payment::updateOrder(TRADE_NO, $result['pay_serial']);
			return $result;
		});
	}

	//支付宝扫码支付
	static public function alipay(){
		global $channel, $device, $mdevice, $siteurl;
		if(in_array('2',$channel['apptype']) && !in_array('1',$channel['apptype'])){
			$code_url = $siteurl.'pay/alipayjs/'.TRADE_NO.'/';
		}else{
			try{
				$result = self::addOrder('1', 'ALIPAY', '41');
				$code_url = $result['acc_resp_fields']['code'];
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
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
			$result = self::addOrder('1', 'ALIPAY', '51', $user_id);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}
		if($method == 'jsapi'){
			return ['type'=>'jsapi','data'=>$result['prepay_id']];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'alipay_jspay','data'=>['alipay_trade_no'=>$result['prepay_id'], 'redirect_url'=>$redirect_url]];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $siteurl, $device, $mdevice;

		if(in_array('2',$channel['apptype']) && !in_array('1',$channel['apptype'])){
			if($channel['appwxa']>0 && $channel['appwxmp']==0){
				$code_url = $siteurl.'pay/wxwappay/'.TRADE_NO.'/';
			}else{
				$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			}
			if(checkwechat() || $mdevice == 'wechat'){
				return ['type'=>'jump','url'=>$code_url];
			} elseif (checkmobile() || $device == 'mobile') {
				return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
			} else {
				return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
			}
		}else{
			try{
				$result = self::addOrder('0', 'WECHAT');
				$code_url = $result['counter_url'];
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
			if (checkmobile() || $device == 'mobile') {
				return ['type'=>'jump','url'=>$code_url];
			} else {
				return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
			}
		}
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl, $channel, $order;

        $wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
		if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信小程序不存在'];
		try {
			$code_url = wxminipay_jump_scheme($wxinfo['id'], TRADE_NO);
		} catch (Exception $e) {
			return ['type'=>'error','msg'=>$e->getMessage()];
		}
		return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
	}

	//微信公众号支付
	static public function wxjspay(){
		global $siteurl, $channel, $order, $method;

		//①、获取用户openid
		if(!empty($order['sub_openid'])){
			if(!empty($order['sub_appid'])){
				$wxinfo['appid'] = $order['sub_appid'];
			}else{
				$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
				if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
			}
			$openid = $order['sub_openid'];
		}else{
			$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
			if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
			try{
				$tools = new \WeChatPay\JsApiTool($wxinfo['appid'], $wxinfo['appsecret']);
				$openid = $tools->GetOpenid();
			}catch(Exception $e){
				return ['type'=>'error','msg'=>$e->getMessage()];
			}
		}
		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks) return $blocks;

		//②、统一下单
		try{
			$result = self::addOrder('1', 'WECHAT', '51', $openid, $wxinfo['appid']);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}
		$payinfo = ['appId'=>$result['acc_resp_fields']['app_id'], 'timeStamp'=>$result['acc_resp_fields']['time_stamp'], 'nonceStr'=>$result['acc_resp_fields']['nonce_str'], 'package'=>$result['acc_resp_fields']['package'], 'signType'=>$result['acc_resp_fields']['sign_type'], 'paySign'=>$result['acc_resp_fields']['pay_sign']];
		if($method == 'jsapi'){
			return ['type'=>'jsapi','data'=>json_encode($payinfo)];
		}

		if($_GET['d']==1){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>json_encode($payinfo), 'redirect_url'=>$redirect_url]];
	}

	//微信小程序支付
	static public function wxminipay(){
		global $siteurl, $channel;

		$code = isset($_GET['code'])?trim($_GET['code']):exit('{"code":-1,"msg":"code不能为空"}');
		
		//①、获取用户openid
		$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
		if(!$wxinfo)exit('{"code":-1,"msg":"支付通道绑定的微信小程序不存在"}');
		try{
			$tools = new \WeChatPay\JsApiTool($wxinfo['appid'], $wxinfo['appsecret']);
			$openid = $tools->AppGetOpenid($code);
		}catch(Exception $e){
			exit('{"code":-1,"msg":"'.$e->getMessage().'"}');
		}
		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks)exit('{"code":-1,"msg":"'.$blocks['msg'].'"}');
		
		//②、统一下单
		try{
			$result = self::addOrder('1', 'WECHAT', '71', $openid, $wxinfo['appid']);
		}catch(Exception $ex){
			exit('{"code":-1,"msg":"微信支付下单失败！'.$ex->getMessage().'"}');
		}
		$payinfo = ['appId'=>$result['acc_resp_fields']['app_id'], 'timeStamp'=>$result['acc_resp_fields']['time_stamp'], 'nonceStr'=>$result['acc_resp_fields']['nonce_str'], 'package'=>$result['acc_resp_fields']['package'], 'signType'=>$result['acc_resp_fields']['sign_type'], 'paySign'=>$result['acc_resp_fields']['pay_sign']];

		exit(json_encode(['code'=>0, 'data'=>$payinfo]));
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$result = self::addOrder('1', 'UQRCODEPAY', '41');
			$code_url = $result['acc_resp_fields']['code'];
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$json = file_get_contents('php://input');
		$arr = json_decode($json, true);
		if(!$arr) return ['type'=>'html','data'=>'no data'];

		require_once(PAY_ROOT."inc/MossClient.php");
		
		try{
			$client = new MossClient($channel['appid'],$channel['appsecret']);
			$data = $client->notify($arr);
		}catch(Exception $ex){
			$client->echoNotify($arr['head'], false, $ex->getMessage());
		}

		if ($data['trade_state'] == 'SUCCESS') {
			$out_trade_no = $data['order_no'];
			$api_trade_no = $data['pay_serial'];
			$money = $data['total_amount'];
			$buyer = $data['user_id2'];
			$bill_trade_no = $data['acc_trade_no'];
			if($out_trade_no == TRADE_NO){
				processNotify($order, $api_trade_no, $buyer, $bill_trade_no);
			}
		}
		$client->echoNotify($arr['head'], true);
	}

	//同步回调
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//支付成功页面
	static public function ok(){
		return ['type'=>'page','page'=>'ok'];
	}
	
	//退款
	static public function refund($order){
		global $channel, $clientip;
		if(empty($order))exit();

		require_once(PAY_ROOT."inc/MossClient.php");

		$params = [
			'order_no' => $order['refund_no'],
			'origin_order_no' => $order['trade_no'],
			'refund_amount' => strval(round($order['refundmoney']*100)),
			'refund_reason' => '订单退款',
			'location_info' => [
				'request_ip' => $clientip
			]
		];
		
		try{
			$client = new MossClient($channel['appid'],$channel['appsecret']);
			$result = $client->execute('lfops.moss.order.ref', $params);
			return ['code'=>0];
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}

	//投诉回调
	static public function complainnotify(){
		global $channel, $order;

		$json = file_get_contents('php://input');
		$arr = json_decode($json, true);
		if(!$arr) return ['type'=>'html','data'=>'no data'];

		require_once(PAY_ROOT."inc/MossClient.php");
		
		try{
			$client = new MossClient($channel['appid'],$channel['appsecret']);
			$data = $client->notify($arr);
		}catch(Exception $ex){
			$client->echoNotify($arr['head'], false, $ex->getMessage());
		}

		if($arr['account_type'] == 'ALIPAY') $channel['type'] = 1;
		else $channel['type'] = 2;
		$model = \lib\Complain\CommUtil::getModel($channel);
		if($arr['account_type'] == 'ALIPAY') $type = $arr['wx_data']['action_type'];
		else $type = $arr['zfb_data']['message_type'];
		$model->refreshNewInfo($arr['complaint_id'], $type);

		$client->echoNotify($arr['head'], true);
	}
}