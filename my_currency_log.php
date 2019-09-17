<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../common/utility.php');

//头文件----start
require('../common/common_from.php');
//头文件----end
	


?>
<!DOCTYPE html>
<html>
<head>
    <title>转赠记录 </title>
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
    
    

    <link rel="stylesheet" id="wp-pagenavi-css" href="./css/list_css/pagenavi-css.css" type="text/css" media="all">
	  <link rel="stylesheet" id="twentytwelve-style-css" href="./css/list_css/style.css" type="text/css" media="all">
	  <link type="text/css" rel="stylesheet" href="./css/list_css/r_style.css" />
    
<style>  
   .selected{border-bottom: 5px solid black; color:black; }
   .list {margin: 10px 5px 0 3px;	overflow: hidden;}
   .pinterest_title{ overflow: hidden;height: 36px;line-height: 19px;font-size:12px;color: #1c1f20;font-weight:bold;}
   .plus-tag-add{width:100%;min-width:350px;line-height:45px;padding-left:10px;}
   .submenu{width:33%;height:45px;line-height:45px;float:left;text-align:center;}
   .area-line{height:25px;width:1px;float:left;margin-top: 10px;padding-top: 20px;border-left:1px solid #cdcdcd;}
   .topDivSel{width:100%;height:45px;top:50px;padding-top:0px;background-color:white;}
   .info_middle_content{width:60%;float:left;}
   .info_middle_content .up{width:100%;float:left;text-align:left;line-height: 45px;color:black;}
   .info_middle_content .down{width:100%;float:left;text-align:left;line-height: 5px;color:#aeaeae;font-size:13px;}
   .info_right{width:40%;float:right;color:black;text-align:right;padding-right:10px;}
   .info_middle{width:94%;height:70px;line-height:60px;background-color:white;margin:0 auto;border-bottom:1px solid #eee;}
   .gray{padding-left:10px;color:#d1d1d1}
   .info_left img{width:50px;padding-top:10px;padding-left:10px;}
</style>


</head>
<!-- Loading Screen -->
<div id='loading' class='loadingPop'style="display: none;"><img src='./images/loading.gif' style="width:40px;"/><p class=""></p></div>

<body data-ctrl=true style="background:#fff;">
	<!-- <header data-am-widget="header" class="am-header am-header-default">
		<div class="am-header-left am-header-nav" onclick="goBack();">
			<img class="am-header-icon-custom" src="./images/center/nav_bar_back.png" style="vertical-align:middle;"/><span style="margin-left:5px;">返回</span>
		</div>
	    <h1 class="am-header-title" style="font-size:18px;">转赠记录 </h1>
	</header>
	<div class="topDiv"></div> --><!-- 暂时屏蔽头部 -->
	
	
	<!-- 所有转赠记录 start -->
	<div style="height: 45px;background-color:#f8f8f8;">
		<div class="plus-tag-add gray">4月</div>
    </div>
    
    <div class="tixianRecord" style="width:100%;">
        <div class="recordDiv" id="recordContainer">
        	<div class="info_middle" onclick="gotoViewRerecordDetail(1,'zhifubao');">
		        <div class="info_middle_content" style="">
		    		<div class="up" ><span>董绪永</span></div>
		    		<div class="down" ><span>2016-04-20 09:54:11</span></div>
		    	</div>
	    	<div class="info_right"><span style="color:black;">1.70元</span></div>
		</div>
    </div>
    <div class="tixianRecord" style="width:100%;">
        <div class="recordDiv" id="recordContainer">
        	<div class="info_middle" onclick="gotoViewRerecordDetail(1,'zhifubao');">
		        <div class="info_middle_content" style="">
		    		<div class="up" ><span>董绪永</span></div>
		    		<div class="down" ><span>2016-04-20 09:54:11</span></div>
		    	</div>
	    	<div class="info_right"><span style="color:black;">1.70元</span></div>
		</div>
    </div>
    <div class="tixianRecord" style="width:100%;">
        <div class="recordDiv" id="recordContainer">
        	<div class="info_middle" onclick="gotoViewRerecordDetail(1,'zhifubao');">
		        <div class="info_middle_content" style="">
		    		<div class="up" ><span>董绪永</span></div>
		    		<div class="down" ><span>2016-04-20 09:54:11</span></div>
		    	</div>
	    	<div class="info_right"><span style="color:black;">1.70元</span></div>
		</div>
    </div>
    <div style="height: 45px;background-color:#f8f8f8;">
		<div class="plus-tag-add gray">3月</div>
    </div>
    <div class="tixianRecord" style="width:100%;">
        <div class="recordDiv" id="recordContainer">
        	<div class="info_middle" onclick="gotoViewRerecordDetail(1,'zhifubao');">
		        <div class="info_middle_content" style="">
		    		<div class="up" ><span>董绪永</span></div>
		    		<div class="down" ><span>2016-04-20 09:54:11</span></div>
		    	</div>
	    	<div class="info_right"><span style="color:black;">1.70元</span></div>
		</div>
    </div>
    <!-- 所有转赠记录 start -->
    
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>    
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script type="text/javascript" src="./js/my_favourite.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>
    <script src="./js/r_global_brain.js" type="text/javascript"></script>
    <script type="text/javascript" src="./js/r_jquery.mobile-1.2.0.min.js"></script>
</body>		

<script type="text/javascript">
    //Jump to 详细
    function gotoViewRerecordDetail(tid,type){
			window.location.href="tixianjilu_xiangqing.html";
	}   
</script>




</body>
<!--引入侧边栏 start-->
<?php  include_once('float.php');?>
<!--引入侧边栏 end-->
<?php require('../common/share.php'); ?>
</html>