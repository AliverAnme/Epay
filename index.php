<?php
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('require PHP >= 7.4 !');
}
$is_defend = true;
$allow_search = true;
include("./includes/common.php");

if(isset($_GET['doc'])){
    $doc = trim($_GET['doc']);
    if(!$conf['apiurl'])$conf['apiurl'] = $siteurl;
    $loadfile = \lib\Template::loadDoc($doc);
    include $loadfile;
    exit;
}

$mod = isset($_GET['mod'])?$_GET['mod']:'index';

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
        <frame name="main" src="'.$conf['homepage_url'].'" scrolling="auto" noresize>
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
include $loadfile;
