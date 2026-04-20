<?php

define('API_KEY', '{apikey}'); //此处填写API密钥


if (function_exists("ignore_user_abort")) @ignore_user_abort(true);

$url = isset($_POST['url']) ? $_POST['url'] : exit('{"code":-1,"msg":"No url"}');
$sign = isset($_POST['sign']) ? $_POST['sign'] : exit('{"code":-1,"msg":"No sign"}');
$timestamp = isset($_POST['timestamp']) ? intval($_POST['timestamp']) : exit('{"code":-1,"msg":"No timestamp"}');

if(abs(time() - $timestamp) > 300){
    exit('{"code":-1,"msg":"时间戳异常"}');
}
if(md5($url . $timestamp . API_KEY) !== $sign){
    exit('{"code":-1,"msg":"签名验证失败"}');
}

$url = base64_decode($url);
if(!is_url($url)){
    exit('{"code":-1,"msg":"URL不合法"}');
}

echo curl_get($url);


function curl_get($url)
{
	global $conf;
	$ch=curl_init($url);
	$httpheader[] = "Accept: */*";
	$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
	$httpheader[] = "Connection: close";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36');
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	$content=curl_exec($ch);
	curl_close($ch);
	return $content;
}
function is_url($url){
	if (preg_match('/^(http|https):\/\/[^\s]+/', $url)) {
		return true;
	} else {
		return false;
	}
}