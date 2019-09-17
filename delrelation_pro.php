<?php
header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
mysql_query("SET NAMES UTF8");

$data		= array();
$product_id	= $configutil->splash_new($_POST["product_id"]);
$pid		= $configutil->splash_new($_POST["pid"]);

$query = "update products_relation_t set 
		isvalid=false 
		where isvalid=true and pid=".$pid." and parent_pid=".$product_id; 
//echo $query;
mysql_query($query)or die('Query failed'.mysql_error());


$data["status"]			= 1;

$error = mysql_error();
//echo $error;
mysql_close($link);
if( !empty( $num ) ){
	$data["status"]			= 0;
}
$data=json_encode($data);
echo $data;
?>