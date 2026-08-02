<?php
/**
 * 后台 React/shadcn 入口共用辅助函数。
 * 页面只负责准备后端数据契约，具体 UI 由 frontend/src/components/epay 渲染。
 */
if(!function_exists('epay_admin_view')){
	function epay_admin_view($view, $config = [], $title = '平台管理'){
		global $islogin, $conf, $admin_csrf_token, $epay_ui_view, $epay_ui_config;
		if($islogin != 1) exit("<script>window.location.href='./login.php';</script>");
		if(!isset($_SESSION['admin_csrf_token'])) $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
		$admin_csrf_token = $_SESSION['admin_csrf_token'];
		$epay_ui_view = (string)$view;
		$epay_ui_config = is_array($config) ? $config : [];
		$epay_ui_config['title'] = isset($epay_ui_config['title']) ? (string)$epay_ui_config['title'] : (string)$title;
		$epay_ui_config['sitename'] = isset($epay_ui_config['sitename']) ? (string)$epay_ui_config['sitename'] : (string)($conf['sitename'] ?? 'Rainbow Pay');
		$epay_ui_config['csrf_token'] = $admin_csrf_token;
		include __DIR__.'/head.php';
		exit('</body></html>');
	}
}
