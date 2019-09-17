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
// $user_id = 194515;
// $customer_id = 3243;
//echo $user_id;
$qq                     = '';      //qq
$sex                    =  0;      //性别
$name                   = '';      //真实姓名
$city                   = '';      //城市
$phone                  = '';      //电弧
$account                = '';      //绑定账号/手机
$birthday               = '';      //生日
$province               = '';      //省份
$occupation             = '';      //职业
$wechat_id              = '';      //微信号
$weixin_name            = '';      //微信名
$wechat_code            = '';      //二维码
$parent_id              = -1;      //推荐人id
$weixin_headimgurl      = '';      //微信头像

$query  = "SELECT name,
                  weixin_name,
                  weixin_headimgurl,
                  sex,
                  phone,
                  qq,
                  birthday,
                  province,
                  city,
                  parent_id,
                  weixin_name AS parent_name 
                  FROM weixin_users  
                  WHERE isvalid=TRUE 
                  AND id=".$user_id." LIMIT 1";

$result = mysql_query($query) or die('Query failed 51: ' . mysql_error());
while( $row=mysql_fetch_object($result) ){
        $qq                = $row->qq;
        $sex               = $row->sex;
        $name              = $row->name;
        $city              = $row->city;
        $phone             = $row->phone;
        $birthday          = $row->birthday;
        $province          = $row->province;
        $parent_id         = $row->parent_id;
        $weixin_name       = $row->weixin_name;
        $parent_id         = $row->parent_id;
        $weixin_headimgurl = $row->weixin_headimgurl;
        if( $wechat_code == NULL ){
            $wechat_code = './images/info_image/my_qrcode.png';
        }
        if( $weixin_headimgurl == NULL || $weixin_headimgurl == ''){
            $weixin_headimgurl = './images/my_qrcode.png';
        }
}

//查等级
$commision_level = -1;
$query = "SELECT commision_level FROM promoters WHERE isvalid=true AND customer_id=$customer_id AND user_id=$user_id LIMIT 1";
$result= mysql_query($query) or die('Query failed 80: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
    $commision_level = $row->commision_level;
}


//查用户是否绑定，有基本信息等
$sys_id       = -1;         //id
$account      = '';    		//绑定的手机号
$wechat_id    = ''; 		//weixinid
$wechat_code  = '';			//微信二维码
$occupation   = ''; 		//职业
$sys_is_bind   = ''; 			//是否绑定微信
//$is_bind      =  0;    		//是否绑定
$query = "SELECT id,account,is_bind FROM system_user_t WHERE isvalid=true AND customer_id=".$customer_id." AND user_id=".$user_id." LIMIT 1";
$result= mysql_query($query) or die('Query failed 90: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
      $sys_id       = $row->id;         //id
      $account      = $row->account;    //绑定的手机号
      $sys_is_bind  = $row->is_bind;
      
}
$ext_id = -1;
$is_up_openid = 0;
$query = "SELECT id,is_up_openid FROM weixin_users_extends WHERE isvalid=true AND user_id=$user_id LIMIT 1";
$result= mysql_query($query) or die('Query failed 102: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
    $ext_id = $row->id;
    $is_up_openid = $row->is_up_openid;
}


if($sys_id>0){
	if( $sys_is_bind == 1 ){  //如果从网页端等进来，则system表肯定有，则判断is_bind是否1
        $is_bind = 1;
    }else{
        $is_bind = 0;			//微信端不存在此情况
    } 
}else{
	$is_bind = -1;				//全新用户
}

$query = "SELECT wechat_id,wechat_code,occupation FROM weixin_users_extends WHERE isvalid=true AND user_id=$user_id LIMIT 1";
$result= mysql_query($query)or die('Query failed 118: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
      $wechat_id    = $row->wechat_id;  //weixinid
      $wechat_code  = $row->wechat_code;//微信二维码
      $occupation   = $row->occupation; //职业
      if( $wechat_id == '' || $wechat_id == NULL || $wechat_id == -1){
          $wechat_id = '尚未填写';
      }
}



?>
<!DOCTYPE html>
<html>
<head>
    <title>编辑个人信息</title>
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
    <script type="text/javascript" src="./js/jquery-2.1.3.min.js"></script>
    <script type="text/javascript" src="./js/lamImgView.js"></script>

    
    
</head>
<style>
    body{
        width: 100%;

    }
	#wode_member{width:100%;text-align:center;font-size:13px;padding-top:5px;display: inline-block;}
	.mem{float:left;width:33%;left;font-size:13px;}
	.mem img{width:20px;height:20px;}
	.area-one{width:30%!important;border-bottom:0px!important;}
	 #middle-tab .area-one img { width: 24px; height: 20px;}
	.my_info{width:100%;height:60px;line-height:60px;background-color:white;padding-left:10px;border-bottom:1px solid #eee;}
    
	.am-modal-dialog{border-radius:1px; min-height:34rem;border:3px solid gray;background:url(./images/info_image/qrcode_background.png);background-size: cover;}
	.am-modal-notice{border-radius:3px; min-height:200px;border:1px solid gray;background-color:white;}
	.am-modal-bd{padding-top:3rem;padding-bottom:2rem;border:none}
	.am-modal-btn{display:inline-block !important; line-height:100%; padding:.5em 1em; height:2em;}
	.am-modal-btn+.am-modal-btn{border-color:#818586}
	.am-modal-btn:last-child{border:1px solid #818586}
    .but1 { vertical-align: middle; width:100%; display: inline-block; text-align: center; color: #333; }
    .left{width:40%;float:left;padding-left:4px;font-weight:200;color:#1c1f20;text-align: left;}
    .right{width: 60%;
    float: right;
    text-align: right;
    padding-right: 15px;
    position: relative;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #888;}
    .right span{color:#888}
    .btn{width:80%;margin:20px auto;text-align:center;float:left;margin-left: 10%;}
    .btn span{width:100%;color:white;height:45px;line-height:45px; padding:10px;letter-spacing:3px;}
    #my_code input{position: absolute;opacity: 0;width: 100%;height: 60px;}
    #my_code img{width:50px;height: 50px;margin-bottom: 5px;}
    .right input{width: 100%;}
	.headimg{position:absolute;height:70px;width:70px;opacity:0;}
	.redbutton{width:80%;text-align:center;float:left;margin-left: 10%;}
    .redbutton span{width:100%;color:white;height:45px;line-height:45px; padding:10px;letter-spacing:3px;}
	.redbutton{background-color:#e72530;}

//ld 点击效果
        .button{ 
          -webkit-transition-duration: 0.4s; /* Safari */
          transition-duration: 0.4s;
        }
        .buttonclick:hover{
          box-shadow:  0 0 3px 0 rgba(0,0,0,0.24);
        }
        

</style>
<!-- Loading Screen -->
<div id='loading' class='loadingPop'style="display: none;"><img src='./images/loading.gif' style="width:40px;"/><p class=""></p></div>


<link type="text/css" rel="stylesheet" href="./css/basic.css" />

<body data-ctrl=true style="background:#f8f8f8;">

    
<form action="save_my_data.php"  enctype="multipart/form-data" method="post" id="uform">
    <div id="myInfoDiv" style="position:relative;height:100px;">
        <div class="info-one" style="float:left;width:30%;text-align: center;padding-top:15px;padding-bottom:10px;" onclick="changeVal(1)">
			<input accept="image/*" type="file" class="headimg" name="Filedata_[]" id="image-input" onchange="LamImgView(this)"> 
            <input type="hidden" value="<?php echo $weixin_headimgurl;?>" name="weixin_headimgurl">
             <div id="lamImgBox1"><img id="lamThumbImg1" class="am-img-thumbnail am-circle" src="<?php echo $weixin_headimgurl;?>" width="70" height="70" style="width: 70px;height: 70px;" alt=""></div>
        </div>
        <div class="info-one" style="float:left;width:70%;padding-top: 25px;">
	        <div style="width:100%;text-align:left;font-size:16px;font-weight:bold;">
	        	<span><?php echo $weixin_name;?></span>
            <?php if( $commision_level > 0 ){?>
	        	<img src="./images/info_image/iconfont-gerendengji<?php echo $commision_level;?>.png" style="width:18px;height:16px;">
            <?php }?>
	        </div>
            <?php if($parent_id>0){?>
	        <div style="width:100%;text-align:left;font-size:13px;"><a onclick="my_parent();" id="my_parent" style="text-decoration:underline;color:white;"></a></div>
            <?php }?>
	    </div>
     
	    <img class="button buttonclick" src="./images/info_image/refresh.png" alt="" onclick="update();" width="20" height="20" style="width: 20px;height: 20px;position: absolute;top:10px;right:10px;"/>
      
    </div>

    <div class="my_info" id="MyID" style="margin-top: 0px;">
        <div class="left"><span>我的ID:</span></div>
      <div class="right"><span style="color:rgb(183, 183, 183);">
            <span><?php echo $user_id;?></span>
        </div>
    </div>

    <div class="my_info" id="my_name" style="margin-top: 0px;">
        <div class="left"><span>真实姓名:</span></div>
    	<div class="right"><span style="color:rgb(183, 183, 183);">
            <input type="text" id="name" name="name" style="text-align:right;border:none;" placeholder="请填写真实姓名" value="<?php echo $name;?>" ></span>
        </div>
    </div>

    <div class="my_info" id="my_wechat">
        <div class="left"><span>微信号:</span></div>
        <div class="right"><span><input type="text" id="wechat" name="wechat_id" style="border:none;text-align:right;" placeholder="请填写微信号" value="<?php echo $wechat_id;?>"></span></div>
    </div>

    <div class="my_info" id="my_sex">
        <div class="left"><span>性别:</span></div>
        <div style="width:60%;float:right;text-align:right;padding-right:10px;">
        	<img id="imgbox" src="./images/info_image/female<?php if($sex==0||$sex==2){echo '';}elseif($sex==1){echo '02';}?>.png" style="margin-right:5px;margin-left:5px;height:14px;"/>

        	<!-- <span style="color:rgb(183, 183, 183);margin-right:5px;"><?php echo $sex;?></span> -->
            <select style="border:2px solid #FFF;outline:none;background:none" name="sex" value="" id="sex" onchange="sexselect(this)">
                <option value="2" <?php if($sex==0||$sex==2){ echo 'selected';}?> >女</option>
                <option value="1" <?php if($sex==1){ echo 'selected';}?>>男</option>
            </select>
        </div>
    </div>
    <div class="my_info" id="my_phone">
        <div class="left"><span>手机号码:</span></div>
    	<div class="right"><span><input type="tel" name="phone" id="phone" style="text-align:right;border:none;" placeholder="请填写手机号码" value="<?php echo $phone;?>"></span></div>
    </div>
    <div class="my_info" id="my_qq">
        <div class="left"><span>QQ:</span></div>
    	<div class="right"><span><input type="text" id="qq" name="qq" style="text-align:right;border:none;" placeholder="请填写QQ号码" value="<?php echo $qq;?>"></span></div>
    </div>
    <div class="my_info" id="my_birth">
        <div class="left"><span>生日:</span></div>
    	<div class="right"><span><input type="date" name="birthday" id="birthday" style="border:2px solid #FFF;outline:none;text-align:right;background:none" placeholder="请设置生日日期" value="<?php echo $birthday;?>"></span></div>
    </div>
    <?php if($id == -1){?>
    <div class="my_info" id="my_addr">
        <div class="left"><span>地址:</span></div>
    	<div class="right">
    		<img src="./images/info_image/position.png" style="margin-right:5px;margin-left:5px;height:14px;"/>
    		<span ><?php echo $province.$city;?></span>
    	</div>
    </div>
    <?php }
      if( $sys_id > 0  ){
    ?>
  
    <div class="button buttonclick my_info" id="my_code"  onclick="changeVal(2)">
        <div class="left"><span>微信二维码:</span></div>
        <div class="right">
            <input accept="image/*" type="file" name="Filedata_[]" id="image-input" onchange="LamImgView(this)"> 
            <div id="lamImgBox2"><img id="lamThumbImg2" src="<?php echo $wechat_code;?>"  /></div>
            <input type="hidden" value="<?php echo $wechat_code;?>" name="wechat_code">
        </div>
    </div>
    <div class="my_info" id="my_job">
        <div class="left"><span>职业:</span></div>
      <div class="right"><span><input type="text" id="job" name="job" style="border:none;text-align:right;" placeholder="请填写职业" value="<?php echo $occupation;?>"></span></div>
    </div>
<?php }?>
    <div class="my_info" id="my_link" onclick="goPhoneView(
	<?php 
	if( $is_bind ==1 ){
		echo 1;
	}elseif($is_bind ==0){		//网页绑定微信号	
		echo 0;
	}elseif($is_bind ==-1){
		echo -1;
	}
	?>);" >
        <div class="left" style="display:none"><span><?php if($from_type == 1){ echo "绑定手机号";}else{ echo "绑定微信号";}?>:</span></div>	
    	<div class="right" style="display:none">
    		<span style="color:#ff5f6c;"><?php if( $is_bind > 0 ){echo "已绑定";}else{echo "未绑定";}?></span><img src="./images/vic/right_arrow.png" style="margin-right:5px;margin-left:5px;height:14px;"/>
    	</div>
    </div>
    <div class="button buttonclick btn" id="affirm_btn" onclick="commit();"><span>确认修改</span></div>
	<?php if($from_type==0){?>
	<div class="button buttonclick redbutton" id="exit_btn" onclick="userexit();"><span>退出登录</span></div>
	<?php }?>
</form>
<input  type="hidden" id="img_val" value="0" />

        
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    
</body>		

<script type="text/javascript">
  $(function(){
get_my_order();
  });
   var winWidth = $(window).width();
   var winheight = $(window).height();
  
   function goPhoneView(is_b){	   
	/*
	@is_b: 是否已经绑定
	*/
    var from_type = '<?php echo $from_type;?>';
		if(is_b ==1){return;}
      if( from_type == 1){
          window.location.href="bind_phone.php?customer_id=<?php echo $customer_id_en;?>&is_b="+is_b;
          return false;
      }else{
          showAlertMsg('提醒','请从微信端进行绑定','确认');
          return false;
      }
		  
   }
 

   function commit(){
        $("#uform").submit();
   }
   function update(){
        window.location.href = 'update_my_wechat.php?customer_id=<?php echo $customer_id_en;?>';
   }
	
	function userexit(){
		window.location.href="userexit.php?customer_id=<?php echo $customer_id_en;?>&user_id=<?php echo $user_id;?>";
		
	}
	
	var sex=document.getElementById("sex");
	var imgbox=document.getElementById("imgbox")
	function sexselect(obj){
			var selvalue=obj.value;
			if(selvalue==1){
				imgbox.setAttribute("src","./images/info_image/female02.png")
			}else{
				imgbox.setAttribute("src","./images/info_image/female.png")
			}
	}
	
	function changeVal(obj){
		$('#img_val').val(obj);
	}
  function my_parent(){
    var persion_id = "<?php echo $parent_id;?>";
    var objform = document.createElement('form');
    document.body.appendChild(objform);
    var obj_p = document.createElement("input");
    obj_p.type = "hidden";
    objform.appendChild(obj_p);
    obj_p.value = persion_id;
    obj_p.name = 'persion_id';
    objform.action = 'team_person.php';
    objform.method = "POST";
    objform.submit();

  }
    function get_my_order(){
    var customer_id = "<?php echo $customer_id_en;?>";
    var type = 'my_data';
    var parent_id = "<?php echo $parent_id;?>";
    $.ajax({
      url   :   'get_personal_data.php',
      type  :   'post',
      dataType:   'json',
      data  :{
            customer_id:customer_id,
            type:type,
            parent_id:parent_id
          },
      success:function(data){
        //var data = eval(data);
        $("#my_parent").html("推荐人："+data);
        //$("#my_parent").html("推荐人：");
      }
    });
  }

</script>
<?php require('./NoShare.php');?>
<div id='loading' class='loadingPop'style="display: none;"><img src='./images/loading.gif' style="width:40px;"/><p class=""></p></div>
</body>
<!--引入侧边栏 start-->
<?php  include_once('float.php');?>
<!--引入侧边栏 end-->
</html>