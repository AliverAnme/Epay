<?php

namespace lib\ProfitSharing;

use Exception;

class Haipay implements IProfitSharing
{

    static $paytype = 'haipay';

    private $channel;
    private $service;

    function __construct($channel){
		$this->channel = $channel;
	}

    //请求分账
    public function submit($trade_no, $api_trade_no, $order_money, $info){
        $channel = $this->channel;

        require_once PLUGIN_ROOT.'haipay/inc/HaiPayClient.php';

        $client = new \HaiPayClient($channel['accessid'], $channel['accesskey']);

        $relation = [];
        $rdata = [];
        foreach($info as $receiver){
            $money = round(floor($order_money * $receiver['rate']) / 100, 2);
            $relation[] = [
                'receive_no' => $receiver['account'],
                'amt' => sprintf('%.2f' , $money),
            ];
            $allmoney += $money;
            $rdata[] = ['account'=>$receiver['account'], 'money'=>$money];
        }

        $params = [
            'apply_no' => date('YmdHis').rand(1000,9999),
            'agent_no' => $channel['agent_no'],
            'merch_no' => $channel['merch_no'],
            'trade_no' => $api_trade_no,
            'ledger_relation' => $relation,
        ];
        try{
            $result = $client->mchRequest('/api/v1/delay-ledger/ledger', $params);
            return ['code'=>1, 'msg'=>'分账成功', 'settle_no'=>$result['apply_no'], 'money'=>round($allmoney, 2), 'rdata'=>$rdata];
        }catch(Exception $e){
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no){
        $channel = $this->channel;

        require_once PLUGIN_ROOT.'haipay/inc/HaiPayClient.php';

        $client = new \HaiPayClient($channel['accessid'], $channel['accesskey']);

        $params = [
            'agent_no' => $channel['agent_no'],
            'merch_no' => $channel['merch_no'],
            'trade_no' => $api_trade_no,
            'apply_no' => $settle_no,
        ];
        try{
            $result = $client->mchRequest('/api/v1/delay-ledger/query-by-applyno', $params);
            $receiver = $result['ledger_relation'][0];
            if($receiver['result'] == 1){
                return ['code'=>0, 'status'=>1];
            }elseif($receiver['result'] == 0){
                return ['code'=>0, 'status'=>2];
            }else{
                return ['code'=>0, 'status'=>0];
            }
        }catch(Exception $e){
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //解冻剩余资金
    public function unfreeeze($trade_no, $api_trade_no){
        $channel = $this->channel;

        require_once PLUGIN_ROOT.'haipay/inc/HaiPayClient.php';

        $client = new \HaiPayClient($channel['accessid'], $channel['accesskey']);

        $params = [
            'agent_no' => $channel['agent_no'],
            'merch_no' => $channel['merch_no'],
            'trade_no' => $api_trade_no,
        ];
        try{
            $client->mchRequest('/api/v1/delay-ledger/finish', $params);
            return ['code'=>0, 'msg'=>'解冻剩余资金成功'];
        }catch(Exception $e){
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $settle_no, $rdata){
        $channel = $this->channel;

        require_once PLUGIN_ROOT.'haipay/inc/HaiPayClient.php';

        $client = new \HaiPayClient($channel['accessid'], $channel['accesskey']);

        $params = [
            'agent_apply_no' => date('YmdHis').rand(1000,9999),
            'agent_no' => $channel['agent_no'],
            'merch_no' => $channel['merch_no'],
            'trade_Id' => $api_trade_no,
            'collection_type' => '1',
        ];
        if(!empty($settle_no)) $params['delay_apply_no'] = $settle_no;
        try{
            $client->mchRequest('/api/v1/merchant-ledger/collection', $params);
            return ['code'=>0];
        }catch(Exception $e){
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //添加分账接收方
    public function addReceiver($account, $name = null){
        global $DB;
        return ['code'=>0, 'msg'=>'添加分账接收方成功'];
        if($DB->getRow("SHOW TABLES LIKE 'pre_haipay_relation'")){
            if($DB->find("haipay_relation", ['account'=>$account])){
                return ['code'=>0, 'msg'=>'添加分账接收方成功'];
            }
        }
        return ['code'=>-1, 'msg'=>'请先在“进件商户管理-分账关系管理”创建分账关系'];
    }

    //删除分账接收方
    public function deleteReceiver($account){
        return ['code'=>0, 'msg'=>'删除分账接收方成功'];
    }
}