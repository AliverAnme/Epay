<?php
/**
 * 支付插件
**/
include("../includes/common.php");
$title='支付插件';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-12 col-lg-10 center-block" style="float: none;">
<?php
$my=isset($_POST['my'])?$_POST['my']:(isset($_GET['my'])?$_GET['my']:null);
if($my=='refresh') {
	if($_SERVER['REQUEST_METHOD']!=='POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token']!==$_SESSION['admin_csrf_token']) showmsg('CSRF验证失败',3);
	\lib\Plugin::updateAll();
	exit("<script language='javascript'>alert('刷新插件列表成功！');history.go(-1);</script>");
}else{
$list = \lib\Plugin::getAll();
?>
<div class="panel panel-info">
   <div class="panel-heading"><h3 class="panel-title">系统共有 <b><?php echo count($list);?></b> 个支付插件&nbsp;<span class="pull-right"><form method="POST" style="display:inline"><input type="hidden" name="my" value="refresh"/><input type="hidden" name="csrf_token" value="<?php echo $admin_csrf_token?>"/><button type="submit" class="btn btn-default btn-xs"><i class="fa fa-refresh"></i> 刷新插件列表</button></form></span></h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>插件名称</th><th>插件描述</th><th>插件作者</th><th>包含的支付方式</th><th>包含的转账方式</th><th>分账</th></tr></thead>
          <tbody>
<?php
foreach($list as $res)
{
  $ps_support = in_array($res['name'], \lib\ProfitSharing\CommUtil::$plugins) ? '支持' : '';
echo '<tr><td><b>'.h($res['name']).'</b></td><td>'.h($res['showname']).'</td><td>'.($res['link']?'<a href="'.h($res['link']).'" target="_blank" rel="noreferrer">'.h($res['author']).'
</a>':h($res['author'])).'</td><td>'.h($res['types']).'</td><td>'.h($res['transtypes']).'</td><td>'.$ps_support.'</td></tr>';
}
?>
          </tbody>
        </table>
      </div>
	  <div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 支付插件目录：/plugins/，请将符合要求的支付插件源码解压到支付插件目录，然后点击 刷新插件列表 即可显示在该列表中。
        </div>
	</div>
<?php }?>
    </div>
  </div>