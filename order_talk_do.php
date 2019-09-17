<?php
header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
mysql_query("SET NAMES UTF8");

$content   = $configutil->splash_new($_POST["content"]);
$batchcode = $configutil->splash_new($_POST["batchcode"]);
$supply_id = $configutil->splash_new($_POST["supply_id"]);

if(!is_numeric($batchcode)){
	$json["status"] = 10002;
	$json["line"] = 24;
	$json["msg"] = "订单号不正确！";
	$jsons=json_encode($json);
	die($jsons);		
}

$Query="insert into weixin_commonshop_supply_message(supplier_id,createtime,isvalid,customer_id,type,batchcode,message) values(".$supply_id.",now(),true,".$customer_id.",0,".$batchcode.",'".$content."')";
mysql_query($Query) or die('Query failed: ' . mysql_error());

$json["status"] = 0;
$json["line"] = 41;
$json["msg"] = "订单编号：".$batchcode."，留言成功";	
$json["time"] = date('Y-m-d H:i:s');

$error =mysql_error();
if(!empty($error)){
	$json["status"] = 10002;
	$json["msg"] = $error;	
}

mysql_close($link);

$jsons=json_encode($json);
die($jsons);
?>