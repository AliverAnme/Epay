<?php

class PayService
{
	private $version = '1.0.0';
	private $mchnt_cd;
	private $platform_public_key;
	private $merchant_private_key;

	public function __construct($mchnt_cd, $platform_public_key, $merchant_private_key)
	{
		$this->mchnt_cd = $mchnt_cd;
		$this->platform_public_key = $platform_public_key;
		$this->merchant_private_key = $merchant_private_key;
	}

	//发起API请求
	public function submit($requrl, $params){
		$public_params = [
			'ver' => $this->version,
			'mchnt_cd' => $this->mchnt_cd,
		];
		$params = array_merge($public_params, $params);
		$data = json_encode($params);
		$data = $this->rsaPublicEncrypt($data);
		$body = json_encode(['mchnt_cd'=>$this->mchnt_cd, 'message'=>$data]);

		$response = get_curl($requrl, $body, 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
		$result = json_decode($response, true);
		
		if(isset($result['resp_code']) && $result['resp_code']=='0000'){
			$data = $this->rsaPrivateDecrypt($result['message']);
			$arr = json_decode($data, true);
			if(!$arr){
				throw new Exception('返回数据私钥解密失败');
			}
			return $arr;
		}elseif(isset($result['resp_desc'])){
			throw new Exception($result['resp_desc']);
		}else{
			throw new Exception('返回数据解析失败');
		}
	}

	//回调数据解密
	public function decryptNotify($message){
		$data = $this->rsaPrivateDecrypt($message);
		return json_decode($data, true);
	}

	//平台公钥加密
	private function rsaPublicEncrypt($data){
		if(!is_string($data)) return null;
		$pubKey = str_replace(array("\r\n", "\r", "\n"), "", $this->platform_public_key);
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
		$pubkeyid = openssl_pkey_get_public($res);
		if(!$pubkeyid){
			throw new Exception('加密失败，平台公钥不正确');
		}

		$encrypted = '';
        $partLen = openssl_pkey_get_details($pubkeyid)['bits']/8 - 11;
        $plainData = str_split($data, $partLen);
        foreach ($plainData as $chunk) {
            $partialEncrypted = '';
            $encryptionOk = openssl_public_encrypt($chunk, $partialEncrypted, $pubkeyid);
            if ($encryptionOk === false) {
                return false;
            }
            $encrypted .= $partialEncrypted;
        }
        return base64_encode($encrypted);
	}

	//商户私钥解密
	private function rsaPrivateDecrypt($data){
		$priKey = str_replace(array("\r\n", "\r", "\n"), "", $this->merchant_private_key);
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
		$pkeyid = openssl_pkey_get_private($res);
		if(!$pkeyid){
			throw new Exception('解密失败，商户私钥不正确');
		}

		$decrypted = '';
        $partLen = openssl_pkey_get_details($pkeyid)['bits'] / 8;
        $data = str_split(base64_decode($data), $partLen);
        foreach ($data as $chunk) {
            $partial = '';
            $decryptionOK = openssl_private_decrypt($chunk, $partial, $pkeyid);
            if ($decryptionOK === false) {
                return false;
            }
            $decrypted .= $partial;
        }
        return $decrypted;
	}

}