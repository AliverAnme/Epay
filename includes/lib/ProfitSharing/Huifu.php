<?php

namespace lib\ProfitSharing;

require_once PLUGIN_ROOT . 'huifu/inc/HuifuClient.php';

use Exception;

class Huifu implements IProfitSharing
{

    static $paytype = 'huifu';

    private $channel;
    private $service;

    function __construct($channel){
		$this->channel = $channel;
        $config_info = [
			'sys_id' =>  $channel['appid'],
			'product_id' => $channel['appurl'],
			'merchant_private_key' => $channel['appsecret'],
			'huifu_public_key' => $channel['appkey'],
		];
        $this->service = new \HuifuClient($config_info);
	}

    //请求分账
    public function submit($trade_no, $api_trade_no, $order_money, $info){
        global $clientip;
        $acct_infos = [];
        $allmoney = 0;
        $rdata = [];
        foreach($info as $receiver){
            $money = round(floor($order_money * $receiver['rate']) / 100, 2);
            $acct_infos[] = ['huifu_id'=>$receiver['account'], 'div_amt' => sprintf('%.2f' , $money)];
            $allmoney += $money;
            $rdata[] = ['account'=>$receiver['account'], 'money'=>$money];
        }
        $params = [
            'req_date' => date('Ymd'),
            'req_seq_id' => date("YmdHis").rand(11111,99999),
            'huifu_id' => $this->channel['appmchid']?$this->channel['appmchid']:$this->channel['appid'],
            'org_hf_seq_id' => $api_trade_no,
            'acct_split_bunch' => json_encode(['acct_infos' => $acct_infos]),
            'risk_check_data' => json_encode(['ip_addr' => $clientip]),
        ];

        try{
            $result = $this->service->requestApi('/v2/trade/payment/delaytrans/confirm', $params);
            if(isset($result['resp_code']) && $result['resp_code']=='00000000') {
                if($result['trans_stat'] == 'S' || $result['trans_stat'] == 'P'){
                    return ['code'=>$result['trans_stat'] == 'S' ? 1 : 0, 'msg'=>'分账成功', 'settle_no'=>$params['req_seq_id'], 'money'=>round($allmoney, 2), 'rdata'=>$rdata];
                }else{
                    throw new Exception('分账失败：'.$result['trans_stat']);
                }
            }elseif(isset($result['resp_desc'])){
				throw new Exception($result['resp_desc']);
			}else{
				throw new Exception('返回数据解析失败');
			}
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no){
        $params = [
            'huifu_id' => $this->channel['appmchid']?$this->channel['appmchid']:$this->channel['appid'],
            'org_req_date' => substr($trade_no, 0, 8),
            'org_req_seq_id' => $trade_no,
        ];
        try{
            $result = $this->service->requestApi('/v3/trade/payment/delaytrans/confirmquery', $params);
            if(isset($result['resp_code']) && $result['resp_code']=='00000000') {
                return ['code'=>0, 'status'=>$result['trans_stat'] == 'S' ? 1 : 0];
            }else{
                throw new Exception($result['resp_desc']?$result['resp_desc']:'返回数据解析失败');
            }
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //解冻剩余资金
    public function unfreeeze($trade_no, $api_trade_no){
        return ['code'=>-1,'msg'=>'不支持当前操作'];
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $settle_no, $rdata){
        $params = [
            'req_date' => date('Ymd'),
            'req_seq_id' => date("YmdHis").rand(11111,99999),
            'huifu_id' => $this->channel['appmchid']?$this->channel['appmchid']:$this->channel['appid'],
            'org_req_date' => substr($settle_no, 0, 8),
            'org_req_seq_id' => $settle_no,
        ];
        try{
            $result = $this->service->requestApi('/v2/trade/payment/delaytrans/confirmrefund', $params);
            if(isset($result['resp_code']) && $result['resp_code']=='00000000') {
                return ['code'=>0, 'msg'=>'退分账成功'];
            }else{
                throw new Exception($result['resp_desc']?$result['resp_desc']:'返回数据解析失败');
            }
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //添加分账接收方
    public function addReceiver($account, $name = null){
        return ['code'=>0, 'msg'=>'添加分账接收方成功'];
    }

    //删除分账接收方
    public function deleteReceiver($account){
        return ['code'=>0, 'msg'=>'删除分账接收方成功'];
    }
}