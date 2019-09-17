<?php
header("Content-type: text/html; charset=utf-8"); //test
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../back_init.php');
require('../common/utility.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require('../proxy_info.php');
require('../auth_user.php');
require('../common/utility_4m.php');
mysql_query("SET NAMES UTF8");
if(!empty($_SESSION['auth_style'])){
    $auth_style=explode(',',$_SESSION['auth_style']);
	if($_GET['default_set']==""){
	switch($auth_style[0]){
			case 1:
				header("Location: base.php?customer_id=".$customer_id_en."");exit;
			break;
			case 2:
				header("Location: fengge.php?customer_id=".$customer_id_en."");exit;
			break;
			case 4:
				header("Location: product.php?customer_id=".$customer_id_en."");exit;
			break;
			case 5:
				header("Location: order.php?customer_id=".$customer_id_en."");exit;
			break;
			case 6:
				header("Location: supply.php?customer_id=".$customer_id_en."");exit;
			break;
			case 7:
				header("Location: agent.php?customer_id=".$customer_id_en."");exit;
			break;
			case 8:
				header("Location: qrsell.php?customer_id=".$customer_id_en."");exit;
			break;
			case 9:
				header("Location: customers.php?customer_id=".$customer_id_en."");exit;
			break;
			case 10:
				header("Location: publicwelfare.php?customer_id=".$customer_id_en."");exit;
			break;
		}
	}
}
$query = "select id,template_id,index_bg,stock_remind,isOpenPublicWelfare from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
$template_id=-1;
$index_bg = "";
$stock_remind = 1;
$isOpenPublicWelfare = 0;
while ($row = mysql_fetch_object($result)) {
	$template_id = $row->template_id;
	$index_bg = $row->index_bg;
	$stock_remind = $row->stock_remind;
	$isOpenPublicWelfare = $row->isOpenPublicWelfare;
}
if($template_id<0){
   $query ="insert weixin_commonshops(name,email,need_express,need_email,template_id,isvalid,customer_id,createtime) values('','',false,false,1,true,".$customer_id.",now())";
   mysql_query($query);
   $template_id=1;
}
$typeLst = new ArrayList();
$query="select id,name from weixin_commonshop_types where isvalid=true and customer_id=".$customer_id;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
   $pt_id = $row->id;
   $pt_name = $row->name;
   
   $pstr = $pt_id."_".$pt_name;
   $typeLst->add($pstr);
}
$typesize = $typeLst->size();

//图文信息
$imginfoLst = new ArrayList();
$query = 'SELECT id,title FROM weixin_subscribes where isvalid=true and parent_id=-1 and customer_id='.$customer_id;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
	  $sub_id =  $row->id ;
	  $title = $row->title;
	  
	  $pstr = $sub_id."_".$title;
      $imginfoLst->add($pstr);
}
$imginfosize = $imginfoLst->size();

$op = "";
if(!empty($_GET["op"])){
   $op = $configutil->splash_new($_GET["op"]);
   if($op=="del"){
       //删除banner
	   $position = $configutil->splash_new($_GET["position"]);
	   $b_imgurl = $configutil->splash_new($_GET["b_imgurl"]);
       $query="select id,imgurl from weixin_commonshop_template_item_imgs where isvalid=true and template_id=".$template_id." and position=".$position." and customer_id=".$customer_id;	
	   $result = mysql_query($query) or die('Query failed: ' . mysql_error());
	   $ti_id=-1;
	   $imgurl_tmp = "";
	   while ($row = mysql_fetch_object($result)) {
		  $ti_id = $row->id;
		  $imgurl = $row->imgurl;
		  break;
	   }
	   $imgurlarr = explode("|*|",$imgurl);
	   $len = count($imgurlarr);
	   for($i=0;$i<$len;$i++){
	       $imgurl = $imgurlarr[$i];
		   if($imgurl!=$b_imgurl){
		       $imgurl_tmp = $imgurl_tmp.$imgurl;
		   }
		   if($i<$len-1){
		      $imgurl_tmp = $imgurl_tmp."|*|";
		   }
	   }
	   $query="update weixin_commonshop_template_item_imgs set imgurl='".$imgurl_tmp."' where id=".$ti_id;
	   mysql_query($query);
   }else if($op=="del_2"){
       $position = $configutil->splash_new($_GET["position"]);
	   $position++;
	   $query="select id,imgurl from weixin_commonshop_template_item_imgs where isvalid=true and template_id=".$template_id." and position=".$position." and customer_id=".$customer_id;	
	   $result = mysql_query($query) or die('Query failed: ' . mysql_error());
	   $ti_id=-1;
	   $imgurl_tmp = "";
	   while ($row = mysql_fetch_object($result)) {
		  $ti_id = $row->id;
		  $imgurl = $row->imgurl;
		  break;
	   }
	   $query="update weixin_commonshop_template_item_imgs set isvalid=false where id=".$ti_id;
	   mysql_query($query);
	   
   }
}
//是否是总部商店

$u4m = new Utiliy_4m();
$rearr = $u4m->is_4M($customer_id);

//是4m分销
$is_shopgeneral = $rearr[0]  ;
//厂家编号
$adminuser_id = $rearr[1] ;
//是否是厂家总店
$is_samelevel = $rearr[2] ;
//总店模板编号
$general_template_id = $rearr[3] ;
//总店商家编号
$general_customer_id = $rearr[4] ;

//1：厂家总店； 2：代理商总店
$owner_general = $rearr[5] ;
//直属代理商编号
$orgin_adminuser_id = $rearr[6] ;

if($orgin_adminuser_id>0 and $is_shopgeneral and $general_template_id>0){
	   //查找总部商店

		   //是总部商家，则更新所有下级商城的模板编号
   $query="update weixin_commonshops set template_id=".$general_template_id." where customer_id in (select id from customers where isvalid=true and adminuser_id =".$orgin_adminuser_id.")";
   mysql_query($query);
   $template_id = $general_template_id;
		
   
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

$query="select sum(totalprice) as today_totalprice from weixin_commonshop_orders where paystatus=1 and sendstatus!=4 and isvalid=true and customer_id=".$customer_id." and year(paytime)=".$year." and month(paytime)=".$month." and day(paytime)=".$day;
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

$query="select count(1) as new_qr_count from promoters where status=1 and  isvalid=true and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $new_qr_count = $row->new_qr_count;
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
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<title></title>
<script>
var template_id = <?php echo $template_id; ?>;
</script>
<link href="css/global.css" rel="stylesheet" type="text/css">
<link href="css/main.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="../common/js/jquery-1.7.2.min.js"></script>
<script charset="utf-8" src="../common/js/jquery.jsonp-2.2.0.js"></script>
<script type="text/javascript" src="js/global.js"></script>

<script>

function changeProductType(selv){
  //var selv =  sel.value;
  //alert(selv);
//  alert('==selv='+selv);
  document.getElementById("div_products_2").style.display="none";
  if(selv.indexOf("_1")!=-1){
     //是产品分类
	 document.getElementById("div_products_2").style.display="block";
	 var pro_typeid= selv.substring(0,selv.indexOf("_1"));
	 url='get_product_list.php?callback=jsonpCallback_get_product_list&type_id='+pro_typeid;
     $.jsonp({
		url:url,
		callbackParameter: 'jsonpCallback_get_product_list'
	});
  }
}

function changeProductType_txt_orgi(selv){
  //var selv =  sel.value;
  //alert(selv);
 document.getElementById("div_products_3").style.display="none";
  if(selv.indexOf("_1")!=-1){
     //是产品分类
	 document.getElementById("div_products_3").style.display="block";
	 var pro_typeid= selv.substring(0,selv.indexOf("_1"));
	 url='get_product_list.php?callback=jsonpCallback_get_product_list_txt&type_id='+pro_typeid;
     $.jsonp({
		url:url,
		callbackParameter: 'jsonpCallback_get_product_list_txt'
	});
  }
}




var p_detail_id = -1;
var p_detail_pos= -1;

function changeProductType2(pro_typeid,d_id){
  
	 p_detail_id = d_id;
	 //是产品分类
	 url='get_product_list.php?callback=jsonpCallback_get_product_list2&type_id='+pro_typeid;
	 $.jsonp({
		url:url,
		callbackParameter: 'jsonpCallback_get_product_list2'
	});
	
 // }
}

function changeProductType_txt(pro_typeid,d_id){
  
	 p_detail_id = d_id;
	 //是产品分类
	 url='get_product_list.php?callback=jsonpCallback_get_product_list_txt&type_id='+pro_typeid;
	 $.jsonp({
		url:url,
		callbackParameter: 'jsonpCallback_get_product_list_txt'
	});
	
 // }
}

function changeProductType3(pro_typeid,d_id,m){
  
     if(m>0){
	 
		 p_detail_id = d_id;
		 p_detail_pos = m;
		 //是产品分类
		 url='get_product_list.php?callback=jsonpCallback_get_product_list3&type_id='+pro_typeid+'&pos='+p_detail_pos+'&pid='+p_detail_id;
		 $.jsonp({
			url:url,
			callbackParameter: 'jsonpCallback_get_product_list3'
		});
	}
 // }
}


function jsonpCallback_get_product_list2(results){
   var len = results.length;
   
   var sel_pro = document.getElementById("product_detail_id_2");
   sel_pro.options.length=0;
   
    var new_option = new Option("---请选择一个产品---",-1);
    sel_pro.options.add(new_option);
   for(i=2;i<len;i++){
      var pid = results[i].pid;
	  var pname = results[i].pname;
	  
	  var new_option = new Option(pname,pid);
       sel_pro.options.add(new_option);
	  if(pid==p_detail_id){
	     new_option.selected=true;
	  }
   }

}



function jsonpCallback_get_product_list3(results){
   var len = results.length;
   // alert('p_detail_pos======'+p_detail_pos);
   var pos = results[0].pos;
   var did = results[1].pid;
   var sel_pro = document.getElementById("product_detail_id_1_"+pos);
   sel_pro.options.length=0;
   
    var new_option = new Option("---请选择一个产品---",-1);
    sel_pro.options.add(new_option);
   for(i=2;i<len;i++){
      var pid = results[i].pid;
	  var pname = results[i].pname;
	  
	  var new_option = new Option(pname,pid);
       sel_pro.options.add(new_option);
	  if(pid==did){
	     new_option.selected=true;
	  }
   }

}   

   



</script>
<style type="text/css" media="screen">
#HomeFileUploadUploader {visibility:hidden}#HomeFileUpload_0Uploader {visibility:hidden}#HomeFileUpload_1Uploader {visibility:hidden}#HomeFileUpload_2Uploader {visibility:hidden}#HomeFileUpload_3Uploader {visibility:hidden}#HomeFileUpload_4Uploader {visibility:hidden}

</style></head>

<body>
<style type="text/css">body, html{background:url(images/main-bg.jpg) left top fixed no-repeat;}</style>

<div class="div_line">
		   <div class="div_line_item" onclick="show_newOrder(<?php echo $customer_id_en; ?>);">
		      今日订单: <span style="padding-left:10px;font-size:18px;font-weight:bold"><?php echo $new_order_count; ?></span>
		   </div>
		   <div class="div_line_item_split"></div>
		   <div class="div_line_item"  onclick="show_todayMoney(<?php echo $customer_id_en; ?>);">
		      今日销售: <span style="padding-left:10px;color:red;font-size:18px;font-weight:bold">￥<?php echo $today_totalprice; ?></span>
		   </div>
		   <div class="div_line_item_split"></div>
		   <div class="div_line_item"  onclick="show_newCustomer(<?php echo $customer_id_en; ?>);">
		       新增客户: <span style="padding-left:10px;font-size:18px;font-weight:bold"><?php echo $new_customer_count; ?></span>
		   </div>
		   <div class="div_line_item_split"></div>
		   <div class="div_line_item"  onclick="show_newQrsell(<?php echo $customer_id_en; ?>);">
		      新增推广员: <span style="padding-left:10px;font-size:18px;font-weight:bold"><?php echo $new_qr_count; ?></span>
		   </div>
		   <div class="div_line_item_split"></div>
		   <?php
  		    $stock_mun=0;
			$stock_pidarr="";
			$query_stock1="select id from weixin_commonshop_products where isvalid=true and storenum<".$stock_remind." and isout=0 and customer_id=".$customer_id;
			//echo $query_stock1;
			$result_stock1 = mysql_query($query_stock1) or die('Query failed: ' . mysql_error());
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
			$result_stock2 = mysql_query($query_stock2) or die('Query failed: ' . mysql_error());
			$stock_mun2=0;
			while ($row_stock2 = mysql_fetch_object($result_stock2)) {
				$stock_pid = $row_stock2->id;			
				$stock_storenum = $row_stock2->storenum;			
				$stock_propertyids = $row_stock2->propertyids;			
				if(!empty($stock_propertyids)){
				   $query_stock3="SELECT * FROM weixin_commonshop_product_prices WHERE storenum<".$stock_remind." and product_id='".$stock_pid."' limit 0,1";
				   //echo  $query_stock3;
				   $result_stock3 = mysql_query($query_stock3) or die('Query failed: ' . mysql_error());
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
		   <div class="div_line_item"  onclick="show_stock(<?php echo $customer_id_en; ?>,'<?php echo $stock_pidarr; ?>');">
		      库存提醒: 已有<span style="padding-left:10px;color:red;font-size:18px;font-weight:bold"><?php echo $stock_mun; ?></span>个商品库存不足了
		   </div>
		</div>
<div id="iframe_page">
<div class="iframe_content">
	
<script type="text/javascript" src="js/shop.js"></script>
	<div class="r_nav">
	   <ul>
			<li id="auth_page0" class=""><a href="base.php?customer_id=<?php echo $customer_id_en; ?>">基本设置</a></li>
			<li id="auth_page1" class=""><a href="fengge.php?customer_id=<?php echo $customer_id_en; ?>">风格设置</a></li>
			<li id="auth_page2" class="cur"><a href="defaultset.php?customer_id=<?php echo $customer_id_en; ?>&default_set=1">首页设置</a></li>
			<li id="auth_page3" class=""><a href="product.php?customer_id=<?php echo $customer_id_en; ?>">产品管理</a></li>
			<li id="auth_page4" class=""><a href="order.php?customer_id=<?php echo $customer_id_en; ?>&status=-1">订单管理</a></li>
			<?php if($is_supplierstr){?><li id="auth_page5" class=""><a href="supply.php?customer_id=<?php echo $customer_id_en; ?>">供应商</a></li><?php }?>
			<?php if($is_distribution){?><li id="auth_page6" class=""><a href="agent.php?customer_id=<?php echo $customer_id_en; ?>">代理商</a></li><?php }?>
			<li id="auth_page7" class=""><a href="qrsell.php?customer_id=<?php echo $customer_id_en; ?>">推广员</a></li>
			<li id="auth_page8" class=""><a href="customers.php?customer_id=<?php echo $customer_id_en; ?>">顾客</a></li>
			<li id="auth_page9"><a href="shops.php?customer_id=<?php echo $customer_id_en; ?>">门店</a></li>
			<?php if($isOpenPublicWelfare){?><li id="auth_page10"><a href="publicwelfare.php?customer_id=<?php echo $customer_id_en; ?>">公益基金</a></li><?php }?>
	   </ul>
	</div>
<link href="css/style.css" rel="stylesheet" type="text/css">
<link href="css/operamasks-ui.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/lean-modal.min.js"></script>
<script type="text/javascript" src="js/operamasks-ui.min.js"></script>

<script language="javascript">

  <?php 
  $general_slider_num=5;
  $logo_url="";
  $img_1="";
  $img_2="";
  $img_3="";
  $img_4="";
  $img_5="";
  $img_6="";
  $img_7="";
  $img_8="";
  $img_9="";
  $img_10="";
  $img_11="";
  $img_12="";
  $img_13="";
  $img_14="";
  $img_15="";
  //先取默认图片
  echo "var shop_skin_data=[";
  $query="select id,imgurl,default_imgurl,contenttype,title,width,height,position,needlink,url,linktype,foreign_id from weixin_commonshop_template_imgs where isvalid=true  and template_id=".$template_id;

  $result = mysql_query($query) or die('Query failed: ' . mysql_error());
  while ($row = mysql_fetch_object($result)) {
	  $id=$row->id;
	  $imgurl=$row->imgurl;
	  
	  $position = $row->position;
	  $contenttype=$row->contenttype;
	  $title = $row->title;
	  $width = $row->width;
	  $height = $row->height;
	  
	  $needlink = $row->needlink;
	  $url=$row->url;
	  $linktype = $row->linktype;
	  $foreign_id = $row->foreign_id;
	  $default_imgurl=$row->default_imgurl;
	  //if(empty($imgurl) or $imgurl==""){
	     $imgurl= $default_imgurl;
	  //}
	  $detail_id=-1;
	  //如果客户已经替换了图片，则用客户的图片
	  $query2="select id,imgurl,position,url,linktype,foreign_id,title,detail_id,video_link from weixin_commonshop_template_item_imgs where isvalid=true  and template_id=".$template_id." and customer_id=".$customer_id." and position=".$position;
	  $result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
	  while ($row2 = mysql_fetch_object($result2)) {
	  
		  $id=$row2->id;
		  $imgurl=$row2->imgurl;
		  $url=$row2->url;
		  $linktype = $row2->linktype;
		  $foreign_id = $row2->foreign_id;
		  $title = $row2->title;
		  $detail_id = $row2->detail_id;
		  $video_link=$row2->video_link;
	  }
	  //如果客户已经加了字体颜色
	  $query3="select font_color from weixin_commonshop_type_font where isvalid=true and template_id=".$template_id." and font_id=".$id;
	  $result3 = mysql_query($query3) or die('Query failed: ' . mysql_error());
	  $font_color="000000";
	  while ($row3 = mysql_fetch_object($result3)) {
		   $font_color=$row3->font_color;
	  }
	  
	  switch($position){
		  case 1:
			$img_1 = $imgurl;
			break;
		  case 2:
			$img_2 = $imgurl;
			break;
		  case 3:
			$img_3 = $imgurl;
			break;
		  case 4:
		   $img_4 = $imgurl;
			break;
		  case 5:
			$img_5 = $imgurl;
			break;
		  case 6:
			$img_6 = $imgurl;
			break;
		 case 7:
			$img_7 = $imgurl;
			break;
		  case 8:
			$img_8 = $imgurl;
			break;
		  case 9:
			$img_9 = $imgurl;
			break;
		  case 10:
			$img_10 = $imgurl;
			break;
		  case 11:
			$img_11 = $imgurl;
			break;
		  case 12:
			$img_12 = $imgurl;
			break;
		  case 13:
			$img_13 = $imgurl;
			break;
		  case 14:
			$img_14 = $imgurl;
			break;
		  case 15:
			$img_15 = $imgurl;
			break;

	  }
	  
	  echo "{\"PId\":\"".$id."\"";
	  echo ",\"SId\":\"".$template_id."\"";
	  echo ",\"MemberId\":\"".$customer_id."\"";
	  echo ",\"ContentsType\":\"".$contenttype."\"";
	  echo ",\"Title\":\"".$title."\"";
	  echo ",\"Video_link\":\"".$video_link."\"";
	  //输出文字颜色
	  echo ",\"font_color\":\"".$font_color."\"";
	  
	  $general_imgurl = "";	  
	  $general_url = "";	  
	  $general_linktype = "";	  
	  //if($template_id==9 and $is_shopgeneral and $general_customer_id>0){
	  if($is_shopgeneral and $general_customer_id>0 and ($template_id==37 or $template_id==6) and $is_samelevel==0){
        //查找总部幻灯片,并且是非厂家的总店
		
		$query2="select id,imgurl,position,url,linktype,foreign_id,title from weixin_commonshop_template_item_imgs where isvalid=true  and template_id=".$template_id." and (customer_id=".$general_customer_id.") and position=".$position." limit 0,5";
		
		
		$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
		while ($row2 = mysql_fetch_object($result2)) {
		  
			  $general_imgurl=$row2->imgurl;
			  //$general_url=$row2->url;
			 // echo "url=======".$url."\r\n";
			  //$general_linktype = $row2->linktype;
		 }
		
		 if(!empty($general_imgurl)){
		     //$imgurl  = $general_imgurl."|*|".$imgurl;
			 if($contenttype==1){
				 /*$imgarr = explode("|*|",$general_imgurl);
				 $icount = count($imgarr);


				 $str="";
				 $general_slider_num = $icount;
				 for($i=0;$i<$icount; $i++){
					$img = $imgarr[$i];
					$str = $str.$img."|*|";
				 }*/
				 //$general_imgurl = rtrim($str,"|*|");
				 if(empty($imgurl)){
					 $imgurl = $general_imgurl;	
				 }else{
				     $imgurl = $imgurl."|*|".$general_imgurl;	
				 }
			 }else{
			     if(!empty($general_imgurl)){
				    $imgurl = $general_imgurl;
				 }
			 }
		  }
		  if(!empty($url)){
		      //$url = $url."|*|".$general_url;
		  }
		  if(!empty($url)){
		      //$linktype = $linktype."|*|".$general_linktype;
		  }
	  }
	  
	  
	  echo ",\"ImgPath\":\"".$imgurl."\"";
	  echo ",\"Url\":\"".$url."\"";
	  echo ",\"linktype\":\"".$linktype."\"";
	  echo ",\"foreign_id\":\"".$foreign_id."\"";
	  echo ",\"detail_id\":\"".$detail_id."\"";
	  echo ",\"Postion\":\"".$position."\"";
	  echo ",\"Width\":\"".$width."\"";
	  echo ",\"Height\":\"".$height."\"";
	  echo ",\"NeedLink\":\"".$needlink."\"";
	  echo "},";
  }
  echo "];";
  
  ?>

</script>
<script language="javascript">

  $(document).ready(shop_obj.home_init);
</script>
<div id="home" class="r_con_wrap" style="display:block;overflow:hidden;">

 <div class="m_lefter">
   <script type="text/javascript">
    var skin_index_init=function(){
	   $('#shop_skin_index .menu .nav a.category').click(function(){
		if($('#category').height()>$(window).height()){
			$('html, body, #cover_layer').css({
				height:$('#category').height(),
				width:$(window).width(),
				overflow:'hidden'
			});
		}else{
			$('#category, #cover_layer').css('height', $(window).height());
			$('html, body').css({
				height:$(window).height(),
				overflow:'hidden'
			});
		}
		$('#cover_layer').show();
		$('#category').animate({left:'0%'}, 500);
		$('#shop_page_contents').animate({margin:'0 -70% 0 70%'}, 500);
		window.scrollTo(0);
		
		return false;
	});
}
</script>


<?php
  if($template_id==1){
?>
<link href="css/shop.css" rel="stylesheet" type="text/css">
<link href="css/index.css" rel="stylesheet" type="text/css">


 <div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="header">
    	<div class="shop_skin_index_list logo" rel="edit-t01" no="0">
        	<div class="img">
			  <img src="<?php echo $img_1; ?>">
			</div>
			<div class="mod" style="display: none;">&nbsp;</div>
        </div>
        <div class="login"><a href="#" style="cursor: default; text-decoration: none;">我的订单</a></div>
        <div class="clear"></div>
        <div class="search">
            <form action="#" method="get">
                <input type="text" name="Keyword" class="input" value="" placeholder="输入商品名称...">
                <input type="submit" class="submit" value=" ">
            </form>
        </div>
    </div>
    <div class="shop_skin_index_list banner" rel="edit-t02" no="1">
        <div class="img"><img src="<?php echo $img_2; ?>"></div>
		<div class="mod" style="display: none;">&nbsp;</div>
    </div>
    <div class="menu">

    	<ul class="nav">
        	<li>
			 	<a href="#" class="category" style="cursor: default; text-decoration: none;">
                	
					<div class="shop_skin_index_list" rel="edit-t07" no="6" iscate="1">
					<div class="img"></div>
					</div>
                    <div class="name shop_skin_index_list" rel="edit-t11" no="10">
						<div class="div_typename"></div>
					</div>
                </a>
            </li>
        	<li>
            	<a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t08" no="7"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t12" no="11">
						<div class="div_typename"></div>
					</div>
                </a>
            </li>
        	<li>
            	<a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t09" no="8"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t13" no="12">
						<div class="div_typename"></div>
					</div>
                </a>
            </li>
        	<li>
            	<a href="#" style="cursor: default; text-decoration: none;">
                	<div class="shop_skin_index_list" rel="edit-t10" no="9"  iscate="1">
					  <div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t14" no="13">
						<div class="div_typename"></div>
					</div>
                </a>
            </li>
        </ul>
        <div class="blank9"></div>
        <ul class="ad">
        	<li><div class="shop_skin_index_list" rel="edit-t03" no="2">
			<div class="img"><img src="<?php echo $img_3; ?>"></div>
			<div class="mod" style="display: none;">&nbsp;</div>
			</div>
			</li>
        	<li><div class="shop_skin_index_list" rel="edit-t04" no="3"><div class="img"><img src="<?php echo $img_4; ?>"></div><div class="mod" style="display: none;">&nbsp;</div></div></li>
        	<li><div class="shop_skin_index_list" rel="edit-t05" no="4"><div class="img"><img src="<?php echo $img_5; ?>"></div><div class="mod" style="display: none;">&nbsp;</div></div></li>
        </ul>
        <div class="clear"></div>
    </div>
    <div class="line"></div>
        <div class="box">
        <div class="shop_skin_index_list ad" rel="edit-t06" no="5"><div class="img"><img src="<?php echo $img_6; ?>"></div>
		<div class="mod" style="display: none;">&nbsp;</div><div id="SetHomeCurrentBox" style="height: 190px; width: 193px;"></div></div>
        <div class="ad_r">
        	            	<div class="item">
                	<a href="#" style="cursor: default; text-decoration: none;">
                        <div class="img"><img src=""></div>
                        <div class="name">aa</div>
                    </a>
                </div>
                    </div>
        <div class="clear"></div>
                            </div>
    </div>

<?php }else if($template_id==2){?>
  <link href="lingshi/css/shop.css" rel="stylesheet" type="text/css">
  <link href="lingshi/css/index.css" rel="stylesheet" type="text/css">
  <div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="header">
        <div class="shop_skin_index_list logo" rel="edit-t01" no="0">
            <div class="img"><img src="logo.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
            <div id="SetHomeCurrentBox" style="height: 40px; width: 120px;"></div>
		</div>
        <div class="search">
            <form action="" method="get">
                <input type="text" name="Keyword" class="input" value="" placeholder="输入商品名称...">
                <input type="submit" class="submit" value=" ">
            </form>
        </div>
    </div>
    <div class="menu">
    	<ul>
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t04" no="3"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t08" no="7">
						<div class="div_typename"></div>
					</div>
                </a>
			</li>
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t05" no="4"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t09" no="8">
						<div class="div_typename"></div>
					</div>
                </a>
			</li>
			<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t06" no="5"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t10" no="9">
						<div class="div_typename"></div>
					</div>
                </a>
			</li>
        	
        	<li>
			 <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t07" no="6"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t11" no="10">
						<div class="div_typename"></div>
					</div>
                </a>
			</li>
        	
        </ul>
        <div class="clear"></div>
    </div>
    <div class="box">
        <div class="shop_skin_index_list banner" rel="edit-t02" no="1">
            <div class="img"><img src="banner.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
        </div>
        <div class="blank3"></div>
		            <div class="item">
                <a href="#" style="cursor: default; text-decoration: none;">
                    <div class="img"><img src=""></div>
					<strong>aa</strong>
					<span>￥0.00</span>
                </a>
            </div>
                <div class="clear"></div>
        <div class="shop_skin_index_list a0" rel="edit-t03" no="2">
            <div class="img"><img src="a0.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
        </div>
        <div class="a1">
                </div>
        <div class="clear"></div>
                <div class="clear"></div>
      </div>
   </div>


<?php }else if($template_id==3){?>
  <link href="bao/css/shop.css" rel="stylesheet" type="text/css">
  <link href="bao/css/index.css" rel="stylesheet" type="text/css">
	<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="header">
			<div class="search">
				 <form action="" method="get">
					<input type="text" name="Keyword" class="input" value="" placeholder="输入商品名称...">
					<input type="submit" class="submit" value=" ">
				</form>
			</div>
		</div>
		<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
			<div class="img"><img src="banner.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		<div id="SetHomeCurrentBox" style="height: 130px; width: 302px;"></div></div>
		<div class="shop_skin_index_list a0" rel="edit-t02" no="1">
			<div class="img"><img src="ad-0.jpg"></div><div class="mod">&nbsp;</div>
		</div>
		<div class="box">
			<ul>
				<li>
					<div class="shop_skin_index_list" rel="edit-t03" no="2">
						<div class="img"><img src="ad-1.jpg"></div><div class="mod">&nbsp;</div>
					</div>
				</li>
				<li>
					<div class="shop_skin_index_list" rel="edit-t04" no="3">
						<div class="img"><img src="ad-2.jpg"></div><div class="mod">&nbsp;</div>
					</div>
				</li>
				<li>
					<div class="shop_skin_index_list" rel="edit-t05" no="4">
						<div class="img"><img src="ad-3.jpg"></div><div class="mod">&nbsp;</div>
					</div>
				</li>
				<li>
					<div class="shop_skin_index_list" rel="edit-t06" no="5">
						<div class="img"><img src="ad-4.jpg"></div><div class="mod">&nbsp;</div>
					</div>
				</li>
			</ul>
			<div class="clear"></div>
		</div>
		<div class="shop_skin_index_list a0" rel="edit-t07" no="6">
			<div class="img"><img src="ad-0.jpg"></div><div class="mod">&nbsp;</div>
		</div>
	</div>
<?php }else if($template_id==4){?>
  <link href="fushi/css/shop.css" rel="stylesheet" type="text/css">
    <link href="fushi/css/index.css" rel="stylesheet" type="text/css">
	<link href="fushi/css/products.css" rel="stylesheet" type="text/css">
	
	<link href="fushi/css/products_media.css" rel="stylesheet" type="text/css">
	<div id="shop_skin_index">
	<div  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
			<div class="img"><img src="e1e3dac757.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		<div id="SetHomeCurrentBox" style="height: 150px; width: 310px;"></div></div>
		
	</div>
	<div id="index_prolist">
		<div class="shop_skin_index_list" rel="edit-t02" no="1">
			<h1 class="div_typename">新品上市</h1>
			<div class="mod">&nbsp;</div>
		</div>
		
		<div id="products">
			<div class="list-1">
							<div class="item">
					<ul>
						<li class="img"><a href="#" style="cursor: default; text-decoration: none;"><img src=""></a></li>
						<li class="name"><a href="#" style="cursor: default; text-decoration: none;">aa</a><span>￥0</span></li>
					</ul>
				</div>
									</div>
		</div>
	</div>
	</div>
<?php }else if($template_id==5){?>
    <link href="huazhuang/css/shop.css" rel="stylesheet" type="text/css">
    <link href="huazhuang/css/index.css" rel="stylesheet" type="text/css">
	<link href="huazhuang/css/products.css" rel="stylesheet" type="text/css">
	
	
	<div id="shop_skin_index"   <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="header">
			<div class="search">
				 <form action="" method="get">
					<input type="text" name="keyword" class="input" value="" placeholder="输入商品名称...">
					<input type="submit" class="submit" value=" ">
				</form>
			</div>
		</div>
		<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
			<div class="img"><img src="banner.jpg"></div><div class="mod">&nbsp;</div>
		<div id="SetHomeCurrentBox" style="height: 150px; width: 310px;"></div></div>
		<div class="clear"></div>
		<div class="index_h">
			<div class="l">热销推荐</div>
			<div class="r"><a href="##" style="cursor: default; text-decoration: none;"><img src="huazhuang/images/r.jpg"></a></div>
		</div>
		<div class="shop_skin_index_list i0" rel="edit-t02" no="1">
			<div class="img"><img src="i1.jpg"></div><div class="mod">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list i1" rel="edit-t03" no="2">
			<div class="img"><img src="i2.jpg"></div><div class="mod">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list i0" rel="edit-t04" no="3">
			<div class="img"><img src="i3.jpg"></div><div class="mod">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list i2" rel="edit-t05" no="4">
			<div class="img"><img src="i4.jpg"></div><div class="mod">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list i2" rel="edit-t06" no="5">
			<div class="img"><img src="i5.jpg"></div><div class="mod">&nbsp;</div>
		</div>
	</div>
	<div id="index_prolist">
		<div class="index_h">
			<div class="l">最新产品</div>
			<div class="r"><a href="#" style="cursor: default; text-decoration: none;">
			<img src="huazhuang/images/r.jpg"></a>
			</div>
		</div>
		<div id="products">
			<div class="list-0">
								<a href="##" style="cursor: default; text-decoration: none;">
						<div class="item">
							<div class="img"><img src=""></div>
							<div class="info">
								<h1>aa</h1>
								<h2>￥0</h2>
								<h3></h3>
							</div>
							<div class="detail"><span></span></div>
						</div>
					</a>
									</div>
		</div>
	</div>
<?php }else if($template_id==6){?>
     <link href="huazhuang2/css/shop.css" rel="stylesheet" type="text/css">
    <link href="huazhuang2/css/index.css" rel="stylesheet" type="text/css">
	<style>
		#shop_skin_index .menu{height:80px;width:100%;float:left; border-top:1px solid #d1d1c9; border-bottom:2px solid #dedbd2; background:#fcfbf9; overflow:hidden;}
		#shop_skin_index .menu li{width:33%; height:80px; overflow:hidden; float:left; box-sizing:border-box; border-left:1px solid #e2e1df;}
		#shop_skin_index .menu li a{display:block; width:100%; height:80px; line-height:135px; overflow:hidden; text-align:center;}
		#shop_skin_index .menu  li  .name{height:20px; line-height:20px; text-align:center;}
		#shop_skin_index .menu  li  .img{height:60px;}
		#shop_skin_index .imgs img{width:38px;height:38px;}
	</style>
	   <div id="shop_skin_index"   <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
			<div class="img"><img src="huazhuang2/images/banner.jpg"></div>
			<div class="mod" style="display: none;">&nbsp;</div>
		   <div id="SetHomeCurrentBox" style="height: 150px; width: 310px;">
		</div></div>
		 <div class="menu">
				<ul>
					<li>
					   <a href="#" style="cursor: default; text-decoration: none;" >
							<div class="shop_skin_index_list" rel="edit-t07" no="6" style="float:none;">
							<div class="img imgs"></div>
							</div>
							<div class="name shop_skin_index_list" rel="edit-t10" no="9" style="float:none;">
								<div class="div_typename"></div>
							</div>
						</a>
					</li>
					<li>
					   <a href="#" style="cursor: default; text-decoration: none;" >
							<div class="shop_skin_index_list" rel="edit-t08" no="7" style="float:none;">
							<div class="img imgs"></div>
							</div>
							 <div class="name shop_skin_index_list" rel="edit-t11" no="10" style="float:none;">
								<div class="div_typename"></div>
							</div>
						</a>
					</li>
					<li>
					   <a href="#" style="cursor: default; text-decoration: none;" >
							<div class="shop_skin_index_list" rel="edit-t09" no="8" style="float:none;">
							<div class="img imgs"></div>
							</div>
							 <div class="name shop_skin_index_list" rel="edit-t12" no="11" style="float:none;">
								<div class="div_typename"></div>
							</div>
						</a>
					</li>
				</ul>
				<div class="clear"></div>
			</div>
		<div class="shop_skin_index_list i0" rel="edit-t02" no="1">
			<div class="img"><img src="huazhuang2/images/i1.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list i1" rel="edit-t03" no="2">
			<div class="img"><img src="huazhuang2/images/68655b4c9d.png"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list i0" rel="edit-t04" no="3">
			<div class="img"><img src="huazhuang2/images/3684d6c0d3.png"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list i2" rel="edit-t05" no="4">
			<div class="img"><img src="huazhuang2/images/i4.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list i2" rel="edit-t06" no="5">
			<div class="img"><img src="huazhuang2/images/i5.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
	</div>
<?php }else if($template_id==7){?>
    <link href="huazhuang3/css/shop.css" rel="stylesheet" type="text/css">
    <link href="huazhuang3/css/index.css" rel="stylesheet" type="text/css">
	<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
			<div class="img"><img src="huazhuang3/images/banner.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		<div id="SetHomeCurrentBox" style="height: 150px; width: 310px;"></div></div>
		<div class="box">
			<div>
				<div class="search">
					<form>
						<input type="text" name="Keyword" class="input" value="" placeholder="输入商品名称...">
						<input type="submit" class="submit" value=" ">
					</form>
				</div>
				<a href="#" class="category" style="cursor: default; text-decoration: none;"></a>
			</div>
		</div>
		<div class="shop_skin_index_list list" rel="edit-t02" no="1">
			<div class="img"><img src="huazhuang3/images/a0.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list list" rel="edit-t03" no="2">
			<div class="img"><img src="huazhuang3/images/a1.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list list" rel="edit-t04" no="3">
			<div class="img"><img src="huazhuang3/images/a2.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list list" rel="edit-t05" no="4">
			<div class="img"><img src="huazhuang3/images/a3.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list list" rel="edit-t06" no="5">
			<div class="img"><img src="huazhuang3/images/a4.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list list" rel="edit-t07" no="6">
			<div class="img"><img src="huazhuang3/images/a5.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
	</div>
<?php }else if($template_id==8){?>
   <script type="text/javascript">
	var skin_index_init=function(){
		$('#web_skin_index .banner *').not('img').height($(window).height());
		$('#index_m').show();
	};
</script>
   <link href="lvyou/css/shop.css" rel="stylesheet" type="text/css">
    <link href="lvyou/css/index.css" rel="stylesheet" type="text/css">
	
   <div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="shop_skin_index_list banner" rel="edit-t01" no="0">
        <div class="img"><img src="lvyou/images/banner.jpg"></div>
		<div class="mod" style="display: none;">&nbsp;</div>
       <div id="SetHomeCurrentBox" style="height: 10px; width: 310px;"></div>
	</div>


    <div id="index_m">
	
	  <div  class="shop_skin_index_list"  rel="edit-t02"  no="1"><div  class="text"><a  href="#" class="div_typename" style="cursor: default; text-decoration: none;">热卖产品</a></div><div  class="mod">&nbsp;</div></div>
	  <div  class="shop_skin_index_list"  rel="edit-t03"  no="2"><div  class="text"><a  href="#" class="div_typename" style="cursor: default; text-decoration: none;">新品上市</a></div><div  class="mod">&nbsp;</div></div>
	
   </div>
</div>
<?php }else if($template_id==9){?>
    <link href="tupian/css/shop.css" rel="stylesheet" type="text/css">
    <link href="tupian/css/index.css" rel="stylesheet" type="text/css">
	
	<div  id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
			<div  class="img"><img  src="tupian/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
		</div>
		<div  class="shop_skin_index_list list"  rel="edit-t02"  no="1">
			<div  class="img"><img  src="tupian/images/01.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
		<div  id="SetHomeCurrentBox"  style="height: 108px; width: 308px;"></div></div>
		<div  class="shop_skin_index_list list"  rel="edit-t03"  no="2">
			<div  class="img"><img  src="tupian/images/02.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
		</div>
		<div  class="shop_skin_index_list list"  rel="edit-t04"  no="3">
			<div  class="img"><img  src="tupian/images/03.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
		</div>
		<div  class="shop_skin_index_list list"  rel="edit-t05"  no="4">
			<div  class="img"><img  src="tupian/images/04.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
		</div>
		<div  class="shop_skin_index_list list"  rel="edit-t06"  no="5">
			<div  class="img"><img  src="tupian/images/04.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
		</div>
		<div  class="shop_skin_index_list list"  rel="edit-t07"  no="6">
			<div  class="img"><img  src="tupian/images/04.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
		</div>
		<div  class="shop_skin_index_list list"  rel="edit-t08"  no="7">
			<div  class="img"><img  src="tupian/images/04.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
		</div>
		<div  class="shop_skin_index_list list"  rel="edit-t09"  no="8">
			<div  class="img"><img  src="tupian/images/04.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
		</div>
		<div  class="shop_skin_index_list list"  rel="edit-t10"  no="9">
			<div  class="img"><img  src="tupian/images/04.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
		</div>
	</div>
<?php }else if($template_id==10){?>
<link href="fushi2/css/shop.css" rel="stylesheet" type="text/css">
<link href="fushi2/css/index.css" rel="stylesheet" type="text/css">
<div  id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
        <div  class="img"><img  src="fushi2/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
    <div  id="SetHomeCurrentBox"  style="height: 445px; width: 310px;"></div></div>
    <div  id="index_m">
    	<div  class="bg"></div>
        <div  class="cont">
        	<div  class="shop_skin_index_list"  rel="edit-t02"  no="1">
			<div  class="text">
			<a  href="#" class="div_typename">栏目1</a>
			</div>
			<div  class="mod">&nbsp;</div>
			</div>
            <div>|</div>
            <div  class="shop_skin_index_list"  rel="edit-t03"  no="2"><div  class="text"><a  href="#" class="div_typename">栏目</a></div><div  class="mod">&nbsp;</div></div>
            <div>|</div>
            <div  class="shop_skin_index_list"  rel="edit-t04"  no="3"><div  class="text"><a  href="#" class="div_typename">栏目</a></div><div  class="mod">&nbsp;</div></div>
            <div>|</div>
            <div  class="shop_skin_index_list"  rel="edit-t05"  no="4"><div  class="text"><a  href="#" class="div_typename">栏目</a></div><div  class="mod"  style="display: none;">&nbsp;</div></div>
        </div>
	</div>
</div>

<?php }else if($template_id==11){?>
<link href="fushi5/css/shop.css" rel="stylesheet" type="text/css">
<link href="fushi5/css/index.css" rel="stylesheet" type="text/css">
<div  id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
        <div  class="img"><img  src="banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
    <div  id="SetHomeCurrentBox"  style="height: 500px; width: 310px;"></div></div>
 	<div  class="box">
    	<ul>
        	<li>
            	<div  class="shop_skin_index_list"  rel="edit-t02"  no="1">
                	<div  class="img"><img  src="01.png"></div><div  class="mod"  style="display: none;">&nbsp;</div>
                    <div  class="text"><a  href="#"></a></div><div  class="mod"  style="display: none;">&nbsp;</div>
                </div>
            </li>
        	<li>
            	<div  class="shop_skin_index_list"  rel="edit-t03"  no="2">
                	<div  class="img"><img  src="02.png"></div><div  class="mod"  style="display: none;">&nbsp;</div>
                    <div  class="text"><a  href="#"></a></div><div  class="mod"  style="display: none;">&nbsp;</div>
                </div>
            </li>
          </ul><ul>
        	<li>
            	<div  class="shop_skin_index_list"  rel="edit-t04"  no="3">
                	<div  class="img"><img  src="03.png"></div><div  class="mod"  style="display: none;">&nbsp;</div>
                    <div  class="text"><a  href="#"></a></div><div  class="mod"  style="display: none;">&nbsp;</div>
                </div>
            </li>
        	<li>
            	<div  class="shop_skin_index_list"  rel="edit-t05"  no="4">
                	<div  class="img"><img  src="04.png"></div><div  class="mod"  style="display: none;">&nbsp;</div>
                    <div  class="text"><a  href="#"></a></div><div  class="mod"  style="display: none;">&nbsp;</div>
                </div>
            </li>
        </ul>
        <div  class="clear"></div>
</div>
</div>

<?php }else if($template_id==12){?>
  <link href="small/css/shop.css" rel="stylesheet" type="text/css">
  <link href="small/css/index.css" rel="stylesheet" type="text/css">
  <div  id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div  class="shop_skin_index_list top_column"  rel="edit-t02"  no="1">
		<div  class="text"><a href="#" class="div_typename">标题</a></div><div  class="mod">&nbsp;</div>
    <div  id="SetHomeCurrentBox"  style="height: 40px; width: 310px;"></div></div>
	<div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
		<div  class="img"><img  src="small/images/banner.jpg"></div><div  class="mod">&nbsp;</div>
    </div>
    <div  class="shop_skin_index_list top_column"  rel="edit-t03"  no="2">
		<div  class="text"><a href="#" class="div_typename">标题</a></div><div  class="mod">&nbsp;</div>
    </div>
    <div  id="index_prolist">
        <div  id="products">
                    </div>
	</div>
  </div>

  
<?php }else if($template_id==13){?>

  <link href="jiazhuang/css/shop.css" rel="stylesheet" type="text/css">
  <link href="jiazhuang/css/index.css" rel="stylesheet" type="text/css">
  <link href="jiazhuang/css/index.css" rel="stylesheet" type="text/css">
  <link href="jiazhuang/css/style.css" rel="stylesheet" type="text/css">
  <link href="fushi5/css/index.css" rel="stylesheet" type="text/css">
  <div  id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div  class="index_header">
    	<div  class="lbar fl">
        	<div  class="search">
             <form  action="#"  method="get">
            	<input  type="text"  name="Keyword"  class="input"  value=""  placeholder="输入商品名称...">
                <input  type="submit"  class="submit"  value=" ">
            </form>
            </div>
         </div>
        <div  class="rbar fl"><a  href="#"  class="cart_icon"  style="cursor: default; text-decoration: none;"></a></div>
        <div  class="clear"></div>
    </div>
	<div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
		<div  class="img"><img  src="jiazhuang/images/banner.jpg"></div><div  class="mod">&nbsp;</div>
    <div  id="SetHomeCurrentBox"  style="height: 150px; width: 310px;"></div></div>
    <div  class="shop_skin_index_list top_column"  rel="edit-t02"  no="1"><div  class="text"><a href="#" class="div_typename">精选推荐</a></div><div  class="mod">&nbsp;</div></div>
    <div  id="index_prolist">
					<div  class="items">
								<div  class="cont">
					<div  class="lbar fl"><a  href="#"  class="name"  style="cursor: default; text-decoration: none;">test2</a></div>
					<div  class="rbar fr"><span  class="price">￥21</span></div>
					<div  class="blank3"></div>
					<div  class="brief">222</div>
				</div>
				<div  class="more"><a  href="#"  style="cursor: default; text-decoration: none;">更多</a></div>
			</div>
			</div>
</div>

<?php }else if($template_id==14){?>
  <link href="fushi6/css/global.css" rel="stylesheet" type="text/css">
  <link href="fushi6/css/shop.css" rel="stylesheet" type="text/css">
  <link href="fushi6/css/index.css" rel="stylesheet" type="text/css">
   
  <div  id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
		<div  class="img"><img  src="fushi6/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
    </div>
    <div  class="ind_one_box">
    	<div  class="lbar fl">
        	<div  class="shop_skin_index_list"  rel="edit-t02"  no="1">
        		<div  class="img"><img  src="fushi6/images/t0.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
        	</div>
        </div>
    	<div  class="rbar fr">
        	<div  class="shop_skin_index_list"  rel="edit-t03"  no="2">
        	<div  class="img"><img  src="fushi6/images/t1.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
        	</div>
        </div>
        <div  class="clear"></div>
    </div>
    
    <div  class="shop_skin_index_list ind_two_box"  rel="edit-t04"  no="3">
		<div  class="img"><img  src="fushi6/images/t2.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
    </div>
    <div  class="ind_th_box">
    	<div  class="lbar fl">
        	<div  class="shop_skin_index_list"  rel="edit-t05"  no="4">
        		<div  class="img"><img  src="fushi6/images/t3.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
        	</div>
        </div>
    	<div  class="rbar fr">
        	<ul>
            	            	<li  class="fl mar_r mar_b">
                	<div  class="shop_skin_index_list"  rel="edit-t06"  no="5">
                        <div  class="img"><img  src="fushi6/images/t4.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
                    </div>
                </li>
                            	<li  class="fl mar_r mar_b">
                	<div  class="shop_skin_index_list"  rel="edit-t07"  no="6">
                        <div  class="img"><img  src="fushi6/images/t5.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
                    </div>
                </li>
                            	<li  class="fl mar_b">
                	<div  class="shop_skin_index_list"  rel="edit-t08"  no="7">
                        <div  class="img"><img  src="fushi6/images/t6.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
                    </div>
                </li>
                            	<li  class="fl mar_r">
                	<div  class="shop_skin_index_list"  rel="edit-t09"  no="8">
                        <div  class="img"><img  src="fushi6/images/t7.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
                    <div  id="SetHomeCurrentBox"  style="height: 73px; width: 69px;"></div></div>
                </li>
                            	<li  class="fl mar_r">
                	<div  class="shop_skin_index_list"  rel="edit-t10"  no="9">
                        <div  class="img"><img  src="fushi6/images/t8.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
                    </div>
                </li>
                            	<li  class="fl">
                	<div  class="shop_skin_index_list"  rel="edit-t11"  no="10">
                        <div  class="img"><img  src="fushi6/images/t9.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
                    </div>
                </li>
                            </ul>
        </div>
        <div  class="clear"></div>
    </div>
</div>

<?php }else if($template_id==15){?>
  <link href="xie2/css/shop.css" rel="stylesheet" type="text/css">
  <link href="xie2/css/index.css" rel="stylesheet" type="text/css">
  <link href="xie2/css/global.css" rel="stylesheet" type="text/css">
  <div  id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div  id="index_header">
		<div  class="lbar fl">
			<div  class="shop_skin_index_list logo"  rel="edit-t01"  no="0">
				<div  class="img"><img  src="xie2/images/logo.jpg"></div><div  class="mod">&nbsp;</div>
   			 <div  id="SetHomeCurrentBox"  style="height: 23px; width: 115px;"></div></div>
		</div>
		<div  class="rbar fr">
			<div  class="head_menu">
				<a  href="#"  class="cart"  style="cursor: default; text-decoration: none;"><img  src="xie2/images/cart_icon.png"></a>
				<a  href="#"  class="cate"  name="show_cate"  style="cursor: default; text-decoration: none;"><img  src="xie2/images/cate_list.png"></a>
				<a  href="#"  class="search"  style="cursor: default; text-decoration: none;"><img  src="xie2/images/search_btn.png"></a>
			</div>
			<div  class="search_box">
             <form  action="#"  method="get">
            	<input  type="text"  name="Keyword"  class="input"  value=""  placeholder="输入商品名称...">
                <input  type="submit"  class="submit"  value=" ">
            </form>
            </div>
		</div>
		<div  class="clear"></div>
	</div>
	<div  class="shop_skin_index_list banner"  rel="edit-t02"  no="1">
		<div  class="img"><img  src="xie2/images/banner.jpg"></div><div  class="mod">&nbsp;</div>
    </div>
			<div  class="products_cont">
			<ul>
				<li  class="column bg_blue"><a  href="#"  style="cursor: default; text-decoration: none;">sss</a></li>
						
			</ul>
			<div  class="clear"></div>
		</div>
			<div  class="products_cont">
			<ul>
				<li  class="column bg_ff8b3e"><a  href="#"  style="cursor: default; text-decoration: none;">饰品</a></li>
						
			</ul>
			<div  class="clear"></div>
		</div>
			<div  class="products_cont">
			<ul>
				<li  class="column bg_78c92e"><a  href="#"  style="cursor: default; text-decoration: none;">包包</a></li>
						
			</ul>
			<div  class="clear"></div>
		</div>
	</div>

<?php }else if($template_id==16){?>

     <link href="fushi7/css/shop.css" rel="stylesheet" type="text/css">
     <link href="fushi7/css/index.css" rel="stylesheet" type="text/css">
	 <link href="fushi7/css/global.css" rel="stylesheet" type="text/css">
	 
    <div  id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
		<div  class="img"><img  src="fushi7/images/banner.jpg"></div><div  class="mod">&nbsp;</div>
    <div  id="SetHomeCurrentBox"  style="height: 148px; width: 310px;"></div></div>
	<div  class="index-h">
				<div  class="items">
            <div class="shop_skin_index_list" rel="edit-t02" no="1">
            	<div class="img"></div>
            </div>
            <div class="name shop_skin_index_list" rel="edit-t06" no="5">  
				<div class="div_typename div_font" ></div>
            </div>
        </div>
        
		<div  class="items">
        	<div class="shop_skin_index_list" rel="edit-t03" no="2">
            	<div class="img"></div>
            </div>
            <div class="name shop_skin_index_list" rel="edit-t07" no="6">  
				<div class="div_typename div_font" ></div>
            </div>
        </div>
            
		<div  class="items">
        	<div class="shop_skin_index_list" rel="edit-t04" no="3">
            	<div class="img"></div>
            </div>
            <div class="name shop_skin_index_list" rel="edit-t08" no="7">  
				<div class="div_typename div_font" ></div>
            </div>
        </div>
            
        <div  class="items">
        	<div class="shop_skin_index_list" rel="edit-t05" no="4">
            	<div class="img"></div>
            </div>
            <div class="name shop_skin_index_list" rel="edit-t09" no="8">  
				<div class="div_typename div_font" ></div>
            </div>
        </div>
	</div>
			<div  class="products_cont">
			<div  class="title bg_blue"><a  href="#"  class="more"  style="cursor: default; text-decoration: none;">更多</a>sss</div>
			<div  class="cont">
				<ul  class="products_list">
									</ul>
			   <div  class="clear"></div>
			</div>
		</div>
    		<div  class="products_cont">
			<div  class="title bg_f8ca5a"><a  href="#"  class="more"  style="cursor: default; text-decoration: none;">更多</a>饰品</div>
			<div  class="cont">
				<ul  class="products_list">
									</ul>
			   <div  class="clear"></div>
			</div>
		</div>
    		<div  class="products_cont">
			<div  class="title bg_ee7884"><a  href="#"  class="more"  style="cursor: default; text-decoration: none;">更多</a>包包</div>
			<div  class="cont">
				<ul  class="products_list">
									</ul>
			   <div  class="clear"></div>
			</div>
		</div>
    </div>
	
<?php }else if($template_id==17){?>

 <link href="huazhuang5/css/shop.css" rel="stylesheet" type="text/css">
  <link href="huazhuang5/css/index.css" rel="stylesheet" type="text/css">
  <link href="huazhuang5/css/global.css" rel="stylesheet" type="text/css">
  <div  id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div  class="shop_skin_index_list logo"  rel="edit-t01"  no="0">
		<div  class="img"><img  src="huazhuang5/images/logo.jpg"></div><div  class="mod">&nbsp;</div>
    <div  id="SetHomeCurrentBox"  style="height: 11px; width: 150px;"></div></div>
    <div  class="search_box">
         <form  action="#"  method="get">
            <input  type="text"  name="Keyword"  class="input"  value=""  placeholder="输入商品名称...">
            <input  type="submit"  class="submit"  value=" ">
        </form>
     </div>
	<div  class="shop_skin_index_list banner"  rel="edit-t02"  no="1">
		<div  class="img"><img  src="huazhuang5/images/banner.jpg"></div><div  class="mod">&nbsp;</div>
    </div>
	
	<div  class="index-h">
		<div  class="shop_skin_index_list items"  rel="edit-t08"  no="7"><div  class="img"  style="width:60px; height:80px;text-align:center;padding-left:5px;"><img  src="huazhuang5/images/gift_icon.png" /></div></div>
		<div  class="shop_skin_index_list items"  rel="edit-t11"  no="10"><div  class="img"  style="width:60px; height:80px;text-align:center;padding-left:5px;"><img  src="huazhuang5/images/gift_icon.png" /></div></div>
		<div  class="shop_skin_index_list items"  rel="edit-t10"  no="9"><div  class="img"  style="width:60px; height:80px;text-align:center;padding-left:5px;"><img  src="huazhuang5/images/gift_icon.png" /></div></div>
		<div  class="shop_skin_index_list items"  rel="edit-t09"  no="8"><div  class="img"  style="width:60px; height:80px;text-align:center;padding-left:5px;"><img  src="huazhuang5/images/gift_icon.png" /></div></div>
	</div>
	<div  class="ind_wrap">
    	<div  class="ind_one_box">
            <div  class="lbar fl">
                <div  class="shop_skin_index_list"  rel="edit-t03"  no="2"><div  class="img"><img  src="huazhuang5/images/t3.jpg"></div><div  class="mod">&nbsp;</div></div>
            </div>
            <div  class="rbar fr">
                <div  class="shop_skin_index_list"  rel="edit-t04"  no="3"><div  class="img"><img  src="huazhuang5/images/t4.jpg"></div><div  class="mod">&nbsp;</div></div>
            </div>
            <div  class="clear"></div>
   		</div>
        <div  class="ad_items"><div  class="shop_skin_index_list"  rel="edit-t05"  no="4"><div  class="img"><img  src="huazhuang5/images/t5.jpg"></div><div  class="mod">&nbsp;</div></div></div>
		
        <div  class="ad_items"><div  class="shop_skin_index_list"  rel="edit-t06"  no="5"><div  class="img"><img  src="huazhuang5/images/t6.jpg"></div><div  class="mod">&nbsp;</div></div></div>
        <div  class="ad_items"><div  class="shop_skin_index_list"  rel="edit-t07"  no="6"><div  class="img"><img  src="huazhuang5/images/t7.jpg"></div><div  class="mod">&nbsp;</div></div></div>
    </div>
</div>

<?php }else if($template_id==18){?>
   
     <link href="fruit/css/shop.css" rel="stylesheet" type="text/css">
     <link href="fruit/css/index.css" rel="stylesheet" type="text/css"> 
	 <link href="fruit/css/global.css" rel="stylesheet" type="text/css">

   <div  id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
		<div  class="img"><img  src="fruit/images/banner.jpg"></div>
		<div  class="mod"  style="display: none;">&nbsp;</div>
    <div  id="SetHomeCurrentBox"  style="height: 190px; width: 310px;"></div></div>
	<div  class="ind_wrap">
    	    	<div  class="category">
        	<h3><a  href="#"  class="more"  style="cursor: default; text-decoration: none;">查看更多</a>sss</h3>
            <div  class="products">
            	                <div  class="clear"></div>
            </div>
        </div>
            	<div  class="category">
        	<h3><a  href="#"  class="more"  style="cursor: default; text-decoration: none;">查看更多</a>饰品</h3>
            <div  class="products">
            	                <div  class="clear"></div>
            </div>
        </div>
            	<div  class="category">
        	<h3><a  href="#"  class="more"  style="cursor: default; text-decoration: none;">查看更多</a>包包</h3>
            <div  class="products">
            	                <div  class="clear"></div>
            </div>
        </div>
            	<div  class="category">
        	<h3><a  href="#"  class="more"  style="cursor: default; text-decoration: none;">查看更多</a>鞋子</h3>
            <div  class="products">
            	                <div  class="clear"></div>
            </div>
        </div>
            	<div  class="category">
        	<h3><a  href="#"  class="more"  style="cursor: default; text-decoration: none;">查看更多</a>衣服</h3>
            <div  class="products">
            	            	<div  class="items fl">
                	<div  class="pro_img"><a  href="#"  style="cursor: default; text-decoration: none;"><img  src="fruit/images/8f529fe94b.jpg"></a></div>
                    <div  class="name"><a  href="#"  style="cursor: default; text-decoration: none;">test2</a></div>
                 </div>
                                <div  class="clear"></div>
            </div>
        </div>
            </div>
   </div>
<?php }else if($template_id==19){?>

    <link rel="stylesheet" type="text/css" href="beijing/css/common.css">
    <link rel="stylesheet" type="text/css" href="beijing/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="beijing/css/mall.css">
	<link href="fruit/css/shop.css" rel="stylesheet" type="text/css">
     <link href="fruit/css/index.css" rel="stylesheet" type="text/css"> 
	 <link href="fruit/css/global.css" rel="stylesheet" type="text/css">
    <div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <!--topbar begin-->
    <section class="box" id="myorder">
        <div class="user_index">
            <div class="user_header" id="actionBar">
                <span  class="shop_skin_index_list top_column"  rel="edit-t02"  no="1">
		            <span  class="text"><a href="#" class="div_typename">标题</a></span><div  class="mod">&nbsp;</div>
                 </span>
                <span  class="shop_skin_index_list top_column"  rel="edit-t03"  no="2">
		            <span  class="text"><a href="#" class="div_typename">标题</a></span><div  class="mod">&nbsp;</div>
                 </span>
                <span class="shop_skin_index_list top_column"><a href="javascript:;" class="shopping-cart">
                     <i class="fa fa-shopping-cart"></i>
                </a></span>
                <span  class="shop_skin_index_list top_column"  rel="edit-t13"  no="12">
		            <span  class="text"><a href="#" class="div_typename">标题</a></span><div  class="mod">&nbsp;</div>
                 </span>
            </div>
        </div>
    </section>
    
    <section class="box" id="banner">
       
      <div class="pfhlkd_frame1">
			<div class="pfhlkd_mode0  pfhlkd_mf10001000"></div>
			<div class="pfhlkd_mode0  pfhlkd_mf10001005"></div>
			<div>
				
					<div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
						<div  class="img"><img  src="fruit/images/banner.jpg" style="width:100px;height:100px;"></div>
						<div  class="mod"  style="display: none;">&nbsp;</div>
						<div  id="SetHomeCurrentBox"  style="height: 190px; width: 310px;"></div>
					</div>
				
			</div>
		</div>

    </section>
     <style>
    .user_nav .user_nav_list li span{width:35px;margin:0 auto;height:30px;}
	.div_font{color:#fff;margin-top:6px;}
    </style>
    <section class="box" id="module">
        <div>
            <div class="user_nav clearfix">
                <ul class="user_nav_list">
                    <!--<li class="pro-class">
                        <a href="javascript:void(0)" id="menu" class="icon-s1"><span class="fa fa-th" style="height:36px!important"></span>所有商品</a>
                    </li>
					-->
                    <li>
                        <a href="javascript:void(0)" class="icon-s1">
                        	<span class="fa ">
                            	<div class="shop_skin_index_list" rel="edit-t04" no="3">
                        			<div class="img"></div>
                        		</div>
                            </span>
                            <div class="name shop_skin_index_list" rel="edit-t08" no="7">  
								<div class="div_typename div_font" ></div>
							</div>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="icon-s1">
                        	<span class="fa ">
                            	<div class="shop_skin_index_list" rel="edit-t05" no="4">
                        			<div class="img"></div>
                        		</div>
                            </span>
                            <div class="name shop_skin_index_list" rel="edit-t09" no="8">  
								<div class="div_typename div_font" ></div>
							</div>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="icon-s1">
                        	<span class="fa">
                            	<div class="shop_skin_index_list" rel="edit-t06" no="5">
                        			<div class="img"></div>
                        		</div>
                            </span>
                            <div class="name shop_skin_index_list" rel="edit-t10" no="9">  
								<div class="div_typename div_font" ></div>
							</div>
                    	</a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="icon-s1">
                        	<span class="fa">
                            	<div class="shop_skin_index_list" rel="edit-t07" no="6">
                        			<div class="img"></div>
                        		</div>
                            </span>
                            <div class="name shop_skin_index_list" rel="edit-t11" no="10">  
								<div class="div_typename div_font" ></div>
							</div>
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </section>
    <div style="clear: both;"></div>
    <div class="user_itlist_nb">
        <div class="name shop_skin_index_list title" rel="edit-t12" no="11">
            <div style="color: #f15a5f;font-size: 18px;font-weight: bold;" class="div_typename div_font r_color"></div>    
        </div>
    </div>

    <section class="main_title" style="display: none" id="top2">

        <h2 id="topname"></h2>
        <a href="javascript:;" data-type="back" class="go-back" id="backurl"><span class="icons fa fa-angle-left" data-icon=""></span></a>

    </section>
    <div class="h30" id="h30" style="display: none"></div>
   
    <div class="WX_con" id="J_main">
        <div class="jx">
            <div class="jx_list">
                
            </div>
            <div class="jx_map">

                <div class="jx_map_bd WX_cat_list">
                    <a href="javascript:;" class="J_ytag WX_cat_sp">防晒丝巾</a><!--00101-00199 -->
                </div>
            </div>
           
        </div>
    </div>
</div>
<?php }else if($template_id==20){?>

<link  rel="stylesheet"  href="fushi_20/css/style.css">
	
	<link  rel="stylesheet"  type="text/css"  href="fushi_20/css/idangerous.swiper.css">
	<link  rel="stylesheet"  href="fushi_20/css/header_style8.css">
	
 <link href="fushi2/css/shop.css" rel="stylesheet" type="text/css">
<link href="fushi2/css/index.css" rel="stylesheet" type="text/css">
<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
        <div  class="img"><img  src="fushi2/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
    <div  id="SetHomeCurrentBox"  style="height: 445px; width: 310px;"></div></div>
    <div  id="index_m" style="bottom:-10px">
    	<div  class="membersbox pad50">
	
<div  class="membersbox">
	<div  class="mobile8_nav">	
       
              <ul>
			        <li>
                       
						<span class="shop_skin_index_list" rel="edit-t02"  no="1" style="margin-top:8px;"><span class="img" style="margin-top:-15px;"><img  src="fushi_20/images/ind1-1.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
						<span class="shop_skin_index_list" rel="edit-t06"  no="5" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;height:6px;"><a  href="#" class="div_typename">栏目</a></span><div  class="mod">&nbsp;</div></span>
						<span class="shop_skin_index_list" rel="edit-t10"  no="9" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;height:56px;"><b  href="#" class="div_typename">栏目</b></span><div  class="mod">&nbsp;</div></span>
                    </li><li>
                        
                         <span class="shop_skin_index_list" rel="edit-t03"  no="2"  style="margin-top:8px;"><span class="img" style="margin-top:-15px;"><img  src="fushi_20/images/ind1-2.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
                         <span class="shop_skin_index_list" rel="edit-t07"  no="6" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;height:6px;"><a  href="#" class="div_typename">栏目</a></span><div  class="mod">&nbsp;</div></span>
						 <span class="shop_skin_index_list" rel="edit-t11"  no="10" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;height:56px;"><b  href="#" class="div_typename">栏目</b></span><div  class="mod">&nbsp;</div></span>
                        
                    </li><li>
                            <span class="shop_skin_index_list" rel="edit-t04"  no="3"  style="margin-top:8px;"><span class="img" style="margin-top:-15px;"><img  src="fushi_20/images/ind1-3.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
                            <span class="shop_skin_index_list" rel="edit-t08"  no="7" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;height:6px;"><a  href="#" class="div_typename">栏目</a></span><div  class="mod">&nbsp;</div></span>
							<span class="shop_skin_index_list" rel="edit-t12"  no="11" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;height:56px;"><b  href="#" class="div_typename">栏目</b></span><div  class="mod">&nbsp;</div></span>
                    </li><li>
                          <span class="shop_skin_index_list" rel="edit-t05"  no="4"  style="margin-top:8px;"><span class="img" style="margin-top:-15px;"><img  src="fushi_20/images/ind1-4.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
                            <span class="shop_skin_index_list" rel="edit-t09"  no="8" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;height:6px;"><a  href="#" class="div_typename">栏目</a></span><div  class="mod">&nbsp;</div></span>
							<span class="shop_skin_index_list" rel="edit-t13"  no="12" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;height:56px;"><b  href="#" class="div_typename">栏目</b></span><div  class="mod">&nbsp;</div></span>
                        
                    </li>			
		</ul>   
    </div></div></div>     	

</div>
</div>
<?php }else if($template_id==21){?>

<link  rel="stylesheet"  href="fushi_21/css/style.css">
	
	
  <link href="fushi2/css/shop.css" rel="stylesheet" type="text/css">
  <link href="fushi2/css/index.css" rel="stylesheet" type="text/css">
  
<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
        <div  class="img"><img  src="fushi2/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
       <div  id="SetHomeCurrentBox"  style="height: 445px; width: 310px;"></div>
	</div>
    <div  id="index_m" style="top:50px;">
	   <div  class="membersbox pad50">
	
			<div  class="homeBbox">
						<span class="shop_skin_index_list" rel="edit-t02"  no="1"><span class="text"><a  href="#" class="div_typename" style="padding-top:20px;">栏目</a></span><div  class="mod" style="margin-top:-50px;heigth:100%;">&nbsp;</div></span>
						<span class="shop_skin_index_list" rel="edit-t03"  no="2"><span class="text"><a  href="#" class="div_typename" style="padding-top:20px;">栏目</a></span><div  class="mod" style="margin-top:-50px">&nbsp;</div></span>
						<span class="shop_skin_index_list" rel="edit-t04"  no="3"><span class="text"><a  href="#" class="div_typename" style="padding-top:20px;">栏目</a></span><div  class="mod" style="margin-top:-50px">&nbsp;</div></span>
						<span class="shop_skin_index_list" rel="edit-t05"  no="4"><span class="text"><a  href="#" class="div_typename" style="padding-top:20px;">栏目</a></span><div  class="mod" style="margin-top:-50px">&nbsp;</div></span>

			</div>

        </div>
	</div>
	
	


</div>     	

 <?php }else if($template_id==22){?>

<link  rel="stylesheet"  href="fushi_22/css/style.css">
<link  rel="stylesheet"  type="text/css"  href="fushi_22/css/idangerous.swiper.css">	
	
  <link href="fushi2/css/shop.css" rel="stylesheet" type="text/css">
  <link href="fushi2/css/index.css" rel="stylesheet" type="text/css">
  
<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
        <div  class="img"><img  src="fushi2/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
       <div  id="SetHomeCurrentBox"  style="height: 445px; width: 310px;"></div>
	</div>
    <div  id="index_m" style="top:10px;">
	   <div  class="membersbox pad50">
	
		   <div  class="homeCpay"  style="top:130px;">
		     <span class="shop_skin_index_list" rel="edit-t04"  no="3"><span class="text"><a  href="#" class="div_typename">所有商品</a></span><div  class="mod">&nbsp;</div></span>
		   </div>
			<div  class="homeCcon shop_skin_index_list" rel="edit-t02"  no="1">
				<span class="" style="height:20px;display:block;"><span class="text"><a  href="#" class="div_typename" style="color:#ff0000;font-size:18px;line-height:40px">大标题</a></span><div  class="mod">&nbsp;</div></span>
				
			</div>
            <div class="shop_skin_index_list" rel="edit-t03"  no="2">
            	<span ><span class="text"><a  href="#" class="div_typename" style="color:#ff0000">小标题</a></span><div  class="mod">&nbsp;</div></span>
            </div>
			<div  class="homeCnav" style="height:100%;top:300px;bottom:10px;">
				<div  class="homeCnavbox swiper-container">
					<ul  class="swiper-wrapper"  style="width: 100%; height: 80px;">
						<li  class="swiper-slide"  style="width: 25%; height: 80px;">
							<span    class="homeCnavbox_a_colblue"  style="background-color: #07a0e7">
								<h2><span class="shop_skin_index_list" rel="edit-t05"  no="4"><span class="text"><span href="#" class="div_typename" style="color:#fff;">大标题</span></span><div  class="mod">&nbsp;</div></span></h2>
								<div  class="shop_skin_index_list items"  rel="edit-t09"  no="8" style="width:25px;text-align:center;margin:0 auto;"><div  class="img"></div></div>
							</span>
						</li>
						<li  class="swiper-slide"  style="width: 25%; height: 80px;">
							<span   class="homeCnavbox_a_colgreen"  style="background-color: #72c201">
								<h2><span class="shop_skin_index_list" rel="edit-t06"  no="5"><span class="text"><a  href="#" class="div_typename" style="color:#fff;">大标题</a></span><div  class="mod">&nbsp;</div></span></h2>
								<div  class="shop_skin_index_list items"  rel="edit-t10"  no="9" style="width:25px;text-align:center;margin:0 auto;"><div  class="img"></div></div>
							</span>
						</li>
						<li  class="swiper-slide"  style="width: 25%; height: 80px;">
							<span   class="homeCnavbox_a_colyellow"  style="background-color: #ffa800">
								<h2><span class="shop_skin_index_list" rel="edit-t07"  no="6"><span class="text"><a  href="#" class="div_typename" style="color:#fff;">大标题</a></span><div  class="mod">&nbsp;</div></span></h2>
								<div  class="shop_skin_index_list items"  rel="edit-t11"  no="10" style="width:25px;text-align:center;margin:0 auto;"><div  class="img"></div></div>
							</span>
						</li>
						<li  class="swiper-slide"  style="width: 25%; height: 80px;">
							<span   class="homeCnavbox_a_colred"  style="background-color: #d50303">
								<h2><span class="shop_skin_index_list" rel="edit-t08"  no="7"><span class="text"><a  href="#" class="div_typename" style="color:#fff;">大标题</a></span><div  class="mod">&nbsp;</div></span></h2>
								<div  class="shop_skin_index_list items"  rel="edit-t12"  no="11" style="width:25px;text-align:center;margin:0 auto;"><div  class="img"></div></div>
							</span>
						</li>                </ul>
				</div>
			</div>    

				

		</div>
	</div>
	
	


</div>    

<?php }else if($template_id==23){?>

<link  rel="stylesheet"  href="fushi_20/css/style.css">
<link  rel="stylesheet"  type="text/css"  href="fushi_20/css/idangerous.swiper.css">
<link href="fushi2/css/shop.css" rel="stylesheet" type="text/css">
<link href="fushi2/css/index.css" rel="stylesheet" type="text/css">
<style>
.name_block{height:100%;display:block;}
.homeA_span{margin:4px auto!important;height:33px;}
</style>
<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
        <div  class="img"><img  src="fushi2/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
       <div  id="SetHomeCurrentBox"  style="height: 445px; width: 310px;"></div>
	</div>
    <div  id="index_m" style="bottom:0px;">
      <div  class="membersbox pad50">
	    <div  class="homeAbox">
		  <div  class="homeA swiper-container">
			<ul  class="swiper-wrapper"  style="width: 100%;height: 235px;">
                <li  class="swiper-slide"  style="width: 25%; height: 135px;">
					<h2><span class="shop_skin_index_list name_block" rel="edit-t02"  no="1"><span class="text"><a  href="#" class="div_typename" style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></h2>
					<span class="shop_skin_index_list" rel="edit-t06"  no="5"><span class="img" ><img  src="fushi_23/images/ind1-1.png"></span><div  class="mod">&nbsp;</div></span>
					<div class="homeA_span" style="width:100%;"><span class="shop_skin_index_list name_block" rel="edit-t10"  no="9"><span class="text"><a  href="#" class="div_typename" style="color: #0680ad;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
					<b></b>
				</li>
				<li  class="swiper-slide"  style="width: 25%; height: 135px;">
					
						<h2><span class="shop_skin_index_list name_block" rel="edit-t03"  no="2"><span class="text"><a  href="#" class="div_typename"  style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></h2>
						<span class="shop_skin_index_list" rel="edit-t07"  no="6" ><span class="img" ><img  src="fushi_23/images/ind1-2.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
						<div class="homeA_span" style="width:100%;"><span class="shop_skin_index_list name_block" rel="edit-t11"  no="10"><span class="text"><a  href="#" class="div_typename" style="color: #0680ad;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
						<b></b>
					
				</li>
				<li  class="swiper-slide"  style="width: 25%; height: 135px;">
					
						<h2><span class="shop_skin_index_list name_block" rel="edit-t04"  no="3"><span class="text"><a  href="#" class="div_typename"  style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></h2>
						<span class="shop_skin_index_list" rel="edit-t08"  no="7" ><span class="img" style="margin-top:-15px;"><img  src="fushi_23/images/ind1-3.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
						<div class="homeA_span" style="width:100%;"><span class="shop_skin_index_list name_block" rel="edit-t12"  no="11"><span class="text"><a  href="#" class="div_typename" style="color: #0680ad;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
						<b></b>
					
				</li>
				<li  class="swiper-slide"  style="width: 25%; height: 135px;">
					
						<h2><span class="shop_skin_index_list name_block" rel="edit-t05"  no="4"><span class="text"><a  href="#" class="div_typename"  style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></h2>
						<span class="shop_skin_index_list" rel="edit-t09"  no="8" style="margin-top:8px;"><span class="img" style="margin-top:-15px;"><img  src="fushi_23/images/ind1-4.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
						<div class="homeA_span" style="width:100%;"><span class="shop_skin_index_list name_block" rel="edit-t13"  no="12"><span class="text"><a  href="#" class="div_typename" style="color: #0680ad;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
						<b></b>
					
				</li>
				
                			</ul>
		</div>
	   </div>
    

        	

    </div>
   	

</div>
</div>

<?php }else if($template_id==24){?>
<style>
	.homeCtitle #SetHomeCurrentBox{height:30px!important}
	.homeCcon #SetHomeCurrentBox{top:15px!important;height:17px!important;}
	
</style>
<link  rel="stylesheet"  href="fushi_24/css/style.css">
<link  rel="stylesheet"  type="text/css"  href="fushi_24/css/idangerous.swiper.css">
<link  rel="stylesheet"  href="fushi_24/css/header_style5.css">

<link href="fushi2/css/shop.css" rel="stylesheet" type="text/css">
<link href="fushi2/css/index.css" rel="stylesheet" type="text/css">
<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
        <div  class="img"><img  src="fushi2/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
       <div  id="SetHomeCurrentBox"  style="height: 445px; width: 310px;"></div>
	</div>
    <div  id="index_m" style="top:120px">
      <div  class="membersbox pad50">
	
    	    <div  class="homeCpay" style="top:150px;"><span class="shop_skin_index_list" rel="edit-t04"  no="3"><span class="text"><a  href="#" class="div_typename" style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
			<div  class="homeCmargin"></div>
			<div  class="homeCtitle shop_skin_index_list"  rel="edit-t02"  no="1" style="color:#ffffff;height:20px;display:block;"><a  href="#" class="div_typename" style="color:#fff;">栏目</a><div  class="mod" style="height:20px;">&nbsp;</div></div>
           
			<div  class="homeCcon shop_skin_index_list"  style="color:#ffffff; height:22px;display:block;padding-top:20px;" rel="edit-t03"  no="2"><a  href="#" class="div_typename" style="color:#fff;height:20px;display:block;">栏目</a><div  class="mod" style="height:20px;">&nbsp;</div></div>
			<div  class="homeCnav" style="position:absolute;top:365px">
				<div  class="homeCnavbox swiper-container">
					<ul  class="swiper-wrapper"  style="width: 100%; height: 65px;">
						<li  class="swiper-slide"  style="width: 25%; height: 65px;">
											
								<div  class="shop_skin_index_list items"  rel="edit-t05"  no="4" style="width:25px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_24/images/index5-2.png"  width="31"  height="26"></div></div>
								<div class="name shop_skin_index_list" rel="edit-t09" no="8" style="margin-top:12px;"><div class="div_typename r_color" style="color:#fff;height:20px;"></div></div>
                                
                            
                            
						</li><li  class="swiper-slide"  style="width: 25%; height: 65px;">
												
								<div  class="shop_skin_index_list items"  rel="edit-t06"  no="5" style="width:25px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_24/images/index5-3.png"  width="31"  height="26"></div></div>
								<div class="name shop_skin_index_list" rel="edit-t10" no="9" style="margin-top:12px;"><div class="div_typename r_color" style="color:#fff;height:20px;"></div></div>
							
						</li><li  class="swiper-slide"  style="width: 25%;height: 65px;">
							
								<div  class="shop_skin_index_list items"  rel="edit-t07"  no="6" style="width:25px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_24/images/index5-4.png"  width="31"  height="26"></div></div>
								<div class="name shop_skin_index_list" rel="edit-t11" no="10" style="margin-top:12px;"><div class="div_typename r_color" style="color:#fff;height:20px;"></div></div>
							
						</li><li  class="swiper-slide"  style="width: 25%; height: 65px;">
							
								<div  class="shop_skin_index_list items"  rel="edit-t08"  no="7" style="width:25px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_24/images/index5-5.png"  width="31"  height="26"></div></div>
								<div class="name shop_skin_index_list" rel="edit-t12" no="11" style="margin-top:12px;"><div class="div_typename r_color" style="color:#fff;height:20px;"></div></div>
							
						</li>			
					</ul>
				</div>
			</div>    
		</div>
   	

    </div>
</div>


<?php }else if($template_id==25){?>

<link  rel="stylesheet"  href="fushi_25/css/style.css">
<link  rel="stylesheet"  type="text/css"  href="fushi_25/css/idangerous.swiper.css">
<link  rel="stylesheet"  href="fushi_25/css/header_style6.css">

<link href="fushi2/css/shop.css" rel="stylesheet" type="text/css">
<link href="fushi2/css/index.css" rel="stylesheet" type="text/css">
<style>
#shop_skin_index .banner *{
	height:100%;
}
.mod_25{height:26px;width: 60px;margin-top: 23px;display:block;margin-left: 5px;line-height:28px;}
.mobile6_navbox ul li a{height:72px;}
</style>
<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
        <div  class="img"><img  src="fushi2/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
       <div  id="SetHomeCurrentBox"  style="height: 445px; width: 310px;"></div>
	</div>
    <div  id="index_m" style="top:20px">
      <div  class="membersbox pad50">
	 
		<div  class="mobile6_title"><a  href="#"><span class="shop_skin_index_list" rel="edit-t02"  no="1" style="height:30px;display:block;"><span style="font-size:24px;" class="div_typename">大标题</span><div  class="mod">&nbsp;</div></span></div></a>
		<div  class="mobile6_con"><a  href="#"><span class="shop_skin_index_list" rel="edit-t03"  no="2" style="height:20px;display:block;"><span class="div_typename">小标题</span><div  class="mod">&nbsp;</div></span></div></a>
		<div  class="mobile6_pay" style="top:140px;color:#fff"><a  href="#"><span class="shop_skin_index_list"  rel="edit-t04"  no="3" style="height:30px;display:block;"><span class="div_typename">立即购买</span><div  class="mod">&nbsp;</div></span></div></a>
		<div  class="mobile6_margin"></div>
		
		<div  class="mobile6_nav" style="top:450px;">
			<div  class="mobile6_navbox swiper-container">
				<ul  class="swiper-wrapper">
					<li  class="swiper-slide " style="width:25%;">
						<a href="#"><span class="shop_skin_index_list mod_25"  rel="edit-t05"  no="4"><span class="div_typename">首页</span><div  class="mod">&nbsp;</div></span></a>
					</li>
					<li  class="swiper-slide " style="width:25%;">
						<a href="#"><span class="shop_skin_index_list mod_25"  rel="edit-t06"  no="5"><span class="div_typename">新品</span><div  class="mod">&nbsp;</div></span></a>
					</li>
					<li  class="swiper-slide " style="width:25%;">
						<a href="#"><span class="shop_skin_index_list mod_25"  rel="edit-t07"  no="6"><span class="div_typename">热卖</span><div  class="mod">&nbsp;</div></span></a>
					</li>
					<li  class="swiper-slide " style="width:25%;">
						<a href="#"><span class="shop_skin_index_list mod_25"  rel="edit-t08"  no="7"><span class="div_typename">促销</span><div  class="mod">&nbsp;</div></span></a>
					</li>				
				</ul>
			</div>
		</div>
    </div>
   	

    </div>
</div>

<script>
$("#shop_skin_index").css("height","550px");

</script>
<?php }else if($template_id==26){?>

 <link href="huazhuang5_1/css/shop.css" rel="stylesheet" type="text/css">
  <link href="huazhuang5_1/css/index.css" rel="stylesheet" type="text/css">
  <link href="huazhuang5_1/css/global.css" rel="stylesheet" type="text/css">
  <div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div  class="shop_skin_index_list logo"  rel="edit-t01"  no="0">
		<div  class="img"><img  src="huazhuang5_1/images/logo.jpg"></div><div  class="mod">&nbsp;</div>
    <div  id="SetHomeCurrentBox"  style="height: 11px; width: 150px;"></div></div>
    <div  class="search_box">
         <form  action="#"  method="get">
            <input  type="text"  name="Keyword"  class="input"  value=""  placeholder="输入商品名称...">
            <input  type="submit"  class="submit"  value=" ">
        </form>
     </div>
	<div  class="shop_skin_index_list banner"  rel="edit-t02"  no="1">
		<div  class="img"><img  src="huazhuang5_1/images/banner.jpg"></div><div  class="mod">&nbsp;</div>
    </div>
	<div  class="index-h">
		<div  class="shop_skin_index_list items"  rel="edit-t08"  no="7"><div  class="img"  style="width:60px; height:80px;text-align:center;padding-left:5px;"><img  src="huazhuang5/images/gift_icon.png" /></div></div>
		<div  class="shop_skin_index_list items"  rel="edit-t11"  no="10"><div  class="img"  style="width:60px; height:80px;text-align:center;padding-left:5px;"><img  src="huazhuang5/images/gift_icon.png" /></div></div>
		<div  class="shop_skin_index_list items"  rel="edit-t10"  no="9"><div  class="img"  style="width:60px; height:80px;text-align:center;padding-left:5px;"><img  src="huazhuang5/images/gift_icon.png" /></div></div>
		<div  class="shop_skin_index_list items"  rel="edit-t09"  no="8"><div  class="img"  style="width:60px; height:80px;text-align:center;padding-left:5px;"><img  src="huazhuang5/images/gift_icon.png" /></div></div>
	</div>
	<div  class="ind_wrap">
    	<div  class="ind_one_box">
            <div  class="lbar fl">
                <div  class="shop_skin_index_list"  rel="edit-t03"  no="2"><div  class="img"><img  src="huazhuang5/images/t3.jpg"></div><div  class="mod">&nbsp;</div></div>
            </div>
            <div  class="rbar fr">
                <div  class="shop_skin_index_list"  rel="edit-t04"  no="3"><div  class="img"><img  src="huazhuang5/images/t4.jpg"></div><div  class="mod">&nbsp;</div></div>
            </div>
            <div  class="clear"></div>
   		</div>
        <div  class="ad_items"><div  class="shop_skin_index_list"  rel="edit-t05"  no="4"><div  class="img"><img  src="huazhuang5/images/t5.jpg"></div><div  class="mod">&nbsp;</div></div></div>
		<div  class="products_list">
				<div  class="items"><a  href="#"  style="cursor: default; text-decoration: none;">DIORISSIMO</a></div>
		  </div>
        <div  class="ad_items"><div  class="shop_skin_index_list"  rel="edit-t06"  no="5"><div  class="img"><img  src="huazhuang5/images/t6.jpg"></div><div  class="mod">&nbsp;</div></div></div>
        <div  class="ad_items"><div  class="shop_skin_index_list"  rel="edit-t07"  no="6"><div  class="img"><img  src="huazhuang5/images/t7.jpg"></div><div  class="mod">&nbsp;</div></div></div>
    </div>
</div>

<?php }else if($template_id==27){?>
<link href="fushi_27/css/jquery.bxslider.css" rel="stylesheet" />
<link  rel="stylesheet"  href="fushi_27/css1/style.css">
<link  rel="stylesheet"  type="text/css"  href="fushi_27/css/idangerous.swiper.css">
<link  rel="stylesheet"  href="fushi_27/css/header_style5.css">
<link href="fushi2/css/shop.css" rel="stylesheet" type="text/css">
<link href="fushi2/css/index.css" rel="stylesheet" type="text/css">

<style>
.wenzi img{
	width: 40px;
	height: 40px;
	z-index: 999;
}

</style> 
<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
        <div  class="img"><img  src="fushi_27/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
       <div  id="SetHomeCurrentBox"  style="height: 445px; width: 310px;"></div>
	</div>
	
<div class="music_div" style="position:absolute;"> 
    <table> 
     <tbody>
      <tr> 
		<td class="img_td">
			<div class="music_img" >
				<img id="img1" src="fushi_27/images/gh.png" alt="" class="gh" style="z-index:333;"/>
				<a href="#" style="cursor: default; text-decoration: none;" >
					<div class="shop_skin_index_list" rel="edit-t02" no="1" style="float:none;height: 70px;">
					<div class="img wenzi" style="position: relative;top: 17px;left: 16px;"><img src="fushi_27/images/wz1.png" /></div>
					</div>
				</a>
				   
			</div>
		</td> 
		
       <td class="img_td">
			<div class="music_img">
				<img id="img2" src="fushi_27/images/gh.png" alt="" class="gh" style="z-index:333"/> 
				 <a href="#" style="cursor: default; text-decoration: none;" >
					<div class="shop_skin_index_list" rel="edit-t03" no="2" style="float:none;height: 70px;">
					<div class="img wenzi" style="position: relative;top: 17px;left: 16px;"><img src="fushi_27/images/wz2.png" /></div>
					</div>
				</a>
			 
			</div> 
		</td> 
       <td class="img_td">
        <div class="music_img"> 
         <img id="img3" src="fushi_27/images/gh.png" alt="" class="gh" style="z-index:333"/> 
			 <a href="#" style="cursor: default; text-decoration: none;" >
				<div class="shop_skin_index_list" rel="edit-t04" no="3" style="float:none;height: 70px;">
				<div class="img wenzi" style="position: relative;top: 17px;left: 16px;"><img src="fushi_27/images/wz3.png" /></div>
				</div>
			</a>
        </div> </td> 
      </tr> 
     </tbody>
    </table> 
   </div> 
   <div class="clear"></div>
	
	
	
	
	
    <div  id="index_m" style="bottom:0px;">
      

			<div  class="homeCnav" style="position:absolute;">
				<div  class="homeCnavbox swiper-container">
					<ul  class="swiper-wrapper"  style="width: 100%; height: 65px;background: #4A0101;">
						<li  class="swiper-slide"  style="width: 25%; height: 65px;">
											
								<div  class="shop_skin_index_list items"  rel="edit-t05"  no="4" style="width:56px;height:36px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_27/images/foot_img1.png"  width="31"  height="26"></div></div>
								<h2 ><span class="shop_skin_index_list" rel="edit-t09"  no="8"><span class="text"><span  href="#" class="div_typename" style="color:#fff;">栏目</span></span><div  class="mod">&nbsp;</div></span></h2>
							
						</li><li  class="swiper-slide"  style="width: 25%; height: 65px;">
												
								<div  class="shop_skin_index_list items"  rel="edit-t06"  no="5" style="width:56px;height:36px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_27/images/foot_img2.png"  width="31"  height="26"></div></div>
								<h2 ><span class="shop_skin_index_list" rel="edit-t10"  no="9"><span class="text"><span  href="#" class="div_typename" style="color:#fff;">栏目</span></span><div  class="mod">&nbsp;</div></span></h2>
							
						</li><li  class="swiper-slide"  style="width: 25%;height: 65px;">
							
								<div  class="shop_skin_index_list items"  rel="edit-t07"  no="6" style="width:56px;height:36px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_27/images/foot_img3.png"  width="31"  height="26"></div></div>
								<h2 ><span class="shop_skin_index_list" rel="edit-t11"  no="10"><span class="text"><span  href="#" class="div_typename" style="color:#fff;">栏目</span></span><div  class="mod">&nbsp;</div></span></h2>
							
						</li><li  class="swiper-slide"  style="width: 25%; height: 65px;">
							
								<div  class="shop_skin_index_list items"  rel="edit-t08"  no="7" style="width:56px;height:36px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_27/images/foot_img4.png"  width="31"  height="26"></div></div>
								<h2 ><span class="shop_skin_index_list" rel="edit-t12"  no="11"><span class="text"><span  href="#" class="div_typename" style="color:#fff;">栏目</span></span><div  class="mod">&nbsp;</div></span></h2>
							
						</li>			
					</ul>
				</div>
			</div>    
		
    </div>
</div>





<?php }else if($template_id==28){?>
  <link href="bao/css/shop.css" rel="stylesheet" type="text/css">
  <link href="bao/css/index.css" rel="stylesheet" type="text/css">
	<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="header">
			<div class="search">
				 <form action="" method="get">
					<input type="text" name="Keyword" class="input" value="" placeholder="输入商品名称...">
					<input type="submit" class="submit" value=" ">
				</form>
			</div>
		</div>
		<div class="shop_skin_index_list banner" rel="edit-t01" no="0" style="height:230px;">
			<div class="img"><img src="banner.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
		<div id="SetHomeCurrentBox" style="height: 130px; width: 302px;"></div></div>
		<div class="box">
			<ul>
				<li>
					<div class="shop_skin_index_list" rel="edit-t02" no="1">
						<div class="img"><img src="ad-1.jpg"></div><div class="mod">&nbsp;</div>
					</div>
				</li>
				<li>
					<div class="shop_skin_index_list" rel="edit-t03" no="2">
						<div class="img"><img src="ad-2.jpg"></div><div class="mod">&nbsp;</div>
					</div>
				</li>
				<li>
					<div class="shop_skin_index_list" rel="edit-t04" no="3">
						<div class="img"><img src="ad-3.jpg"></div><div class="mod">&nbsp;</div>
					</div>
				</li>
				<li>
					<div class="shop_skin_index_list" rel="edit-t05" no="4">
						<div class="img"><img src="ad-4.jpg"></div><div class="mod">&nbsp;</div>
					</div>
				</li>
			</ul>
			<div class="clear"></div>
		</div>
		<div class="shop_skin_index_list a0" rel="edit-t06" no="5">
			<div class="img"><img src="ad-0.jpg"></div><div class="mod">&nbsp;</div>
		</div>
	</div>
	
	
	
	
	<?php }else if($template_id==29){?>
	
	<link href="yzzj/css/shop.css" rel="stylesheet" type="text/css">
    <link href="yzzj/css/index.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="js/shop.js"></script>
	
 <div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="header" style="padding:0px;">
    	<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
        	<div class="img">
			  <img src="./yzzj/photo.jpg">
			</div>
			<div class="mod" style="display: none;">&nbsp;</div>
        </div>
    </div>
	
<style>
.div_font {
	font-size:10px;
	color:#F64004;
}
.uili li {
	float:left;
	text-align:center;
	line-height: 24px;
	height: 24px;
	width: 20%;
}
.sd_color {
	background-color:#F74A11;
}

</style>
    <div class="menu" style="padding:0px;background:#F9F9F9;">
    	<ul class="nav" style="float:right;">
        	<li style="width:49px;">
            	<a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t02" no="1"  iscate="1">
					<div class="img " style="background-size:30px 28px;"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t07" no="6">
						<div class="div_typename div_font" ></div>
					</div>
                </a>
            </li>
        	<li style="width:49px;">
            	<a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t03" no="2"  iscate="1">
					<div class="img " style="background-size:30px 28px;"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t08" no="7">
						<div class="div_typename div_font" ></div>
					</div>
                </a>
            </li>
        	<li style="width:49px;">
            	<a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t04" no="3"  iscate="1">
					<div class="img " style="background-size:31px 31px;"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t09" no="8">
						<div class="div_typename div_font" ></div>
					</div>
                </a>
            </li>
        	<li style="width:49px;">
            	<a href="#" style="cursor: default; text-decoration: none;">
                	<div class="shop_skin_index_list" rel="edit-t05" no="4"  iscate="1">
					  <div class="img " style="background-size:31px 31px;"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t10" no="9">
						<div class="div_typename div_font" ></div>
					</div>
                </a>
            </li>
			<li style="width:49px;" id="site_nav">
			 	<a href="#" style="cursor: default; text-decoration: none;">
					<div class="shop_skin_index_list"  rel="edit-t06" no="5" iscate="1">
						<div class="img "  style="background-size:44px 41px;"></div>
					</div>
                    <div class="name shop_skin_index_list"  rel="edit-t11" no="10">
						<div class="div_typename div_font"></div>
					</div>
                </a>
            </li>
        </ul>  
		<div  class="clear"></div>

		
    </div>	
	
	
		<div  class="ind_wrap">
			<div  class="category" id="site_nav1">
				<div  class="" style="background-color:#303537;height:30px;">
					
					<a  href="#" class="more" style="cursor: default; text-decoration: none;">查看更多</a>
					<div class="div_typename" style="color:white;margin:5px;">太阳眼镜</div>

				</div>
				<div  class="products" >
							<div  class="items" style="float:left;">
								<div  class="pro_img" style="width:141px;height:186px;" rel="edit-t16" no="15"><div class="img "></div><div >产品介绍</div><div style="float:right;background-color: #000;
										color: #fff;margin:0 3px 0 0;">1.5折</div><div ><span style="color: #ff0000;font-size:15px;">￥60</span> <span style="text-decoration: line-through;">￥70</span></div></div>
							</div>
							<div  class="items" style="float:left;">
								<div  class="pro_img" style="width:141px;height:186px;" rel="edit-t17" no="16"><div class="img "></div><div >产品介绍</div><div style="float:right;background-color: #000;
										color: #fff;margin:0 3px 0 0;">1.5折</div><div ><span style="color: #ff0000;font-size:15px;">￥60</span> <span style="text-decoration: line-through;">￥70</span></div></div>
							</div>
							
							<div  class="clear"></div>	
				</div>

				
			</div>
				
			
			
			<div  class="category">
				<div  class="" style="background-color:#303537;height:30px;">
					
					<a  href="#" class="more" style="cursor: default; text-decoration: none;">查看更多</a>
					<div class="div_typename" style="color:white;margin:5px;">婚纱</div>
						<div  class="products">
							<div  class="clear"></div>
						</div>
				</div>
			</div>
			
			<div  class="category">
				<div  class="" style="background-color:#303537;height:30px;">
					
					<a  href="#" class="more" style="cursor: default; text-decoration: none;">查看更多</a>
					<div class="div_typename" style="color:white;margin:5px;">短外套</div>
						<div  class="products">
							<div  class="clear"></div>
						</div>
				</div>
			</div>
			
			<div  class="category">
				<div  class="" style="background-color:#303537;height:30px;">
					
					<a  href="#" class="more" style="cursor: default; text-decoration: none;">查看更多</a>
					<div class="div_typename" style="color:white;margin:5px;">短裙</div>
						<div  class="products">
							<div  class="clear"></div>
						</div>
				</div>
			</div>
		</div>
    </div>  
	
	<?php }else if($template_id==30){?>
  <link href="lingshi/css/shop.css" rel="stylesheet" type="text/css">
  <link href="lingshi/css/index1.css" rel="stylesheet" type="text/css">
  <!--<link href="lingshi/css/main.css" rel="stylesheet" type="text/css">-->
  <div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="header">
        <div class="shop_skin_index_list logo" rel="edit-t01" no="0">
            <div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
            <div id="SetHomeCurrentBox" style="height: 40px; width: 120px;"></div>
		</div>
        <div class="search">
            <form action="" method="get">
                <input type="text" name="Keyword" class="input" value="" placeholder="输入商品名称...">
                <input type="submit" class="submit" value=" ">
            </form>
        </div>
    </div>
    <div class="menu">
    	<ul>
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t04" no="3"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t12" no="11">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t05" no="4"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t13" no="12">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
			<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t06" no="5"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t14" no="13">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	
        	<li>
			 <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t07" no="6"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t15" no="14">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
			
			
			
			<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t08" no="7"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t16" no="15">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t09" no="8"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t17" no="16">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
			<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t10" no="9"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t18" no="17">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	
        	<li>
			 <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t11" no="10"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t19" no="18">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	
        </ul>
        <div class="clear"></div>
    </div>
    <div class="box">
        <div class="shop_skin_index_list banner" rel="edit-t02" no="1">
            <div class="img"><img src="banner.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
        </div>
        <div class="blank3"></div>
		            <div class="item">
                <a href="#" style="cursor: default; text-decoration: none;">
                    <div class="img"><img src=""></div>
					<strong>aa</strong>
					<span>￥0.00</span>
                </a>
            </div>
                <div class="clear"></div>
        <div class="shop_skin_index_list a0" rel="edit-t03" no="2">
            <div class="img"><img src="a0.jpg"></div><div class="mod" style="display: none;">&nbsp;</div>
        </div>
        <div class="a1">
                </div>
        <div class="clear"></div>
                <div class="clear"></div>
      </div>
   </div>
	
	<?php }else if($template_id==31){?>
	<link href="css/shop.css" rel="stylesheet" type="text/css">
    <link href="fengge31/css/index.css" rel="stylesheet" type="text/css">
	<link href="fengge31/css/style.css" rel="stylesheet" type="text/css">
	   <div id="shop_skin_index"   <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="shop_skin_index_list" rel="edit-t01" no="0" style="height:117px;">
			<div class="img"></div>
			<div class="mod" style="display: none;">&nbsp;</div>
			
		</div>
		<div class="title shop_skin_index_list" rel="edit-t02" no="1" >
			<div class="div_typename"></div>
		</div>
		
		<style>
		.coupic{
			height:36px !important;
		}
		
		</style>
		<div class="menu coupon">
			<ul>
				<li>
				   <a href="#" style="cursor: default; text-decoration: none;" >
					<div class="shop_skin_index_list coupon_price" rel="edit-t03" no="2" style="float:none;">
					<div class="div_typename" style="color:#fff;font-size:20px;"></div>
					</div>
					<div class="coupon_desc shop_skin_index_list" rel="edit-t04" no="3" style="float:none;">
						<div class="div_typename" style="color:#D53A49;"></div>
					</div>
					<div class="shop_skin_index_list" rel="edit-t19" no="18">
						<div class="img coupic"></div>
						<div class="mod" style="display: none;">&nbsp;</div>
					</div>
					</a>
				</li>
				<li>
				   <a href="#" style="cursor: default; text-decoration: none;" >
					<div class="shop_skin_index_list coupon_price" rel="edit-t06" no="5" style="float:none;">
					<div class="div_typename" style="color:#fff;font-size:20px;"></div>
					</div>
					<div class="coupon_desc shop_skin_index_list" rel="edit-t07" no="6" style="float:none;">
						<div class="div_typename" style="color:#FF9900;"></div>
					</div>
					<div class="shop_skin_index_list" rel="edit-t20" no="19">
						<div class="img coupic"></div>
						<div class="mod" style="display: none;">&nbsp;</div>
					</div>
					</a>
				</li>
				<li>
				   <a href="#" style="cursor: default; text-decoration: none;" >
					<div class="shop_skin_index_list coupon_price" rel="edit-t09" no="8" style="float:none;">
					<div class="div_typename" style="color:#fff;font-size:20px;"></div>
					</div>
					<div class="coupon_desc shop_skin_index_list" rel="edit-t10" no="9" style="float:none;">
						<div class="div_typename" style="color:#79A003;"></div>
					</div>
					<div class="shop_skin_index_list" rel="edit-t21" no="20">
						<div class="img coupic"></div>
						<div class="mod" style="display: none;">&nbsp;</div>
					</div>
					</a>
				</li>
			</ul>
			<div class="clear"></div>
		</div>
		<div class="notice shop_skin_index_list" rel="edit-t12" no="11" >
			<div class="div_typename notice" style="width: 300px;"></div>
		</div>
		<div>
			<div class="shop_skin_index_list i0" rel="edit-t13" no="12">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
			</div>
			<div class="shop_skin_index_list i0" rel="edit-t14" no="13">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
			</div>
			<div class="shop_skin_index_list i0" rel="edit-t15" no="14">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
			</div>
			<div class="shop_skin_index_list i0" rel="edit-t16" no="15">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
			</div>
		</div>
		<div class="shop_skin_index_list banner" rel="edit-t17" no="16" style="height:160px;">
			<div class="img"></div><div class="mod">&nbsp;</div>
		<div id="SetHomeCurrentBox" style="height: 150px; width: 310px;"></div></div>
		<div class="shop_skin_index_list" >
			<div class="img" style="height:230px;"><img src="./fengge31/img/pic2.png"></div>
		</div>
	</div>
	
	
<?php }else if($template_id==32){?>
  <link href="yzzj2/css/shop.css" rel="stylesheet" type="text/css">
    <link href="yzzj2/css/index.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="js/shop.js"></script>
	
 <div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="header" style="padding:0px;">
    	<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
        	<div class="img">
			  <img src="./yzzj2/photo.jpg">
			</div>
			<div class="mod" style="display: none;">&nbsp;</div>
        </div>
    </div>
	
<style>
.div_font {
	font-size:10px;
	color:#F64004;
}
.uili li {
	float:left;
	text-align:center;
	line-height: 24px;
	height: 24px;
	width: 20%;
}
.sd_color {
	background-color:#F74A11;
}


</style>
    <div class="menu" style="padding:0px;background:#F9F9F9;">
    	<ul class="nav" style="float:right;">
        	<li style="width:49px;">
            	<a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t02" no="1"  iscate="1">
					<div class="img " style="background-size:80% 80%;"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t07" no="6">
						<div class="div_typename div_font" ></div>
					</div>
                </a>
            </li>
        	<li style="width:49px;">
            	<a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t03" no="2"  iscate="1">
					<div class="img " style="background-size:80% 80%;"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t08" no="7">
						<div class="div_typename div_font" ></div>
					</div>
                </a>
            </li>
        	<li style="width:49px;">
            	<a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t04" no="3"  iscate="1">
					<div class="img " style="background-size:80% 80%;"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t09" no="8">
						<div class="div_typename div_font" ></div>
					</div>
                </a>
            </li>
        	<li style="width:49px;">
            	<a href="#" style="cursor: default; text-decoration: none;">
                	<div class="shop_skin_index_list" rel="edit-t05" no="4"  iscate="1">
					  <div class="img " style="background-size:80% 80%;"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t10" no="9">
						<div class="div_typename div_font" ></div>
					</div>
                </a>
            </li>
			<li style="width:49px;" id="site_nav">
			 	<a href="#" style="cursor: default; text-decoration: none;">
					<div class="shop_skin_index_list"  rel="edit-t06" no="5" iscate="1">
						<div class="img "  style="background-size:80% 80%;"></div>
					</div>
                    <div class="name shop_skin_index_list"  rel="edit-t11" no="10">
						<div class="div_typename div_font"></div>
					</div>
                </a>
            </li>
        </ul>  
		<div  class="clear"></div>

		
    </div>	
	
	
		<div  class="ind_wrap">
			<div  class="category" id="site_nav1">
				<div  class="shop_skin_index_list" style="background-color:#303537;" rel="edit-t12"  no="11">
					
					<a  href="#" class="more" style="cursor: default; text-decoration: none;">查看更多</a>
					<div class="div_typename" style="color:white;margin:5px;"></div>

				</div>
				<div  class="products" >
					<div  class="items" style="float:left;">
						<div  class="pro_img" rel="edit-t16" no="15">
							<div class="img ">
								<img class="auto_img" src="yzzj2/images/1.jpg">
							</div>
						</div>
					</div>
					<div  class="items" style="float:left;">
						<div  class="pro_img"  rel="edit-t17" no="16">
							<div class="img ">
								<img class="auto_img" src="yzzj2/images/2.jpg">
							</div>
						</div>
					</div>		
					<div  class="items" style="float:left;">
						<div  class="pro_img"  rel="edit-t17" no="16">
							<div class="img ">
								<img class="auto_img" src="yzzj2/images/3.jpg">
							</div>
						</div>
					</div>						
					<div  class="clear"></div>	
				</div>
			</div>				
		</div>
    </div>
<?php }else if($template_id==33){?>
<link href="yzzj3/css/shop.css" rel="stylesheet" type="text/css">
<link href="yzzj3/css/index.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/shop.js"></script>
	
 <div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="header" style="padding:0px;">
    	<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
        	<div class="img">
			  <img src="./yzzj2/photo.jpg">
			</div>
			<div class="mod" style="display: none;">&nbsp;</div>
        </div>
    </div>
	
<style>
.div_font {
	font-size:10px;
	color:#F64004;
}
.uili li {
	float:left;
	text-align:center;
	line-height: 24px;
	height: 24px;
	width: 20%;
}
.sd_color {
	background-color:#F74A11;
}

</style>
	<div class="marquee shop_skin_index_list"  rel="edit-t18" no="17">
		<div class="div_typename r_color"></div>
	</div>
    <div class="menu" style="padding:0px;background:#F9F9F9;">
    	<ul class="nav">
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t02" no="1"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t10" no="9">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t03" no="2"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t11" no="10">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
			<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t04" no="3"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t12" no="11">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	
        	<li>
			 <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t05" no="4"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t13" no="12">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
			
			
			
			<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t06" no="5"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t14" no="13">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t07" no="6"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t15" no="14">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
			<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t08" no="7"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t16" no="15">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	
        	<li>
			 <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t09" no="8"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t17" no="16">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	
        </ul>
		<div  class="clear"></div>

		
    </div>	
	

	
		<div  class="ind_wrap" style="margin-top: 5px;">
			<div  class="category" id="site_nav1">
				<div  class="shop_skin_index_list" style="background-color:#303537;" rel="edit-t12"  no="11">
					
					<a  href="#" class="more" style="cursor: default; text-decoration: none;">查看更多</a>
					<div class="div_typename" style="color:white;margin:5px;"></div>

				</div>
				<div  class="products" >
					<div  class="items" style="float:left;">
						<div  class="pro_img" rel="edit-t16" no="15">
							<div class="img ">
								<img class="auto_img" src="yzzj2/images/1.jpg">
							</div>
						</div>
					</div>
					<div  class="items" style="float:left;">
						<div  class="pro_img"  rel="edit-t17" no="16">
							<div class="img ">
								<img class="auto_img" src="yzzj2/images/2.jpg">
							</div>
						</div>
					</div>		
					<div  class="items" style="float:left;">
						<div  class="pro_img"  rel="edit-t17" no="16">
							<div class="img ">
								<img class="auto_img" src="yzzj2/images/3.jpg">
							</div>
						</div>
					</div>						
					<div  class="clear"></div>	
				</div>
			</div>				
		</div>
    </div>
<?php }else if($template_id==34){?>
<link href="css/shop.css" rel="stylesheet" type="text/css">
	<link href="fengge34/css/style.css" rel="stylesheet" type="text/css">
    <!--<link href="fengge34/css/scroll.css" rel="stylesheet" type="text/css">-->
    <link href="fengge34/css/PreFoot.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="js/shop.js"></script>
    <!--<script src="fengge34/js/PreFoot.js"></script> -->
<style>
.bg_img img{max-width:1280px;height:160px;}
.members_head_nav_ri .div_font{font-size:12px;}
.iconjh-cart img{width:20px;height:20px;}
.iconjh-brand .shop_skin_index_list{height:25px;}
</style>  

 <div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="header bg_img" style="padding:0px;">
            <div class="shop_skin_index_list banner"  rel="edit-t01" no="0">
                <div class="img">
                    <img src="fengge34/css/images/20150521072033344276.jpg" class="">
                </div>   
                <div class="mod" style="display: none;">&nbsp;</div>                                  
            </div>      
        </div> 
                
            <section class="members_head_nav">
              <section class="members_head_nav_le" style="z-index:999;"><img src="fengge34/css/images/20141015093446677334.png" width="60" height="60"></section>
              <section class="members_head_nav_ri">
                <ul>
                  <li style="width:50px;"><span>30</span>
              		<div class="name shop_skin_index_list" rel="edit-t06" no="5">
						<div class="div_typename div_font" ></div>
					</div>
                  </li>
                  <li style="width:50px;">
	                 <span class="iconjh-brand" >
                      	<div class="shop_skin_index_list" rel="edit-t02" no="1" >
                        	<div class="img"></div>
                      	</div>
                     </span>
                     <div class="name shop_skin_index_list" rel="edit-t07" no="6">  
						<div class="div_typename div_font" ></div>
					</div>
                  </li>
                  <li style="width:50px;">
                  	<span class="iconjh-cart" >
                    	<div class="shop_skin_index_list" rel="edit-t03" no="2">
                        	<div class="img"></div>
                        </div>
                    </span>  
                    <div class="name shop_skin_index_list" rel="edit-t08" no="7">
						<div class="div_typename div_font" ></div>
					</div>
                  </li>
                  <li style="width:50px;">
                  	<span class="iconjh-member" >
                    	<div class="shop_skin_index_list" rel="edit-t04" no="3">
                        	<div class="img"></div>
                        </div>
                    </span>
                   <div class="name shop_skin_index_list" rel="edit-t09" no="8">
						<div class="div_typename div_font" ></div>
					</div>
                  </li>
                  <li style="width:51px;">
                  	<span class="iconjh-member" >
                    	<div class="shop_skin_index_list" rel="edit-t05" no="4">
                        	<div class="img"></div>
                        </div>
                    </span>
                   <div class="name shop_skin_index_list" rel="edit-t10" no="9">
						<div class="div_typename div_font" ></div>
					</div>
                  </li>
                  
                </ul>
              </section>
            </section>
		
        <div class="members_con">
            <section class="members_goodspic">
                <div class="pro_list_title">
                    <div class="left">防晒/沙滩/空调披肩系列</div>
                    <div class="right">更多&gt;&gt;</div>
                </div>
            <ul>
                <li class="mingoods">
                   
                    <img class="lazy" src="fengge34/css/images/201552918540387.jpg" data-original="" width="100%" style="display: inline;">
                    
                    <span class="goods-title">素色防晒披肩</span>
                    <span class="price">￥68.00</span>
                    <em style="padding-left:10px; font-size:10px; color:#999">已售：11156笔</em>  
                </li>
                <li class="mingoods">
                   
                    <img class="lazy" src="fengge34/css/images/2015520124711501.jpg" data-original="" width="100%" style="display: inline;">
                   
                    <span class="goods-title">素色防晒披肩</span>
                    <span class="price">￥68.00</span>
                    <em style="padding-left:10px; font-size:10px; color:#999">已售：11156笔</em>  
                </li>
                <li class="mingoods">
                   
                    <img class="lazy" src="fengge34/css/images/2015520124711501.jpg" data-original="" width="100%" style="display: inline;">
                   
                    <span class="goods-title">素色防晒披肩</span>
                    <span class="price">￥68.00</span>
                    <em style="padding-left:10px; font-size:10px; color:#999">已售：11156笔</em>  
                </li>
                <li class="mingoods">
                    
                    <img class="lazy" src="fengge34/css/images/2015520124711501.jpg" data-original="" width="100%" style="display: inline;">
                   
                    <span class="goods-title">素色防晒披肩</span>
                    <span class="price">￥68.00</span>
                    <em style="padding-left:10px; font-size:10px; color:#999">已售：11156笔</em>  
                </li>
            </ul>
            </section>
        </div>
	
              
                <!--distribution contact us end-->
                    <dl class="sub-nav nav-b5">
                        <dd class="active">
                            <div class="nav-b5-relative shop_skin_index_list" rel="edit-t11" no="10"><div class="img"></div></div><div class="name shop_skin_index_list" rel="edit-t15" no="14">
						<div class="div_typename div_font" ></div>
					</div>
                        </dd>
                        <dd>
                              <div class="nav-b5-relative shop_skin_index_list" rel="edit-t12" no="11"><div class="img"></div></div><div class="name shop_skin_index_list" rel="edit-t16" no="15">
						<div class="div_typename div_font" ></div>
					</div>
                        </dd>
                        <dd>
                              <div class="nav-b5-relative shop_skin_index_list" rel="edit-t13" no="12"><div class="img"></div></div><div class="name shop_skin_index_list" rel="edit-t17" no="16">
						<div class="div_typename div_font" ></div>
					</div>
                        </dd>
                        <dd>
                              <div class="nav-b5-relative shop_skin_index_list" rel="edit-t14" no="13"><div class="img"></div></div><div class="name shop_skin_index_list" rel="edit-t18" no="17">
						<div class="div_typename div_font" ></div>
					</div>
                        </dd>
                    </dl>
              
  
	
    </div>
<?php }else if($template_id==35){?>
<link href="yzzj4/css/shop.css" rel="stylesheet" type="text/css">
<link href="yzzj4/css/index.css?ver=<?php echo time();?>" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/shop.js"></script>
	
 <div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="header" style="padding:0px;height:300px">
    	<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
        	<div class="img">
			  <img src="./yzzj2/photo.jpg">
			</div>
			<div class="mod" style="display: none;height:290px">&nbsp;</div>
        </div>
    </div>

 	<div class="marquee shop_skin_index_list"  rel="edit-t18" no="17">
		<div class="div_typename r_color"></div>
	</div>
    <div class="menu" style="padding:0px;background:#F9F9F9;">
    	<ul class="nav">
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t02" no="1"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t10" no="9">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t03" no="2"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t11" no="10">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
			<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t04" no="3"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t12" no="11">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	
        	<li>
			 <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t05" no="4"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t13" no="12">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
			
			
			
			<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t06" no="5"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t14" no="13">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t07" no="6"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t15" no="14">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
			<li>
			   <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t08" no="7"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t16" no="15">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	
        	<li>
			 <a href="#" style="cursor: default; text-decoration: none;" >
                	<div class="shop_skin_index_list" rel="edit-t09" no="8"  iscate="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t17" no="16">
						<div class="div_typename r_color"></div>
					</div>
                </a>
			</li>
        	
        </ul>
		<div  class="clear"></div>

		
    </div>	
	
		<div class="button_tab_menu">
			<table>
				<tbody>
					<tr>
						<td align="center" valign="middle" style="width: 19%;">
							<div class="footer_div">
								<div class="shop_skin_index_list" rel="edit-t19" no="18"  iscate="1">
									<div class="footer_tab_index_0 footer_icon footer_icon_0 img"></div>
								</div>
								<div class="name shop_skin_index_list" rel="edit-t23" no="22">
									<div class="div_typename r_color"></div>
								</div>
							</div>							
						</td>
						<td align="center" valign="middle" style="width: 19%;">
							<div class="footer_div">
								<div class="shop_skin_index_list" rel="edit-t20" no="19"  iscate="1">
									<div class="footer_tab_index_1 footer_icon footer_icon_1 img"></div>
								</div>
								<div class="name shop_skin_index_list" rel="edit-t24" no="23">
									<div class="div_typename r_color"></div>
								</div>
							</div>
						</td>
						<td align="center" valign="middle" style="width: 24%;">
							<a style="width: 100%">
								<div id="logo" class="shop_skin_index_list" style="position:absolute;" rel="edit-t27" no="26">
															
									<div id="divuserheader" class="img"></div>									
								
								</div>
							</a>
						</td>
						<td align="center" valign="middle" style="width: 19%;">
							<div class="footer_div">
								<div class="shop_skin_index_list" rel="edit-t21" no="20"  iscate="1">
									<div class="footer_tab_index_2 footer_icon footer_icon_2 img"></div>
								</div>
								<div class="name shop_skin_index_list" rel="edit-t25" no="24">
									<div class="div_typename r_color"></div>
								</div>
							</div>
						</td>
						<td align="center" valign="middle" style="width: 19%;">
							<div class="footer_div">
								<div class="shop_skin_index_list" rel="edit-t22" no="21"  iscate="1">
									<div class="footer_tab_index_3 footer_icon footer_icon_3 img"></div>
								</div>
								<div class="name shop_skin_index_list" rel="edit-t26" no="25">
									<div class="div_typename r_color"></div>
								</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	<!-- 	 <div  class="ind_wrap" style="margin-top: 5px;">
			<div  class="category" id="site_nav1">
				<div  class="shop_skin_index_list" style="background-color:#303537;" rel="edit-t12"  no="11">
					
					<a  href="#" class="more" style="cursor: default; text-decoration: none;">查看更多</a>
					<div class="div_typename" style="color:white;margin:5px;"></div>

				</div>
				<div  class="products" >
					<div  class="items" style="float:left;">
						<div  class="pro_img" rel="edit-t16" no="15">
							<div class="img ">
								<img class="auto_img" src="yzzj2/images/1.jpg">
							</div>
						</div>
					</div>
					<div  class="items" style="float:left;">
						<div  class="pro_img"  rel="edit-t17" no="16">
							<div class="img ">
								<img class="auto_img" src="yzzj2/images/2.jpg">
							</div>
						</div>
					</div>		
					<div  class="items" style="float:left;">
						<div  class="pro_img"  rel="edit-t17" no="16">
							<div class="img ">
								<img class="auto_img" src="yzzj2/images/3.jpg">
							</div>
						</div>
					</div>						
					<div  class="clear"></div>	
				</div>
			</div>				
		</div> -->
    </div>
    <?php }else if($template_id==36){?>
<link href="fengge36/shop.css" rel="stylesheet" type="text/css">
<link href="fengge36/index.css?ver=<?php echo time();?>" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="fengge36/base_index.css">
<link rel="stylesheet" href="fengge36/showcase_index.css">
<link rel="stylesheet" href="fengge36/index_36.css">

<script src="fengge36/1_files/Swipe.js"></script>
<script src="fengge36/1_files/index.js"></script>
<script type="text/javascript" src="js/shop.js"></script>
	
 <div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="header" style="padding:0px;height:160px">
    	<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
        	<div class="img">
			  <img src="./yzzj2/photo.jpg">
			</div>
			<div class="mod" style="display: none;height:160px">&nbsp;</div>
        </div>
    </div>

 	<div class="marquee shop_skin_index_list"  rel="edit-t18" no="17">
		<div class="div_typename r_color"></div>
	</div>
    	<style>
        .app-preview-anmin .img{width:40px;margin:0 auto;}
		.name{height:18px;display:block;} 
        </style>
     <div id="app-field-model-page-1" style="width:100%">
        <div class="app-field clearfix clearfix_list b_white app-preview-anmin"><!--icon开始-->
            <div style="height: 10px;"></div>
           
            <div style="width:25%;float:left;text-align:center;">
					<div class="shop_skin_index_list" rel="edit-t02" no="1">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t10" no="9">
						<div class="div_typename r_color"></div>
					</div>
                
                
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t03" no="2" >
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t11" no="10">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t04" no="3">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t12" no="11">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
               <div class="shop_skin_index_list" rel="edit-t05" no="4">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t13" no="12">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t06" no="5">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t14" no="13">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t07" no="6">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t15" no="14">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t08" no="7">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t16" no="15">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t09" no="8" >
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t17" no="16">
						<div class="div_typename r_color"></div>
					</div>
            </div>
    	</div>        
    </div>

		
            <div class="shop_skin_index_list banner" rel="edit-t19" no="18" style="height:100px;">
                <div class="img" style="height:100px;">
                  
                </div>
                <div class="mod" style="display: none;height:100px">&nbsp;</div>
            </div>
    	
        	<div style="height:10px;display:block;"></div>
        	<div class="shop_skin_index_list banner" rel="edit-t20" no="19" style="height:100px;">
                <div class="img" style="height:100px;">
                  
                </div>
                <div class="mod" style="display: none;height:100px">&nbsp;</div>
            </div>
            
			<div  class="clear"></div>
            <div class="button_tab_menu">
            <style>
            .footer_div .img{height:25px;width:25px;}
            </style>
            
			<table>
				<tbody>
					<tr>
						<td align="center" valign="middle" style="width: 19%;">
							<div class="footer_div">
								<div class="shop_skin_index_list" rel="edit-t21" no="20"  iscate="1">
									<div class="footer_tab_index_0 footer_icon footer_icon_0 img"></div>
								</div>
								<div class="name shop_skin_index_list" rel="edit-t25" no="24">
									<div class="div_typename r_color"></div>
								</div>
							</div>							
						</td>
						<td align="center" valign="middle" style="width: 19%;">
							<div class="footer_div">
								<div class="shop_skin_index_list" rel="edit-t22" no="21"  iscate="1">
									<div class="footer_tab_index_1 footer_icon footer_icon_1 img"></div>
								</div>
								<div class="name shop_skin_index_list" rel="edit-t26" no="25">
									<div class="div_typename r_color"></div>
								</div>
							</div>
						</td>
						<td align="center" valign="middle" style="width: 24%;">
							<a style="width: 100%">
								<div id="logo" class="shop_skin_index_list" style="position:relative;width:50px;height:50px;left:0px;top:7px;" rel="edit-t29" no="28">
															
									<div id="divuserheader" class="img"></div>									
								
								</div>
							</a>
						</td>
						<td align="center" valign="middle" style="width: 19%;">
							<div class="footer_div">
								<div class="shop_skin_index_list" rel="edit-t23" no="22"  iscate="1">
									<div class="footer_tab_index_2 footer_icon footer_icon_2 img"></div>
								</div>
								<div class="name shop_skin_index_list" rel="edit-t27" no="26">
									<div class="div_typename r_color"></div>
								</div>
							</div>
						</td>
						<td align="center" valign="middle" style="width: 19%;">
							<div class="footer_div">
								<div class="shop_skin_index_list" rel="edit-t24" no="23"  iscate="1">
									<div class="footer_tab_index_3 footer_icon footer_icon_3 img"></div>
								</div>
								<div class="name shop_skin_index_list" rel="edit-t28" no="27">
									<div class="div_typename r_color"></div>
								</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

    </div>    
    
<?php }else if($template_id==37){?>
<link href="fengge37/shop.css" rel="stylesheet" type="text/css">
<link href="fengge37/index.css?ver=<?php echo time();?>" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="fengge37/base_index.css">
<link rel="stylesheet" href="fengge37/showcase_index.css">
<link rel="stylesheet" href="fengge37/index_36.css">
<link href="fengge37/PreFoot.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/shop.js"></script>
	
 <div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="header" style="padding:0px;height:160px">
    	<div class="shop_skin_index_list banner" rel="edit-t01" no="0">
        	<div class="img">
			  <img src="./yzzj2/photo.jpg">
			</div>
			<div class="mod" style="display: none;height:160px">&nbsp;</div>
        </div>
    </div>

 	<div class="marquee shop_skin_index_list"  rel="edit-t18" no="17">
		<div class="div_typename r_color" style="color:#e60014"></div>
	</div>
    	<style>
        .app-preview-anmin .img{width:40px;height:40px;margin:0 auto;}
        </style>
     <div id="app-field-model-page-1" style="width:100%">
        <div class="app-field clearfix clearfix_list b_white app-preview-anmin"><!--icon开始-->
            <div style="height: 10px;"></div>
           
            <div style="width:25%;float:left;text-align:center;">
					<div class="shop_skin_index_list" rel="edit-t02" no="1" >
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t10" no="9">
						<div class="div_typename r_color"></div>
					</div>
                
                
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t03" no="2" >
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t11" no="10">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t04" no="3">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t12" no="11">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
               <div class="shop_skin_index_list" rel="edit-t05" no="4">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t13" no="12">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t06" no="5">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t14" no="13">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t07" no="6">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t15" no="14">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t08" no="7">
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t16" no="15">
						<div class="div_typename r_color"></div>
					</div>
            </div>
            <div style="width:25%;float:left;text-align:center;">
                <div class="shop_skin_index_list" rel="edit-t09" no="8" >
					<div class="img"></div>
					</div>
                     <div class="name shop_skin_index_list" rel="edit-t17" no="16">
						<div class="div_typename r_color"></div>
					</div>
            </div>
    	</div>        
    </div>
    <style>
    	.video_class{width:100%;height:250px;}
    </style>
	<div class="shop_skin_index_list" rel="edit-t31" no="30"><!--添加视频-->
    	<div class="div_typevideo"></div>
	</div>
		
            <div class="shop_skin_index_list banner" rel="edit-t19" no="18" style="height:100px;">
                <div class="img" style="height:100px;">
                  
                </div>
                <div class="mod" style="display: none;height:100px">&nbsp;</div>
            </div>
    	
        	<div style="height:10px;display:block;"></div>
        	<div class="shop_skin_index_list banner" rel="edit-t20" no="19" style="height:100px;">
                <div class="img" style="height:100px;">
                  
                </div>
                <div class="mod" style="display: none;height:100px">&nbsp;</div>
            </div>
            
            <style>
            .brand_list ul li{list-style:none;}
			.brand_list li{float:left;}
			.brand_list li img{width:106px;height:71px;}
			.brand_head{height:20px;line-height:20px;position:relative}
			.brand_head .cat_name{float:left;font-size:14px;font-weight:bold;color:#000;margin-left:15px;}
			.brand_head .more{float:right;font-size:12px;color:#000;margin-right:5px;}
            </style> 
            
            <div class="app-field clearfix clearfix_list  b_white app-preview-anmin" style="border-bottom:1px dashed #999;padding-bottom:4px;margin-bottom:5px;"><!--分类楼层开始-->
              <div style="height:10px"></div>
              <div style="width:100%;background-color:#fff">
                  <div style="height:5px"></div>
                      <div class="brand_head">
                          <span class="cat_name">名品专区</span>
                          <span class="more">更多>></span>
                      </div>
                      <div style="height:5px"></div>
                      <div class="brand_list">
                      	<ul>
                        	<li>
                            	<img src="fengge37/images/brand1.png" >
                            </li>
                            <li>
                            	<img src="fengge37/images/brand2.png" >
                            </li>
                            <li>
                            	<img src="fengge37/images/brand3.png" >
                            </li>
                            <li>
                            	<img src="fengge37/images/brand4.png" >
                            </li>
                            <li>
                            	<img src="fengge37/images/brand5.png" >
                            </li>
                            <li>
                            	<img src="fengge37/images/brand6.png" >
                            </li>
                        </ul>
                      </div>

               </div> 
            </div>
            
            <div class="shop_skin_index_list banner" rel="edit-t22" no="21" style="height:100px;">
                <div class="img" style="height:100px;">
                  
                </div>
                <div class="mod" style="display: none;height:100px">&nbsp;</div>
            </div>
            
            
            <div class="app-field clearfix clearfix_list  b_white app-preview-anmin"><!--搜索开始-->
                <div class="custom-search">        
                  <form>              
                    <input type="search" class="custom-search-input" placeholder="商品搜索：请输入商品关键字" name="searchname"  id="searchname" value="" onkeydown="javascript:if(event.keyCode==13){search_name();return false;}">
                    <button type="button" onclick="search_name()" class="custom-search-button">搜索</button>
                  </form>                                   
            	</div>  
         	</div>
            
			<div  class="clear"></div>
            <div class="button_tab_menu">
                <!--distribution contact us end-->
                <dl class="sub-nav nav-b5">
                    <dd class="active">
                        <div class="nav-b5-relative shop_skin_index_list" rel="edit-t23" no="22"><div class="img"></div></div><div class="name shop_skin_index_list" rel="edit-t27" no="26">
                    <div class="div_typename div_font" style="color:#2b9939"></div>
                </div>
                    </dd>
                    <dd>
                          <div class="nav-b5-relative shop_skin_index_list" rel="edit-t24" no="23"><div class="img"></div></div><div class="name shop_skin_index_list" rel="edit-t28" no="27">
                    <div class="div_typename div_font" ></div>
                </div>
                    </dd>
                    <dd>
                          <div class="nav-b5-relative shop_skin_index_list" rel="edit-t25" no="24"><div class="img"></div></div><div class="name shop_skin_index_list" rel="edit-t29" no="28">
                    <div class="div_typename div_font" ></div>
                </div>
                    </dd>
                    <dd>
                          <div class="nav-b5-relative shop_skin_index_list" rel="edit-t26" no="25"><div class="img"></div></div><div class="name shop_skin_index_list" rel="edit-t30" no="29">
                    <div class="div_typename div_font" ></div>
                </div>
                    </dd>
                </dl>
		</div>
		
    </div>
<?php }else if($template_id==38){?>
<link href="css/shop.css" rel="stylesheet" type="text/css">
	<link href="fengge38/css/style.css" rel="stylesheet" type="text/css">
    <!--<link href="fengge34/css/scroll.css" rel="stylesheet" type="text/css">-->
    <link href="fengge38/css/PreFoot.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="js/shop.js"></script>
    <!--<script src="fengge34/js/PreFoot.js"></script> -->
 

 <div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="header bg_img" style="padding:0px;">
            <div class="shop_skin_index_list banner"  rel="edit-t01" no="0">
                <div class="img">
                    <img src="fengge34/css/images/20150521072033344276.jpg" class="">
                </div>   
                <div class="mod" style="display: none;">&nbsp;</div>                                  
            </div>      
        </div>  
        <style>
         .shop_skin_index_list .div_typename{display:block;height:20px!important;}
		
        </style>                  
            <section class="members_head_nav">
              <section class="members_head_nav_le" style="z-index:999;"><img src="fengge38/css/images/fengge38logo.png" width="60" height="60"></section>
              <section class="members_head_nav_ri">
                <ul>
                  <li style="width:20%;">
	                 <span class="iconjh" >
                      	<div class="shop_skin_index_list" rel="edit-t02" no="1" >
                        	<div class="img"></div>
                      	</div>
                     </span>
                     <div class="name shop_skin_index_list" rel="edit-t07" no="6">  
						<div class="div_typename div_font" ></div>
					</div>
                  </li>
                  <li style="width:20%;">
                  	<span class="iconjh" >
                    	<div class="shop_skin_index_list" rel="edit-t03" no="2">
                        	<div class="img"></div>
                        </div>
                    </span>
                    <div class="name shop_skin_index_list" rel="edit-t08" no="7">
						<div class="div_typename div_font" ></div>
					</div>
                  </li>
                  <li style="width:20%;">
                  	<span class="iconjh" >
                    	<div class="shop_skin_index_list" rel="edit-t04" no="3">
                        	<div class="img"></div>
                        </div>
                    </span>
                   <div class="name shop_skin_index_list" rel="edit-t09" no="8">
						<div class="div_typename div_font" ></div>
					</div>
                  </li>
                  <li style="width:20%;">
                  	<span class="iconjh" >
                    	<div class="shop_skin_index_list" rel="edit-t05" no="4">
                        	<div class="img"></div>
                        </div>
                    </span>
                   <div class="name shop_skin_index_list" rel="edit-t10" no="9">
						<div class="div_typename div_font" ></div>
					</div>
                  </li>
                  
                  <li style="width:20%;">
                  	<span class="iconjh" >
                    	<div class="shop_skin_index_list" rel="edit-t06" no="5">
                        	<div class="img"></div>
                        </div>
                    </span>
                   <div class="name shop_skin_index_list" rel="edit-t11" no="10">
						<div class="div_typename div_font" ></div>
					</div>
                  </li>
                 
                </ul>
              </section>
            </section>
            
            <!--<div style="width:100%;border-bottom:1px solid #ccc;display:block;margin-top:2px;float:left;"></div>
            <div class="ad_title" style="float:left;display:block;margin:15px 0px;width:100%;text-align:center;">
            	<span style="margin-left:2%;width:32%;border-top:2px solid #ccc;margin-top:12px;display:block;float:left;"></span>	
                <div class="name shop_skin_index_list" rel="edit-t16" no="15" style="width:32%;float:left;height:26px;overflow:hidden;">
                    <div class="div_typename div_font" style="font-size:18px!important;"></div>
                </div>
                <span style="didplay:block;margin-right:2%;width:32%;border-top:2px solid #ccc;margin-top:12px;display:block;float:right;"></span>	
            </div>
            --> 
			<div class="members_con members_co">
                <div class="shop_skin_index_list"  rel="edit-t12" no="11">
                	<div class="img"></div>   
                </div>  
            </div>
            
            <div class="members_con members_co1">
               
                	<div class="img">
                    	<img src="fengge38/css/images/indexcatimg.png">
                    </div>   
               
            </div>
           
        <div class="members_con margin" >
                <section class="members_goodspic">                
                    <ul>
                        <li class="mingoods">
                           
                            <img class="lazy" src="fengge38/css/images/product.png" data-original="" width="100%" style="display: inline;">
                            
                            <span class="goods-title">素色防晒披肩</span>
                            <span class="price">￥68.00</span>
                          	<div class="buy_btn"><img src="fengge38/css/images/buy_now.png"></div>
                            
                        </li>
                        <li class="mingoods">
                           
                            <img class="lazy" src="fengge38/css/images/product.png" data-original="" width="100%" style="display: inline;">
                           
                            <span class="goods-title">素色防晒披肩</span>
                            <span class="price">￥68.00</span>
                           
                        </li>
                        <li class="mingoods">
                           
                            <img class="lazy" src="fengge38/css/images/product.png" data-original="" width="100%" style="display: inline;">
                           
                            <span class="goods-title">素色防晒披肩</span>
                            <span class="price">￥68.00</span>
                           	<div class="buy_btn"><img src="fengge38/css/images/buy_now.png"></div>
                           
                        </li>
                        <li class="mingoods">
                            
                            <img class="lazy" src="fengge38/css/images/product.png" data-original="" width="100%" style="display: inline;">
                           
                            <span class="goods-title">素色防晒披肩</span>
                            <span class="price">￥68.00</span>
                            <div class="buy_btn"><img src="fengge38/css/images/buy_now.png"></div>
                            
                        </li>
                    </ul>
                </section>
            </div>

           <!--<div class="members_con" style="width:100%;display:block;border-top:1px solid #8b8b8b;margin-top:20px;margin-left:0px!important;padding-top:5px;">
         		<div class="shop_skin_index_list bottom_menu_img"  rel="edit-t17" no="16" >
                	<div class="img"></div>   
                </div>  
                <div class="name shop_skin_index_list bottom_menu" rel="edit-t18" no="17">
						<div class="div_typename div_font bottom_font" ></div>
                </div>
                <div class="name shop_skin_index_list bottom_menu" rel="edit-t19" no="18">
                    <div class="div_typename div_font bottom_font" ></div>
                </div>
                <div class="name shop_skin_index_list bottom_menu" rel="edit-t20" no="19">
                    <div class="div_typename div_font bottom_font" ></div>
                </div>
         	</div>
            -->
    </div> 
<?php }else if($template_id==39){?>
	<link href="css/shop.css" rel="stylesheet" type="text/css">
	<link href="fengge39/css/style.css" rel="stylesheet" type="text/css">
   <link rel="stylesheet" href="fengge39/css/index.css" />
	<script type="text/javascript" src="js/shop.js"></script>
 

 <div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="header bg_img" style="padding:0px;">
            <div class="shop_skin_index_list banner"  rel="edit-t01" no="0">
                <div class="img">
                    <img src="fengge34/css/images/20150521072033344276.jpg" class="">
                </div>   
                <div class="mod" style="display: none;">&nbsp;</div>                                  
            </div>      
        </div>   
        <style>
         .shop_skin_index_list .div_typename{display:block;height:20px!important;}
		 .members_head_nav .div_typename{color:#fff!important;width:56px;display:block;font-size:14px!important;line-height:26px;}
		
        </style>              
            <section class="members_head_nav">
             <div class="phone-homepage ">
			<ul>
				<div class="phone-homepage-first" style="z-index:999"><img src="fengge39/img/phone-img3.png" class="img-responsive"/></div>
				<li>
                	<div class="name shop_skin_index_list" rel="edit-t02" no="1">
                    	<div class="div_typename div_font" ></div>
                	</div>
                </li>
				<li>
                	<div class="name shop_skin_index_list" rel="edit-t03" no="2">
                    	<div class="div_typename div_font" ></div>
                	</div>
                </li>
                <li>
                	<div class="name shop_skin_index_list" rel="edit-t04" no="3">
                    	<div class="div_typename div_font" ></div>
                	</div>
                </li>
                <li>
                	<div class="name shop_skin_index_list" rel="edit-t05" no="4">
                    	<div class="div_typename div_font" ></div>
                	</div>
                </li>
			</ul>
		</div>
            </section>

			<div class="members_con members_co">
                <div class="shop_skin_index_list"  rel="edit-t06" no="5">
                	<div class="img"></div>   
                </div>  
            </div>  
            <div class="members_con members_co">
                <div class="shop_skin_index_list"  rel="edit-t07" no="6">
                	<div class="img"></div>   
                </div>   
            </div> 

            <div class="members_con" style="width:100%;display:block;margin-top:5px;margin-left:0px!important;padding-top:5px;background:#ecffff;">
         		
                <div class="name shop_skin_index_list bottom_menu" rel="edit-t08" no="7">
						<div class="div_typename div_font bottom_font" ></div>
                </div>
                <div class="name shop_skin_index_list bottom_menu" rel="edit-t09" no="8">
                    <div class="div_typename div_font bottom_font" ></div>
                </div>
                <div class="name shop_skin_index_list bottom_menu" rel="edit-t10" no="9">
                    <div class="div_typename div_font bottom_font" ></div>
                </div>
         	</div>
    </div>  
<?php }else if($template_id==40){?>
	<link href="css/shop.css" rel="stylesheet" type="text/css">
	<link href="fengge40/css/style.css" rel="stylesheet" type="text/css">
  <!-- <link rel="stylesheet" href="fengge39/css/index.css" />-->
	<script type="text/javascript" src="js/shop.js"></script>
 

<div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div class="main">
            <div class="header">
                <div class="bg-green">
                	<div class="shop_skin_index_list banner"  rel="edit-t01" no="0">
                		<div class="img"></div>   
                		<div class="mod" style="display: none;">&nbsp;</div> 
                    </div>                
                </div>
                <style>
                	.div_typename{display:block;height:20px;overflow:hidden;}
                </style>
                <img src="fengge40/images/pic.png" class="head-photo">
                <div class="bg-white">
                    <div class="op">
                    	<div class="shop_skin_index_list" rel="edit-t05" no="4">
                        	<div class="img"></div>
                        </div>
                        <p class="text-grey">
                        	<div class="name shop_skin_index_list" rel="edit-t06" no="5">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </div>
                    <div class="op br-right">
                    	<div class="shop_skin_index_list" rel="edit-t03" no="2">
                        	<div class="img"></div>
                        </div>
                        <p class="text-grey">
                        	<div class="name shop_skin_index_list" rel="edit-t04" no="3">
								<div class="div_typename div_font" ></div>
							</div>
                       	</p>
                    </div>
                    <div class="op br-right">
                    	<p class="text-green">62</p>
                    	<p class="text-grey">                        	
                        	<div class="name shop_skin_index_list" rel="edit-t02" no="1">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </div>
                    
                    
                    
                </div>
            </div>
            <div class="search-box">
                <input type="text" class="search-text" placeholder="商品搜索：请输入商品关键字">
                <button class="search-button">搜索</button>
            </div>
            <div class="item-box">
            <div class="content-box">
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t07" no="6">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t19" no="18">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t08" no="7">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t20" no="19">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t09" no="8">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t21" no="20">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t10" no="9">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t22" no="21">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t11" no="10">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t23" no="22">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t12" no="11">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t24" no="23">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t13" no="12">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t25" no="24">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t14" no="13">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t57" no="56">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t15" no="14">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t26" no="25">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t16" no="15">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t27" no="26">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t17" no="15">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t28" no="27">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="icon-box">
                	<a href="#">
                    	<div class="shop_skin_index_list" rel="edit-t18" no="17">
                        	<div class="img"></div>
                        </div>
                        <p>
                        	<div class="name shop_skin_index_list" rel="edit-t29" no="28">
								<div class="div_typename div_font" ></div>
							</div>
                        </p>
                    </a>
                </div>
                <div class="clear"></div>
            </div>
            <div class="bg-grey"></div>
            </div>
            <div class="hot-market">
                <div class="title-box">
                    <div class="title">
                    	<div class="name shop_skin_index_list" rel="edit-t47" no="46">
								<div class="div_typename div_font" ></div>
						</div>
                    </div>
                </div>
                <div class="market-box">
                    <div class="half-box border-r">
                        
                        	<div class="name shop_skin_index_list" rel="edit-t36" no="35">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                      
                       
                        <div class="shop_skin_index_list " rel="edit-t30" no="29">
                        	<div class="img hot-market hotimg"></div>
                        </div>
                        
                    </div>
                    <div class="half-box">
                        <p class="name">
                        	<div class="name shop_skin_index_list" rel="edit-t37" no="36">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                        <div class="shop_skin_index_list" rel="edit-t31" no="30">
                        	<div class="img hot-market hotimg"></div>
                        </div>
                    </div>
                    <div class="four-box border-r">
                        <p class="name">
                        	<div class="name shop_skin_index_list" rel="edit-t38" no="37">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                        <div class="shop_skin_index_list" rel="edit-t32" no="31">
                        	<div class="img hot-market hotimg2"></div>
                        </div>
                    </div>
                    <div class="four-box border-r">
                        <p class="name">
                        	<div class="name shop_skin_index_list" rel="edit-t39" no="38">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                        <div class="shop_skin_index_list" rel="edit-t33" no="32">
                        	<div class="img hot-market hotimg2"></div>
                        </div>
                    </div>
                    <div class="four-box border-r">
                        <p class="name">
                        	<div class="name shop_skin_index_list" rel="edit-t40" no="39">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                        <div class="shop_skin_index_list" rel="edit-t34" no="33">
                        	<div class="img hot-market hotimg2"></div>
                        </div>
                    </div>
                    <div class="four-box">
                        <p class="name">
                        	<div class="name shop_skin_index_list" rel="edit-t41" no="40">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                        <div class="shop_skin_index_list" rel="edit-t35" no="34">
                        	<div class="img hot-market hotimg2"></div>
                        </div>
                    </div>
                </div>
         		
                	<div class="shop_skin_index_list" rel="edit-t42" no="41" style="margin:3px 0px;">
                    	<div class="img"></div>
                	</div>  
                
                
            </div>
            
            <div class="like-box">
                 <div class="title-box">
                    <div class="title">
                    	<div class="name shop_skin_index_list" rel="edit-t48" no="47">
								<div class="div_typename div_font"></div>
						</div>
                    </div>
                 </div>
                 <div class="product-box">
                    <div class="shop_skin_index_list" rel="edit-t43" no="42">
                    	<div class="img like-market"></div>
                    </div>
                 </div>
                 <div class="product-box">
                     <div class="shop_skin_index_list" rel="edit-t44" no="43">
                    	<div class="img like-market"></div>
                    </div>
                    
                 </div>
                 
                 <div class="product-box">
                    <div class="shop_skin_index_list" rel="edit-t45" no="44">
                    	<div class="img like-market"></div>
                    </div>
                 </div>
                 <div class="product-box">
                    <div class="shop_skin_index_list" rel="edit-t46" no="45">
                    	<div class="img like-market"></div>
                    </div>
                 </div>
            </div>
             
            <div class="footer">
            <div class="footer-box">
                <div class="weidian active">
                    
                    	<div class="shop_skin_index_list" rel="edit-t49" no="48">
                    		<div class="img"></div>
                    	</div>
                    	<p>
                        	<div class="name shop_skin_index_list" rel="edit-t53" no="52">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                    
                </div>
                <div class="weidian">
                   
                    	<div class="shop_skin_index_list" rel="edit-t50" no="49">
                    		<div class="img"></div>
                    	</div>
                    	<p>
                        	<div class="name shop_skin_index_list" rel="edit-t54" no="53">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                   
                </div>
                <div class="weidian">
                    
                    	<div class="shop_skin_index_list" rel="edit-t51" no="50">
                    		<div class="img"></div>
                    	</div>
                    	<p>
                        	<div class="name shop_skin_index_list" rel="edit-t55" no="54">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                   
                </div>
                <div class="weidian">
                   
                    	<div class="shop_skin_index_list" rel="edit-t52" no="51">
                    		<div class="img"></div>
                    	</div>
                    	<p>
                        	<div class="name shop_skin_index_list" rel="edit-t56" no="55">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                   
                </div>
            </div>
            </div>
           <!--<div class="clear-fix"></div>-->
        </div>
 
</div> 

<?php }else if($template_id==41){?>
<link href="fengge41/css/style.css" rel="stylesheet" type="text/css">
<link href="css/shop.css" rel="stylesheet" type="text/css">
<div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
<div class="main">  
    <div class="header"><!--头部开始-->
    	<div class="shop_skin_index_list banner"  rel="edit-t01" no="0">
            <div class="img"></div>   
            <div class="mod" style="display: none;">&nbsp;</div> 
        </div>      	
        
        <img src="fengge41/images/logo.png" class="picture">
        <div class="detail-info">
            <div class="one">
                <p class="num">1846</p>
                <p class="name">
                	<div class="name shop_skin_index_list" rel="edit-t07" no="6">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="one">
                <p class="num">1625</p>
                <p class="name">
                	<div class="name shop_skin_index_list" rel="edit-t08" no="7">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div> 
            <div class="one">
                <div class="shop_skin_index_list" rel="edit-t12" no="11">
                    <div class="img"></div>
                </div>
                <p class="name">
                	<div class="name shop_skin_index_list" rel="edit-t09" no="8">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="one">
                <div class="shop_skin_index_list" rel="edit-t13" no="12">
                    <div class="img"></div>
                </div>
                <p class="name">
                	<div class="name shop_skin_index_list" rel="edit-t10" no="9">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="one">
                <div class="shop_skin_index_list" rel="edit-t14" no="13">
                    <div class="img"></div>
                </div>
                <p class="name">
                	<div class="name shop_skin_index_list" rel="edit-t11" no="10">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
        </div>
    </div><!--头部结束-->
    
    <div class="shop_skin_index_list" rel="edit-t02" no="1">
        <div class="img"></div>
    </div>
    <div class="shop_skin_index_list" rel="edit-t03" no="2">
        <div class="img"></div>
    </div>
    <div class="shop_skin_index_list" rel="edit-t04" no="3">
        <div class="img"></div>
    </div>
    <div class="four-shop"><!--分类楼层开始-->
        <div class="left-box">
            <img src="" class="title">
            <div class="big-box">
                <div class="name">夏季女装新品来袭</div>
                <div class="second">新品上线抢先购</div>
                <img src="">
            </div>
        </div>
        <div class="right-box">
            <div class="st-box">
                <div class="name">修身女裤</div>
                <div class="second">人气商品精选</div>
                <img src="">
            </div>
            <div class="st-box">
                <div class="name">多款魅力文胸</div>
                <div class="second">魅力曲线</div>
                <img src="">
            </div>
            <div class="st-box">
                <div class="name">夏季真丝旗袍</div>
                <div class="second">爱上她的丝滑</div>
                <img src="">
            </div>
            <div class="st-box">
                <div class="name">优质睡衣 品质生活</div>
                <div class="second">舒适与时尚并存</div>
                <img src="">
            </div>
            <div class="st-box">
                <div class="name">时尚百搭，百变大咖</div>
                <div class="second">时尚拒绝紫外线</div>
                <img src="">
            </div>
            <div class="st-box">
                <div class="name">时间因我而在</div>
                <div class="second">爱在每时每分每秒</div>
                <img src="">
            </div>
            <div class="st-box">
                <div class="name">舒适从脚下开始</div>
                <div class="second">夏季潮品 品牌直销</div>
                <img src="">
            </div>
            <div class="st-box">
                <div class="name">时尚单品 花样百搭</div>
                <div class="second">一个美包，瞬间扭转你的形象</div>
                <img src="">
            </div>
        </div>
        <div class="clear"></div>
    </div><!--分类楼层结束-->
       
    <div class="div-box"><!--全部商品开始-->
        <div class="shop_skin_index_list" rel="edit-t05" no="04">
            <div class="img"></div>
        </div>
        <div class="shop_skin_index_list" rel="edit-t06" no="5">
            <div class="img"></div>
        </div>
        <div class="small-box">
            <img src="">
            <span class="span1">原价:￥659</span><span class="span2">折后价:￥215</span>
            <button class="red-button">立即抢购</button>
        </div>
        <div class="small-box">
            <img src="">
            <span class="span1">原价:￥659</span><span class="span2">折后价:￥215</span>
            <button class="red-button">立即抢购</button>
        </div>
        <div class="clear"></div>
    </div><!--全部商品结束-->

</div>
</div>

<?php }else if($template_id==42){?>
<link href="fengge42/css/style.css" rel="stylesheet" type="text/css">
<link href="css/shop.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/shop.js"></script>

<div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div class="main">
        <div class="shop_skin_index_list" rel="edit-t01" no="0">
        	<div class="img"></div>
    	</div>
        <div class="search-box">
            <input type="text" class="search-input"  placeholder="查找宝贝">
        </div>
        <div class="shop_skin_index_list banner"  rel="edit-t02" no="1">
            <div class="img"></div>   
            
        </div>
        <div class="content-box">
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t03" no="2">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t11" no="10">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t04" no="3">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t12" no="11">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t05" no="4">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t13" no="12">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t06" no="5">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t14" no="13">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t07" no="6">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t15" no="14">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t08" no="7">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t16" no="15">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t09" no="8">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t17" no="16">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t10" no="9">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t18" no="17">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            
            <div class="clear"></div>
        </div>
        <div class="dapai">
            <div class="left-box">
                <div class="shop_skin_index_list"  rel="edit-t19" no="18">
            		<div class="img"></div>   
            	</div>
            </div>
            <div class="right-box">
                <div class="first-box">
                    <div class="shop_skin_index_list"  rel="edit-t20" no="19">
                        <div class="img"></div>   
                    </div>
                </div>
                <div class="second-box">
                    <div class="shop_skin_index_list"  rel="edit-t21" no="20">
                        <div class="img"></div>   
                    </div>
                </div>
            </div>
            <div class="clear"></div>
        </div>
           <!-- <div class="skin-care">
                <div style="width:320px;height:27px;background:#999999;margin-bottom:5px;text-align:center;color:#fff;line-height:26px;">分类楼层顶部图1024*88</div>
                <div class="content">
                    <div class="left-box"><div style="width:150px;height:118px;background:#999999;text-align:center;color:#fff;line-height:42px;padding-top:30px;">第一张分类图<br>481*504</div></div>
                    <div class="right-box">
                        <div style="width:150px;height:49px;background:#999999;margin-bottom:10px;text-align:center;color:#fff;line-height:16px;padding-top:20px;">第二张分类图<br>481*238</div>
                        <div style="width:150px;height:49px;background:#999999;text-align:center;color:#fff;line-height:16px;padding-top:20px;">第三张分类图<br>481*238</div>
                    </div>
                    <div class="clear"></div>
                </div>
            </div>
            -->
            <div class="makeup">
                    <div class="shop_skin_index_list"  rel="edit-t29" no="28" style="margin-top:5px;">
                        <div class="img"></div>   
                    </div>
                    <div class="content-bg">
                    <div class="content">
                        <div class="left-box">
                        	<div class="top_box" >
                            	<div class="shop_skin_index_list"  rel="edit-t35" no="34">
                                    <div class="img"></div>   
                                </div>
                            </div>   
                            <div class="first-box">
                                <div class="shop_skin_index_list"  rel="edit-t30" no="29">
                                    <div class="img"></div>   
                                </div>
                            </div>
                            <div class="second-box">
                                <div class="shop_skin_index_list"  rel="edit-t31" no="30">
                                        <div class="img"></div>   
                                    </div>
                                </div>
                            <div class="third-box">
                                <div class="shop_skin_index_list"  rel="edit-t32" no="31">
                                    <div class="img"></div>   
                                </div>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="right-box">
                            <div class="first-box">
                                <div class="shop_skin_index_list"  rel="edit-t33" no="32">
                                        <div class="img"></div>   
                                    </div>
                                </div>
                            <div class="second-box">
                                <div class="shop_skin_index_list"  rel="edit-t34" no="33">
                                        <div class="img"></div>   
                                    </div>
                                </div>
                            <div class="clear"></div>
                        </div>
                        <div class="clear"></div>
                    </div>
                    </div>
                </div>
            
            
            <div class="new-product" >
                <div class="title">
                    <div class="name">近日新品</div>
                    <div class="look">查看全部>></div>
                </div>
                <div class="product-box">
                    <div class="one-box">
                        <img src="fengge42/images/new1.jpg">
                        <div class="name">新佰草集新玉润保湿化妆水200ml爽肤水柔肤水补水</div>
                        <div class="price">￥58</div>
                        <div class="sale">已售：128笔</div>
                    </div>
                    <div class="one-box">
                        <img src="fengge42/images/new1.jpg">
                        <div class="name">新佰草集新玉润保湿化妆水200ml爽肤水柔肤水补水</div>
                        <div class="price">￥58</div>
                        <div class="sale">已售：128笔</div>
                    </div>
                    
                    <div class="clear"></div>
                </div>
            </div>
            <div class="new-product" >
                <div class="title">
                    <div class="name">热销爆款</div>
                    <div class="look">查看全部>></div>
                </div>
                <div class="product-box">
                    <div class="one-box">
                        <img src="fengge42/images/new1.jpg">
                        <div class="name">新佰草集新玉润保湿化妆水200ml爽肤水柔肤水补水</div>
                        <div class="price">￥58</div>
                        <div class="sale">已售：128笔</div>
                    </div>
                    <div class="one-box">
                        <img src="fengge42/images/new1.jpg">
                        <div class="name">新佰草集新玉润保湿化妆水200ml爽肤水柔肤水补水</div>
                        <div class="price">￥58</div>
                        <div class="sale">已售：128笔</div>
                    </div>
                    
                    <div class="clear"></div>
                </div>
            </div>
            
            <style>
            	.weidian .first img{width:65px!important;height:35px!important;}
            </style>      
            <div class="footer">
            <div class="footer-box">
                <div class="weidian active">
                    
                    	<div class="shop_skin_index_list" rel="edit-t22" no="21">
                    		<div class="img first" ></div>
                    	</div>
                    	
                    
                </div>
                <div class="weidian">
                   
                    	<div class="shop_skin_index_list" rel="edit-t23" no="22">
                    		<div class="img"></div>
                    	</div>
                    	<p>
                        	<div class="name shop_skin_index_list" rel="edit-t26" no="25">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                   
                </div>
                <div class="weidian">
                    
                    	<div class="shop_skin_index_list" rel="edit-t24" no="23">
                    		<div class="img"></div>
                    	</div>
                    	<p>
                        	<div class="name shop_skin_index_list" rel="edit-t27" no="26">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                   
                </div>
                <div class="weidian">
                   
                    	<div class="shop_skin_index_list" rel="edit-t25" no="24">
                    		<div class="img"></div>
                    	</div>
                    	<p>
                        	<div class="name shop_skin_index_list" rel="edit-t28" no="27">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                   
                </div>
            </div>
            </div>
            
            
       
        </div>
</div>

<?php }else if($template_id==43){?>

<link href="fengge43/css/style.css" rel="stylesheet" type="text/css">
<link href="fengge43/css/common.css" rel="stylesheet" type="text/css">
<link href="fengge43/css/detail3.css" rel="stylesheet" type="text/css">
<link href="fengge43/css/font-awesome.css" rel="stylesheet" type="text/css">
<link href="fengge43/css/idangerous.swiper.css" rel="stylesheet" type="text/css">
<link href="fengge43/css/reset.css" rel="stylesheet" type="text/css">
<link href="fengge43/css/weimobfont2.css" rel="stylesheet" type="text/css">
<link href="fengge43/css/wicons.css" rel="stylesheet" type="text/css">
<link href="fengge43/css/widget_menu.css" rel="stylesheet" type="text/css">
<link href="fengge43/css/widget_public.css" rel="stylesheet" type="text/css">
<link href="css/shop.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/shop.js"></script>

<div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div class="main">
			<div class="header_3" style="background:#8D4E00;">
					<label style="color:#FEFFFC;">
						<i>
						<div class="shop_skin_index_list" rel="edit-t01" no="0">
							<div class="img" style=" border-radius:100px;"></div>
						</div> 
						</i>
						<div class="name shop_skin_index_list title" rel="edit-t24" no="23">
							<div class="div_typename div_font" ></div>
						</div>
					</label>
			</div>	
			<div class="shop_skin_index_list banner"  rel="edit-t02" no="1">
				<div class="img"></div>        
			</div>
			<div class="widget_wrap">
				<ul>
						<li style="background:#F7F7F5" loop="1">
							<div class="shop_skin_index_list" rel="edit-t03" no="2">
								<div class="img"></div>
							</div> 
						<div class="name shop_skin_index_list" rel="edit-t07" no="6">
							<div class="div_typename div_font" ></div>
						</div>							
						</li>
						<li style="background:#F7F7F5" loop="1">
							<div class="shop_skin_index_list" rel="edit-t04" no="3">
								<div class="img"></div>
							</div> 
							<div class="name shop_skin_index_list" rel="edit-t08" no="7">
								<div class="div_typename div_font" ></div>
							</div>								
						</li>
						<li style="background:#F7F7F5" loop="1">
							<div class="shop_skin_index_list" rel="edit-t05" no="4">
								<div class="img"></div>
							</div> 		
							<div class="name shop_skin_index_list" rel="edit-t09" no="8">
								<div class="div_typename div_font" ></div>
							</div>								
						</li>
						<li style="background:#F7F7F5" loop="1">
							<div class="shop_skin_index_list" rel="edit-t06" no="5">
								<div class="img"></div>
							</div> 	
							<div class="name shop_skin_index_list" rel="edit-t10" no="9">
								<div class="div_typename div_font" ></div>
							</div>								
						</li>
				</ul>
			</div>	
            <div Type="2" data-role="widget" data-widget="search_2" class="search_2">
				<div class="widget_wrap">
					<form action="#">
						<div>
							<input type="search" value="输关键词找宝贝" name="search" placeholder="输关键词找宝贝" />
						</div>
						<div>
							<img src="fengge43/images/widget_search_pic.png" />
						</div>
					</form>
				</div>
			</div>        
            <div class="shop_skin_index_list" rel="edit-t11" no="10">
				<div class="img"></div>
			</div> 
			<div class="shop_skin_index_list" rel="edit-t12" no="11">
				<div class="img"></div>
			</div>
			<div Type="1" data-role="widget" data-widget="line_4" class="line_4">
			  <div class="widget_wrap" style="height:5px;padding:0; position:relative;"></div>
			</div>
			<div Type="1" data-role="widget" data-widget="pic_1" class="pic_1">
			  <div class="widget_wrap">
				  <img src="fengge43/images/1508251103576708.jpg" />
			  </div>
			</div> 
			<div Type="1" data-role="widget" data-widget="goodsList_1" class="goodsList_1">
				<div class="goods">
				<ul>
					<li loop="1">
						<div class="goodlists">
							<div class="img_wrap">
								<img style="background-image:url(fengge43/images/1507241549100744.jpg);" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQIW2NkAAIAAAoAAggA9GkAAAAASUVORK5CYII=" />
								<!-- <span name="goodsdetailspan" class="tag">团购促销</span> -->
							</div>
							<div>
								<p class="title">
									<a href="#" title="【团购】瑞士凯琳斯蒂手工皂七件套男女美白保湿去黑头粉刺祛痘消印全身美白洁面正品">【团购】瑞士凯琳斯蒂手工皂七件套男女美白保湿去黑头粉刺祛痘消印全身美白洁面正品</a>
								</p>
								<label class="price">￥139</label>
								<div class="">
									<a href="javascript:;" vid="76227" vPrice="139" vMember="False" class="goods_Buy"></a>
								</div>
							</div>
						</div>
					</li>
					<li loop="1">
						<div class="goodlists">
							<div class="img_wrap">
								<img style="background-image:url(fengge43/images/1507241549100744.jpg);" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQIW2NkAAIAAAoAAggA9GkAAAAASUVORK5CYII=" />
								<!-- <span name="goodsdetailspan" class="tag">团购促销</span> -->
							</div>
							<div>
								<p class="title">
									<a href="#" title="【团购】瑞士凯琳斯蒂手工皂七件套男女美白保湿去黑头粉刺祛痘消印全身美白洁面正品">【团购】瑞士凯琳斯蒂手工皂七件套男女美白保湿去黑头粉刺祛痘消印全身美白洁面正品</a>
								</p>
								<label class="price">￥139</label>
								<div class="">
									<a href="javascript:;" vid="76227" vPrice="139" vMember="False" class="goods_Buy"></a>
								</div>
							</div>
						</div>
					</li>				
				</ul>
				</div>
			</div>
			
			<div class="shop_skin_index_list" rel="edit-t13" no="12">
				<div class="img"></div>
			</div>
			<div data-role="widget" data-widget="menu_2" class="menu_2">
				<div class="widget_wrap">
					<ul>
						
						<li style="background: #F7F7F5">
							<div class="shop_skin_index_list" rel="edit-t14" no="13">
								<div class="img foot"></div>
							</div> 	
							<div class="name shop_skin_index_list" rel="edit-t19" no="18">
								<div class="div_typename div_font" ></div>
							</div>	
						</li>
						
						<li style="background: #F7F7F5">
							<div class="shop_skin_index_list" rel="edit-t15" no="14">
								<div class="img foot"></div>
							</div> 	
							<div class="name shop_skin_index_list" rel="edit-t20" no="19">
								<div class="div_typename div_font" ></div>
							</div>	
						</li>
						
						<li style="background: #F7F7F5">
							<div class="shop_skin_index_list" rel="edit-t16" no="15">
								<div class="img foot"></div>
							</div> 	
							<div class="name shop_skin_index_list" rel="edit-t21" no="20">
								<div class="div_typename div_font" ></div>
							</div>	
						</li>
						
						<li style="background: #F7F7F5">
							<div class="shop_skin_index_list" rel="edit-t17" no="16">
								<div class="img foot"></div>
							</div> 	
							<div class="name shop_skin_index_list" rel="edit-t22" no="21">
								<div class="div_typename div_font" ></div>
							</div>
						</li>
						
						<li style="background: #F7F7F5">
							<div class="shop_skin_index_list" rel="edit-t18" no="17">
								<div class="img foot"></div>
							</div> 	
							<div class="name shop_skin_index_list" rel="edit-t23" no="22">
								<div class="div_typename div_font" ></div>
							</div>
						</li>
						
					</ul>
				</div>
			</div>
    
            
            
    </div>
</div>

<?php }else if($template_id==44){?>
<link href="fengge44/css/style.css" rel="stylesheet" type="text/css">
<link href="css/shop.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/shop.js"></script>

<div id="shop_skin_index" <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
	<div class="main">
        <div class="shop_skin_index_list" rel="edit-t01" no="0">
        	<div class="img"></div>
    	</div>
        <div class="search-box">
            <input type="text" class="search-input"  placeholder="查找宝贝">
        </div>
        <div class="shop_skin_index_list banner"  rel="edit-t02" no="1">
            <div class="img"></div>   
            
        </div>
        <div class="content-box">
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t03" no="2">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t11" no="10">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t04" no="3">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t12" no="11">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t05" no="4">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t13" no="12">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t06" no="5">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t14" no="13">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t07" no="6">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t15" no="14">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t08" no="7">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t16" no="15">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t09" no="8">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t17" no="16">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            <div class="icon-box">            
                <div class="shop_skin_index_list" rel="edit-t10" no="9">
                    <div class="img"></div>
                </div>
                <p>
                    <div class="name shop_skin_index_list" rel="edit-t18" no="17">
                        <div class="div_typename div_font" ></div>
                    </div>
                </p>
            </div>
            
            <div class="clear"></div>
        </div>
        <div class="dapai">
            <div class="left-box">
                <div class="shop_skin_index_list"  rel="edit-t19" no="18">
            		<div class="img"></div>   
            	</div>
            </div>
            <div class="right-box">
                <div class="first-box">
                    <div class="shop_skin_index_list"  rel="edit-t20" no="19">
                        <div class="img"></div>   
                    </div>
                </div>
                <div class="second-box">
                    <div class="shop_skin_index_list"  rel="edit-t21" no="20">
                        <div class="img"></div>   
                    </div>
                </div>
            </div>
            <div class="clear"></div>
        </div>
            <div class="skin-care">
                <div style="width:320px;height:27px;background:#999999;margin-bottom:5px;text-align:center;color:#fff;line-height:26px;">分类楼层顶部图1024*88</div>
                <div class="content">
                    <div class="left-box"><div style="width:150px;height:118px;background:#999999;text-align:center;color:#fff;line-height:42px;padding-top:30px;">第一张分类图<br>481*504</div></div>
                    <div class="right-box">
                        <div style="width:150px;height:49px;background:#999999;margin-bottom:10px;text-align:center;color:#fff;line-height:16px;padding-top:20px;">第二张分类图<br>481*238</div>
                        <div style="width:150px;height:49px;background:#999999;text-align:center;color:#fff;line-height:16px;padding-top:20px;">第三张分类图<br>481*238</div>
                    </div>
                    <div class="clear"></div>
                </div>
            </div>
            
            <!--<div class="makeup">
                    <div class="shop_skin_index_list "  rel="edit-t29" no="28" style="margin-top:5px;">
                        <div class="img"></div>   
                    </div>
                    <div class="content-bg">
                    <div class="content">
                        <div class="left-box">
                        	<div class="top_box" >
                            	<div class="shop_skin_index_list"  rel="edit-t35" no="34">
                                    <div class="img"></div>   
                                </div>
                            </div>   
                            <div class="first-box">
                                <div class="shop_skin_index_list"  rel="edit-t30" no="29">
                                    <div class="img"></div>   
                                </div>
                            </div>
                            <div class="second-box">
                                <div class="shop_skin_index_list"  rel="edit-t31" no="30">
                                        <div class="img"></div>   
                                    </div>
                                </div>
                            <div class="third-box">
                                <div class="shop_skin_index_list"  rel="edit-t32" no="31">
                                    <div class="img"></div>   
                                </div>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="right-box">
                            <div class="first-box">
                                <div class="shop_skin_index_list"  rel="edit-t33" no="32">
                                        <div class="img"></div>   
                                    </div>
                                </div>
                            <div class="second-box">
                                <div class="shop_skin_index_list"  rel="edit-t34" no="33">
                                        <div class="img"></div>   
                                    </div>
                                </div>
                            <div class="clear"></div>
                        </div>
                        <div class="clear"></div>
                    </div>
                    </div>
                </div>
            -->
            
            <div class="new-product" >
                <div class="title">
                    <div class="name">近日新品</div>
                    <div class="look">查看全部>></div>
                </div>
                <div class="product-box">
                    <div class="one-box">
                        <img src="fengge42/images/new1.jpg">
                        <div class="name">新佰草集新玉润保湿化妆水200ml爽肤水柔肤水补水</div>
                        <div class="price">￥58</div>
                        <div class="sale">已售：128笔</div>
                    </div>
                    <div class="one-box">
                        <img src="fengge42/images/new1.jpg">
                        <div class="name">新佰草集新玉润保湿化妆水200ml爽肤水柔肤水补水</div>
                        <div class="price">￥58</div>
                        <div class="sale">已售：128笔</div>
                    </div>
                    
                    <div class="clear"></div>
                </div>
            </div>
            <div class="new-product" >
                <div class="title">
                    <div class="name">热销爆款</div>
                    <div class="look">查看全部>></div>
                </div>
                <div class="product-box">
                    <div class="one-box">
                        <img src="fengge42/images/new1.jpg">
                        <div class="name">新佰草集新玉润保湿化妆水200ml爽肤水柔肤水补水</div>
                        <div class="price">￥58</div>
                        <div class="sale">已售：128笔</div>
                    </div>
                    <div class="one-box">
                        <img src="fengge42/images/new1.jpg">
                        <div class="name">新佰草集新玉润保湿化妆水200ml爽肤水柔肤水补水</div>
                        <div class="price">￥58</div>
                        <div class="sale">已售：128笔</div>
                    </div>
                    
                    <div class="clear"></div>
                </div>
            </div>
            
            <style>
            	.weidian .first img{width:65px!important;height:35px!important;}
            </style>      
            <div class="footer">
            <div class="footer-box">
                <div class="weidian active">
                    
                    	<div class="shop_skin_index_list" rel="edit-t22" no="21">
                    		<div class="img first" ></div>
                    	</div>
                    	
                    
                </div>
                <div class="weidian">
                   
                    	<div class="shop_skin_index_list" rel="edit-t23" no="22">
                    		<div class="img"></div>
                    	</div>
                    	<p>
                        	<div class="name shop_skin_index_list" rel="edit-t26" no="25">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                   
                </div>
                <div class="weidian">
                    
                    	<div class="shop_skin_index_list" rel="edit-t24" no="23">
                    		<div class="img"></div>
                    	</div>
                    	<p>
                        	<div class="name shop_skin_index_list" rel="edit-t27" no="26">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                   
                </div>
                <div class="weidian">
                   
                    	<div class="shop_skin_index_list" rel="edit-t25" no="24">
                    		<div class="img"></div>
                    	</div>
                    	<p>
                        	<div class="name shop_skin_index_list" rel="edit-t28" no="27">
                        		<div class="div_typename div_font" ></div>
                        	</div>
                        </p>
                   
                </div>
            </div>
            </div>
            
            
       
        </div>
</div>

<?php }else if($template_id==45){?>
	<link href="css/shop.css" rel="stylesheet" type="text/css">
    <link href="fengge45/css/index.css" rel="stylesheet" type="text/css">
	<link href="fengge45/css/style.css" rel="stylesheet" type="text/css">
	<div id="shop_skin_index"   <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
		<div class="shop_skin_index_list" rel="edit-t01" no="0" style="height:117px;">
			<div class="img"></div>
			<div class="mod" style="display: none;">&nbsp;</div>
			
		</div>
		<div class="title shop_skin_index_list" rel="edit-t02" no="1" >
			<div class="div_typename"></div>
		</div>
		
		<ul class="custom-coupon" style="float:right;">
            <li>
                <a href="##" class="js-select-coupon" style="text-decoration:none;">
                        <div class="shop_skin_index_list custom-coupon-price" rel="edit-t03" no="2" style="float:none;">
							<div class="div_typename" style="color:#fa5262 !important;"></div>
						</div>
                        <div class="shop_skin_index_list custom-coupon-desc" rel="edit-t04" no="3" style="float:none;">
							<div class="div_typename" style="color:#fa5262 !important;"></div>
						</div>
                </a>
            </li>
            <li>
                <a href="##" class="js-select-coupon" style="text-decoration:none;">
                        <div class="shop_skin_index_list custom-coupon-price" rel="edit-t05" no="4" style="float:none;">
							<div class="div_typename" style="color:#7acf8d !important;"></div>
						</div>
                        <div class="shop_skin_index_list custom-coupon-desc" rel="edit-t06" no="5" style="float:none;">
							<div class="div_typename" style="color:#7acf8d !important;"></div>
						</div> 
                </a>
            </li>
            <li>
                <a href="##" class="js-select-coupon" style="text-decoration:none;">
                        <div class="shop_skin_index_list custom-coupon-price" rel="edit-t07" no="6" style="float:none;">
							<div class="div_typename" style="color:#ff9664 !important;"></div>
						</div>
                        <div class="shop_skin_index_list custom-coupon-desc" rel="edit-t08" no="7" style="float:none;">
							<div class="div_typename" style="color:#ff9664 !important;"></div>
						</div>
                </a>
            </li>
        </ul>
		<div class="notice shop_skin_index_list" rel="edit-t09" no="8" >
			<div class="div_typename notice" style="width: 300px;"></div>
		</div>
		<div>
			<div class="shop_skin_index_list i0" rel="edit-t10" no="9">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
			</div>
			<div class="shop_skin_index_list i0" rel="edit-t11" no="10">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
			</div>
			<div class="shop_skin_index_list i0" rel="edit-t12" no="11">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
			</div>
			<div class="shop_skin_index_list i0" rel="edit-t13" no="12">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
			</div>
		</div>
		<div class="shop_skin_index_list" rel="edit-t14" no="13">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list banner" rel="edit-t15" no="14" style="height:160px;">
			<div class="img"></div><div class="mod">&nbsp;</div>
			<div id="SetHomeCurrentBox" style="height: 150px; width: 310px;"></div>
		</div>
		<div class="shop_skin_index_list" style="width: 100%;">
			<hr style="border-top: 1px;border-top: 1px dashed #bbb;">
		</div>
		<div class="shop_skin_index_list" rel="edit-t16" no="15">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div class="shop_skin_index_list" rel="edit-t17" no="16">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
		</div>
		<div>
			<div class="shop_skin_index_list i2" rel="edit-t18" no="17">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
			</div>
			<div class="shop_skin_index_list i2" rel="edit-t19" no="18">
				<div class="img"></div><div class="mod" style="display: none;">&nbsp;</div>
			</div>
		</div>
		<div class="shop_skin_index_list" style="width: 100%;">
			<hr style="border-top: 1px;border-top: 1px dashed #bbb;">
		</div>
		<div class="img" style="height:230px;float:left;"><img src="./fengge31/img/pic2.png"></div>
		
		
	</div>

<?php } ?>
  </div>
  <div class="m_righter">
  <script type="text/javascript" src="../common/js/jscolor/jscolor.js"></script>
		<form id="frm_uploadimg" action="save_templateimg.php?customer_id=<?php echo $customer_id_en; ?>&template_id=<?php echo $template_id; ?>" method="post" enctype="multipart/form-data">
		    <div style="padding-left:10px;height:30px;line-height:30px;">背景颜色：<input class="color" value="<?php echo $index_bg; ?>" name="index_bg" id="index_bg">&nbsp;<span style="cursor:pointer" onclick="document.getElementById('index_bg').value='';" >清除颜色</span> </div>
			<div id="setbanner" style="display: none;">
				<div class="item">
					<div class="rows">
						<div class="b_l">
							<strong>图片(1)</strong>
							<span class="tips">大图建议尺寸：<label  id="label_slide_1">640*320</label>px</span>
							<a href="#" value="0" id="a_banner_1">
							   <img src="images/del.gif" align="absmiddle"></a><br>
							<div class="blank6"></div>
							<div>
							  <input style="width:208;border:1 solid #9a9999; font-size:9pt; background-color:#ffffff; height:18" size="17" name="upfile1_1" id="upfile1_1" type=file>
							</div>
						</div>
						<div class="b_r" id="banner_img_1">
						   <a href="images/banner.jpg" target="_blank"><img src="images/banner.jpg"></a>
						</div>
						<input type=hidden name="imgids_1_1" id="imgids_1_1"  />
					</div>
					<div class="blank9"></div>
					<div class="rows url_select" style="display: block;">
					
						<div class="u_l">链接页面</div>
						<div class="u_r">
						<select name="type_id_1_1" id="type_id_1_1" onchange="changeSliderType(1,this.value);">
						
                        <option value="-1" selected="selected">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						<option value="-8" >个人中心</option>
						<option value="-7" >产品分类页</option>
						
						<optgroup label="---------------产品分类---------------"></optgroup>
						<?php 
						  if($typesize>0){
						     for($i=0;$i<$typesize; $i++){
							    $typestr= $typeLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
						<?php  }
						}
						?>
						
						<optgroup label="---------------图文消息---------------"></optgroup>
						<?php 
						  if($imginfosize>0){
						     for($i=0;$i<$imginfosize; $i++){
							    $typestr= $imginfoLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						</select>
					   </div>
					   <div id="div_products_1_1" style="display:none;padding-left:72px;">
							<select name="product_detail_id_1_1" id="product_detail_id_1_1">
								<option value=-1>---请选择一款产品---</option>
							</select>
						</div>
					</div>
					<div class="clear"></div>
					
				</div>
					<div class="item">
					<div class="rows">
						<div class="b_l">
							<strong>图片(2)</strong><span class="tips">大图建议尺寸：<label  id="label_slide_2">640*320</label>px</span>
							<a href="#" value="1" id="a_banner_2">
							<img src="images/del.gif" align="absmiddle">
							</a><br>
							<div class="blank6"></div>
							<div>
							   <input style="width:208;border:1 solid #9a9999; font-size:9pt; background-color:#ffffff; height:18" size="17" name="upfile1_2" id="upfile1_2" type=file>
							</div>
						</div>
						<div class="b_r" id="banner_img_2">
						   <a href="images/banner.jpg" target="_blank"><img src="images/banner.jpg"></a>
						</div>
						<input type=hidden name="imgids_1_2" id="imgids_1_2"  />
					</div>
					<div class="blank9"></div>
					<div class="rows url_select" style="display: block;">
						<div class="u_l">链接页面</div>
						<div class="u_r">
						<select  name="type_id_1_2"  id="type_id_1_2"   onchange="changeSliderType(2,this.value);">
						<option value="-1">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						<option value="-8" >个人中心</option>
						<option value="-7" >产品分类页</option>
						
						<optgroup label="---------------产品分类---------------"></optgroup>
						<?php 
						  if($typesize>0){
						     for($i=0;$i<$typesize; $i++){
							    $typestr= $typeLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						
						<optgroup label="---------------图文消息---------------"></optgroup>
						<?php 
						  if($imginfosize>0){
						     for($i=0;$i<$imginfosize; $i++){
							    $typestr= $imginfoLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						</select>
						
						</div>
						<div id="div_products_1_2" style="display:none;padding-left:72px;">
							<select name="product_detail_id_1_2" id="product_detail_id_1_2">
								<option value=-1>---请选择一款产品---</option>
							</select>
						</div>
					</div>
					<div class="clear"></div>
				</div>
								<div class="item">
					<div class="rows">
						<div class="b_l">
							<strong>图片(3)</strong><span class="tips">大图建议尺寸：<label  id="label_slide_3">640*320</label>px</span>
							<a href="#" value="2" id="a_banner_3">
							<img src="images/del.gif" align="absmiddle"></a><br>
							<div class="blank6"></div>
							<div>
							  <input style="width:208;border:1 solid #9a9999; font-size:9pt; background-color:#ffffff; height:18" size="17" name="upfile1_3" id="upfile1_3" type=file>
							</div>
						</div>
						<div class="b_r" id="banner_img_3">
						   <a href="images/banner.jpg" target="_blank">
						   <img src="images/banner.jpg">
						   </a>
						</div>
						
						<input type=hidden name="imgids_1_3" id="imgids_1_3"  />
					</div>
					<div class="blank9"></div>
					<div class="rows url_select" style="display: block;">
						<div class="u_l">链接页面</div>
						<div class="u_r">
						<select  name="type_id_1_3"  id="type_id_1_3"  onchange="changeSliderType(3,this.value);">
						<option value="-1">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						<option value="-8" >个人中心</option>
						<option value="-7" >产品分类页</option>
						
						<optgroup label="---------------产品分类---------------"></optgroup>
						<?php 
						  if($typesize>0){
						     for($i=0;$i<$typesize; $i++){
							    $typestr= $typeLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						<optgroup label="---------------图文消息---------------"></optgroup>
						<?php 
						  if($imginfosize>0){
						     for($i=0;$i<$imginfosize; $i++){
							    $typestr= $imginfoLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						</select>
					</div>
					<div id="div_products_1_3" style="display:none;padding-left:72px;">
							<select name="product_detail_id_1_3" id="product_detail_id_1_3">
								<option value=-1>---请选择一款产品---</option>
							</select>
					</div>
					</div>
					<div class="clear"></div>
				</div>
								<div class="item">
					<div class="rows">
						<div class="b_l">
							<strong>图片(4)</strong><span class="tips">大图建议尺寸：<label id="label_slide_4">640*320</label>px</span>
							<a href="#" value="3" id="a_banner_4">
							<img src="images/del.gif" align="absmiddle"></a><br>
							<div class="blank6"></div>
							<div>
							  <input style="width:208;border:1 solid #9a9999; font-size:9pt; background-color:#ffffff; height:18" size="17" name="upfile1_4" id="upfile1_4" type=file>
							</div>
						</div>
						<div class="b_r" id="banner_img_4">
						   <a href="images/banner.jpg" target="_blank"><img src="images/banner.jpg"></a>
						</div>
						<input type=hidden name="imgids_1_4" id="imgids_1_4"  />
					</div>
					<div class="blank9"></div>
					<div class="rows url_select" style="display: block;">
						<div class="u_l">链接页面</div>
						<div class="u_r">
						<select  name="type_id_1_4"  id="type_id_1_4"  onchange="changeSliderType(4,this.value);">
						<option value="-1">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						<option value="-8" >个人中心</option>
						<option value="-7" >产品分类页</option>
						
						<optgroup label="---------------产品分类---------------"></optgroup>
						<?php 
						  if($typesize>0){
						     for($i=0;$i<$typesize; $i++){
							    $typestr= $typeLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						
						<optgroup label="---------------图文消息---------------"></optgroup>
						<?php 
						  if($imginfosize>0){
						     for($i=0;$i<$imginfosize; $i++){
							    $typestr= $imginfoLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						</select>
						</div>
					  <div id="div_products_1_4" style="display:none;padding-left:72px;">
							<select name="product_detail_id_1_4" id="product_detail_id_1_4">
								<option value=-1>---请选择一款产品---</option>
							</select>
					  </div>
					</div>
					<div class="clear"></div>
				</div>
					<div class="item">
					<div class="rows">
						<div class="b_l">
							<strong>图片(5)</strong><span class="tips">大图建议尺寸：<label  id="label_slide_5">640*320</label>px</span>
							<a href="#" value="4" id="a_banner_5">
							  <img src="images/del.gif" align="absmiddle"></a><br>
							<div class="blank6"></div>
							<div>
							 <input style="width:208;border:1 solid #9a9999; font-size:9pt; background-color:#ffffff; height:18" size="17" name="upfile1_5" id="upfile1_5" type=file>
							</div>
						</div>
						<div class="b_r" id="banner_img_5">
						   <a href="images/banner.jpg" target="_blank"><img src="images/banner.jpg"></a>
						</div>
						<input type=hidden name="imgids_1_5" id="imgids_1_5"  />
					</div>
					<div class="blank9"></div>
					<div class="rows url_select" style="display: block;">
						<div class="u_l">链接页面</div>
						<div class="u_r">
						<select  name="type_id_1_5"  id="type_id_1_5"  onchange="changeSliderType(5,this.value);">
						<option value="-1" selected="selected">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						<option value="-8" >个人中心</option>
						<option value="-7" >产品分类页</option>
						
						<optgroup label="---------------产品分类---------------"></optgroup>
						<?php 
						  if($typesize>0){
						     for($i=0;$i<$typesize; $i++){
							    $typestr= $typeLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						
						<optgroup label="---------------图文消息---------------"></optgroup>
						<?php 
						  if($imginfosize>0){
						     for($i=0;$i<$imginfosize; $i++){
							    $typestr= $imginfoLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						</select>
						
						</div>
						<div id="div_products_1_5" style="display:none;padding-left:72px;">
							<select name="product_detail_id_1_5" id="product_detail_id_1_5">
								<option value=-1>---请选择一款产品---</option>
							</select>
					</div>
					</div>
					<div class="clear"></div>
				</div>
				<?php 
				
				//if($template_id==9 and $is_shopgeneral){
               if($is_shopgeneral and $general_template_id=37){				
				   if($is_samelevel==0){
				?>
				 <p>---总部商品---</p>
				<?php 
				  for($m=0;$m<$general_slider_num; $m++){
				     $num = $m+6;
				  
				?>
				
				    <div class="item">
					<div class="rows">
						<div class="b_l">
							<strong>图片(<?php echo $num; ?>)</strong><span class="tips">大图建议尺寸：<label>640*320</label>px</span>
							<div class="blank6"></div>
							<div>
							</div>
						</div>
						<div class="b_r" id="banner_img_<?php echo $num; ?>">
						   <a href="images/banner.jpg" target="_blank"><img src="images/banner.jpg"></a>
						</div>
						<input type=hidden name="imgids_1_<?php echo $num; ?>" id="imgids_1_<?php echo $num; ?>"  />
					</div>
					<div class="blank9"></div>
					<div class="rows url_select" style="display: block;">
						<div class="u_l">链接页面</div>
						<div class="u_r">
						<select  name="type_id_1_<?php echo $num; ?>"  id="type_id_1_<?php echo $num; ?>"  onchange="changeSliderType(<?php echo $num; ?>,this.value);">
						<option value="-1" selected="selected">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						<option value="-8" >个人中心</option>
						<option value="-7" >产品分类页</option>
						
						<optgroup label="---------------产品分类---------------"></optgroup>
						<?php 
						  if($typesize>0){
						     for($i=0;$i<$typesize; $i++){
							    $typestr= $typeLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						
						<optgroup label="---------------图文消息---------------"></optgroup>
						<?php 
						  if($imginfosize>0){
						     for($i=0;$i<$imginfosize; $i++){
							    $typestr= $imginfoLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						</select>
						
						</div>
						<div id="div_products_1_<?php echo $num; ?>" style="display:none;padding-left:72px;">
							<select name="product_detail_id_1_<?php echo $num; ?>" id="product_detail_id_1_<?php echo $num; ?>">
								<option value=-1>---请选择一款产品---</option>
							</select>
					   </div>
					</div>
					<div class="clear"></div>
				</div>
				
				<?php }
				    }
                  }
				?>
			</div>
			<div id="setimages" style="display: block;">
				<div class="item">
					<div value="title" style="display: none;">
						<span class="fc_red">*</span> 标题<br>
						<div class="input"><input name="Title" value="" type="text"></div>
						<div class="blank20"></div>
					</div>
					<div value="images">
						<span class="fc_red">*</span> 图片<span class="tips">大图建议尺寸：<label>400*400</label>px</span>
						<a href="#" id="a_banner_2_1">
						  <img src="images/del.gif" align="absmiddle"></a><br>
						<div class="blank6"></div>
						<div>
				           <input style="width:208;border:1 solid #9a9999; font-size:9pt; background-color:#ffffff; height:18" size="17" name="upfile2" id="upfile2" type=file>
						   <div id="HomeFileUploadQueue" class="om-fileupload-queue"></div>
						</div>
						<div class="blank20"></div>
						
					</div>
					<div class="url_select" style="display: block;">
						<span class="fc_red">*</span> 链接页面<br>
						<div class="input"> 
						<select  name="type_id_2"  id="type_id_2" onchange="changeProductType(this.value);">
						<option value="-1" selected="selected">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						<option value="-8" >个人中心</option>
						<option value="-7" >产品分类页</option>
						
						<optgroup label="---------------产品分类---------------"></optgroup>
						<?php 
						  if($typesize>0){
						     for($i=0;$i<$typesize; $i++){
							    $typestr= $typeLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						
						<optgroup label="---------------图文消息---------------"></optgroup>
						<?php 
						  if($imginfosize>0){
						     for($i=0;$i<$imginfosize; $i++){
							    $typestr= $imginfoLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						
						</select>
						<div id="div_products_2" style="display:none">
							<select name="product_detail_id_2" id="product_detail_id_2">
								<option value=-1>---请选择一款产品---</option>
							</select>
						</div>
						</div>
					</div>
					<input type=hidden name="imgurl2" id="imgurl2" value="" />
				</div>
			</div>
			
			<div id="set_title" style="display: none;">
				<div class="item">
					<div value="title">
						<span class="fc_red">*</span> 标题<br>
						<div class="input"><input name="title" value="" id="title_3" type="text"></div>
						<div class="blank20"></div>
					</div>
					<div style="padding-left:10px;height:30px;line-height:30px;">
						字体颜色：<input class="color" value="" name="font_bg" id="font_bg">&nbsp;
						<span style="cursor:pointer" onclick="document.getElementById('font_bg').value='';" >清除颜色</span> 
					</div>
					<div class="url_select" style="display: block;">
						<span class="fc_red">*</span> 链接页面<br>
						<div class="input">
						<select  name="type_id_3"  id="type_id_3"  onchange="changeProductType_txt_orgi(this.value);">
						<option value="-1" selected="selected">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						<option value="-8" >个人中心</option>
						<option value="-7" >产品分类页</option>
						
						<optgroup label="---------------产品分类---------------"></optgroup>
						<?php 
						  if($typesize>0){
						     for($i=0;$i<$typesize; $i++){
							    $typestr= $typeLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						
						<optgroup label="---------------图文消息---------------"></optgroup>
						<?php 
						  if($imginfosize>0){
						     for($i=0;$i<$imginfosize; $i++){
							    $typestr= $imginfoLst->Get($i);
								
							    $typearr = explode("_",$typestr);
								$type_id = $typearr[0];
								$type_name = $typearr[1];
								
							 
						?>
						  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
						<?php  }
						
						} ?>
						
						</select>
						</div>
						<div id="div_products_3" style="display:none">
							<select name="product_detail_id_3" id="product_detail_id_3">
								<option value=-1>---请选择一款产品---</option>
							</select>
						</div>
					</div>
				</div>
			</div>
            
            <div id="set_video_link" style="display: none;"><!--视频链接开始-->
				<div class="item">
					<div value="title">
						<span class="fc_red">*</span>视频地址<br>
						<div class="input"><input name="video_link" value="" id="title_4" type="text" style="width:300px;"></div>
						<div class="blank20"></div>
                        <span class="fc_red">
                        	用户进入视频网站，点击视频，在分享一栏找到通用的视频代码，将"http://"开头的视频地址复制到此处即可。<br/>
                            例如 http://player.youku.com/embed/XMTMyMzYzMTUzMg==                         
                        </span>
					</div>					
				</div>
			</div><!--视频链接开始-->
            
			<div class="button"><input type="submit" class="btn_green" name="submit_button" value="提交保存"></div>
			<input type="hidden" name="contenttype" id="contenttype" value="2">
			<input type="hidden" name="position" id="position" value="1">
			
		</form>
	</div>
        <!--分类图片-->
    <?php if($template_id==38 or $template_id==31 or $template_id==45){?>
    <?php 	
	$producttype_id=-1;
	$btn="添加分类";
	if(!empty($_GET["producttype_id"])){
	   $producttype_id=$configutil->splash_new($_GET["producttype_id"]);
	   $btn="保存修改";
	}
	
	$type_imgurl="";
	if($producttype_id>0 and empty($_GET["op"])){
	   //编辑属性的才读取数据，删除不需要读取数据
	   
	   $query="select name,parent_id,sendstyle,imgurl from weixin_commonshop_types where isvalid=true and  id=".$producttype_id;
	   $result = mysql_query($query) or die('Query failed: ' . mysql_error());
	   while ($row = mysql_fetch_object($result)) {
		   $producttype_name = $row->name;
		   $producttype_parent_id = $row->parent_id;
		   $producttype_sendstyle= $row->sendstyle;	      
		   $type_imgurl= $row->imgurl;	      
	   }
	}
	?>  
	<style>  
		.fenlei_a{width:140px;height:30px;line-height:30px;font-size:16px;color:#fff;background:#5bb75b;display:block;border-radius:4px;margin:3px 0px;text-align:center;text-decoration:none;}				
		.category a:hover{color:#fff;text-decoration:none;}
		.m_righter .input{display:block;text-align:center;width:100%;}           
    </style>
    <div class="category"><!--category begin-->
        <div class="m_lefter" style="margin-left:10px;"><!--m_lefter begin-->
            <dl data-listidx="0" style="margin:5px 10px;">
             <span style="text-align:center;font-size:16px;margin-bottom:10px;display:block;">添加分类楼层图片</span>
                <?php 
                   $query= "select id,name,parent_id,sendstyle,is_shelves,index_catnum,create_type,asort from weixin_commonshop_types where isvalid=true and customer_id=".$customer_id." and parent_id=-1 order by asort desc,id desc";
                   $result = mysql_query($query) or die('Query failed: ' . mysql_error());
                   while ($row = mysql_fetch_object($result)) {
                       $pt_id = $row->id;
                       $pt_name = $row->name;
                       $pt_parent_id = $row->parent_id;
                       $pt_sendstyle= $row->sendstyle;
                       $pt_is_shelves= $row->is_shelves;
                       $create_type = $row->create_type;
                       $asort = $row->asort;			   
                ?>
                
                      <dd cateid="<?php echo $pt_id; ?>" style="cursor: pointer;">
                          <div class="category no_ext">                        
                            <?php if((($owner_general==1 and $create_type==1) or ($owner_general==2 and $create_type==2)) or ($create_type==3)){ ?>
                                <span style="width:100%;text-align:center;margin:0 auto;">
                                    <a class="fenlei_a" href="defaultset3.php?customer_id=<?php echo $customer_id_en; ?>&producttype_id=<?php echo $pt_id;?>&default_set=1" title="<?php echo $pt_name; ?>"><i class="fa fa-gear"></i><?php echo $pt_name; ?></a> 
                              </span>                                                             
                            <?php } ?>												
                         </div>
                       </dd>
                      <?php 
                       $str = $u4m->getSubProductTypes($pt_id,$customer_id,1,$owner_general);				  
                   } ?>
                
            </dl>
        </div><!--m_lefter end-->

	<div class="m_righter" ><!--m_righter begin-->
		<form id="frm_producttype" class="" action="save_producttype_img.php?customer_id=<?php echo $customer_id_en; ?>&adminuser_id=<?php echo $adminuser_id; ?>&owner_general=<?php echo $owner_general; ?>&orgin_adminuser_id=<?php echo $orgin_adminuser_id; ?>" method="post" enctype="multipart/form-data" style="margin:5px 10px;">
        	<?php
            	$query_catnum="select index_catnum from weixin_commonshop_types where isvalid=true and customer_id=".$customer_id." and id=".$producttype_id."";
				$result_catnum=mysql_query($query_catnum) or die ('catnym faild' .mysql_error());
				while($row=mysql_fetch_object($result_catnum)){
					 $index_catnum=$row->index_catnum;
					
				}
			?>
			<div class="">
            	<?php if($template_id==38){?>
                分类楼层显示数量:<input type="text" name="index_catnum" id="index_catnum" style="width:50px;" value="<?php echo $index_catnum;?>"></div><br/>			
                <?php }?>
                <div class="opt_item">			
                    <span class="upload_file">
                        <div>
                            <iframe src="product_type_catimg.php?customer_id=<?php echo $customer_id_en; ?>&type_imgurl=<?php echo $type_imgurl; ?>&keyid=<?php echo $producttype_id; ?>" height=200 width=100% FRAMEBORDER=0 SCROLLING=no></iframe>
                            <?php if($template_id==38 ||$template_id==31){echo "上传1张图片，作为首页的图片。图片大小建议：640*110像素";}elseif($template_id==42){echo "上传1张图片，作为首页楼层分类图片。图片大小建议：640*55像素";}elseif($template_id==45){echo "上传1张图片，作为首页楼层分类图片。图片大小建议：640*128像素";}?>						
                        </div>     
                    </span>
                    <input type=hidden name="type_imgurl" id="type_imgurl" value="<?php echo $type_imgurl ; ?>" />
                </div>
                <div class="opt_item">				
                    <input type="hidden" id="keyid" name="keyid" value="<?php echo $producttype_id;?>">					
                	<div class="clear"></div>
				</div>
				<div class="opt_item">
					<label></label>
					<span class="input">				
						<input type="submit" name="submit_button" class="imgbtnsub" value="<?php echo $btn;?>" title="<?php echo $btn;?>" style="width:140px;height:28px;line-height:28px;background:url(images/ok-btn-bg.jpg);cursor:pointer;color:#fff;margin-bottom:10px;">
						<div class="clear"></div>
                    </span>
				</div>  
		</form>
	</div><!--m_righter end-->
	<div class="clear"></div>
</div><!--category end-->
<?php }?>
<!--分类添加图片结束-->
    <div style="display:block;overflow:hidden;">
    <!--分类图片41模板专用-->
    <?php if($template_id==44){?>   
    <?php 	
	$producttype_id=-1;
	$btn="添加分类";
	if(!empty($_GET["producttype_id"])){
	   $producttype_id=$configutil->splash_new($_GET["producttype_id"]);
	   $btn="保存修改";
	}
	
	$type_imgurl="";
	if($producttype_id>0 and empty($_GET["op"])){
	   //编辑属性的才读取数据，删除不需要读取数据
	   
	   $query="select name,parent_id,sendstyle,index_imgurl from weixin_commonshop_types where isvalid=true and is_shelves=1 and  id=".$producttype_id;
	   $result = mysql_query($query) or die('Query failed: ' . mysql_error());
	   while ($row = mysql_fetch_object($result)) {
		   $producttype_name = $row->name;
		   $producttype_parent_id = $row->parent_id;
		   $producttype_sendstyle= $row->sendstyle;	      
		   $type_imgurl= $row->index_imgurl;	      
	   }
	}
	?>  
	<style>  
		.fenlei_a{width:140px;height:30px;line-height:30px;font-size:16px;color:#fff;background:#5bb75b;display:block;border-radius:4px;margin:3px 0px;text-align:center;text-decoration:none;}				
		.category a:hover{color:#fff;text-decoration:none;}
		.input{display:block;text-align:center;width:100%;}           
    </style>
    <div class="category" style="overflow:hidden;margin-bottom:10px;"><!--category begin-->
        <div class="m_lefter" style="margin-left:10px;height:100%;"><!--m_lefter begin-->
            <dl data-listidx="0" style="margin:5px 10px;">
             <span style="text-align:center;font-size:16px;margin-bottom:10px;display:block;">添加分类楼层图片</span>
                <?php 
                   $query= "select id,name,parent_id,sendstyle,is_shelves,index_catnum,create_type,asort from weixin_commonshop_types where isvalid=true and is_shelves=1 and customer_id=".$customer_id." and parent_id=-1 order by asort desc,id desc";
                   $result = mysql_query($query) or die('Query failed: ' . mysql_error());
                   while ($row = mysql_fetch_object($result)) {
                       $pt_id = $row->id;
                       $pt_name = $row->name;
                       $pt_parent_id = $row->parent_id;
                       $pt_sendstyle= $row->sendstyle;
                       $pt_is_shelves= $row->is_shelves;
                       $create_type = $row->create_type;
                       $asort = $row->asort;			   
                ?>  
                
                      <dd cateid="<?php echo $pt_id; ?>" style="cursor: pointer;">
                          <div class="category no_ext">                        
                            <?php if((($owner_general==1 and $create_type==1) or ($owner_general==2 and $create_type==2)) or ($create_type==3)){ ?>
                                <span style="width:100%;text-align:center;margin:0 auto;">
                                    <a class="fenlei_a" href="defaultset.php?customer_id=<?php echo $customer_id_en; ?>&producttype_id=<?php echo $pt_id;?>&default_set=1" title="<?php echo $pt_name; ?>"><i class="fa fa-gear"></i><?php echo $pt_name; ?></a> 
                              </span>                                                             
                            <?php } ?>												
                         </div>
                       </dd>
                      <?php 
                       $str = $u4m->getSubProductTypes($pt_id,$customer_id,1,$owner_general);				  
                   } ?>
                
            </dl>
        </div><!--m_lefter end-->

	<div class="m_righter" style="margin-bottom:20px;"><!--m_righter begin-->
		<form id="frm_producttype" class="" action="save_producttype_img.php?customer_id=<?php echo $customer_id_en; ?>&adminuser_id=<?php echo $adminuser_id; ?>&owner_general=<?php echo $owner_general; ?>&orgin_adminuser_id=<?php echo $orgin_adminuser_id; ?>" method="post" enctype="multipart/form-data" style="margin:5px 10px;">
        	  
			<div class="">	
                <div class="opt_item">			
                    <span class="upload_file">
                        <div>
                            <iframe src="product_type_catimg.php?customer_id=<?php echo $customer_id_en; ?>&type_imgurl=<?php echo $type_imgurl; ?>&keyid=<?php echo $producttype_id; ?>" height=200 width=100% FRAMEBORDER=0 SCROLLING=no></iframe>						
                        </div>  
                    </span>
                    <input type=hidden name="type_imgurl" id="type_imgurl" value="<?php echo $type_imgurl ; ?>" />
                </div>
                <div class="opt_item">				
                    <input type="hidden" id="keyid" name="keyid" value="<?php echo $producttype_id;?>">					
                	<div class="clear"></div>
				</div>
                <span style="margin-bottom:10px;display:block;">楼层顶部图片大小为1024px*88px;</span>

				<div class="opt_item">
					<label></label>
					<span class="input">				
						<input type="submit" name="submit_button" class="imgbtnsub" value="<?php echo $btn;?>" title="<?php echo $btn;?>" style="width:140px;height:28px;line-height:28px;background:url(images/ok-btn-bg.jpg);cursor:pointer;color:#fff;margin-bottom:10px;">
						<div class="clear"></div>
                    </span>
				</div>
               
		</form>
	</div><!--m_righter end-->

	<div class="clear"></div>
</div><!--category end-->
<!--模板首页分类图-->
</div>
<?php 	
	$producttype_id=-1;
	$btn="添加分类";
	if(!empty($_GET["producttype_id"])){
	   $producttype_id=$configutil->splash_new($_GET["producttype_id"]);
	   $btn="保存修改";
	}
	
	$type_imgurl="";
	if($producttype_id>0 and empty($_GET["op"])){
	   //编辑属性的才读取数据，删除不需要读取数据
	   
	   $query="select name,parent_id,sendstyle,cat_index_imgurl  from weixin_commonshop_types where isvalid=true and is_shelves=1 and  id=".$producttype_id;
	   $result = mysql_query($query) or die('Query failed: ' . mysql_error());
	   while ($row = mysql_fetch_object($result)) {
		   $producttype_name = $row->name;
		   $producttype_parent_id = $row->parent_id;
		   $producttype_sendstyle= $row->sendstyle;	      
		   $cat_index_imgurl= $row->cat_index_imgurl;	      
	   }
	}
	?>  
	<style>  
		.fenlei_a{width:140px;height:30px;line-height:30px;font-size:16px;color:#fff;background:#5bb75b;display:block;border-radius:4px;margin:3px 0px;text-align:center;text-decoration:none;}				
		.category a:hover{color:#fff;text-decoration:none;}
		.input{display:block;text-align:center;width:100%;}           
    </style>
    <div class="category"><!--category begin-->
        <div class="m_lefter" style="margin-left:10px;"><!--m_lefter begin-->
            <dl data-listidx="0" style="margin:5px 10px;">
             <span style="text-align:center;font-size:16px;margin-bottom:10px;display:block;">分类首页显示图（最多显示3个二级分类）</span>
                <?php 
                   $query= "select id,name,parent_id,sendstyle,is_shelves,index_catnum,create_type,asort from weixin_commonshop_types where isvalid=true and is_shelves=1 and parent_id=-1 and customer_id=".$customer_id." order by asort desc,id desc";
                   $result = mysql_query($query) or die('Query failed: ' . mysql_error());
                   while ($row = mysql_fetch_object($result)) {
                       $pt_id = $row->id;
                       $pt_name = $row->name;
                       $pt_parent_id = $row->parent_id;
                       $pt_sendstyle= $row->sendstyle;
                       $pt_is_shelves= $row->is_shelves;
                       $create_type = $row->create_type;
                       $asort = $row->asort;			   
                ?>
                
                      <dd cateid="<?php echo $pt_id; ?>" style="cursor: pointer;">
                          <div class="category no_ext">                        
                            <?php if((($owner_general==1 and $create_type==1) or ($owner_general==2 and $create_type==2)) or ($create_type==3)){ ?>
                                <span style="width:100%;text-align:center;margin:0 auto;">
                                	<?php if($pt_parent_id==-1){echo "<a class='fenlei_a'>$pt_name</a>";}else{?>
                                    <a class="fenlei_a" href="defaultset.php?customer_id=<?php echo $customer_id_en; ?>&producttype_id=<?php echo $pt_id;?>&default_set=1&cat_op=catad" title="<?php echo $pt_name; ?>"><i class="fa fa-gear"></i><?php echo $pt_name; ?></a>
                                    <?php }?> 
                              </span>                                                             
                            <?php } ?>												
                         </div>
                       </dd>
                       
                       <?php 
                  		$query_son= "select id,name,parent_id,sendstyle,is_shelves,index_catnum,create_type,asort from weixin_commonshop_types where isvalid=true and is_shelves=1 and parent_id=".$pt_id." and customer_id=".$customer_id." order by asort desc,id desc"; 
                   $result_son = mysql_query($query_son) or die('Query failed: ' . mysql_error());
                   while ($row = mysql_fetch_object($result_son)) {
                       $son_id = $row->id;
                       $son_name = $row->name;
                       $son_parent_id = $row->parent_id;
                       $son_sendstyle= $row->sendstyle;
                       $son_is_shelves= $row->is_shelves;
                       $create_type = $row->create_type;
                       $asort = $row->asort;			   
               		 ?>
                      <dd cateid="<?php echo $son_id; ?>" style="cursor: pointer;margin-left:30px;">
                          <div class="category no_ext">                        
                            <?php if((($owner_general==1 and $create_type==1) or ($owner_general==2 and $create_type==2)) or ($create_type==3)){ ?>
                                <span style="width:100%;text-align:center;margin:0 auto;">
                                    <a class="fenlei_a" href="defaultset.php?customer_id=<?php echo $customer_id_en; ?>&producttype_id=<?php echo $son_id;?>&default_set=1&cat_op=catindex" title="<?php echo $son_name; ?>"><i class="fa fa-gear"></i><?php echo $son_name; ?></a> 
                              </span>                                                             
                            <?php } ?>												
                         </div>
                       </dd>
                      <?php }?> 
                   
                       
                       
                      <?php 
                       $str = $u4m->getSubProductTypes($pt_id,$customer_id,1,$owner_general);				  
                   } ?>
                
            </dl>
        </div><!--m_lefter end-->

	<div class="m_righter" ><!--m_righter begin-->
		<form id="frm_producttype" class="" action="save_catindex_img.php?customer_id=<?php echo $customer_id_en; ?>&adminuser_id=<?php echo $adminuser_id; ?>&owner_general=<?php echo $owner_general; ?>&orgin_adminuser_id=<?php echo $orgin_adminuser_id; ?>" method="post" enctype="multipart/form-data" style="margin:5px 10px;">
        	
			<div class="">	
                <div class="opt_item">			
                    <span class="upload_file">
                        <div>
                            <iframe src="product_cat_indeximg.php?customer_id=<?php echo $customer_id_en; ?>&type_imgurl=<?php echo $cat_index_imgurl; ?>&keyid=<?php echo $producttype_id; ?>" height=200 width=100% FRAMEBORDER=0 SCROLLING=no></iframe>						
                        </div>  
                    </span>
                    <input type=hidden name="type_imgurl" id="type_imgurl_cat" value="<?php echo $type_imgurl ; ?>" />
                </div>
                <div class="opt_item">				
                    <input type="hidden" id="keyid" name="keyid" value="<?php echo $producttype_id;?>">					
                	<div class="clear"></div>
				</div>
               

				<div class="opt_item">
					<label></label>
					<span class="input">				
						<input type="submit" name="submit_button" class="imgbtnsub" value="<?php echo $btn;?>" title="<?php echo $btn;?>" style="width:140px;height:28px;line-height:28px;background:url(images/ok-btn-bg.jpg);cursor:pointer;color:#fff;margin-bottom:10px;">
						<div class="clear"></div>
                    </span>
				</div>  
		</form>
	</div><!--m_righter end-->
    
    
    
    
	<div class="clear"></div>
</div><!--category end-->

<?php }?>
<!--分类添加图片结束41模板专用-->
	<div class="clear"></div>
</div>

<?php if($template_id==31 or $template_id==45){?>
	<style>
		.navigation_left{float:left;background:#f7f7f7;width: 345px;border: 1px solid #ddd;min-height: 200px;}
		.navigation_left #frm_pro h1{text-align:center;}
		.navigation_left #frm_pro .opt_item label{float:left;width:115px;height:28px;line-height:28px;text-align:right;}
		.navigation_left #frm_pro .opt_item .input{float: left;width: 220px;display: block;line-height: 28px;}
		.navigation_left #frm_pro .opt_item .input img{cursor:pointer;vertical-align:middle;}
		.navigation_right{float:left;background:#f7f7f7;width: 345px;border: 1px solid #ddd;min-height: 200px;margin-left:6px;padding:10px;}
		.navigation_right dl dd{border-bottom:1px solid #ddd;background:#f7f7f7;}
		.navigation_right ul li{width:50%;float:left;}
	</style>
	<?php 
		$key_id="";
		if(!empty($_GET["key_id"])){
			$key_id=$configutil->splash_new($_GET["key_id"]);
			$result=mysql_query("select name,chk_submenu,navigation_id,subpros,sublinks_id from weixin_commonshop_userdefined_nav where isvalid=true and id=".$key_id) or die ("Query failed_key_id:".$mysql_error());
			while($row=mysql_fetch_object($result)){
				$pre_name=$row->name;
				$pre_chk_submenu=$row->chk_submenu;
				$pre_navigation_id=$row->navigation_id;
				$pre_subpros=$row->subpros;
				$pre_sublinks=$row->sublinks_id;
			}
			
		}
		
	?>
	<div class="navigation_left">
		<form id="frm_pro" name="frm_pro" method="post" action="save_navigation.php?customer_id=<?php echo $customer_id_en; ?>&template_id=<?php echo $template_id;?>">
			<h1>添加自定义底部导航栏</h1>
			<div class="opt_item">
				<label>导航栏名称：</label>
				<span class="input"><input type="text" name="name" id="name" value="<?php echo $pre_name; ?>" class="form_input" size="15" maxlength="30" notnull=""></span>
				<div class="clear"></div>
			</div>
			<div class="opt_item">
				<label>是否有子菜单：</label>
				<span class="input">
				<input type="radio" name="is_submenu" class="is_submenu" value="0" <?php if($pre_chk_submenu==0){?>checked="checked" <?php } ?>>否<input type="radio" name="is_submenu" value="1" <?php if($pre_chk_submenu==1){?>checked="checked" <?php } ?> class="is_submenu">是
				<input type="hidden" id="chk_submenu" name="chk_submenu" value="<?php echo $pre_chk_submenu;?>">
				</span>
				<div class="clear"></div>
			</div>
			<?php if($key_id>0){
					?>
						<div class="opt_item navigation_link" <?php if($pre_chk_submenu==0){ ?> style="display:block;" <?php }else{ ?> style="display:none;"<?php } ?> >
							<label>导航栏链接：</label>
								<div class="input"> 
									<select  name="navigation_id"  id="navigation_id">
										<option value="-1" <?php if($pre_navigation_id==-1){ ?> selected="selected" <?php } ?> >--请选择--</option>
										<option value="-6" <?php if($pre_navigation_id==-6){ ?> selected="selected" <?php } ?> >全部产品</option>
										<option value="-2" <?php if($pre_navigation_id==-2){ ?> selected="selected" <?php } ?> >新品上市</option>
										<option value="-3" <?php if($pre_navigation_id==-3){ ?> selected="selected" <?php } ?> >热卖产品</option>
										<option value="-4" <?php if($pre_navigation_id==-4){ ?> selected="selected" <?php } ?> >购物车</option>
										<option value="-8" <?php if($pre_navigation_id==-8){ ?> selected="selected" <?php } ?> >个人中心</option>
										<option value="-7" <?php if($pre_navigation_id==-7){ ?> selected="selected" <?php } ?> >产品分类页</option>
										
										<optgroup label="---------------产品分类---------------"></optgroup>
										<?php 
										  if($typesize>0){
											 for($i=0;$i<$typesize; $i++){
												$typestr= $typeLst->Get($i);
												
												$typearr = explode("_",$typestr);
												$type_id = $typearr[0];
												$type_name = $typearr[1];
												
											 
										?>
										  <option value="<?php echo $type_id; ?>_1" <?php if($pre_navigation_id==$type_id){ ?> selected="selected" <?php } ?> ><?php echo $type_name; ?></option>
										<?php  }
										
										} ?>
										
										<optgroup label="---------------图文消息---------------"></optgroup>
										<?php 
										  if($imginfosize>0){
											 for($i=0;$i<$imginfosize; $i++){
												$typestr= $imginfoLst->Get($i);
												
												$typearr = explode("_",$typestr);
												$type_id = $typearr[0];
												$type_name = $typearr[1]; 
												
											 
										?>
										  <option value="<?php echo $type_id; ?>_2" <?php if($pre_navigation_id==$type_id){ ?> selected="selected" <?php } ?> ><?php echo $type_name; ?></option>
										<?php  }
										
										} ?>
									</select>
								</div>
							<div class="clear"></div>
						</div>
						
					<?php 
						$pros_separate=explode("#",$pre_subpros);
						$links_separate=explode("#",$pre_sublinks);
						$count=count($pros_separate);
						?>
						
						<div class="opt_item submenu" <?php if($pre_chk_submenu==1){ ?> style="display:block;" <?php }else{ ?> style="display:none;"<?php } ?> >
							<label>子菜单：</label>
							<span class="input">
								<ul>
								<?php $k=1;
									for($j=0;$j<$count;$j++){
								?>
									<li>
										<input type="text" name="SubmenuName[]" value="<?php echo $pros_separate[$j];?>" class="form_input" size="15" maxlength="30">
										<input type="hidden" name="NId[]" value="">
										<img src="images/del.gif">
										<?php if($k==$count){ ?><img src="images/add.gif"> <?php } ?>
									</li>
									<li>
										<select  name="Submenulink[]">
											<option value="-1" <?php if($links_separate[$j]==-1){?> selected="selected"<?php } ?> >--请选择--</option>
											<option value="-6" <?php if($links_separate[$j]==-6){?> selected="selected"<?php } ?> >全部产品</option>
											<option value="-2" <?php if($links_separate[$j]==-2){?> selected="selected"<?php } ?> >新品上市</option>
											<option value="-3" <?php if($links_separate[$j]==-3){?> selected="selected"<?php } ?> >热卖产品</option>
											<option value="-4" <?php if($links_separate[$i]==-4){?> selected="selected"<?php } ?> >购物车</option>
											<option value="-8" <?php if($links_separate[$j]==-8){?> selected="selected"<?php } ?> >个人中心</option>
											<option value="-7" <?php if($links_separate[$j]==-7){?> selected="selected"<?php } ?> >产品分类页</option>
											
											<optgroup label="---------------产品分类---------------"></optgroup>
											<?php 
											  if($typesize>0){
												 for($i=0;$i<$typesize; $i++){
													$typestr= $typeLst->Get($i);
													
													$typearr = explode("_",$typestr);
													$type_id = $typearr[0];
													$type_name = $typearr[1];
													
												 
											?>
											  <option value="<?php echo $type_id; ?>_1" <?php if($links_separate[$j]==$type_id."_1"){?> selected="selected"<?php } ?> ><?php echo $type_name; ?></option>
											<?php  }
											
											} ?>
											
											<optgroup label="---------------图文消息---------------"></optgroup>
											<?php 
											  if($imginfosize>0){
												 for($i=0;$i<$imginfosize; $i++){
													$typestr= $imginfoLst->Get($i);
													
													$typearr = explode("_",$typestr);
													$type_id = $typearr[0];
													$type_name = $typearr[1];
													
												 
											?> 
											  <option value="<?php echo $type_id; ?>_2" <?php if($links_separate[$j]==$type_id."_2"){?> selected="selected"<?php } ?> ><?php echo $type_name; ?></option>
											<?php  }
											
											} ?>
										</select>
									</li>
								<?php  $k++;} ?> 
								</ul>
							</span>
							<input type="hidden" id="keyid" name="keyid" value="<?php echo $key_id;?>">
							<input type="hidden" id="subpro" name="subpro" value="">
							<input type="hidden" id="sublinks_id" name="sublinks_id" value="">
							<div class="clear"></div>
						</div> 
						
						
			<?php }else{ ?> 
			<div class="opt_item navigation_link">
				<label>导航栏链接：</label>
					<div class="input">
						<select  name="navigation_id"  id="navigation_id">
							<option value="-1" selected="selected">--请选择--</option>
							<option value="-6" >全部产品</option>
							<option value="-2" >新品上市</option>
							<option value="-3" >热卖产品</option>
							<option value="-4" >购物车</option>
							<option value="-8" >个人中心</option>
							<option value="-7" >产品分类页</option>
							
							<optgroup label="---------------产品分类---------------"></optgroup>
							<?php 
							  if($typesize>0){
								 for($i=0;$i<$typesize; $i++){
									$typestr= $typeLst->Get($i);
									
									$typearr = explode("_",$typestr);
									$type_id = $typearr[0];
									$type_name = $typearr[1];
									
								 
							?>
							  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
							<?php  }
							
							} ?>
							
							<optgroup label="---------------图文消息---------------"></optgroup>
							<?php 
							  if($imginfosize>0){
								 for($i=0;$i<$imginfosize; $i++){
									$typestr= $imginfoLst->Get($i);
									
									$typearr = explode("_",$typestr);
									$type_id = $typearr[0];
									$type_name = $typearr[1];
									
								 
							?>
							  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
							<?php  }
							
							} ?>
						</select>
					</div>
				<div class="clear"></div>
			</div>
			<div class="opt_item submenu" style="display:none;">
				<label>子菜单：</label>
				<span class="input">
					<ul>
						<li>
							<input type="text" name="SubmenuName[]" value="" class="form_input" size="15" maxlength="30">
							<input type="hidden" name="NId[]" value="">
							<img src="images/del.gif">
						</li>
						<li>
							<select  name="Submenulink[]">
								<option value="-1" selected="selected">--请选择--</option>
								<option value="-6" >全部产品</option>
								<option value="-2" >新品上市</option>
								<option value="-3" >热卖产品</option>
								<option value="-4" >购物车</option>
								<option value="-8" >个人中心</option>
								<option value="-7" >产品分类页</option>
								
								<optgroup label="---------------产品分类---------------"></optgroup>
								<?php 
								  if($typesize>0){
									 for($i=0;$i<$typesize; $i++){
										$typestr= $typeLst->Get($i);
										
										$typearr = explode("_",$typestr);
										$type_id = $typearr[0];
										$type_name = $typearr[1];
										
									 
								?>
								  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
								<?php  }
								
								} ?>
								
								<optgroup label="---------------图文消息---------------"></optgroup>
								<?php 
								  if($imginfosize>0){
									 for($i=0;$i<$imginfosize; $i++){
										$typestr= $imginfoLst->Get($i);
										
										$typearr = explode("_",$typestr);
										$type_id = $typearr[0];
										$type_name = $typearr[1];
										
									 
								?>
								  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
								<?php  }
								
								} ?>
							</select>
						</li>
						<li>
							<input type="text" name="SubmenuName[]" value="" class="form_input" size="15" maxlength="30">
							<input type="hidden" name="NId[]" value="">
							<img src="images/del.gif">
							<img src="images/add.gif">
						</li>
						<li>
							<select  name="Submenulink[]">
								<option value="-1" selected="selected">--请选择--</option>
								<option value="-6" >全部产品</option>
								<option value="-2" >新品上市</option>
								<option value="-3" >热卖产品</option>
								<option value="-4" >购物车</option>
								<option value="-8" >个人中心</option>
								<option value="-7" >产品分类页</option>
								
								<optgroup label="---------------产品分类---------------"></optgroup>
								<?php 
								  if($typesize>0){
									 for($i=0;$i<$typesize; $i++){
										$typestr= $typeLst->Get($i);
										
										$typearr = explode("_",$typestr);
										$type_id = $typearr[0];
										$type_name = $typearr[1];
										
									 
								?>
								  <option value="<?php echo $type_id; ?>_1" ><?php echo $type_name; ?></option>
								<?php  }
								
								} ?>
								
								<optgroup label="---------------图文消息---------------"></optgroup>
								<?php 
								  if($imginfosize>0){
									 for($i=0;$i<$imginfosize; $i++){
										$typestr= $imginfoLst->Get($i);
										
										$typearr = explode("_",$typestr);
										$type_id = $typearr[0];
										$type_name = $typearr[1];
										
									 
								?>
								  <option value="<?php echo $type_id; ?>_2" ><?php echo $type_name; ?></option>
								<?php  }
								
								} ?>
							</select>
						</li>
					</ul>
				</span>
				<input type="hidden" id="keyid" name="keyid" value="">
				<input type="hidden" id="subpro" name="subpro" value="">
				<input type="hidden" id="sublinks_id" name="sublinks_id" value="">
				<div class="clear"></div>
			</div>
			<?php } ?>
			
			<div class="opt_item">
				<label></label>
				<span class="input">
				<input type="button" class="btn_green btn_w_120" name="submit_button" value="添加导航" onclick="subNav();">
				<a href="defaultset.php" class="btn_gray">返回</a></span>
				<div class="clear"></div>
			</div>
		</form>
	</div>
	<div class="navigation_right">
		  <dl>
		        <?php 
				$query="select id,name,chk_submenu,subpros from weixin_commonshop_userdefined_nav where isvalid=true and template_id=".$template_id." and customer_id=".$customer_id;
				$chk_submenu=0;
				$result = mysql_query($query) or die('Query failed: ' . mysql_error());
			    while ($row = mysql_fetch_object($result)) {
					$nav_id = $row->id;
					$name = $row->name;
					$chk_submenu = $row->chk_submenu;
					$subpros=$row->subpros;
				?>
					<dd>
						<div class="list">
						    <?php if($name!=""){ ?>
								<a href="defaultset3.php?customer_id=<?php echo $customer_id_en; ?>&template_id=<?php echo $template_id; ?>&key_id=<?php echo $nav_id;?>" title="修改">
								<img src="images/mod.gif" align="absmiddle">
								</a>
								<a href="save_navigation.php?customer_id=<?php echo $customer_id_en; ?>&op=del&id=<?php echo $nav_id; ?>&template_id=<?php echo $template_id;?>" title="删除" onclick="if(!confirm(&#39;删除后不可恢复，继续吗？&#39;)){return false};">
								<img src="images/del.gif" align="absmiddle">
								</a>
							<?php } ?>
							导航名：<?php echo $name; ?>
						</div>
						<ul>
						   <?php 
							if($chk_submenu==1){
								$submenu=explode("#",$subpros);
								$count_sub=count($submenu);
								for($i=0;$i<$count_sub;$i++){
						   ?>
							<li> 
								<div class="title"><img src="images/jt.gif">子菜单名：<?php echo $submenu[$i]; ?></div>
							</li>
							<?php }	
							}?>
						</ul>
						<div class="blank9"></div>
					</dd>
				<?php } ?>
		 </dl>
	</div>
	<div class="clear"></div>	
	<script> 
	$(document).ready(function(){
		var chk_submenu='<?php echo $pre_chk_submenu;?>';
		if(chk_submenu==0){
			document.getElementById("chk_submenu").value=0;
		}else{
			document.getElementById("chk_submenu").value=chk_submenu;
			$(".submenu").css('display','block');
			$(".navigation_link").css('display','none');
		}
		
	})
		function subNav(){
			var name = $("#name").attr("value");
		   if($.trim(name)==""){
			  alert('请输入导航名称');
			  return;
		   }

		   //子菜单名称
		    var SubmenuName = document.getElementsByName("SubmenuName[]");
			var SNlen = SubmenuName.length;
			var subpros=""; 
			for(i=0;i<SNlen;i++){
				   sub = SubmenuName[i];
				   var sv = sub.value;
				   if($.trim(sv)!=""){
					  //sv = sv.replace("_","");
					  subpros = subpros+sv+"#";
				   }
			}
			if(subpros.length>0){
				  subpros= subpros.substring(0,subpros.length-1);
			}

			//子菜单链接
			var Submenulink = document.getElementsByName("Submenulink[]");
			var SLlen = Submenulink.length;
			var sublinks_id=""; 
			for(i=0;i<SLlen;i++){
				   sublink = Submenulink[i];
				   var slv = sublink.value;
				   if($.trim(slv)!=""){
					  //slv = slv.replace("_","");
					  sublinks_id = sublinks_id+slv+"#";
				   }
			}
			if(sublinks_id.length>0){
				  sublinks_id= sublinks_id.substring(0,sublinks_id.length-1);
			}
			document.getElementById("subpro").value=subpros;
			document.getElementById("sublinks_id").value=sublinks_id;
			$("#frm_pro").submit();
		}
		
		$('.is_submenu').click(function(){
			if($(this).val()==1){
				document.getElementById("chk_submenu").value=1;
				$(".submenu").css('display','block');
				$(".navigation_link").css('display','none');
			}else{
				document.getElementById("chk_submenu").value=0;
				$(".submenu").css('display','none');
				$(".navigation_link").css('display','block');
			}
		})
		
		var ul=$('.navigation_left #frm_pro .submenu span ul');
		var add_btn="<img src='images/add.gif'>";
		$('.navigation_left #frm_pro .submenu span ul li img').live('click',function(){
			var img_btn=$(this).attr('src');
			img_btn=img_btn.slice(7,10);
			if(img_btn=='add'){
				ul.append(ul.children('li').eq(-2).clone(true));
				ul.append(ul.children('li').eq(-2).clone(true));
				$(this).remove();
			}else if(img_btn=='del'){
				if(ul.children('li').size()==2){
					alert("再删就没了，不需要子菜单请勾选【否】");
					return;
				}else{
					$(this).parent().next().remove();
					$(this).parent().remove();
					if(ul.find('img[src*=add]').size()==0){
						ul.children('li').eq(-2).append(add_btn);
					}
				}
			}
		})
	</script>
<?php } ?> 

<div id="home_mod_tips" class="lean-modal pop_win">
	<div class="h">首页设置<a class="modal_close" href="#"></a></div>
	<div class="tips">首页设置成功</div>
</div>	</div>
<div>
<script>
function setParentDefaultimgurl(type_imgurl){
    document.getElementById("type_imgurl").value=type_imgurl;
}

function up2(pt_id){
	document.location ="product_type.php?op=up&producttype_id="+pt_id;
	
}

function down2(pt_id){
	document.location ="product_type.php?op=down&producttype_id="+pt_id;
}

function setParentDefaultimgurl_cat(type_imgurl_cat){
    document.getElementById("type_imgurl_cat").value=type_imgurl_cat;
}

function up2_cat(pt_id){
	document.location ="product_type.php?op=up&producttype_id="+pt_id;
	
}

function down2_cat(pt_id){
	document.location ="product_type.php?op=down&producttype_id="+pt_id;
}

</script>
<script>
var detail_id=<?php echo $detail_id; ?>;
function jsonpCallback_get_product_list(results){
   var len = results.length;
   var sel_pro = document.getElementById("product_detail_id_2");
   sel_pro.options.length=0;
   
    var new_option = new Option("---请选择一个产品---",-1);
    sel_pro.options.add(new_option);
   for(i=2;i<len;i++){
      var pid = results[i].pid;
	  var pname = results[i].pname;
	  
	  var new_option = new Option(pname,pid);
       sel_pro.options.add(new_option);
	  if(pid==detail_id){
	     new_option.selected=true;
	  }
   }
   
}

function jsonpCallback_get_product_list_txt(results){
   var len = results.length;
   var sel_pro = document.getElementById("product_detail_id_3");
   sel_pro.options.length=0;
   
    var new_option = new Option("---请选择一个产品---",-1);
    sel_pro.options.add(new_option);
   for(i=2;i<len;i++){
      var pid = results[i].pid;
	  var pname = results[i].pname;
	  
	  var new_option = new Option(pname,pid);
       sel_pro.options.add(new_option);
	  if(pid==p_detail_id){
	     new_option.selected=true;
	  }
   }
   
}

var slide_type=1;

function changeSliderType(type,selv){
   slide_type = type;
   document.getElementById("div_products_1_"+slide_type).style.display="none";
   if(selv.indexOf("_1")!=-1){
     //是产品分类
	 document.getElementById("div_products_1_"+slide_type).style.display="block";
	 var pro_typeid= selv.substring(0,selv.indexOf("_1"));
	 url='get_product_list.php?callback=jsonpCallback_get_product_list_slider&type_id='+pro_typeid;
     $.jsonp({
		url:url,
		callbackParameter: 'jsonpCallback_get_product_list_slider'
	});
  }
}
 
function jsonpCallback_get_product_list_slider(results){
   var len = results.length;
   var sel_pro = document.getElementById("product_detail_id_1_"+slide_type);
   sel_pro.options.length=0;
   
    var new_option = new Option("---请选择一个产品---",-1);
    sel_pro.options.add(new_option);
   for(i=2;i<len;i++){
      var pid = results[i].pid;
	  var pname = results[i].pname;
	  
	  var new_option = new Option(pname,pid);
       sel_pro.options.add(new_option);
	  if(pid==detail_id){
	     new_option.selected=true;
	  }
   }
}
</script>
<?php 

mysql_close($link);
?>

</div></div></body></html>