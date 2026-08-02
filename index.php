<?php
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('require PHP >= 7.4 !');
}
$is_defend = true;
$allow_search = true;
include("./includes/common.php");

function render_epay_ui_page($view, $config = [], $title = 'Rainbow Pay'){
    $payload = htmlspecialchars(json_encode($config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.h($title).'</title><link rel="stylesheet" href="/assets/dist/epay-ui.css"></head><body><div id="epay-react-root" data-epay-view="'.h($view).'" data-epay-config="'.$payload.'"></div><script type="module" src="/assets/dist/epay-ui.js"></script></body></html>';
    exit;
}

function wrap_epay_legacy_page($html, $view, $config = []){
    $payload = htmlspecialchars(json_encode($config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    $root = '<div id="epay-react-root" data-epay-view="'.h($view).'" data-epay-config="'.$payload.'"></div><div id="epay-react-legacy-source">';
    $assets = '<link rel="stylesheet" href="/assets/dist/epay-ui.css"><script type="module" src="/assets/dist/epay-ui.js"></script>';
    if(stripos($html, '</head>') !== false){
        $html = preg_replace('~</head>~i', $assets.'</head>', $html, 1);
    }else{
        $html = $assets.$html;
    }
    if(preg_match('~<body\b[^>]*>~i', $html)){
        $html = preg_replace_callback('~(<body\b[^>]*>)~i', function($matches) use ($root){
            return $matches[1].$root;
        }, $html, 1);
        $html = preg_replace('~</body>~i', '</div></body>', $html, 1);
    }else{
        $html = $root.$html.'</div>';
    }
    return $html;
}

if(isset($_GET['doc'])){
    $doc = trim($_GET['doc']);
    if(!$conf['apiurl'])$conf['apiurl'] = $siteurl;
    $loadfile = \lib\Template::loadDoc($doc);
    ob_start();
    include $loadfile;
    echo wrap_epay_legacy_page(ob_get_clean(), 'documentation-shell', [
        'doc' => $doc,
        'sitename' => $conf['sitename'],
        'title' => '开发文档',
    ]);
    exit;
}

$mod = isset($_GET['mod'])?$_GET['mod']:'index';

if($mod === 'payok' || $mod === 'payerr'){
    render_epay_ui_page('payment-status', [
        'status' => $mod === 'payok' ? 'success' : 'error',
        'sitename' => $conf['sitename'],
        'message' => $mod === 'payok' ? '支付成功，请回到网站查看订单' : '该订单处理异常，已自动退款！',
    ], $mod === 'payok' ? '支付成功' : '支付异常');
}

if(in_array($mod, ['agreement', 'doc_old', 'wx'], true)){
    $loadfile = \lib\Template::load($mod);
    ob_start();
    include $loadfile;
    echo wrap_epay_legacy_page(ob_get_clean(), 'documentation-shell', [
        'doc' => $mod,
        'sitename' => $conf['sitename'],
        'title' => $mod === 'agreement' ? '服务条款' : ($mod === 'wx' ? '微信支付教程' : '旧版开发文档'),
    ]);
    exit;
}

if(isset($_GET['invite'])){
    $invite_code = trim($_GET['invite']);
    $uid = get_invite_uid($invite_code);
    if($uid && is_numeric($uid)){
        $_SESSION['invite_uid'] = intval($uid);
    }
}

if($mod=='index'){
    if($conf['homepage']==2){
        echo '<html><frameset framespacing="0" border="0" rows="0" frameborder="0">
        <frame name="main" src="'.h($conf['homepage_url']).'" scrolling="auto" noresize>
    </frameset></html>';
        exit;
    }elseif($conf['homepage']==1){
        exit("<script language='javascript'>window.location.href='/user/';</script>");
    }
}

if($mod=='index'){
    $epay_ui_public_config = [
        'sitename' => $conf['sitename'],
        'title' => $conf['title'],
        'description' => $conf['description'],
        'orgname' => $conf['orgname'],
        'kfqq' => $conf['kfqq'],
        'email' => $conf['email'],
        'footer' => $conf['footer'],
        'test_open' => (int)$conf['test_open'],
    ];
    ?><!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?php echo h($conf['description'])?>">
        <title><?php echo h($conf['title'])?></title>
        <link rel="stylesheet" href="/assets/dist/epay-ui.css">
    </head>
    <body>
        <div id="epay-react-root" data-epay-view="public-home" data-epay-config="<?php echo h(json_encode($epay_ui_public_config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE))?>"></div>
        <script type="module" src="/assets/dist/epay-ui.js"></script>
    </body>
    </html><?php
    exit;
}

$loadfile = \lib\Template::load($mod);
ob_start();
include $loadfile;
echo wrap_epay_legacy_page(ob_get_clean(), 'public-legacy-shell', [
    'mod' => $mod,
    'sitename' => $conf['sitename'],
    'title' => $mod === 'aboutUs' ? '关于我们' : ($mod === 'produceIntroduce' ? '产品介绍' : '网站页面'),
    'description' => '彩虹易支付官方网站内容。',
]);
exit;
