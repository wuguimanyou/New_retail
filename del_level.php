<?php

header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
//echo "0000";return;
$kid = 0;
if(!empty($_GET["kid"])){
	$kid = $configutil->splash_new($_GET["kid"]); 
	//是否存在
}
//echo $kid;return;
if( 0 < $kid ){
	$sql="update charitable_name_t set isvalid=0 where id = ".$kid;
	$result = mysql_query($sql) or die('Query failed: ' . mysql_error());
	$re_result["result"] = 1;
}
echo json_encode($re_result);
mysql_close($link);
?>