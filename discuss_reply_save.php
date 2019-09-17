<?php 
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
$customer_id = passport_decrypt($customer_id);  
require('../back_init.php');

$link =mysql_connect(DB_HOST,DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
mysql_query("SET NAMES UTF8");

//方法
if(!empty($_GET["op"])){
$op = $configutil->splash_new($_GET["op"]);
}

switch($op){
	case "save":
	
		$discuss = $configutil->splash_new($_POST["description"]);
		$keyid = $configutil->splash_new($_POST["keyid"]);
	
		$query = "SELECT user_id,batchcode,product_id FROM weixin_commonshop_product_evaluations where id=".$keyid;
		$result = mysql_query($query) or die('Query failed: ' . mysql_error());  		
		while ($row = mysql_fetch_object($result)) {
			$user_id = $row->user_id;
			$batchcode = $row->batchcode;
			$product_id = $row->product_id;
		}	
		
		$query2 = "insert into weixin_commonshop_product_evaluations(product_id,user_id,level,isvalid,createtime,customer_id,discuss,status,type,batchcode,reply_id) values(".$product_id.",".$user_id.",0,true,now(),".$customer_id.",'".$discuss."',true,3,".$batchcode.",".$keyid.")";
		mysql_query($query2) or die('Query2 failed: ' . mysql_error());  		
	
	
	break;	
	default:
	mysql_close($link);
	echo "未知方法，请联系管理员！";
	break;	
} 

$url="discuss.php?customer_id=" . passport_encrypt($customer_id) . "&pid=" . $product_id;
echo "<script>document.location='".$url."';</script>";	
mysql_close($link);

?>