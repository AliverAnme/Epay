<?php
/**
 * 微信小程序回调页面
 */
include("./inc.php");

$weworkMsg = new \lib\wechat\WeWorkMsg($conf['wxappkf_token'], $conf['wxappkf_aeskey']);

if(isset($_GET['echostr'])) {
    $weworkMsg->verifyURL(true);
}

$msg = $weworkMsg->getMessage();

if(!isset($msg['MsgType'])) exit('消息内容异常');

if($msg['MsgType'] == 'event' && $msg['Event'] == 'user_enter_tempsession'){
    $openid = $msg['FromUserName'];
    $orderid = $msg['SessionFrom'];
    if(empty($openid) || empty($orderid)) exit('success');
    
    $wechat = new \lib\wechat\WechatAPI($conf['wxappkf_applet']);

    $order = $DB->find('order', 'trade_no,realmoney,payurl', ['trade_no'=>$orderid]);
    if($order){
        if(!empty($order['payurl'])){
            $payurl = $order['payurl'];
            $msgtype = 'link';
            $picurl = $siteurl.'assets/img/wxpay.png';
            $content = ['title'=>'点击支付', 'description'=>'金额：'.$order['realmoney'].'元', 'url'=>$payurl, 'thumb_url'=>$picurl];
        }else{
            $msgtype = 'text';
            $content = ['content' => '订单支付链接不存在'];
        }
    }else{
        $msgtype = 'text';
        $content = ['content' => '订单不存在'];
    }

    try{
        $wechat->sendCustomMessage($openid, $msgtype, $content);
    }catch(Exception $e){
        $errmsg = $e->getMessage();
        $CACHE->save('wxappkferrmsg', ['errmsg'=>$errmsg, 'time'=>$date], 86400);
        exit($errmsg);
    }
    echo 'success';
}elseif(!empty($msg['MsgType']) && $msg['MsgType'] != 'event'){
    if($conf['wxappkf_msg_transfer'] && !empty($msg['FromUserName']) && !empty($msg['ToUserName'])){
        $wxinfo = \lib\Channel::getWeixin($conf['wxappkf_applet']);
        if(empty($wxinfo)) exit('success');
        $weworkMsg->responseMessage([
            'ToUserName' => $msg['FromUserName'],
            'FromUserName' => $msg['ToUserName'],
            'CreateTime' => time(),
            'MsgType' => 'transfer_customer_service'
        ], $wxinfo['appid']);
        exit;
    }
    echo 'success';
}