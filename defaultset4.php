<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../back_init.php');
require('../common/utility.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require('../proxy_info.php');

mysql_query("SET NAMES UTF8");

$query = "select id,template_id,index_bg from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
$template_id=-1;
$index_bg = "";
while ($row = mysql_fetch_object($result)) {
	$template_id = $row->template_id;
	$index_bg = $row->index_bg;
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
   $op = $_GET["op"];
   if($op=="del"){
       //删除banner
	   $position = $_GET["position"];
	   $b_imgurl = $_GET["b_imgurl"];
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
       $position = $_GET["position"];
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
$is_generalcustomer = 1;
//if($template_id==9){
    //总部模板才添加
	$query="select adminuser_id from customers where isvalid=true and id=".$customer_id;
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	$adminuser_id=-1;
	while ($row = mysql_fetch_object($result)) {
	   $adminuser_id = $row->adminuser_id;
	   break;
	}
	while($adminuser_id>0){
	   $query="select channel_level_id,parent_id from adminusers where isvalid=true and id=".$adminuser_id;
	   $result = mysql_query($query) or die('Query failed: ' . mysql_error());   
	   $channel_level_id = -1;
	   $parent_id2 = -1;
	   while ($row = mysql_fetch_object($result)) {
			$channel_level_id = $row->channel_level_id;
			$parent_id2 = $row->parent_id;
	   }
	   if($channel_level_id==5){
		  //找到贴牌
		  $query="select is_shopgeneral from oem_infos where isvalid=true and adminuser_id=".$adminuser_id;
		  $result = mysql_query($query) or die('Query failed: ' . mysql_error());   
		   while ($row = mysql_fetch_object($result)) {
			  $is_shopgeneral = $row->is_shopgeneral;
		   }
		   break;
	   }else{
		   $adminuser_id = $parent_id2;
		   $is_generalcustomer = 0;
	   }
	}
	if($adminuser_id>0){
	   //查找总部商店
	   $query = "select id from customers where isvalid=true and is_general=true and adminuser_id=".$adminuser_id;
	   $result = mysql_query($query) or die('Query failed: ' . mysql_error());   
	   while ($row = mysql_fetch_object($result)) {
	      $general_customer_id = $row->id;
		  break;
	   }
	}
//}

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

$query="select count(1) as new_qr_count from promoters where status=1 and  isvalid=true and customer_id=".$customer_id." and year(createtime)=".$year." and month(createtime)=".$month." and day(createtime)=".$day;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());  
 //  echo $query;
while ($row = mysql_fetch_object($result)) {
   $new_qr_count = $row->new_qr_count;
   break;
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
<script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="js/global.js"></script>
<style type="text/css" media="screen">
#HomeFileUploadUploader {visibility:hidden}#HomeFileUpload_0Uploader {visibility:hidden}#HomeFileUpload_1Uploader {visibility:hidden}#HomeFileUpload_2Uploader {visibility:hidden}#HomeFileUpload_3Uploader {visibility:hidden}#HomeFileUpload_4Uploader {visibility:hidden}
</style></head>

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
	
<script type="text/javascript" src="js/shop.js"></script>
	<div class="r_nav">
	   <ul>
			<li class=""><a href="base.php?customer_id=<?php echo $customer_id; ?>">基本设置</a></li>
			<li class=""><a href="fengge.php?customer_id=<?php echo $customer_id; ?>">风格设置</a></li>
			<li class="cur"><a href="defaultset.php?customer_id=<?php echo $customer_id; ?>">首页设置</a></li>
			<li class=""><a href="product.php?customer_id=<?php echo $customer_id; ?>">产品管理</a></li>
			<li class=""><a href="order.php?customer_id=<?php echo $customer_id; ?>&status=-1">订单管理</a></li>
			<li class=""><a href="qrsell.php?customer_id=<?php echo $customer_id; ?>">推广员</a></li>
			<li class=""><a href="customers.php?customer_id=<?php echo $customer_id; ?>">顾客</a></li>
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
	  
	  //如果客户已经替换了图片，则用客户的图片
	  $query2="select id,imgurl,position,url,linktype,foreign_id,title from weixin_commonshop_template_item_imgs where isvalid=true  and template_id=".$template_id." and customer_id=".$customer_id." and position=".$position;
	  $result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
	  while ($row2 = mysql_fetch_object($result2)) {
	  
		  $id=$row2->id;
		  $imgurl=$row2->imgurl;
		  $url=$row2->url;
		  $linktype = $row2->linktype;
		  $foreign_id = $row2->foreign_id;
		  $title = $row2->title;
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
	  
	  
	  $general_imgurl = "";	  
	  $general_url = "";	  
	  $general_linktype = "";	  
	  //if($template_id==9 and $is_shopgeneral and $general_customer_id>0){
	  if($is_shopgeneral and $general_customer_id>0){
        //查找总部幻灯片
		
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
				 $imgurl = $imgurl."|*|".$general_imgurl;	
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
<div id="home" class="r_con_wrap">

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
		<div  class="items"><a  href="#"  style="cursor: default; text-decoration: none;"><img  src="fushi7/images/vip_icon.png"><br>会员中心</a></div>
		<div  class="items"><a  href="#"  style="cursor: default; text-decoration: none;"><img  src="fushi7/images/gift_icon.png"><br>最新产品</a></div>
		<div  class="items"><a  href="#"  style="cursor: default; text-decoration: none;"><img  src="fushi7/images/home_icon.png"><br>热卖产品</a></div>
        <div  class="items"><a  href="#"  style="cursor: default; text-decoration: none;"><img  src="fushi7/images/cart_icon.png"><br>购物车</a></div>
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
		<div  class="products_list">
				<div  class="items"><a  href="#"  style="cursor: default; text-decoration: none;">DIORISSIMO</a></div>
		  </div>
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
                <span><a href="javascript:;" class="shopping-cart">
                     <i class="fa fa-shopping-cart"></i>
                </a></span>
                <span><a href="javascript:;">我的订单</a></span>
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
    <section class="box" id="module">
        <div>
            <div class="user_nav clearfix">
                <ul class="user_nav_list">
                    <li class="pro-class">
                        <a href="javascript:void(0)" id="menu" class="icon-s1"><span class="fa fa-th"></span>所有商品</a>
                        <div class="WX_cat_pop WX_cat_list J_smenu_list" id="menulist" style="display: none;">
                            <i class="WX_cat_btn-arrow"></i>
                           
                            <a href="javascript:;" class="J_ytag WX_cat_sp">防晒丝巾</a>
						   
                           
                           
                        </div>
                    </li>

                    <li>
                        <a href="javascript:void(0)" class="icon-s1"><span class="fa fa-clock-o"></span>新品上架</a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="icon-s1"><span class="fa fa-heart"></span>精选商品</a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="icon-s1"><span class="fa fa-tags"></span>特惠商品</a>
                    </li>

                </ul>
            </div>
        </div>
    </section>
    <div style="clear: both;"></div>
    <div class="user_itlist_nb">
        <div class="title">
            <h2>新品推荐</h2>
            
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
						<span class="shop_skin_index_list" rel="edit-t06"  no="5" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;"><a  href="#" class="div_typename">栏目</a></span><div  class="mod">&nbsp;</div></span>
						<span class="shop_skin_index_list" rel="edit-t10"  no="9" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;"><b  href="#" class="div_typename">栏目</b></span><div  class="mod">&nbsp;</div></span>
                    </li><li>
                        
                         <span class="shop_skin_index_list" rel="edit-t03"  no="2"  style="margin-top:8px;"><span class="img" style="margin-top:-15px;"><img  src="fushi_20/images/ind1-2.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
                         <span class="shop_skin_index_list" rel="edit-t07"  no="6" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;"><a  href="#" class="div_typename">栏目</a></span><div  class="mod">&nbsp;</div></span>
						 <span class="shop_skin_index_list" rel="edit-t11"  no="10" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;"><b  href="#" class="div_typename">栏目</b></span><div  class="mod">&nbsp;</div></span>
                        
                    </li><li>
                            <span class="shop_skin_index_list" rel="edit-t04"  no="3"  style="margin-top:8px;"><span class="img" style="margin-top:-15px;"><img  src="fushi_20/images/ind1-3.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
                            <span class="shop_skin_index_list" rel="edit-t08"  no="7" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;"><a  href="#" class="div_typename">栏目</a></span><div  class="mod">&nbsp;</div></span>
							<span class="shop_skin_index_list" rel="edit-t12"  no="11" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;"><b  href="#" class="div_typename">栏目</b></span><div  class="mod">&nbsp;</div></span>
                    </li><li>
                          <span class="shop_skin_index_list" rel="edit-t05"  no="4"  style="margin-top:8px;"><span class="img" style="margin-top:-15px;"><img  src="fushi_20/images/ind1-4.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
                            <span class="shop_skin_index_list" rel="edit-t09"  no="8" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;"><a  href="#" class="div_typename">栏目</a></span><div  class="mod">&nbsp;</div></span>
							<span class="shop_skin_index_list" rel="edit-t13"  no="12" style="margin-top:-15px;"><span class="text" style="margin-top:-12px;"><b  href="#" class="div_typename">栏目</b></span><div  class="mod">&nbsp;</div></span>
                        
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
			<div  class="homeCcon">
				<span class="shop_skin_index_list" rel="edit-t02"  no="1"><span class="text"><a  href="#" class="div_typename" style="color:#ff0000;font-size:18px;line-height:40px">大标题</a></span><div  class="mod">&nbsp;</div></span>
				<span class="shop_skin_index_list" rel="edit-t03"  no="2"><span class="text"><a  href="#" class="div_typename" style="color:#ff0000">小标题</a></span><div  class="mod">&nbsp;</div></span>
				
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
					<h2><span class="shop_skin_index_list" rel="edit-t02"  no="1"><span class="text"><a  href="#" class="div_typename" style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></h2>
					<span class="shop_skin_index_list" rel="edit-t06"  no="5"><span class="img" ><img  src="fushi_23/images/ind1-1.png"></span><div  class="mod">&nbsp;</div></span>
					<div class="homeA_span" style="width:100%;"><span class="shop_skin_index_list" rel="edit-t10"  no="9"><span class="text"><a  href="#" class="div_typename" style="color: #0680ad;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
					<b></b>
				</li>
				<li  class="swiper-slide"  style="width: 25%; height: 135px;">
					
						<h2><span class="shop_skin_index_list" rel="edit-t03"  no="2"><span class="text"><a  href="#" class="div_typename"  style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></h2>
						<span class="shop_skin_index_list" rel="edit-t07"  no="6" ><span class="img" ><img  src="fushi_23/images/ind1-2.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
						<div class="homeA_span" style="width:100%;"><span class="shop_skin_index_list" rel="edit-t11"  no="10"><span class="text"><a  href="#" class="div_typename" style="color: #0680ad;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
						<b></b>
					
				</li>
				<li  class="swiper-slide"  style="width: 25%; height: 135px;">
					
						<h2><span class="shop_skin_index_list" rel="edit-t04"  no="3"><span class="text"><a  href="#" class="div_typename"  style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></h2>
						<span class="shop_skin_index_list" rel="edit-t08"  no="7" ><span class="img" style="margin-top:-15px;"><img  src="fushi_23/images/ind1-3.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
						<div class="homeA_span" style="width:100%;"><span class="shop_skin_index_list" rel="edit-t12"  no="11"><span class="text"><a  href="#" class="div_typename" style="color: #0680ad;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
						<b></b>
					
				</li>
				<li  class="swiper-slide"  style="width: 25%; height: 135px;">
					
						<h2><span class="shop_skin_index_list" rel="edit-t05"  no="4"><span class="text"><a  href="#" class="div_typename"  style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></h2>
						<span class="shop_skin_index_list" rel="edit-t09"  no="8" style="margin-top:8px;"><span class="img" style="margin-top:-15px;"><img  src="fushi_23/images/ind1-4.png"  width="32"  height="25"></span><div  class="mod">&nbsp;</div></span>
						<div class="homeA_span" style="width:100%;"><span class="shop_skin_index_list" rel="edit-t13"  no="12"><span class="text"><a  href="#" class="div_typename" style="color: #0680ad;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
						<b></b>
					
				</li>
				
                			</ul>
		</div>
	   </div>
    

        	

    </div>
   	

</div>
</div>

<?php }else if($template_id==24){?>

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
			<div  class="homeCtitle"  style="color:#ffffff"><span class="shop_skin_index_list" rel="edit-t02"  no="1"><span class="text"><a  href="#" class="div_typename" style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
			<div  class="homeCcon"  style="color:#ffffff"><span class="shop_skin_index_list" rel="edit-t03"  no="2"><span class="text"><a  href="#" class="div_typename" style="color:#fff;">栏目</a></span><div  class="mod">&nbsp;</div></span></div>
			<div  class="homeCnav" style="position:absolute;top:365px">
				<div  class="homeCnavbox swiper-container">
					<ul  class="swiper-wrapper"  style="width: 100%; height: 65px;">
						<li  class="swiper-slide"  style="width: 25%; height: 65px;">
											
								<div  class="shop_skin_index_list items"  rel="edit-t05"  no="4" style="width:25px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_24/images/index5-2.png"  width="31"  height="26"></div></div>
								<h2 style="margin-top:-5px;"><span class="shop_skin_index_list" rel="edit-t09"  no="8"><span class="text"><span  href="#" class="div_typename" style="color:#fff;">栏目</span></span><div  class="mod">&nbsp;</div></span></h2>
							
						</li><li  class="swiper-slide"  style="width: 25%; height: 65px;">
												
								<div  class="shop_skin_index_list items"  rel="edit-t06"  no="5" style="width:25px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_24/images/index5-3.png"  width="31"  height="26"></div></div>
								<h2 style="margin-top:-5px;"><span class="shop_skin_index_list" rel="edit-t10"  no="9"><span class="text"><span  href="#" class="div_typename" style="color:#fff;">栏目</span></span><div  class="mod">&nbsp;</div></span></h2>
							
						</li><li  class="swiper-slide"  style="width: 25%;height: 65px;">
							
								<div  class="shop_skin_index_list items"  rel="edit-t07"  no="6" style="width:25px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_24/images/index5-4.png"  width="31"  height="26"></div></div>
								<h2 style="margin-top:-5px;"><span class="shop_skin_index_list" rel="edit-t11"  no="10"><span class="text"><span  href="#" class="div_typename" style="color:#fff;">栏目</span></span><div  class="mod">&nbsp;</div></span></h2>
							
						</li><li  class="swiper-slide"  style="width: 25%; height: 65px;">
							
								<div  class="shop_skin_index_list items"  rel="edit-t08"  no="7" style="width:25px;text-align:center;margin:0 auto;padding-top:5px;"><div  class="img"><img  src="fushi_24/images/index5-5.png"  width="31"  height="26"></div></div>
								<h2 style="margin-top:-5px;"><span class="shop_skin_index_list" rel="edit-t12"  no="11"><span class="text"><span  href="#" class="div_typename" style="color:#fff;">栏目</span></span><div  class="mod">&nbsp;</div></span></h2>
							
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
<div id="shop_skin_index"  <?php if(!empty($index_bg)){ ?>style="background:#<?php echo $index_bg; ?>"<?php } ?>>
    <div  class="shop_skin_index_list banner"  rel="edit-t01"  no="0">
        <div  class="img"><img  src="fushi2/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
       <div  id="SetHomeCurrentBox"  style="height: 445px; width: 310px;"></div>
	</div>
    <div  id="index_m" style="top:20px">
      <div  class="membersbox pad50">
	 
		<div  class="mobile6_title"><a  href="#"><span class="shop_skin_index_list" rel="edit-t02"  no="1"><span style="font-size:24px;" class="div_typename">大标题</span><div  class="mod">&nbsp;</div></span></div></a>
		<div  class="mobile6_con"><a  href="#"><span class="shop_skin_index_list" rel="edit-t03"  no="2"><span class="div_typename">小标题</span><div  class="mod">&nbsp;</div></span></div></a>
		<div  class="mobile6_pay" style="top:140px;color:#fff"><a  href="#"><span class="shop_skin_index_list"  rel="edit-t04"  no="3"><span class="div_typename">立即购买</span><div  class="mod">&nbsp;</div></span></div></a>
		<div  class="mobile6_margin"></div>
		
		<div  class="mobile6_nav" style="top:450px;">
			<div  class="mobile6_navbox swiper-container">
				<ul  class="swiper-wrapper">
					<li  class="swiper-slide " style="width:25%;">
						<a href="#"><span class="shop_skin_index_list"  rel="edit-t05"  no="4"><span class="div_typename">首页</span><div  class="mod">&nbsp;</div></span></a>
					</li>
					<li  class="swiper-slide " style="width:25%;">
						<a href="#"><span class="shop_skin_index_list"  rel="edit-t06"  no="5"><span class="div_typename">新品</span><div  class="mod">&nbsp;</div></span></a>
					</li>
					<li  class="swiper-slide " style="width:25%;">
						<a href="#"><span class="shop_skin_index_list"  rel="edit-t07"  no="6"><span class="div_typename">热卖</span><div  class="mod">&nbsp;</div></span></a>
					</li>
					<li  class="swiper-slide " style="width:25%;">
						<a href="#"><span class="shop_skin_index_list"  rel="edit-t08"  no="7"><span class="div_typename">促销</span><div  class="mod">&nbsp;</div></span></a>
					</li>				
				</ul>
			</div>
		</div>
    </div>
   	

    </div>
</div>


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
 <script type="text/javascript" src="fushi_27/js/jquery-1.9.0.min.js"></script>
<script type="text/javascript" src="fushi_27/js/jqueryrotate.js"></script>
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
        <div  class="img"><img  src="fushi2/images/banner.jpg"></div><div  class="mod"  style="display: none;">&nbsp;</div>
       <div  id="SetHomeCurrentBox"  style="height: 445px; width: 310px;"></div>
	</div>
 
   <div class="music_div" style="position:absolute;"> 
    <table> 
     <tbody>
      <tr> 
       <td class="img_td">
        <div class="music_img" style="z-index:333">
         <img id="img1" src="fushi_27/images/gh.png" alt="" class="gh" style="z-index:333"/>
		 <div  class="shop_skin_index_list"  rel="edit-t02"  no="1"><div  class="img wenzi" ><img  src="fushi_27/images/wz1.png" alt=""></div><div  class="mod">&nbsp;</div>
		   
		 </div>
		
		
       
        </div> </td> 
       <td class="img_td"><a href=""> </a>
        <div class="music_img">
         <a href=""><img src="fushi_27/images/wz2.png" alt="" class="wenzi" /></a> 
         <img id="img2" src="fushi_27/images/gh.png" alt="" class="gh" style="z-index:333"/> 
        </div> </td> 
       <td class="img_td"><a href=""> </a>
        <div class="music_img">
         <a href="#"><img src="fushi_27/images/wz3.png" alt="" class="wenzi" /></a> 
         <img id="img3" src="fushi_27/images/gh.png" alt="" class="gh" style="z-index:333"/> 
        </div> </td> 
      </tr> 
     </tbody>
    </table> 
   </div> 
   <footer> 
    <div class="footer" style="position:absolute;"> 
     <ul class="clearfix"> 
	 
	 
	 
                    
        
      <li><a href=""><img src="fushi_27/images/foot_img1.png" /><span>首页</span></a></li>
      <li><a href=""><img src="fushi_27/images/foot_img2.png" /><span>咨询</span></a></li> 
      <li><a href=""><img src="fushi_27/images/foot_img3.png" /><span>购物中心</span></a></li> 
      <li><a href=""><img src="fushi_27/images/foot_img4.png" /><span>会员登录</span></a></li> 
     </ul> 
    </div> 
   </footer> 

</div>

  <script language="javascript">
$(function(){

var size = window.innerHeight;
$('#main').css('height',size);
	
var rotation = function (){
   $("#img1").rotate({
      angle:0, 
      animateTo:360, 
	  duration : 3000,
      callback: rotation,
      easing: function (x,t,b,c,d){
        return c*(t/d)+b;
	  }
   });
   
   $("#img2").rotate({
      angle:0, 
      animateTo:360, 
	  duration : 3000,
      callback: rotation,
      easing: function (x,t,b,c,d){
        return c*(t/d)+b;
	  }
   });
   
   $("#img3").rotate({
      angle:0, 
      animateTo:360, 
	  duration : 3000,
      callback: rotation,
      easing: function (x,t,b,c,d){
        return c*(t/d)+b;
	  }
   });
}
rotation();

setInterval(function(){ 
	$(".sharp1").fadeOut(100).fadeIn(100); 
},600);

setInterval(function(){ 
	$(".sharp2").fadeOut(100).fadeIn(100); 
},700);

setInterval(function(){ 
	$(".sharp3").fadeOut(100).fadeIn(100); 
},500);

});

function showTel(){
	alert("tel:");
}
</script> 

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
<?php } ?>
  </div>
  <div class="m_righter">
  <script type="text/javascript" src="../common/js/jscolor/jscolor.js"></script>
		<form id="frm_uploadimg" action="save_templateimg.php?customer_id=<?php echo $customer_id; ?>&template_id=<?php echo $template_id; ?>" method="post" enctype="multipart/form-data">
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
						<select name="type_id_1_1" id="type_id_1_1">
						<option value="-1" selected="selected">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						
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
						<select  name="type_id_1_2"  id="type_id_1_2">
						<option value="-1">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						
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
						</select></div>
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
						<select  name="type_id_1_3"  id="type_id_1_3">
						<option value="-1">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						
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
						</select></div>
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
						<select  name="type_id_1_4"  id="type_id_1_4">
						<option value="-1">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						
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
						</select></div>
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
						<select  name="type_id_1_5"  id="type_id_1_5">
						<option value="-1" selected="selected">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						
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
						</select></div>
					</div>
					<div class="clear"></div>
				</div>
				<?php 
				
				//if($template_id==9 and $is_shopgeneral){
               if($is_shopgeneral){				
				   if($is_generalcustomer==0){
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
						<select  name="type_id_1_<?php echo $num; ?>"  id="type_id_1_<?php echo $num; ?>">
						<option value="-1" selected="selected">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						
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
						</select></div>
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
						<select  name="type_id_2"  id="type_id_2">
						<option value="-1" selected="selected">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						
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
						
						</select></div>
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
					<div class="url_select" style="display: block;">
						<span class="fc_red">*</span> 链接页面<br>
						<div class="input">
						<select  name="type_id_3"  id="type_id_3">
						<option value="-1" selected="selected">--请选择--</option>
						<option value="-6" >全部产品</option>
						<option value="-2" >新品上市</option>
						<option value="-3" >热卖产品</option>
						<option value="-4" >购物车</option>
						
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
						
						</select></div>
					</div>
				</div>
			</div>
			<div class="button"><input type="submit" class="btn_green" name="submit_button" value="提交保存"></div>
			<input type="hidden" name="contenttype" id="contenttype" value="2">
			<input type="hidden" name="position" id="position" value="1">
			
		</form>
	</div>
	<div class="clear"></div>
</div>
<div id="home_mod_tips" class="lean-modal pop_win">
	<div class="h">首页设置<a class="modal_close" href="#"></a></div>
	<div class="tips">首页设置成功</div>
</div>	</div>
<div>

<?php 

mysql_close($link);
?>
</div></div></body></html>