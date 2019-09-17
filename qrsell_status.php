<?php 
header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
$customer_id =  $configutil->splash_new($_POST["customer_id"]);
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../common/utility_shop.php');
require('../../../proxy_info.php');
mysql_query("SET NAMES UTF8");

$id        =  $configutil->splash_new($_POST["id"]);
$user_id   =  $configutil->splash_new($_POST["user_id"]);
$parent_id =  $configutil->splash_new($_POST["parent_id"]);
$isAgent   =  $configutil->splash_new($_POST["isAgent"]);
$pagenum   =  $configutil->splash_new($_POST["pagenum"]);
$reason    =  $configutil->splash_new($_POST["reason"]);
$status    =  $configutil->splash_new($_POST["status"]);

$type      = $configutil->splash_new($_POST["type"]);//type 1:成为顶级推广员,2:推广员通过 3:驳回推广员 4:删除推广员 5:取消上下级关系
	  if($type==1){	//type 1:成为顶级推广员
			
		  $sql="update weixin_qrs set status=1,reason='' where id=".$id;
		  mysql_query($sql);
		  $sql="update promoters set status=1, parent_id=-1 where user_id=".$user_id." and isvalid=true and customer_id=".$customer_id;	//更新为顶级推广员,取消上下级关系
		  mysql_query($sql);			  
		  $sql="update weixin_qr_scans set isvalid=false where  user_id=".$user_id." and customer_id=".$customer_id." and scene_id=".$parent_id;//取消上下级关系
		  mysql_query($sql);
		  
		  //1.判断用户是否存在	
		$query = "SELECT generation,gflag,is_lock FROM weixin_users where isvalid=true and customer_id=".$customer_id." and id=".$user_id." limit 0,1";
		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		$error = mysql_error();
		
		
		
		$is_lock=0;//是否已经被锁定关系
		$old_flag = "";
		$old_generation = 1;
		while ($row = mysql_fetch_object($result)) {
			$is_lock=$row->is_lock;
			$old_flag=$row->gflag;
			$old_generation=$row->generation;
			break;
		}
		  if($old_generation>1){
			  
			  $add_generation=1-$old_generation;
			  
			   $sql="update weixin_users set parent_id=-1,generation=1,is_lock=1,gflag=',-1,' where id=".$user_id;//取消上下级关系
			  mysql_query($sql);
			  //减少上级的粉丝数和推广员数
			  $sql="update promoters set fans_count= fans_count-1,promoter_count=promoter_count-1 where isvalid=true and user_id=".$parent_id;
			  mysql_query($sql);
			  
			  $query="update  weixin_users set gflag=replace(gflag,'".$old_flag."',',-1,') ,generation=generation+".$add_generation." where match(gflag) against (',".$user_id.",')";
			   mysql_query($query);
		  }
		  
		$shopmessage = new shopMessage_Utlity(); 
		$shopmessage -> ChangeRelation_new($user_id,-1,$parent_id,$customer_id,1,2); 
		  
		 
		  
		  exit('{"status": 1001, "errorMsg":"通过顶级推广员申请"}');
	}else if($type==2){ //type 2:推广员通过
			
		  $sql="update weixin_qrs set status=1,reason='' where id=".$id;
		  mysql_query($sql);
		  $sql="update promoters set status=1,createtime=now() where isvalid=true and user_id=".$user_id;
		  mysql_query($sql);
		  
		  //增加上级的推广员数
		  $sql="update promoters set promoter_count=promoter_count+1 where isvalid=true and user_id=".$parent_id;
		  $shopmessage = new shopMessage_Utlity(); 
			$shopmessage -> ChangeRelation_new($user_id,$parent_id,$parent_id,$customer_id,1,5); 
		  mysql_query($sql);
		  exit('{"status": 1002, "errorMsg":"通过推广员申请"}');
	} else if($type==6){
		  $sql="update weixin_qrs set status=0 where id=".$id;
		  mysql_query($sql);
		  $sql="update promoters set status=0,isAgent=0 where user_id=".$user_id;
		  mysql_query($sql);
		  
		  //减少上级的推广员数
		  $sql="update promoters set promoter_count=promoter_count-1 where isvalid=true and user_id=".$parent_id;
		  mysql_query($sql);
		  
	}else if($type==3){	//type 3:驳回推广员 
		
	      $reason = $_POST["reason"];
	      $sql="update weixin_qrs set status=-1,reason='".$reason."' where id=".$id;
	      mysql_query($sql);
		  
		  $sql="update promoters set status=-1,isAgent=0 where user_id=".$user_id." and isvalid=true and customer_id=".$customer_id;
		  mysql_query($sql);
		  
		  //减少上级的推广员数
		  $sql="update promoters set promoter_count=promoter_count-1 where isvalid=true and user_id=".$parent_id;
		  mysql_query($sql);
		  if( $status == 0 ){
			  $Cstatus = 8;
		  }else{
			  $Cstatus = 7;
		  }
		  $shopmessage = new shopMessage_Utlity(); 
			$shopmessage -> ChangeRelation_new($user_id,$parent_id,$parent_id,$customer_id,1,$Cstatus); 
		  exit('{"status": 1003, "errorMsg":"驳回推广员申请"}');
	}else if($type==4){ 	//type 4:删除推广员
	 	
	  /* $query2="select createtime,isAgent from promoters where isvalid=true and user_id=".$user_id;
	  $result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
	  $isAgent = 0;
	  $isturn = 0;
	  while ($row2 = mysql_fetch_object($result2)) {
		$isAgent = $row2->isAgent;	//判断 0为推广员 1为代理商 2为顶级推广员
		break;
	  }
		if($isAgent==1){
			echo "<script>alert('您还是代理商,请删除代理商身份');</script>";	
			$isturn = 1;
			if($isturn){echo  "<script>window.history.back(-1);</script>";	}
			return;
		}   
		if($isAgent==3){
			echo "<script>alert('您还是供应商,请删除供应商身份');</script>";	
			$isturn = 1;
			if($isturn){echo  "<script>window.history.back(-1);</script>";	}
			return;
		} */
	  //$shopmessage= new shopMessage_Utlity();
	  //$shopmessage->ChangeRelation($user_id,$parent_id,$customer_id,4);	//1:商家后台手动改动关系 2:通过分享建立关系 3:推广二维码扫描建立关系;4:删除推广员
      $sql="update weixin_qrs set isvalid=false where id=".$id;
	  mysql_query($sql) or die('w94 Query failed: ' . mysql_error());
	  
	  $sql="update promoters set isvalid=false where  user_id=".$user_id." and isvalid=true and customer_id=".$customer_id;
	  mysql_query($sql) or die('w97 Query failed: ' . mysql_error());
	  
	  //所有下线都取消上级  
	  //$sql="update promoters set parent_id=-1 where isvalid=true and  parent_id=".$user_id." and customer_id=".$customer_id;
	  //mysql_query($sql) or die('w101 Query failed: ' . mysql_error());
	  
	  //$sql="update weixin_users set parent_id=-1 where isvalid=true and  parent_id=".$user_id." and customer_id=".$customer_id;
	  //mysql_query($sql) or die('w104 Query failed: ' . mysql_error());
	  
	  //取消扫描关系
	  $qr_info_id = $_POST["qr_info_id"];
	  $sql="update weixin_qr_infos set isvalid=false where id=".$qr_info_id;
	  mysql_query($sql) or die('w109 Query failed: ' . mysql_error());
	  //清除推广记录
	  $sql="update weixin_qr_scans set isvalid=false where scene_id=".$user_id;
	  mysql_query($sql) or die('w112 Query failed: ' . mysql_error());
	  $sql="update weixin_qr_scans set isvalid=false where  user_id=".$user_id." and customer_id=".$customer_id." and scene_id=".$parent_id;
	  mysql_query($sql) or die('w114 Query failed: ' . mysql_error());
	  //去掉 用户表里面的上下级关系
	  //$sql="update weixin_users set parent_id=-1 where id=".$user_id;
	  //mysql_query($sql) or die('w117 Query failed: ' . mysql_error());
	  
	  $sql="update weixin_commonshop_applyagents set isvalid=false where user_id=".$user_id." and isvalid=true";
	  mysql_query($sql) or die('w120 Query failed: ' . mysql_error());//删掉代理商申请  
	  $sql="update weixin_commonshop_agentfee_records set isvalid=false where user_id=".$user_id." and isvalid=true";
	  mysql_query($sql) or die('w122 Query failed: ' . mysql_error());//消费记录
	  
	  //减少上级的粉丝数和推广员数
	  $sql="update promoters set fans_count= fans_count-1,promoter_count=promoter_count-1 where isvalid=true and user_id=".$parent_id;
	  mysql_query($sql) or die('w126 Query failed: ' . mysql_error());
	  $shopmessage = new shopMessage_Utlity(); 
	  $shopmessage -> ChangeRelation_new($user_id,$parent_id,$parent_id,$customer_id,1,6); 
	 exit('{"status": 1004, "errorMsg":"删除推广员"}');
   }else if($type==5){	//5:取消上下级关系
	 
	  //$shopmessage= new shopMessage_Utlity();
	  //$shopmessage->ChangeRelation($user_id,$parent_id,$customer_id,5);	//1:商家后台手动改动关系 2:通过分享建立关系 3:推广二维码扫描建立关系;4:删除推广员;5:取消上下级关系;
	  
	  $sql="update promoters set parent_id=-1 where  user_id=".$user_id." and isvalid=true and customer_id=".$customer_id;
	  mysql_query($sql);
	  
	  $sql="update weixin_qr_scans set isvalid=false where  user_id=".$user_id." and customer_id=".$customer_id." and scene_id=".$parent_id;
	  mysql_query($sql);
	  
	   //1.判断用户是否存在	
		$query = "SELECT generation,gflag,is_lock FROM weixin_users where isvalid=true and customer_id=".$customer_id." and id=".$user_id." limit 0,1";
		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		$error = mysql_error();
		
		
		
		$is_lock=0;//是否已经被锁定关系
		$old_flag = "";
		$old_generation = 1;
		while ($row = mysql_fetch_object($result)) {
			$is_lock=$row->is_lock;
			$old_flag=$row->gflag;
			$old_generation=$row->generation;
			break;
		}
		  if($old_generation>1){
			  
			  $add_generation=1-$old_generation;
			  
			   $sql="update weixin_users set parent_id=-1,generation=1,is_lock=0,gflag=',-1,' where id=".$user_id;//取消上下级关系
			  mysql_query($sql);
			  //减少上级的粉丝数和推广员数
			  $sql="update promoters set fans_count= fans_count-1,promoter_count=promoter_count-1 where isvalid=true and user_id=".$parent_id;
			  mysql_query($sql);
			  
			  $query="update  weixin_users set gflag=replace(gflag,'".$old_flag."',',-1,') ,generation=generation+".$add_generation." where match(gflag) against (',".$user_id.",')";
			   mysql_query($query);
		  }else{
			  $sql="update weixin_users set is_lock=0 where id=".$user_id;//取消上下级关系
			  mysql_query($sql);
			  
		  }
		$shopmessage = new shopMessage_Utlity(); 
	  $shopmessage -> ChangeRelation_new($user_id,$parent_id,-1,$customer_id,1,3); 
	  exit('{"status": 1005, "errorMsg":"取消上下级关系"}');
   }
   
	
?>