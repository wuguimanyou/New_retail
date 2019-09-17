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
require('select_skin.php');
// $customer_id = $_SESION['customer_id'];
// $user_id     = 195203;



//查询商城有无开启上传身份证件设置
$is_identity 	   = 0; 		//身份证限制
$is_uploadidentity = 0;       //是否开启上传身份证附件
$query = "select id, is_uploadidentity,is_identity from weixin_commonshops where isvalid=true and customer_id=".$customer_id." LIMIT 1";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
while($row=mysql_fetch_object($result)){
	$is_uploadidentity = $row->is_uploadidentity;
	$is_identity 	   = $row->is_identity;
}


$id 		 = -1;
$id 		 = $_GET['id'];
$type 		 = $_GET['type'];

$name        = 	'';//收货人名字
$phone       = 	'';//联系电话
$address     = 	'';//自定义街道等信息
$identity  	 = 	 '';//身份证号
$location_p  = 	'';//省
$location_c  = 	'';//市
$location_a  = 	'';//镇区
$is_default  = 	 0;//是否默认

$identityimgt=  '';//身份证正面
$identityimgf=  '';//身份证反面


if($type=='edit'){
	$query  = "SELECT address,name,phone,location_p,location_c,location_a,is_default,identityimgt,identityimgf,identity FROM weixin_commonshop_addresses WHERE isvalid=true and id=".$id;
	$result = mysql_query($query);
	while( $row = mysql_fetch_object($result) ){
	    $name        = $row->name;
	    $phone       = $row->phone;
	    $address     = htmlspecialchars($row->address);	
	    $identity    = $row->identity;
	    $location_p  = $row->location_p;
	    $location_c  = $row->location_c;
	    $location_a  = $row->location_a;
	    $is_default  = $row->is_default;
	    $identityimgt= $row->identityimgt;
	    $identityimgf= $row->identityimgf;
	}
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>填写地址</title>
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
    <link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" /> 

<style type="text/css">
select {
  /*Chrome和Firefox里面的边框是不一样的，所以复写了一下*/
  border: solid 1px #000;

  /*很关键：将默认的select选择框样式清除*/
  appearance:none;
  -moz-appearance:none;
  -webkit-appearance:none;

  /*在选择框的最右侧中间显示小箭头图片*/
  background: url("images/list_image/tagbg_item_down.png") no-repeat scroll right center transparent;
	background-color:#fff;
	background-size: 15px 15px;

  /*为下拉小箭头留出一点位置，避免被文字覆盖*/
  padding-right: 15px;
}
	.pay_address{
		display: inline-block;
	}
	#location_p,#location_c,#location_a{
line-height: 18px;
    width: 70px;
    border: none;
    height: 28px;
    padding-right: 24px;
    overflow: hidden;
	}
	.list-one .left-title{
		width:25%;
		float: left;
		line-height: 24px;
	}
	.frame_image .area-one {
    position: relative;
    width: 90%;
    height: 150px;
    display: block;
	margin:10px auto 0;
}
	.frame_image .area-one p{
		width: 100%;
		height: 150px;
		text-align: center;
		border:1px solid #d1d1d1;
		line-height: 150px;
		background-color: #fff;
}
	.frame_image .area-one img{
		width: 100%;
		height: 150px;
		text-align: center;
		position: absolute;
    	top: 0;
    	left: 0;
}
	.frame_image_select {
    width: 100%;
    height: 150px;
    opacity: 0;
    position: absolute;
    top: 0px;
    left: 0px;
}
</style>

</head>
<link type="text/css" rel="stylesheet" href="./css/order_css/style.css" media="all">
<link type="text/css" rel="stylesheet" href="./css/order_css/dingdan.css"/>
<link type="text/css" rel="stylesheet" href="./css/order_css/address.css" />
<body id="mainBody" data-ctrl=true style="background:#f8f8f8;">
    <div id="mainDiv">
	   <!--  <header data-am-widget="header" class="am-header am-header-default">
		    <div class="am-header-left am-header-nav" onclick="history.go(-1)">
			    <img class="am-header-icon-custom icon_back" src="./images/center/nav_bar_back.png"/><span>返回</span>
		    </div>
	        <h1 class="am-header-title" style="font-size:18px;">填写地址</h1>
	    </header>
        <div class="topDiv"></div> --><!-- 暂时隐藏头部导航栏 -->
         
		<!-- 收货人 -->
		<form action="save_address.php"  method="post" enctype="multipart/form-data" id="upForm">
		<input type="hidden" name="type" value="<?php echo $type?>">
		<input type="hidden" name="id" value="<?php echo $id?>">
        <div class="list-one">
            <div class="left-title"><span>收货人</span></div>
            <div class="right-content"><input type="text" name="name" id="to_name" value="<?php echo $name;?>" placeholder="填写收货人姓名" class="input_text"></div>
        </div>
		
		<!-- 联系电话 -->
        <div class="list-one">
            <div class="left-title"><span>联系电话</span></div>
            <div class="right-content"><input type="text" value="<?php echo $phone;?>" id="phone" name="phone" placeholder="固话/手机号码" class="input_text"></div>
        </div>
		
		
		<!-- 收货地址 -->
        <div onclick="" class="list-one">
            <div class="left-title"><span>所在地区</span></div>
				<div class="div_address">
					<div id="pay_address_p" class="pay_address">	
						<select name="location_p" id="location_p" class="default"></select>
					</div>					
					<div id="pay_address_c" class="pay_address">	
						<select name="location_c" id="location_c" class="default"></select>					
					</div>					
					<div id="pay_address_a" class="pay_address">		
						<select name="location_a" id="location_a" class="default"></select>
					</div>
				</div>			
        </div>
		<!-- 详细地址 -->
		<div class="list-one">
			<div class="text_frame">
				<textarea id="reasonContent" class="input_text" name="address" style="height:100%;resize:none;" placeholder="详细地址"><?php echo $address?></textarea>
			</div>
		</div>       
		<!-- 设为默认地址 -->
		<div class="list-one" style="margin-top:10px;border-top: 1px solid #eee;">
            <div class="left-title"><span>设为默认地址</span></div>
            <div onclick="clickedDefault();" class="right-content">
				<img id="ivDefault" src="./images/order_image/icon_check_gray.png">
				<input type="hidden" id="checkdefalt" name="default" value="<?php echo $is_default;?>">
			</div>
        </div>
		<?php 
			if($is_identity){
		?>
		<!-- 身份证号 -->
		<div class="list-one">
            <div class="left-title"><span>身份证号</span></div>
            <div class="right-content"><input type="text" value="<?php echo $identity;?>" id="identity" name="identity" placeholder="身份证号" class="input_text"></div>
        </div>
        <?php }
			if($is_uploadidentity){
		?>
       <div class="frame_image" id="">
            <div class="area-one">
            	<p style="position:relative;">上传身份证正面</p>
                <img id="img_0" src="<?php echo $identityimgt;?>" >
                <input type="file" style="z-index:2;" id="image1"  accept="image/*" class="frame_image_select" name="Filedata_[]" value="" old_identityimgt="<?php echo $identityimgt;?>">
            </div>
            <div class="area-one">
            	<p style="position:relative;">上传身份证背面</p>
                <img id="img_1" src="<?php echo $identityimgf;?>" >
				<input type="file" style="z-index:2;" id="image2"  accept="image/*" class="frame_image_select" name="Filedata_[]" value="" old_identityimgf="<?php echo $identityimgf;?>">
            </div>
        </div>
		<?php  }?>
        </form>		
		<!-- 下面的按钮地区 - 开始 -->
		<div class="list-one div_gray_background" style="background-color: #f8f8f8;border:none;">
			<div onclick="clickedSave();" class="button_black18">保存</div>
			<!-- <div onclick="clickedDelete();" class="button_white18">删除</div> -->
		</div>
		<!-- 下面的按钮地区 - 终结 -->		
    </div>
    
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>    
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>
    <script type="text/javascript" src="../common/region_select.js"></script>
    <script type="text/javascript" src="../common/js/common.js"></script>
</body>		

<script type="text/javascript">
	var is_default = <?php echo $is_default?>;
	var isDefault = false;//设为默认地址flag
	checkd(is_default);
	
	$(function(){
		 new PCAS('location_p', 'location_c', 'location_a', '<?php echo $location_p?>', '<?php echo $location_c?>', '<?php echo $location_a?>',1);
		 $(".frame_image").on("change",":file",function(){
            fileSelect_banner(this);
        });
	});
	
	function checkd(is_default){    //判断修改的时候是否已经是默认地址
		if(is_default==1){
			$("#ivDefault").attr("src","./<?php echo $images_skin?>/order_image/icon_check_orange.png");
		}
	}	
	//点击【设为默认地址】
	function clickedDefault(){	//改变默认的隐藏域值再去保存
		if(isDefault){
    		isDefault = false;
			 $("#ivDefault").attr("src","./images/order_image/icon_check_gray.png");
			 $("#checkdefalt").val(0);
    	}else{
    		isDefault = true;
			$("#ivDefault").attr("src","./<?php echo $images_skin?>/order_image/icon_check_orange.png");
			$("#checkdefalt").val(1);
    	}
	}

	
	//点击【保存】
	function clickedSave(){
		
		var type 			= "<?php echo $type?>";
		var id 				= "<?php echo $id?>";
		
		var name 			= $("#to_name").val();			//联系人
		var phone 			= $("#phone").val();			//电话
		var location_p 		= $("#location_p").val();		//省
		var location_c 		= $("#location_c").val();		//市
		var location_a 		= $("#location_a").val();		//区
		var address 		= $("#reasonContent").val();	//详细地址
		var identity 		= $("#identity").val();     	//身份证号码
		var checkdefalt 	= $("#checkdefalt").val();		//是否默认地址
		var img1 			= $("#image1").val();
		var img2 			= $("#image2").val();
		var old_identityimgt 			= $("#image1").attr('old_identityimgt');
		var old_identityimgf 			= $("#image2").attr('old_identityimgf');	
		
		var moblie_phone 	= /^((13[0-9])|(15[^4,\\D])|(18[0,5-9]))\\d{8}$/;
		var fixed_phone 	= /0\d{2,3}-\d{5,9}|0\d{2,3}-\d{5,9}/;
		var identity_15     =/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$/;//15位身份证正则式
		var identity_18     =/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/;//18位身份证正则式

		if( location_p=='' || location_c=='' || location_a=='' ){
			showAlertMsg("提示","所在地区必须选择！","确定");
			return false;
		}

		if( name =='' ){
			showAlertMsg("提示","请填写联系人","确定");
			return false;
		}
		if( phone =='' ){
			showAlertMsg("提示","请填写联系号码","确定");
			return false;
		}
		<?php 
			if($is_identity){
		?>
		if( identity =='' ){
			showAlertMsg("提示","请填写身份证号码","确定");
			return false;
		}
		if( !identity_15.test(identity) && !identity_18.test(identity) ){
			//alert("请填写正确的身份证号码");
			showAlertMsg("提示","请填写正确的身份证号码","确定");
			return false;
		}
		<?php }
			if($is_uploadidentity){
		?>
		if((img1=='' && old_identityimgt =='') || (img2=='' && old_identityimgf=='') ){
			showAlertMsg("提示","身份证附件必须上传","确定");
			return false;
		}

		<?php }?>

		if(chkPhoneNumber(phone)==false && !fixed_phone.test(phone)){
			//alert("请填写正确的联系号码");
			showAlertMsg("提示","请填写正确的联系号码","确定");
			return false;
		}

		if( address == '' ){
			//alert("请填写详细地址");
			showAlertMsg("提示","请填写详细地址","确定");
			return false;
		}
		$(".sharebg ").click(function(){
		//alert(1);
	})
		loading(100,1);
	$("#upForm").submit();


	}

	    //获取本地的图片
    function fileSelect_banner(evt) {
        if (window.File && window.FileReader && window.FileList && window.Blob) {
            currfile = evt;
            var files = evt.files;//直接传入file对象，evt.target改成evt
            var pid = $(evt).data("pid");	//现在选择的商品的pid
            var file;
            file = files[0];
            if (!file.type.match('image.*')) {
                return;
            }
            reader = new FileReader();
            reader.onload = (function (tFile) {
                return function (evt) {
                    dataURL = evt.target.result;
                    $(currfile).prev("img").eq(0).attr("src",dataURL);
                    }
            }(file));
            reader.readAsDataURL(file);
            sendFile = file;
        } else {
            alert('该浏览器不支持文件管理。');
        }
    }
</script>
<!--引入侧边栏 start-->
<?php  include_once('float.php');?>
<!--引入侧边栏 end-->
<?php require('../common/share.php'); ?>
</body>
</html>