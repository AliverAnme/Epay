<?php

/**
 * https://docs.baofu.com/
 */
class BaofuClient
{
	public $gateway_url = 'https://juhe.baofoo.com/api';
	protected $merId;
	protected $terId;
	protected $publicCertPath;
	protected $privateCertPath;
	protected $privateKeyPwd;
	protected $version = '1.0';
	protected $format = 'json';
	protected $signType = 'RSA';
	protected $charset = 'UTF-8';

	public function __construct($merId, $terId, $privateKeyPwd)
	{
		$this->merId = $merId;
		$this->terId = $terId;
		$this->privateKeyPwd = $privateKeyPwd;
		$this->publicCertPath = PLUGIN_ROOT.'baofu/cert/baofu.cer';
		if(file_exists(PLUGIN_ROOT.'baofu/cert/'.$merId.'.pfx')){
            $this->privateCertPath = PLUGIN_ROOT.'baofu/cert/'.$merId.'.pfx';
        }else{
            $this->privateCertPath = PLUGIN_ROOT.'baofu/cert/client.pfx';
        }
        if(!file_exists($this->publicCertPath)){
            throw new \Exception('宝付公钥证书文件baofu.cer不存在');
        }
        if(!file_exists($this->privateCertPath)){
            throw new \Exception('商户私钥证书文件client.pfx不存在');
        }
	}

	//聚合交易接口
    public function execute($method, $bizContent){
		$biz_content = json_encode($bizContent);
        $params = [
			'merId' => $this->merId,
			'terId' => $this->terId,
			'method' => $method,
			'charset' => $this->charset,
			'version' => $this->version,
			'format' => $this->format,
			'timestamp' => date('YmdHis'),
			'signType' => $this->signType,
			'signSn' => '1',
			'ncrptnSn' => '1',
            'bizContent' => $biz_content,
			'signStr' => $this->rsaPrivateSign($biz_content),
        ];
        $response = get_curl($this->gateway_url, http_build_query($params));
        $result = json_decode($response, true);
        if(isset($result['returnCode']) && $result['returnCode'] == 'SUCCESS'){
			if(empty($result['dataContent'])) throw new Exception('返回值dataContent为空');
			if(empty($result['signStr'])) throw new Exception('返回值signStr为空');
			if(!$this->rsaPubilcVerify($result['dataContent'], $result['signStr'])) throw new Exception('返回数据验签失败');
            return json_decode($result['dataContent'], true);
        }else{
            throw new \Exception($result['returnMsg']?$result['returnMsg']:'返回数据解析失败');
        }
    }

	//代付接口
	public function transfer($method, $bizContent){
		$biz_content = json_encode($bizContent, JSON_UNESCAPED_UNICODE);
		$requrl = 'https://public.baofoo.com/baofoo-fopay/pay/'.$method.'.do';
		$params = [
			'member_id' => $this->merId,
			'terminal_id' => $this->terId,
			'data_type' => 'json',
			'data_content' => $this->rsaPrivateEncrypt($biz_content),
			'version' => '4.0.2',
		];
		$response = get_curl($requrl, http_build_query($params));
		if(strpos($response, 'trans_content') === false){
			$response = $this->rsaPublicDecrypt($response);
			if(!$response) throw new Exception('返回数据解密失败');
		}
		$arr = json_decode($response, true);
		if(isset($arr['trans_content']['trans_head']['return_code'])){
			if($arr['trans_content']['trans_head']['return_code'] == '0000'){
				return $arr['trans_reqDatas'];
			}else{
				throw new Exception($arr['trans_content']['trans_head']['return_msg']);
			}
		}else{
			throw new Exception('返回数据解析失败');
		}
	}

	//统一入口
	public function submit($service_tp, $params){
        $requrl = 'https://public.baofu.com/union-gw/api/'.$service_tp.'/transReq.do';
        $content = [
            'header' => [
                'memberId' => $this->merId,
                'terminalId' => $this->terId,
                'serviceTp' => $service_tp,
                'verifyType' => '1',
            ],
            'body' => $params,
        ];
        $contentStr = json_encode($content);
        $enContent = $this->rsaPrivateEncrypt($contentStr);

        $post = [
            'memberId' => $this->merId,
            'terminalId' => $this->terId,
            'verifyType' => '1',
            'content' => $enContent,
        ];
        $response = get_curl($requrl, http_build_query($post));
        if(empty($response)) throw new Exception('请求返回为空');
        if(strpos($response, "header") != false){
            $result = json_decode($response, true);
        }else{
            $decrypted = $this->rsaPublicDecrypt($response);
            if($decrypted === false) throw new Exception('返回数据解密失败');
            $result = json_decode($decrypted, true);
        }
        if($result['header']['sysRespCode'] == 'S_0000'){
            return $result['body'];
        }elseif(isset($result['header']['sysRespCode'])){
            throw new Exception('['.$result['header']['sysRespCode'].']'.$result['header']['sysRespDesc']);
        }else{
            throw new Exception('返回数据解析失败');
        }
    }

	//文件上传接口
    public function upload($params, $file_name, $file_path){
        $requrl = 'https://upload.baofoo.com/baofu-upload-trade/trade/syncUploadFile';
        $contentStr = json_encode($params);
        $enContent = $this->rsaPrivateEncrypt($contentStr);
        $post = [
            'memberId' => $this->merId,
            'terminalId' => $this->terId,
            'orderType' => '0',
            'content' => $enContent,
            'file' => new \CURLFile($file_path, mime_content_type($file_path), $file_name),
        ];
        $response = get_curl($requrl, $post);
        if(empty($response)) throw new Exception('请求返回为空');
        $decrypted = $this->rsaPublicDecrypt($response);
        if($decrypted === false) throw new Exception('返回数据解密失败');
        $result = json_decode($decrypted, true);
        if($result['success'] == 1 || $result['success'] == 2){
            return $result['result'];
        }elseif(isset($result['errorCode'])){
            throw new Exception('['.$result['errorCode'].']'.$result['errorMsg']);
        }else{
            throw new Exception('返回数据解析失败');
        }
    }

	//回调验签
	public function verifyNotify($data, $sign){
		if(empty($sign)) return false;
		return $this->rsaPubilcVerify($data, $sign);
	}

	//商户私钥签名
	private function rsaPrivateSign($data){
		$pkcs12 = file_get_contents($this->privateCertPath);
        if (!openssl_pkcs12_read($pkcs12, $keyarr, $this->privateKeyPwd)) {
            throw new Exception('商户私钥证书解析失败');
        }
        $private_key = openssl_pkey_get_private($keyarr['pkey']);
		openssl_sign($data, $signature, $private_key, OPENSSL_ALGO_SHA256);
        return bin2hex($signature);
	}

	//平台公钥验签
	private function rsaPubilcVerify($data, $signature){
		$keyFile = file_get_contents($this->publicCertPath);
        $public_key = openssl_pkey_get_public($keyFile);
		if(!$public_key){
			throw new Exception('验签失败，平台公钥证书不正确');
		}
		$result = openssl_verify($data, hex2bin($signature), $public_key, OPENSSL_ALGO_SHA256);
		return $result === 1;
	}

	//商户私钥加密
	private function rsaPrivateEncrypt($data){
        $pkcs12 = file_get_contents($this->privateCertPath);
        if (!openssl_pkcs12_read($pkcs12, $keyarr, $this->privateKeyPwd)) {
            throw new Exception('商户私钥证书解析失败');
        }
        $privateKey = openssl_pkey_get_private($keyarr['pkey']);
        
        $data = base64_encode($data);
        $encrypted = '';
        $partLen = openssl_pkey_get_details($privateKey)['bits']/8 - 11;
        $plainData = str_split($data, $partLen);
        foreach ($plainData as $chunk) {
            $partialEncrypted = '';
            $encryptionOk = openssl_private_encrypt($chunk, $partialEncrypted, $privateKey);
            if ($encryptionOk === false) {
                return false;
            }
            $encrypted .= $partialEncrypted;
        }
        return bin2hex($encrypted);
    }

	//平台公钥解密
    public function rsaPublicDecrypt($data){
        $keyFile = file_get_contents($this->publicCertPath);
        $publicKey = openssl_pkey_get_public($keyFile);
        
        $decrypted = '';
        $partLen = openssl_pkey_get_details($publicKey)['bits']/8;
        $data = hex2bin($data);
        $plainData = str_split($data, $partLen);
        foreach ($plainData as $chunk) {
            $partialDecrypted = '';
            $decryptionOk = openssl_public_decrypt($chunk, $partialDecrypted, $publicKey);
            if ($decryptionOk === false) {
                return false;
            }
            $decrypted .= $partialDecrypted;
        }
        return base64_decode($decrypted);
    }
}