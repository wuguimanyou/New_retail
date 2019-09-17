<?php
header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../proxy_info.php');

mysql_query("SET NAMES UTF8");
$head=3;

//分页---start
$pagenum = 1;
$pagesize = 20;
$begintime="";
$endtime ="";
if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}

$start = ($pagenum-1) * $pagesize;
$end = $pagesize;

$name 			= '';	//姓名
$weixin_name 	= '';	//微信名
$account 		= '';	//绑定的号码
$money 			= 0;	//设计金额
$type 			= 0;	//进出账 0：进账 1：出账 2:充值
$batchcode 		= '';	//订单号
$createtime 	= '';	//时间
$remark 		= '';	//备注



/*$query = "SELECT l.id,u.name,u.weixin_name,s.account,l.money,l.type,l.batchcode,l.remark,l.createtime,l.user_id from weixin_users u LEFT JOIN  moneybag_log l on u.id=l.user_id LEFT JOIN system_user_t s ON l.user_id=s.user_id where u.isvalid=true and l.isvalid=true and l.customer_id=".$customer_id;*/

$query = "SELECT id,money,type,batchcode,remark,createtime,user_id FROM moneybag_log WHERE isvalid=true AND customer_id=$customer_id";

$query1 = $query;
$sql = $query." ORDER BY createtime desc limit ".$start.",".$end;
//日期条件--开始时间
$begintime = "";
if( !empty($_GET['AccTime_E']) ){  //结算/发放 时间 
	$begintime = $_GET['AccTime_E'];
	$sql = $query." and UNIX_TIMESTAMP(createtime)>=".strtotime($begintime);	
	$query1 = $sql;
}
//日期条件--结束时间
$endtime = "";	
if( !empty($_GET['AccTime_B']) ){   //结算/发放 End
	$endtime = $_GET['AccTime_B'];
	$query1 = $sql." and UNIX_TIMESTAMP(createtime)<=".strtotime($endtime)." order by createtime desc ";
	$sql = $sql." and UNIX_TIMESTAMP(createtime)<=".strtotime($endtime)." order by createtime desc limit ".$start.",".$end;	
}

if( !empty($_GET["promoter"]) ){
	 $user_id = $configutil->splash_new($_GET["promoter"]);
	 $query1 = $query." and user_id=".$user_id;
  	 $sql = $query." and user_id=".$user_id." order by createtime desc limit ".$start.",".$end;
}
//echo $user_id;
$result = mysql_query($query1) or die('Query failed2: ' . mysql_error());
$rcount_q = mysql_num_rows($result);
$page=ceil($rcount_q/$end); 
 /* 输出数量结束 */


?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>待提现记录</title>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/inside.css" media="all">
<script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="../../../js/WdatePicker.js"></script>
<script type="text/javascript" src="../../../common/js/layer/layer.js"></script>
<script src="../../Common/js/Data/js/echarts/echarts.js"></script>
<script type="text/javascript" src="../../Common/js/Data/js/ichartjs/ichart.1.2.min.js"></script>
<script type="text/javascript" src="../../../common/js/inside.js"></script>
<style>

table th{color: #FFF;line-height: 30px;text-align: center;font-size: 12px; }
table td{height: 40px;line-height: 20px;font-size: 12px;color: #323232;padding: 0px 1em;text-align: center;border: 1px solid #D8D8D8; }
.display{display:none}
table td img{width: 20px;height: 20px;margin-left: 5px;}


</style>

</head>

<body id="bod" style="min-height: 580px;">
	<!--内容框架-->
	<div class="WSY_content" style="height: 100%;">

		<!--列表内容大框-->
		<div class="WSY_columnbox">
			<!--列表头部切换开始-->
			
				<?php
			include("basic_head.php"); 
			?>
		
			<!--列表头部切换结束-->
<!--门店列表开始-->
  <div  class="WSY_data">
	 <!--列表按钮开始-->
      <div class="WSY_list" id="WSY_list">

	<form action="" >

      	<div style="margin-left:40px;margin-top:0px;">
      		<span style="margin-left:10px;">会员编号：</span>
      		<input type="text" name="promoter" id="promoter_num" value="<?php echo $user_id;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">
      	<!-- 	<span style="margin-left:20px;">会员卡编号：</span>
      		<input type="text" name="card_num" id="card_member_id" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;"> -->
		<div class="WSY_position1" style="float:left">
			<ul>		
				<li class="WSY_position_date tate001" >
					<p>时间：<input class="date_picker" type="text" name="AccTime_E" id="begintime" value="<?php echo $begintime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
					<p style="margin-left:0px;">&nbsp;&nbsp;-&nbsp;&nbsp;<input class="date_picker" type="text" name="AccTime_B" id="endtime" value="<?php echo $endtime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
				</li>				
			</ul>
		</div>
		<input type="submit" id="my_search" >
		</div>

		

	</form>

             <br class="WSY_clearfloat";>
        </div> 
        <!--列表按钮开始-->
		
        <!--表格开始-->
		<div class="WSY_data" id="type1" style="margin-left: 1.5%;">
		
		<table class="WSY_t2"  width="97%"  style="border: 1px solid #D8D8D8;border-collapse: collapse;">
			<thead class="WSY_table_header">
				<tr style="border:none">
					<th width="2%" >ID</th>
					<th width="4%" >编号</th>
					<th width="6%">姓名（微信名）</th>
					<th width="6%">绑定手机</th>
					<th width="6%">涉及金额</th>
					<th width="6%">进出账</th>
					<th width="6%">订单号</th>			
					<th width="8%">时间</th>
					<th width="10%">备注信息</th>
				</tr>
			</thead>
			<tbody>
			<?php	
				$result= mysql_query($sql);
				while($row=mysql_fetch_object($result)){
					$id 			= $row->id;
					$user_id 		= $row->user_id;
					$name = '';
					$weixin_name = '';
					$query_u = "SELECT name,weixin_name FROM weixin_users WHERE isvalid=true AND id=$user_id";
					$result_u= mysql_query($query_u) or die('Query failed 163: ' . mysql_error());
					while( $info = mysql_fetch_object($result_u) ){
						$name = $info->name;
						$weixin_name = $info->weixin_name;
					}
					$account = '<span style="color:#c22439;font-weight:blod;font-size:14px;">尚未绑定</span>';
					$query_s = "SELECT account FROM system_user_t WHERE isvalid=true AND user_id=$user_id";
					$result_s= mysql_query($query_s) or die('Query failed 163: ' . mysql_error());
					while( $info = mysql_fetch_object( $result_s )){
						$account = $info->account;
					}
					if( $account == '' || $account == NULL ){
							$account = '<span style="color:#c22439;font-weight:blod;font-size:14px;">尚未绑定</span>';
						}

					$money 			= $row->money;
					$type 			= $row->type;
					switch($type){
						case '0':
							$type   = '<span style="color:#c22439;font-weight:blod;font-size:14px;">进账</span>';
						break;
						
						case '1':
							$type   = '<span style="color:#68af27;font-weight:blod;font-size:14px;">支出</span>';
						break;
						
						case '2':
							$type   = '充值';
						break;		
					}
					$batchcode 		= $row->batchcode;
					$createtime 	= $row->createtime;
					$remark 		= $row->remark;
				

			?>
				<tr style="border:1px solid #D8D8D8">
					<td><?php echo $id;?></td>
					<td><?php echo $user_id;?></td>
					<td><?php echo $name;?>（<?php echo $weixin_name;?>）</td>
					<td><?php echo $account?></td>
					<td><?php echo $money;?></td>
					<td><?php echo $type;?></td>
					<td><?php echo $batchcode;?></td>
					<td><?php echo $createtime;?></td>
					<td><?php echo $remark;?></td>				
				</tr>
			<?PHP }?> 
			
			</tbody>
			
			</table>
			
			<!--翻页开始-->
			<div class="WSY_page">
				
			</div>
			<!--翻页结束-->
		</div>
		<script src="../../../js/fenye/jquery.page1.js"></script>
		<script type="text/javascript">
		 var pagenum = <?php echo $pagenum ?>;
		  var count =<?php echo $page ?>;//总页数
			//pageCount：总页数
			//current：当前页
			var user_id = $("#promoter_num").val();
			var card_id = $("#card_member_id").val();
			var AccTime_E = $("#begintime").val();
			var AccTime_B = $("#endtime").val();

			
			$(".WSY_page").createPage({
				pageCount:count,
				current:pagenum,
				backFn:function(p){
				 document.location= "recharge_log.php?pagenum="+p+"&promoter="+user_id+"&AccTime_E="+AccTime_E+"&AccTime_B="+AccTime_B;
			   }
			});

		  var page = <?php echo $page ?>;
		  
		  function jumppage(){
			var a=parseInt($("#WSY_jump_page").val());
			if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
				return false;
			}else{
			document.location= "recharge_log.php?pagenum="+p+"&promoter="+user_id+"&AccTime_E="+AccTime_E+"&AccTime_B="+AccTime_B;
			}
		  }	
		</script>

	</div>
</div>
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/fenye/fenye.css" media="all">


<?php 

mysql_close($link);
?>

</body>
</html>
