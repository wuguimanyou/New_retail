<?php
header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../back_init.php');
$link =    mysql_connect(DB_HOST,DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
mysql_query("SET NAMES UTF8");
require('../../../proxy_info.php');
$pagenum = 1;
if(!empty($_GET["pagenum"])){
   $pagenum = $_GET["pagenum"];
}
$start = ($pagenum-1) * 20;
$end = 20;
$query = 'SELECT id,name,price,is_include,region,cost,expressCode FROM weixin_expresses where isvalid=true and customer_id='.$customer_id;
$query_num='select count(1) as rcount from weixin_expresses where isvalid=true and customer_id='.$customer_id;
$result_num=mysql_query($query_num)or die('Query failed'.mysql_error());
while($row=mysql_fetch_object($result_num)){
	$wcount = $row->rcount;
}
$query = $query." order by id desc"." limit ".$start.",".$end;
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
?>
<html>  
<head>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link rel="stylesheet" type="text/css" href="../../Common/css/Distribution/d-style.css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/css2.css" media="all">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/icon.css" media="all">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/inside.css" media="all">
<script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="../../../common/js/inside.js"></script>
<script type="text/javascript" src="../../../js/tis.js"></script>
<script type="text/javascript" src="../../../js/WdatePicker.js"></script>
<script type="text/javascript" src="../../../common/js/layer/layer.js"></script>
<meta http-equiv="content-type" content="text/html;charset=UTF-8">
<style type="text/css">
body{background: #e4e4e4;}
a:hover{text-decoration: none;}   
.button_blue{margin-left: 17px;font-size: 14px;display: block;line-height: 30px;background-color: #06a7e1;padding-left: 15px;padding-right: 15px;border-radius: 3px 3px 3px 3px;margin-top:15px;color: #fff;}
.button_blue:hover{background:#0e98c9;}
.WSY_righticon .WSY_inputicon input{margin-top:0px}
</style>
</head>
<body> 
<div >  
    <div class="WSY_content">
		<div class="WSY_columnbox">
			<?php 
			$header = 1;
			include("head.php");
			
			?>
			
			<div class="table-box" style="padding:0px 20px">
				<div class="WSY_list">
					<li class="WSY_left"><a>运费列表</a></li>
					<ul class="WSY_righticon">
						<?php
						//本地测试，韦工 2016.04.14
						if($_SERVER['HTTP_HOST'] == 'a.com:8012'){$http_host = $_SERVER['HTTP_HOST'];}
						?>
						<li class="WSY_inputicon"><input type="button" value="添加运费规则" onClick="gweiUrl2('addexpress.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>',1,'http://xmy.xmtzxm.net.cn/weixinpl/back_newshops/Distribution/express/')" class="button_blue" style="cursor:hand"></li>
					</ul>
				</div>
				<table width="97%" class="WSY_table WSY_t2" id="WSY_t1">
				<thead class="WSY_table_header">
				<tr>  
				<th width="5%" height="30" align="center" >ID</th>  
				<th width="15%" height="30" align="center" >名称</th>
				<th width="15%" height="30" align="center" >运费(元)</th>
				<th width="15%" height="30" align="center" >使用所需费用(元)</th>
				<th width="15%" height="30" align="center" >区域模式</th>
				<th width="15%" height="30" align="center" >区域选择</th>
				<th width="20%" height="30" align="center" >操作</th>
				</tr>
				</thead>
					<form name="form1" method="post">  
					<?php
					   $keyid = -1;
					   $name = "";
					   $is_include = 0;
					   $price = 0;
					   $region = "";
					   $cost = 0;
					   $expressCode ="";
					   while ($row = mysql_fetch_object($result)) {
							$keyid =  $row->id ;
							$name = $row->name;
							$price = $row->price;   
							if(empty($price)){
							   $price="免运费";
							}
							$is_include = $row->is_include;
							if($is_include==0){
								$region_str = "区域之内";
							}else{
								$region_str = "区域之外";
							}
							$region = $row->region;
							if(empty($region)){
								$region_str = "";
							}
							
							$cost = $row->cost;
							$expressCode = $row->expressCode;
							
					?>
					<tr  onMouseOver="this.style.backgroundColor='#e4f1fc'" onMouseOut="this.style.backgroundColor=''">
					<td ><?php echo $keyid ?></td>
					<td ><?php echo $name; ?></td>
					<td ><?php echo $price; ?></td>
					<td ><?php echo $cost; ?></td>
					<td ><?php echo $region_str; ?></td>
					<td ><?php echo $region; ?></td>	
					<td >
					<a href="addexpress.php?keyid=<?php echo $keyid ?>&customer_id=<?php echo passport_encrypt((string)$customer_id) ?>" style="cursor:pointer;" title="编辑"><span class="edit_icon"></span></a>&nbsp;
					<a  class="btn"  href="javascript: G.ui.tips.confirm('您确定删除吗？','addexpress.php?keyid=<?php echo $keyid ?>&del=isok&customer_id=<?php echo passport_encrypt((string)$customer_id) ?>');"  title="删除">
					<span class="remove_icon"></span>
					</a>
					</td>


					</tr>
					<?php
					  
					}

					mysql_close($link);
					?>
					</form>
				</table>
			</div>
			<br/>
			<!--翻页开始-->
			<div class="WSY_page">
				
			</div>
			<!--翻页结束-->     
		</div>
	</div>
</div>


<script src="../../../js/fenye/jquery.page1.js"></script>
<script type="text/javascript" background="#ffffff"> 
var pagenum = <?php echo $pagenum ?>;
var rcount_q2 = <?php echo $wcount ?>;
var end = <?php echo $end ?>;
var count =Math.ceil(rcount_q2/end);//总页数
var page = count;
var customer_id_en= "<?php echo $customer_id_en; ?>";
//pageCount：总页数
//current：当前页
$(".WSY_page").createPage({
	pageCount:count,
	current:pagenum,
	backFn:function(p){
		document.location= "express.php?pagenum="+p+"&customer_id="+customer_id_en;
	}
});
function jumppage(){
	var a=parseInt($("#WSY_jump_page").val());  
	if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
		return false;
	}else{
		document.location= "express.php?pagenum="+p+"&customer_id="+customer_id_en;
	}
}

</script>




</body>
</html>