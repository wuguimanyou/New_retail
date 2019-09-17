<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../back_init.php');
require('../common/utility.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

$key_id =$configutil->splash_new($_GET["key_id"]);
if(!$key_id){
	die("参数错误!");
}

	$query="update weixin_commonshop_distributor_article set isvalid=0 where id=$key_id";

	mysql_query($query)or die('Query failed1: ' . mysql_error()); 


$error =mysql_error();
mysql_close($link);
if($error){
	echo '插入数据库失败'.$error; 
}else{
 echo "<h1>操作成功,页面跳转中...</h1><script>location.href='distributor_article.php?customer_id=".$customer_id_en."';</script>" ;
}


?>