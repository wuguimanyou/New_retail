<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../back_init.php');

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
mysql_query("SET NAMES UTF8");

require('../proxy_info.php');


$op="";
if(!empty($_GET["op"])){
   $op=$configutil->splash_new($_GET["op"]);
   $keyid=$configutil->splash_new($_GET["keyid"]);
   if($op=="del"){
	   $sql="update weixin_commonshop_product_evaluations set isvalid=false where id=".$keyid;
	   mysql_query($sql);
   }else if($op=="status"){
       $status= $configutil->splash_new($_GET["status"]);
	   $sql="update weixin_commonshop_product_evaluations set status=".$status." where id=".$keyid;
	   mysql_query($sql);
   }
}
$new_baseurl = BaseURL."back_commonshop/";

$pid=-1;
if(!empty($_GET["pid"])){
   $pid=$configutil->splash_new($_GET["pid"]);
}

//渠道取消代理商功能
$is_distribution=0;
//代理模式,分销商城的功能项是 266
$query1="select cf.id,c.filename from customer_funs cf inner join columns c where c.isvalid=true and cf.isvalid=true and cf.customer_id=".$customer_id." and c.filename='scdl' and c.id=cf.column_id";
$result1 = mysql_query($query1) or die('Query failed: ' . mysql_error());  
$dcount= mysql_num_rows($result1);
if($dcount>0){
   $is_distribution=1;
}

//渠道取消供应商功能
$is_supplierstr=0;
//供应商模式,渠道开通与不开通
$query1="select cf.id,c.filename from customer_funs cf inner join columns c where c.isvalid=true and cf.isvalid=true and cf.customer_id=".$customer_id." and c.filename='scgys' and c.id=cf.column_id";
$result1 = mysql_query($query1) or die('Query failed: ' . mysql_error());  
$dcount= mysql_num_rows($result1);
if($dcount>0){
   $is_supplierstr=1;
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>产品评价</title>
<link rel="stylesheet" type="text/css" href="../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../common/css_V6.0/content<?php echo $theme; ?>.css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../css/inside.css" media="all">
<script type="text/javascript" src="../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="../common/js/inside.js"></script>
</head>

<body>

	<div class="WSY_content">

		<div class="WSY_columnbox">

			<div class="WSY_column_header">
				<div class="WSY_columnnav">
					<a href="base.php?customer_id=<?php echo $customer_id_en; ?>">基本设置</a>
					<a href="fengge.php?customer_id=<?php echo $customer_id_en; ?>">风格设置</a>
					<a href="defaultset.php?customer_id=<?php echo $customer_id_en; ?>&default_set=1">首页设置</a>
					<a class="white1" href="product.php?customer_id=<?php echo $customer_id_en; ?>">产品管理</a>
					<a href="order.php?customer_id=<?php echo $customer_id_en; ?>&status=-1">订单管理</a> 
					<?php if($is_supplierstr){?><a href="supply.php?customer_id=<?php echo $customer_id_en; ?>">供应商</a><?php }?>
					<?php if($is_distribution){?><a href="agent.php?customer_id=<?php echo $customer_id_en; ?>">代理商</a><?php }?>
					<a href="qrsell.php?customer_id=<?php echo $customer_id_en; ?>">推广员</a>
					<a href="customers.php?customer_id=<?php echo $customer_id_en; ?>">顾客</a>
					<a href="shops.php?customer_id=<?php echo $customer_id_en; ?>">门店</a>
				</div>
			</div>

		<div class="WSY_data">

			<div class="WSY_list" id="WSY_list" style="min-height: 500px;">
				<div class="WSY_left" ><a>产品评价</a>
				</div>
				
				<ul class="WSY_righticon">
					<!--<li class="WSY_inputicon"><input type="button" value="批量删除"></li>-->
				</ul>
				<br class="WSY_clearfloat";>

        <table width="97%" class="WSY_table WSY_t2" id="WSY_t1">
          <thead class="WSY_table_header">
				<th width="3%"><input id="s" onclick="$(this).attr('checked')?checkAll():uncheckAll()" type="checkbox"></th>
				<td width="7%" nowrap="nowrap" align="center">序号</td>
				<td width="8%" nowrap="nowrap" align="center">评论者</td>
				<td width="10%" nowrap="nowrap" align="center">订单号</td>
				<td width="8%" nowrap="nowrap" align="center">评论级别</td>
				<td width="8%" nowrap="nowrap" align="center">类型</td>
				<td width="25%" nowrap="nowrap" align="center">评论</td>
				<td width="25%" nowrap="nowrap" align="center">图片</td>
				<td width="10%" nowrap="nowrap" align="center">时间</td>
				<td width="6%" nowrap="nowrap" align="center">状态</td>
				<td width="10%" nowrap="nowrap" class="last">操作</td>
          </thead>
			<?php

				$pagenum = 1;

				if(!empty($_GET["pagenum"])){
				   $pagenum = $configutil->splash_new($_GET["pagenum"]);
				}

				$start = ($pagenum-1) * 20;
				$end = 20; 
				
				$query_page = 'SELECT count(1) as wcount FROM weixin_commonshop_product_evaluations where isvalid=true and product_id='.$pid;
				$result_page = mysql_query($query_page) or die('Query_page failed: ' . mysql_error());
				$wcount =0;
				$page=0;
				while ($row_page = mysql_fetch_object($result_page)) {
					$wcount =  $row_page->wcount ;
				}			
				$page=ceil($wcount/$end);				
				
				$query2="select id,user_id,status,discuss,level,createtime,discussimg,type,batchcode from weixin_commonshop_product_evaluations where isvalid=true and product_id=".$pid;
				
				$query2=$query2." order by batchcode desc,id desc limit ".$start.",".$end;
				$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
				$rcount_q = mysql_num_rows($result2);
				while ($row2 = mysql_fetch_object($result2)) {
				   $d_id = $row2->id;
				   $user_id = $row2->user_id;
				   $level = $row2->level;
				   $discuss = $row2->discuss;
				   $createtime = $row2->createtime;
				   $status = $row2->status;
				   $discussimg = $row2->discussimg;
				   $type = $row2->type;
				   $batchcode = $row2->batchcode;
				   $img_array = explode(",", $discussimg); 
				   $statusname="无效";
				   $typename="未知";
				   switch($type){
					  case 1:
						  $typename="普通";
						 break;
					  case 2:
						 $typename="追加";
				   }		   
				   if($status==1){
					  $statusname="有效";
				   }
				   $levelname="好评";
				   switch($level){
					  case 2:
						  $levelname="中评";
						 break;
					  case 3:
						 $levelname="差评";
						 break;
				   }
				   $query3="select name,weixin_headimgurl,weixin_name from weixin_users where isvalid=true and id=".$user_id;
				   $result3 = mysql_query($query3) or die('Query failed: ' . mysql_error());
				   $username="";
				   $headimgurl="";
				   while ($row3 = mysql_fetch_object($result3)) {
					  $username=$row3->name;
					  $headimgurl = $row3->weixin_headimgurl;
					  $weixin_name=$row3->weixin_name;
					  break;
				   }
				   if(empty($username)){
					  $username = $weixin_name;
				   }

			?>
			<tr class="WSY_q1">
				<td><input type="checkbox" name="code_Value" value="<?php echo $d_id; ?>"></td>
				<td align="center"><?php echo $d_id; ?></td>
				<td align="center"><?php echo $username; ?></td>
				<td align="center"><?php echo $batchcode; ?></td>
				<td align="center"><?php echo $levelname; ?></td>
				<td align="center"><?php echo $typename; ?></td>
				<td align="center"><?php echo $discuss; ?></td>
				<td align="center">
				<?php  
					if(!empty($discussimg)){ 
							foreach($img_array as $key=>$value){
							$value=substr($value,3);
							echo '<a href="'.$value.'"><img style="width:50px;height:50px;vertical-align:middle;padding: 2px 5px;" src="'.$value.'"></img></a>';			
							}	
					}else{ echo '无'; } 
				?>			
				</td>
				<td align="center"><?php echo $createtime; ?></td>
				<td align="center"><?php echo $statusname; ?></td>
										
				<td class="WSY_t4">
				
					 <?php if($status==0){?>	
					<a href="discuss.php?op=status&keyid=<?php echo $d_id; ?>&pid=<?php echo $pid; ?>&status=1" style="cursor:pointer;" class="WSY_operation" title="确认"><img src="../common/images_V6.0/operating_icon/icon23.png"></a>					
					<?php }else{ ?>
					
					<a href="discuss.php?op=status&keyid=<?php echo $d_id; ?>&pid=<?php echo $pid; ?>&status=1" style="cursor:pointer;" class="WSY_operation" title="商家回复"><img src="../common/images_V6.0/operating_icon/icon11.png"></a>		
					
					<a href="discuss.php?op=status&keyid=<?php echo $d_id; ?>&pid=<?php echo $pid; ?>&status=0" style="cursor:pointer;" class="WSY_operation" title="取消确认"><img src="../common/images_V6.0/operating_icon/icon26.png"></a>
					<?php } ?>
					<a href="javascript: G.ui.tips.confirm('您确定删除吗(删除后不可恢复)？','discuss.php?customer_id=<?php echo $customer_id_en; ?>&keyid=<?php echo $d_id; ?>&op=del&pid=<?php echo $pid; ?>');" title="删除"><img src="../common/images_V6.0/operating_icon/icon04.png"></a> 				
				</td>
          </tr>
		  <?php
  
			}

			mysql_close($link);
			?>
        </table>
        <!--表格结束-->
        
        <!--翻页开始-->
        <div class="WSY_page">
        	
        </div>
        <!--翻页结束-->
        </div>
		</div>
		</div>
		<div style="width:100%;height:20px;"></div>
	</div>

<script src="../js/fenye/jquery.page1.js"></script>
<script>

  var pagenum = <?php echo $pagenum ?>;
  var count =<?php echo $page ?>;//总页数
  	//pageCount：总页数
	//current：当前页
	$(".WSY_page").createPage({
        pageCount:count,
        current:pagenum,
        backFn:function(p){
		 document.location= "discuss.php?pagenum="+p+"&customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&pid=<?php echo $pid; ?>";
	   }
    });

  function jumppage(){
	var a=parseInt($("#WSY_jump_page").val()); 
	if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
		return false;
	}else{
	document.location= "discuss.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&pid=<?php echo $pid; ?>&pagenum="+a;
	}
  }
  
</script>	

</body>
</html>
