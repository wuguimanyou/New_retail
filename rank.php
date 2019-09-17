<?php

header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
mysql_query("SET NAMES UTF8");
require('../../../proxy_info.php');
$head=2;//头部文件  0基本设置,1基金明细

//接受页面参数
$pagenum = 1;
$pagesize = 20;
$begintime="";
$endtime ="";
if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}
$start = ($pagenum-1) * $pagesize;
$end = $pagesize;

 if(!empty($_GET["search_id"])){
   $search_id = $configutil->splash_new($_GET["search_id"]);
}

if(!empty($_GET["search_name"])){
   $search_name = $configutil->splash_new($_GET["search_name"]);
}

$total_charitable = 0;
$query="select sum(charitable) as charitable from weixin_users where isvalid=true and customer_id=".$customer_id;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());				   
while ($row = mysql_fetch_object($result)) {
	$total_charitable = $row->charitable;
}
$total_charitable = round($total_charitable,2);  

$user_id    	   = -1;//用户id
$name        	   = "匿名";//用户名字
$weixin_name 	   = "匿名";//用户微信名字
$charitable        = "0";//用户慈善分
$weixin_headimgurl = "0";//用户头像
$charitable_level = -1; //慈善公益等级id
/*输出数据语句*/
$query = "select id,name,weixin_name,charitable,weixin_headimgurl,charitable_level from weixin_users where isvalid=true and customer_id=".$customer_id;
/*输出数据语句*/
/*统计数据数量*/
$query_num ="select count(distinct id) as wcount from weixin_users where isvalid=true and customer_id=".$customer_id;
/*统计数据数量*/
$sql = "";
 if(!empty($search_id)){			   
	$sql .= " and id like '%".$search_id."%'";
}
if(!empty($search_name)){			   
	$sql .= " and ((name like '%".$search_name."%')";
	$sql .= " or (weixin_name like '%".$search_name."%'))";
}
/*运行统计数据数量*/
$query_num .= $sql;
$result_num = mysql_query($query_num) or die('Query_num failed: ' . mysql_error());
$wcount     = 0;//数据数量
$page       = 0;//分页数
while ($row_num = mysql_fetch_object($result_num)) {
	$wcount =  $row_num->wcount ;
}			
$page=ceil($wcount/$end);
/*运行统计数据数量*/
$query .=  $sql." ORDER BY charitable DESC limit ".$start.",".$end; 
?>  
<!doctype html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<title></title>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link rel="stylesheet" type="text/css" href="../../Common/css/Mode/welfare/set.css">
<script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="../../../js/tis.js"></script>
<script type="text/javascript" src="../../../common/utility.js" charset="utf-8"></script>
<script type="text/javascript" src="../../../common/js/jquery.blockUI.js"></script>
<script charset="utf-8" src="../../../common/js/jquery.jsonp-2.2.0.js"></script>
<script type="text/javascript" src="../../../js/WdatePicker.js"></script>
<style> 
table#WSY_t1 td {
    text-align: center;
}
tr {
    line-height: 22px;
}
</style>
<title>基金明细</title>
<meta http-equiv="content-type" content="text/html;charset=UTF-8">
</head>
<body> 
	<!--内容框架-->
	<div class="WSY_content">
		<!--列表内容大框-->
		<div class="WSY_columnbox">
			<!--列表头部切换开始-->
			<?php
			include("basic_head.php"); 
			?>
			<!--列表头部切换结束-->
			<div class="WSY_remind_main">
				<dl class="WSY_remind_dl02" style="margin-left: 36px;">
					<dt style="line-height:28px;" class="WSY_left">慈善基金总额：</dt>
					<dd>
						<span style="padding-left:10px;color:red;font-size:24px;font-weight:bold">￥<?php echo $total_charitable;?></span>
					</dd>
				</dl>
				<form class="search" id="search_form" style="margin-left:18px; margin-top: 18px;">
					<div class="WSY_list" style="margin-top: 18px;">
						<li class="WSY_position_text">
							<a>会员编号：<input type="text" name="search_id" id="search_id" value="<?php echo $search_id; ?>"></a>
							<a>姓名：<input type="text" name="search_name" id="search_name" value="<?php echo $search_name; ?>"></a>
							<input type="button" class="search_btn" onclick="searchForm();" value="搜 索"> 
						</li>
						
					</div>     
				</form>	
				<table width="97%" class="WSY_table" id="WSY_t1">
					<thead class="WSY_table_header">
						<th width="20%">排名</th>
						<th width="20%">会员编号</th>
						<th width="20%">头像</th>
						<th width="20%">名称</th>
						<th width="20%">积分</th>
						<th width="20%">等级</th>
					</thead>
					<tbody>
					   <?php 
						
						$result = mysql_query($query) or die('Query failed: ' . mysql_error());				   
						$i = 0;
						while ($row = mysql_fetch_object($result)) {
						$user_id     	   = $row->id;
						$name        	   = $row->name;
						$weixin_name 	   = $row->weixin_name;
						$charitable        = $row->charitable;
						$weixin_headimgurl = $row->weixin_headimgurl;
						$charitable_level = $row->charitable_level;
						$i++;
						$username    = $name ."(".$weixin_name.")";
						$charitable_name = "无";
						$query2="select name from charitable_name_t where isvalid=true and id=".$charitable_level." and customer_id=".$customer_id." limit 0,1";
						//echo $query2;
						$result2=mysql_query($query2) or die('L883 '.mysql_error());
						while($row2=mysql_fetch_object($result2)){
							$charitable_name  = $row2->name;
						}
						
					   ?>
						<tr>
							<td><?php echo $i; ?></td>
							<td><?php echo $user_id; ?></td>
						   <td><img style="height:60px;" src="<?php echo $weixin_headimgurl; ?>"></td>
						   <td><a href="user_detail.php?customer_id=<?php echo $customer_id_en; ?>&user_id=<?php echo $user_id; ?>"><?php echo $username; ?></a></td>
						   <td><?php echo $charitable; ?></td>
						   <td><?php echo $charitable_name; ?></td>
						</tr>
					   <?php } ?>
					    					
					</tbody>					
				</table>
				<div class="blank20"></div>
				<div id="turn_page"></div>
				<!--翻页开始-->
				<div class="WSY_page">
        	
				</div>
				<!--翻页结束-->
			</div>
		</div>
	</div>

	
<script src="../../../js/fenye/jquery.page1.js"></script>
<script>
var customer_id = "<?php echo $customer_id_en;?>";
var pagenum     = <?php echo $pagenum ?>;
var count       = <?php echo $page ?>;//总页数	
</script>

<?php mysql_close($link);?>	

<script type="text/javascript" src="../../../common/js_V6.0/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script type="text/javascript" src="../../Common/js/Mode/charitable/rank.js"></script>
</body>
</html>