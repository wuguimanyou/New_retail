<?php
header("Content-type: text/html; charset=utf-8"); 
require('../../config.php');
$customer_id = passport_decrypt($customer_id);
require('../../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
mysql_query("SET NAMES UTF8");
require('../../proxy_info.php');

$keyid = -1;
$pid = -1;
$del = '';
$pagenum = 1;

if(!empty($_GET["keyid"])){
	$keyid = $configutil->splash_new($_GET["keyid"]);
}
if(!empty($_GET["pid"])){
	$pid = $configutil->splash_new($_GET["pid"]);
}
if(!empty($_GET["del"])){
	$del = $configutil->splash_new($_GET["del"]);
}
if(!empty($_GET["pagenum"])){
	$pagenum = $configutil->splash_new($_GET["pagenum"]);
}
if($del=="isok"){
	$query = "update snapup_product_t set isvalid=false where customer_id=".$customer_id." and pid=".$pid." and time_id=".$keyid;
	mysql_query($query) or die('Query failed'.mysql_error());
	
	header("Location:snapup_product.php?customer_id=".passport_encrypt((string)$customer_id)."&keyid=".$keyid."&pagenum=".$pagenum);
}
if($del=="allisok"){
	$pid = explode(",",$pid);
	$pid_count = count($pid);
	$str = "";
	for($i=0;$i<$pid_count;$i++){
		if(is_numeric($pid[$i])){
			$str .= " WHEN ".$pid[$i]." THEN false";
		}	 
	}
	if(!empty($str)){
		$query = "update snapup_product_t set isvalid= case pid ".$str." else isvalid END where customer_id=".$customer_id." and time_id=".$keyid;
		//echo $query;
		mysql_query($query) or die('Query failed'.mysql_error());
	}
	header("Location:snapup_product.php?customer_id=".passport_encrypt((string)$customer_id)."&keyid=".$keyid."&pagenum=".$pagenum);
}
if($pid>0){
	$query = "select name from weixin_commonshop_products where isvalid=true and customer_id=".$customer_id." and id=".$pid;
	$result = mysql_query($query) or die('Query failed'.mysql_error());
	$name = '';
	while($row = mysql_fetch_object($result)){
		$name = $row->name;
	}
	
	$query2 = "select snapup_img from snapup_product_t where isvalid=true and customer_id=".$customer_id." and time_id=".$keyid." and pid=".$pid;
	$result2 = mysql_query($query2) or die('Query failed2'.mysql_error());
	$snapup_img = '';
	while($row2 = mysql_fetch_object($result2)){
		$snapup_img = $row2->snapup_img;
	}
}
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../common/css_V6.0/content<?php echo $theme; ?>.css">
<script type="text/javascript" src="../../js/tis.js"></script>
<script type="text/javascript" src="../../common/js/jquery-2.1.0.min.js"></script>
<script type="text/javascript" src="../../common/js/layer/layer.js"></script>
<meta http-equiv="content-type" content="text/html;charset=UTF-8">
</head>
<script>
  
  function submitV(){	 
	  document.getElementById("upform").submit();
  }
</script>
<body>
<div class="div_new_content">
<form action="save_snapup_img.php?customer_id=<?php echo passport_encrypt((string)$customer_id);?>&pagenum=<?php echo $pagenum;?>" id="upform" name="upform" enctype="multipart/form-data" method="post">
<input type="hidden" name="keyid" value="<?php echo $keyid;?>" />
<input type="hidden" name="pid" value="<?php echo $pid;?>" />
	<div class="WSY_content">
		<div class="WSY_columnbox WSY_list">
	
			<div class="WSY_column_header">
				<div class="WSY_columnnav">
					<a class="white1">抢购封面</a> 
				</div>
			</div>
			<div class="WSY_data">
					<dl class="WSY_member">					
						<div>
							<dt>产品：</dt>
							<dd><?php echo $name;?></dd>
						</div>
					</dl>
					<dl class="WSY_member">			
						<div>
							<dt>封面图片</dt>
							<dd class="spa">
							<?php if($snapup_img!=""){?>
								<img src="<?php echo $snapup_img; ?>" id="img_v" style="width:350px;height:120px;" /><br/>
								<input style="width:200;border:1 solid #9a9999; font-size:9pt; background-color:#ffffff; height:20; margin-left: 100px;margin-top: 5px;margin-bottom: 5px;" id="upfile" size="17" name="upfile" type=file value="<?php echo $snapup_img;?>"> (图片尺寸：宽350*高120)
								<input type=hidden value="<?php echo $snapup_img;?>" name="snapup_img" id="snapup_img" />
											   
							<?php }else{ ?>
											
								<img src="pic/pic.png" id="img_v" style="width:350px;height:120px;" /><br/>
								<input style="width:200;border:1 solid #9a9999; font-size:9pt; background-color:#ffffff; height:20; margin-left: 100px;margin-top: 5px;margin-bottom: 5px;" size="17" name="upfile" id="upfile" type=file value="<?php echo $snapup_img;?>"> (图片尺寸：宽350*高120)
								<input type=hidden value="<?php echo $snapup_img;?>" name="snapup_img" id="snapup_img" />
												
							<?php } ?>		
							</dd>	
							
						</div>
					</dl>
					<script>
						$(function(){
							function getObjectURL(file) {
								var url = null ; 
								if (window.createObjectURL!=undefined) {
									url = window.createObjectURL(file) ;
								} else if (window.URL!=undefined) {
									url = window.URL.createObjectURL(file) ;
									} else if (window.webkitURL!=undefined) {
									url = window.webkitURL.createObjectURL(file) ;
									}
								return url ;
							}
							$("#upfile").change(function(){
								var objUrl;
								if(navigator.userAgent.indexOf("MSIE")>0){
									objUrl = this.value;
								}else
								objUrl = getObjectURL(this.files[0]);
								$("#img_v").attr("src",objUrl);
							}) ;
						})
					</script>
					<div class="WSY_text_input01">
						<div class="WSY_text_input"><input type="button" class="WSY_button" value="提交" onclick="submitV();" style="cursor:pointer;"/></div>
						<div class="WSY_text_input"><input type="button" class="WSY_button" value="取消" onclick="javascript:history.go(-1);" style="cursor:pointer;"/></div>
					</div>
			
			</div>
	
		</div>
	</div>
 </form>
 <div style="width:100%;height:20px;">
 </div>
</div>
<?php
	mysql_close($link);
?>
</body>
</html>