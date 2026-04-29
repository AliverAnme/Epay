<?php
$nosession=true;
include("../includes/common.php");
if(isset($_GET['code']) && isset($_GET['state'])){
    if(str_starts_with($_GET['state'], '/')){
        $state = isset($_GET['state'])?$_GET['state']:'';
        if(!str_starts_with($state, '/') || strpos($state, '//') !== false) exit('Invalid state');
        header('Location: '.$siteurl . substr($state, 1));
        exit;
    }