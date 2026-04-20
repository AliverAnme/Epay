<?php

class baofu_plugin
{
	static public $info = [
		'name'        => 'baofu', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '宝付支付', //支付插件显示名称
		'author'      => '宝付', //支付插件作者
		'link'        => 'https://www.baofu.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'transtypes'  => ['bank'], //支付插件支持的转账方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户号',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '终端号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '私钥密码',
				'type' => 'input',
				'note' => '',
			],
			'appmchid' => [
				'name' => '聚合交易商户号',
				'type' => 'input',
				'note' => '在微信/支付宝报备的商户号',
			],
		],
		'select' => null,
		'select_alipay' => [
			'1' => '扫码支付',
			'2' => 'JS支付',
		],
		'select_wxpay' => [
			'1' => '聚合码支付',
			'2' => '公众号/小程序支付',
		],
		'select_bank' => [
			'1' => '扫码支付',
			'2' => 'JS支付',
		],
		'note' => '需要将商户私钥证书client.pfx（或商户号.pfx）放到 /plugins/baofu/cert/ 文件夹下', //支付密钥填写说明
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
			}elseif($order['typename']=='bank'){
				return self::bankjs();
			}
		}elseif($order['typename']=='alipay'){
			if($mdevice=='alipay' && in_array('2',$channel['apptype'])){
				return ['type'=>'jump','url'=>'/pay/alipayjs/'.TRADE_NO.'/?d=1'];
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

	//统一下单
	static private function addOrder($pay_code, $sub_openid = null, $sub_appid = null){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;
		$subMchId = $channel['appmchid'];
		if(strpos($subMchId, '|')){
			$subMchIds = explode('|', $subMchId);
			$subMchId = $subMchIds[array_rand($subMchIds)];
		}

		require_once PAY_ROOT.'inc/BaofuClient.php';

		if($pay_code == 'WECHAT_JSAPI'){
			$pay_extend = [
				'body' => $ordername,
				'area_info' => '110101',
				'sub_appid' => $sub_appid,
				'sub_openid' => $sub_openid,
			];
		}elseif($pay_code == 'WECHAT_APP'){
			$pay_extend = [
				'body' => $ordername,
				'area_info' => '110101',
				'sub_appid' => $sub_appid,
			];
		}elseif($pay_code == 'WECHAT_MICROPAY'){
			$pay_extend = [
				'body' => $ordername,
				'area_info' => '110101',
				'auth_code' => $order['auth_code'],
			];
		}elseif($pay_code == 'ALIPAY_NATIVE'){
			$pay_extend = [
				'subject' => $ordername,
			];
		}elseif($pay_code == 'ALIPAY_JSAPI'){
			$pay_extend = [
				'subject' => $ordername,
				'buyer_id' => $sub_openid,
			];
		}elseif($pay_code == 'ALIPAY_MICROPAY'){
			$pay_extend = [
				'subject' => $ordername,
				'auth_code' => $order['auth_code'],
			];
		}elseif($pay_code == 'QUICK_PASS_NATIVE'){
			$pay_extend = [
				'areaInfo' => '1560001',
			];
		}elseif($pay_code == 'QUICK_PASS_NATIVE_JS'){
			$pay_extend = [
				'userId' => $sub_openid,
				'areaInfo' => '1560001',
				'customerIp' => $clientip,
				'orderDesc' => $ordername,
			];
		}

		$params = [
			'merId' => $channel['appid'],
			'terId' => $channel['appurl'],
			'outTradeNo' => TRADE_NO,
			'txnAmt' => intval(round($order['realmoney'] * 100)),
			'txnTime' => date('YmdHis'),
			'totalAmt' => intval(round($order['realmoney'] * 100)),
			'prodType' => 'ORDINARY',
			//'orderType' => '7',
			'payCode' => $pay_code,
			'payExtend' => $pay_extend,
			'subMchId' => $subMchId,
			'notifyUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'pageUrl' => $siteurl.'pay/return/'.TRADE_NO.'/',
			'riskInfo' => ['clientIp'=>$clientip],
		];

		$client = new BaofuClient($channel['appid'], $channel['appurl'], $channel['appkey']);
		
		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $params) {
			$result = $client->execute('unified_order', $params);
			if($result['resultCode'] == 'SUCCESS'){
				return $result['chlRetParam'];
			}else{
				throw new Exception('['.$result['errCode'].']'.$result['errMsg']);
			}
		});
	}

	//扫码支付
	static private function qrcode($pay_code){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;
		$subMchId = $channel['appmchid'];
		if(strpos($subMchId, '|')){
			$subMchIds = explode('|', $subMchId);
			$subMchId = $subMchIds[array_rand($subMchIds)];
		}

		require_once PAY_ROOT.'inc/BaofuClient.php';

		$pay_extend = [
			'goods_name' => $ordername,
			'merchant_name' => '聚合商户',
			'area_info' => '110101',
		];
		if($pay_code == 'WECHAT_JSAPI'){
			$pay_extend['wechat_sub_member_id'] = $subMchId;
		}else{
			$pay_extend['alipay_sub_member_id'] = $subMchId;
		}
		$params = [
			'merId' => $channel['appid'],
			'terId' => $channel['appurl'],
			'outTradeNo' => TRADE_NO,
			'txnAmt' => intval(round($order['realmoney'] * 100)),
			'txnTime' => date('YmdHis'),
			'totalAmt' => intval(round($order['realmoney'] * 100)),
			'prodType' => 'ORDINARY',
			//'orderType' => '7',
			'cashierFlag' => '0',
			'payCode' => $pay_code,
			'payExtend' => $pay_extend,
			'subMchId' => $subMchId,
			'notifyUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'pageUrl' => $siteurl.'pay/return/'.TRADE_NO.'/',
			'riskInfo' => ['clientIp'=>$clientip],
		];

		$client = new BaofuClient($channel['appid'], $channel['appurl'], $channel['appkey']);
		
		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $params) {
			$result = $client->execute('pre_unified_order', $params);
			if($result['resultCode'] == 'SUCCESS'){
				return $result['token'];
			}else{
				throw new Exception('['.$result['errCode'].']'.$result['errMsg']);
			}
		});
	}

	//支付宝扫码支付
	static public function alipay(){
		global $channel, $device, $mdevice, $siteurl;
		if(in_array('2',$channel['apptype']) && !in_array('1',$channel['apptype'])){
			$code_url = $siteurl.'pay/alipayjs/'.TRADE_NO.'/';
		}else{
			try{
				$result = self::addOrder('ALIPAY_NATIVE');
				$code_url = $result['qr_code'];
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
			$result = self::addOrder('ALIPAY_JSAPI', $user_id);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}
		if($method == 'jsapi'){
			return ['type'=>'jsapi','data'=>$result['trade_no']];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'alipay_jspay','data'=>['alipay_trade_no'=>$result['trade_no'], 'redirect_url'=>$redirect_url]];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $device, $mdevice, $siteurl;
		if(in_array('2',$channel['apptype']) && !in_array('1',$channel['apptype'])){
			if($channel['appwxmp']>0 && $channel['appwxa']==0){
				$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			}else{
				$code_url = $siteurl.'pay/wxwappay/'.TRADE_NO.'/';
			}
		}else{
			try{
				$code_url = self::qrcode('WECHAT_JSAPI');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败 '.$ex->getMessage()];
			}
		}

		if(checkwechat() || $mdevice=='wechat'){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile() || $device=='mobile') {
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
			$result = self::addOrder('WECHAT_JSAPI', $openid, $wxinfo['appid']);
			$pay_info = $result['wc_pay_data'];
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
			$result = self::addOrder('WECHAT_JSAPI', $openid, $wxinfo['appid']);
			$pay_info = $result['wc_pay_data'];
		}catch(Exception $ex){
			exit(json_encode(['code'=>-1, 'msg'=>'微信支付下单失败 '.$ex->getMessage()]));
		}

		exit(json_encode(['code'=>0, 'data'=>json_decode($pay_info, true)]));
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
		if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信小程序不存在'];
		try{
			$code_url = wxminipay_jump_scheme($wxinfo['id'], TRADE_NO);
		}catch(Exception $e){
			return ['type'=>'error','msg'=>$e->getMessage()];
		}
		return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::addOrder('QUICK_PASS_NATIVE');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		if(checkunionpay()){
			return ['type'=>'jump','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
		}
	}

	static public function bankjs(){
		global $method, $order;
		try{
			$result = self::addOrder('QUICK_PASS_NATIVE_JS', $order['sub_openid']);
			$code_url = $result['redirectUrl'];
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'jump','url'=>$code_url];
	}

	static public function get_unionpay_userid($channel, $userAuthCode){
		require_once PLUGIN_ROOT.'baofu/inc/BaofuClient.php';

		$param = [
			'merId' => $channel['appid'],
			'terId' => $channel['appurl'],
			'userAuthCode' => $userAuthCode,
			'appUpIdentifier' => get_unionpay_ua(),
		];

		$client = new BaofuClient($channel['appid'], $channel['appurl'], $channel['appkey']);
		try{
			$result = $client->execute('quick_pass_auth', $param);
			if($result['resultCode'] == 'SUCCESS'){
				return ['code'=>0, 'data'=>$result['userId']];
			}else{
				return ['code'=>-1, 'msg'=>'['.$result['errCode'].']'.$result['errMsg']];
			}
		}catch(Exception $e){
			return ['code'=>-1,'msg'=>$e->getMessage()];
		}
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		if(!isset($_POST['dataContent'])) return ['type'=>'html','data'=>'NO'];

		require_once PAY_ROOT.'inc/BaofuClient.php';
		
		$client = new BaofuClient($channel['appid'], $channel['appurl'], $channel['appkey']);
		$verify_result = $client->verifyNotify($_POST['dataContent'], $_POST['signStr']);

		if($verify_result) {//验证成功

			$params = json_decode($_POST['dataContent'], true);
			if ($params['txnState'] == 'SUCCESS') {
				if($params['outTradeNo'] == TRADE_NO){
					if(strpos($params['payCode'], 'ALIPAY_') !== false){
						$buyer = $params['chlRetParam']['buyer_id'];
						$bill_trade_no = $params['chlRetParam']['trade_no'];
					}elseif(strpos($params['payCode'], 'WECHAT_') !== false){
						$buyer = null;
						$bill_trade_no = $params['chlRetParam']['transaction_id'];
					}elseif(strpos($params['payCode'], 'QUICK_PASS_') !== false){
						$buyer = null;
						$bill_trade_no = $params['chlRetParam']['voucherNum'];
					}
					$bill_mch_trade_no = $params['reqChlNo'];
					/*if(!empty($channel['share_merid'])){
						self::share($client, $params['tradeNo']);
					}*/
					processNotify($order, $params['tradeNo'], $buyer, $bill_trade_no, $bill_mch_trade_no);
				}
			}
			return ['type'=>'html','data'=>'OK'];
		}
		else {
			return ['type'=>'html','data'=>'FAIL'];
		}
	}

	static private function share($client, $originTradeNo){
		global $channel, $order;
		$param = [
			'merId' => $channel['appid'],
			'terId' => $channel['appurl'],
			'originTradeNo' => $originTradeNo,
			'txnTime' => date('YmdHis'),
			'outTradeNo' => TRADE_NO,
			'sharingDetails' => [
				[
					'sharingMerId' => $channel['share_merid'],
					'sharingAmt' => intval(round($order['realmoney'] * 100)),
				]
			],
		];
		usleep(500000);
		try{
			$result = $client->execute('share_after_pay', $param);
			if($result['resultCode'] == 'SUCCESS'){
				return ['code'=>0];
			}else{
				return ['code'=>-1, 'msg'=>'['.$result['errCode'].']'.$result['errMsg']];
			}
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel, $conf;
		if(empty($order))exit();

		require_once PAY_ROOT.'inc/BaofuClient.php';

		$client = new BaofuClient($channel['appid'], $channel['appurl'], $channel['appkey']);

		$param = [
			'merId' => $channel['appid'],
			'terId' => $channel['appurl'],
			'originTradeNo' => $order['api_trade_no'],
			'outTradeNo' => $order['refund_no'],
			'refundAmt' => intval(round($order['refundmoney'] * 100)),
			'totalAmt' => intval(round($order['refundmoney'] * 100)),
			'txnTime' => date('YmdHis'),
			'refundReason' => '申请退款',
		];

		try{
			$result = $client->execute('order_refund', $param);
			if($result['resultCode'] == 'SUCCESS'){
				return ['code'=>0, 'trade_no'=>$result['tradeNo'], 'refund_fee'=>$result['refundAmt']/100];
			}else{
				return ['code'=>-1, 'msg'=>'['.$result['errCode'].']'.$result['errMsg']];
			}
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}

	//进件通知
	static public function applynotify(){
		global $channel;

		if(!isset($_POST['data_content'])) {
			return ['type'=>'html','data'=>'NO'];
		}

		$model = \lib\Applyments\CommUtil::getModel2($channel);
		if($model) $model->notify($_POST['data_content']);

		return ['type'=>'html','data'=>'OK'];
	}

	//转账
	static public function transfer($channel, $bizParam){
		global $conf, $clientip;
		if(empty($channel) || empty($bizParam))exit();

		try{
			$bank_info = getBankCardInfo($bizParam['payee_account']);
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}

		require_once(PLUGIN_ROOT.'baofu/inc/BaofuClient.php');
		$client = new BaofuClient($channel['appid'], $channel['appurl'], $channel['appkey']);

		$params = [
			'trans_content' => ['trans_reqDatas'=>[['trans_reqData'=>[[
				'trans_no' => $bizParam['out_biz_no'],
				'trans_money' => $bizParam['money'],
				'to_acc_name' => $bizParam['payee_real_name'],
				'to_acc_no' => $bizParam['payee_account'],
				'to_bank_name' => $bank_info['bank_name'],
			]]]]],
		];

		try{
			$result = $client->transfer('BF0040001', $params);
			$info = $result[0]['trans_reqData'][0];
			return ['code'=>0, 'status'=>0, 'orderid'=>$info['trans_batchid'], 'paydate'=>date('Y-m-d H:i:s')];
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}

	//转账查询
	static public function transfer_query($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		require_once(PLUGIN_ROOT.'baofu/inc/BaofuClient.php');
		$client = new BaofuClient($channel['appid'], $channel['appurl'], $channel['appkey']);

		$params = [
			'trans_content' => ['trans_reqDatas'=>[['trans_reqData'=>[[
				'trans_no' => $bizParam['out_biz_no']
			]]]]],
		];
		try{
			$result = $client->transfer('BF0040002', $params);
			$info = $result[0]['trans_reqData'][0];
			if($info['state'] == '1'){
				$status = 1;
			}elseif($info['trade_status'] == '-1' || $info['trade_status'] == '2'){
				$status = 2;
				$errmsg = $info['trans_remark'];
			}else{
				$status = 0;
			}
			return ['code'=>0, 'status'=>$status, 'errmsg'=>$errmsg];
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}

	//电子回单
	static public function transfer_proof($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		require_once(PLUGIN_ROOT.'baofu/inc/BaofuClient.php');
		$client = new BaofuClient($channel['appid'], $channel['appurl'], $channel['appkey']);

		$params = [
			'memberTransId' => $bizParam['out_biz_no'],
			'fileType' => '2',
			'transferDate' => substr($bizParam['out_biz_no'], 0, 4).'-'.substr($bizParam['out_biz_no'], 4, 2).'-'.substr($bizParam['out_biz_no'], 6, 2),
			'orderType' => '0',
			'version' => 'V1.1',
		];
		try{
			$result = $client->submit('T-1001-003-03', $params);
			if($result['state'] == '0000'){
				return ['code'=>0, 'msg'=>'电子回单生成成功！', 'download_url'=>$result['urlDownload']];
			}else{
				return ['code'=>-1, 'msg'=>'电子回单生成失败！['.$result['state'].']'.$result['message']];
			}
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}

	//余额查询
	static public function balance_query($channel, $bizParam){
		if(empty($channel))exit();

		require_once(PLUGIN_ROOT.'baofu/inc/BaofuClient.php');
		$client = new BaofuClient($channel['appid'], $channel['appurl'], $channel['appkey']);

		$params = [
			'version' => '4.0.0',
			'accountType' => 'BASE_ACCOUNT',
		];
		try{
			$result = $client->submit('T-1001-006-03', $params);
			return ['code'=>0, 'amount'=>$result['balance']];
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}
}