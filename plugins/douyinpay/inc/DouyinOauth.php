<?php
namespace DouYinPay;

use Exception;

class DouyinOauth
{
    private $client_key;
    private $client_secret;

    public function __construct($client_key, $client_secret)
    {
        $this->client_key = $client_key;
        $this->client_secret = $client_secret;
    }

    public function get_openid($redirect_uri, $state = null)
    {
        if (isset($_GET['code'])) {
            $code = $_GET['code'];
            $data = $this->callback($code);
            return $data['open_id'];
        }else{
            $this->login($redirect_uri, $state);
        }
    }

    public function login($redirect_uri, $state = null)
    {
        $param = [
            "client_key" => $this->client_key,
            "scope" => "login_id",
            "response_type" => "code",
            "redirect_uri" => $redirect_uri,
        ];
        if($state) $param['state'] = $state;
        $url = 'https://aweme.snssdk.com/passport/open/silent_auth/?' . http_build_query($param);
        Header("Location: $url");
        exit;
    }

    public function callback(string $code): array
    {
        $param = [
            "client_key" => $this->client_key,
            "client_secret" => $this->client_secret,
            "code" => $code,
            "grant_type" => "authorization_code"
        ];
        $url = 'https://open.douyin.com/oauth/access_token/';
        $res = get_curl($url, http_build_query($param));
        $data = json_decode($res, true);
        if (isset($data['data']['error_code']) && $data['data']['error_code'] == 0) {
            return $data['data'];
        } elseif (isset($data['data']['error_code'])) {
            throw new Exception('Openid获取失败 [' . $data['data']['error_code'] . ']' . $data['data']['description']);
        } else {
            throw new Exception('Openid获取失败，原因未知');
        }
    }
}