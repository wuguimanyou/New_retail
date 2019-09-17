<?php
header("Content-type: text/html; charset=utf-8"); //test
require('../config.php');
require('../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
/* define("bug_time", "2015-5-11 12:18:00");  //新旧版本返佣翻倍 时间上判断常量 ////
define("version", "2"); //新旧版确定订单，有楼上bug_time的是版本2没有就是版本1.. */
require('../proxy_info.php');
include_once("../log_.php");
$log_ = new Log_();
$log_name="./notify_url_order.log";//log文件路径

mysql_query("SET NAMES UTF8");

require('../common/utility_shop.php');
require('../auth_user.php'); 
$pagenum = 1;

if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}
$pagesize=20;
if(!empty($_GET["pagesize"])){
    $pagesize = $configutil->splash_new($_GET["pagesize"]);
}
if(!empty($_POST["pagesize"])){
    $pagesize = $configutil->splash_new($_POST["pagesize"]);
}
$start = ($pagenum-1) * $pagesize;
$end = $pagesize;

$query ="select isOpenPublicWelfare from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	while ($row = mysql_fetch_object($result)) {
	   $isOpenPublicWelfare = $row->isOpenPublicWelfare;
	}

$orgin_from=20;
if(!empty($_GET["orgin_from"])){
    $orgin_from = $configutil->splash_new($_GET["orgin_from"]);
}
if(!empty($_POST["orgin_from"])){
    $orgin_from = $configutil->splash_new($_POST["orgin_from"]);
}

$search_batchcode="";
if(!empty($_GET["search_batchcode"])){
    $search_batchcode = $configutil->splash_new($_GET["search_batchcode"]);
}
if(!empty($_POST["search_batchcode"])){
    $search_batchcode = $configutil->splash_new($_POST["search_batchcode"]);
}
$search_name="";
if(!empty($_GET["search_name"])){
    $search_name = $configutil->splash_new($_GET["search_name"]);
}
if(!empty($_POST["search_name"])){
    $search_name = $configutil->splash_new($_POST["search_name"]);
}

$search_paystyle="-1";
if(!empty($_GET["search_paystyle"])){
    $search_paystyle = $configutil->splash_new($_GET["search_paystyle"]);
}
if(!empty($_POST["search_paystyle"])){
    $search_paystyle = $configutil->splash_new($_POST["search_paystyle"]);
}
$search_name_type=1;	//1为搜索微信名称 2为搜索收货名称
if(!empty($_GET["search_name_type"])){		
    $search_name_type = $configutil->splash_new($_GET["search_name_type"]);
}
if(!empty($_POST["search_name_type"])){
    $search_name_type = $configutil->splash_new($_POST["search_name_type"]);
}
//顾客编号
$user_ids="";	//1为搜索微信名称 2为搜索收货名称
if(!empty($_GET["user_ids"])){		
    $user_ids = $configutil->splash_new($_GET["user_ids"]);
}
if(!empty($_POST["user_ids"])){
    $user_ids = $configutil->splash_new($_POST["user_ids"]);
}


$op="";
if(!empty($_GET["op"])){
   $op = $configutil->splash_new($_GET["op"]);
   //$order_id=$_GET["order_id"];
   $batchcode=$configutil->splash_new($_GET["batchcode"]);
   $card_member_id = $configutil->splash_new($_GET["card_member_id"]);
   $totalprice = $configutil->splash_new($_GET["totalprice"]);
    $paystyle = $configutil->splash_new($_GET["paystyle"]);
    $createtime = $configutil->splash_new($_GET["createtime"]);
   if($op=="status"){
       //完成
	   $query = "select fromuser_app,agentcont_type,sendstatus,status from weixin_commonshop_orders where batchcode='".$batchcode."'";
		$result = mysql_query($query) or die('Query failed1: ' . mysql_error());
		$fromuser_app="";
		$agentcont_type=0;
		$sendstatus=0;
		$status=0;	
		while ($row = mysql_fetch_object($result)) {
			$sendstatus = $row->sendstatus;
			$fromuser_app = $row->fromuser_app;
			$agentcont_type = $row->agentcont_type;		//代理结算 0:推广员结算 1:代理结算
			$status = $row->status;		//1:确认完成1
			
		}
		
		$type=-1;
		if($fromuser_app!=""){
			$query = "select type from weixin_users where weixin_fromuser='".$fromuser_app."' and customer_id=".$customer_id;
			$result = mysql_query($query) or die('Query failed2: ' . mysql_error());
			while ($row = mysql_fetch_object($result)) {
				$type = $row->type;
			}
		}
		if($status==1){
			echo '<script language="javascript">alert("代理商已确认订单");</script>';  
		}else{
		//如果为app运营商用户，则进行消费反馈
		if($type==4){
			//file_put_contents ( "log.txt", "mul=消费反馈=1==\r\n", FILE_APPEND );
			//通过接口 反馈给app运营商用户
				$customerid = $customer_id;
				$app_type=6;
				$paymoney=$now_totalprice;
				$score_data = array('fromuser'=>$fromuser_app,'paymoney'=>$paymoney,'type'=>$app_type,'customerid'=>$customerid);
				//file_put_contents ( "log.txt", "mul=消费反馈=2==\r\n", FILE_APPEND );
				$data_url=http_build_query($score_data);
				//file_put_contents ( "log.txt", "mul=消费反馈=3==\r\n", FILE_APPEND );
				$score_url = "http://115.28.160.40:8080/mayidaxiang/invoke/feedbackScore.jsp?";		
				$url=$score_url.$data_url;
				//echo $url;
				//file_put_contents("log.txt", "status=============".$url."=======".date("Y-m-d h:i:sa")."\r\n",FILE_APPEND);
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $url); 
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_AUTOREFERER, 1); 
				curl_setopt($ch, CURLOPT_POSTFIELDS, "");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 		
				$json = curl_exec($ch); 
				curl_close($ch); 
				//接口end
				//echo $json;
				$WH_status = -1;
				$jsonObj = json_decode($json);    //json解码
				//获取接口返回信息
				foreach ($jsonObj as $v) {
					if(isset($v->status)){
						$WH_status=$v->status;
						
					}
				}
				
				if($WH_status==1){
					$sql="update weixin_commonshop_orders set status=1,fromuser_app='' where batchcode='".$batchcode."'"; 
					mysql_query($sql);
				}else{
					$sql="update weixin_commonshop_orders set status=1 where batchcode='".$batchcode."'"; 
					mysql_query($sql);
				}
				
				
		}else{

            $sql="update weixin_commonshop_orders set status=1,confirm_receivetime=now() where batchcode='".$batchcode."'"; //test 
	        mysql_query($sql);
			$shopUtility_get =  new shopMessage_Utlity();
		   /* if($sendstatus!=4){
			  if(version==2 && strtotime($createtime) < strtotime(bug_time) ){//调旧方法
				 if($agentcont_type==1){
					$shopUtility_get->Confirm_GetMoney_Agent_old($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);
				 }else{
					$shopUtility_get->Confirm_GetMoney_old($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);					   
				 }
			  }else{//新方法
				 if($agentcont_type==1){
					$shopUtility_get->Confirm_GetMoney_Agent($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);
				 }else{
					$shopUtility_get->Confirm_GetMoney($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);

				}
			}

		  } */
			if($sendstatus!=4 and $sendstatus!=6){
				if($agentcont_type==1){
					file_put_contents("Confirm_GetMoney_Agent.txt", "1.batchcode=======".var_export($batchcode,true)."\r\n",FILE_APPEND); 
					file_put_contents("Confirm_GetMoney_Agent.txt", "2.card_member_id=======".var_export($card_member_id,true)."\r\n",FILE_APPEND); 
					file_put_contents("Confirm_GetMoney_Agent.txt", "3.totalprice=======".var_export($totalprice,true)."\r\n",FILE_APPEND); 
					file_put_contents("Confirm_GetMoney_Agent.txt", "4.customer_id=======".var_export($customer_id,true)."\r\n",FILE_APPEND); 
					file_put_contents("Confirm_GetMoney_Agent.txt", "5.paystyle=======".var_export($paystyle,true)."\r\n",FILE_APPEND); 
					$shopUtility_get->Confirm_GetMoney_Agent($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);
				}else{
					$shopUtility_get->Confirm_GetMoney($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);

				}
				
				//增加团队订单数
				$shopUtility_get->Confirm_Team_order($batchcode); 
			}
		  //给顾客增加消费积分奖励.1 表示为商城消费   
		  $shopUtility_get->AddScore_level($card_member_id,$totalprice,1,$paystyle); 
		 
	    }
	   if($isOpenPublicWelfare==1){
		$query="select valuepercent from weixin_commonshop_publicwelfare where isvalid=true and customer_id=".$customer_id; 
		$result = mysql_query($query);
		while ($row = mysql_fetch_object($result)) {
			$valuepercent=$row->valuepercent;
		}
		$batchcode=$configutil->splash_new($_GET["batchcode"]);
		$welfare_user_id=$configutil->splash_new($_GET["user_id"]);
		$totalprice=$configutil->splash_new($_GET["totalprice"]);
		$express_price=$configutil->splash_new($_GET["express_price"]);
		if($express_price>0){$totalprice=$totalprice-$express_price;}//减去运费
		$welfare=$totalprice*$valuepercent;
		$welfare=round($welfare,2);
		
		$query="select id from weixin_commonshop_publicwelfare_log where isvalid=true and customer_id=".$customer_id." and user_id=".$welfare_user_id; 
		$result = mysql_query($query);
		$welfare_id = -1;
		while ($row = mysql_fetch_object($result)) {
			$welfare_id=$row->id;
		}
		//判断此用户是否曾经捐助过 
		if($welfare_id>0){
			$query="select before_score,add_score from weixin_commonshop_publicwelfare_log where isvalid=true and customer_id=".$customer_id." and user_id=".$welfare_user_id." order by id desc limit 0,1";
			$result = mysql_query($query);
			while ($row = mysql_fetch_object($result)) {
				$before_score=$row->before_score;
				$add_score=$row->add_score;
			}
			$new_before_score=$before_score+$add_score;
			$sql="insert into weixin_commonshop_publicwelfare_log(user_id,createtime,isvalid,customer_id,before_score,add_score,batchcode) values(".$welfare_user_id.",now(),true,".$customer_id.",".$new_before_score.",".$welfare.",".$batchcode.")";
			mysql_query($sql);
			//累加至奖金池
		    $query="select publicwelfare from weixin_commonshop_publicwelfare where isvalid=true and customer_id=".$customer_id; 
			$result = mysql_query($query);
			while ($row = mysql_fetch_object($result)) {
				$publicwelfare=$row->publicwelfare;
			}
			$new_publicwelfare=$publicwelfare+$welfare;
			$sql = "update weixin_commonshop_publicwelfare set publicwelfare=".$new_publicwelfare." where customer_id=".$customer_id;
            mysql_query($sql);
		}else{
			$sql="insert into weixin_commonshop_publicwelfare_log(user_id,createtime,isvalid,customer_id,before_score,add_score,batchcode) values(".$welfare_user_id.",now(),true,".$customer_id.",0,".$welfare.",".$batchcode.")";
			mysql_query($sql);
			//累加至奖金池
			$query="select publicwelfare from weixin_commonshop_publicwelfare where isvalid=true and customer_id=".$customer_id; 
			$result = mysql_query($query); 
			while ($row = mysql_fetch_object($result)) {
				$publicwelfare=$row->publicwelfare;
			}
			$new_publicwelfare=$publicwelfare+$welfare;
			$sql = "update weixin_commonshop_publicwelfare set publicwelfare=".$new_publicwelfare." where customer_id=".$customer_id;
            mysql_query($sql);
			}
		}
	}
	
   }else if($op=="del"){
       $sql = "update weixin_commonshop_orders set isvalid=false where batchcode='".$batchcode."'";
       mysql_query($sql);
   }else if($op=="status_back"){
	   $query="select pid,rcount,prvalues,sendstatus from weixin_commonshop_orders where isvalid=true and batchcode='".$batchcode."'"; 
		$result = mysql_query($query) or die('Query failed3: ' . mysql_error());
		$pid=-1;
		$rcount = 0;
		$prvalues="";
		 while ($row = mysql_fetch_object($result)) {
			$pid = $row->pid;
			$rcount = $row->rcount;
			$sendstatus= $row->sendstatus;
			$prvalues= $row->prvalues;
		 }
		 if($sendstatus !=4){
			//退货完成
			$sql = "update weixin_commonshop_orders set sendstatus=4 where batchcode='".$batchcode."'";
			mysql_query($sql);

			$log_->log_result($log_name,"退货开始 sql======".$sql);

			$shopUtility_back =  new shopMessage_Utlity();
			$log_->log_result($log_name,"退货进入card_member_id-------".$card_member_id);
			$log_->log_result($log_name,"退货进入totalprice-------".$totalprice);
			$log_->log_result($log_name,"退货进入customer_id-------".$customer_id);
			$log_->log_result($log_name,"退货进入paystyle-------".$paystyle);
			/* if(version==2 && strtotime($createtime) < strtotime(bug_time) ){
				$log_->log_result($log_name,"退货进入1-------".$batchcode);
				$shopUtility_back->Back_GetMoney_old($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);
				//$shopUtility_back->Back_GetMoney($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);
			}else{
				$log_->log_result($log_name,"退货进入2-------".$batchcode);
				$shopUtility_back->Back_GetMoney($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);
			} */
			$log_->log_result($log_name,"退货进入2-------".$batchcode);
			$shopUtility_back->Back_GetMoney($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);
		 }
       
		$prvalues= rtrim($prvalues,"_");
		if(!empty($prvalues)){
			$sql="update weixin_commonshop_product_prices set storenum= storenum+".$rcount." where product_id=".$pid." and proids='".$prvalues."'";
			mysql_query($sql);
		}else{
			$sql="update weixin_commonshop_products set storenum= storenum+".$rcount." where id=".$pid;
			mysql_query($sql);
		}
		
		//更新库存
/* 		$query="select pid,rcount,prvalues from weixin_commonshop_orders where isvalid=true and batchcode='".$batchcode."'"; 
		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		$pid=-1;
		$rcount = 0;
		$prvalues="";
		 while ($row = mysql_fetch_object($result)) {
			$pid = $row->pid;
			$rcount = $row->rcount;
			$prvalues= $row->prvalues;
			
			$prvalues= rtrim($prvalues,"_");
			if(!empty($prvalues)){
				$sql="update weixin_commonshop_product_prices set storenum= storenum+".$rcount." where product_id=".$pid." and proids='".$prvalues."'";
				mysql_query($sql);
			}else{
				$sql="update weixin_commonshop_products set storenum= storenum+".$rcount." where id=".$pid;
				mysql_query($sql);
			}
		}  */
   }else if($op=="confirm_pay"){
       $f = fopen('out2.txt', 'w');  
	   $query="select paystatus,sendstyle from weixin_commonshop_orders where isvalid=true and batchcode='".$batchcode."'";	
	   $result = mysql_query($query) or die('Query failed4: ' . mysql_error());
	   $orgin_paystatus=0;
	   $sendstyle="";
	   while ($row = mysql_fetch_object($result)) {
	      $orgin_paystatus=$row->paystatus;
	      $sendstyle=$row->sendstyle;
	   }
	   //货到付款是下单的时候打印小票的
	   if($paystyle !="货到付款" or $sendstyle != "货到付款"){
			$shopUtility_get =  new shopMessage_Utlity();
			$shopUtility_get->GetTicket($http_host,$batchcode);
	   }
	   
	   if($orgin_paystatus==0){
	       //防止重复确认支付
		   $sql = "update weixin_commonshop_orders set Pay_Method=1,paystatus=1,paytime=now() where isvalid=true and batchcode='".$batchcode."'"; 
		   mysql_query($sql);
		   $query = "select is_autoupgrade,auto_upgrade_money_2,reward_type ,issell,init_reward from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
		   $result = mysql_query($query) or die('Query failed5: ' . mysql_error());
		   $issell = false;
		   $reward_type = 1;
		   $init_reward= 1;
		   $is_autoupgrade=0;
		   $auto_upgrade_money_2 = 0;
		   
		   while ($row = mysql_fetch_object($result)) {
			   $issell = $row->issell;
			   $reward_type = $row->reward_type;
			   $init_reward = $row->init_reward;
			   $is_autoupgrade = $row->is_autoupgrade;
			   $auto_upgrade_money_2 = $row->auto_upgrade_money_2;
		   }
		   fwrite($f, "===auto_upgrade_money_2====".$auto_upgrade_money_2."\r\n"); 
		   fwrite($f, "===is_autoupgrade====".$is_autoupgrade."\r\n"); 
		   if($is_autoupgrade==2){
			   //自动更新为推广员
			   if($totalprice>=$auto_upgrade_money_2){
				   //条件满足
					 $curr_user_id = $configutil->splash_new($_GET["user_id"]);
					 $qr_info_id =-1;
					 $query="select id,scene_id from weixin_qr_infos where type=1 and isvalid=true and customer_id=".$customer_id." and foreign_id=".$curr_user_id;
					 $result = mysql_query($query) or die('Query failed6: ' . mysql_error());  
					 
					 while ($row = mysql_fetch_object($result)) {
						$scene_id = $row->scene_id;
						$qr_info_id=$row->id;
					 }
					 fwrite($f, "===query====".$query."\r\n"); 
					 if($qr_info_id<0){
						$query="select max(scene_id) as scene_id from weixin_qr_infos where isvalid=true and customer_id=".$customer_id;
						$result = mysql_query($query) or die('Query failed7: ' . mysql_error());  
						$scene_id=1;
						while ($row = mysql_fetch_object($result)) {
							$scene_id = $row->scene_id;
							break;
						}
						$scene_id++;
						$sql="insert into weixin_qr_infos(foreign_id,type,scene_id,isvalid,customer_id) values(".$curr_user_id.",1,".$scene_id.",true,".$customer_id.")";
						mysql_query($sql);
						$qr_info_id = mysql_insert_id();
					 }
					 fwrite($f, "===qr_info_id====".$qr_info_id."\r\n"); 
					 $query="select id,ticket,status from weixin_qrs where customer_id=".$customer_id." and isvalid=true and type=1 and qr_info_id=".$qr_info_id;
					 $qr_id=-1;
					 $status = 0;
					 $result = mysql_query($query) or die('Query failed8: ' . mysql_error());  
					 while ($row = mysql_fetch_object($result)) {
						 $qr_id = $row->id;
						 $status= $row->status;
						 break;
					 }
					 
					 $parent_id=-1;
					  $query="select parent_id from weixin_users where isvalid=true and id=".$curr_user_id;
					  $result = mysql_query($query) or die('Query failed9: ' . mysql_error());
					   while ($row = mysql_fetch_object($result)) {
						   $parent_id = $row->parent_id;
					   }
					 
					 if($qr_id<0){
						 $action_name ="QR_LIMIT_SCENE";
						 $query="insert into weixin_qrs(action_name,expire_seconds,qr_info_id,customer_id,isvalid,createtime,type,status) values('".$action_name."',-1,".$qr_info_id.",".$customer_id.",true,now(),1,1)";
						 mysql_query($query);
						 $qr_id = mysql_insert_id();
					 }else{
						 $query="update weixin_qrs set status=1 where id=".$qr_id;
						 mysql_query($query);
						 
						 $query="update promoters set status=1,parent_id=".$parent_id." where user_id=".$curr_user_id;
						 mysql_query($query);
					 }
					 
					   $query="select id,pwd,customer_id from promoters where  isvalid=true  and user_id=".$curr_user_id;
					   $result = mysql_query($query) or die('Query failed10: ' . mysql_error());
					   $pwd = "";
					   $before_customer_id=-1;
					   $promoter_id = -1;
					   while ($row = mysql_fetch_object($result)) {
						   $promoter_id = $row->id;
						   $pwd=$row->pwd;
						   $before_customer_id = $row->customer_id;
					   }
					   if($promoter_id<0){
						   $pwd="888888";
						   $sql ="insert into promoters(user_id,pwd,isvalid,customer_id,parent_id,createtime,status) values(".$curr_user_id.",'888888',true,".$customer_id.",".$parent_id.",now(),1)";
						   mysql_query($sql);
						   $error=mysql_error();
						   //echo $error;
						   //增加推广员数量
						   $query="update promoters set promoter_count= promoter_count+1 where isvalid=true and status=1 and user_id=".$parent_id;
						   mysql_query($query);
					   }else{
						   $sql="update promoters set parent_id=".$parent_id.",status=1 where id=".$promoter_id;
						   mysql_query($sql);
					   }
			   }
		   }
		   
		  
	fwrite($f, "===card_member_id====".$card_member_id."\r\n");  
	fwrite($f, "===paystyle====".$paystyle."\r\n");  
		   if($card_member_id>0 and $paystyle=="会员卡余额支付"){
			   //会员卡余额支付，扣除费用
			   
				$query="select card_id,user_id from weixin_card_members where isvalid=true and id=".$card_member_id;
				$result = mysql_query($query) or die('Query failed11: ' . mysql_error());
				$card_id=-1;
				while ($row = mysql_fetch_object($result)) {
					$card_id = $row->card_id;
					$curr_user_id = $row->user_id;
					break;
				}
	fwrite($f, "===query====".$query."\r\n");  			   
				$consume_score =1 ;
				$query="select consume_score from weixin_cards where isvalid=true and id=".$card_id;
				$result = mysql_query($query) or die('Query failed12: ' . mysql_error());
				while ($row = mysql_fetch_object($result)) {
					$consume_score = $row->consume_score;
					break;
				}
				$t_consume_score = $consume_score * $totalprice;
				
				
			   //会员卡余额消费才扣费
			   $query="select remain_consume from weixin_card_member_consumes where isvalid=true and card_member_id=".$card_member_id;
			   $result = mysql_query($query) or die('Query failed13: ' . mysql_error());
			   $before_money=0;
			   while ($row = mysql_fetch_object($result)) {
				  $before_money = $row->remain_consume;
			   }
			   $after_money = $before_money-$totalprice;
			   
			   $paystyle=3;
			   $sql = "insert into weixin_card_coupon_records(money,card_shop_id,card_coupon_id,paystyle,card_member_id,isvalid,createtime,score,ex_type,foreign_id,before_money,after_money) values(".$totalprice.",-1,-1,".$paystyle.",".$card_member_id.",true,now(),".$t_consume_score.",3,".$batchcode.",".$before_money.",".$after_money.");";
			   mysql_query($sql);
			   
				$remark="会员卡余额消费：".$totalprice;
				$sql = "insert into weixin_card_recharge_records(new_record,before_cost,cost,after_cost,card_member_id,isvalid,createtime,remark) values(1,".$before_money.",".-$totalprice.",".$after_money.",".$card_member_id.",true,now(),'".$remark."')";
				mysql_query($sql);
				
				$sql = "update weixin_card_member_consumes set total_consume= total_consume+".$totalprice.", remain_consume = remain_consume-".$totalprice." where card_member_id=".$card_member_id;
				mysql_query($sql);
			}
			
			//更新库存
			$query="select pid,rcount,prvalues from weixin_commonshop_orders where isvalid=true and batchcode='".$batchcode."'"; 
			$result = mysql_query($query) or die('Query failed14: ' . mysql_error());

			$pid=-1;
			$rcount = 0;
			$prvalues="";
			 while ($row = mysql_fetch_object($result)) {
				$pid = $row->pid;
				$rcount = $row->rcount;
				$prvalues= $row->prvalues;
				
				$prvalues= rtrim($prvalues,"_");
				if(!empty($prvalues)){
					$sql="update weixin_commonshop_product_prices set storenum= storenum-".$rcount." where product_id=".$pid." and proids='".$prvalues."'";
					mysql_query($sql);
				}else{
					$sql="update weixin_commonshop_products set storenum= storenum-".$rcount." where id=".$pid;
					mysql_query($sql);
				}
			}
			fclose($f);
			
		   $shopUtility =  new shopMessage_Utlity();
		   $shopUtility->GetMoney($batchcode,$card_member_id,$totalprice,$customer_id,$paystyle);
	   }
   }
    
}


$keyword="";
$begintime="";
$pay_begintime="";
//$endtime= date('Y-m-d',time());
$endtime = date('Y-m-d H:i',strtotime('+1 day'));
$pay_endtime ='';
$status=-1;
$isauto = 0;
$search_status=-1;
$search_sendstatus=-1;
if(!empty($_GET["isauto"])){
   $isauto = $_GET["isauto"];
}
if(!empty($_POST["keyword"])){
   $keyword=$_POST["keyword"];
}
if(!empty($_POST["AccTime_S"])){
   $begintime=$_POST["AccTime_S"];
}
if(!empty($_POST["AccTime_A"])){
   $pay_begintime=$_POST["AccTime_A"];
}
if(!empty($_GET["begintime"])){
   $begintime=$_GET["begintime"];
}
if(!empty($_GET["pay_begintime"])){
   $pay_begintime=$_GET["pay_begintime"];
}
if(!empty($_POST["AccTime_E"])){
   $endtime=$_POST["AccTime_E"];
}
if(!empty($_POST["AccTime_B"])){
   $pay_endtime=$_POST["AccTime_B"];
}
if(!empty($_POST["search_status"])){
   $search_status=$_POST["search_status"];
}
if(!empty($_GET["search_status"])){
   $search_status=$_GET["search_status"];
}

if(!empty($_GET["search_sendstatus"])){
   $search_sendstatus=$_GET["search_sendstatus"];
}

if(!empty($_POST["search_sendstatus"])){
   $search_sendstatus=$_POST["search_sendstatus"];
}


if(!empty($_GET["endtime"])){
   $endtime=$_GET["endtime"];
}
if(!empty($_GET["pay_endtime"])){
   $pay_endtime=$_GET["pay_endtime"];
}
if(!empty($_GET["search_order_ascription"])){
   $search_order_ascription=$_GET["search_order_ascription"];
}

if(!empty($_POST["status"])){
   $status=$_POST["status"];
}else{
   if(!empty($_GET["issearch"])){
     //$status=0;
   }
}
if($status==0){
	if(!empty($_GET["status"])){
	   $status=$_GET["status"];
	}else{
	  /// $status = 0;
	}
}


//是否是总部商店
$is_generalcustomer = 1;
$is_shopgeneral = 0;

//总部模板才添加
$query="select adminuser_id from customers where isvalid=true and id=".$customer_id;
$result = mysql_query($query) or die('Query failed15: ' . mysql_error()); 
$adminuser_id=-1;
while ($row = mysql_fetch_object($result)) {
   $adminuser_id = $row->adminuser_id;
   break;
}
while($adminuser_id>0){
   $query="select channel_level_id,parent_id from adminusers where isvalid=true and id=".$adminuser_id;
   $result = mysql_query($query) or die('Query failed16: ' . mysql_error());   
   $channel_level_id = -1;
   $parent_id2 = -1;
   while ($row = mysql_fetch_object($result)) {
		$channel_level_id = $row->channel_level_id;
		$parent_id2 = $row->parent_id;
   }
   if($channel_level_id==5){
	  //找到贴牌
	  $query="select is_shopgeneral from oem_infos where isvalid=true and adminuser_id=".$adminuser_id;
	  $result = mysql_query($query) or die('Query failed17: ' . mysql_error());   
	   while ($row = mysql_fetch_object($result)) {
		  $is_shopgeneral = $row->is_shopgeneral;
	   }
	   break;
   }else{
	   $adminuser_id = $parent_id2;
	   $is_generalcustomer = 0;
   }
}

$query="select version from weixinpays where isvalid=true and customer_id=".$customer_id;
$result = mysql_query($query) or die('Query failed18: ' . mysql_error());
$version=1;
while ($row = mysql_fetch_object($result)) {
	$version = $row->version;
	break;
}
//新增客户
$new_customer_count =0;
//今日销售
$today_totalprice=0;
//新增订单
$new_order_count =0;
//新增推广员
$new_qr_count =0;

$nowtime = time();
$year = date('Y',$nowtime);
$month = date('m',$nowtime);
$day = date('d',$nowtime);

$query="select count(distinct batchcode) as new_order_count from weixin_commonshop_orders where isvalid=true and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed19: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $new_order_count = $row->new_order_count;
   break;
}

$query="select sum(totalprice) as today_totalprice from weixin_commonshop_orders where paystatus=1 and sendstatus!=4 and sendstatus!=6 and isvalid=true and customer_id=".$customer_id." and year(paytime)=".$year." and month(paytime)=".$month." and day(paytime)=".$day;
$result = mysql_query($query) or die('Query failed20: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $today_totalprice = $row->today_totalprice;
   break;
}
$today_totalprice = round($today_totalprice,2);

$query="select count(1) as new_customer_count from weixin_commonshop_customers where isvalid=true and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed21: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $new_customer_count = $row->new_customer_count;
   break;
}

$query="select count(1) as new_qr_count from promoters where status=1 and isvalid=true and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed22: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $new_qr_count = $row->new_qr_count;
   break;
}

$nopostage_money=0;

$query="select nopostage_money,stock_remind,name from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
$result = mysql_query($query) or die('Query failed23: ' . mysql_error());   
while ($row = mysql_fetch_object($result)) {
   $nopostage_money = $row->nopostage_money;
   $stock_remind = $row->stock_remind;
   $shopname = $row->name; 
}


$is_distribution=0;//渠道取消代理商功能
//代理模式,分销商城的功能项是 266
$query1="select cf.id,c.filename from customer_funs cf inner join columns c where c.isvalid=true and cf.isvalid=true and cf.customer_id=".$customer_id." and c.filename='scdl' and c.id=cf.column_id";
$result1 = mysql_query($query1) or die('Query failed: ' . mysql_error());  
$dcount= mysql_num_rows($result1);
if($dcount>0){
   $is_distribution=1;
}
$is_supplierstr=0;//渠道取消供应商功能
//供应商模式,渠道开通与不开通
$query1="select cf.id,c.filename from customer_funs cf inner join columns c where c.isvalid=true and cf.isvalid=true and cf.customer_id=".$customer_id." and c.filename='scgys' and c.id=cf.column_id";
$result1 = mysql_query($query1) or die('Query failed: ' . mysql_error());  
$dcount= mysql_num_rows($result1);
if($dcount>0){
   $is_supplierstr=1;
}

$kuaidi100 = 0;
$query_100 = "select `status`  from weixin_kuaidi100 where isvalid=true and customer_id = ".$customer_id;
$result_100 = mysql_query($query_100) or die("L728 query error : ".mysql_error());
while ($row_100 = mysql_fetch_object($result_100)) {
   $kuaidi100 = $row_100->status;
}
?>
<!DOCTYPE html>

<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<title></title>
<script>
var customer_id = <?php echo $customer_id; ?>;
</script>
<link href="css/global.css" rel="stylesheet" type="text/css">
<link href="css/main.css" rel="stylesheet" type="text/css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../css/icon.css" media="all">
<script type="text/javascript" src="../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="js/global.js"></script>
<script type="text/javascript" src="../common/utility.js" charset="utf-8"></script>
<script type="text/javascript" src="../common/js/jquery.blockUI.js"></script>
<script charset="utf-8" src="../common/js/jquery.jsonp-2.2.0.js"></script>
	
<style>
.orderdetail{
   width:100%;
   margin:0 auto;
   height:auto;
}
.orderdetail_one{
   width:100%;
   height:30px;
}
.orderdetail_one_r{
   text-align:right;
   height:100%;
   padding-right:5px;
   padding-top:5px;
   cursor:pointer;
}
.orderdetail_two{
    width:98%;
	min-height:300px;
	margin: 0 auto;
}
.orderdetail_two_l{
   width:48%;
   float:left;
   height:100%;
}

.orderdetail_two_l_t{
    width:100%;
	height:50%;
	border:1px solid #dddddd;
}

.orderdetail_two_l_t_t{
    width:99.4%;
	height:30px;
	line-height:30px;
	text-align:left;
	padding-left:5px;
	border-bottom: 1px solid #dddddd;
	border-right: 1px solid #dddddd;
	color:080808#;
	background:#f5f5f5;
}

.orderdetail_two_l_t_b{
    width:100%;
}

.orderdetail_two_l_t_b_item{
   width:100%;
   height:25px;
   line-height:25px;
}

.orderdetail_two_l_t_b_item_l{
   width:25%;
   text-align:right;
   float:left;
   color:#000;
   font-weight:bold;
}
.orderdetail_two_l_t_b_item_r{
   padding-left:2%;
   width:70%;
   float:left;
   text-align:left;
}



.orderdetail_two_l_b{
    width:100%;
	height:50%;
	border:1px solid #dddddd;
}

.orderdetail_two_r{
   width:48%;
   height:100%; 
   float:left;
}

.orderdetail_two_r_con{
   width:90%;
   margin:0 auto;
   height:100%;
   border:1px solid #dddddd;
}
.split_line{
   width:100%;
   height:1px;
   border-top:1px solid #f0f0f0;
}

.orderdetail_two_l_t_b_item_img_l{
   margin-left:20%;
   height:30px;
   background:#428bca;
   color:#fff;
   line-height:30px;
   text-align:center;
   float:left;
   width:150px;
   border-radius:5px;
   cursor:pointer;
}

.orderdetail_two_l_t_b_item_img_l2{
   margin-left:20%;
   height:30px;
   background:#428bca;
   color:#fff;
   line-height:30px;
   text-align:center;
   float:left;
   width:150px;
   border-radius:5px;
   cursor:pointer;
}

.orderdetail_two_l_t_b_item_img_r{
   margin-left:10px;
   height:30px;
   background:#fff;
   color:#000;
   line-height:30px;
   text-align:center;
   float:left;
   width:80px;
   border-radius:5px;
   cursor:pointer;
}

.orderdetail_two_l_t_b_item_img_r2{
   margin-left:10px;
   height:30px;
   background:#fff;
   color:#000;
   line-height:30px;
   text-align:center;
   float:left;
   width:80px;
   border-radius:5px;
   cursor:pointer;
}

.orderdetail_two_l_t_b_item_p{
    width:100%;
	height:auto;
}
.orderdetail_two_l_t_b_item_p_l{
   width:120px;
   height:180px;
   float:left;
   text-align:left;
   padding-left:10px;
   padding-top:5px;
   
}

.orderdetail_two_l_t_b_item_p_r{
   
   height:150px;
   float:left;
   word-wrap: break-word;
   word-break: normal; 
}

.orderdetail_two_l_t_b_item_p_r_item{
   height:25px;
   line-height:25px;
   text-align:left;
   word-wrap: break-word;
   word-break: normal; 
}

.bg_no{
   margin-left:10px;
   height:30px;
   background:#777777;
   color:#fff;
   line-height:30px;
   text-align:center;
   float:left;
   width:80px;
   border-radius:5px;
}

.bg_yes{
   margin-left:10px;
   height:30px;
   background:#449d44;
   color:#fff;
   line-height:30px;
   text-align:center;
   float:left;
   width:80px;
   border-radius:5px;
}
.r_con_table tbody tr:hover{background:#fff;}

</style>
</head>

<body>

<style type="text/css">body, html{background:url(images/main-bg.jpg) left top fixed no-repeat;}</style>
<div class="div_line">
		   <div class="div_line_item" onclick="show_newOrder(<?php echo $customer_id; ?>);">
		      今日订单: <span style="padding-left:10px;font-size:18px;font-weight:bold"><?php echo $new_order_count; ?></span>
		   </div>
		   <div class="div_line_item_split"></div>
		   <div class="div_line_item"  onclick="show_todayMoney(<?php echo $customer_id; ?>);">
		      今日销售: <span style="padding-left:10px;color:red;font-size:18px;font-weight:bold">￥<?php echo $today_totalprice; ?></span>
		   </div>
		   <div class="div_line_item_split"></div>
		   <div class="div_line_item"  onclick="show_newCustomer(<?php echo $customer_id; ?>);">
		       新增客户: <span style="padding-left:10px;font-size:18px;font-weight:bold"><?php echo $new_customer_count; ?></span>
		   </div>
		   <div class="div_line_item_split"></div>
		   <div class="div_line_item"  onclick="show_newQrsell(<?php echo $customer_id; ?>);">
		      新增推广员: <span style="padding-left:10px;font-size:18px;font-weight:bold"><?php echo $new_qr_count; ?></span>
		   </div>
		   <div class="div_line_item_split"></div>
		   <?php
  		    $stock_mun=0;
			$stock_pidarr="";
			$query_stock1="select id from weixin_commonshop_products where isvalid=true and storenum<".$stock_remind." and isout=0 and customer_id=".$customer_id;
			//echo $query_stock1;
			$result_stock1 = mysql_query($query_stock1) or die('Query failed24: ' . mysql_error());
			$stock_mun1 = mysql_num_rows($result_stock1);
			while ($row_stock1 = mysql_fetch_object($result_stock1)) {
				$stock_pid1 = $row_stock1->id;
				if(!empty($stock_pidarr)){
					$stock_pidarr=$stock_pidarr."_".$stock_pid1;
				}else{
					$stock_pidarr=$stock_pid1;
				}
				
			}
			
			$query_stock2="select id,propertyids,storenum from weixin_commonshop_products where isvalid=true and isout=0 and storenum>".$stock_remind." and customer_id=".$customer_id;
			$result_stock2 = mysql_query($query_stock2) or die('Query failed25: ' . mysql_error());
			$stock_mun2=0;
			while ($row_stock2 = mysql_fetch_object($result_stock2)) {
				$stock_pid = $row_stock2->id;			
				$stock_storenum = $row_stock2->storenum;			
				$stock_propertyids = $row_stock2->propertyids;			
				if(!empty($stock_propertyids)){
				   $query_stock3="SELECT * FROM weixin_commonshop_product_prices WHERE storenum<".$stock_remind." and product_id='".$stock_pid."' limit 0,1";
				   //echo  $query_stock3;
				   $result_stock3 = mysql_query($query_stock3) or die('Query failed26: ' . mysql_error());
				   $result_stock3_mun1 = mysql_num_rows($result_stock3);
				   while ($row_stock3 = mysql_fetch_object($result_stock3)) {
						$stock_pid2 = $row_stock3->product_id;
					}
				   if($result_stock3_mun1 !=0){
					   $stock_mun2=$stock_mun2 + 1;
					   if(!empty($stock_pidarr)){
							$stock_pidarr=$stock_pidarr."_".$stock_pid2;
						}else{
							$stock_pidarr=$stock_pid2;
						}
				   }				   
				}
			}
			$stock_mun=$stock_mun1+$stock_mun2; 
			
		   ?>
		   <div class="div_line_item"  onclick="show_stock(<?php echo $customer_id; ?>,'<?php echo $stock_pidarr; ?>');">
		      库存提醒: 已有<span style="padding-left:10px;color:red;font-size:18px;font-weight:bold"><?php echo $stock_mun; ?></span>个商品库存不足了
		   </div>
		   
		</div>
<div id="iframe_page">
	<div class="iframe_content">
	<link href="css/shop.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/shop.js"></script>
	<div class="r_nav">
		<ul>
			<li id="auth_page0" class=""><a href="base.php?customer_id=<?php echo $customer_id; ?>">基本设置</a></li>
			<li id="auth_page1" class=""><a href="fengge.php?customer_id=<?php echo $customer_id; ?>">风格设置</a></li>
			<li id="auth_page2" class=""><a href="defaultset.php?customer_id=<?php echo $customer_id; ?>&default_set=1">首页设置</a></li>
			<li id="auth_page3" class=""><a href="product.php?customer_id=<?php echo $customer_id; ?>">产品管理</a></li>
			<li id="auth_page4" class="cur"><a href="order.php?customer_id=<?php echo $customer_id; ?>&status=-1">订单管理</a></li>
			<?php if($is_supplierstr){?><li id="auth_page5" class=""><a href="supply.php?customer_id=<?php echo $customer_id; ?>">供应商</a></li><?php }?>
			<?php if($is_distribution){?><li id="auth_page6" class=""><a href="agent.php?customer_id=<?php echo $customer_id; ?>">代理商</a></li><?php }?>
			<li id="auth_page7" class=""><a href="qrsell.php?customer_id=<?php echo $customer_id; ?>">推广员</a></li>
			<li id="auth_page8" class=""><a href="customers.php?customer_id=<?php echo $customer_id; ?>">顾客</a></li>
			<li id="auth_page9"><a href="shops.php?customer_id=<?php echo $customer_id; ?>">门店</a></li>
			<?php if($isOpenPublicWelfare){?><li id="auth_page10"><a href="publicwelfare.php?customer_id=<?php echo $customer_id; ?>">公益基金</a></li><?php }?>
		</ul>
	</div>
<link href="css/operamasks-ui.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/operamasks-ui.min.js"></script>
<script type="text/javascript" src="../js/tis.js"></script>
<script language="javascript">

$(document).ready(shop_obj.orders_init);
</script>
<div id="orders" class="r_con_wrap">
<!-- 			<form class="search" id="search_form" method="post" action="order.php?customer_id=<?php echo $customer_id; ?>&issearch=1"> -->
			<form class="search" id="search_form" >
			<p>支付时间： 
			<span class="om-calendar om-widget om-state-default">
			<input type="text" class="input" id="pay_begintime" name="AccTime_A" value="<?php echo $pay_begintime; ?>" maxlength="21" id="K_1389249066532">
			<span class="om-calendar-trigger"></span></span>-<span class="om-calendar om-widget om-state-default">
			<input type="text" class="input" id="pay_endtime" name="AccTime_B" value="<?php echo $pay_endtime; ?>" maxlength="20" id="K_1389249066580">
			<span class="om-calendar-trigger"></span></span>&nbsp;
			下单时间：<span class="om-calendar om-widget om-state-default">
			<input type="text" class="input" id="begintime" name="AccTime_S" value="<?php echo $begintime; ?>" maxlength="20" id="K_1389249066532">
			<span class="om-calendar-trigger"></span></span>-<span class="om-calendar om-widget om-state-default">
			<input type="text" class="input" id="endtime" name="AccTime_E" value="<?php echo $endtime; ?>" maxlength="20" id="K_1389249066580">
			<span class="om-calendar-trigger"></span></span>&nbsp;
			&nbsp;每页记录数：<select name="pagesize" id="pagesize" style="width:70px;">
			                <option value=20 <?php if($pagesize==20){ ?>selected<?php } ?>>20</option>
							<option value=50 <?php if($pagesize==50){ ?>selected<?php } ?>>50</option>
							<option value=100 <?php if($pagesize==100){ ?>selected<?php } ?>>100</option>
							<option value=200 <?php if($pagesize==200){ ?>selected<?php } ?>>200</option>
			             </select>
			 &nbsp;订单来源：<select name="orgin_from" id="orgin_from" style="width:100px;">
			                <option value=-1>--所有--</option>
			                <option value=1 <?php if($orgin_from==1){ ?>selected<?php } ?>>不是推广来的订单</option>
							<option value=2 <?php if($orgin_from==2){ ?>selected<?php } ?>>推广来的订单</option>
							
			             </select>
			</p>
			
			订单状态：<select name="search_status" id="search_status" style="width:100px;">
				<option value="-1">--请选择--</option>
				<option value="1" <?php if($search_status==1){ ?>selected <?php } ?>>已确认</option>
				<option value="2" <?php if($search_status==2){ ?>selected <?php } ?>>待确认</option>
				<option value="3" <?php if($search_status==3){ ?>selected <?php } ?>>已支付</option>
				<option value="4" <?php if($search_status==4){ ?>selected <?php } ?>>未支付</option>
				<option value="5" <?php if($search_status==5){ ?>selected <?php } ?>>已发货</option>
				<option value="6" <?php if($search_status==6){ ?>selected <?php } ?>>未发货</option>
				<option value="7" <?php if($search_status==7){ ?>selected <?php } ?>>申请退货</option>
				<option value="8" <?php if($search_status==8){ ?>selected <?php } ?>>已取消</option>
				
				</select>
			发货状态：<select name="search_sendstatus" id="search_sendstatus" style="width:100px;">
				<option value="-1">--请选择--</option>
				<option value="1" <?php if($search_sendstatus==1){ ?>selected <?php } ?>>未发货</option>
				<option value="2" <?php if($search_sendstatus==2){ ?>selected <?php } ?>>已发货</option>
				<option value="3" <?php if($search_sendstatus==3){ ?>selected <?php } ?>>已收货</option>
				<option value="4" <?php if($search_sendstatus==4){ ?>selected <?php } ?>>申请退货</option>
				<option value="5" <?php if($search_sendstatus==5){ ?>selected <?php } ?>>退货已确认</option>
				</select>
			&nbsp;支付方式：
			<select name="search_paystyle" id="search_paystyle" style="width:100px;">
				<option value="-1">--请选择--</option>
				<option value="微信支付" <?php if($search_paystyle=="微信支付"){ ?>selected <?php } ?>>微信支付</option>
				<option value="支付宝支付" <?php if($search_paystyle=="支付宝支付"){ ?>selected <?php } ?>>支付宝支付</option>
				<option value="通联支付" <?php if($search_paystyle=="通联支付"){ ?>selected <?php } ?>>通联支付</option>
				<option value="货到付款" <?php if($search_paystyle=="货到付款"){ ?>selected <?php } ?>>货到付款</option>
				<option value="到店支付" <?php if($search_paystyle=="到店支付"){ ?>selected <?php } ?>>到店支付</option>
				<option value="会员卡余额支付" <?php if($search_paystyle=="会员卡余额支付"){ ?>selected <?php } ?>>会员卡余额支付</option>
				</select>
			
			订单号:<input type=text name="search_batchcode" id="search_batchcode" style="width:100px;" value="<?php echo $search_batchcode; ?>" />&nbsp;
			&nbsp;
			
			姓名:<input type=text name="search_name" id="search_name" style="width:100px;" value="<?php echo $search_name; ?>" />&nbsp;
			<select name="search_name_type" id="search_name_type" style="width:100px;">
				<option value="1" <?php if($search_name_type==1){ ?>selected <?php } ?>>微信昵称</option>
				<option value="2" <?php if($search_name_type==2){ ?>selected <?php } ?>>收货人</option>
			</select>
			订单归属
			<select name="search_order_ascription" id="search_order_ascription" onchange="change_ascription(this.value);" style="width:100px;">
				<option value="-2">--请选择--</option>
				<option value="-1" <?php if($search_order_ascription==-1){ ?>selected <?php } ?>>平台</option>
				<?php 
					$sql_supply="select user_id from promoters where isvalid=true and customer_id=".$customer_id." and isAgent=3";
					$result_supply = mysql_query($sql_supply) or die('Query failed_sql_supply: ' . mysql_error());
					$supply_id=-1;
					while ($row_supply = mysql_fetch_object($result_supply)) {
						$supply_id = $row_supply->user_id;
						$weixin_name="";
						$sql="select weixin_name from weixin_users where isvalid=true and customer_id=".$customer_id." and id=".$supply_id;
						$result = mysql_query($sql) or die('Query failed_sql_name: ' . mysql_error());
						while ($row = mysql_fetch_object($result)) {
							$weixin_name = $row->weixin_name; 
						}
				?>
				<option value="<?php echo $supply_id;?>" <?php if($search_order_ascription==$supply_id){ ?>selected <?php } ?>><?php echo $weixin_name; ?></option>
				<?php } ?>
			</select>
			<input type="button" onclick="searchForm();" class="search_btn" value="订单搜索">
			<input type="button" class="search_btn" value="导出记录+" onClick="exportRecord();" class="button" style="cursor:hand">
			<input type="button" class="search_btn" value="导出飞豆+" onClick="exportFeiDouRecord();" class="button" style="cursor:hand"> 
			<input type="button" class="search_btn" value="导出海关模板+" onClick="exportCustoms();" class="button" style="cursor:hand"> 
			
			<div>
			<!--<input type="checkbox" id="auto_refer"><label for="auto_refer">自动刷新订单</label>-->
			<input type="checkbox" style="float:left" id="auto_refer" name="auto_refer" value="on" <?php if($isauto){?> checked<?php } ?>><label for="auto_refer">自动刷新订单</label>
			</div>
		</form>
		<table border="0" cellpadding="5" cellspacing="0" class="r_con_table" id="order_list">
			<thead>
				<tr>
					<td width="8%" nowrap="nowrap">订单号</td>
					<td width="8%" nowrap="nowrap">姓名</td>
					<td width="8%" nowrap="nowrap">金额(快递费)</td>
					<td width="8%" nowrap="nowrap">付款方式</td>
					<td width="8%" nowrap="nowrap">支付状态</td>
					<td width="10%" nowrap="nowrap">下单时间</td>
					<td width="8%" nowrap="nowrap">发货状态</td>
					<td width="8%" nowrap="nowrap">订单状态</td>
					<td width="8%" nowrap="nowrap">推广员</td>
					<td width="10%" nowrap="nowrap" class="last">操作</td>
				</tr>
			</thead>
			<tbody>
			   <?php 
				
			     $sum_totalprice=0;
			     $agentcont_type=0;

                 $query_base="";
			     //$query_base="select wco.id as id,wco.pid,wco.paystatus,sum(wco.totalprice) as totalprice,sum(wco.need_score) as need_score,wco.is_payother,wco.confirm_sendtime,wco.confirm_receivetime,wco.address_id,wco.sendstatus,wco.exp_user_id,wco.status,wco.batchcode,wco.prvalues,wco.createtime,wco.user_id,wco.card_member_id,wco.paystyle,wco.expressnum,wco.payother_trade_no,wco.expressname,wco.express_id,wco.allipay_orderid,wco.allipay_isconsumed,wco.agentcont_type,wco.agent_id,wco.supply_id,wco.paytime,wco.Pay_Method,wco.remark,wu.name as wname,wu.weixin_name as wweixin_name,wca.name as adname from weixin_commonshop_orders wco , weixin_users wu,weixin_commonshop_addresses wca where wco.address_id=wca.id and wco.user_id=wu.id and wco.isvalid=true and wco.customer_id=".$customer_id;
				 //优化性能
				 if(!empty($search_name)){
					 if(!empty($search_name_type)){
						 switch($search_name_type){
							  case 1:
								$query_base="select wco.id as id,wco.pid,wco.paystatus,sum(wco.totalprice) as totalprice,sum(wco.need_score) as need_score,wco.is_payother,wco.confirm_sendtime,wco.confirm_receivetime,wco.address_id,wco.sendstatus,wco.exp_user_id,wco.status,wco.batchcode,wco.prvalues,wco.createtime,wco.user_id,wco.card_member_id,wco.paystyle,wco.expressnum,wco.payother_trade_no,wco.expressname,wco.express_id,wco.allipay_orderid,wco.allipay_isconsumed,wco.agentcont_type,wco.agent_id,wco.supply_id,wco.paytime,wco.Pay_Method,wco.remark from weixin_commonshop_orders wco  inner join weixin_users wu on wu.id=wco.user_id and   wco.isvalid=true and wco.customer_id=".$customer_id;
								
								$query_count="select count(1) as tcount from weixin_commonshop_orders wco  inner join weixin_users wu on wu.id=wco.user_id and   wco.isvalid=true and wco.customer_id=".$customer_id;
								
							    break;
							  case 2:
							    
								$query_base="select wco.id as id,wco.pid,wco.paystatus,sum(wco.totalprice) as totalprice,sum(wco.need_score) as need_score,wco.is_payother,wco.confirm_sendtime,wco.confirm_receivetime,wco.address_id,wco.sendstatus,wco.exp_user_id,wco.status,wco.batchcode,wco.prvalues,wco.createtime,wco.user_id,wco.card_member_id,wco.paystyle,wco.expressnum,wco.payother_trade_no,wco.expressname,wco.express_id,wco.allipay_orderid,wco.allipay_isconsumed,wco.agentcont_type,wco.agent_id,wco.supply_id,wco.paytime,wco.Pay_Method,wco.remark from weixin_commonshop_orders wco  inner join weixin_commonshop_addresses wca on wca.id=wco.address_id and   wco.isvalid=true and wco.customer_id=".$customer_id;
								
								$query_count="select count(1) as tcount from weixin_commonshop_orders wco  inner join weixin_commonshop_addresses wca on wca.id=wco.address_id and   wco.isvalid=true and wco.customer_id=".$customer_id;
								
							  break;
						 }
					 }
				 }else{
				      $query_base="select wco.id as id,wco.pid,wco.paystatus,sum(wco.totalprice) as totalprice,sum(wco.need_score) as need_score,wco.is_payother,wco.confirm_sendtime,wco.confirm_receivetime,wco.address_id,wco.sendstatus,wco.exp_user_id,wco.status,wco.batchcode,wco.prvalues,wco.createtime,wco.user_id,wco.card_member_id,wco.paystyle,wco.expressnum,wco.payother_trade_no,wco.expressname,wco.express_id,wco.allipay_orderid,wco.allipay_isconsumed,wco.agentcont_type,wco.agent_id,wco.supply_id,wco.paytime,wco.Pay_Method,wco.remark from weixin_commonshop_orders wco  where  wco.isvalid=true and wco.customer_id=".$customer_id;
					  
					   $query_count="select count(1) as tcount from weixin_commonshop_orders wco  where  wco.isvalid=true and wco.customer_id=".$customer_id;
				 }
				  echo $query_base."=========";
				 $query_totalprice="select sum(totalprice) as totalprice from weixin_commonshop_orders where isvalid=true and customer_id=".$customer_id;
				 $query="";
				 if($status>=0){
				    $query = $query." and wco.status=".$status;
				 }
				
				 switch($search_status){
				     case 1:
					   //已确认
                       $query = $query." and wco.status=1";					   
					   break;
					 case 2:
					   //未确认
                       $query = $query." and wco.status=0";					   
					   break;
					 case 3:
					   //未确认
                       $query = $query." and wco.paystatus=1";					   
					   break;
					 case 4:
					   //未确认
                       $query = $query." and wco.paystatus=0";					   
					   break;
					 case 5:
					   //已发货
                			   
                       $query = $query." and (wco.sendstatus=1 or wco.sendstatus=2)";		 			
					    
					   break;
					 case 6:
					   //未确认
                       $query = $query." and wco.sendstatus=0";					   
					   break;
					  case 7:
					   //已退货
                       $query = $query." and wco.sendstatus=3";					   
					   break;
					  case 8:
					   //已取消
                       $query = $query." and wco.status=-1";
					   break;
					   
				 }
				 
				 switch($search_sendstatus){
					 case 1:
					   //未发货
                       $query = $query." and wco.sendstatus=0";					   
					   break;
				     case 2:
					   //已发货
                			   
                       $query = $query." and (wco.sendstatus=1 or wco.sendstatus=2)";		 			   
					   break;
					 case 3:
					   //顾客已收货
                       $query = $query." and wco.sendstatus=2";					   
					   break;
					 case 4:
					   //顾客已退货
                       $query = $query." and wco.sendstatus=3";					   
					   break;
					 case 5:
					   //退货已确认
                       $query = $query." and wco.sendstatus=4";					   
					   break;
					 
				 }
				 
				 //查找相应的客户
				 if(!empty($user_ids)){
					 $query=$query." and wco.user_id in (".$user_ids.")";
				 }
				 //订单归属
				 if(!empty($search_order_ascription)){
					 switch($search_order_ascription){
						case -2:
						//所有订单				   
						break;
						case -1:
						//平台订单
						$query=$query." and wco.supply_id=-1";		   
						break;
						default:
						//供应商订单
						$query=$query." and wco.supply_id=".$search_order_ascription;
					 }					 
				 }
				 
				 if($begintime!=""){
				   $query = $query." and UNIX_TIMESTAMP(wco.createtime)>".strtotime($begintime);
				 }
				 if($endtime!=""){
				   $query = $query." and UNIX_TIMESTAMP(wco.createtime)<".strtotime($endtime);
				 } 
				   if($pay_begintime!=""){
				   $query = $query." and UNIX_TIMESTAMP(wco.paytime)>".strtotime($pay_begintime);
				 }
				 if($pay_endtime!=""){
				   $query = $query." and UNIX_TIMESTAMP(wco.paytime)<".strtotime($pay_endtime);
				 }  
				 if($search_paystyle!="-1"){
				    $query = $query." and wco.paystyle='".$search_paystyle."'";
				 }
				 $id=-1;
				 if(!empty($_GET["id"])){
				     $id = $_GET["id"];
					 $query = $query." and wco.id=".$id;
				 }
				 $user_id=-1;
				 if(!empty($_GET["user_id"])){
				     $user_id = $_GET["user_id"];
					 $query = $query." and wco.user_id=".$user_id;
				 }
				 
				 switch($orgin_from){
				    case 1:
					   $query = $query." and wco.exp_user_id<0";
					   break;
					case 2:
					   $query = $query." and wco.exp_user_id>0";
					   break;
					default:
					   break;
					   
				 }
				 if(!empty($search_batchcode)){
				     $query = $query." and wco.batchcode like '%".$search_batchcode."%'";
				 }
				
				 if(!empty($search_name)){
					 if(!empty($search_name_type)){
						 switch($search_name_type){
							  case 1:
								$query = $query." and wu.weixin_name like '%".$search_name."%'";
							  break;
							  case 2:
							    
								$query = $query." and wca.name like '%".$search_name."%'";
								
							  break;
						 }
					 }
				 }
				 // $query_base = $query_base.$query." group by batchcode order by batchcode desc ";
				 if($pay_begintime!="" or $pay_endtime!=""){
					 $query_base = $query_base.$query." group by wco.batchcode order by wco.paytime desc,wco.createtime desc";
				 }else{
					 $query_base = $query_base.$query." group by wco.batchcode order by wco.createtime desc ";
				 }
				 
				
				 $query_totalprice = $query_totalprice;
				 
				 $result = mysql_query($query_totalprice) or die('Query failed27: ' . mysql_error());
				 $all_totalprice=0;
				 while ($row = mysql_fetch_object($result)) {
				    $all_totalprice = $row->totalprice;
					
				 }
				 
				 $all_totalprice = round($all_totalprice,2);
				 /* 输出数量开始 */
			 	 $query2 = $query_count.$query;
				 $rcount_q2 = 0;
				 $result2 = mysql_query($query2) or die('Query failed28: ' . mysql_error());
				 while ($row2 = mysql_fetch_object($result2)) {
					$rcount_q2=$row2->tcount;
				 }
				 /* 输出数量结束 */
				 
				 $query_limit = $query_base." limit ".$start.",".$end;
echo $query_limit;
				 $result = mysql_query($query_limit) or die('Query failed29: ' . mysql_error());
				 $agent_id=-1; //代理商user_id
				 $supply_id=-1;//供应商user_id
				 $t_totalprice=0;
				 $weipay_style=0;// 0:不是微信支付/找人代付 1:微信支付/找人代付
	             while ($row = mysql_fetch_object($result)) {
				    $user_id = $row->user_id;
					$id = $row->id;
					$paystatus = $row->paystatus;
					$totalprice = $row->totalprice;
					
					$allipay_isconsumed = $row->allipay_isconsumed;
					$createtime = $row->createtime;
					$prvalues = $row->prvalues;
					$expressnum = $row->expressnum;
					$paystyle = $row->paystyle;
					$status = $row->status;
					$express_id = $row->express_id;
					$expressname = $row->expressname;
					$allipay_orderid = $row->allipay_orderid;
					$pid = $row->pid;
					
					$need_score = $row->need_score;
					$agentcont_type = $row->agentcont_type; //是否走代理商结算模式 1:代理商 0:推广员
					
					$batchcode = $row->batchcode;
					$Pay_Method = $row->Pay_Method; 
					$order_paytime = $row->paytime;	//支付时间
					$agent_id = $row->agent_id;	//代理商
					$supply_id = $row->supply_id;	//供应商
					$payother_trade_no = $row->payother_trade_no;	//代付商户订单号
					$remark = $row->remark;	//订单备注
					$d_totalprice = $totalprice ; //订单的金额
					$transaction_id=-1;
					if($paystyle=="微信支付" or $paystyle=="找人代付"){
						$weipay = "select transaction_id from weixin_weipay_notifys where isvalid=true and out_trade_no='".$batchcode."'";
						$result_weipay = mysql_query($weipay) or die('Query failed30: ' . mysql_error());
						while ($row_result_weipay = mysql_fetch_object($result_weipay)) {
							$transaction_id = $row_result_weipay->transaction_id;
						}
						 $weipay_style=1;// 0:不是微信支付/找人代付 1:微信支付/找人代付
					}
					
					/* if($paystyle=="找人代付"){
						 $weipay_style=1;// 0:不是微信支付/找人代付 1:微信支付/找人代付
					} */
					$query2="select price from weixin_commonshop_order_prices where isvalid=true and batchcode='".$batchcode."'";
					//added by wenjun 是否已经调整了价格. 单个购物没有调整价格（没有添加快递费），购物车购物价格已经做了调整，所以不需要加快递费
					$has_adjuest_price = false;
					$result2 = mysql_query($query2) or die('Query failed31: ' . mysql_error());
					while ($row2 = mysql_fetch_object($result2)) {
					    //获取订单的真实价格（可能是折扣总价）
					    $totalprice = $row2->price;
						$has_adjuest_price = true;
						//break;
						$query_express="select price from weixin_commonshop_order_express_prices where isvalid=true and batchcode='".$batchcode."' limit 0,1";
						$result_express = mysql_query($query_express) or die('Query failed32: ' . mysql_error());
						$express_price = 0;
						while ($row_express = mysql_fetch_object($result_express)) {
							$express_price = $row_express->price;
							break;
						}
					}
					
					//echo "totalprice====".$totalprice."<br/>";
					/*$expressfee="";
					$query2="select price from weixin_expresses where isvalid=true and id=".$express_id;
					$result2 = mysql_query($query2) or die('Query failed33: ' . mysql_error());
					while ($row2 = mysql_fetch_object($result2)) {
						$expressfee= $row2->price;
					}*/
	
					$statusstr="<span class='bg_no'>未完成</span>";	
					if($status==1){
					   $statusstr="<span class='bg_yes'>已完成</span>";	
					}else if($status==-1){
					   $statusstr="<span class='bg_no'>顾客已取消</span>";	
					}
					
					
					
					$query2= "select name,phone,weixin_name,weixin_fromuser from weixin_users where isvalid=true and id=".$user_id; 
					//echo $query2;
					$result2 = mysql_query($query2) or die('Query failed34: ' . mysql_error());
					$username="";
					$userphone="";
					$weixin_fromuser="";
					$weixin_name = "";
	                while ($row2 = mysql_fetch_object($result2)) {
					    $username=$row2->name;
						$userphone = $row2->phone;
						$weixin_fromuser= $row2->weixin_fromuser;
						$weixin_name=$row2->weixin_name;
						$username = $username."(".$weixin_name.")";
						break;
					}
					
					$paystatusstr = "<span class='bg_no'>未支付</span>";
					
					$callpaystr="<div  class='btn'  onclick=callpay(".$customer_id.",'".$weixin_fromuser."',".$user_id.")  title='催单'><i  class='icon-exclamation-sign'></i></div>";
					$paystatusstr =$paystatusstr.$callpaystr;
					
					if($paystatus==1){
					   $paystatusstr="<span class='bg_yes'>已支付</span>";
					}
					
					
					$Query2= "SELECT name,phone,weixin_name,weixin_fromuser FROM weixin_users WHERE isvalid=true and id=".$supply_id; 
					//echo $query2;
					$Result2 = mysql_query($Query2) or die('Query failed35: ' . mysql_error());
					$supply_username="";
					$supply_userphone="";
					$supply_weixin_fromuser="";
					$supply_username = "";
	                while ($Row2 = mysql_fetch_object($Result2)) {
					    $supply_username=$Row2->name;
						$supply_userphone = $Row2->phone;
						$supply_weixin_fromuser= $Row2->weixin_fromuser;
						$supply_weixin_name=$Row2->weixin_name;
						$supply_username = $supply_username."(".$supply_weixin_name.")";
						break;
					}
					$Query2= "SELECT name,phone,weixin_name,weixin_fromuser FROM weixin_users WHERE isvalid=true and id=".$agent_id; //代理商的姓名
					//echo $query2;
					$Result2 = mysql_query($Query2) or die('Query failed35_1: ' . mysql_error());
					$agent_username="";
					$agent_userphone="";
					$agent_weixin_fromuser="";
					$agent_username = "";
	                while ($Row2 = mysql_fetch_object($Result2)) {
					    $agent_username=$Row2->name;
						$agent_userphone = $Row2->phone;
						$agent_weixin_fromuser= $Row2->weixin_fromuser;
						$agent_weixin_name=$Row2->weixin_name;
						$agent_username = $agent_username."(".$agent_weixin_name.")";
						break;
					}
					if(empty($userphone)){
					   //如果没有输入信息，则以订单的一个地址为准
					   $query2="select name,phone from  weixin_commonshop_addresses where isvalid=true and user_id=".$user_id." limit 0,1";
					   $result2 = mysql_query($query2) or die('Query failed36: ' . mysql_error());
					   while ($row2 = mysql_fetch_object($result2)) {
					       $username = $row2->name;
						   $username = $username."(".$weixin_name.")";
						   $userphone = $row2->phone;
						   break;
					   }
					   
					}
					//查上一级推广员 start
					/* $exp_user_id = $row->exp_user_id;
					$exp_user_name="";
					if($exp_user_id>0){
					    $query2= "select name,phone,weixin_name,weixin_fromuser from weixin_users where isvalid=true and id=".$exp_user_id; 
					    $result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
						while ($row2 = mysql_fetch_object($result2)) {
							$exp_user_name=$row2->name;
							$weixin_name = $row2->weixin_name;
							$exp_user_name = $exp_user_name."(".$weixin_name.")";
							break;
						} 
					} */
					
					$exp_user_id = $row->exp_user_id;
					$query2="select id,parent_id,isAgent from promoters where  status=1 and isvalid=true and user_id=".$user_id;
					$result2 = mysql_query($query2) or die('Query failed37: ' . mysql_error());
					$parent_id = -1;
					$isAgent = 0;
					while ($row2 = mysql_fetch_object($result2)) {
						$parent_id = $row2->parent_id;
						$isAgent = $row2->isAgent;
						break;
					}	
					$exp_user_name="";
					$exp_fromuser="";
					if($exp_user_id>0){
					    $query2= "select name,phone,weixin_name,weixin_fromuser from weixin_users where isvalid=true and id=".$exp_user_id; 
					    $result2 = mysql_query($query2) or die('Query failed38: ' . mysql_error());
						while ($row2 = mysql_fetch_object($result2)) {
							$exp_user_name=$row2->name;
							$exp_weixin_name = $row2->weixin_name;
							$exp_fromuser = $row2->weixin_fromuser;
							$exp_user_name = $exp_user_name."(".$exp_weixin_name.")";
							break;
						}	
					/* 	$query2= "select name,phone,weixin_name,weixin_fromuser from weixin_users where isvalid=true and id=".$parent_id; 
						$result2 = mysql_query($query2) or die('Query failed39: ' . mysql_error());
						while ($row2 = mysql_fetch_object($result2)) {
							$exp_user_name=$row2->name;
							$weixin_name = $row2->weixin_name;
							$exp_user_name = $exp_user_name."(".$weixin_name.")";
							break;
						} */
					}
					
					//查上一级推广员 end
					$card_member_id=$row->card_member_id;
					$card_name="";
					if($card_member_id>0){
					    $query2="select card_id from weixin_card_members where isvalid=true and id=".$card_member_id;
						$result2 = mysql_query($query2) or die('Query failed40: ' . mysql_error());
						$card_id=-1;
						while ($row2 = mysql_fetch_object($result2)) {
						    $card_id = $row2->card_id;
						}
						
						$query2="select name from weixin_cards where isvalid=true and id=".$card_id;
						$result2 = mysql_query($query2) or die('Query failed41: ' . mysql_error());
						
						while ($row2 = mysql_fetch_object($result2)) {
						    $card_name = $row2->name;
						}
						
					}
					/*if($card_member_id<0){
						$query2 = "SELECT id from weixin_card_members where isvalid=true and  user_id=".$user_id." limit 0,1";
						$result2 = mysql_query($query2) or die('Query failed42: ' . mysql_error());
						$card_member_id=-1;
						while ($row2 = mysql_fetch_object($result2)) {
							$card_member_id=$row2->id;
						}
					}*/
					
					/*if(empty($before_batchcode)){
					     $before_batchcode=$batchcode;
						 $t_totalprice =  $t_totalprice + $totalprice;
						 continue;
					}else if($before_batchcode==$batchcode){
					    $t_totalprice =  $t_totalprice + $totalprice;
						continue;
					}else{
					    $t_totalprice  = $totalprice;
					}*/
					
					$query5="select count(1) as order_count from weixin_commonshop_orders where isvalid=true and batchcode='".$batchcode."'";
					$result5 = mysql_query($query5) or die('Query failed43: ' . mysql_error());
					$order_count;
					while ($row5 = mysql_fetch_object($result5)) {
					    $order_count = $row5->order_count;
					}
					$t_totalprice  = $totalprice;
					if(!$has_adjuest_price){
						$query5="select price from weixin_commonshop_order_express_prices where isvalid=true and batchcode='".$batchcode."' limit 0,1";
						$result5 = mysql_query($query5) or die('Query failed44: ' . mysql_error());
						$express_price = 0;
						while ($row5 = mysql_fetch_object($result5)) {
							$express_price = $row5->price;
							$t_totalprice = $t_totalprice+$express_price;
							break;
						}
						if($express_price ==0 and $express_id>0){
							if($supply_id>0){
								$query2="select price from weixin_expresses_supply where isvalid=true and id=".$express_id;
							}else{
							$query2="select price from weixin_expresses where isvalid=true and id=".$express_id;
							}
							$result2 = mysql_query($query2) or die('Query failed45: ' . mysql_error());
							while ($row2 = mysql_fetch_object($result2)) {
								$express_price= $row2->price;
							}
							if($t_totalprice<$nopostage_money or $nopostage_money==0){
								$t_totalprice  = $t_totalprice + $express_price;
							}else{
								$express_price=0;
							}
						}
					}
					
					$query5= "select totalprice from weixin_commonshop_changeprices where status=1 and isvalid=1 and batchcode='".$batchcode."'";
					$result5 = mysql_query($query5) or die('Query failed46: ' . mysql_error());
					$changeprice_str = "";
					while ($row5 = mysql_fetch_object($result5)) {
					    $t_totalprice = $row5->totalprice;
						if($t_totalprice>0){
							$changeprice_str = "(改价后)";
						}
						break;
					}
					
					$t_totalprice =round($t_totalprice,2); 
					
					$sum_totalprice = $sum_totalprice + $totalprice;
					$sum_totalprice =round($sum_totalprice,2);
					
					$address_id = $row->address_id;
					
					/* $query3="select name,phone,address,location_p,location_c,location_a from weixin_commonshop_addresses where  id=".$address_id;
					$result3 = mysql_query($query3) or die('Query failed: ' . mysql_error());
					$order_username = "";
                    $order_userphone ="";
                    $order_address="";					
					while ($row3 = mysql_fetch_object($result3)) {
					    $order_username = $row3->name;
						$order_userphone = $row3->phone;
						$order_address = $row3->address;
						$location_p=$row3->location_p;
						$location_c=$row3->location_c;
						$location_a=$row3->location_a;
					} */
					$query3="select name,phone,address,location_p,location_c,location_a from weixin_commonshop_order_addresses where  batchcode=".$batchcode;
					$result3 = mysql_query($query3) or die('Query failed47: ' . mysql_error());
					$order_username = "";
                    $order_userphone ="";
                    $order_address="";					
					while ($row3 = mysql_fetch_object($result3)) {
					    $order_username = $row3->name;
					    $order_username_show =  $order_username."(".$weixin_name.")";
						$order_userphone = $row3->phone;
						$order_address = $row3->address;
						$location_p=$row3->location_p;
						$location_c=$row3->location_c;
						$location_a=$row3->location_a;
					}
					if(empty($order_username)){
					   $query3="select name,phone,address,location_p,location_c,location_a from weixin_commonshop_addresses where  id=".$address_id;
						$result3 = mysql_query($query3) or die('Query failed48: ' . mysql_error());
						$order_username = "";
						$order_userphone ="";
						$order_address="";					
						while ($row3 = mysql_fetch_object($result3)) {
							$order_username = $row3->name;
							$order_username_show =  $order_username."(".$weixin_name.")";
							$order_userphone = $row3->phone;
							$order_address = $row3->address;
							$location_p=$row3->location_p;
							$location_c=$row3->location_c;
							$location_a=$row3->location_a;
						}
					}
					
					$sendstatus = $row->sendstatus;
					
					$sendstatusstr="<span class='bg_no'>未发货</span>";	
					$confirm_sendtimestr="";
					$confirm_receivetimestr="";
					switch($sendstatus){
					   case 1:
					       $sendstatusstr="<p><span class='bg_yes'>已发货</span></p>";	
					       break;
					   case 2:
					       $sendstatusstr="<span class='bg_yes'>顾客已收货</span>";	 
						   break;
					   case 3:
					       $sendstatusstr="<span class='bg_no'>顾客已退货</span>";	 
						   break;
						case 4:
					       $sendstatusstr="<span class='bg_yes'>退货已确认</span>";	 
						   break;
						case 5:
					       $sendstatusstr="<span class='bg_yes'>顾客申请退款</span>";	 
						   break;
						case 6:
					       $sendstatusstr="<span class='bg_yes'>退款完成</span>";	 
						   break;
					}
					$confirm_sendtime = $row->confirm_sendtime;
					$confirm_receivetime = $row->confirm_receivetime;
					if(!empty($confirm_sendtime) and $confirm_sendtime!="0000-00-00 00:00:00"){
					  $confirm_sendtimestr="发货时间:".$confirm_sendtime."";
				    }
				    if(!empty($confirm_receivetime) and $confirm_receivetime!="0000-00-00 00:00:00"){
					  
					  if($sendstatus==4 or $sendstatus==6){
						  $confirm_receivetimestr="<span style='font-size:10px;'>退货时间:".$confirm_receivetime."</span>";
					  }else{
						  $confirm_receivetimestr="<span style='font-size:10px;'>收货时间:".$confirm_receivetime."</span>";
					  }
				    }
					
					$is_payother = $row->is_payother;
					if($is_payother){
					   //找人代付
					   
					}
					
					$refund= 0;
					$query5="select sum(refund) as refund from weixin_commonshop_refunds where isvalid=true and batchcode='".$batchcode."'";
					if(!empty($payother_trade_no)){
						$query5="select sum(refund) as refund from weixin_commonshop_refunds where isvalid=true and batchcode='".$payother_trade_no."'";
					}
					$result5 = mysql_query($query5) or die('Query failed49: ' . mysql_error());
					while ($row5 = mysql_fetch_object($result5)) {
					   $refund = $row5->refund;
					}
					/*if($t_totalprice<$nopostage_money or $nopostage_money==0){//总额加运费.
						$t_totalprice  = $t_totalprice + $express_price;
					} */ 
					
			   ?>
                      <tr id="batchcode_<?php echo $batchcode;?>" slide="0">
				   
					       <td>
						   <div style="text-align: right;">
						   <?php if($agentcont_type==1){?>
						   <img src="images/dai.png" ondragstart="return false;">
						   <?php }?>
						   <?php if($supply_id>0){?>
					       <img src="images/gong.png" ondragstart="return false;">
						   <?php }?>
						   </div>
						   <a href="javascript:show_send('<?php echo $batchcode; ?>',1);" ><?php echo $batchcode; ?></a>
						   
						   </td>
						   <td><p><?php echo $order_username_show; ?>
						   <?php if(!empty($weixin_fromuser)){
							 ?>  
							   <a  class="btn"  href="../weixin_inter/send_to_msg.php?fromuserid=<?php echo $weixin_fromuser; ?>&customer_id=<?php echo passport_encrypt($customer_id)?>"  title="对话"><i  class="icon-comment"></i></a>
							<?php   
						   }  ?>
						   </p>
						   <p><?php echo $order_userphone ; ?></p>
						   </td>
						   
						   <td><span id="order_totalprice_<?php echo $batchcode; ?>"><?php echo $t_totalprice; ?></span>
						   <?php if($express_price>0){ echo "(含运费:".$express_price.")"; } ?>
						   <?php if($need_score>0){ ?>
						      <br/>所需积分:<?php echo $need_score; ?>
						   <?php } ?>
						   </td>
						   
						   <td><?php echo $paystyle; ?>
							<?php if($weipay_style and $transaction_id!=-1){?>
							(<a href="weipay_detail.php?batchcode=<?php echo $batchcode;?>&customer_id=<?php echo $customer_id; ?>&fromuser=<?php echo $weixin_fromuser; ?>&exp_user_id=<?php echo $exp_user_id; ?>&id=<?php echo $id; ?>" style="cursor:pointer;"><?php echo $transaction_id;?></a>)
							<?php } ?>
						   <?php if($paystyle=="通联支付"){ ?>  
						   <br/>
						   <?php if($paystatus==1){?>
						   (<a href="allipay_detail.php?allipay_orderid=<?php echo $allipay_orderid; ?>">
						   <?php echo $allipay_orderid; ?></a>)
						   <?php }else{?>
						   (<?php echo $allipay_orderid; ?>)
						   <?php }?>
						   <?php if($allipay_isconsumed){ ?>
						     <br/>(已消费)
						   <?php } ?>
						   <?php  } ?>
						       <?php if(!empty($card_name)){ ?>
							     <br/>使用会员卡:<a href="../card_member.php?customer_id=<?php echo passport_encrypt($customer_id)?>&card_id=<?php echo $card_id; ?>&card_member_id=<?php echo $card_member_id; ?>"><?php echo $card_name; ?></a>
							   <?php } ?>
						   </td>
						   <td><?php echo $paystatusstr; ?><br/>
						    <?php if(!$is_payother and $order_paytime!=NULL){?><br/>支付时间:<?php echo $order_paytime; ?><br/><?php }?>
							 
						   <?php if($is_payother){ //找人代付
						       $query3 = "select pay_user_id ,paytime,pay_username from weixin_commonshop_otherpay_descs where isvalid=true and batchcode='".$batchcode."' order by  id desc limit 0,1";
							   $result3 = mysql_query($query3) or die('Query failed50: ' . mysql_error());
					           $pay_user_id=-1;
							   $paytime = "";
							    $pay_username="";
					           while ($row3 = mysql_fetch_object($result3)) {
							       $pay_user_id=$row3->pay_user_id;
								   if($pay_user_id>0){
									   $paytime = $row3->paytime;
									   $pay_username = $row3->pay_username;
									     if(empty($pay_username)){
									      $query5="select weixin_name from weixin_users where id=".$pay_user_id;
										  $result5 = mysql_query($query5) or die('Query failed51: ' . mysql_error());
										  while ($row5 = mysql_fetch_object($result5)) {
										      $pay_username = $row5->weixin_name;
											  break;
										  }
									   }
								   }
							   }
							  if($Pay_Method==1){ $paytime = $order_paytime;}//
						   ?>
						      
						       <br/>代付人:<?php echo $pay_username; ?><br/>
							   支付时间：<?php echo $paytime; ?> 
						   <?php } ?>
						   <?php if($refund>0){ 
						      
						   ?>
						      已退款：<?php echo $refund; ?>
						   <?php } ?>
						   <?php if($Pay_Method==1){?><span style="color:red;">后台支付</span><?php }?>
						   </td>
						   <td><?php echo $createtime; ?></td>
						   <td><span id="span_sendstatus_<?php echo $batchcode; ?>"><?php echo $sendstatusstr; ?></span>
						   <span id="sendtime_<?php echo $batchcode;?>" style='font-size:10px;'>
						   <?php if(!empty($confirm_sendtimestr)){ ?>
						      <br/><br/><?php echo $confirm_sendtimestr; ?>
						   <?php } ?></span>
						   <?php if(!empty($confirm_receivetimestr)){ ?>
						      <br/><br/><?php echo $confirm_receivetimestr; ?>
						   <?php } ?>
						   </td>
						   <td><?php echo $statusstr; ?></td>
						   <td>
						  
						   <?php if($exp_user_id>0 and $isAgent!=1 and $isAgent!=3){ ?> 
						   <a href="qrsell.php?exp_user_id=<?php echo $exp_user_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $exp_user_name; ?></a>
						   <?php if(!empty($exp_fromuser)){
							 ?>  
							   <a  class="btn"  href="../weixin_inter/send_to_msg.php?fromuserid=<?php echo $exp_fromuser; ?>&customer_id=<?php echo passport_encrypt($customer_id)?>"  title="对话"><i  class="icon-comment"></i></a>
							<?php
								}  
							} ?>
						   <?php if($supply_id>0){?><br>供应商: <a href="supply.php?search_user_id=<?php echo $supply_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $supply_username;}?></a>
						   </td>
						   <td>
							<p><a href="../common_shop/jiushop/order_detail.php?batchcode=<?php echo $batchcode;?>&customer_id=<?php echo $customer_id; ?>&prvalues=<?php echo $prvalues; ?>" style="cursor:pointer;">订单详情</a></p>
						
							<?php if($sendstatus==0 and ($paystatus==1 or $paystyle=="货到付款") and $supply_id<0){ ?> 
							<p id="p_sendstatus_<?php echo $batchcode; ?>"><a href="javascript:show_send('<?php echo $batchcode; ?>',0);" style="cursor:pointer;">发货</a></p>
							<?php } ?>
							
							<?php if($status==0 and $sendstatus==0){ ?>
							<p id="p_changeaddress_<?php echo $batchcode; ?>"><a href="javascript:show_changeaddress('<?php echo $batchcode; ?>');" style="cursor:pointer;">修改收件地址</a></p>
							<?php } ?>
							<?php if($sendstatus==1 and $paystatus==1 and $status!=1){ ?>
							<p id="p_sendstatus_new_<?php echo $batchcode; ?>"><a href="javascript:show_send('<?php echo $batchcode; ?>',0);" style="cursor:pointer;">修改发货信息</a></p>
							<?php } ?>
							<p id="p_sendstatus_back_<?php echo $batchcode; ?>" style="display:none;"><a href="javascript:show_send('<?php echo $batchcode; ?>',0);" style="cursor:pointer;">修改发货信息</a></p>
							
			
							<?php if($status==0 and $paystatus==0 ){ ?> 
							<?php if($agentcont_type==0 and $supply_id<0){?><p id="p_changeprice_<?php echo $batchcode; ?>"><a href="javascript:show_changeprice('<?php echo $batchcode; ?>',<?php echo $order_count; ?>);" style="cursor:pointer;">改价</a></p><?php }?> 
							<p><span onclick="confirmPay('order.php?batchcode=<?php echo $batchcode; ?>&op=confirm_pay&paystyle=<?php echo $paystyle; ?>&card_member_id=<?php echo $card_member_id; ?>&totalprice=<?php echo $t_totalprice ; ?>&paystyle=<?php echo $paystyle; ?>&pid=<?php echo $pid; ?>&user_id=<?php echo $user_id; ?>&need_score=<?php echo $need_score; ?>');" style="cursor:pointer">确认支付</span></p>
							<?php } ?>
							
							<?php 
							if($weipay_style){
							?>
							  <?php if($version==2){ ?>
							  <p><a href="weipay_detail.php?batchcode=<?php echo $batchcode;?>&customer_id=<?php echo $customer_id; ?>&fromuser=<?php echo $weixin_fromuser; ?>&exp_user_id=<?php echo $exp_user_id; ?>&id=<?php echo $id; ?>" style="cursor:pointer;">微信支付详情</a></p>
							 <?php }else{ ?>
							  <p><a href="weipay_detail2.php?batchcode=<?php echo $batchcode;?>&customer_id=<?php echo $customer_id; ?>&fromuser=<?php echo $weixin_fromuser; ?>" style="cursor:pointer;">微信支付详情</a></p>  
							 <?php }
							 
							 } ?>
							<?php 
							if($paystyle=="支付宝"){
							?>
							  <p><a href="alipay_detail.php?order_id=<?php echo $id;?>&customer_id=<?php echo $customer_id; ?>&fromuser=<?php echo $weixin_fromuser; ?>" style="cursor:pointer;">支付宝支付详情</a></p>
							<?php } ?>
							<?php /*if($status==0 and $paystatus==1){ if($sendstatus==2 or $sendstatus==4 or $express_id==0){ */?>
							<p id="confirm_<?php echo $batchcode ?>" class="<?php echo $status ?>_<?php echo $paystatus ?>" <?php if($status==0 and $paystatus==1){ if($sendstatus==2 or $sendstatus==4 or $sendstatus==6 or $express_id==0){ ?> style="display:block" <?php }else{?>style="display:none"<?php  }}else{ ?> style="display:none" <?php } ?>>
							<span onclick="if(!confirm('您确认要确认订单完成吗？确认后，表示订单已经完成，并且不能取消！'))return false; else goUrl('order.php?batchcode=<?php echo $batchcode ?>&op=status&paystyle=<?php echo $paystyle; ?>&card_member_id=<?php echo $card_member_id; ?>&totalprice=<?php echo $t_totalprice ; ?>&paystyle=<?php echo $paystyle; ?>&pid=<?php echo $pid; ?>&user_id=<?php echo $user_id; ?>&createtime=<?php echo $createtime;?>&express_price=<?php echo $express_price;?>');" style="cursor:pointer">确认完成</span>
							<?php if($sendstatus!=4 and $sendstatus!=6){?><br><span><a href="order_send_redpack.php?customer_id=<?php echo $customer_id;?>&batchcode=<?php echo $batchcode;?>">红包确定</a></span><?php }?>
							</p>
							<?php/* } } */?>
							
							<?php if($sendstatus==3 and $supply_id<0){ ?>  
							<p><span onclick="if(!confirm('您确定要确认退货完成吗？确定后，表示该订单退货已经完成，并且不能取消！'))return false; else goUrl('order.php?batchcode=<?php echo $batchcode ?>&op=status_back&paystyle=<?php echo $paystyle; ?>&card_member_id=<?php echo $card_member_id; ?>&totalprice=<?php echo $t_totalprice ; ?>&paystyle=<?php echo $paystyle; ?>&pid=<?php echo $pid; ?>&user_id=<?php echo $user_id; ?>');" style="cursor:pointer">退货完成</span></p>
							<?php } ?>
							<?php if($paystatus==1){ ?>
							<p><a href="order_rebate_log.php?batchcode=<?php echo $batchcode ?>&customer_id=<?php echo $customer_id;?>">返佣记录</a></p>
							<?php } ?>							
							<?php if($sendstatus==5){ ?>
							<p><span onclick="if(!confirm('您确定要确认退款完成吗？确定后，表示该订单退款已经完成，并且不能取消！'))return false; else goUrl('order_refund.php?batchcode=<?php echo $batchcode ?>&paystyle=<?php echo $paystyle; ?>&totalprice=<?php echo $t_totalprice ; ?>&pid=<?php echo $pid; ?>&user_id=<?php echo $user_id; ?>');" style="cursor:pointer">退款完成</span></p>
							<?php } ?>
							<?php 
							if(($status==0 and $paystatus==0) or $status==1 or $status==-1){
								if($is_shopgeneral==0 or $is_generalcustomer==1){ 
							?>
							<p><span onclick="if(!confirm('您确认要删除此数据吗?删除后数据不能恢复!'))return false; else goUrl('order.php?batchcode=<?php echo $batchcode ?>&op=del&card_member_id=<?php echo $card_member_id; ?>&totalprice=<?php echo $t_totalprice ; ?>');"  style="cursor:pointer">删除</span></p> 
							<?php 
								} 
							}
							?>
							
						   </td>
					   </tr>
					   <tr style="background:none;display:none" id="tr_changeprice_<?php echo $batchcode; ?>">
					     <td  colspan=13 style="width:100%;">
						  <table style="width:100%;"><tr><td>
						     <div style="width:98%;margin:0 auto;height:100px;">
							     <div class="orderdetail_one" onclick="cancel_changeprice('<?php echo $batchcode; ?>');">
								    <div class="orderdetail_one_r">
                                      <i  class="icon-remove"></i>
								   </div>
								 </div>
								 <style>
								    .changeprice_one{
									    width:90%;
										margin: 0 auto;
										height:90px;
									}
									.changeprice_one_item{
									    text-align:right;
										height:40px;
										width:100%;
									}
								 </style>
								 <div class="changeprice_one">
								      <div class="changeprice_one_item">
									       订单价格:&nbsp;&nbsp;￥<?php echo $t_totalprice; ?>
									  </div>
								      <div class="changeprice_one_item">
									       现价:&nbsp;&nbsp;<input type=text value="" name="nowprice_<?php echo $batchcode; ?>" id="nowprice_<?php echo $batchcode; ?>" />	
									  </div>
									  <style>
									  .item_img_l{
										   height:30px;
										   background:#428bca;
										   color:#fff;
										   line-height:30px;
										   text-align:center;
										   width:150px;
										   border-radius:5px;
										   cursor:pointer;
										   float:right;
										}
										
										.item_img_r{
										   height:30px;
										   background:#fff;
										   color:#000;
										   line-height:30px;
										   text-align:center;
										   width:80px;
										   border-radius:5px;
										   cursor:pointer;
										   float:right;
										}
									  </style>
									  <div class="changeprice_one_item">
									    
										<div class="item_img_r"  onclick="cancel_changeprice('<?php echo $batchcode; ?>');">
										   取消
										</div>
										 <div class="item_img_l"  onclick="sub_changeprice('<?php echo $batchcode; ?>');">
										   确认改价
										</div>
									  </div>
								 </div>
							 	
							 </div>
						  </td></tr>
						  </table>
						 </td>
					  </tr>
					  <tr style="background:none;display:none" id="tr_changeaddress_<?php echo $batchcode; ?>">
					     <td  colspan=13 style="width:100%;">
						  <table style="width:100%;"><tr><td>
						     <div style="width:98%;margin:0 auto;height:100px;">
							     <div class="orderdetail_one" onclick="cancel_changeaddress('<?php echo $batchcode; ?>');">
								    <div class="orderdetail_one_r">
                                      <i  class="icon-remove"></i>
								   </div>
								 </div>
								 <style>
								    .changeprice_one{
									    width:90%;
										margin: 0 auto;
										height:90px;
									}
									.changeprice_one_item{
									    text-align:right;
										height:40px;
										width:100%;
									}
								 </style>
								 <div class="changeprice_one">
								    
								      <div class="changeprice_one_item">
									       收件人姓名:&nbsp;&nbsp;<input type=text value="<?php echo $order_username; ?>" name="order_username_<?php echo $batchcode; ?>" id="order_username_<?php echo $batchcode; ?>" />	
									  </div>
									  <div class="changeprice_one_item">
									       收件人手机:&nbsp;&nbsp;<input type=text value="<?php echo $order_userphone; ?>" name="order_userphone_<?php echo $batchcode; ?>" id="order_userphone_<?php echo $batchcode; ?>" />	
									  </div>
									  <div class="changeprice_one_item" style="height:160px">
									       收件人地址:&nbsp;&nbsp;
			
							<select name="location_p_<?php echo $batchcode; ?>" id="location_p_<?php echo $batchcode; ?>" ></select><br>
							<select name="location_c_<?php echo $batchcode; ?>" id="location_c_<?php echo $batchcode; ?>" ></select><br>
							<select name="location_a_<?php echo $batchcode; ?>" id="location_a_<?php echo $batchcode; ?>" ></select><br>
							<script src="../common_shop/jiushop/js/region_select.js"></script>
							<script type="text/javascript">
								new PCAS('location_p_<?php echo $batchcode; ?>', 'location_c_<?php echo $batchcode; ?>', 'location_a_<?php echo $batchcode; ?>', '<?php echo $location_p; ?>', '<?php echo $location_c; ?>', '<?php echo $location_a; ?>');
							</script>

										   <input type=text value="<?php echo $order_address; ?>" name="order_address_<?php echo $batchcode; ?>" id="order_address_<?php echo $batchcode; ?>" />	
									  </div>
									  <style>
									  .item_img_l{
										   height:30px;
										   background:#428bca;
										   color:#fff;
										   line-height:30px;
										   text-align:center;
										   width:150px;
										   border-radius:5px;
										   cursor:pointer;
										   float:right;
										}
										
										.item_img_r{
										   height:30px;
										   background:#fff;
										   color:#000;
										   line-height:30px;
										   text-align:center;
										   width:80px;
										   border-radius:5px;
										   cursor:pointer;
										   float:right;
										}
									  </style>
									  <div class="changeprice_one_item">
									    
										<div class="item_img_r"  onclick="cancel_changeaddress('<?php echo $batchcode; ?>');">
										   取消
										</div>
										 <div class="item_img_l"  onclick="sub_changeaddress('<?php echo $batchcode; ?>',<?php echo $address_id; ?>);">
										   确认修改
										</div>
									  </div>
								 </div>
							 	
							 </div>
						  </td></tr>
						  </table>
						 </td>
					  </tr>
					   <tr style="background:none;display:none" id="tr_<?php echo $batchcode; ?>">
					     <td  colspan=13 style="width:100%;background:#f9f9f9;">
						  <table style="width:100%;"><tr><td>
						     <div class="orderdetail">
							     <div class="orderdetail_one" onclick="cancel_send('<?php echo $batchcode; ?>');">
								   <div class="orderdetail_one_r">
                                      <i  class="icon-remove"></i>
								   </div>
								</div>
							    <div class="orderdetail_two">
								  <div class="orderdetail_two_l">
								   <div class="orderdetail_two_l_t">
								      <div class="orderdetail_two_l_t_t">
									     订单信息
									  </div>
									  
									  <div class="orderdetail_two_l_t_b">
									     <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      订单号:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											    <?php echo $batchcode; ?>
											 </div>
                                         </div>
										  <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      下单时间:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											    <?php echo $createtime; ?>
											 </div>
                                         </div>
										 <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      支付时间:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											      <?php if(!$is_payother and $order_paytime!=NULL){ echo $order_paytime; }?>
												  <?php if($is_payother){ //找人代付  ?> <?php echo $paytime;} ?>
											 </div>
                                         </div>
                                         <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      支付方式:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											    <?php echo $paystyle; ?>
											 </div>
                                         </div>
										 <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      订单金额:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											    <span style="color:red;">￥<?php echo $d_totalprice; ?>元</span>
											 </div>
                                         </div> 
                                         <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      买家姓名:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											    <?php echo $username; ?>
											 </div>
                                         </div>
                                         <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      买家电话:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											    <?php echo $userphone; ?>
											 </div>
                                         </div>
										 <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      邮费:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											  <span style="color:red;">￥<?php echo $express_price; ?>元</span>
											 </div>
                                         </div>
										 <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      实付金额:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											     <span style="color:red;">￥<?php echo $t_totalprice.$changeprice_str; ?>元</span>
											 </div>
                                         </div>
										 <?php if($supply_id>0){?>
										 <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      供应商:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											     <span><?php echo $supply_username; ?></span>
											 </div>
                                         </div>
										 <?php }?>
										 <?php if($agent_id>0 and $agentcont_type==1){?>
										 <div class="orderdetail_two_l_t_b_item">
										     <div class="orderdetail_two_l_t_b_item_l">
											      代理商:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											     <span><?php echo $agent_username; ?></span>
											 </div>
                                         </div>
										 <?php }?>
									  </div>
								      
								   </div>
								   <div class="orderdetail_two_l_m">
								      
								   </div>
								   
								    <?php $query3 = "SELECT id,pid,rcount,prvalues,totalprice FROM weixin_commonshop_orders where isvalid=true and  batchcode='".$batchcode."' and customer_id=".$customer_id;
											  $result3 = mysql_query($query3) or die('Query failed52: ' . mysql_error());
											  while ($row3 = mysql_fetch_object($result3)) { 
											  
											      $pid = $row3->pid;
												  $rcount = $row3->rcount;
												  $prvalues = $row3->prvalues;
												  $p_totalprice = $row3->totalprice;
												  $prvalues=str_replace("|","",$prvalues);
												  
												  $query2 = 'SELECT id,good_level,meu_level,bad_level,name,description,orgin_price,now_price,type_id,foreign_mark FROM weixin_commonshop_products where id='.$pid;

													$result2 = mysql_query($query2) or die('Query failed53: ' . mysql_error());

													$product_name="";
													$product_imgurl= "";
													
                                                    $foreign_mark="";
													while ($row2 = mysql_fetch_object($result2)) {
													   
														$product_name = $row2->name;
														$foreign_mark = $row2->foreign_mark;
														
													}
													
													$query2 = 'SELECT id,imgurl FROM weixin_commonshop_product_imgs where  isvalid=true and product_id='.$pid;
													$result2 = mysql_query($query2) or die('Query failed54: ' . mysql_error());

													//$title="";
													while ($row2 = mysql_fetch_object($result2)) {
														$product_imgurl = $row2->imgurl;
														//$title = $row->title;
													}
													
													$prvarr= explode("_",$prvalues);
													$prvstr="";
													for($i=0;$i<count($prvarr);$i++){
														$prvid = $prvarr[$i];
														if($prvid>0){
															$query2 = "SELECT name from weixin_commonshop_pros where isvalid=true and id=".$prvid;
															//echo $query;
															$result2 = mysql_query($query2) or die('Query failed55: ' . mysql_error());
															while ($row2 = mysql_fetch_object($result2)) {
															   $prname = $row2->name;
															   $prvstr = $prvstr.$prname."  ";
															}
														}
													}
													$query2="select foreign_mark from weixin_commonshop_product_prices where product_id=".$pid." and proids='".$prvalues."'";
													//echo $query2;
													$result2 = mysql_query($query2) or die('Query failed56: ' . mysql_error());

													while ($row2 = mysql_fetch_object($result2)) {
													     $foreign_mark = $row2->foreign_mark;
													}

											  ?>
								   <div class="orderdetail_two_l_b" style="margin-top:20px">
								       <div class="orderdetail_two_l_t_t">
									     商品信息 
									  </div>
									  
									  <div class="orderdetail_two_l_t_b">
									   
					
									        <div class="orderdetail_two_l_t_b_item_p">
												 <div class="orderdetail_two_l_t_b_item_p_l">
												   <img src="<?php echo $product_imgurl; ?>" style="width:80px;height:80px;" />
												 </div>
												 <div class="orderdetail_two_l_t_b_item_p_r">
													 <div class="orderdetail_two_l_t_b_item_p_r_item">
														<?php echo $product_name; ?>
													 </div>
													 <div class="orderdetail_two_l_t_b_item_p_r_item">
														数量:&nbsp;<?php echo $rcount; ?>
													 </div>
													 <div class="orderdetail_two_l_t_b_item_p_r_item">
														价格:&nbsp;￥<?php echo $p_totalprice; ?>
													 </div>
													 <div class="orderdetail_two_l_t_b_item_p_r_item">
														外部标识:&nbsp;<span style="color:red"><?php echo $foreign_mark; ?></span>
													 </div>
													 <div class="orderdetail_two_l_t_b_item_p_r_item">
														<?php echo $prvstr; ?>
													 </div>
													 
												 
												 </div>
											 </div>
											 <div style="clear:both;"></div>
                                         
									  
                                 
                                         </div>								 
								   </div>
								   <?php } ?>	
								</div>
								
							<div class="orderdetail_two_r">
								    <div class="orderdetail_two_r_con">
									  <div class="orderdetail_two_l_t_t">发货信息</div>
									  
									  <div class="orderdetail_two_l_t_b">
									    <div class="orderdetail_two_l_t_b_item"  style="height:30px;">
										   <div class="orderdetail_two_l_t_b_item_l">收货人:&nbsp;&nbsp;</div>
											<div class="orderdetail_two_l_t_b_item_r"><?php echo $order_username; ?></div>
                                  </div>
										<div class="orderdetail_two_l_t_b_item"  style="height:30px;">
										   <div class="orderdetail_two_l_t_b_item_l">联系电话:&nbsp;&nbsp;</div>
											<div class="orderdetail_two_l_t_b_item_r"><?php echo $order_userphone; ?></div>
                                  </div>
										<div class="orderdetail_two_l_t_b_item"  style="height:30px;">
										    <div class="orderdetail_two_l_t_b_item_l">收货地址:&nbsp;&nbsp;</div>
											 <div class="orderdetail_two_l_t_b_item_r"><?php echo $location_p.$location_c.$location_a.$order_address; ?></div>
                                  </div>
										<div class="orderdetail_two_l_t_b_item"  style="height:30px;">
										    <div class="orderdetail_two_l_t_b_item_l">订单备注:&nbsp;&nbsp;
											</div>
											<div class="orderdetail_two_l_t_b_item_r"><?php echo $remark; ?>
											</div>
                                  </div>
										 
										 <div class="orderdetail_two_l_t_b_item"  style="height:30px;">
										     <div class="orderdetail_two_l_t_b_item_l">
											      物流公司:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											    <select name="express_id_<?php echo $batchcode; ?>" id="express_id_<?php echo $batchcode; ?>" style="width:150px;height:30px;">
													<option value="0" >虚拟发货</option>
												   <?php 
												   $query3 = 'SELECT id,name,price FROM weixin_expresses where isvalid=true and customer_id='.$customer_id;
                                                   $result3 = mysql_query($query3) or die('Query failed57: ' . mysql_error()); 
												   while ($row3 = mysql_fetch_object($result3)) {
												      $e_id= $row3->id;
													  $e_name = $row3->name;
                                                   ?>
												     <option value="<?php echo $e_id; ?>" <?php if($express_id==$e_id){ ?>selected<?php } ?>><?php echo $e_name; ?></option>
												   <?php } ?>
												</select>
											 </div>
                                         </div>
										 <div class="orderdetail_two_l_t_b_item"  style="height:50px;"> 
										     <div class="orderdetail_two_l_t_b_item_l">
											      物流单号:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											    <input type="text" placeholder="虚拟发货可不用填" value="<?php echo $expressnum ?>" name="expressnum_<?php echo $batchcode; ?>" id="expressnum_<?php echo $batchcode; ?>" onkeyup="this.value=this.value.replace(/\D/g,'')"  onafterpaste="this.value=this.value.replace(/\D/g,'')"/>	
												
												<?php if(!empty($expressnum) and $kuaidi100==0){?>
												<a href='http://www.kuaidi100.com/chaxun?com=<?php echo $e_name;?>&nu=<?php echo $expressnum;?>'>(点击查看物流)</a> <?php }else if(!empty($expressnum) and $kuaidi100==1){ ?>
												<a onclick="KuaiDi100(<?php echo $batchcode; ?>)" >(点击查看物流)</a>
												<?php } ?>
											 </div>
                                         </div>
										 <div class="orderdetail_two_l_t_b_item"  style="height:75px;"> 
										     <div class="orderdetail_two_l_t_b_item_l">
											      物流备注:&nbsp;&nbsp;
											 </div>
											 <div class="orderdetail_two_l_t_b_item_r">
											    <textarea id="remarks_<?php echo $batchcode; ?>" name="remarks_<?php echo $batchcode; ?>" rows="2" cols="30" placeholder="写上要留言的信息"></textarea>
											 </div>
                                         </div>
										 
											<div class="orderdetail_two_l_t_b_item" style="height:40px;">
												<div class="orderdetail_two_l_t_b_item_img_l" onclick="sub_sendgood('<?php echo $batchcode; ?>');">确认发货</div>
												<div class="orderdetail_two_l_t_b_item_img_r" onclick="cancel_send('<?php echo $batchcode; ?>');">取消</div>
											</div>
										 
									</div>
								
								</div>
								<?php if($supply_id>0){ ?>
								<div class="orderdetail_two_r_con" style="margin-top:20px">
									<div class="orderdetail_two_l_t_t">留言信息 </div>
									<div class="orderdetail_two_l_t_b">				
										<div class="orderdetail_two_l_t_b_item_p" >
											<div class="orderdetail_two_l_t_b_item" style="width: 98%;height: 150px;border: 1px solid #DDD;margin: 5px auto;overflow-y: auto;">
											<?php 
												   $query_msg = 'SELECT message,createtime,type,sub_supplier_id FROM weixin_commonshop_supply_message where isvalid=true and batchcode='.$batchcode;
                                                   $result_msg = mysql_query($query_msg) or die('Query_msg failed57: ' . mysql_error()); 
												   while ($row_msg = mysql_fetch_object($result_msg)) {
												      $msg_message= $row_msg->message;
													  $msg_createtime = $row_msg->createtime;
													  $msg_type = $row_msg->type;
													  $sub_supplier_id = $row_msg->sub_supplier_id;
											?>											
											  <div style="width: 98%; margin: 5px auto 0px; border: 1px solid #ddd;">
												<div class="orderdetail_two_l_t_t" style="text-align:center">
												<?php
												if($msg_type==1){
													$msg_user = $supply_username;
												}else if ($msg_type==2){
												    $query_subname = 'SELECT username FROM weixin_commonshop_supply_users where id='.$sub_supplier_id;
													$result_subname = mysql_query($query_subname) or die("L2380 Query_subname error : ".mysql_error());
													$msg_user = $supply_username.":".mysql_result($result_subname,0,0);	
												}else{
													$msg_user = "我";
												}
												echo $msg_user."  留言于  ".$msg_createtime;
												?>
												</div>
												<div class=""><?php echo $msg_message; ?></div>
											  </div>	
										<?php
												   }
										?>											  
											</div>	
											<form method="post" action="order_talk_do.php?order_id=<?php echo $batchcode; ?>" id="form_order_talk" onsubmit="return checkForm(this)">
												<div class="orderdetail_two_l_t_b_item" style="width: 100%;height: 85px;">
													<textarea  rows="2" cols="30" name="order_talk" id="order_talk" placeholder="写上要发送给供应商的信息" style="width: 95%;margin-top:5px;"></textarea>
													<input type="hidden" name="supply_id" value="<?php echo $supply_id; ?>" />
												</div>
												<div class="orderdetail_two_l_t_b_item" style="height:40px;">
													<button type="submit" class="orderdetail_two_l_t_b_item_img_l2" >发送</button>
													<button type="button" class="orderdetail_two_l_t_b_item_img_r2" onclick="cancel_send('<?php echo $batchcode; ?>');">取消</button> 
												</div>
											</form>	
										</div>
										 <div style="clear:both;"></div>                            
									</div>						 		 
							   </div>						
							<?php } ?>
							</div>
						   </div>
						  </div>
						  </td>
						  </tr>
						  </table>
						  
						    
						 </td>
					   </tr>
					   
				
				
                
				  
				    
				  
			   <?php } ?>
			   <tr>
			      <td colspan=2 align=right>该页总金额:￥<?php echo $sum_totalprice; ?></td>
				  
				  <td style="color:red" colospan=8>总金额:￥<?php echo $all_totalprice; ?></td>
			   </tr>
			   <tr>
			      <td colspan=12>
				  <div class="tcdPageCode"></div>
				 </td>
			   </tr>
			</tbody>
			
		</table>
		<div class="blank20"></div>
		<div id="turn_page"></div>
	</div>	</div>
<div>
</div></div><div style="top: 101px; position: absolute; background-color: white; z-index: 2000; left: 398px; visibility: hidden; background-position: initial initial; background-repeat: initial initial;" class="om-calendar-list-wrapper om-widget om-clearfix om-widget-content multi-1"><div class="om-cal-box" id="om-cal-4381460996810347"><div class="om-cal-hd om-widget-header"><a href="javascript:void(0);" class="om-prev "><span class="om-icon om-icon-seek-prev">Prev</span></a><a href="javascript:void(0);" class="om-title">2014年1月</a><a href="javascript:void(0);" class="om-next "><span class="om-icon om-icon-seek-next">Next</span></a></div><div class="om-cal-bd"><div class="om-whd"><span>日</span><span>一</span><span>二</span><span>三</span><span>四</span><span>五</span><span>六</span></div><div class="om-dbd om-clearfix"><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);">1</a><a href="javascript:void(0);">2</a><a href="javascript:void(0);">3</a><a href="javascript:void(0);">4</a><a href="javascript:void(0);">5</a><a href="javascript:void(0);">6</a><a href="javascript:void(0);">7</a><a href="javascript:void(0);">8</a><a href="javascript:void(0);" class="om-state-highlight om-state-nobd">9</a><a href="javascript:void(0);" class="om-state-disabled">10</a><a href="javascript:void(0);" class="om-state-disabled">11</a><a href="javascript:void(0);" class="om-state-disabled">12</a><a href="javascript:void(0);" class="om-state-disabled">13</a><a href="javascript:void(0);" class="om-state-disabled">14</a><a href="javascript:void(0);" class="om-state-disabled">15</a><a href="javascript:void(0);" class="om-state-disabled">16</a><a href="javascript:void(0);" class="om-state-disabled">17</a><a href="javascript:void(0);" class="om-state-disabled">18</a><a href="javascript:void(0);" class="om-state-disabled">19</a><a href="javascript:void(0);" class="om-state-disabled">20</a><a href="javascript:void(0);" class="om-state-disabled">21</a><a href="javascript:void(0);" class="om-state-disabled">22</a><a href="javascript:void(0);" class="om-state-disabled">23</a><a href="javascript:void(0);" class="om-state-disabled">24</a><a href="javascript:void(0);" class="om-state-disabled">25</a><a href="javascript:void(0);" class="om-state-disabled">26</a><a href="javascript:void(0);" class="om-state-disabled">27</a><a href="javascript:void(0);" class="om-state-disabled">28</a><a href="javascript:void(0);" class="om-state-disabled">29</a><a href="javascript:void(0);" class="om-state-disabled">30</a><a href="javascript:void(0);" class="om-state-disabled">31</a><a href="javascript:void(0);" class="om-null">0</a></div></div><div class="om-setime om-state-default hidden"></div><div class="om-cal-ft"><div class="om-cal-time om-state-default">时间：<span class="h">0</span>:<span class="m">0</span>:<span class="s">0</span><div class="cta"><button class="u om-icon om-icon-triangle-1-n"></button><button class="d om-icon om-icon-triangle-1-s"></button></div></div><button class="ct-ok om-state-default">确定</button></div><div class="om-selectime om-state-default hidden"></div></div></div><div style="top: 101px; position: absolute; background-color: white; z-index: 2000; left: 564px; visibility: hidden; background-position: initial initial; background-repeat: initial initial;" class="om-calendar-list-wrapper om-widget om-clearfix om-widget-content multi-1"><div class="om-cal-box" id="om-cal-8113757355604321"><div class="om-cal-hd om-widget-header"><a href="javascript:void(0);" class="om-prev "><span class="om-icon om-icon-seek-prev">Prev</span></a><a href="javascript:void(0);" class="om-title">2014年1月</a><a href="javascript:void(0);" class="om-next "><span class="om-icon om-icon-seek-next">Next</span></a></div><div class="om-cal-bd"><div class="om-whd"><span>日</span><span>一</span><span>二</span><span>三</span><span>四</span><span>五</span><span>六</span></div><div class="om-dbd om-clearfix"><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);">1</a><a href="javascript:void(0);">2</a><a href="javascript:void(0);">3</a><a href="javascript:void(0);">4</a><a href="javascript:void(0);">5</a><a href="javascript:void(0);">6</a><a href="javascript:void(0);">7</a><a href="javascript:void(0);">8</a><a href="javascript:void(0);" class="om-state-highlight om-state-nobd">9</a><a href="javascript:void(0);" class="om-state-disabled">10</a><a href="javascript:void(0);" class="om-state-disabled">11</a><a href="javascript:void(0);" class="om-state-disabled">12</a><a href="javascript:void(0);" class="om-state-disabled">13</a><a href="javascript:void(0);" class="om-state-disabled">14</a><a href="javascript:void(0);" class="om-state-disabled">15</a><a href="javascript:void(0);" class="om-state-disabled">16</a><a href="javascript:void(0);" class="om-state-disabled">17</a><a href="javascript:void(0);" class="om-state-disabled">18</a><a href="javascript:void(0);" class="om-state-disabled">19</a><a href="javascript:void(0);" class="om-state-disabled">20</a><a href="javascript:void(0);" class="om-state-disabled">21</a><a href="javascript:void(0);" class="om-state-disabled">22</a><a href="javascript:void(0);" class="om-state-disabled">23</a><a href="javascript:void(0);" class="om-state-disabled">24</a><a href="javascript:void(0);" class="om-state-disabled">25</a><a href="javascript:void(0);" class="om-state-disabled">26</a><a href="javascript:void(0);" class="om-state-disabled">27</a><a href="javascript:void(0);" class="om-state-disabled">28</a><a href="javascript:void(0);" class="om-state-disabled">29</a><a href="javascript:void(0);" class="om-state-disabled">30</a><a href="javascript:void(0);" class="om-state-disabled">31</a><a href="javascript:void(0);" class="om-null">0</a></div></div><div class="om-setime om-state-default hidden"></div><div class="om-cal-ft"><div class="om-cal-time om-state-default">时间：<span class="h">0</span>:<span class="m">0</span>:<span class="s">0</span><div class="cta"><button class="u om-icon om-icon-triangle-1-n"></button><button class="d om-icon om-icon-triangle-1-s"></button></div></div><button class="ct-ok om-state-default">确定</button></div><div class="om-selectime om-state-default hidden"></div></div></div>

<link type="text/css" rel="stylesheet" rev="stylesheet" href="../css/fenye/fenye.css" media="all">
<script src="../js/fenye/jquery.page.js"></script>
<script>
	var pagenum = <?php echo $pagenum ?>;
	var rcount_q2 = <?php echo $rcount_q2 ?>;
	var end = <?php echo $end ?>;
	var count =Math.ceil(rcount_q2/end);//总页数
  	//pageCount：总页数
	//current：当前页
	$(".tcdPageCode").createPage({
        pageCount:count,
        current:pagenum,
        backFn:function(p){
		 var search_order_ascription = document.getElementById("search_order_ascription").value;
		 var search_status = document.getElementById("search_status").value;
		 var search_sendstatus = document.getElementById("search_sendstatus").value;
		 var search_paystyle = document.getElementById("search_paystyle").value;
		 var pagesize = document.getElementById("pagesize").value;
		 var begintime = document.getElementById("begintime").value;
		 var endtime = document.getElementById("endtime").value;
		 var pay_begintime = document.getElementById("pay_begintime").value;
		 var pay_endtime = document.getElementById("pay_endtime").value;
		 var search_name_type = document.getElementById("search_name_type").value;
		 var user_ids="<?php echo $user_ids; ?>";
		 var search_name = document.getElementById("search_name").value;
		 var search_batchcode = document.getElementById("search_batchcode").value;
		 document.location= "order.php?pagenum="+p+"&search_status="+search_status+"&search_order_ascription="+search_order_ascription+"&begintime="+begintime+"&endtime="+endtime+"&pay_begintime="+pay_begintime+"&pay_endtime="+pay_endtime+"&pagesize="+pagesize+"&search_name="+search_name+"&search_batchcode="+search_batchcode+"&search_paystyle="+search_paystyle+"&search_name_type="+search_name_type+"&search_sendstatus="+search_sendstatus+"&user_ids="+user_ids;
	   }
    });
function change_ascription(ascription){
	var search_status = document.getElementById("search_status").value;
	var search_sendstatus = document.getElementById("search_sendstatus").value;
	var search_paystyle = document.getElementById("search_paystyle").value;
	var pagesize = document.getElementById("pagesize").value;
	var begintime = document.getElementById("begintime").value;
	var endtime = document.getElementById("endtime").value;
	var pay_begintime = document.getElementById("pay_begintime").value;
	var pay_endtime = document.getElementById("pay_endtime").value;
	var search_name_type = document.getElementById("search_name_type").value;
	var search_name = document.getElementById("search_name").value;
	var search_batchcode = document.getElementById("search_batchcode").value;
	document.location= "order.php?pagenum=1&search_status="+search_status+"&begintime="+begintime+"&endtime="+endtime+"&pay_begintime="+pay_begintime+"&pay_endtime="+pay_endtime+"&pagesize="+pagesize+"&search_name="+search_name+"&search_batchcode="+search_batchcode+"&search_paystyle="+search_paystyle+"&search_sendstatus="+search_sendstatus+"&search_name_type="+search_name_type+"&search_order_ascription="+ascription+"&issearch=1";	
}

	function searchForm(){
		var search_order_ascription = document.getElementById("search_order_ascription").value;
		var search_status = document.getElementById("search_status").value;
		var search_sendstatus = document.getElementById("search_sendstatus").value;
		var search_paystyle = document.getElementById("search_paystyle").value;
		var pagesize = document.getElementById("pagesize").value;
		var begintime = document.getElementById("begintime").value;
		var endtime = document.getElementById("endtime").value;
		var pay_begintime = document.getElementById("pay_begintime").value;
		var pay_endtime = document.getElementById("pay_endtime").value;
		var search_name_type = document.getElementById("search_name_type").value;

		var search_name = document.getElementById("search_name").value;
		var search_batchcode = document.getElementById("search_batchcode").value;
		document.location= "order.php?pagenum=1&search_status="+search_status+"&search_order_ascription="+search_order_ascription+"&begintime="+begintime+"&endtime="+endtime+"&pay_begintime="+pay_begintime+"&pay_endtime="+pay_endtime+"&pagesize="+pagesize+"&search_name="+search_name+"&search_batchcode="+search_batchcode+"&search_paystyle="+search_paystyle+"&search_sendstatus="+search_sendstatus+"&search_name_type="+search_name_type+"&issearch=1";		
	}
 
	function exportRecord(){
		var search_status = document.getElementById("search_status").value;
		var search_sendstatus = document.getElementById("search_sendstatus").value;
		var begintime = document.getElementById("begintime").value;
		var endtime = document.getElementById("endtime").value;
		var pagesize = document.getElementById("pagesize").value;
		var search_order_ascription = document.getElementById("search_order_ascription").value;
		if(begintime==""){
		 begintime = 0;
		}
		if(endtime==""){
		 endtime = 0;
		}
		var user_ids = "<?php echo $user_ids; ?>";
		var url='/weixin/plat/app/index.php/Excel/commonshop_excel/customer_id/<?php echo $customer_id; ?>/begintime/'+begintime+'/endtime/'+endtime+'/status/'+search_status+'/search_sendstatus/'+search_sendstatus+'/search_order_ascription/'+search_order_ascription+'/user_ids/'+user_ids+'/';
		console.log(url);
		goExcel(url,1,'http://<?php echo $http_host;?>/weixinpl/');
	 }
	 
    function exportFeiDouRecord(){
		var search_status = document.getElementById("search_status").value;
		var search_sendstatus = document.getElementById("search_sendstatus").value;
		var begintime = document.getElementById("begintime").value;
		var endtime = document.getElementById("endtime").value;
		var pagesize = document.getElementById("pagesize").value;
		var search_order_ascription = document.getElementById("search_order_ascription").value;
		if(begintime==""){
		 begintime = 0;
		}
		if(endtime==""){
		 endtime = 0;
		}
		var url='/weixin/plat/app/index.php/Excel/commonshop_feidou_excel/customer_id/<?php echo $customer_id; ?>/shopname/<?php echo $shopname;?>/begintime/'+begintime+'/endtime/'+endtime+'/status/'+search_status+'/search_order_ascription/'+search_order_ascription+'/search_sendstatus/'+search_sendstatus+'/';
		console.log(url);
		goExcel(url,1,'http://<?php echo $http_host;?>/weixinpl/');
  }
  
function exportCustoms(){
	var search_status = document.getElementById("search_status").value;
	var search_sendstatus = document.getElementById("search_sendstatus").value;
	var begintime = document.getElementById("begintime").value;
	var endtime = document.getElementById("endtime").value;
	var pagesize = document.getElementById("pagesize").value;
	var search_order_ascription = document.getElementById("search_order_ascription").value;
	if(begintime==""){
		begintime = 0;
	}
	if(endtime==""){
		endtime = 0;
	}
	var user_ids = "<?php echo $user_ids; ?>";
	var url='/weixin/plat/app/index.php/Excel/commonshop_customs_excel/customer_id/<?php echo $customer_id; ?>/begintime/'+begintime+'/endtime/'+endtime+'/status/'+search_status+'/search_sendstatus/'+search_sendstatus+'/search_order_ascription/'+search_order_ascription+'/user_ids/'+user_ids+'/';
	console.log(url);
	goExcel(url,1,'http://<?php echo $http_host;?>/weixinpl/');
}
  
	function cancel_send(batchcode){
		$("#tr_"+batchcode).slideToggle();
		$("#batchcode_"+batchcode).attr("slide","0"); 
	}
  //催单
	function callpay(id,openid,userid){
		//alert("催单成功！"+id+" "+openid);
		url='callpaymsg.php?callback=jsonpCallback_callpaymsg&customerid='+id+'&fromuser='+openid+'&userid='+userid; 
		console.log(url);
		$.jsonp({
		url:url,
		callbackParameter: 'jsonpCallback_callpaymsg'
		});
	}
  
	function jsonpCallback_callpaymsg(results){
		alert("发送成功！");
	}
	
  function show_send(batchcode,type){
	 var slide = $("#batchcode_"+batchcode).attr("slide");
	 if(slide==0){
		  $("#tr_"+batchcode).slideToggle(1000);
		  $("#batchcode_"+batchcode).attr("slide","1"); 
	 }
	 if(type==1){		//type 1:点击订单查看 0:发货或者修改发货地址
		 $('#express_id_'+batchcode).attr("disabled",true);
		 $('#expressnum_'+batchcode).attr("readonly","readonly");
		 $('#remarks_'+batchcode).attr("readonly","readonly"); 
		 $('.orderdetail_two_l_t_b_item_img_l').css("display","none");
		 $('.orderdetail_two_l_t_b_item_img_r').css("display","none");
	 }else{ 
		 $('#express_id_'+batchcode).attr("disabled",false);
		 $('#expressnum_'+batchcode).removeAttr("readonly");
		 $('#remarks_'+batchcode).removeAttr("readonly"); 
		 $('.orderdetail_two_l_t_b_item_img_l').css("display","block");
		 $('.orderdetail_two_l_t_b_item_img_r').css("display","block");
	 }
  }
  
	function cancel_changeprice(batchcode){
		$("#tr_changeprice_"+batchcode).slideToggle();
	}
  
	function show_changeprice(batchcode,order_count){
		if(order_count>1){
			alert("单个商品才能修改价格！");
			return;
		}
	$("#tr_changeprice_"+batchcode).slideToggle(1000);
	}
  
  
	function cancel_changeaddress(batchcode){
		$("#tr_changeaddress_"+batchcode).slideToggle();
	}
  
	function show_changeaddress(batchcode){
		$("#tr_changeaddress_"+batchcode).slideToggle(1000);
	}
  
  
	var batchcode ="";
	function sub_sendgood(bc){
		batchcode = bc;
		$("#batchcode_"+batchcode).attr("slide","0");
		var expressnum = document.getElementById("expressnum_"+bc).value;
		var express_id = document.getElementById("express_id_"+bc).value;
		if(expressnum=="" && express_id!=0){
			alert("请输入快递单号");
			return;
		}
		var express_id = document.getElementById("express_id_"+bc).value;
		var expressnum = document.getElementById("expressnum_"+bc).value;
		var remarks = document.getElementById("remarks_"+bc).value;
		url='save_sendstatus.php?callback=jsonpCallback_savesendstatus&batchcode='+batchcode+"&express_id="+express_id+"&expressnum="+expressnum+"&remarks="+remarks; 
		console.log(url);
		$.jsonp({
			url:url,
			callbackParameter: 'jsonpCallback_savesendstatus'
		});
	  }

	function jsonpCallback_savesendstatus(results){
		document.getElementById("tr_"+batchcode).style.display="none";
		document.getElementById("p_changeaddress_"+batchcode).style.display="none";
		document.getElementById("p_sendstatus_back_"+batchcode).style.display="block"; 
		document.getElementById("p_sendstatus_"+batchcode).style.display="none"; 
		var express_id = document.getElementById("express_id_"+batchcode).value;
		if(express_id==0){
		  document.getElementById("confirm_"+batchcode).style.display="block";
		}
		document.getElementById("span_sendstatus_"+batchcode).innerHTML="<span class='bg_yes'>已发货</span>";
		document.getElementById("sendtime_"+batchcode).innerHTML="<br><br>发货时间:"+results[0].confirmtime;
	}
	var order_totalprice = 0;
	function sub_changeprice(bc){
     batchcode = bc;	 
	 var totalprice = document.getElementById("nowprice_"+bc).value;
	 if(totalprice<=0){
		 alert("价格必须大于0！");
		 return;
	 }
	 order_totalprice  = totalprice;
     url='save_changeprice.php?callback=jsonpCallback_changeprice&batchcode='+batchcode+"&totalprice="+totalprice;
	 $.jsonp({
	    url:url,
		callbackParameter: 'jsonpCallback_changeprice'
    });
  }
  
	function jsonpCallback_changeprice(results){
		document.getElementById("tr_changeprice_"+batchcode).style.display="none";
		document.getElementById("order_totalprice_"+batchcode).innerHTML = order_totalprice;
	}
  
  
	function sub_changeaddress(bc,address_id){

		batchcode = bc;

		var order_username = document.getElementById("order_username_"+bc).value;
		var order_userphone = document.getElementById("order_userphone_"+bc).value;
		var order_address = document.getElementById("order_address_"+bc).value;
		var location_p = document.getElementById("location_p_"+bc).value;
		var location_c = document.getElementById("location_c_"+bc).value;
		var location_a = document.getElementById("location_a_"+bc).value; 

		url='save_changeaddress.php?callback=jsonpCallback_changeaddress&batchcode='+batchcode+"&address_id="+address_id+"&order_address="+order_address+"&order_username="+order_username+"&order_userphone="+order_userphone+"&location_p="+location_p+"&location_c="+location_c+"&location_a="+location_a;

		$.jsonp({
			url:url,
			callbackParameter: 'jsonpCallback_changeaddress'
		});
	}
  
	function jsonpCallback_changeaddress(results){
		document.getElementById("tr_changeaddress_"+batchcode).style.display="none";
	}
  
	function confirmPay(url){
		if(confirm('您确认要确认支付吗？确认后，表示订单已经支付，并且不能取消！')){
			goUrl(url);
		}
	}  
  
	function checkForm(obj){
		if(obj.order_talk.value==""){
		alert("警告","留言内容不能为空");
		return false;
		}	
	}
	
</script>

<?php 

mysql_close($link);
?>
<?php if($kuaidi100){  ?>
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../common/kuaidi100/css/ui-dialog.css" media="all">
<script type="text/javascript" src="../common/kuaidi100/js/dialog-min.js" charset="utf-8"></script>	
<script type="text/javascript" src="../common/kuaidi100/js/dialog-plus-min.js" charset="utf-8"></script>	
<script>
	function KuaiDi100(obj){
		obj2 = $("#expressnum_"+obj).val();
		console.log(obj2);
	$.ajax({
		url: "../../../weixin/plat/app/index.php/KuaDi100/order/",
		type:"POST",
		//async: false,
		data:{'batchcode_id':obj,'expressnum':obj2},
		dataType:"json",
		success: function(res){
			if(res.status===0){					
				var d = dialog({
					title: '消息',
					height: 380,
					width: 600,
					url: res.url,
					okValue: '确 定',
					ok: function () {
					}
				});
				d.show();
			}else{
				alert("参数不正确");
			}			
		},	
		error:function(){
			alert("网络错误请检查网络");
		}						
	});
	}
</script>
<?php } ?>	
</body></html>