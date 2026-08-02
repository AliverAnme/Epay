<?php
if(!defined('IN_CRONLITE'))exit();
$epay_transfer_config=[
	'kind'=>'red-wx',
	'sitename'=>$conf['sitename'],
	'amount'=>$trans['money'],
	'createdAt'=>$trans['addtime'],
	'payload'=>[
		'n'=>$biz_no,
		't'=>$time,
		's'=>$sign,
		'openid'=>$openid,
	],
];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>红包领取确认</title>
    <link href="/assets/dist/epay-ui.css" rel="stylesheet">
</head>
<body>
<div id="epay-react-root" data-epay-view="transfer-confirm" data-epay-config="<?php echo h(json_encode($epay_transfer_config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE))?>"></div>
<script src="//res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
<script type="module" src="/assets/dist/epay-ui.js"></script>
<script>
window.__epayWxReady = false;
window.__epayWxError = '';
wx.config(<?php echo $wxconfig?>);
wx.ready(function () {
  wx.checkJsApi({
    jsApiList: ['requestMerchantTransfer'],
    success: function (res) {
      if (res.checkResult['requestMerchantTransfer']) {
        window.__epayWxReady = true;
        window.dispatchEvent(new Event('epay-wx-state'));
      } else {
        window.__epayWxError = '你的微信版本过低，请更新至最新版本。';
        window.dispatchEvent(new Event('epay-wx-state'));
      }
    }
  });
});
wx.error(function(res){
  window.__epayWxError = '微信收款能力加载失败，请刷新页面重试。';
  window.dispatchEvent(new Event('epay-wx-state'));
});
</script>
</body>
</html>
