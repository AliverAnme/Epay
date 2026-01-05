<?php
class jfyui_plugin {
	public static $info = [
	        'name' => 'jfyui', //支付插件英文名称，需和目录名称一致，不能有重复
	'showname' => '缴费易v1', //支付插件显示名称
	'author' => '脉冲科技', //支付插件作者
	'link' => '#', //支付插件作者链接
	'types' => ['alipay', 'wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
	'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
	'appurl' => [
	                'name' => '商户ID',
	                'type' => 'input',
	                'note' => '解码得收款链接中PID值',
	            ],
	            'appid' => [
	                'name' => '店铺名',
	                'type' => 'input',
	                'note' => '收银台店铺名',
	            ],
	            'appkey' => [
	                'name' => '商户号',
	                'type' => 'input',
	                'note' => '拉卡拉商户号',
	            ],
	            'appmchid' => ['name' => '收款备注', 'type' => 'input', 'note' => '备注'],
	            'appsecret' => [
	                'name' => '指定IP',
	                'type' => 'input',
	                'note' => '任意国内IP，多通道不可复用',
	            ],
	        ],
	        'select' => null,
	        'note' => '', //支付密钥填写说明
	'bindwxmp' => false, //是否支持绑定微信公众号
	'bindwxa' => false, //是否支持绑定微信小程序
	];
	public static function submit() {
		global $siteurl, $channel, $order, $ordername, $sitename, $conf, $DB;
		return ['type'=>'jump','url'=>'/pay/'.$order['typename'].'/'.TRADE_NO.'/?sitename='.$sitename];
	}
	public static function mapi() {
		global $siteurl, $channel, $order, $conf, $device, $mdevice;
		$typename = $order['typename'];
		return self::$typename();
	}
	//支付宝下单
	public static function alipay() {
		try {
			$code_url = self::qrcode();
		}
		catch (Exception $ex) {
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
			return ['type'=>'page','page'=>'wxopen'];
		} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
			return ['type' => 'qrcode', 'page' => 'alipay_wap', 'url' => $code_url];
		} elseif (self::isMobile() && !isset($_GET['qrcode'])) {
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
		}
	}
	//微信下单
	public static function wxpay() {
		try {
			$code_url = self::qrcode();
		}
		catch (Exception $ex) {
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
			return ['type'=>'jump','url'=>$code_url];
		} elseif (self::isMobile() && !isset($_GET['qrcode'])) {
			return ['type'=>'qrcode','page'=>'wxpay_h5','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}
	//云闪付下单
	public static function bank() {
		try {
			$code_url = self::qrcode();
		}
		catch (Exception $ex) {
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}
		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}
	public static function qrcode() {
		global $siteurl, $channel, $order, $ordername, $sitename, $conf, $DB;
		// 金额
		$amount = number_format($order['realmoney'], 2, '.', '');
		$orderData = array(
		// 商户ID
		    "merchId" => $channel['appurl'],
		    // 金额
		    "tradeAmount" => $amount,
		    // 备注
		    "remark" => $channel['appmchid'],
		    "orderTemplateData" => array(
        array(
            "key" => 1747822239290,
            "type" => "number",
            "index" => 0,
            "label" => "支付金额",
            "value" => $amount,
            "origin" => "number17478222392900",
            "options" => array(
                "label" => "支付金额",
                "content" => $amount,
                "required" => true,
                "labelAlign" => ""
            ),
            "displayName" => "金额类型",
            "formItemFlag" => false,
            "settingsTitle" => "金额类型设置",
            "marginLeftRight" => 10,
            "marginTopBottom" => 5,
            "cashierTemplateName" => $channel['appid'],
            "state" => true
        )
    )
		);
		$orderResponse = self::curlRequest(
		    'https://jfyconsole.lakala.com/order/api/cashier/pay',
		    'POST',
		    ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36'],
		    $orderData
		);
		
		// 返回响应
		$payscode = $orderResponse['code'] ?? '';
		// 判断响应
		if($payscode == 200){
		// 成功
		$paysurl = $orderResponse['data']['payUrl'] ?? '';
		if (strpos($paysurl, 'lakala') === false) {
    throw new Exception('未返回支付地址');
}
		// 开始截取
		$paysurl2 = parse_url($paysurl, PHP_URL_QUERY);
		$paysurl3 = urldecode($paysurl2);
		parse_str($paysurl3, $paysurl4);
		$paysurl5 = $paysurl4['payOrderNo'];
		// 结束截取
		$paysurl6 = $DB->query('INSERT INTO pre_order (trade_no, api_trade_no) VALUES (?, ?) ON DUPLICATE KEY UPDATE api_trade_no = VALUES(api_trade_no)', [TRADE_NO, $paysurl5]);
		if(!$paysurl6) {
			throw new Exception('订单号无法更新');
		}
		// 返回支付链接
		return $paysurl;
		}else{
		throw new Exception('订单创建失败');
		}
		// 结束
	}
	
	// 状态监控-计划任务版
	public static function btjk($payid, array $channel, $OrderId) {
		global $DB;
		$headers = ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36'];
		if (isset($channel['appsecret']) && !empty($channel['appsecret'])) {
			$headers[] = 'X-FORWARDED-FOR: ' . $channel['appsecret'];
			$headers[] = 'CLIENT-IP: ' . $channel['appsecret'];
		}
		$confirmResponse = self::curlRequest(
		    'https://payment.lakala.com/m/ccss/counter/order/query',
		    'POST',
		    $headers,
		    [
		    "reqTime" => date('YmdHis'), "version" => "1.0", "reqData" => [
		    "channelId" => "95",
		    "payOrderNo" => $OrderId,
		    "merchantNo" => $channel['appkey']
		    ]
		    ]
		);
		if (empty($confirmResponse['code']) || $confirmResponse['code'] != 000000) {
			return $payid."查单出错";
		}elseif($confirmResponse['respData']['orderStatus'] != 2){
		return $payid."订单还未支付";
		}		
		$order = $DB->getRow('select * from pre_order where trade_no = ? limit 1', [$payid]);
		if (empty($order)) {
			return "订单". $payid ."不存在或已过期";
		}		
		$payid = daddslashes($payid);
		processNotify($order, $payid);
		return "订单" . $payid . "已完成";
	}
	// 检测是否为手机端
	private static function isMobile() {
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$mobileAgents = [
		        'Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone', 'BlackBerry', 'Nokia', 'Sony', 'Symbian', 'Opera Mini',
		    ];
		foreach ($mobileAgents as $agent) {
			if (strpos($userAgent, $agent) !== false) {
				return true;
			}
		}
		return false;
	}
	// 请求
	public static function curlRequest($url, $method, $headers = [], $data = []) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data ? json_encode($data) : null);
			$headers[] = 'Content-Type: application/json;charset=utf-8';
		} elseif ($method === 'GET') {
			if (!empty($data)) {
				$url .= '?' . http_build_query($data);
			}
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);
		return json_decode($response, true);
	}
}