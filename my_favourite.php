<?php
header("Content-type: text/html; charset=utf-8");     
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
//require('../back_init.php'); 
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');
mysql_query("SET NAMES UTF8");
require('../common/jssdk.php');

//初始化--star
$user_id  	 = 194515;
$customer_id = 3243;
//初始化--star
$start = 0;
$end   = 10;
$query1 = "select p.name,p.orgin_price,p.now_price,p.is_invoice,p.show_sell_count,p.sell_count,p.isvp,p.vp_score from weixin_user_collect c left join weixin_commonshop_products p on c.collect_id=p.id where c.isvalid=true and c.customer_id=".$customer_id." and c.user_id=".$user_id." and c.collect_type=1";//商品资料

$query1_num = "select count(1) pcount from weixin_user_collect c left join weixin_commonshop_products p on c.collect_id=p.id where c.isvalid=true and c.customer_id=".$customer_id." and c.user_id=".$user_id." and c.collect_type=1";//商品收藏数
$result = mysql_query($query1_num) or die('Query failed num1: ' . mysql_error());  
while ($row = mysql_fetch_object($result)) {
	$pcount = $row->pcount;
}
$sql = "select isOpenBrandSupply from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
	$result = mysql_query($sql) or die('Query failed: ' . mysql_error());  
	while ($row = mysql_fetch_object($result)) {
		$isOpenBrandSupply = $row->isOpenBrandSupply;
	}//商家是否开启供应商品牌
	
$query2 = "select collect_id from weixin_user_collect where isvalid=true and customer_id=".$customer_id." and user_id=".$user_id." and collect_type=2";//店铺资料
//echo $query;
$result1 = mysql_query($query1) or die('Query failed1: ' . mysql_error());
$result1_num = mysql_query($query1_num) or die('Query failed1: ' . mysql_error());
$result2 = mysql_query($query2) or die('Query failed2: ' . mysql_error());
?>
<!DOCTYPE html>
<html>
<head>
    <title>我的收藏</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta content="no" name="apple-touch-fullscreen">
    <meta name="MobileOptimized" content="320"/>
    <meta name="format-detection" content="telephone=no">
    <meta name=apple-mobile-web-app-capable content=yes>
    <meta name=apple-mobile-web-app-status-bar-style content=black>
    <meta http-equiv="pragma" content="nocache">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8">
    
    <link type="text/css" rel="stylesheet" href="./assets/css/amazeui.min.css" />
    <link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />    
    <link type="text/css" rel="stylesheet" href="./css/css_orange.css" />

    

    <link rel="stylesheet" id="wp-pagenavi-css" href="./css/list_css/pagenavi-css.css" type="text/css" media="all">
	<link rel="stylesheet" id="twentytwelve-style-css" href="./css/list_css/style.css" type="text/css" media="all">	
	<link type="text/css" rel="stylesheet" href="./css/list_css/r_style.css" />
    
<style>  
	.am-header-icon-custom{height:16px;margin-left:2px;}
	.white-list{background-color: white;border-top: 1px solid #DEDBD5;border-bottom: 1px solid #DEDBD5;}
	.right-actionImg{width: 14px;height: 19px;}
	.cost-title{background:transparent;padding: 7px;}
	.pinterestUl{padding:4px !important;margin-bottom:-64px !important;transition: height 1s !important; height: 12202px;}
	.list{padding:2px;margin-top:2px;width:97%;background: #fff;height: 111px;}
	.listImg{width: 29%;float: left;min-width:70px;}
	.listImgBlow{width: 68%;float: right;margin-top: 5px;padding: 5Px;margin-right: 6px;}
	.listTitle{margin-top: -10px;}
	.pinterest_title{overflow: hidden;max-height: 38px;line-height: 20px;font-size:12px;color: #1c1f20;font-weight:bold;text-indent: 32px;padding-top: 2px;}	
	  #topDivSel2{width:100%;height:45px;line-height:45px;padding:0px 10px;;background-color:white;}
	  .plus-tag-add{width:100%; overflow:auto;min-width:100px;line-height:50px;}
	  .plus-tag-add-left{float:left;color:#656668;}
	  .plus-tag-add-right{float:right; margin-right:10px;height:45px;line-height:45px;}
	  .font-red{color:red;}
	  .plus-tag-add-right-button{padding: 5px 10px; border: 1px solid #2e2e2e; color:#2e2e2e;}
	  .my-entry-content{clear:both;}
	  #pinterestList{background:#f8f8f8;}
	  .list dd{margin-bottom:0px !important;}
	  #product{width:50%;height:45px;line-height:45px;float:left;text-align:center;}
	  #shop{width:50%;height:45px;line-height:45px;float:left;text-align:center;}
	  .topDivSel{width:100%;height:45px;padding-top:0px;background-color:#f8f8f8;}
  
</style>


</head>
<!-- Loading Screen -->
<div id='loading' class='loadingPop'style="display: none;"><img src='./images/loading.gif' style="width:40px;"/><p class=""></p></div>

<body data-ctrl=true style="background:#fff;">
	<!-- <header data-am-widget="header" class="am-header am-header-default">
		<div class="am-header-left am-header-nav" onclick="goBack();">
			<img class="am-header-icon-custom" src="./images/center/nav_bar_back.png" style="vertical-align:middle;"/><span style="margin-left:5px;">返回</span>
		</div>
	    <h1 class="am-header-title" style="font-size:18px;">我的收藏</h1>
	    <div class="am-header-right am-header-nav" onclick="bianjiShangpin();">
			<img class="am-header-icon-custom" src="./images/center/nav_home.png" />
		</div>
	</header>
	<div class="topDiv"></div> -->   <!-- 暂时屏蔽头部 -->
	    <div class="topDivSel" style="">
		    <div class="plus-tag-add" style="color:rgb(174, 174, 174);padding-left:0px;">
				<div id="product" class="selected" onclick="viewMyFavourite('product');">商品</div>
				<div id="shop" onclick="viewMyFavourite('shop');">店铺</div>
			</div>
	    </div>
	    <div style="height:45px;"></div> <!-- 占据选项框的高度 -->
	    <div class="topDivSel" id = "topDivSel2">
		    <div class="plus-tag-add" id = "plus-tag-add1">
		    	<div class = "plus-tag-add-left" ><span>共收藏<font class = "font-red"><?php echo $pcount; ?></font>个商品</span></div>
		    	<div class = "plus-tag-add-right"><span class = "plus-tag-add-right-button" id = "bianji-btn1"> 编辑</span></div>	
		    </div>
		    <div class="plus-tag-add" id = "plus-tag-add2" style="display:none;">
		    	<div class = "plus-tag-add-left"><span>共收藏<font class = "font-red">5</font> 个店铺</span></div> 
		    	<div class = "plus-tag-add-right"><span class = "plus-tag-add-right-button" id = "bianji-btn2"> 编辑</span></div>	
		    </div>
	    </div>
	     <div style="height:45px;"></div> <!-- 占据选项框的高度 -->
    <!--- <div style="text-align: center;width:100%;">            
        <div style="text-align:center;font-weight: bold;padding:10px 0px 5px 0px;"></div>            
    </div> -->
    <!-- 收藏商品列表 start -->
    <div class="productDiv" id="productDiv">
		<!-- 商品列表 start -->
	    	<div class="entry-content my-entry-content">
				<ul class="pinterestUl col" id="pinterestList" fixcols="1" >
				<?php 
					$p_name		       = "";//商品名
					$p_orgin_price     = "";//商品原价
					$p_now_price       = "";//商品现价
					$p_is_invoice      = "";//商品是否开启发票开关
					$p_show_sell_count = "";//商品虚拟销售数量
					$p_sell_count      = "";//商品真实销售数量
					$p_isvp            = "";//商品是否为VP商品
					$p_vp_score        = "";//商品VP值
					$pro_discount      = 0;
					$total_sales       = 0;
				
					while ($row = mysql_fetch_object($result1)) {
							$p_name            = $row->name;
							$p_orgin_price     = $row->orgin_price;
							$p_now_price       = $row->now_price;
							$p_is_invoice      = $row->is_invoice;
							$p_show_sell_count = $row->show_sell_count;
							$p_sell_count      = $row->sell_count;
							$p_isvp            = $row->isvp;
							$p_vp_score        = $row->vp_score;
							
							$total_sales  = $p_sell_count + $p_show_sell_count; //虚拟销售量+销售量
							$discount = new ConfigUtility();
							$pro_discount = $discount->calc($p_now_price,$p_orgin_price,"div");	//折扣率
				?>
				<div class="list" onclick="gotoProductDetail(0);" style="display: block; left: 2px; top: 10px;">    	
					<div class="listImg">        
						<a class="pinterest_img"><img style="height:106px;" src="./images/temp/r_temp/temp_pic1.png" alt="DX9 大火车 再版" data-original="./images/temp/r_temp/temp_pic1.png" class="ori" id="artwork_img_0"> 
						</a>    	
					</div> 
					<div class="listImgBlow">        	
						<div class="listTitle">            		
							<div class="pinterest_title"><?php echo $p_name; ?></div>            	
							<div class="listTitleMark"><span type="text" class="am-btn am-btn-danger am-radius">品牌</span></div>        	
						</div>        	
						<div class="middleinDiv">            
						<div class="productMoney">￥<?php echo $p_now_price; ?><span class="g_price">￥<?php echo $p_orgin_price; ?></span></div>        
						</div>        
						<div class="middleinDiv">        	
						<span type="text" class="am-btn am-btn-danger am-radius"><?php echo $pro_discount; ?>折</span>        	
						<?php if($p_isvp==1){ ?><span type="text" class="am-btn am-btn-secondary am-radius">VP:<?php echo $p_vp_score; ?></span><?php } ?>        	
						<span type="text" class="am-btn am-btn-warning am-radius">返￥500</span>      
						</div>        
						<div class="BottominDiv">        	
						<img src="./images/list_image/mail.png" class="list_markImag_b">        
						<span class="s_rightstr">已销<?php echo $total_sales; ?></span>       	 
						</div>     
					</div>					
			</div>
			<?php } ?>
					<!-- 商品列表-->
				</ul>
				<p id="pinterestMore" style="display: block;">----- 向下滚动加载更多 -----</p>
				<p id="pinterestDone">----- 加载完毕 -----</p>
			</div><!-- .entry-content -->
			
    	</div>
    	<!-- 商品列表 end -->
	</div>
    <!-- <div id="productContainerDiv" style="width:100%;margin-top:101px;">
        
    	
    </div> -->
    <!-- 收藏店铺列表 start -->
    <!-- 推荐商品列表 end -->
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>    
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>
    <script src="./js/r_global_brain.js" type="text/javascript"></script>
	<script type="text/javascript" src="./js/r_jquery.mobile-1.2.0.min.js"></script>
	<script type="text/javascript" src="./js/my_favourite.js"></script>
	<script src="./js/r_pinterest1.js" type="text/javascript"></script>
    </body>		


</body>
</html>