<?php
/** 批量结算（原生 shadcn 表格与逐笔操作） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$type = isset($_GET['type']) && is_scalar($_GET['type']) ? intval($_GET['type']) : 1;
$batch = (string)($_GET['batch'] ?? '');
$rows = $batch === '' ? [] : $DB->getAll("SELECT id,uid,account,username,money,realmoney,transfer_status,transfer_result,transfer_date FROM pre_settle WHERE batch=:batch AND type=:type ORDER BY id ASC", [':batch'=>$batch, ':type'=>$type]);
$app = [1=>'alipay',2=>'wxpay',3=>'qqpay',4=>'bank'][$type] ?? 'alipay';
$channels = [];
foreach($DB->getAll("SELECT id,name FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE FIND_IN_SET(:type,transtypes)>0) ORDER BY id ASC", [':type'=>$app]) as $row) $channels[] = ['value'=>(string)$row['id'],'label'=>$row['id'].' - '.$row['name']];
epay_admin_view('admin-settle-batch', ['title'=>'批量结算','description'=>'批次 '.($batch ?: '未指定').' 的结算记录，逐笔处理并查看转账结果。','type'=>$type,'rows'=>$rows,'channels'=>$channels]);
