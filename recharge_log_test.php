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
      		<span>会员编号：</span>
      		<input type="text" name="promoter" id="promoter_num" value="<?php echo $user_id;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">
      	<!-- 	<span style="margin-left:20px;">会员卡编号：</span>
      		<input type="text" name="card_num" id="card_member_id" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;"> -->
			<input type="submit" id="my_search" >
		</div>
		<div class="WSY_position1" style="float:left">
			<ul>		
				<li class="WSY_position_date tate001" >
					<p>时间：<input class="date_picker" type="text" name="AccTime_E" id="begintime" value="<?php echo $begintime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
					<p style="margin-left:0px;">&nbsp;&nbsp;-&nbsp;&nbsp;<input class="date_picker" type="text" name="AccTime_B" id="endtime" value="<?php echo $endtime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
				</li>				
			</ul>
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
					<th width="6%">姓名</th>
					<th width="6%">绑定手机</th>
					<th width="6%">设计金额</th>
					<th width="6%">进出账</th>
					<th width="6%">订单号</th>			
					<th width="8%">时间</th>
					<th width="10%">备注信息</th>
				</tr>
			</thead>
			<tbody>
				<tr style="border:1px solid #D8D8D8" id="demo">
					<td v-repeat="items">{{massage}}</td>				
				</tr>

			
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
</body>
<script type="text/javascript" src="./vue.js"></script>
<script type="text/javascript">
	var demo = new Vue({
	  el: '#demo',
	  data: {
	    //parentMsg: 'Hello',
	    items: [
	      { massage: 'Foo' },
	      { massage: 'Bar' }
	    ]
	  }
	})
</script>
</html>
