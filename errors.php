<?php
header("Content-type: text/html; charset=utf-8");     
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');
mysql_query("SET NAMES UTF8");
require('../common/jssdk.php');
require('../proxy_info.php');
/*$jssdk = new JSSDK($customer_id);
$signPackage = $jssdk->GetSignPackage();*/

/**
 * 使用例子：
 *     window.location.href = "errors.php?customer_id=".$customer_id_en."&msg=缺少订单号";(不带url参数)
 *     点击返回上一层	跳转到	上一页
 * 	or
 *     window.location.href = "errors.php?customer_id=".$customer_id_en."&url=product_detail.php&pid=".$pid;(带url参数可自定义跳转)
 *     点击返回上一层	跳转到	product_detail.php?customer_id=VmNUZFY0CDI=&pid=110
 */
$url = '';
$i	 = 0;
if(!empty($_GET['url'])){
	$url = $configutil->splash_new($_GET['url']);	//自定义跳转页面
	foreach($_GET as $key => $val){		//获取参数
		if($key == 'url' or $key == 'msg'){
			continue;
		}
		$data[$i]['key'] = $key;
		$data[$i]['val'] = $val;
		$i++;
	}
	for($j=0;$j<count($data);$j++){
		if(0 == $j){
			$url .= "?".$data[$j]['key']."=".$data[$j]['val'];	//重组url地址
		}else{
			$url .= "&".$data[$j]['key']."=".$data[$j]['val'];	//重组url地址
		}
	}
}else if(!empty($_SESSION['nurl_'.$customer_id])){
	$url = $_SESSION['nurl_'.$customer_id];		//上一页url地址
}

$msg = '';		//错误信息
if(!empty($_GET['msg'])){
	$msg = $configutil->splash_new($_GET['msg']);
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>报错界面</title>
	<meta http-equiv="content-type" content="text/php; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
	<meta content="telephone=no" name="format-detection" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	

	<style>
	*{margin: 0;padding: 0;}
	img{max-width: 100%;height:auto;border:none;}
	a{text-decoration: none;color: black;}
	body{font-family:"Microsoft YaHei",Arial,Helvetica,sans-serif;-webkit-text-size-adjust:none;}
	input[type='number'],input[type='password'],input[type='reset'],input[type='submit'],input[type='button'],input[type='tel'],button,textarea{-webkit-appearance:none;border-radius: 0;border:1px solid #ddd;} /*去掉苹果的默认UI来渲染按钮*/
	.clear{clear: both; display: block; height: 0; overflow: hidden; visibility: hidden; width: 0;}
	ol,ul {list-style: none;}  
	h1,h2,h3,h4,h5,h6 {font-weight: normal;}
	body{background-color: #f8f8f8;}
	.header{width: 100%;height: 265px;}
	img{display: block; width:200px;margin: 0 auto ;padding-top: 60px; }
	p{text-align: center;font-size: 20px;}
	.grey{color: #707070;font-size: 16px;margin-top: 5px;}
	.foot{width: 100%;overflow: hidden;margin-top: 90px;}
	.left{float: left;width: 38%; margin-left: 9%;background-color: #fff;border: 1px solid #fd7c24;color: #fd7c24;padding: 12px ;font-size: 15px; }
	.right{float: right;width: 38%; margin-right: 9%;background-color:#fd7c24;border: 1px solid #fd7c24;color: #fff;padding: 12px ;font-size: 15px; }
	</style>
</head>
<body>
    <div class="header">
    	<img src="images/feiji.png">
    </div>
    <p><?php if(!empty($msg)){echo $msg;}else{echo "页面跑路了";}?></p>
    <p class="grey">可以通过以下方式继续访问</p>
    <div class="foot">
    	<button id="toBack" class="left">返回上一层</button><button id="toIndex" class="right">返回首页</button>
    </div>
    
    <script type="text/javascript" src="./js/jquery-1.11.1.min.js"></script>
</body>
<script>
var customer_id_en = '<?php echo $customer_id_en;?>';
var back_url 	   = '<?php echo $url;?>';
$('#toBack').click(function(){
	if(back_url == ''){
		back_url = "../common_shop/jiushop/index.php?customer_id="+customer_id_en;
	}
	window.location.href = back_url;
})

$('#toIndex').click(function(){
	window.location.href = "../common_shop/jiushop/index.php?customer_id="+customer_id_en;
})
</script>


</html>