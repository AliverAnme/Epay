<?php

class douyinpay_plugin
{
	static public $info = [
		'name'        => 'douyinpay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '抖音支付', //支付插件显示名称
		'author'      => '抖音支付', //支付插件作者
		'link'        => 'https://pay.douyinpay.com/', //支付插件作者链接
		'types'       => ['douyinpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '应用ID',
				'type' => 'input',
				'note' => '抖音开放平台网站应用Client Key',
			],
			'appsecret' => [
				'name' => '应用Secret',
				'type' => 'input',
				'note' => '对应应用的Client Secret，仅JSAPI支付需要填写',
			],
			'appmchid' => [
				'name' => '商户号',
				'type' => 'input',
				'note' => '',
			],
			'apikey' => [
				'name' => '接口加密密钥',
				'type' => 'input',
				'note' => '',
			],
			'certserial' => [
				'name' => '商户API证书序列号',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => [ //选择已开启的支付方式
			'1' => 'Native支付',
			'2' => 'JSAPI支付',
			'3' => 'H5支付',
			'4' => 'APP支付',
		],
		'note' => '<p>请将商户API私钥“apiclient_key.pem”放到 /plugins/douyinpay/cert/ 文件夹内（或 /plugins/douyinpay/cert/商户号/ 文件夹内）。</p><p>上方应用ID必须为网站应用，需要在抖音支付后台关联对应的应用才能使用。</p><p>若开启JSAPI支付，需在开放平台配置应用授权回调URL：[siteurl]user/douyinoauth.php</p>', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $submit2, $conf;

		$urlpre = '/';
		if(checkdouyin() && in_array('2',$channel['apptype'])){
			return ['type'=>'jump','url'=>$urlpre.'pay/jspay/'.TRADE_NO.'/?d=1'];
		}elseif(checkmobile() && in_array('3',$channel['apptype'])){
			return ['type'=>'jump','url'=>$urlpre.'pay/h5/'.TRADE_NO.'/'];
		}else{
			return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice, $method;

		$urlpre = $siteurl;
		if($method=='app'){
			return self::apppay();
		}elseif($method=='jsapi'){
			return self::jspay();
		}
		elseif($mdevice=='douyin' && in_array('2',$channel['apptype'])){
			return ['type'=>'jump','url'=>$urlpre.'pay/jspay/'.TRADE_NO.'/?d=1'];
		}elseif($device=='mobile' && in_array('3',$channel['apptype'])){
			return ['type'=>'jump','url'=>$urlpre.'pay/h5/'.TRADE_NO.'/'];
		}else{
			return self::qrcode();
		}
	}

	//扫码支付
	static public function qrcode(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip, $device, $mdevice;

		if(in_array('1',$channel['apptype'])){

			$param = [
				'description' => $ordername,
				'out_trade_no' => TRADE_NO,
				'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
				'amount' => [
					'total' => intval(round($order['realmoney']*100)),
					'currency' => 'CNY'
				],
				'scene_info' => [
					'payer_client_ip' => $clientip
				]
			];
			if($order['profits']>0){
				$param['settle_info'] = ['profit_sharing' => true];
			}

			require(PAY_ROOT.'inc/PaymentService.php');
			$douyinpay_config = require(PAY_ROOT.'inc/config.php');
			try{
				$client = new \DouYinPay\PaymentService($douyinpay_config);
				$result = $client->nativePay($param);
				$code_url = $result['code_url'];
			} catch (Exception $e) {
				return ['type'=>'error','msg'=>'抖音支付下单失败！'.$e->getMessage()];
			}

		}elseif(in_array('2',$channel['apptype'])){
			$code_url = $siteurl.'pay/jspay/'.TRADE_NO.'/';
		}elseif(in_array('3',$channel['apptype'])){
			$code_url = $siteurl.'pay/h5/'.TRADE_NO.'/';
		}else{
			return ['type'=>'error','msg'=>'当前支付通道没有开启的支付方式'];
		}

		if(checkmobile() || $device == 'mobile'){
			if(in_array('1',$channel['apptype'])){
				return ['type'=>'qrcode','page'=>'douyinpay_h5','url'=>$code_url];
			}else{
				return ['type'=>'qrcode','page'=>'douyinpay_wap','url'=>$code_url];
			}
		}else{
			return ['type'=>'qrcode','page'=>'douyinpay_qrcode','url'=>$code_url];
		}
	}

	//JS支付
	static public function jspay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip, $method;

		require(PAY_ROOT.'inc/DouyinOauth.php');
		require(PAY_ROOT.'inc/PaymentService.php');
		
		if(!empty($order['sub_openid'])){
			if(!empty($order['sub_appid'])){
				$channel['appid'] = $order['sub_appid'];
			}
			$openid = $order['sub_openid'];
		}else{
			$oauth = new \DouYinPay\DouyinOauth($channel['appid'], $channel['appsecret']);
			$redirect_uri = $siteurl.'user/douyinoauth.php';
			$state = $_SERVER['REQUEST_URI'];
			$openid = $oauth->get_openid($redirect_uri, $state);
		}

		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks) return $blocks;

		//②、统一下单
		$param = [
			'description' => $ordername,
			'out_trade_no' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'amount' => [
				'total' => intval(round($order['realmoney']*100)),
				'currency' => 'CNY'
			],
			'payer' => [
				'openid' => $openid
			],
			'scene_info' => [
				'payer_client_ip' => $clientip
			]
		];
		if($order['profits']>0){
			$param['settle_info'] = ['profit_sharing' => true];
		}

		$douyinpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \DouYinPay\PaymentService($douyinpay_config);
			$result = $client->jsapiPay($param);
			$jsApiParameters = json_encode($result);
		} catch (Exception $e) {
			return ['type'=>'error','msg'=>'抖音支付下单失败！'.$e->getMessage()];
		}
		if($method == 'jsapi'){
			return ['type'=>'jsapi','data'=>$jsApiParameters];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'douyinpay_jspay','data'=>['jsApiParameters'=>$jsApiParameters, 'redirect_url'=>$redirect_url]];
	}

	//H5支付
	static public function h5(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		$param = [
			'description' => $ordername,
			'out_trade_no' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'amount' => [
				'total' => intval(round($order['realmoney']*100)),
				'currency' => 'CNY'
			],
			'scene_info' => [
				'payer_client_ip' => $clientip,
				'h5_info' => [
					'type' => 'Wap',
					'app_name' => $conf['sitename'],
					'app_url' => $siteurl,
				],
			]
		];
		if($order['profits']>0){
			$param['settle_info'] = ['profit_sharing' => true];
		}

		require(PAY_ROOT.'inc/PaymentService.php');
		$douyinpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \DouYinPay\PaymentService($douyinpay_config);
			$result = $client->h5Pay($param);
			$redirect_url=$siteurl.'pay/return/'.TRADE_NO.'/';
			$url=$result['h5_url'].'&return_url='.urlencode($redirect_url);
			return ['type'=>'jump','url'=>$url];
		} catch (Exception $e) {
			return ['type'=>'error','msg'=>'抖音支付下单失败！'.$e->getMessage()];
		}
	}

	//APP支付
	static public function apppay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip, $method;

		$param = [
			'description' => $ordername,
			'out_trade_no' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'amount' => [
				'total' => intval(round($order['realmoney']*100)),
				'currency' => 'CNY'
			],
			'scene_info' => [
				'payer_client_ip' => $clientip
			]
		];
		if($order['profits']>0){
			$param['settle_info'] = ['profit_sharing' => true];
		}
		require(PAY_ROOT.'inc/PaymentService.php');
		$douyinpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \DouYinPay\PaymentService($douyinpay_config);
			$result = $client->appPay($param);
			return ['type'=>'app','data'=>json_encode($result)];
		}catch(Exception $e){
			return ['type'=>'error','msg'=>'抖音支付下单失败！'.$e->getMessage()];
		}
	}

	//支付成功页面
	static public function ok(){
		return ['type'=>'page','page'=>'ok'];
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT.'inc/PaymentService.php');
		$douyinpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \DouYinPay\PaymentService($douyinpay_config);
			$data = $client->notify();
		} catch (Exception $e) {
			$client->replyNotify(false, $e->getMessage());
			exit;
		}

		if ($data['trade_state'] == 'SUCCESS') {
			if($data['out_trade_no'] == TRADE_NO){
				processNotify($order, $data['transaction_id'], $data['payer']['openid']);
			}
		}
		$client->replyNotify(true);
	}

	//退款
	static public function refund($order){
		global $channel, $conf;
		if(empty($order))exit();

		$param = [
			'transaction_id' => $order['api_trade_no'],
			'out_refund_no' => $order['refund_no'],
			'notify_url' => $conf['localurl'].'pay/refundnotify/'.TRADE_NO.'/',
			'amount' => [
				'refund' => intval(round($order['refundmoney']*100)),
				'total' => intval(round($order['realmoney']*100)),
				'currency' => 'CNY'
			]
		];

		require(PAY_ROOT.'inc/PaymentService.php');
		$douyinpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \DouYinPay\PaymentService($douyinpay_config);
			$result = $client->refund($param);
			$result = ['code'=>0, 'trade_no'=>$result['refund_id'], 'refund_fee'=>$result['amount']['refund']/100];
		} catch (Exception $e) {
			$result = ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		return $result;
	}

	static public function refundnotify(){
		global $channel, $order;

		require(PAY_ROOT.'inc/PaymentService.php');
		$douyinpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \DouYinPay\PaymentService($douyinpay_config);
			$data = $client->notify();
		} catch (Exception $e) {
			$client->replyNotify(false, $e->getMessage());
			exit;
		}

		if ($data['refund_status'] == 'SUCCESS') {

		}
		$client->replyNotify(true);
	}

	//关闭订单
	static public function close($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT.'inc/PaymentService.php');
		$douyinpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \DouYinPay\PaymentService($douyinpay_config);
			$client->closeOrder($order['trade_no']);
			$result = ['code'=>0];
		} catch (Exception $e) {
			$result = ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		return $result;
	}

}