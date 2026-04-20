<?php

/**
 * http://fundwx.fuiou.com/doc/#/aggregatePay/
 */
class fuiou_plugin
{
	static public $info = [
		'name'        => 'fuiou', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '富友支付(前置商户)', //支付插件显示名称
		'author'      => '富友', //支付插件作者
		'link'        => 'https://www.fuiou.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '订单号前缀',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => null,
		'select_alipay' => [
			'1' => '扫码支付',
			'2' => 'JS支付',
		],
		'select_wxpay' => [
			'1' => '扫码支付',
			'2' => '公众号/小程序支付',
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
			}elseif(checkmobile() && !checkwechat() && in_array('2',$channel['apptype']) && $channel['appwxa']>0){
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
			}elseif($device=='mobile' && $mdevice!='wechat' && in_array('2',$channel['apptype']) && $channel['appwxa']>0){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//通用下单
	static private function addOrder($pay_type){
		global $siteurl, $channel, $order, $ordername, $clientip, $conf;

		$apiurl = 'https://aipay-cloud.fuioupay.com/aggregatePay/preCreate';
		$param = [
			'version' => '1.0',
			'mchnt_cd' => $channel['appid'],
			'random_str' => random(32),
			'order_type' => $pay_type,
			'order_amt' => strval($order['realmoney']*100),
			'mchnt_order_no' => $channel['appurl'].TRADE_NO,
			'txn_begin_ts' => date('YmdHis'),
			'goods_des' => $ordername,
			'term_id' => rand(10000000,99999999).'',
			'term_ip' => $clientip,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
		];

		$param_ord = ['mchnt_cd', 'order_type', 'order_amt', 'mchnt_order_no', 'txn_begin_ts', 'goods_des', 'term_id', 'term_ip', 'notify_url', 'random_str', 'version'];
		$signStr = '';
		foreach($param_ord as $key){
			$signStr .= $param[$key] . '|';
		}
		$signStr .= $channel['appkey'];
		$param['sign'] = md5($signStr);

		return \lib\Payment::lockPayData(TRADE_NO, function() use($apiurl, $param) {
			$data = get_curl($apiurl, json_encode($param), 0, 0, 0, 0, 0, ['Content-Type: application/json']);

			$result = json_decode($data, true);

			if(isset($result['result_code']) && $result['result_code']=='000000'){
				$code_url = $result['qr_code'];
			}else{
				throw new Exception($result['result_msg']?$result['result_msg']:'返回数据解析失败');
			}
			return $code_url;
		});
	}

	//公众号小程序下单
	static private function jspay($trade_type, $sub_openid, $sub_appid = null){
		global $siteurl, $channel, $order, $ordername, $clientip, $conf;

		$apiurl = 'https://aipay-cloud.fuioupay.com/aggregatePay/wxPreCreate';
		$param = [
			'version' => '1.0',
			'mchnt_cd' => $channel['appid'],
			'random_str' => random(32),
			'order_amt' => strval($order['realmoney']*100),
			'mchnt_order_no' => $channel['appurl'].TRADE_NO,
			'txn_begin_ts' => date('YmdHis'),
			'goods_des' => $ordername,
			'term_id' => rand(10000000,99999999).'',
			'term_ip' => $clientip,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'trade_type' => $trade_type,
			'sub_openid' => $sub_openid,
		];
		if($sub_appid){
			$param['sub_appid'] = $sub_appid;
		}

		$param_ord = ['mchnt_cd', 'trade_type', 'order_amt', 'mchnt_order_no', 'txn_begin_ts', 'goods_des', 'term_id', 'term_ip', 'notify_url', 'random_str', 'version'];
		$signStr = '';
		foreach($param_ord as $key){
			$signStr .= $param[$key] . '|';
		}
		$signStr .= $channel['appkey'];
		$param['sign'] = md5($signStr);

		return \lib\Payment::lockPayData(TRADE_NO, function() use($apiurl, $param) {
			$data = get_curl($apiurl, json_encode($param), 0, 0, 0, 0, 0, ['Content-Type: application/json']);

			$result = json_decode($data, true);

			if(isset($result['result_code']) && $result['result_code']=='000000'){
				$code_url = $result;
			}else{
				throw new Exception($result['result_msg']?$result['result_msg']:'返回数据解析失败');
			}
			return $code_url;
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
			$result = self::jspay('FWC', $user_id);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝下单失败！'.$ex->getMessage()];
		}
		if($method == 'jsapi'){
			return ['type'=>'jsapi','data'=>$result['reserved_transaction_id']];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'alipay_jspay','data'=>['alipay_trade_no'=>$result['reserved_transaction_id'], 'redirect_url'=>$redirect_url]];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $siteurl, $device, $mdevice;
		if(in_array('2',$channel['apptype']) && !in_array('1',$channel['apptype'])){
			if($channel['appwxmp']>0){
				$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			}else{
				$code_url = $siteurl.'pay/wxwappay/'.TRADE_NO.'/';
			}
		}else{
			try{
				$code_url = self::addOrder('WECHAT');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
		}

		if($mdevice == 'wechat' || checkwechat()){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile()) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信小程序支付
	static public function wxminipay(){
		global $siteurl, $channel;

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
			$result = self::jspay('LETPAY', $openid, $wxinfo['appid']);
		}catch(Exception $ex){
			exit('{"code":-1,"msg":"微信支付下单失败！'.$ex->getMessage().'"}');
		}

		$payinfo = ['appId'=>$result['sdk_appid'], 'timeStamp'=>$result['sdk_timestamp'], 'nonceStr'=>$result['sdk_noncestr'], 'package'=>$result['sdk_package'], 'signType'=>$result['sdk_signtype'], 'paySign'=>$result['sdk_paysign']];

		exit(json_encode(['code'=>0, 'data'=>$payinfo]));
	}

	//微信公众号支付
	static public function wxjspay(){
		global $siteurl, $channel, $order, $method, $conf, $clientip;

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
			$result = self::jspay($order['is_applet'] == 1 ? 'LETPAY' : 'JSAPI', $openid, $wxinfo['appid']);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

        $payinfo = ['appId'=>$result['sdk_appid'], 'timeStamp'=>$result['sdk_timestamp'], 'nonceStr'=>$result['sdk_noncestr'], 'package'=>$result['sdk_package'], 'signType'=>$result['sdk_signtype'], 'paySign'=>$result['sdk_paysign']];

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
		//file_put_contents('logs.txt', $json);
		$arr = json_decode($json,true);

		$param_ord = ['mchnt_cd', 'mchnt_order_no', 'settle_order_amt', 'order_amt', 'txn_fin_ts', 'reserved_fy_settle_dt', 'random_str'];
		$signStr = '';
		foreach($param_ord as $key){
			$signStr .= $arr[$key] . '|';
		}
		$signStr .= $channel['appkey'];
		$sign = md5($signStr);

        if ($sign === $arr['sign']) {
			$out_trade_no = substr($arr['mchnt_order_no'],strlen($channel['appurl']));
			$trade_no = $arr['transaction_id'];
			$money = $arr['order_amt'];
			$buyer = $arr['reserved_buyer_logon_id'];
			$bill_mch_trade_no = $arr['reserved_channel_order_id'];
			if($out_trade_no == TRADE_NO){
				processNotify($order, $trade_no, $buyer, null, $bill_mch_trade_no);
			}
			return ['type'=>'html','data'=>'1'];
        }else{
			return ['type'=>'html','data'=>'0'];
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

		$apiurl = 'https://aipay-cloud.fuioupay.com/aggregatePay/commonRefund';

		if($order['type'] == 1) $pay_type = 'ALIPAY';
		else if($order['type'] == 2) $pay_type = 'WECHAT';
		else if($order['type'] == 4) $pay_type = 'UNIONPAY';

		$param = [
			'version' => '1.0',
			'mchnt_cd' => $channel['appid'],
			'term_id' => rand(10000000,99999999).'',
			'random_str' => random(32),
			'mchnt_order_no' => $channel['appurl'].$order['trade_no'],
			'refund_order_no' => $channel['appurl'].$order['refund_no'],
			'order_type' => $pay_type,
			'total_amt' => strval($order['realmoney']*100),
			'refund_amt' => strval($order['refundmoney']*100),
		];

		$param_ord = ['mchnt_cd', 'order_type', 'mchnt_order_no', 'refund_order_no', 'total_amt', 'refund_amt', 'term_id', 'random_str', 'version'];
		$signStr = '';
		foreach($param_ord as $key){
			$signStr .= $param[$key] . '|';
		}
		$signStr .= $channel['appkey'];
		$param['sign'] = md5($signStr);

		$data = get_curl($apiurl, json_encode($param), 0, 0, 0, 0, 0, ['Content-Type: application/json']);
		$result = json_decode($data, true);

		if($result["result_code"]=='000000'){
			$result = ['code'=>0, 'trade_no'=>$result['mchnt_order_no'], 'refund_fee'=>$result['reserved_refund_amt']];
		}else{
			$result = ['code'=>-1, 'msg'=>$result["result_msg"]];
		}
		return $result;
	}

}