<?php
header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../proxy_info.php');  /*fenxiao下链接出错 11.13 by cdr*/

$op = '';
if($_GET["op"]){
	$op	=	$configutil->splash_new($_GET["op"]);	
}
$title = '';
if($_POST["title"]){
	$title	=	$configutil->splash_new($_POST["title"]);	
}
//var_dump($express_id);

$supply_id = -1;
if($_GET["supply_id"]){
	$supply_id	=	$configutil->splash_new($_GET["supply_id"]);	
}

$tem_id = -1;
if($_GET["tem_id"]){
	$tem_id	=	$configutil->splash_new($_GET["tem_id"]);	
}

if($op=="add"){
	//添加
	$query_add = "insert into weixin_expresses_company(customer_id,expresses_name,isvalid,createtime,supply_id)values(".$customer_id.",'".$title."',true,now(),".$supply_id.")";
	mysql_query($query_add)or die('Query failed'.mysql_error());
	$company_t_id = mysql_insert_id();
	echo "<script>location.href='express_company.php?customer_id=".$customer_id_en."';</script>";
	
}
if($op=="update"){//修改
	//更新名称
	$query = "update weixin_expresses_company set expresses_name='".$title."' where isvalid=true and id=".$tem_id."";	
	mysql_query($query)or die('Query failed'.mysql_error());
	echo "<script>location.href='express_company.php?customer_id=".$customer_id_en."';</script>";
	
}
if($op=="del"){
	//删除
	$res = array();
	$tem_id = -1;
	if($_POST["tem_id"]){
		$tem_id	=	$configutil->splash_new($_POST["tem_id"]);	
		
	}
	$query_del="update weixin_expresses_company set isvalid=false where id=".$tem_id." and supply_id=".$supply_id." and customer_id=".$customer_id."";
	//echo $query_del;
	mysql_query($query_del)or die('Query failed'.mysql_error());	
	$error = mysql_error();
	if($error==0){
		 $res['code'] = 1;
		echo json_encode($res);
	}else{
		 $res['code'] = 0;
		echo json_encode($res);
	}
}

if($op=="express_check"){
	//删除
	$res = array();
	$tem_id = -1;
	if($_POST["tem_id"]){
		$tem_id	=	$configutil->splash_new($_POST["tem_id"]);	
		
	}
	//清除所有默认值
	$query_del="update weixin_expresses_company set is_default=false where  supply_id=".$supply_id." and customer_id=".$customer_id."";
	//echo $query_del;
	mysql_query($query_del)or die('Query failed'.mysql_error());	
	
	//修改选中的模板默认值为1
	$query_del="update weixin_expresses_company set is_default=true where id=".$tem_id." and supply_id=".$supply_id." and customer_id=".$customer_id."";
	//echo $query_del;
	mysql_query($query_del)or die('Query failed'.mysql_error());	
	$error = mysql_error();
	if($error==0){
		 $res['code'] = 1;
		echo json_encode($res);
	}else{
		 $res['code'] = 0;
		echo json_encode($res);
	}
}
// if($op=="deleteall"){
	// //删除
	// $res = array();
	// $temidarr = '';			//批量删除的id
	// if($_POST["temidarr"]){
		// $temidarr	=	$configutil->splash_new($_POST["temidarr"]);	
		
	// }
	// $temidarr = substr($temidarr,0,-1);	//去除最后一个逗号
	// $temidarray = explode(',',$temidarr);
	// $temidarray2 = explode(',',$temidarr);
	// $query = "update express_company_t set isvalid = Case id ";
	// $sql_str = '';
	// foreach($temidarray as $values){
		// $sql_str .= " WHEN ".$values." THEN false ";
	// }
	// $query .= $sql_str." end where id IN (".$temidarr.")"; 
	// //echo $query;	
	// mysql_query($query)or die('Query failed'.mysql_error());	
	// $error = mysql_error();
	// $sql_str = "(".$customer_id.",";
	// $sql_str2 = ",2,'".$_SESSION['username']."',-1,true,now()),";
	// $sql = '';
	// foreach ($temidarray2 as $values){
		
		// $sql .= $sql_str.$values.$sql_str2;		//拼接插入数据
		
	// }	
	// $sql = substr($sql,0,-1);					//去掉最后一个逗号
	// $query_log = "insert into express_company_logs(customer_id,express_id,operation,operation_user,supply_id,isvalid,createtime) values".$sql."";
	// mysql_query($query_log) or die('Query_log failed'.mysql_error());
	// if($error==0){
		 // $res['code'] = 1;
		// echo json_encode($res);
	// }else{
		 // $res['code'] = 0;
		// echo json_encode($res);
	// }
// }
if($op=="checkTitle"){
	//检查模板名称是否重名
	$tcount = 0;
	$query = "select count(1) as tcount from express_company_t where isvalid=true and title='".$title."' and customer_id=".$customer_id;
	$result = mysql_query($query) or die('checkTitle Query failed:'.mysql_error());
	while($row = mysql_fetch_object($result)){
		$tcount = $row->tcount;
		break;
	}
	if($tcount>0){
		$res['status'] = 1;
		echo json_encode($res);
	}else{
		$res['status'] = 0;
		echo json_encode($res);
	}
}
mysql_close($link);

?>