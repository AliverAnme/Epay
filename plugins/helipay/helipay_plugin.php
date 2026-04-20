<?php

//http://xianshang-doc.helipay.com/server/?s=/api/extLogin/bySecretKey&username=pu_user&time=2025060415&token=b2ba7e9c5db86737d5e43fb5e46dabc9
class helipay_plugin
{
	static public $info = [
		'name'        => 'helipay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '合利宝', //支付插件显示名称
		'author'      => '合利宝', //支付插件作者
		'link'        => 'http://www.helipay.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','qqpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户编号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '扫码产品-签名密钥',
				'type' => 'input',
				'note' => '',
			],
			'appsecret' => [
				'name' => '扫码产品-加密密钥',
				'type' => 'input',
				'note' => '',
			],
			'public_signkey' => [
				'name' => '公共产品-签名密钥',
				'type' => 'input',
				'note' => '',
			],
			'public_enckey' => [
				'name' => '公共产品-加密密钥',
				'type' => 'input',
				'note' => '',
			],
			'settle_signkey' => [
				'name' => '结算产品-MD5签名密钥',
				'type' => 'input',
				'note' => '',
			],
			'settle_signkey2' => [
				'name' => '结算产品-RSA签名私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'accpay_signkey' => [
				'name' => '虚拟账户支付-签名密钥',
				'type' => 'input',
				'note' => '',
			],
			'accpay_enckey' => [
				'name' => '虚拟账户支付-加密密钥',
				'type' => 'input',
				'note' => '',
			],
			'appmchid' => [
				'name' => '子商户号',
				'type' => 'input',
				'note' => '留空为使用商户编号发起支付',
			],
			'reportid' => [
				'name' => '扫码支付-报备ID',
				'type' => 'input',
				'note' => '可留空，多个报备ID可用,隔开',
			],
			'reportid2' => [
				'name' => '公众号支付-报备ID',
				'type' => 'input',
				'note' => '可留空，多个报备ID可用,隔开',
			],
			'reportid3' => [
				'name' => 'H5支付-报备ID',
				'type' => 'input',
				'note' => '可留空，多个报备ID可用,隔开',
			],
			'split_pubkey' => [
				'name' => '分账产品-SM2平台公钥',
				'type' => 'textarea',
				'note' => 'hex格式',
			],
			'split_prikey' => [
				'name' => '分账产品-SM2商户私钥',
				'type' => 'textarea',
				'note' => 'hex格式',
			],
		],
		'select' => null,
		'select_alipay' => [
			'1' => '扫码支付',
			'2' => '生活号支付',
		],
		'select_wxpay' => [
			'1' => '扫码支付',
			'2' => '公众号/小程序支付',
			'3' => 'H5支付',
		],
		'note' => '', //支付密钥填写说明
		'bindwxmp' => true, //是否支持绑定微信公众号
		'bindwxa' => true, //是否支持绑定微信小程序
	];

	const API_URL = 'http://pay.trx.helipay.com/trx/app/interface.action';

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
			}elseif(checkmobile() && (in_array('3',$channel['apptype']) || in_array('2',$channel['apptype']) && $channel['appwxa']>0)){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
			}
		}elseif($order['typename']=='qqpay'){
			return ['type'=>'jump','url'=>'/pay/qqpay/'.TRADE_NO.'/'];
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
			if($mdevice=='wechat' && in_array('2',$channel['apptype']) && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif($device=='mobile' && (in_array('3',$channel['apptype']) || in_array('2',$channel['apptype']) && $channel['appwxa']>0)){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='qqpay'){
			return self::qqpay();
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	static private function make_sign($param, $key){
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign"){
				$signstr .= '&'.$v;
			}
		}
		$signstr .= '&'.$key;
		$sign = md5($signstr);
		return $sign;
	}

	static private function getReportId($reportid){
		global $order;
		if(!empty($order['param']) && is_numeric($order['param'])){
			return $order['param'];
		}
		if(strpos($reportid, ',')){
            $reportids = explode(',', $reportid);
            $reportid = $reportids[array_rand($reportids)];
        }
		global $DB;
		$DB->update('order', ['param'=>$reportid], ['trade_no'=>TRADE_NO]);
		return $reportid;
	}

	//扫码支付预下单
	static private function qrcode($pay_type){
		global $siteurl, $conf, $channel, $order, $ordername, $clientip;

		require(PAY_ROOT."inc/DES3.class.php");

		$params = [
			'P1_bizType' => 'AppPay',
			'P2_orderId' => TRADE_NO,
			'P3_customerNumber' => $channel['appid'],
			'P4_payType' => 'SCAN',
			'P5_orderAmount' => $order['realmoney'],
			'P6_currency' => 'CNY',
			'P7_authcode' => '1',
			'P8_appType' => $pay_type,
			'P9_notifyUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'P10_successToUrl' => '',
			'P11_orderIp' => $clientip,
			'P12_goodsName' => $ordername,
			'P13_goodsDetail' => '',
			'P14_desc' => '',
		];
		if(!empty($channel['appmchid'])) $params['P3_customerNumber'] = $channel['appmchid'];
		$params['sign'] = self::make_sign($params, $channel['appkey']);
		$params['signatureType'] = 'MD5';
		if(!empty($channel['reportid'])) $params['P15_subMerchantId'] = self::getReportId($channel['reportid']);
		if($order['profits'] > 0){
			self::handleProfits($params);
		}
		
		return \lib\Payment::lockPayData(TRADE_NO, function() use($params) {
			$response = get_curl(self::API_URL, http_build_query($params));
			$result = json_decode($response, true);
			if(isset($result["rt2_retCode"]) && ($result["rt2_retCode"]=='0000' || $result["rt2_retCode"]=='0001')){
				\lib\Payment::updateOrder(TRADE_NO, $result['rt6_serialNumber']);
				return $result['rt8_qrcode'];
			}else{
				throw new Exception($result["rt3_retMsg"]?$result["rt3_retMsg"]:'返回数据解析失败');
			}
		});
	}

	//公众号支付预下单
	static private function publicpay($pay_type, $appid, $openid){
		global $siteurl, $conf, $channel, $order, $ordername, $clientip;

		require(PAY_ROOT."inc/DES3.class.php");

		$params = [
			'P1_bizType' => 'AppPayPublic',
			'P2_orderId' => TRADE_NO,
			'P3_customerNumber' => $channel['appid'],
			'P4_payType' => 'PUBLIC',
			'P5_appid' => $appid,
			'P6_deviceInfo' => 'WEB',
			'P7_isRaw' => $openid == '1' ? '0' : '1',
			'P8_openid' => $openid,
			'P9_orderAmount' => $order['realmoney'],
			'P10_currency' => 'CNY',
			'P11_appType' => $pay_type,
			'P12_notifyUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'P13_successToUrl' => $siteurl . 'pay/return/' . TRADE_NO . '/',
			'P14_orderIp' => $clientip,
			'P15_goodsName' => $ordername,
			'P16_goodsDetail' => '',
			'P17_limitCreditPay' => '',
			'P18_desc' => '',
		];
		if(!empty($channel['appmchid'])) $params['P3_customerNumber'] = $channel['appmchid'];
		$params['sign'] = self::make_sign($params, $channel['appkey']);
		$params['signatureType'] = 'MD5';
		if(!empty($channel['reportid2'])) $params['P20_subMerchantId'] = self::getReportId($channel['reportid2']);
		if($order['profits'] > 0){
			self::handleProfits($params);
		}
		
		return \lib\Payment::lockPayData(TRADE_NO, function() use($params) {
			$response = get_curl(self::API_URL, http_build_query($params));
			$result = json_decode($response, true);
			if(isset($result["rt2_retCode"]) && ($result["rt2_retCode"]=='0000' || $result["rt2_retCode"]=='0001')){
				\lib\Payment::updateOrder(TRADE_NO, $result['rt6_serialNumber']);
				return $result['rt10_payInfo'];
			}else{
				throw new Exception($result["rt3_retMsg"]?$result["rt3_retMsg"]:'返回数据解析失败');
			}
		});
	}

	//小程序支付预下单
	static private function appletpay($pay_type, $appid, $openid){
		global $siteurl, $conf, $channel, $order, $ordername, $clientip;

		require(PAY_ROOT."inc/DES3.class.php");

		$params = [
			'P1_bizType' => 'AppPayApplet',
			'P2_orderId' => TRADE_NO,
			'P3_customerNumber' => $channel['appid'],
			'P4_payType' => 'APPLET',
			'P5_appid' => $appid,
			'P6_deviceInfo' => 'WEB',
			'P7_isRaw' => '1',
			'P8_openid' => $openid,
			'P9_orderAmount' => $order['realmoney'],
			'P10_currency' => 'CNY',
			'P11_appType' => $pay_type,
			'P12_notifyUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'P13_successToUrl' => $siteurl . 'pay/return/' . TRADE_NO . '/',
			'P14_orderIp' => $clientip,
			'P15_goodsName' => $ordername,
			'P16_goodsDetail' => '',
			'P17_limitCreditPay' => '',
			'P18_desc' => '',
		];
		if(!empty($channel['appmchid'])) $params['P3_customerNumber'] = $channel['appmchid'];
		$params['sign'] = self::make_sign($params, $channel['appkey']);
		$params['signatureType'] = 'MD5';
		if(!empty($channel['reportid2'])) $params['P20_subMerchantId'] = self::getReportId($channel['reportid2']);
		if($order['profits'] > 0){
			self::handleProfits($params);
		}
		
		return \lib\Payment::lockPayData(TRADE_NO, function() use($params) {
			$response = get_curl(self::API_URL, http_build_query($params));
			$result = json_decode($response, true);
			if(isset($result["rt2_retCode"]) && ($result["rt2_retCode"]=='0000' || $result["rt2_retCode"]=='0001')){
				\lib\Payment::updateOrder(TRADE_NO, $result['rt6_serialNumber']);
				return $result['rt10_payInfo'];
			}else{
				throw new Exception($result["rt3_retMsg"]?$result["rt3_retMsg"]:'返回数据解析失败');
			}
		});
	}

	//H5支付预下单
	static private function h5pay($pay_type){
		global $siteurl, $conf, $channel, $order, $ordername, $clientip;

		require(PAY_ROOT."inc/DES3.class.php");

		$params = [
			'P1_bizType' => 'AppPayH5WFT',
			'P2_orderId' => TRADE_NO,
			'P3_customerNumber' => $channel['appid'],
			'P4_orderAmount' => $order['realmoney'],
			'P5_currency' => 'CNY',
			'P6_orderIp' => $clientip,
			'P7_notifyUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'P8_appPayType' => $pay_type,
			'P9_payType' => 'WAP',
			'P10_appName' => $conf['sitename'],
			'P11_deviceInfo' => 'AND_WAP',
			'P12_applicationId' => $siteurl,
			'P13_goodsName' => $ordername,
			'P14_goodsDetail' => '',
			'P15_desc' => '',
		];
		if(!empty($channel['appmchid'])) $params['P3_customerNumber'] = $channel['appmchid'];
		$params['sign'] = self::make_sign($params, $channel['appkey']);
		$params['signatureType'] = 'MD5';
		if(!empty($channel['reportid3'])) $params['subMerchantId'] = self::getReportId($channel['reportid3']);
		$params['isRaw'] = '0';
		$params['nonRawMode'] = '1';
		$params['appId'] = 'wxd11af679e86cdf65';
		$params['successToUrl'] = $siteurl . 'pay/return/' . TRADE_NO . '/';
		if($order['profits'] > 0){
			self::handleProfits($param);
		}
		
		return \lib\Payment::lockPayData(TRADE_NO, function() use($params) {
			$response = get_curl(self::API_URL, http_build_query($params));
			$result = json_decode($response, true);
			if(isset($result["rt2_retCode"]) && ($result["rt2_retCode"]=='0000' || $result["rt2_retCode"]=='0001')){
				\lib\Payment::updateOrder(TRADE_NO, $result['rt6_serialNumber']);
				return $result['rt8_payInfo'];
			}else{
				throw new Exception($result["rt3_retMsg"]?$result["rt3_retMsg"]:'返回数据解析失败');
			}
		});
	}

	static private function handleProfits(&$param){
		global $order, $channel;
		$psreceiver = \lib\ProfitSharing\CommUtil::getReceiver($order['profits']);
		if($psreceiver && $psreceiver['mode'] == 0){
			$rules = [];
			foreach($psreceiver['info'] as $receiver){
				$psmoney = round(floor($order['realmoney'] * $receiver['rate']) / 100, 2);
				$rules[] = [
					'splitBillMerchantNo' => $receiver['account'],
					'splitBillAmount' => $psmoney,
				];
			}
			$param['splitBillType'] = 'FIXED_AMOUNT';
			$des = new DES3();
			$param['ruleJson'] = $des->encrypt2(json_encode($rules), $channel['appsecret']);
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		global $channel, $device, $mdevice, $siteurl;
		if(in_array('2',$channel['apptype']) && !in_array('1',$channel['apptype'])){
			$code_url = $siteurl.'pay/alipayjs/'.TRADE_NO.'/';
		}else{
			try{
				$code_url = self::qrcode('ALIPAY');
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
			$pay_info = self::publicpay('ALIPAY', '1', $user_id);
			$pay_info = json_decode($pay_info, true);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}
		if($method == 'jsapi'){
			return ['type'=>'jsapi','data'=>$pay_info['tradeNO']];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'alipay_jspay','data'=>['alipay_trade_no'=>$pay_info['tradeNO'], 'redirect_url'=>$redirect_url]];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $device, $mdevice, $siteurl;
		if(in_array('1',$channel['apptype'])){
			try{
				$code_url = self::qrcode('WXPAY');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
		}elseif(in_array('2',$channel['apptype'])){
			if($channel['appwxmp']>0){
				$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			}elseif($channel['appwxa']>0){
				$code_url = $siteurl.'pay/wxwappay/'.TRADE_NO.'/';
			}else{
				try{
					$code_url = self::publicpay('WXPAY', '1', '1');
				}catch(Exception $ex){
					return ['type'=>'error','msg'=>'微信支付下单失败 '.$ex->getMessage()];
				}
			}
		}else{
			try{
				$code_url = self::h5pay('WXPAY');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
		}

		if(checkwechat() || $mdevice == 'wechat'){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile() || $device == 'mobile') {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信公众号支付
	static public function wxjspay(){
		global $siteurl, $channel, $order, $ordername, $conf, $method;

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

		try{
			$pay_info = self::publicpay('WXPAY', $wxinfo['appid'], $openid);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败 '.$ex->getMessage()];
		}
		if($method == 'jsapi'){
			return ['type'=>'jsapi','data'=>$pay_info];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$pay_info, 'redirect_url'=>$redirect_url]];
	}

	//微信小程序支付
	static public function wxminipay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		$code = isset($_GET['code'])?trim($_GET['code']):exit('{"code":-1,"msg":"code不能为空"}');

		$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
		if(!$wxinfo)exit('{"code":-1,"msg":"支付通道绑定的微信小程序不存在"}');

		try{
			$openid = wechat_applet_oauth($code, $wxinfo);
		}catch(Exception $e){
			exit('{"code":-1,"msg":"'.$e->getMessage().'"}');
		}
		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks)exit('{"code":-1,"msg":"'.$blocks['msg'].'"}');

		try{
			$pay_info = self::appletpay('WXPAY', $wxinfo['appid'], $openid);
		}catch(Exception $ex){
			exit(json_encode(['code'=>-1, 'msg'=>'微信支付下单失败 '.$ex->getMessage()]));
		}

		exit(json_encode(['code'=>0, 'data'=>json_decode($pay_info, true)]));
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		if(in_array('3',$channel['apptype'])){
			try{
				$code_url = self::h5pay('WXPAY');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
			return ['type'=>'jump','url'=>$code_url];
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

	//QQ扫码支付
	static public function qqpay(){
		global $siteurl, $device, $mdevice;
		try{
			$code_url = self::qrcode('QQPAY');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败！'.$ex->getMessage()];
		}

		if(checkmobbileqq() || $mdevice == 'qq'){
			return ['type'=>'jump','url'=>$code_url];
		} elseif((checkmobile() || $device == 'mobile') && !isset($_GET['qrcode'])){
			return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$code_url];
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::qrcode('UNIONPAY');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$sign_param = [
			'rt1_customerNumber' => $_POST['rt1_customerNumber'],
			'rt2_orderId' => $_POST['rt2_orderId'],
			'rt3_systemSerial' => $_POST['rt3_systemSerial'],
			'rt4_status' => $_POST['rt4_status'],
			'rt5_orderAmount' => $_POST['rt5_orderAmount'],
			'rt6_currency' => $_POST['rt6_currency'],
			'rt7_timestamp' => $_POST['rt7_timestamp'],
			'rt8_desc' => $_POST['rt8_desc'],
		];
		$sign = self::make_sign($sign_param, $channel['appkey']);

		if($sign === $_POST["sign"]){
			if($_POST['rt4_status'] == 'SUCCESS'){
				$out_trade_no = $_POST['rt2_orderId'];
				$api_trade_no = $_POST['rt3_systemSerial'];
				$money = $_POST['rt5_orderAmount'];
				$buyer = $_POST['rt19_subOpenId'];
				$bill_trade_no = $_POST['rt17_outTransactionOrderId'];

				if ($out_trade_no == TRADE_NO) {
					processNotify($order, $api_trade_no, $buyer, $bill_trade_no);
				}
			}
			return ['type'=>'html','data'=>'success'];
		}else{
			return ['type'=>'html','data'=>'fail'];
		}
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel, $clientip;
		if(empty($order))exit();
		require(PAY_ROOT."inc/DES3.class.php");

		$params = [
			'P1_bizType' => 'AppPayRefund',
			'P2_orderId' => $order['trade_no'],
			'P3_customerNumber' => $channel['appid'],
			'P4_refundOrderId' => $order['refund_no'],
			'P5_amount' => $order['refundmoney'],
			'P6_callbackUrl' => '',
		];
		if(!empty($channel['appmchid'])) $params['P3_customerNumber'] = $channel['appmchid'];
		$params['sign'] = self::make_sign($params, $channel['appkey']);
		$params['signatureType'] = 'MD5';
		if($order['profits'] > 0){
			$psorder = \lib\ProfitSharing\CommUtil::getOrder($order['trade_no']);
			if($psorder && $psorder['rdata']){
				$leftmoney = (float)$order['refundmoney'];
				$rules = [];
				foreach($psorder['rdata'] as $receiver){
					$money = $receiver['money'] > $leftmoney ? $leftmoney : $receiver['money'];
					$rules[] = [
						'merchantNo' => $receiver['account'],
						'refundAmount' => round($money, 2),
					];
					$leftmoney -= $money;
					if($leftmoney <= 0) break;
				}
				if($leftmoney > 0){
					$rules[] = [
						'merchantNo' => $params['P3_customerNumber'],
						'refundAmount' => round($leftmoney, 2),
					];
				}
				$des = new DES3();
				$params['ruleJson'] = $des->encrypt2(json_encode($rules), $channel['appsecret']);
			}
		}
		
        $response = get_curl(self::API_URL, http_build_query($params));
		$result = json_decode($response, true);
		if(isset($result["rt2_retCode"]) && ($result["rt2_retCode"]=='0000' || $result["rt2_retCode"]=='0001')){
			return ['code'=>0, 'trade_no'=>$result['rt7_serialNumber'], 'refund_fee'=>$result['rt8_amount']];
        } elseif(isset($result['rt3_retMsg'])) {
			return ['code'=>-1, 'msg'=>$result['rt3_retMsg']];
		}else{
			return ['code'=>-1, 'msg'=>'未知错误'];
		}
	}

	//进件异步回调
	static public function applynotify(){
		global $channel, $order;
		require(PAY_ROOT."inc/DES3.class.php");

		$json = file_get_contents('php://input');
		$arr = json_decode($json, true);
		if(!$arr) return ['type'=>'html','data'=>'no data'];

		$sign = md5($arr['data'].'&'.$channel['public_signkey']);
		if($sign === $arr["sign"]){
			$des = new \DES3();
			$decrypted = $des->decrypt2($arr['data'], $channel['public_enckey']);
			if($decrypted){
				$data = json_decode($decrypted, true);
				$model = \lib\Applyments\CommUtil::getModel2($channel);
				if($model) $model->notify($data);
			}
			return ['type'=>'html','data'=>'success'];
		}else{
			return ['type'=>'html','data'=>'fail'];
		}
	}

	//结算异步回调
	static public function settlenotify(){
		global $channel, $order, $DB;

		$json = file_get_contents('php://input');
		$arr = json_decode($json, true);
		if(!$arr) return ['type'=>'html','data'=>'no data'];

		unset($arr['rt3_retMsg']);
		$sign = self::make_sign($arr, $channel['settle_signkey']);
		if($sign === $arr["sign"]){
			$records = json_decode($arr['rt4_settleRecords'], true);
			if(!empty($records)){
				$srow = $records[0];
				$trade_row = $DB->find('helipay_settle', '*', ['orderno'=>$srow['orderId']]);
				if($trade_row && $trade_row['status'] != $srow['orderStatus']){
					$DB->update('helipay_settle', [
						'money'=>$srow['settlementAmount'],
						'fee'=>$srow['settleFee'],
						'status'=>$srow['orderStatus'],
						'reason'=>$srow['reason'],
						'finishtime'=>$srow['completeDate']?$srow['completeDate']:null,
					], ['id'=>$trade_row['id']]);
				}
			}
			return ['type'=>'html','data'=>'success'];
		}else{
			return ['type'=>'html','data'=>'fail'];
		}
	}

	//转账异步回调
	static public function tradenotify(){
		global $channel, $order, $DB;

		if(!$_POST) return ['type'=>'html','data'=>'no data'];
		
		uksort($_POST, function ($a, $b) {
			if ($a == 'sign') return 1;
			if ($b == 'sign') return -1;
			$na = substr($a, 2, strpos($a, '_') - 2);
			$nb = substr($b, 2, strpos($b, '_') - 2);
			return $na - $nb;
		});
		$sign = self::make_sign($_POST, $channel['accpay_signkey']);
		if($sign === $_POST["sign"]){
			$trade_row = $DB->find('helipay_trade', '*', ['orderno'=>$_POST['rt7_orderId']]);
			if($trade_row && $trade_row['status'] != $_POST['rt10_orderStatus']){
				$DB->update('helipay_trade', [
					'status'=>$_POST['rt10_orderStatus'],
					'reason'=>$_POST['rt12_reason'],
					'endtime'=>date('Y-m-d H:i:s'),
				], ['id'=>$trade_row['id']]);
			}
			return ['type'=>'html','data'=>'success'];
		}else{
			return ['type'=>'html','data'=>'fail'];
		}
	}

	//投诉异步回调
	static public function complainnotify(){
		global $channel, $order;
		require(PAY_ROOT."inc/DES3.class.php");

		$json = file_get_contents('php://input');
		$arr = json_decode($json, true);
		if(!$arr) return ['type'=>'html','data'=>'no data'];

		$sign = md5($arr['data'].'&'.$channel['public_signkey']);
		if($sign === $arr["sign"]){
			$des = new \DES3();
			$decrypted = $des->decrypt2($arr['data'], $channel['public_enckey']);
			if($decrypted){
				$data = json_decode($decrypted, true);
				if($data['appPayType'] == 'ALIPAY') $channel['type'] = 1;
				else $channel['type'] = 2;
				$channel['appmchid'] = $data['merchantNo'];
				$model = \lib\Complain\CommUtil::getModel($channel);
				$model->refreshNewInfo($data['complaintId']);
			}
			return ['type'=>'html','data'=>'success'];
		}else{
			return ['type'=>'html','data'=>'fail'];
		}
	}
}