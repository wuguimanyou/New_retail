<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../back_init.php');
$link = mysql_connect("localhost",DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require('../proxy_info.php');

mysql_query("SET NAMES UTF8");

$op="";
if(!empty($_GET["op"])){
   $op = $_GET["op"];
   $id = $_GET["id"];
   if($op=="status"){
	  $status = $_GET["status"];
	  $user_id = $_GET["user_id"];
	  if($status==1){
	      $parent_id = $_GET["parent_id"];
	      $sql="update weixin_qrs set status=".$status.",reason='' where id=".$id;
		  mysql_query($sql);
		  
		  $sql="update promoters set status=1 where user_id=".$user_id;
		  mysql_query($sql);
	  }else if($status==0){
	      $sql="update weixin_qrs set status=".$status." where id=".$id;
	      mysql_query($sql);
		  $sql="update promoters set status=0 where user_id=".$user_id;
	      mysql_query($sql);
	  }else if($status==-1){
	      $reason = $_GET["reason"];
		  $parent_id = $_GET["parent_id"];
	      $sql="update weixin_qrs set status=".$status.",reason='".$reason."' where id=".$id;
	      mysql_query($sql);
		  
		  $sql="update promoters set status=-1 where user_id=".$user_id;
		  mysql_query($sql);
	  }
	  
   }else if($op=="del"){
      $sql="update weixin_qrs set isvalid=false where id=".$id;
	  mysql_query($sql);
	  
	  $user_id = $_GET["user_id"];
	  $sql="update promoters set isvalid=false where  user_id=".$user_id." and customer_id=".$customer_id;
	  mysql_query($sql);
	  
	  //所有下线都取消上级  
	  $sql="update promoters set parent_id=-1 where isvalid=true and  parent_id=".$user_id." and customer_id=".$customer_id;
	  mysql_query($sql);
	  
	  $sql="update weixin_users set parent_id=-1 where isvalid=true and  parent_id=".$user_id." and customer_id=".$customer_id;
	  mysql_query($sql);
	  
	  //取消扫描关系
	  $qr_info_id = $_GET["qr_info_id"];
	  $sql="update weixin_qr_infos set isvalid=false where id=".$qr_info_id;
	  mysql_query($sql);
	  //清除推广记录
	  $sql="update weixin_qr_scans set isvalid=false where scene_id=".$user_id;
	  mysql_query($sql);
	  //去掉 用户表里面的上下级关系
	  $sql="update weixin_users set parent_id=-1 where id=".$user_id;
	  mysql_query($sql);
	  
   }else if($op=="cancel_level"){
      $user_id = $_GET["user_id"];
	  $parent_id = $_GET["parent_id"];
	  $sql="update promoters set parent_id=-1 where  user_id=".$user_id." and customer_id=".$customer_id;
	  mysql_query($sql);
	  
	  $sql="update weixin_qr_scans set isvalid=false where  user_id=".$user_id." and customer_id=".$customer_id." and scene_id=".$parent_id;
	  mysql_query($sql);
	  
	  $sql="update weixin_users set parent_id=-1 where id=".$user_id;
	  mysql_query($sql);
   }
}
$exp_user_id=-1;

if(!empty($_GET["exp_user_id"])){
    $exp_user_id = $_GET["exp_user_id"];
}
$search_status=-1;
if(!empty($_GET["search_status"])){
    $search_status = $_GET["search_status"];
}
if(!empty($_POST["search_status"])){
    $search_status = $_POST["search_status"];
}

$search_name="";
if(!empty($_GET["search_name"])){
    $search_status = $_GET["search_name"];
}
if(!empty($_POST["search_name"])){
    $search_name = $_POST["search_name"];
}

$search_user_id="";
if(!empty($_GET["search_user_id"])){
    $search_user_id = $_GET["search_user_id"];
}
if(!empty($_POST["search_user_id"])){
    $search_user_id = $_POST["search_user_id"];
}


$search_phone="";
if(!empty($_GET["search_phone"])){
    $search_phone = $_GET["search_phone"];
}
if(!empty($_POST["search_phone"])){
    $search_phone = $_POST["search_phone"];
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

$query="select count(1) as new_order_count from weixin_commonshop_orders where isvalid=true and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $new_order_count = $row->new_order_count;
   break;
}

$query="select sum(totalprice) as today_totalprice from weixin_commonshop_orders where paystatus=1 and isvalid=true and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $today_totalprice = $row->today_totalprice;
   break;
}
$today_totalprice = round($today_totalprice,2);

$query="select count(1) as new_customer_count from weixin_commonshop_customers where isvalid=true and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $new_customer_count = $row->new_customer_count;
   break;
}

$query="select count(1) as new_qr_count from promoters where status=1 and isvalid=true and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $new_qr_count = $row->new_qr_count;
   break;
}

$op="";
if(!empty($_GET["op"])){
   $op = $_GET["op"];
   if($op=="resetpwd"){
       $keyid = $_GET["keyid"];
	   $user_id = $_GET["user_id"];
	   $sql="update promoters set pwd='888888' where user_id=".$user_id;
	   mysql_query($sql);
	   
   }
}

 $exp_name="推广员";
 $shop_card_id=-1;
 $query="select exp_name,shop_card_id from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
 $result = mysql_query($query) or die('Query failed: ' . mysql_error());  
 while ($row = mysql_fetch_object($result)) {
    
     $exp_name = $row->exp_name;
     $shop_card_id = $row->shop_card_id;
	
	 break;
 }
?>
<!DOCTYPE html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<title></title>
<link href="css/global.css" rel="stylesheet" type="text/css">
<link href="css/main.css" rel="stylesheet" type="text/css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../css/icon.css" media="all">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../css/inside.css" media="all">
<script type="text/javascript" src="../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="js/global.js"></script>
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
		</div>
<div id="iframe_page">
	<div class="iframe_content">
			<link href="css/shop.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/shop.js"></script>
	<div class="r_nav">
		<ul>
			<li class=""><a href="base.php?customer_id=<?php echo $customer_id; ?>">基本设置</a></li>
			<li class=""><a href="fengge.php?customer_id=<?php echo $customer_id; ?>">风格设置</a></li>
			<li class=""><a href="defaultset.php?customer_id=<?php echo $customer_id; ?>">首页设置</a></li>
			<li class=""><a href="product.php?customer_id=<?php echo $customer_id; ?>">产品管理</a></li>
			<li class=""><a href="order.php?customer_id=<?php echo $customer_id; ?>&status=-1">订单管理</a></li>
			<li class="cur"><a href="qrsell.php?customer_id=<?php echo $customer_id; ?>">推广员</a></li>
			<li class=""><a href="customers.php?customer_id=<?php echo $customer_id; ?>">顾客</a></li>
		</ul>
	</div>
<link href="css/operamasks-ui.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/operamasks-ui.min.js"></script>
<script type="text/javascript" src="../js/tis.js"></script>
<script language="javascript">

$(document).ready(shop_obj.orders_init);
</script>
<div id="orders" class="r_con_wrap">
		<form class="search" id="search_form" method="post" action="qrsell.php?customer_id=<?php echo $customer_id; ?>&issearch=1">
			推广员状态：<select name="search_status" id="search_status"  style="width:100px;" >
				<option value="-1">--请选择--</option>
				<option value="2" <?php if($search_status==2){ ?>selected <?php } ?>>待审核</option>
				<option value="1" <?php if($search_status==1){ ?>selected <?php } ?>>已确认</option>
				<option value="-2" <?php if($search_status==-2){ ?>selected <?php } ?>>已驳回/暂停</option>
				</select>
				&nbsp;推广员编号:<input type=text name="search_user_id" id="search_user_id" value="<?php echo $search_user_id; ?>" style="width:80px;" />
				&nbsp;姓名:<input type=text name="search_name" id="search_name" value="<?php echo $search_name; ?>" style="width:80px;" />
				&nbsp;电话:<input type=text name="search_phone" id="search_phone" value="<?php echo $search_phone; ?>"  style="width:80px;" />
			
			<input type="submit" class="search_btn" value="搜 索">
			<input type="button" class="search_btn" value="导出记录+" onClick="exportRecord();" class="button" style="cursor:hand">
			
		</form>	 
		<table border="0" cellpadding="5" cellspacing="0" class="r_con_table" id="order_list">
			<thead>
				<tr>
					<td width="8%" nowrap="nowrap">推广员编号</td>
					<td width="8%" nowrap="nowrap">姓名</td>
					<td width="12%" nowrap="nowrap">推广二维码</td>
					<td width="8%" nowrap="nowrap">直接推广人数</td>
					<td width="8%" nowrap="nowrap">直接推广金额</td>
					<td width="8%" nowrap="nowrap">总获奖积分</td>				
					<td width="8%" nowrap="nowrap">总获奖金额</td>				
					<td width="8%" nowrap="nowrap">状态</td>
					<td width="8%" nowrap="nowrap">上线</td>
					<td width="8%" nowrap="nowrap">总消费金额</td>
					<td width="8%" nowrap="nowrap">申请时间</td>
					<td width="8%" nowrap="nowrap">操作</td>
				</tr>
			</thead>
			<tbody>
			   <?php 
			   
			   $pagenum = 1;

				if(!empty($_GET["pagenum"])){
				   $pagenum = $_GET["pagenum"];
				}

				$start = ($pagenum-1) * 20;
				$end = 20;
				
			     $query="select distinct(wq.id) as id,qr_info_id,wq.reason as reason,wu.id as user_id,wu.name as name,wu.weixin_name as weixin_name,wu.phone as phone,wu.parent_id as parent_id ,imgurl_qr,wq.status,reward_score,reward_money,wq.createtime from weixin_qrs wq inner join weixin_qr_infos wqi inner join weixin_users wu inner join promoters promoter  on wq.qr_info_id=wqi.id and promoter.user_id=wu.id and promoter.isvalid=true and wq.isvalid=true and wqi.isvalid=true and  wqi.foreign_id = wu.id and wu.isvalid=true and  wq.isvalid=true and wq.type=1 and wq.customer_id=".$customer_id;
				 if($exp_user_id>0){
				     $query = $query." and wqi.foreign_id=".$exp_user_id;
				 }
				 switch($search_status){
				    case 2:
					   $query = $query." and wq.status=0";
					   break;
					case 1:
					   $query = $query." and wq.status=1";
					   break;
					case -2:
					   $query = $query." and wq.status=-1";
					   break;
					
				     
				 }
				 
				 if(!empty($search_name)){
				   
					$query = $query." and (wu.name like '%".$search_name."%' or wu.weixin_name like '%".$search_name."%')";
				 }
				 
				 if(!empty($search_phone)){
				   
					$query = $query." and wu.phone like '%".$search_phone."'";
				 }
				 
				 if(!empty($search_user_id)){
				   
					$query = $query." and wu.id like '%".$search_user_id."%'";
				 }
				 
				 
				 $query = $query." order by wq.id desc"." limit ".$start.",".$end;
				 $result = mysql_query($query) or die('Query failed: ' . mysql_error());
				 $rcount_q = mysql_num_rows($result);
	             while ($row = mysql_fetch_object($result)) {
				 
				    $qr_info_id = $row->qr_info_id;
					$user_id =$row->user_id;
					/*$query2="select foreign_id from weixin_qr_infos where isvalid=true and id=".$qr_info_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
	                while ($row2 = mysql_fetch_object($result2)) {
					    $user_id = $row2->foreign_id;
					}*/
					$id = $row->id;
					$reward_score = $row->reward_score;
					$reward_money = $row->reward_money;
					
					$reward_money = round($reward_money, 2);
					$reason = $row->reason;
					
					$username=$row->name;
					$weixin_name = $row->weixin_name;
					$username = $username."(".$weixin_name.")";
					$userphone = $row->phone;
					//$parent_id = $row->parent_id;
					
					$imgurl_qr=$row->imgurl_qr;
					
					$rcount=0;
					$query2 = "select count(1) as rcount from weixin_qr_scans wqs inner join weixin_users wu on wu.id = wqs.user_id  and wqs.isvalid=true and wu.isvalid=true and wqs.customer_id=".$customer_id." and scene_id=".$user_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					while ($row2 = mysql_fetch_object($result2)) {
					   $rcount = $row2->rcount;
					}
					$sum_totalprice=0;
					$query2="select sum(totalprice) as sum_totalprice from weixin_commonshop_orders where isvalid=true and status =1 and paystatus=1 and exp_user_id>0 and   exp_user_id=".$user_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					//echo $query2;
					while ($row2 = mysql_fetch_object($result2)) {
					   $sum_totalprice = $row2->sum_totalprice;
					   break;
					}
					if(empty($sum_totalprice)){
					   $sum_totalprice = 0;
					}
					
				    $sum_totalprice = round($sum_totalprice, 2);
						
					$status = $row->status;
					$statusstr="待审核";
					switch($status){
					   case 1:
					   
					     $statusstr="已确认";
						 break;
					   case -1:
					     $statusstr="已驳回/暂停";
						 break;
					}
					$parent_name = "";
					$query2="select parent_id from promoters where  status=1 and isvalid=true and user_id=".$user_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					$parent_id = -1;
					while ($row2 = mysql_fetch_object($result2)) {
					    $parent_id = $row2->parent_id;
						break;
					}
					//推广员数量
					$promoter_count=0;
					$query2="select count(1) as promoter_count from promoters where status=1 and isvalid=true and parent_id=".$user_id." and customer_id=".$customer_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					while ($row2 = mysql_fetch_object($result2)) {    
					    $promoter_count = $row2->promoter_count;
						break;
					}
					if($parent_id>0 and $parent_id!=$user_id){
					   
						$query2="select id from promoters where  status=1 and isvalid=true and user_id=".$parent_id;
						$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
						$promoter_id = -1;
						while ($row2 = mysql_fetch_object($result2)) {    
						    $promoter_id = $row2->id;
							break;
						}
                       						
						if($promoter_id>0){
							$query2= "select name,phone,parent_id,weixin_name from weixin_users where isvalid=true and id=".$parent_id; 
							$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
							while ($row2 = mysql_fetch_object($result2)) {
								$parent_name=$row2->name;
								$weixin_name = $row2->weixin_name;
								$parent_name = $parent_name."(".$weixin_name.")";
								break;
							}
						}else{
						    $parent_id = -1;
						}
					}
					//查找账户和支付宝
					
					$query2="select account,account_type,bank_open,bank_name from weixin_card_members where isvalid=true and user_id=".$user_id." and card_id=".$shop_card_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					$account = "";
					$account_type="";
					$bank_open="";
					$bank_name="";
					while ($row2 = mysql_fetch_object($result2)) {
					    $account= $row2->account;
						$account_type =$row2->account_type;
						$bank_open = $row2->bank_open;
						$bank_name = $row2->bank_name;
					}
					$account_type_str="";
					switch($account_type){
					    case 1:
						   $account_type_str="支付宝";
						   break;
					    case 2:
						   $account_type_str="财付通";
						   break;
						case 3:
						   $account_type_str="银行账户";
						   break;
					}
					
					//显示该推广员已经购买的商品总金额(已经付款的)
					$query2="select sum(totalprice) as s_totalprice from weixin_commonshop_orders where isvalid=true and paystatus=1 and  user_id=".$user_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					$s_totalprice=0;;
					while ($row2 = mysql_fetch_object($result2)) {
					    $s_totalprice = $row2->s_totalprice;
					}
					
					$s_totalprice = round($s_totalprice,2);
					
					$query2="select title,online_qq from weixin_commonshop_owners where isvalid=true and user_id=".$user_id;
					$mystore_title="";
					$mystore_qq="";
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					
					while ($row2 = mysql_fetch_object($result2)) {
					    $mystore_title=$row2->title;
						$mystore_qq = $row2->online_qq;
						break;
					}
					
					$createtime = $row->createtime;
					
					
			   ?>
                <tr>
				   <td><?php echo $user_id; ?></td>
				   <td style="text-align:left;"><?php echo $username; ?><br/>
				       <?php echo $userphone; ?><br/>
					   收款类型:<?php echo $account_type_str; ?><br/>
					   收款账户:<?php echo $account; ?>
					   <?php if($account_type==3){ ?>
					   <br/>开户银行：<?php echo $bank_open; ?>
					   <br/>开户姓名：<?php echo $bank_name; ?>
					   <?php } ?>
					   <?php if(!empty($mystore_title)){ ?>
					     <br/>微店名称:<?php echo $mystore_title; ?><br/>
						 在线QQ:<?php echo $mystore_qq; ?> 
					   <?php } ?>
				   </td>
				   
				   <td><a href="<?php echo $imgurl_qr; ?>" target="_blank"><img src="<?php echo $imgurl_qr; ?>" style="width:40px;height:40px;" /></a></td>
				   <td>
				   会员数:&nbsp;<a href="qrsell_detail.php?customer_id=<?php echo $customer_id; ?>&scene_id=<?php echo $user_id; ?>&rcount=<?php echo $rcount; ?>"><?php echo $rcount; ?></a><br/>
				   推广员数:&nbsp;<a href="qrsell_detail.php?customer_id=<?php echo $customer_id; ?>&scene_id=<?php echo $user_id; ?>&rcount=<?php echo $rcount; ?>"><?php echo $promoter_count; ?></a>
				   </td>
 				   <td><a href="qrsell_money.php?customer_id=<?php echo $customer_id; ?>&scene_id=<?php echo $user_id; ?>&sum_totalprice=<?php echo $sum_totalprice; ?>"><?php echo $sum_totalprice; ?>元</a></td>
				   <td>
				     <a href="qrsell_rewardmoney.php?customer_id=<?php echo $customer_id; ?>&scene_id=<?php echo $user_id; ?>&type=1&sum_totalscore=<?php echo $reward_score; ?>"><?php echo $reward_score; ?></a>
				   </td>
				   <td>
				     <a href="qrsell_rewardmoney.php?customer_id=<?php echo $customer_id; ?>&scene_id=<?php echo $user_id; ?>&type=2&sum_totalprice=<?php echo $reward_money; ?>"><?php echo $reward_money; ?></a>
				   </td>
				   <td>
				     <?php echo $statusstr; ?><br/>
					 <?php if(!empty($reason)){ ?>
					 (<span style="font-size:12px;"><?php echo $reason; ?></span>)
					 <?php } ?>
				   </td>
				   <td>
				     <a href="qrsell.php?exp_user_id=<?php echo $parent_id; ?>&customer_id=<?php echo $customer_id; ?>"><?php echo $parent_name; ?></a>
				   </td>
				   <td><a href="customers.php?search_user_id=<?php echo $user_id; ?>"><?php echo $s_totalprice; ?></a></td>
				   <td><?php echo $createtime; ?></td>
				   <td>
				     <a href="add_qrsell_account.php?customer_id=<?php echo $customer_id; ?>&user_id=<?php echo $user_id; ?>&parent_id=<?php echo $parent_id; ?>&status=<?php echo $status; ?>&pagenum=<?php echo $pagenum; ?>"><img src="images/mod.gif" align="absmiddle" alt="编辑推广员" title="编辑推广员"></a> 
					 <a href="qrsell.php?customer_id=<?php echo $customer_id; ?>&keyid=<?php echo $id; ?>&op=resetpwd&user_id=<?php echo $user_id; ?>&pagenum=<?php echo $pagenum; ?>" onclick="if(!confirm(&#39;重置后密码为：888888。继续？&#39;)){return false};"><img src="images/m-ico-9.png" align="absmiddle" alt="重置密码" title="重置密码"></a> 
					 
				    <?php if($status==0){?>	
					   <a  class="btn"  href="qrsell.php?op=status&id=<?php echo $id; ?>&status=1&user_id=<?php echo $user_id; ?>&parent_id=<?php echo $parent_id; ?>&pagenum=<?php echo $pagenum; ?>"  title="通过">
						  <i  class="icon-ok"></i>
						</a>
						<a  class="btn"  href="javascript:showReason('qrsell.php?op=status&id=<?php echo $id; ?>&status=-1&parent_id=<?php echo $parent_id; ?>&user_id=<?php echo $user_id; ?>&pagenum=<?php echo $pagenum; ?>');"  title="驳回/暂停">
						  <i  class="icon-minus"></i>
						</a>
					<?php }else if($status==1){ ?>
					    <a  class="btn"  href="javascript:showReason('qrsell.php?op=status&id=<?php echo $id; ?>&status=-1&parent_id=<?php echo $parent_id; ?>&user_id=<?php echo $user_id; ?>&pagenum=<?php echo $pagenum; ?>');"  title="驳回/暂停">
						  <i  class="icon-minus"></i>
						</a>
						
						 <a  class="btn"  href="qrsell.php?op=cancel_level&id=<?php echo $id; ?>&status=-1&parent_id=<?php echo $parent_id; ?>&user_id=<?php echo $user_id; ?>&pagenum=<?php echo $pagenum; ?>" onclick="if(!confirm(&#39;确认取消上下级关系后不可恢复，继续吗？&#39;)){return false};"  title="取消上下级关系">
						  <i  class="icon-minus"></i>
						</a>
						
					<?php }else if($status==-1){ ?>
					   <a  class="btn"  href="qrsell.php?op=status&id=<?php echo $id; ?>&status=1&user_id=<?php echo $user_id; ?>&parent_id=<?php echo $parent_id; ?>"  title="通过">
						  <i  class="icon-ok"></i>
						</a>
					<?php } ?>
					<a href="qrsell.php?customer_id=<?php echo $customer_id; ?>&id=<?php echo $id; ?>&op=del&user_id=<?php echo $user_id; ?>&qr_info_id=<?php echo $qr_info_id; ?>&pagenum=<?php echo $pagenum; ?>&parent_id=<?php echo $parent_id; ?>" onclick="if(!confirm(&#39;删除后不可恢复，继续吗？&#39;)){return false};"><img src="images/del.gif" align="absmiddle" alt="删除"></a>
				   </td>
				   
                </tr>				
				
			   <?php } ?>
			   
			   <tr>
			      <td colspan=12>
			       <div class="getmore">
					 <?php if($pagenum>1){ ?>
					 <div class="getmore_l" onclick="prePage();">
						上一页
					 </div>
					 <?php } ?>
					  
					 <?php if($rcount_q==20){?>
					 <div class="getmore_r"  onclick="nextPage();">
						下一页
					 </div>
					 <?php } ?>
				  </div>
				 </td>
			   </tr>
			</tbody>
		</table>
		<div class="blank20"></div>
		<div id="turn_page"></div>
	</div>	</div>
<div>
</div></div>


<?php 

mysql_close($link);
?>

<script>
var pagenum = <?php echo $pagenum ?>;
  function prePage(){
     pagenum--;
	 var search_status = document.getElementById("search_status").value; 
	 var search_name = document.getElementById("search_name").value; 
	 var search_phone = document.getElementById("search_phone").value; 
     document.location= "qrsell.php?pagenum="+pagenum+"&search_status="+search_status+"&search_name="+search_name+"&search_phone="+search_phone;
  }
  
  function nextPage(){
     pagenum++;
	 var search_status = document.getElementById("search_status").value;
	 var search_name = document.getElementById("search_name").value; 
	 var search_phone = document.getElementById("search_phone").value; 
     document.location= "qrsell.php?pagenum="+pagenum+"&search_status="+search_status+"&search_name="+search_name+"&search_phone="+search_phone;
  }
  
  function showReason(url){
  
    var str=prompt("请输入驳回/暂停理由","您不符合<?php echo $exp_name; ?>条件，请联系客服");
    if(str)
    {
	   document.location = url+"&reason="+str;
    }
  }
  
  function exportRecord(){
     var search_status = document.getElementById("search_status").value;
     var search_user_id =document.getElementById("search_user_id").value;
	 var search_name =document.getElementById("search_name").value;
	 var search_phone =document.getElementById("search_phone").value;
	 
	 if(search_user_id==""){
	    search_user_id="0";
	 }
	 if(search_name==""){
	    search_name="0";
		alert('name=====');
	 }
	 if(search_phone==""){
	    search_phone="0";
	 }
     var url='/weixin/plat/app/index.php/Excel/commonshop_excel_qrsell/customer_id/<?php echo $customer_id; ?>/status/'+search_status+'/search_user_id/'+search_user_id+'/search_name/'+search_name+'/search_phone/'+search_phone+'/exp_user_id/<?php echo $exp_user_id; ?>/';
	 console.log(url);
	 goExcel(url,1,'http://<?php echo $http_host;?>/weixinpl/');
  }
</script>
</body></html>