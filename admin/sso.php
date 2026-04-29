<?php
include("../includes/common.php");

if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$uid=intval($_GET['uid']);

$userrow=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
if(!$userrow)sysmsg('当前用户不存在！');

$DB->insert('log', ['uid'=>$uid, 'type'=>'管理员模拟登录', 'date'=>'NOW()', 'ip'=>$clientip]);

$session=md5($uid.$userrow['key'].$password_hash);
$expiretime=time()+604800;
$token=authcode("{$uid}\t{$session}\t{$expiretime}", 'ENCODE', SYS_KEY);
$secure = is_https();
setcookie("user_token", $token, time() + 604800, '/', '', $secure, true);

exit("<script language='javascript'>window.location.href='../user/';</script>");
