<?php

namespace DouYinPay;

require 'BaseService.php';

use Exception;

/**
 * 基础支付服务类
 * @see https://pay.douyinpay.com/wiki
 */
class PaymentService extends BaseService
{
    public function __construct(array $config)
    {
        parent::__construct($config);
    }


	/**
	 * NATIVE支付
	 * @param array $params 下单参数
	 * @return mixed {"code_url":"二维码链接"}
	 * @throws Exception
	 */
    public function nativePay(array $params){
        $path = '/v1/trade/transactions/native';
        $publicParams = [
            'appid' => $this->appId,
            'mchid' => $this->mchId,
        ];
        $params = array_merge($publicParams, $params);
        return $this->execute('POST', $path, $params);
    }

	/**
	 * JSAPI支付
	 * @param array $params 下单参数
	 * @return array Jsapi支付json数据
	 * @throws Exception
	 */
    public function jsapiPay(array $params): array
    {
        $path = '/v1/trade/transactions/jsapi';
        $publicParams = [
            'appid' => $this->appId,
            'mchid' => $this->mchId,
        ];
        $params = array_merge($publicParams, $params);
        $result = $this->execute('POST', $path, $params);
        return $this->getJsApiParameters($result['prepay_id']);
    }

    /**
     * 获取JSAPI支付的参数
     * @param string $prepay_id 预支付交易会话标识
     * @return array json数据
     */
    private function getJsApiParameters(string $prepay_id): array
    {
        $params = [
            'appId' => $this->appId,
            'timeStamp' => time().'',
            'nonceStr' => $this->getNonceStr(),
            'package' => 'prepay_id=' . $prepay_id,
        ];
        $params['paySign'] = $this->makeSign([$params['appId'], $params['timeStamp'], $params['nonceStr'], $params['package']]);
        $params['signType'] = 'DouyinPay-RSA';
        return $params;
    }

	/**
	 * H5支付
	 * @param array $params 下单参数
	 * @return mixed {"h5_url":"支付跳转链接"}
	 * @throws Exception
	 */
    public function h5Pay(array $params){
        $path = '/v1/trade/transactions/h5';
        $publicParams = [
            'appid' => $this->appId,
            'mchid' => $this->mchId,
        ];
        $params = array_merge($publicParams, $params);
        return $this->execute('POST', $path, $params);
    }

	/**
	 * APP支付
	 * @param array $params 下单参数
	 * @return array APP支付json数据
	 * @throws Exception
	 */
    public function appPay(array $params): array
    {
        $path = '/v1/trade/transactions/app';
        $publicParams = [
            'appid' => $this->appId,
            'mchid' => $this->mchId,
        ];
        $params = array_merge($publicParams, $params);
        $result = $this->execute('POST', $path, $params);
        return $this->getAppParameters($result['prepay_id']);
    }

    /**
     * 获取APP支付的参数
     * @param string $prepay_id 预支付交易会话标识
     * @return array
     */
    private function getAppParameters(string $prepay_id): array
    {
        $params = [
            'appid' => $this->appId,
            'partnerid' => $this->mchId,
            'prepayid' => $prepay_id,
            'package' => 'Sign=DYPay',
            'noncestr' => $this->getNonceStr(),
            'timestamp' => time().'',
        ];
        $params['sign'] = $this->makeSign([$params['appid'], $params['timestamp'], $params['noncestr'], $params['prepayid']]);
        return $params;
    }

	/**
	 * 查询订单，抖音订单号、商户订单号至少填一个
	 * @param string|null $transaction_id 抖音订单号
	 * @param string|null $out_trade_no 商户订单号
	 * @return mixed
	 * @throws Exception
	 */
    public function orderQuery(string $transaction_id = null, string $out_trade_no = null){
        if(!empty($transaction_id)){
            $path = '/v1/trade/transactions/id/'.$transaction_id;
        }elseif(!empty($out_trade_no)){
            $path = '/v1/trade/transactions/out-trade-no/'.$out_trade_no;
        }else{
            throw new Exception('抖音支付订单号和商户订单号不能同时为空');
        }
        
        $params = [
            'mchid' => $this->mchId,
        ];
        return $this->execute('GET', $path, $params);
    }

    /**
     * 判断订单是否已完成
     * @param string $transaction_id 抖音订单号
     * @return bool
     */
    public function orderQueryResult(string $transaction_id): bool
    {
        try {
            $data = $this->orderQuery($transaction_id);
            return $data['trade_state'] == 'SUCCESS' || $data['trade_state'] == 'REFUND';
        } catch (Exception $e) {
            return false;
        }
    }

	/**
	 * 关闭订单
	 * @param string $out_trade_no 商户订单号
	 * @return mixed
	 * @throws Exception
	 */
    public function closeOrder(string $out_trade_no){
        $path = '/v1/trade/transactions/out-trade-no/'.$out_trade_no.'/close';
        $params = [
            'mchid' => $this->mchId,
        ];
        return $this->execute('POST', $path, $params);
    }

	/**
	 * 申请退款
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
    public function refund(array $params){
        $path = '/v1/trade/refund/domestic/refunds';
        $publicParams = [
            'mchid' => $this->mchId,
        ];
        $params = array_merge($publicParams, $params);
        return $this->execute('POST', $path, $params);
    }

	/**
	 * 查询退款
	 * @param string $out_refund_no
	 * @return mixed
	 * @throws Exception
	 */
    public function refundQuery(string $out_refund_no){
        $path = '/v1/trade/refund/domestic/refunds/'.$out_refund_no;
        $params = [
            'mchid' => $this->mchId,
        ];
        return $this->execute('GET', $path, $params);
    }

	/**
	 * 申请交易账单
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
    public function tradeBill(array $params){
        $path = '/v1/bill/billapply';
        $publicParams = [
            'mchid' => $this->mchId,
        ];
        $params = array_merge($publicParams, $params);
        return $this->execute('GET', $path, $params);
    }

	/**
	 * 申请资金账单
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
    public function fundflowBill(array $params){
        $path = '/v1/bill/fundflowbill';
        $publicParams = [
            'mchid' => $this->mchId,
        ];
        $params = array_merge($publicParams, $params);
        return $this->execute('GET', $path, $params);
    }

    /**
     * 申请结算账单
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function billApply(array $params){
        $path = '/v1/bill/billapply';
        $publicParams = [
            'mchid' => $this->mchId,
        ];
        $params = array_merge($publicParams, $params);
        return $this->execute('GET', $path, $params);
    }

    /**
     * 申请分账账单
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function splitBill(array $params){
        $path = '/v1/bill/splitbill';
        $publicParams = [
            'mchid' => $this->mchId,
        ];
        $params = array_merge($publicParams, $params);
        return $this->execute('GET', $path, $params);
    }

	/**
	 * 支付通知处理
	 * @return array 支付成功通知参数
	 * @throws Exception
	 */
    public function notify(): array
    {
        $data = parent::notify();
        if (!$data || !isset($data['transaction_id'])) {
            throw new Exception('缺少订单号参数');
        }
        if (!$this->orderQueryResult($data['transaction_id'])) {
            throw new Exception('订单未完成');
        }
        return $data;
    }

}