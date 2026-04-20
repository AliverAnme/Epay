<?php

class HelipayClient
{
    /** 签名类型 */
    const SIGN_TYPE = 'SM3WITHSM2';
    
    /** 版本号 */
    const VERSION = '1.0';
    
    /** SM4-CBC 固定IV (Base64)，与Java demo保持一致，勿修改 */
    const SM4_IV_BASE64 = 'AQ4Zvt54xKn9QaW86ZzWdg==';

    /** @var string 商户编号 */
    private $customerNumber;
    
    /** @var string 合利宝平台SM2公钥(hex格式: 128位或04+128位) */
    private $platformPublicKey;
    
    /** @var string 商户SM2私钥(hex格式: 64位) */
    private $merchantPrivateKey;
    
    /** @var string 网关基础URL */
    private $gatewayUrl = 'https://pay.trx.helipay.com';

    /**
     * @param string $customerNumber 商户编号
     * @param string $platformPublicKey 合利宝平台公钥(hex: 128位xy或130位04+xy)
     * @param string $merchantPrivateKey 商户私钥(hex: 64位)
     * @param string $gatewayUrl 网关URL，留空使用默认
     */
    public function __construct($customerNumber, $platformPublicKey, $merchantPrivateKey)
    {
        if (!function_exists('gmp_init')) {
            throw new Exception('请先安装GMP扩展');
        }
        $this->customerNumber = $customerNumber;
        $this->platformPublicKey = $platformPublicKey;
        $this->merchantPrivateKey = $merchantPrivateKey;
    }

    /**
     * 发起国密API请求
     * 
     * @param string $path 接口路径
     * @param array $data 业务数据
     * @return array
     */
    public function execute($path, $data)
    {
        $params = $this->assemblyRequestParams($data);
        $url = $this->gatewayUrl . $path;
        $body = json_encode($params, JSON_UNESCAPED_UNICODE);

        $response = get_curl(
            $url,
            $body,
            0, 0, 0, 0, 0,
            ['Content-Type: application/json; charset=utf-8']
        );

        $result = json_decode($response, true);
        if ($result === null) {
            throw new Exception('返回数据解析失败');
        }

        if (isset($result['code']) && $result['code'] == '0000') {
            if (!empty($result['sign']) && isset($result['data'])) {
                if (!$this->verifySign($result['data'], $result['sign'])) {
                    throw new Exception('返回数据验签失败');
                }
            }
            return json_decode($result['data'], true);
        }

        $msg = isset($result['message']) ? $result['message'] : '请求失败';
        throw new Exception($msg);
    }

    /**
     * 组装国密请求参数
     * 流程：SM4加密data -> SM2加密SM4密钥 -> SM2签名data密文
     * 
     * @param array $data 业务数据
     * @return array
     */
    public function assemblyRequestParams($data)
    {
        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);

        // 1. 生成16位SM4随机密钥
        $sm4Key = random(16);

        // 2. 使用平台公钥加密SM4密钥
        $encryptionKey = $this->sm2Encrypt($sm4Key);

        // 3. 使用SM4-CBC加密业务数据
        $encryptedData = $this->sm4Encrypt($dataJson, $sm4Key);

        // 4. 使用商户私钥对data密文进行SM2签名
        $sign = $this->sm2Sign($encryptedData);

        return [
            'data'           => $encryptedData,
            'customerNumber' => $this->customerNumber,
            'encryptionKey'  => $encryptionKey,
            'signType'       => self::SIGN_TYPE,
            'sign'           => $sign,
            'version'        => self::VERSION,
            'timestamp'      => (string)(microtime(true) * 1000),
        ];
    }

    /**
     * 验签(用于响应或回调)
     * @param mixed $data 待验签数据，数组会被json_encode
     * @param string $sign Base64签名
     * @return bool
     */
    public function verifySign($data, $sign)
    {
        $dataStr = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : (string)$data;
        $sm2 = new Rtgm\sm\RtSm2('base64', true);
        return $sm2->verifySign($dataStr, $sign, $this->platformPublicKey);
    }

    /**
     * SM2加密(平台公钥)
     * @param string $plaintext 明文
     * @return string Base64密文
     */
    private function sm2Encrypt($plaintext)
    {
        $sm2 = new Rtgm\sm\RtSm2('base64');
        $hex = $sm2->doEncrypt($plaintext, $this->platformPublicKey);
        return base64_encode(hex2bin('04' . $hex));
    }

    /**
     * SM2签名(商户私钥)
     * @param string $data 待签名内容
     * @return string Base64签名，去除换行
     */
    private function sm2Sign($data)
    {
        $sm2 = new Rtgm\sm\RtSm2('base64', true);
        $sign = $sm2->doSign($data, $this->merchantPrivateKey);
        return str_replace(["\r", "\n"], '', trim($sign));
    }

    /**
     * SM4-CBC加密
     * @param string $plaintext 明文
     * @param string $key 16字节密钥
     * @return string Base64密文
     */
    private function sm4Encrypt($plaintext, $key)
    {
        $iv = base64_decode(self::SM4_IV_BASE64);
        if (strlen($iv) !== 16) {
            throw new Exception('SM4 IV长度必须为16字节');
        }
        $sm4 = new Rtgm\sm\RtSm4($key);
        return $sm4->encrypt($plaintext, 'sm4-cbc', $iv, 'base64');
    }

    /**
     * SM4-CBC解密(用于解析加密响应时使用)
     * @param string $ciphertext Base64密文
     * @param string $key 16字节密钥
     * @return string 明文
     */
    public function sm4Decrypt($ciphertext, $key)
    {
        $iv = base64_decode(self::SM4_IV_BASE64);
        $sm4 = new Rtgm\sm\RtSm4($key);
        return $sm4->decrypt($ciphertext, 'sm4-cbc', $iv, 'base64');
    }
}
