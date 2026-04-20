<?php
namespace lib;

class AntiDigitalCertify {
	private $AccessId;
	private $AccessSecret;
	private $gatewayUrl = 'https://openapi.antchain.antgroup.com/gateway.do';
	private $SceneId;

	function __construct($AccessId, $AccessSecret, $SceneId){
		$this->AccessId = $AccessId;
		$this->AccessSecret = $AccessSecret;
		$this->SceneId = $SceneId;
	}

	//身份认证初始化服务
	public function initialize($cert_name, $cert_no, $return_url) {
		$params = [
			'biz_code' => 'FACE',
			'scene_id' => $this->SceneId,
			'outer_order_no' => getSid(),
			'identity_type' => 'CERT_INFO',
			'cert_type' => 'IDENTITY_CARD',
			'cert_no' => $cert_no,
			'cert_name' => $cert_name,
			'return_url' => $return_url,
			'callback_url' => $return_url,
		];
		return $this->request('di.realperson.facevrf.server.create', $params);
	}

	//身份认证记录查询
	public function query($certify_id) {
		$params = [
			'certify_id' => $certify_id,
			'outer_order_no' => getSid(),
			'scene_id' => $this->SceneId
		];
		return $this->request('di.realperson.facevrf.server.query', $params);
    }

	//签名方法
	private function getSign($parameters, $accessKeySecret)
	{
		ksort($parameters);
		$stringToSign = '';
		foreach ($parameters as $key => $value) {
			if($value === null) continue;
			$stringToSign .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
		}
		$stringToSign = substr($stringToSign, 1);
		$signature = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret, true));

		return $signature;
	}
	private function percentEncode($str)
	{
		$search = ['+', '*', '%7E'];
		$replace = ['%20', '%2A', '~'];
		return str_replace($search, $replace, urlencode($str));
	}
	private function request($method, $param){
		if(empty($this->AccessId)||empty($this->AccessSecret))return false;
		$data = [
			'method' => $method,
			'version' => '1.0',
			'access_key' => $this->AccessId,
			'req_msg_id' => getSid(),
			'req_time' => gmdate('Y-m-d\TH:i:s\Z'),
			'sign_type' => 'HmacSHA1',
		];
		$data['sign'] = $this->getSign(array_merge($data, $param), $this->AccessSecret);
		$url = $this->gatewayUrl. '?'. http_build_query($data);
		$response = get_curl($url, http_build_query($param));
		$result = json_decode($response,true);
		return $result;
	}
}
