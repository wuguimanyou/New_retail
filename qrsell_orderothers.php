<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../proxy_info.php');

mysql_query("SET NAMES UTF8");
$qrsell_orderothers = ""; //推广员申请自定义字段
$query = "select qrsell_orderothers from weixin_commonshops where isvalid=true and customer_id=".$customer_id." limit 0,1";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
    $qrsell_orderothers=$row->qrsell_orderothers;
}
?>
<!DOCTYPE html>

<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<title></title>
<link href="css/global.css" rel="stylesheet" type="text/css">
<link href="css/main.css" rel="stylesheet" type="text/css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../css/icon.css" media="all">
<script type="text/javascript" src="../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="js/global.js"></script>
<script type="text/javascript" src="../common/js/jquery.blockUI.js"></script>
<script charset="utf-8" src="../common/js/jquery.jsonp-2.2.0.js"></script>
<script type="text/javascript" src="js/base_qrsell_custom.js"></script>	

</head>

<body>

<style type="text/css">body, html{background:url(images/main-bg.jpg) left top fixed no-repeat;}</style>
<style>
	.diy_one{
	width:80%;height:auto;
	border: 1px solid #dddddd;
	font-size:12px;
	}
	.diy_one_one{
	width:100%;
	margin:0 auto;
	height:30px;
	line-height:30px;
	background:#f4f4f4;
	color:#333333;
	font-weight:bold;
	font-size:12px;
	}
	.diy_one_one_l{
	width:15%;
	height:100%;
	float:left;
	border-right: 1px solid #dddddd;
	padding-left:2px;
	font-size:12px;
	}
	.diy_one_one_m{
	width:32%;
	height:100%;
	float:left;
	border-right: 1px solid #dddddd;
	padding-left:2px;
	font-size:12px;
	}
	.diy_one_one_r{
	width:12%;
	height:100%;
	float:left;
	padding-left:2px;
	font-size:12px;
	}

	.diy_one_two{
	width:100%;
	margin:0 auto;
	height:40px;
	line-height:40px;
	color:#333333;
	border-bottom: 1px solid #dddddd;
	font-size:12px;
	}
	</style>
<div id="iframe_page">
		<div class="orders r_con_wrap r_con_config">
		<form id="config_form" action="save_qrsell_orderothers.php?customer_id=<?php echo $customer_id_en; ?>" method="post" enctype="multipart/form-data">
			<h4>推广员申请自定义字段</h4>
			<span class="input">	
				<div class="diy_one" id="diy_one">
					<div class="diy_one_one">
					<div class="diy_one_one_l">字段类型</div>
					<div class="diy_one_one_m">字段名称</div>
					<div class="diy_one_one_m">初始内容</div>
					<div class="diy_one_one_r">操作</div>
					</div>
					<?php
					$otherarr = explode(",",$qrsell_orderothers);
					$len =  count($otherarr);
					$diy_num = $len;
					$is_1= 0;
					$is_2= 0;
					$is_3 = 0;
					for($i=0;$i<$len;$i++){
						$varr= $otherarr[$i];
						if(empty($varr)){
						continue;
					}
					$vlst = explode("_",$varr);

					$type = $vlst[0];
					if(empty($vlst[1])){
						continue;
					}
					$name = $vlst[1];
					$value = $vlst[2];
					switch((int)$type){
						case 1:
					?>
					<div class="diy_one_two" id="diy_item_<?php echo $i+1; ?>">
						<div class="diy_one_one_l">单行文字</div>
						<div class="diy_one_one_m">
							<input type=text name="singletext" id="singletext_<?php echo $diy_num; ?>" value="<?php echo $name; ?>" placeholder="请输入字段名"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_m">
							<input type=text name="singletext_con" id="singletext_con<?php echo $diy_num; ?>" value="<?php echo $value; ?>" placeholder="请输入初始内容"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_r">
						<a href="javascript:diy_del(<?php echo $i+1; ?>,1);">删除</a>&nbsp;
						<a href="javascript:diy_add(1);">添加</a>
						</div>
					</div>
					<?php    
						$is_1 = 1; 
						break;
						case 2:
					?>						  
					<div class="diy_one_two" id="diy_item_<?php echo $i+1; ?>">
						<div class="diy_one_one_l">日期选择</div>
						<div class="diy_one_one_m">
							<input type=text name="singledate" id="singledate_<?php echo $diy_num; ?>" value="<?php echo $name; ?>" placeholder="请输入字段名"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_m">
							<input type=text name="singledate_con" id="singledate_con<?php echo $diy_num; ?>" value="<?php echo $value; ?>" placeholder="请输入初始内容" style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_r">
							<a href="javascript:diy_del(<?php echo $i+1; ?>,2);">删除</a>&nbsp;
							<a href="javascript:diy_add(2);">添加</a>
						</div>
					</div>
					<?php    
					$is_2  =2;
					break;
					case 3:
					?>
					<div class="diy_one_two" id="diy_item_<?php echo $i+1; ?>">
						<div class="diy_one_one_l">下拉选择</div>
						<div class="diy_one_one_m">
							<input type=text name="singleselect" id="singleselect_<?php echo $diy_num; ?>" value="<?php echo $name; ?>" placeholder="自定义下拉框"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_m">
							<input type=text name="singleselect_con" id="singleselect_con<?php echo $diy_num; ?>" value="<?php echo $value; ?>" placeholder="选择1|选择2"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_r">
							<a href="javascript:diy_del(<?php echo $i+1; ?>,3);">删除</a>&nbsp;
							<a href="javascript:diy_add(3);">添加</a>
						</div>
					</div>

					<?php  
					$is_3 = 3;
					break;
						}
					} 
					if(empty($is_1)){ 
						$diy_num++;
					?>
					<div class="diy_one_two" id="diy_item_<?php echo $diy_num; ?>">
						<div class="diy_one_one_l">单行文字</div>
						<div class="diy_one_one_m">
							<input type=text name="singletext" value="" id="singletext_<?php echo $diy_num; ?>" placeholder="请输入字段名"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_m">
							<input type=text name="singletext_con" value="" id="singletext_con<?php echo $diy_num; ?>" placeholder="请输入初始内容"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_r">
							<a href="javascript:diy_del(<?php echo $i+1; ?>,1);">删除</a>&nbsp;
							<a href="javascript:diy_add(1);">添加</a>
						</div>
					</div>

					<?php
					}
					?>
					<?php 
					if(empty($is_2)){ 
						$diy_num++;
					?>
					<div class="diy_one_two" id="diy_item_<?php echo $diy_num; ?>">
						<div class="diy_one_one_l">日期选择</div>
						<div class="diy_one_one_m">
							<input type=text name="singledate" value="" id="singledate_<?php echo $diy_num; ?>" placeholder="请输入字段名"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_m">
							<input type=text name="singledate_con" value="" id="singledate_con<?php echo $diy_num; ?>" placeholder="请输入初始内容"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_r">
							<a href="javascript:diy_del(<?php echo $diy_num; ?>,2);">删除</a>&nbsp;
							<a href="javascript:diy_add(2);">添加</a>
						</div>
					</div>

					<?php
					}
					?>

					<?php if(empty($is_3)){ 
					$diy_num++;
					?>
					<div class="diy_one_two" id="diy_item_<?php echo $diy_num; ?>">
						<div class="diy_one_one_l">下拉选择</div>
						<div class="diy_one_one_m">
							<input type=text name="singleselect" id="singleselect_<?php echo $diy_num; ?>" value="" placeholder="自定义下拉框"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_m">
							<input type=text name="singleselect_con" id="singleselect_con<?php echo $diy_num; ?>" value="" placeholder="选择1|选择2"  style="margin-top:5px;" />
						</div>
						<div class="diy_one_one_r">
							<a href="javascript:diy_del(<?php echo $diy_num; ?>,3);">删除</a>&nbsp;
							<a href="javascript:diy_add(3);">添加</a>
						</div>
					</div>

					<?php
					}
					?>
					<input type=hidden name="qrsell_orderothers" id="qrsell_orderothers" value="<?php echo $qrsell_orderothers ?>" />
				</div>
			</span>
			<div class="clear"></div>
			<div class="submit" style="margin-top: 30px;"><input type="button" name="submit_button" value="提交保存" onclick="saveothers();"></div>
			</form>
		</div>
	
</div>
<script type="text/javascript">
var diy_num = <?php echo $diy_num; ?>;

function saveothers(){
		  var str = "";
	var singletext = document.getElementsByName("singletext");
	var singletext_con = document.getElementsByName("singletext_con");
	
	var len = singletext.length;
	for(i=0;i<len;i++){
	  
	    var v = singletext[i].value;
		var con = singletext_con[i].value;
		str = str +"1_"+v+"_"+con+",";
	}
	
	if(str!=""){
	   str = str.substring(0,str.length-1);
	}
	str = str + ",";
	
	var singledate = document.getElementsByName("singledate");
	var singledate_con = document.getElementsByName("singledate_con");
	len = singledate.length;
	for(i=0;i<len;i++){
	    var v = singledate[i].value;
		var con = singledate_con[i].value;
		str = str +"2_"+v+"_"+con+",";
	}
	if(str!=""){
	   str = str.substring(0,str.length-1);
	}
	str = str + ",";
	
	var singleselect = document.getElementsByName("singleselect");
	var singleselect_con = document.getElementsByName("singleselect_con");
	len = singleselect.length;
	for(i=0;i<len;i++){
	    var v = singleselect[i].value;
		var con = singleselect_con[i].value;
		str = str +"3_"+v+"_"+con+",";
	}
	
	if(str!=""){
	   str = str.substring(0,str.length-1);
	}
	//alert('str==========='+str);
	document.getElementById("qrsell_orderothers").value=str;
	 document.getElementById("config_form").submit();
}


</script>
<?php 

mysql_close($link);
?>
</body></html>