<?php
define('CURR_PATH', dirname(__DIR__));
require CURR_PATH . '/../includes/common.php';
require CURR_PATH . '/jfyui/jfyui_plugin.php';
if (function_exists("set_time_limit")) {
	@set_time_limit(0);
}
$sotime = date('Y-m-d H:i:s');
// 检索所有符合条件的支付通道
$query = "SELECT id FROM pre_channel WHERE plugin = 'jfyui' AND status = 1";
$channels = $DB->query($query)->fetchAll(PDO::FETCH_COLUMN);
if (empty($channels)) {
	exit("暂无须监控通道\n");
}
foreach ($channels as $id) {
	// 检索订单数据
	$id = intval($id);
	$channel = $DB->getRow('SELECT * FROM pre_channel WHERE id = ? LIMIT 1', [$id]);
	$query = "SELECT trade_no,uid,api_trade_no,subchannel FROM pre_order WHERE addtime >= DATE_SUB('{$sotime}', INTERVAL 3 MINUTE) AND status = 0 AND channel = '{$id}'";
	$result = $DB->query($query);
	$orders = $result->fetchAll(PDO::FETCH_ASSOC);
	if (empty($orders)) {
		echo "通道ID为 {$id} 的通道暂无需监控订单\n";
	} else {
		foreach ($orders as $order) {
			// 子通道
			if (!empty($order['subchannel']) && $order['subchannel'] != 0) {
				$channelInfo = $DB->getColumn("SELECT info FROM pre_subchannel WHERE id='{$order['subchannel']}' LIMIT 1");
				$channel = \lib\Channel::get($id, $channelInfo);
			} else {
				// 非子直清
				if ($channel['mode'] == 1) {
					$channelInfo = $DB->getColumn("SELECT channelinfo FROM pre_user WHERE uid='{$order['uid']}' LIMIT 1");
					$channel = \lib\Channel::get($id, $channelInfo);
				} else {
					// 非子代收
					$channel = \lib\Channel::get($id);
				}
			}
			$tradeNo = $order['trade_no'];
			$api_trade_no = $order['api_trade_no'];
			$result = jfyui_plugin::btjk($tradeNo, $channel, $api_trade_no);
			echo $result . "\n";
		}
	}
}