<?php
//抖音支付配置文件

$douyinpay_config = [
    //应用ID
    'appid' => $channel['appid'],

    //商户号
    'mchid' => $channel['appmchid'],

    //接口加密密钥
    'apikey' => $channel['apikey'],

    //「商户API私钥」文件路径
    'merchantPrivateKeyFilePath' => PLUGIN_ROOT.$channel['plugin'].'/cert/apiclient_key.pem',

    //「商户API证书」的「证书序列号」
    'merchantCertificateSerial' => $channel['certserial'],

    //「微信支付平台证书」文件路径
    'platformCertificateFilePath' => PLUGIN_ROOT.$channel['plugin'].'/cert/cert.pem',
];

if(file_exists(PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/apiclient_key.pem')){
    $douyinpay_config['merchantPrivateKeyFilePath'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/apiclient_key.pem';
    $douyinpay_config['platformCertificateFilePath'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/cert.pem';
}

return $douyinpay_config;