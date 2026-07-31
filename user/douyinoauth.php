<?php
$nosession = true;
include("../includes/common.php");

// 抖音 OAuth 回调只允许回到站内相对路径，且不把授权 code 暴露到地址栏。
if(isset($_GET['code']) && isset($_GET['state'])){
	$state = is_string($_GET['state']) ? $_GET['state'] : '';
	if($state !== ''){
		$valid_state = preg_match('#^/(?!/)[A-Za-z0-9._~!$&\'()*+,;=:@%/-]*$#', $state) && strpos($state, '\\') === false;
		if(!$valid_state) exit('Invalid state');
		header('Location: '.rtrim($siteurl, '/').'/'.ltrim($state, '/'));
	}else{
		header('Location: '.$siteurl);
	}
	exit;
}

header('Location: '.$siteurl);
exit;
