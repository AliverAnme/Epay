<?php
$nosession=true;
include("../includes/common.php");
if(isset($_GET['code']) && isset($_GET['state'])){
    if(str_starts_with($_GET['state'], '/')){
        $redirect = $siteurl . substr($_GET['state'], 1) . '?code='.$_GET['code'];
    }else{
        $redirect = $siteurl;
    }
    header('Location: '.$redirect);
    exit;
}