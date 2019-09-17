<?php
	header("Content-type: text/html; charset=utf-8"); 
	require('../config.php');
	require('../back_init.php');

	$keyid = 0;
	$op = "";

	if(!empty($_GET["keyid"])){
		$keyid = $configutil->splash_new($_GET["keyid"]);
	}
 
	if(!empty($_GET["op"])){
		$op = $configutil->splash_new($_GET["op"]);
	} 
   

	if($keyid>0){
		
		$link = mysql_connect(DB_HOST,DB_USER, DB_PWD);
		mysql_select_db(DB_NAME) or die('Could not select database');
		mysql_query("SET NAMES UTF8");
		require('../proxy_info.php');

		$discuss ='';		
		$batchcode ='';		
		$name ='';								
		
		$query = "SELECT ev.discuss,ev.batchcode,pro.name FROM weixin_commonshop_product_evaluations as ev left join weixin_commonshop_products as pro on pro.id=ev.product_id where ev.id=".$keyid." and pro.customer_id=".$customer_id;
		$result = mysql_query($query) or die('Query failed: ' . mysql_error());  

		//检查是否存在评价
		$num = mysql_num_rows($result);
		if($num == 0){
			echo "产品评价不存在!";
			mysql_close($link);
			return;
		}
		
		while ($row = mysql_fetch_object($result)) {
			$discuss = $row->discuss;
			$batchcode = $row->batchcode;
			$name = $row->name;
		}
	}else{
		echo "评价编号不正确"; 
		return;
	}
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../common/css_V6.0/content<?php echo $theme; ?>.css">
<script type="text/javascript" src="../js/tis.js"></script>
<script type="text/javascript" src="../js/WdatePicker.js"></script>
<script type="text/javascript" src="../common/js/jquery-2.1.0.min.js"></script>
<script type="text/javascript" src="../common/js/layer/layer.js"></script>
<script type="text/javascript" src="../common/js/jscolor.js" ></script>
<title>商家回复</title>
<meta http-equiv="content-type" content="text/html;charset=UTF-8">
</head>
<script>
function check(num){
	var check_num=/^[0-9]*$/.test(num);
	return check_num;
}	

 function submitV(a){
	if($(a).hasClass("disable")){
        return;
    }
	
	var description = document.getElementById("description").value;
	if(description==""){
	    alert('请输入商家回复!');
	   return;
	}
	
	$(a).addClass('disable').val("提交中...");
    document.getElementById("upform").submit();
 }

</script>
<style type="text/css">
.WSY_member textarea {
width: 350px;
height: 150px;
}
</style>
<body>
<div class="div_new_content">
<form action="discuss_reply_save.php?op=save&customer_id=<?php echo $customer_id ?>" method="post" id="upform" name="upform">
	<input type="hidden" name="keyid" value="<?php echo $keyid ?>" />
    <div class="WSY_content">
		<div class="WSY_columnbox">
			<div class="WSY_column_header">
				<div class="WSY_columnnav">
					<a class="white1">产品评价回复</a>
				</div>
			</div>

			<div class="WSY_data">
				<dl class="WSY_member">
					<dt>产品名称</dt>
					<dd><input type="text" value="<?php echo $name; ?>" disabled="disabled" name="name" id="name" style="width:250px;" /></dd>
				</dl>								

				<dl class="WSY_member">
					<dt>订单号</dt>
					<dd><input type="text" value="<?php echo $batchcode; ?>" disabled="disabled" name="batchcode" id="batchcode" style="width:250px;" /></dd>
				</dl>

				<dl class="WSY_member">
					<dt>顾客评价</dt>
					<dd><input type="text" value="<?php echo $discuss; ?>" disabled="disabled" name="discuss" id="discuss" style="width:250px;" /></dd>
				</dl>
				
				<dl class="WSY_member">
					<dt>商家回复</dt>
					<dd><textarea id="description"  name="description"><?php echo $description; ?></textarea></dd>
				</dl>								
				
				<div class="WSY_text_input01">
					<div class="WSY_text_input"><input type="button" class="WSY_button" value="提交" onclick="submitV(this);" style="cursor:pointer;"/></div>
					<div class="WSY_text_input"><input type="button" class="WSY_button" value="取消" onclick="javascript:history.go(-1);" style="cursor:pointer;"/></div>
				</div>
				
			</div>
		</div>
	</div>
 </form>

<div style="width:100%;height:20px;">
</div>
</div>	
</body>
<?php mysql_close($link);?>	
</html>