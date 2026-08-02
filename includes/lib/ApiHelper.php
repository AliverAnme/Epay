<?php
namespace lib;

use Exception;

class ApiHelper
{
    //无需签名验证的接口
    private static $exclude_list = [
        'pay/submit',
        'pay/create',
        'complain/image'
    ];

    public static function load_api($s){
        if(preg_match('/^(.[a-zA-Z0-9\_]+)\/(.[a-zA-Z0-9\_]+)$/',$s, $matchs)){
            $class = $matchs[1];
            $func = $matchs[2];
            $classname = '\\lib\\api\\'.ucfirst($class).'';
            if (class_exists($classname) && method_exists($classname, $func)) {
                try{
                    if(in_array($class.'/'.$func, self::$exclude_list)){
                        $classname::$func();
                    }else{
                        self::verify();
                        $result = $classname::$func();
                        $result['timestamp'] = time().'';
                        $result['sign_type'] = 'RSA';
                        $result['sign'] = \lib\Payment::makeSign($result, null);
                        echojson($result);
                    }
                }catch(Exception $e){
                    $code = $e->getCode();
                    echojsonmsg($e->getMessage(), $code != 0 ? $code : -1);
                }
            }else{
                echojsonmsg('接口方法不存在', -5);
            }
        }else{
            echojsonmsg('URL Error!', -5);
        }
    }

    private static function verify(){
        global $DB, $conf, $userrow, $queryArr;

        if(isset($_POST['pid'])){
            $queryArr=$_POST;
        }else{
            throw new Exception('未传入任何参数', -4);
        }

        $pid=intval($queryArr['pid']);
        if(empty($pid))throw new Exception('商户ID不能为空');
        $userrow=$DB->getRow("SELECT `uid`,`gid`,`key`,`money`,`channelinfo`,`keytype`,`publickey`,`status`,`pay`,`settle`,`refund`,`transfer` FROM `pre_user` WHERE `uid`='{$pid}' LIMIT 1");
        if(!$userrow)throw new Exception('商户不存在！');
        if($userrow['status']==0)throw new Exception('商户已被封禁');

        try{
            self::api_verify($userrow, $queryArr);
        }catch(Exception $e){
            throw new Exception($e->getMessage(), -3);
        }
    }

    //API签名校验
    static public function api_verify($userrow, $queryArr, $forceRsa = false){
        global $conf;
        if($forceRsa && $queryArr['sign_type'] != 'RSA')throw new Exception('该接口只能使用RSA签名类型');
        if($userrow['keytype'] == 1 && $queryArr['sign_type'] != 'RSA')throw new Exception('该商户只能使用RSA签名类型');
        //timestamp校验开关，兼容不传timestamp的老平台。优先级：部署常量API_TIMESTAMP_CHECK > 后台配置api_timestamp_check > 默认开启
        $api_timestamp_check = isset($conf['api_timestamp_check']) && $conf['api_timestamp_check'] !== '' ? $conf['api_timestamp_check'] : 1;
        if(defined('API_TIMESTAMP_CHECK')) $api_timestamp_check = API_TIMESTAMP_CHECK;
        if($api_timestamp_check){
            if(empty($queryArr['timestamp'])){
                throw new Exception('timestamp 不能为空');
            }
            if(abs(time() - $queryArr['timestamp']) > 300){
                throw new Exception('时间戳字段不正确，请检查服务器时间');
            }
        }
        if(!empty($queryArr['nonce'])){
            if(strlen($queryArr['nonce']) < 8){
                throw new Exception('nonce 长度不能小于8位');
            }
            global $CACHE;
            $cacheKey = 'api_nonce:'.$userrow['uid'].':'.$queryArr['nonce'];
            if($CACHE->read($cacheKey)){
                throw new Exception('请求已被使用（nonce 重复）');
            }
            $CACHE->save($cacheKey, 1, 600);
        }
        $sign_type = $queryArr['sign_type'] ? $queryArr['sign_type'] : 'MD5';
        if(!\lib\Payment::verifySign($queryArr, $userrow['key'], $userrow['publickey']))throw new Exception($sign_type.'签名校验失败');
    }
}