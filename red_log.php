<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../proxy_info.php');

mysql_query("SET NAMES UTF8");


/*if(!empty($_GET["customer_id"])){
   $customer_id = $configutil->splash_new($_GET["customer_id"]);
}*/ //前面引用的文件中有获取
$pagenum = 1;
$pagesize = 20;
$begintime="";
$endtime ="";
if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}
$start = ($pagenum-1) * $pagesize;
$end = $pagesize;
$change_type=-1;
if(!empty($_GET["search_red"])){
   $search_red = $configutil->splash_new($_GET["search_red"]);
}
if(!empty($_GET["red_order"])){
   $red_order = $configutil->splash_new($_GET["red_order"]);
}
if(!empty($_GET["deal_id"])){
   $deal_id = $configutil->splash_new($_GET["deal_id"]);
}
if(!empty($_GET["name"])){
   $name = $configutil->splash_new($_GET["name"]);
}
if(!empty($_GET["begintime"])){
   $begintime = $configutil->splash_new($_GET["begintime"]);
}
if(!empty($_GET["endtime"])){
   $endtime = $configutil->splash_new($_GET["endtime"]);
}
if(!empty($_GET["change_type"])){
   $change_type = $configutil->splash_new($_GET["change_type"]);
}
$total_money=0;
$query="select r.id,u.name,u.weixin_name,customer_red_id,weixin_red_id,r.remark,user_id,r.type,deal_id,r.createtime,red_money from weixin_red_log r inner join weixin_users u where r.isvalid=true and r.customer_id=".$customer_id." and r.user_id=u.id";
if(!empty($search_red)){			   
	$query = $query." and r.weixin_red_id like '%".$search_red."%'";
}
if(!empty($red_order)){			   
	$query = $query." and r.customer_red_id like '%".$red_order."%'";
}
if(!empty($deal_id)){			   
	$query = $query." and r.deal_id like '%".$deal_id."%'";
}
if(!empty($name)){			   
	$query = $query." and u.name like '%".$name."%'";
}
if(!empty($begintime)){			   
	$query = $query." and UNIX_TIMESTAMP(r.createtime)<".strtotime($begintime);
}
if(!empty($endtime)){			   
	$query = $query." and UNIX_TIMESTAMP(r.createtime)<".strtotime($endtime);
}
if(!empty($name)){			   
	$query = $query." and u.weixin_name like '%".$name."%'";
}
if($change_type!=-1){			   
	$query = $query." and r.type=".$change_type;
}
 //echo $query;
  /* 输出数量开始 */
$result = mysql_query($query) or die('Query failed2: ' . mysql_error());
$rcount_q = mysql_num_rows($result);
 /* 输出数量结束 */
?>
<!DOCTYPE html>

<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<title></title>
<link href="css/global.css" rel="stylesheet" type="text/css">
<link href="css/main.css" rel="stylesheet" type="text/css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../css/icon.css" media="all">
<script type="text/javascript" src="../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="js/global.js"></script>
<script type="text/javascript" src="../common/utility.js" charset="utf-8"></script>
<script type="text/javascript" src="../common/js/jquery.blockUI.js"></script>
<script charset="utf-8" src="../common/js/jquery.jsonp-2.2.0.js"></script>
	

</head>

<body>

<style type="text/css">body, html{background:url(images/main-bg.jpg) left top fixed no-repeat;}</style>

<div id="iframe_page">
	<div class="iframe_content">
	<link href="css/shop.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/shop.js"></script>
<link href="css/operamasks-ui.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/operamasks-ui.min.js"></script>
<script type="text/javascript" src="../js/tis.js"></script>
<script language="javascript">

$(document).ready(shop_obj.orders_init);
</script>
<div id="orders" class="r_con_wrap" style="min-width:1210px;"> 
		<div class="search" id="search_form">
			&nbsp;微信商户单号:<input type=text name="search_red" id="search_red" value="<?php echo $search_red; ?>" style="width:150px;" />
			&nbsp;红包单号:<input type=text name="red_order" id="red_order" value="<?php echo $red_order; ?>" style="width:150px;" />
			&nbsp;订单号:<input type=text name="deal_id" id="deal_id" value="<?php echo $deal_id; ?>" style="width:150px;" />
			&nbsp;名称:<input type=text name="name" id="name" value="<?php echo $name; ?>" style="width:150px;" />
		   
		   <span>
		   时间：
		   <span class="om-calendar om-widget om-state-default">
				<input type="text" class="input" id="begintime" name="AccTime_A" value="<?php echo $begintime; ?>" maxlength="21" id="K_1389249066532">
				<span class="om-calendar-trigger"></span>
			</span>
			-
			<span class="om-calendar om-widget om-state-default">
				<input type="text" class="input" id="endtime" name="AccTime_B" value="<?php echo $endtime; ?>" maxlength="20" id="K_1389249066580">
				<span class="om-calendar-trigger"></span>
			</span>
		      
		   </span>
		   <span>
			红包类型:
			<select id="change_type" name="change_type" style="width:100px" onchange="change_type(this.value);">
				<option value=-1 <?php if($change_type==-1){ ?>selected<?php } ?>>--请选择--</option>
				<option value=1 <?php if($change_type==1){ ?>selected<?php } ?>>佣金红包</option>
				<option value=2 <?php if($change_type==2){ ?>selected<?php } ?>>微信零钱</option>
			</select>
		   </span>
		   <input type="button" class="search_btn" onclick="search_redname();" value="搜 索">	  
			<input type="button" class="search_btn" onclick="exportRed();;" value="导出数据">			   
		</div>	
		<table border="0" cellpadding="5" cellspacing="0" class="r_con_table" id="order_list">
			<thead>
			<?php 
				/* $query1="select sum(red_money) as total_money from weixin_red_log where isvalid=true and customer_id=".$customer_id; */
				$result = mysql_query($query) or die('Query failed: ' . mysql_error());
				while ($row = mysql_fetch_object($result)) {
					$red_money=$row->red_money;
					$total_money=$total_money+$red_money;
					
				}
			?>
				<tr style="background: #fff;">
					<td colspan="9">
					总共发出红包:<span style="color:red;font-size:22px;"><?php echo $total_money;?></span>元
					</td>
				</tr>
				<tr>
					<td width="8%" nowrap="nowrap">ID</td>
					<td width="8%" nowrap="nowrap">红包单号</td>
					<td width="12%" nowrap="nowrap">微信商户单号</td>
					<td width="8%" nowrap="nowrap">订单号/会员卡号</td>
					<td width="8%" nowrap="nowrap">名称(微信名称)</td>
					<td width="8%" nowrap="nowrap">类型</td>
					<td width="8%" nowrap="nowrap">确认时间</td>
					<td width="8%" nowrap="nowrap">红包金额</td>
					<td width="12%" nowrap="nowrap">备注</td>
				</tr>
			</thead>
			<tbody>
			<?PHP
				
				$query = $query." order by id desc"." limit ".$start.",".$end;
				//echo $query;
				$result1 = mysql_query($query) or die('Query failed1: ' . mysql_error());
				$sun_money=0;
					while ($row = mysql_fetch_object($result1)) {
						$log_id=$row->id;
						$customer_red_id=$row->customer_red_id;//红包单号
						$weixin_red_id=$row->weixin_red_id;//微信商户单号
						$remark=$row->remark;//备注
						$user_id=$row->user_id;
						$type=$row->type;//红包类型
						$deal_id=$row->deal_id;//订单号/会员卡号
						$createtime=$row->createtime;
						$red_money=$row->red_money;
						$username=$row->name;
						$weixin_name=$row->weixin_name;
						$sun_money=$sun_money+$red_money;
						$type_name="佣金红包";
						if($type==1){
							$type_name="佣金红包";
						}else if($type==2){
							$type_name="微信零钱";
						}
						/* $query2="select name,weixin_name from weixin_users where isvalid=true and id=".$user_id;
						$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
						while ($row2 = mysql_fetch_object($result2)) {
							$username=$row2->name;
							$weixin_name=$row2->weixin_name;
						} */
			?>
				<tr>
					<td><?php echo $log_id;?></td>
					<td><?php echo $customer_red_id;?></td>
					<td><?php echo $weixin_red_id;?></td>
					<td><?php echo $deal_id;?></td>
					<td><?php echo $username;?>(<?php echo $weixin_name;?>)</td>
					<td><?php echo $type_name;?></td>
					<td><?php echo $createtime;?></td>
					<td><?php echo $red_money;?></td>
					<td><?php echo $remark;?></td>
				</tr>
			<?PHP }?> 
				<tr>
					<td colspan="9">
					当前页发出红包:<span style="color:red;font-size:22px;"><?php echo $sun_money;?></span>元
					</td>
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
</div></div>
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../css/fenye/fenye.css" media="all">
<script src="../js/fenye/jquery.page.js"></script>
<script>
	var pagenum = <?php echo $pagenum ?>;
	var rcount_q = <?php echo $rcount_q ?>;
	var end = <?php echo $end ?>;
	var count =Math.ceil(rcount_q/end);//总页数

  	//pageCount：总页数
	//current：当前页
	 $(".tcdPageCode").createPage({
        pageCount:count,
        current:pagenum,
        backFn:function(p){
		 var search_red = document.getElementById("search_red").value;
		 var change_type = document.getElementById("change_type").value;
		 var name = document.getElementById("name").value;
		 var deal_id = document.getElementById("deal_id").value;
		 var red_order = document.getElementById("red_order").value;
		 document.location= "red_log.php?pagenum="+p+"&search_red="+search_red+"&name="+name+"&deal_id="+deal_id+"&red_order="+red_order+"&change_type="+change_type+"&customer_id="+'<?php echo $customer_id_en;?>';
	   }
    }); 
function search_redname(){
	var search_red = document.getElementById("search_red").value;
	var change_type = document.getElementById("change_type").value;
	var name = document.getElementById("name").value;
	var deal_id = document.getElementById("deal_id").value;
	var red_order = document.getElementById("red_order").value;
	var begintime = document.getElementById("begintime").value;
	var endtime = document.getElementById("endtime").value;
	var url="red_log.php?pagenum=1&search_red="+search_red+"&name="+name+"&deal_id="+deal_id+"&red_order="+red_order+"&customer_id="+'<?php echo $customer_id_en;?>';
	if(change_type!=-1){
		url=url+"&change_type="+change_type;
	}
	if(begintime !=""){
		url=url+"&begintime="+begintime;
	}
	if(endtime !=""){
		url=url+"&endtime="+endtime;
	}
	document.location= url;
}
function change_type(change_type){
	var search_red = document.getElementById("search_red").value;
	document.location= "red_log.php?pagenum=1&change_type="+change_type+"&search_red="+search_red+"&customer_id="+'<?php echo $customer_id_en;?>';	

	
}
function exportRed(){
	
	var begintime = document.getElementById("begintime").value;
	var endtime = document.getElementById("endtime").value;
	var url='/weixin/plat/app/index.php/Excel/red_excel/customer_id/<?php echo $customer_id; ?>/';
	if(begintime !=""){
		url=url+'begintime/'+begintime+'/';
	}
	if(endtime !=""){
		url=url+'endtime/'+endtime+'/';
	}
	console.log(url);
	goExcel(url,1,'http://<?php echo $http_host;?>/weixinpl/');
}
</script>

<?php 

mysql_close($link);
?>
</body></html>