<?php
use Exception;

/**
 * @see https://docs.qq.com/aio/DU0VpckhxWGRTQU1x
 */
class MossClient
{
	private static $gateway_url = 'https://moss.lakala.com/ord-api/unified/v3';
	private static $platform_public_text = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQD3E6H3qfgqF7aKypmSuzMIRuL/pRFMzsyqMlSEzzo2aJqN7w8Lb2tfVRfnAUVKMFyDxUzNWER4E/UfR4ymo0YHOaiIJI3AHWdJngJyGgK+SfvYDs9rqC++yisrzYv/TN3fZ93Ru1YWOYi4x4lBSCC9UX2b28hwx32MpJHT7gIrMQIDAQAB';

	private $appid;
	private $platform_public_key;
	private $mch_private_key;
	private $version = '1.0';

	public function __construct($appid, $mch_private_key)
	{
		$this->appid = $appid;
		$this->platform_public_key = $this->loadPublicKey(self::$platform_public_text);
		$this->mch_private_key = $this->loadPrivateKey($mch_private_key);
	}

	//发起API请求
	public function execute($service, $data){
		$request = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$params = [
			'head' => [
				'versionId' => $this->version,
				'serviceId' => $service,
				'channelId' => 'API',
				'requestTime' => date('YmdHis'),
				'serviceSn' => getSid(),
				'businessChannel' => $this->appid,
			],
			'requestEncrypted' => $this->rsaPublicEncrypt($request),
		];
		$params['sign'] = $this->generateSign($params);
		$body = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$response = get_curl(self::$gateway_url, $body, 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
		$result = json_decode($response, true);
		if(isset($result['head']['code']) && $result['head']['code'] == '000000'){
			if (!$this->verifySign($result)) {
				throw new Exception('响应报文验签失败');
			}
			$response = $this->rsaPrivateDecrypt($result['responseEncrypted']);
			if (!$response) {
				throw new Exception('响应报文解密失败');
			}
			return json_decode($response, true);
		}elseif(isset($result['head']['code'])){
			throw new Exception('['.$result['head']['code'].']'.$result['head']['desc']);
		}else{
			throw new Exception('请求失败,'.$response);
		}
	}

	private function getSignContent($params){
		ksort($params);
		$signstr = '';

		foreach($params as $k => $v){
			if($k != 'sign' && $v !== null){
				if (is_array($v)){
					ksort($v);
					$v = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				}
				$signstr .= ($signstr ? '&' : '').$k.'='.$v;
			}
		}
		return $signstr;
	}

	//生成签名
	private function generateSign($params){
		return $this->rsaPrivateSign($this->getSignContent($params));
	}

	//验签方法
	private function verifySign($params){
		if(empty($params['sign'])) return false;
		return $this->rsaPubilcVerify($this->getSignContent($params), $params['sign']);
	}

	//解析异步通知
	public function notify($data){
		if (!$this->verifySign($data)) {
			throw new Exception('验签失败');
		}
		$request = $this->rsaPrivateDecrypt($data['requestEncrypted']);
		if (!$request) {
			throw new Exception('解密失败');
		}
		return json_decode($request, true);
	}

	//异步通知返回
	public function echoNotify($head, $success = true, $message = null){
		$params = [
			'head' => [
				'code' => $success ? '000000' : '000001',
				'desc' => $success ? 'success' : ($message ? $message : 'failure'),
				'serviceTime' => date('YmdHis'),
				'serviceSn' => $head['serviceSn'],
				'serviceId' => $head['serviceId'],
			]
		];
		$params['sign'] = $this->generateSign($params);
		exit(json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}

	//客户私钥签名
	private function rsaPrivateSign($data){
		openssl_sign($data, $signature, $this->mch_private_key);
		$signature = base64_encode($signature);
		return $signature;
	}

	//平台公钥验签
	private function rsaPubilcVerify($data, $signature){
		$result = openssl_verify($data, base64_decode($signature), $this->platform_public_key);
		return $result === 1;
	}

	//平台公钥加密
	private function rsaPublicEncrypt($data){
		$encrypted = '';
        $partLen = openssl_pkey_get_details($this->platform_public_key)['bits']/8 - 11;
        $plainData = str_split($data, $partLen);
        foreach ($plainData as $chunk) {
            $partialEncrypted = '';
            $encryptionOk = openssl_public_encrypt($chunk, $partialEncrypted, $this->platform_public_key);
            if ($encryptionOk === false) {
                return false;
            }
            $encrypted .= $partialEncrypted;
        }
        return base64_encode($encrypted);
	}

	//客户私钥解密
	private function rsaPrivateDecrypt($data){
		$decrypted = '';
        $partLen = openssl_pkey_get_details($this->mch_private_key)['bits'] / 8;
        $data = str_split(base64_decode($data), $partLen);
        foreach ($data as $chunk) {
            $partial = '';
            $decryptionOK = openssl_private_decrypt($chunk, $partial, $this->mch_private_key);
            if ($decryptionOK === false) {
                return false;
            }
            $decrypted .= $partial;
        }
        return $decrypted;
	}

	//加载平台公钥
    private function loadPublicKey($public_key)
    {
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($public_key, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $pubkeyid = openssl_pkey_get_public($res);
        if (!$pubkeyid) {
            throw new Exception('平台公钥不正确');
        }
        return $pubkeyid;
    }

    //加载客户私钥
    private function loadPrivateKey($private_key)
    {
		if(strpos($private_key, '-----BEGIN') === false){
			$private_key = str_replace(["\n", "\r"], '', $private_key);
			$private_key = "-----BEGIN PRIVATE KEY-----\n" .
				wordwrap($private_key, 64, "\n", true) .
				"\n-----END PRIVATE KEY-----";
		}
        $prikeyid = openssl_pkey_get_private($private_key);
        if (!$prikeyid) {
            throw new Exception('客户私钥不正确');
        }
        return $prikeyid;
    }

}