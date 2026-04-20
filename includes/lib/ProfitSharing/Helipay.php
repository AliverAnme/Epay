<?php

namespace lib\ProfitSharing;

require_once PLUGIN_ROOT . 'helipay/inc/HelipayClient.php';

use Exception;

class Helipay implements IProfitSharing
{
    static $paytype = 'helipay';

    private $channel;
    /** @var \HelipayClient */
    private $client;

    function __construct($channel)
    {
        $this->channel = $channel;
        $merchantNo = !empty($channel['appmchid']) ? $channel['appmchid'] : $channel['appid'];
        $this->client = new \HelipayClient($merchantNo, $channel['split_pubkey'], $channel['split_prikey']);
    }

    //请求分账
    public function submit($trade_no, $api_trade_no, $order_money, $info)
    {
        $orderId = date('YmdHis') . rand(1000, 9999);
        $ruleJson = [];
        $allmoney = 0;
        $rdata = [];
        foreach ($info as $receiver) {
            $money = round(floor($order_money * $receiver['rate']) / 100, 2);
            $ruleJson[] = [
                'splitBillMerchantNo' => $receiver['account'],
                'splitBillAmount' => $money,
            ];
            $allmoney += $money;
            $rdata[] = ['account' => $receiver['account'], 'money' => $money];
        }

        $params = [
            'orderId' => $orderId,
            'originalOrderId' => $trade_no,
            'originalProductCode' => 'APPPAY',
            'ruleJson' => $ruleJson,
        ];

        try {
            $result = $this->client->execute('/trx/delayedSplit/apply', $params);
            return ['code' => $result['status'] == 'SUCCESS' ? 1 : 0, 'msg' => '分账成功', 'settle_no' => $orderId, 'money' => round($allmoney, 2), 'rdata' => $rdata, 'result'=>$result];
        } catch (Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no)
    {
        $params = ['orderId' => $settle_no];
        try {
            $result = $this->client->execute('/trx/delayedSplit/apply/query', $params);
            if (isset($result['status']) && $result['status'] == 'SUCCESS') {
                return ['code' => 0, 'status' => 1];
            }
            if (isset($result['status']) && in_array($result['status'], ['FAIL', 'REFUNDED'])) {
                return ['code' => 0, 'status' => 2];
            }
            return ['code' => 0, 'status' => 0];
        } catch (Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    //解冻剩余资金
    public function unfreeeze($trade_no, $api_trade_no)
    {
        global $DB;
        $requestNo = date('YmdHis') . rand(1000, 9999);
        $order_money = $DB->findColumn('order', 'realmoney', ['trade_no'=>$trade_no]);
        $params = [
            'requestNo' => $requestNo,
            'bizOrderNum' => $trade_no,
            'productEnumType' => 'APPPAY',
            'amount' => $order_money,
            'adjustType' => 'DECREASE',
        ];
        try {
            $this->client->execute('/trx/splittableAmount/adjust', $params);
            return ['code' => 0, 'msg' => '解冻剩余资金成功'];
        } catch (Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $settle_no, $rdata)
    {
        $refundOrderId = date('YmdHis') . rand(1000, 9999);
        $ruleJson = [];
        foreach ($rdata as $row) {
            $ruleJson[] = [
                'refundAmount' => (float)$row['money'],
                'merchantNo'   => $row['account'],
            ];
        }
        $params = [
            'refundOrderId' => $refundOrderId,
            'originalOrderId' => $settle_no,
            'ruleJson' => $ruleJson,
        ];
        try {
            $this->client->execute('/trx/delayedSplit/back', $params);
            return ['code' => 0, 'msg' => '分账回退成功'];
        } catch (Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    //添加分账接收方
    public function addReceiver($account, $name = null)
    {
        return ['code' => 0, 'msg' => '添加分账接收方成功'];
    }

    //删除分账接收方
    public function deleteReceiver($account)
    {
        return ['code' => 0, 'msg' => '删除分账接收方成功'];
    }
}
