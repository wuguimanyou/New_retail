<?php
header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require('../../../proxy_info.php');

mysql_query("SET NAMES UTF8");
require('../../../auth_user.php');
$op="";
if(!empty($_GET["op"])){
   $op = $configutil->splash_new($_GET["op"]);
   $id = $configutil->splash_new($_GET["shop_customer_id"]);
   if($op=="del"){
      $sql="update weixin_commonshop_customers set isvalid=false where id=".$id;
	  mysql_query($sql);
	  
	 
   }
}
$exp_user_id=-1;

if(!empty($_GET["exp_user_id"])){
    $exp_user_id = $configutil->splash_new($_GET["exp_user_id"]);
}

$query ="select isOpenPublicWelfare from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	while ($row = mysql_fetch_object($result)) {
	   $isOpenPublicWelfare = $row->isOpenPublicWelfare;
	}
$query = 'SELECT id,appid,appsecret,access_token FROM weixin_menus where isvalid=true and customer_id='.$customer_id;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());  
$access_token="";
while ($row = mysql_fetch_object($result)) {
	$keyid =  $row->id ;
	$appid =  $row->appid ;
	$appsecret = $row->appsecret;
	$access_token = $row->access_token;
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

$query="select count(distinct batchcode) as new_order_count from weixin_finance_orders where isvalid=true and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $new_order_count = $row->new_order_count;
   break;
}

$query="select sum(totalprice) as today_totalprice from weixin_finance_orders where paystatus=1 and sendstatus!=4 and isvalid=true and customer_id=".$customer_id." and year(consumetime)=".$year." and month(consumetime)=".$month." and day(consumetime)=".$day;
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

$query="select count(1) as new_qr_count from promoters where isvalid=true and status=1 and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $new_qr_count = $row->new_qr_count;
   break;
}

$search_user_id=-1;
if(!empty($_GET["search_user_id"])){
   $search_user_id = $configutil->splash_new($_GET["search_user_id"]);
}
$search_name="";
if(!empty($_GET["search_name"])){
    $search_name = $configutil->splash_new($_GET["search_name"]);
}
if(!empty($_POST["search_name"])){
    $search_name = $configutil->splash_new($_POST["search_name"]);
}
$search_phone="";
if(!empty($_GET["search_phone"])){
    $search_phone = $configutil->splash_new($_GET["search_phone"]);
}
if(!empty($_POST["search_phone"])){
    $search_phone = $configutil->splash_new($_POST["search_phone"]) ;
}
$search_name_type=1;	//1为搜索微信名称 2为搜索收货名称
if(!empty($_GET["search_name_type"])){		
    $search_name_type = $configutil->splash_new($_GET["search_name_type"]);
}
if(!empty($_POST["search_name_type"])){
    $search_name_type = $configutil->splash_new($_POST["search_name_type"]);
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
?>
<!DOCTYPE html>
<!-- saved from url=(0047)http://www.ptweixin.com/member/?m=shop&a=orders -->
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title></title>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link rel="stylesheet" type="text/css" href="../../Common/css/Users/promoter/promoter.css">
<script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
<style>
a{
	color: #06A7E1;
}
</style>
</head>

<body>
<div id="WSY_content">
	<div class="WSY_columnbox" style="min-height: 300px;">
		<div class="WSY_column_header">
			<div class="WSY_columnnav">
				<a href="customers.php?search_user_id=<?php echo $search_user_id ?>" >推广员</a>
				<a class="white1">商圈_金融保险</a>
			</div>
		</div>
		<div  class="WSY_data">
			<!--<div id="WSY_list" class="WSY_list">
				<div class="WSY_left" style="background: none;">
					<form class="search" id="search_form">
						<select name="search_name_type" id="search_name_type" style="width:100px;">
							<option value="1" <?php if($search_name_type==1){ ?>selected <?php } ?>>微信昵称</option>
							<option value="2" <?php if($search_name_type==2){ ?>selected <?php } ?>>收货人</option>
						</select>
						&nbsp;姓名:<input type=text name="search_name" id="search_name" value="<?php echo $search_name; ?>" style="width:80px;" />
						&nbsp;电话:<input type=text name="search_phone" id="search_phone" value="<?php echo $search_phone; ?>"  style="width:80px;" />
						<input type="button" class="search_btn" onclick="searchForm();" value="搜 索">
					</form>
				</div>
			</div>	-->
		<table width="97%" class="WSY_table WSY_t2" id="WSY_t1">
			<thead class="WSY_table_header">
				<tr>
					<th width="8%" nowrap="nowrap">姓名</th>
					<th width="13%" nowrap="nowrap">手机号</th>
					<th width="10%" nowrap="nowrap">已完成订单金额</th>
					<th width="10%" nowrap="nowrap">未支付订单金额</th>
					<th width="10%" nowrap="nowrap">已支付订单金额</th>
					<th width="8%" nowrap="nowrap">最近购买时间</th>
					<th width="8%" nowrap="nowrap">来源</th>
					<th width="8%" nowrap="nowrap">推广员</th>		
                    <th width="8%" nowrap="nowrap">操作</th>							
				</tr>
			</thead>
			<tbody>
				<?php 
				$pagenum = 1;

				if(!empty($_GET["pagenum"])){
				   $pagenum = $configutil->splash_new($_GET["pagenum"]);
				}

				$start = ($pagenum-1) * 20;
				$end = 20;
				$query_count="select count(1) as tcount from weixin_commonshop_customers wcc inner join weixin_users wu on wcc.user_id=wu.id and  wcc.isvalid=true and wcc.customer_id=".$customer_id;
				
				$query = "select distinct(user_id), wcc.id as id,wu.name,wu.weixin_name,wu.weixin_fromuser,wu.phone,wu.parent_id as parent_id,wu.fromw from weixin_commonshop_customers wcc inner join weixin_users wu on wcc.user_id=wu.id and  wcc.isvalid=true and wcc.customer_id=".$customer_id;
				
				if($search_user_id>0){
				   $query = $query." and wu.id =".$search_user_id;
				   $query_count = $query_count." and wu.id =".$search_user_id;
				}
				 if(!empty($search_name)){
					 if(!empty($search_name_type)){
						 switch($search_name_type){
							  case 1:
								$query = $query." and wu.weixin_name like '%".$search_name."%'";
								$query_count = $query_count." and wu.weixin_name like '%".$search_name."%'";
							  break;
							  case 2:
							    $query = $query." and wca.name like '%".$search_name."%'";
								$query_count = $query_count." and wca.name like '%".$search_name."%'";
							  break;
						 }
					 }  					
				 }				 
				 if(!empty($search_phone)){
				   
					$query = $query." and wu.phone like '%".$search_phone."'";
					$query_count = $query_count." and wu.phone like '%".$search_phone."'";
				 }
				 /* 输出数量开始 */
				$rcount_q2=0;
				$result2 = mysql_query($query_count) or die('Query failed: ' . mysql_error());
				while ($row2 = mysql_fetch_object($result2)) {
					$rcount_q2=$row2->tcount;
				 }
				 /* 输出数量结束 */				 
				 $query = $query." order by wcc.id desc"." limit ".$start.",".$end;
				 $result = mysql_query($query) or die('Query failed: ' . mysql_error());
				 $rcount_q = mysql_num_rows($result);
				 $weixin_fromuser="";
	             while ($row = mysql_fetch_object($result)) {				 
				    $user_id =$row->user_id;					
					$id = $row->id;					
					$username=$row->name;
					$weixin_name = $row->weixin_name;					
					if(empty($weixin_name)){
					    $weixin_fromuser = $row->weixin_fromuser;
					    $url="https://api.weixin.qq.com/cgi-bin/user/info";
                        $data = array('access_token'=>$access_token,'openid'=>$weixin_fromuser); 
						$ch = curl_init(); 
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_POST, 1); 
						// 这一句是最主要的
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
						$html = curl_exec($ch);  
						curl_close($ch) ;
						$obj=json_decode($html);						
						 if(!empty($obj->errcode)){
						     $errcode =$obj->errcode ;
						    //echo $errorcode;
						    if($errcode==42001||$errcode==40014 ||$errcode==40001){
							 //高级接口超时，重新绑定
							//echo "<script>win_alert('发生未知错误！请联系商家');</script>";
							    $data = array('grant_type'=>'client_credential','appid'=>$appid,'secret'=>$appsecret);  
							     $url = "https://api.weixin.qq.com/cgi-bin/token";
								$ch = curl_init(); 
								curl_setopt($ch, CURLOPT_URL, $url);
								curl_setopt($ch, CURLOPT_POST, 1); 
								// 这一句是最主要的
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
								curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
								$html = curl_exec($ch);  
								$obj=json_decode($html);								
								$access_token = "";
								curl_close($ch) ;
								if(!empty($obj->access_token)){
								   $access_token = $obj->access_token;
								   $query4="update weixin_menus set appid='".$appid."',appsecret='".$appsecret."', access_token = '".$access_token."' where customer_id=".$customer_id;
								   mysql_query($query4);								   
								    $url="https://api.weixin.qq.com/cgi-bin/user/info";
                                   $data = array('access_token'=>$access_token,'openid'=>$weixin_fromuser); 
									$ch = curl_init(); 
									curl_setopt($ch, CURLOPT_URL, $url);
									curl_setopt($ch, CURLOPT_POST, 1); 
									// 这一句是最主要的
									curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
									curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
									$html = curl_exec($ch);  
									$obj=json_decode($html);
									$weixin_name =  $obj->nickname;
									$sex = $obj->sex;
									$headimgurl= $obj->headimgurl;
									$subscribe_time = $obj->subscribe_time;
									$query4 = "update weixin_users set weixin_headimgurl='".$headimgurl."',weixin_name='".$weixin_name."',sex=".$sex." where id=".$user_id;
									//echo $query;	
									mysql_query($query4);
								}else{
								   echo "<script>win_alert('发生未知错误！请联系商家');</script>";
								   return;
								}
							}
						}else{
							$weixin_name =  $obj->nickname;
							$sex = $obj->sex;
							$headimgurl= $obj->headimgurl;
							$subscribe_time = $obj->subscribe_time;
							}						 
					}
					$username=$username."(".$weixin_name.")";
					$userphone = $row->phone;
					$parent_id = $row->parent_id;									
					$query2="select sum(totalprice) as totalprice from weixin_finance_orders wco where  status=2 and aftersale_state!=4 and  wco.isvalid=true and wco.user_id=".$user_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					//订单完成的
					$totalprice_1 = 0;
					//未支付的
					$totalprice_2 = 0;
					//已支付的
					$totalprice_3 = 0;
				    while ($row2 = mysql_fetch_object($result2)) {
					   $totalprice_1 = $row2->totalprice;
					}					
					/* 原查询未支付金额 
					$query2="select sum(totalprice) as totalprice from weixin_finance_orders  where  paystatus=0 and  isvalid=true and user_id=".$user_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					while ($row2 = mysql_fetch_object($result2)) {
					   $totalprice_2= $row2->totalprice;
					}
					*/
					
					/* 新查询未支付金额*/
					$totalprice_wzf=0;
					$batchcode_wzf=-1;
					$query2="select totalprice,batchcode from weixin_finance_orders  where  paystatus=0 and  isvalid=true and user_id=".$user_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					while ($row2 = mysql_fetch_object($result2)) {
					   	$totalprice_wzf= $row2->totalprice;
					   	$batchcode_wzf= $row2->batchcode;
						$totalprice_2 += $totalprice_wzf;
					}										 				
					/*新查询未支付金额end */
					
					$query2="select sum(totalprice) as totalprice from weixin_finance_orders wco where  paystatus=1 and  wco.isvalid=true and wco.user_id=".$user_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					$totalprice_3 = 0;
				    while ($row2 = mysql_fetch_object($result2)) {
					   $totalprice_3= $row2->totalprice;
					}
					
					$fromw = $row->fromw;
					$fromwstr="主动关注";
					switch($fromw){
					   case 1:
					      $fromwstr="主动关注";
					      break;
					   case 3:
					      $fromwstr="带参数二维码";
					       break;
						case 2:
					      $fromwstr="朋友圈";
					       break;
					}
					
					$parent_name = "";
					$parent_weixin_fromuser="";
					if($parent_id>0 and $parent_id!=$user_id){
						$query2="select id from promoters where status=1 and isvalid=true and user_id=".$parent_id;
						$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
						$promoter_id = -1;
						while ($row2 = mysql_fetch_object($result2)) {    
						    $promoter_id = $row2->id;
							break;
						}			
						if($promoter_id>0){
							$query2= "select name,phone,parent_id,weixin_name,weixin_fromuser from weixin_users where isvalid=true and id=".$parent_id; 
							$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
							while ($row2 = mysql_fetch_object($result2)) {
								$parent_name=$row2->name;
								$parent_weixin_fromuser = $row2->weixin_fromuser;
								$weixin_name = $row2->weixin_name;
								$parent_name = $parent_name."(".$weixin_name.")";
								break;
							}
						}else{
						    $parent_id = -1;
						}
					}
					
					$query2="select createtime from weixin_finance_orders wco where   wco.user_id=".$user_id." order by id desc limit 0,1";
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					$createtime = 0;
				    while ($row2 = mysql_fetch_object($result2)) {
					   $createtime= $row2->createtime;
					}
					
					$totalprice_1 = round($totalprice_1, 2);
					$totalprice_2 = round($totalprice_2, 2);
					$totalprice_3 = round($totalprice_3, 2);
				    
					$query2="select name,phone,address from weixin_commonshop_addresses where isvalid=true and user_id=".$user_id;
					$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
					$add_phone="";
					$add_name="";
					while ($row2 = mysql_fetch_object($result2)) {
					   $add_phone = $row2->phone;
					   $add_name = $row2->name;
					}
					if(empty($userphone)){
					    $userphone = $add_name."(".$add_phone.")";
					}
					
					$query5="select weixin_fromuser from weixin_users where isvalid=true and id=".$user_id." limit 0,1";
					$result5 = mysql_query($query5) or die('Query failed: ' . mysql_error());
					while ($row5 = mysql_fetch_object($result5)) {
						$weixin_fromuser =  $row5->weixin_fromuser ;
					}

				?>
                <tr>
					<td align="center" style="padding-top: 17px;"><?php echo $username; ?>
						<?php if(!empty($weixin_fromuser)){?>  
						<a  class="btn"  href="../../../weixin_inter/send_to_msg.php?fromuserid=<?php echo $weixin_fromuser; ?>&customer_id=<?php echo passport_encrypt($customer_id)?>"  title="对话"><i  class="icon-comment"></i></a>
						<?php } ?>
					</td>
					<td align="center"><?php echo $userphone; ?></td>
					<td align="center">
						<a href="../../../back_city/Finance/orders/order.php?customer_id=<?php echo $customer_id_en; ?>&search_class=3&user_id=<?php echo $user_id; ?>"><?php echo $totalprice_1; ?></a>
					</td >	
					<td align="center">
						<a href="../../../back_city/Finance/orders/order.php?customer_id=<?php echo $customer_id_en; ?>&search_class=1&user_id=<?php echo $user_id; ?>"><?php echo $totalprice_2; ?></a>
					</td>
					<td align="center">	
						<a href="../../../back_city/Finance/orders/order.php?customer_id=<?php echo $customer_id_en; ?>&search_class=2&user_id=<?php echo $user_id; ?>"><?php echo $totalprice_3; ?></a>
					</td>
					<td align="center"><?php echo $createtime; ?></td>
					<td align="center"><?php echo $fromwstr; ?></td>
					<td align="center"><?php echo $parent_name; ?>
						<?php if(!empty($parent_weixin_fromuser)){?>  
							<a  class="btn"  href="../weixin_inter/send_to_msg.php?fromuserid=<?php echo $parent_weixin_fromuser; ?>&customer_id=<?php echo passport_encrypt($customer_id)?>"  title="对话"><i  class="icon-comment"></i></a>
						<?php } ?>
					</td>
					<td align="center"><p>
							<span onclick="if(!confirm('您确认要删除此数据吗?删除后数据不能恢复!'))return false; else goUrl('customers.php?op=del&shop_customer_id=<?php echo $id; ?>&pagenum=<?php echo $pagenum; ?>');"  style="cursor:pointer"><img src="../../../common/images_V6.0/operating_icon/icon04.png" align="absmiddle" alt="删除" title="删除" style="    width: 18px;height: 18px;vertical-align: baseline;"></span>
						</p>							
					</td>				   
                </tr>						
			   <?php } ?>
			</tbody>
		</table>
		<div class="blank20"></div>
		<div id="turn_page"></div>
		</div>
		<div class="WSY_page">

		</div>		
	</div>
	<div style="width:100%;height:20px;"></div>
</div>
	
<?php 

mysql_close($link);
?>
<script src="../../../js/fenye/jquery.page1.js"></script>
<script>
var pagenum = <?php echo $pagenum ?>;
var rcount_q2 = <?php echo $rcount_q2 ?>;
var end = <?php echo $end ?>;
var count =Math.ceil(rcount_q2/end);//总页数
//pageCount：总页数
//current：当前页
$(".WSY_page").createPage({
	pageCount:count,
	current:pagenum,
	backFn:function(p){
		var search_name = document.getElementById("search_name").value; 
		var search_phone = document.getElementById("search_phone").value; 
		document.location= "customers.php?pagenum="+p+"&search_name="+search_name+"&search_phone="+search_phone;
	}
});
function jumppage(){
	var a=parseInt($("#WSY_jump_page").val());  
	if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
		return false;
	}else{
		var search_name = document.getElementById("search_name").value; 
		var search_phone = document.getElementById("search_phone").value; 
		document.location= "customers.php?pagenum="+p+"&search_name="+search_name+"&search_phone="+search_phone;
	}
  }
function searchForm(){
	 var search_name = document.getElementById("search_name").value; 
		 var search_phone = document.getElementById("search_phone").value; 
		document.location= "customers.php?pagenum=1&search_name="+search_name+"&search_phone="+search_phone;
}
/*   function prePage(){
     pagenum--;
	
     document.location= "customers.php?pagenum="+pagenum;
  }
  
  function nextPage(){
     pagenum++;
     document.location= "customers.php?pagenum="+pagenum;
  } */
</script>
</body></html>