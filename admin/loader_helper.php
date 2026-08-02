<?php
/** Swoole Loader 安装助手的 shadcn 页面契约。 */
if(!defined('IN_CRONLITE')) exit('Access Denied');
require_once __DIR__.'/epay_ui_entry.php';

preg_match("#^\d.\d#", PHP_VERSION, $p_v);
$php_v = str_replace('.', '', $p_v[0] ?? PHP_VERSION);
$is_win = strtolower(substr(PHP_OS, 0, 3)) === 'win';
$loader_ext = $is_win ? 'dll' : 'so';
$loader_path = str_replace('/', DIRECTORY_SEPARATOR, ROOT.'assets/loader/swoole_loader_'.$php_v.'_nts.'.$loader_ext);
$notice = PHP_VERSION < 8.0 || PHP_VERSION >= 8.1
	? '当前插件只支持 PHP 8.0。'
	: (!file_exists($loader_path) ? 'Swoole Loader 文件不存在，请联系管理员手动安装。' : '请按照下方步骤完成 PHP 扩展配置。');

epay_admin_view('admin-form', [
	'title'=>'Swoole Loader 安装助手',
	'description'=>'使用原生 shadcn 信息卡查看扩展安装步骤。',
	'notice'=>$notice,
	'fields'=>[
		['key'=>'php_ini','label'=>'PHP 配置文件','value'=>(string)(php_ini_loaded_file() ?: '未找到'),'readOnly'=>true],
		['key'=>'extension','label'=>'扩展配置','value'=>'extension='.$loader_path,'readOnly'=>true],
	],
]);
