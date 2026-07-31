<?php
if(!defined('IN_CRONLITE'))exit();
require INDEX_ROOT.'head.php';
?>
<main class="landing-page">
<section class="screen1 hero-section" aria-labelledby="hero-title">
<div class="hero-shell">
<div class="container hero-container">
<div class="hero-grid">
<div class="hero-art" aria-hidden="true">
<div class="hero-art-glow"></div>
<img src="<?php echo STATIC_ROOT?>images/banner4.png" class="hero-illustration" alt="">
<div class="hero-art-note"><span class="hero-art-dot"></span> 一站式支付基础设施</div>
</div>
<div class="hero-content">
<p class="hero-eyebrow">PAYMENT INFRASTRUCTURE <span>•</span> READY FOR BUSINESS</p>
<h1 id="hero-title">欢迎使用<?php echo h($conf['sitename'])?></h1>
<p class="hero-copy">提供免签约支付宝、QQ钱包、微信支付，帮助商户更快接入、更稳收款。</p>
<div class="hero-actions">
<a href="/user/" class="btn hero-button hero-button-primary">登录商户 <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
<a href="/user/reg.php" class="btn hero-button hero-button-ghost">注册商户</a>
</div>
<dl class="hero-metrics" aria-label="平台能力">
<div><dt>多渠道</dt><dd>支付宝 · 微信 · QQ</dd></div>
<div><dt>低门槛</dt><dd>快速接入，实时收款</dd></div>
</dl>
</div>
</div>
</div>
</div>
</section>

<section class="screen3 feature-section" aria-labelledby="feature-title">
<div class="container section-shell">
<header class="section-heading">
<p class="section-kicker">CORE CAPABILITIES</p>
<h2 id="feature-title">一套平台，连接主流支付场景</h2>
<p>从收款、结算到风控，把复杂的支付流程交给平台处理。</p>
</header>
<div class="feature-grid">
<article class="feature-card">
<div class="feature-card-top"><span class="feature-index">01</span><div class="server_item container_server" aria-hidden="true"></div></div>
<div class="feature-copy"><h3>多种支付方式</h3><p>支持财付通、支付宝、微信与 QQ 钱包，覆盖常用付款场景。</p></div>
</article>
<article class="feature-card">
<div class="feature-card-top"><span class="feature-index">02</span><div class="server_item arrange" aria-hidden="true"></div></div>
<div class="feature-copy"><h3>对接费率更低</h3><p>清晰的费率规则与统一接口，降低接入和日常运营成本。</p></div>
</article>
<article class="feature-card">
<div class="feature-card-top"><span class="feature-index">03</span><div class="server_item codebuild" aria-hidden="true"></div></div>
<div class="feature-copy"><h3>结算更省心</h3><p>达到结算条件后自动处理，商户无需重复操作和人工跟进。</p></div>
</article>
</div>
</div>
</section>

<section class="screen4 partner-section" aria-labelledby="partner-title">
<div class="container section-shell">
<header class="partner-heading">
<div><p class="section-kicker">PAYMENT NETWORK</p><h2 id="partner-title">平台合作伙伴</h2></div>
<p>兼容支付宝、微信、QQ 钱包等主流支付生态，让用户用熟悉的方式完成付款。</p>
</header>
<div class="partner-grid">
<div class="partner-card"><img src="<?php echo STATIC_ROOT?>images/alipay.png" alt="支付宝"></div>
<div class="partner-card"><img src="<?php echo STATIC_ROOT?>images/wxpay.png" alt="微信支付"></div>
<div class="partner-card"><img src="<?php echo STATIC_ROOT?>images/qqpay.png" alt="QQ 钱包"></div>
<div class="partner-card"><img src="<?php echo STATIC_ROOT?>images/tenpay.png" alt="财付通"></div>
</div>
</div>
</section>
</main>

<?php require INDEX_ROOT.'foot.php';?>
