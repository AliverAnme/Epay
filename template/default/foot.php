<?php
if(!defined('IN_CRONLITE'))exit();
?>

<div class="address">
<footer>
<div class="container">
<div class="footer-grid">
<div class="footer-brand">
<img src="/assets/img/logo.png" alt="<?php echo h($conf['sitename'])?>">
<p>让支付接入更简单，让每一笔收款都清晰可控。</p>
</div>
<div class="footer-column">
<h4>产品</h4>
<a href="agreement.html" target="_blank" rel="noopener noreferrer">服务条款</a>
<a href="doc.html" target="_blank" rel="noopener noreferrer">开发文档</a>
</div>
<div class="footer-column">
<h4>关于我们</h4>
<p><?php echo h($conf['sitename'])?>是<?php echo h($conf['orgname'])?>旗下的免签约支付产品。</p>
</div>
<div class="footer-column">
<h4>联系我们</h4>
<a href="https://wpa.qq.com/msgrd?v=3&uin=<?php echo h($conf['kfqq'])?>&Site=pay&Menu=yes" target="_blank" rel="noopener noreferrer">QQ：<?php echo h($conf['kfqq'])?></a>
<?php if(!empty($conf['email'])){?><a href="mailto:<?php echo h($conf['email'])?>">Email：<?php echo h($conf['email'])?></a><?php }?>
</div>
</div>
<div class="xinxi">
<p><?php echo h($conf['sitename'])?>&nbsp;&nbsp;&copy;<?php echo date("Y")?>&nbsp;All Rights Reserved.&nbsp;&nbsp;<?php echo $conf['footer']?></p>
</div>
<script type="text/javascript">
        if('ontouchend' in document.body && $(window).width() < 996){
          $('.col-xs-12 .h2').css('text-align','center');
        }
      </script>
</div>
</footer>
</div>
</div>
</body>
</html>
