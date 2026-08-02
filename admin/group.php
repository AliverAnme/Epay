<?php
/** 用户组购买设置（原生 shadcn 列表） */
include("../includes/common.php");
require_once __DIR__.'/epay_ui_entry.php';
$rows = [];
foreach($DB->getAll("SELECT gid,name,price,expire,sort,visible,isbuy FROM pre_group ORDER BY isbuy DESC,sort ASC,gid ASC") as $row){
	$rows[] = ['id'=>(string)$row['gid'],'gid'=>(string)$row['gid'],'name'=>(string)$row['name'],'price'=>(string)$row['price'],'expire'=>(string)$row['expire'],'sort'=>(string)$row['sort'],'visible'=>(string)$row['visible'],'isbuy'=>(string)$row['isbuy']];
}
epay_admin_view('admin-group-purchase', [
	'title'=>'用户组购买设置','description'=>'管理用户组上架状态、售价、有效期与可见范围。','group_buy'=>(int)($conf['group_buy'] ?? 0),'rows'=>$rows,
]);
