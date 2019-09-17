<?php
header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require('../../../proxy_info.php');
$scene_id = $configutil->splash_new($_GET["scene_id"]);
$type = $configutil->splash_new($_GET["type"]);
$sum_totalprice = 0;


if(!empty($_GET["sum_totalprice"])){
   $sum_totalprice = $configutil->splash_new($_GET["sum_totalprice"]);
}

mysql_query("SET NAMES UTF8");
$query2= "select name,phone from weixin_users where isvalid=true and id=".$scene_id; 
$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
$username="";
$userphone="";
while ($row2 = mysql_fetch_object($result2)) {
	$username=$row2->name;
	$exceltitle=$row2->name;//生成excel用
	$userphone = $row2->phone;
	break;
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
<script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
</head>

<body>
<div id="WSY_content">
	<div class="WSY_columnbox" style="min-height: 300px;">
		<div class="WSY_column_header">
			<div class="WSY_columnnav">
				<a class="white1">推广员</a>
			</div>
		</div>
		<div  class="WSY_data">
			<div id="WSY_list" class="WSY_list">
				<div class="WSY_left" style="background: none;">
					<div class="search">
						姓名：<span style="font-weight:bold"><?php echo $username; ?></span>&nbsp;&nbsp;&nbsp; 手机号：<span style="font-weight:bold"><?php echo $userphone; ?></span>&nbsp;&nbsp;&nbsp;
						<?php if($type==2){ ?>
						推广金额：<span style="font-weight:bold;font-size:22px;color:red"><?php echo $sum_totalprice; ?></span>
						<?php }else{ ?>
						推广积分：<span style="font-weight:bold;font-size:22px;color:red"><?php echo $sum_totalscore; ?></span>
						<?php } ?>
						<input type="button" class="search_btn" value="导出+" onclick="export_rewardmoney();" style="cursor:hand"> 
					</div>
				</div>
				<li style="margin-right: 60px;float:right;"><a href="javascript:history.go(-1);" class="WSY_button" style="margin-top: 0;width: 60px;height: 28px;vertical-align: middle;line-height: 28px;">返回</a></li>

			</div>
		<table width="97%" class="WSY_table WSY_t2" id="WSY_t1">
			<thead class="WSY_table_header">
				<tr>
					<th width="13%" nowrap="nowrap">订单号</th>  
					<th width="13%" nowrap="nowrap">顾客</th>
					<th width="13%" nowrap="nowrap">佣金(类型)</th>
					<th width="10%" nowrap="nowrap">佣金</th>
					<th width="10%" nowrap="nowrap">时间</th>
					<th width="20%" nowrap="nowrap">备注</th>
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
					$query="select reward,remark,createtime,paytype,batchcode,type from weixin_commonshop_order_promoters where isvalid=true and user_id=".$scene_id." order by id_new desc";
					 $result_q = mysql_query($query) or die('Query failed5: ' . mysql_error());
					$rcount_q = mysql_num_rows($result_q);
					$query = $query." limit ".$start.",".$end;
					//echo $query;
				 $result = mysql_query($query) or die('Query failed: ' . mysql_error());
					while ($row = mysql_fetch_object($result)) {
						$reward = $row->reward;
						$remark = $row->remark;
						$createtime = $row->createtime;
						$paytype = $row->paytype;
						$batchcode = $row->batchcode;
						$type = $row->type;
						$sql="select customer_red_id from weixin_red_log where isvalid=true and deal_id='".$batchcode."'";
						$result3 = mysql_query($sql) or die('Query failed: ' . mysql_error());
						while ($row3 = mysql_fetch_object($result3)) {
							$customer_red_id = $row3->customer_red_id;
						}
						
						if($paytype == 0){
							$paytpyestr= "已支付";
						}else if($paytype == 1){
							$paytpyestr= "<span style='color:red'>已到账</span>";
						}else if($paytype == 2){
							$paytpyestr= "已退货";
							$remark = "(撤销)".$remark;
						}else if($paytype == 4){
							$paytpyestr= "已退款";
							$remark = "(撤销)".$remark;
						}else{
							$paytpyestr= "<span style='color:red'>已发红包</span>";
						}
						$query2= "select user_id from weixin_commonshop_orders where isvalid=true and batchcode='".$batchcode."'"; 
						$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
						$buy_user_id=-1;
						while ($row2 = mysql_fetch_object($result2)) {
							$buy_user_id=$row2->user_id;
						}
						$query2= "select name,weixin_name,phone from weixin_users where isvalid=true and id=".$buy_user_id;
						$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
						$username="";
						$userphone="";
						$weixin_name="";
						while ($row2 = mysql_fetch_object($result2)) {
							$username=$row2->name;
							$userphone = $row2->phone;
							$weixin_name = $row2->weixin_name;
							break;
						}
						$username = $username."(".$weixin_name.")";
					?>
					<tr>
					  
					   <td>
                        <?php 
                        switch($type) {
                            case 0:
                            case 1:
                            case 2:
                            case 3:
                            case 4:
                            case 5:
                        ?>
                            <a href="../../Order/order/order.php?customer_id=<?php echo $customer_id_en; ?>&search_batchcode=<?php echo $batchcode; ?>&status=-1" style="color:#2eade8;"><?php echo $batchcode; ?>
                            </a>
                        <?php 
                            break;
                            case 6:
                            case 7:
                            case 8:
                        ?>
                            <a href="../../../back_city/Finance/orders/order.php?customer_id=<?php echo $customer_id_en; ?>&search_batchcode=<?php echo $batchcode; ?>"  style="color:#2eade8;"><?php echo $batchcode; ?>
                            </a>
                        <?php
                        	break;
                        	case 9:
                         ?> 
                         	<a href="../../Reward/GlobalBonus/globalbonus_change.php?s_batchcode=<?php echo $batchcode; ?>"  style="color:#2eade8;"><?php echo $batchcode; ?>
                            </a>
                          <?php	
                          	break;
                            case 10:
                         ?>  
                         	<a href="../../Order/order/order.php?customer_id=<?php echo $customer_id_en; ?>&search_batchcode=<?php echo $batchcode; ?>&status=-1" style="color:#2eade8;"><?php echo $batchcode; ?>
                            </a>
                         <?php
                            break;
                        }
                         ?>    
					   </td>
					   <td><?php echo $username; ?></td>
					   <td><?php echo $paytpyestr; ?>
					   <?php if($paytype==3){?>
					   </br>(<?php echo $customer_red_id?>)
					   <?php } ?>
					   </td>
					   <td><?php echo $reward; ?></td>
					   
					   <td><?php echo $createtime; ?></td>
					   <td><?php echo $remark; ?></td>
					</tr>				
				   <?php } ?>
			</tbody>
		</table>
		<div class="blank20"></div>
		<div id="turn_page"></div>
		</div>
		<!--翻页开始-->
        <div class="WSY_page">
        	
        </div>
        <!--翻页结束-->		
	</div>
</div>
	
<div style="top: 101px; position: absolute; background-color: white; z-index: 2000; left: 398px; visibility: hidden; background-position: initial initial; background-repeat: initial initial;" class="om-calendar-list-wrapper om-widget om-clearfix om-widget-content multi-1"><div class="om-cal-box" id="om-cal-4381460996810347"><div class="om-cal-hd om-widget-header"><a href="javascript:void(0);" class="om-prev "><span class="om-icon om-icon-seek-prev">Prev</span></a><a href="javascript:void(0);" class="om-title">2014年1月</a><a href="javascript:void(0);" class="om-next "><span class="om-icon om-icon-seek-next">Next</span></a></div><div class="om-cal-bd"><div class="om-whd"><span>日</span><span>一</span><span>二</span><span>三</span><span>四</span><span>五</span><span>六</span></div><div class="om-dbd om-clearfix"><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);">1</a><a href="javascript:void(0);">2</a><a href="javascript:void(0);">3</a><a href="javascript:void(0);">4</a><a href="javascript:void(0);">5</a><a href="javascript:void(0);">6</a><a href="javascript:void(0);">7</a><a href="javascript:void(0);">8</a><a href="javascript:void(0);" class="om-state-highlight om-state-nobd">9</a><a href="javascript:void(0);" class="om-state-disabled">10</a><a href="javascript:void(0);" class="om-state-disabled">11</a><a href="javascript:void(0);" class="om-state-disabled">12</a><a href="javascript:void(0);" class="om-state-disabled">13</a><a href="javascript:void(0);" class="om-state-disabled">14</a><a href="javascript:void(0);" class="om-state-disabled">15</a><a href="javascript:void(0);" class="om-state-disabled">16</a><a href="javascript:void(0);" class="om-state-disabled">17</a><a href="javascript:void(0);" class="om-state-disabled">18</a><a href="javascript:void(0);" class="om-state-disabled">19</a><a href="javascript:void(0);" class="om-state-disabled">20</a><a href="javascript:void(0);" class="om-state-disabled">21</a><a href="javascript:void(0);" class="om-state-disabled">22</a><a href="javascript:void(0);" class="om-state-disabled">23</a><a href="javascript:void(0);" class="om-state-disabled">24</a><a href="javascript:void(0);" class="om-state-disabled">25</a><a href="javascript:void(0);" class="om-state-disabled">26</a><a href="javascript:void(0);" class="om-state-disabled">27</a><a href="javascript:void(0);" class="om-state-disabled">28</a><a href="javascript:void(0);" class="om-state-disabled">29</a><a href="javascript:void(0);" class="om-state-disabled">30</a><a href="javascript:void(0);" class="om-state-disabled">31</a><a href="javascript:void(0);" class="om-null">0</a></div></div><div class="om-setime om-state-default hidden"></div><div class="om-cal-ft"><div class="om-cal-time om-state-default">时间：<span class="h">0</span>:<span class="m">0</span>:<span class="s">0</span><div class="cta"><button class="u om-icon om-icon-triangle-1-n"></button><button class="d om-icon om-icon-triangle-1-s"></button></div></div><button class="ct-ok om-state-default">确定</button></div><div class="om-selectime om-state-default hidden"></div></div></div><div style="top: 101px; position: absolute; background-color: white; z-index: 2000; left: 564px; visibility: hidden; background-position: initial initial; background-repeat: initial initial;" class="om-calendar-list-wrapper om-widget om-clearfix om-widget-content multi-1"><div class="om-cal-box" id="om-cal-8113757355604321"><div class="om-cal-hd om-widget-header"><a href="javascript:void(0);" class="om-prev "><span class="om-icon om-icon-seek-prev">Prev</span></a><a href="javascript:void(0);" class="om-title">2014年1月</a><a href="javascript:void(0);" class="om-next "><span class="om-icon om-icon-seek-next">Next</span></a></div><div class="om-cal-bd"><div class="om-whd"><span>日</span><span>一</span><span>二</span><span>三</span><span>四</span><span>五</span><span>六</span></div><div class="om-dbd om-clearfix"><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);" class="om-null">0</a><a href="javascript:void(0);">1</a><a href="javascript:void(0);">2</a><a href="javascript:void(0);">3</a><a href="javascript:void(0);">4</a><a href="javascript:void(0);">5</a><a href="javascript:void(0);">6</a><a href="javascript:void(0);">7</a><a href="javascript:void(0);">8</a><a href="javascript:void(0);" class="om-state-highlight om-state-nobd">9</a><a href="javascript:void(0);" class="om-state-disabled">10</a><a href="javascript:void(0);" class="om-state-disabled">11</a><a href="javascript:void(0);" class="om-state-disabled">12</a><a href="javascript:void(0);" class="om-state-disabled">13</a><a href="javascript:void(0);" class="om-state-disabled">14</a><a href="javascript:void(0);" class="om-state-disabled">15</a><a href="javascript:void(0);" class="om-state-disabled">16</a><a href="javascript:void(0);" class="om-state-disabled">17</a><a href="javascript:void(0);" class="om-state-disabled">18</a><a href="javascript:void(0);" class="om-state-disabled">19</a><a href="javascript:void(0);" class="om-state-disabled">20</a><a href="javascript:void(0);" class="om-state-disabled">21</a><a href="javascript:void(0);" class="om-state-disabled">22</a><a href="javascript:void(0);" class="om-state-disabled">23</a><a href="javascript:void(0);" class="om-state-disabled">24</a><a href="javascript:void(0);" class="om-state-disabled">25</a><a href="javascript:void(0);" class="om-state-disabled">26</a><a href="javascript:void(0);" class="om-state-disabled">27</a><a href="javascript:void(0);" class="om-state-disabled">28</a><a href="javascript:void(0);" class="om-state-disabled">29</a><a href="javascript:void(0);" class="om-state-disabled">30</a><a href="javascript:void(0);" class="om-state-disabled">31</a><a href="javascript:void(0);" class="om-null">0</a></div></div><div class="om-setime om-state-default hidden"></div><div class="om-cal-ft"><div class="om-cal-time om-state-default">时间：<span class="h">0</span>:<span class="m">0</span>:<span class="s">0</span><div class="cta"><button class="u om-icon om-icon-triangle-1-n"></button><button class="d om-icon om-icon-triangle-1-s"></button></div></div><button class="ct-ok om-state-default">确定</button></div><div class="om-selectime om-state-default hidden"></div></div></div>

<?php 

mysql_close($link);
?>
<script>
	function export_rewardmoney(){
     var url='/weixin/plat/app/index.php/Excel/commonshop_excel_qrsell_rewardmoney/type/<?php echo $type;?>/scene_id/<?php echo $scene_id;?>/exceltitle/<?php echo $exceltitle;?>';
	 console.log(url);
	// goExcel(url,1,'http://<?php echo $http_host;?>/weixinpl/');
	 location.href=url;
  }
</script>
<script src="../../../js/fenye/jquery.page1.js"></script>
<script>
var customer_id = '<?php echo $customer_id_en ?>';
var sum_totalprice = <?php echo $sum_totalprice ?>;
var type = <?php echo $type ?>;
var scene_id = <?php echo $scene_id ?>;
var pagenum = <?php echo $pagenum ?>;
var rcount_q2 = <?php echo $rcount_q ?>;
var end = <?php echo $end ?>;
var count = Math.ceil(rcount_q2/end);//总页数

var page = count;
  	//pageCount：总页数
	//current：当前页
	$(".WSY_page").createPage({
        pageCount:count,
        current:pagenum,
        backFn:function(p){
			
		document.location= "qrsell_rewardmoney.php?pagenum="+p+"&customer_id="+customer_id+"&sum_totalprice="+sum_totalprice+"&type="+type+"&scene_id="+scene_id;
	   }
    });

  function jumppage(){
	var a=parseInt($("#WSY_jump_page").val()); 
	if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
		return false;
	}else{
		document.location= "qrsell_rewardmoney.php?pagenum="+a+"&customer_id="+customer_id+"&sum_totalprice="+sum_totalprice+"&type="+type+"&scene_id="+scene_id;
		
	}
  }
</script>
</body></html>