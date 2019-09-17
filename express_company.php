<?php
header("Content-type: text/html; charset=utf-8"); 
require('../../../config.php');
require('../../../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../back_init.php');
require('../../../common/utility.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../proxy_info.php');
mysql_query("SET NAMES UTF8");

$search_keyword="";   
if(!empty($_GET["search_keyword"])){
	$search_keyword = $_GET["search_keyword"];
};

$pagenum = 1;					
if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}
$start = ($pagenum-1) * 20;
$end =20;
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme;?>.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../Base/personalization/custom/css/per-style.css">
<script type="text/javascript" src="../../../common/js/jquery-2.1.0.min.js"></script>
<script type="text/javascript" src="../../../common/js/layer/V2_1/layer.js"></script>

</head>

<body>
<!--内容框架开始-->
<div class="WSY_content" id="WSY_content_height" style="z-index:1">
<!--微商城统计代码结束-->

<style type="text/css">
/*蓝色*/
.input_butn{margin-top:30%}
.input_butn input{display:block;width:192px;background:#06a7e1;border:solid 1px #0b91c2;height:32px;line-height:30px;border-radius:3px;font-size:14px;color:#fff;}
.input_butn input:hover{background:#017ca9;cursor:pointer;}
.input_butn01 input{width:268px;}
.leftA01 .leftA01_dl dd .tj{background:#07a7e1;border:solid 1px #0b91c2;color:#fff;}
.leftA01 .leftA01_dl dd .tj:hover{background:#0b91c2;}
.WSY_homeright .WSY_homeright_nav li .blueAA{background:#06a7e1;color:#fff;}

body{background: #e4e4e4;}
a:hover{text-decoration: none;}   
.button_blue{margin-left: 17px;font-size: 14px;display: block;line-height: 30px;background-color: #06a7e1;padding-left: 15px;padding-right: 15px;border-radius: 3px 3px 3px 3px;margin-top:15px;color: #fff;}
.button_blue:hover{background:#0e98c9;}
.WSY_righticon .WSY_inputicon input{margin-top:0px}
</style>
       <!--列表内容大框开始-->
	<div class="WSY_columnbox">
    	<!--列表头部切换开始-->
    	<?php
			$header = 3;
			include("head.php"); 
		?>
        <!--列表头部切换结束-->
         
    <!--首页设置代码开始-->
<div class="main">
	
	<a style="margin-top: 0px;float: right;" href="express_company_add.php?customer_id=<?php echo passport_encrypt((string)$customer_id);?>&action=add"><button class="btn-green mt15 newadd diy_btn" >新建物流公司</button></a>
	
	<div style="float: right;" class="search-box">
		<input class="search-text" type="text" placeholder="物流公司名称" value="<?php echo $search_keyword;?>" id="search_keyword">
		<button onClick="search();" class="diy_btn">搜索</button>	
	</div>
	<div style="clear: both;"></div>
	
	
	<div class="content-box">
		<table>
			<colgroup>
				<col width="2%">
				<col width="30%">
				<col width="13%">
				<col width="25%">
			</colgroup>
			<thead class="WSY_table_header">
				<tr>
					<th style="padding:10px 8px;"><input type="checkbox" id="checkAllChange" /></th>
					<th>物流公司名称</th>
					<th>创建时间</th>
					<th>操作</th>
				</tr>
			</thead>
			<tbody>
				<?php
				    //运费模板信息 
					$query_temid="SELECT id,expresses_name,is_default,createtime FROM weixin_expresses_company where isvalid=true and customer_id=".$customer_id." and supply_id=-1";
					if($search_keyword){
						$query_temid = $query_temid."  and expresses_name like '%".$search_keyword."%'";
					}
					$query_count = $query_temid;
					$query_temid = $query_temid." /*group by is_default*/ order by id asc limit ".$start.",".$end."";
					//echo $query_temid;
					$result_query_temid=mysql_query($query_temid) or die ('query_temid faild' .mysql_error());
					$result_count=mysql_query($query_count) or die ('query_count faild' .mysql_error());
					
					//分页
					$wcount =0;
					$page   =0;
					$wcount = mysql_num_rows($result_count);
					$page=ceil($wcount/$end);
					
					$tem_id       = -1; //主键
					$title        = ""; //标题
					$isused       =  0; //是否默认
					$createtime   = "";
					while($row=mysql_fetch_object($result_query_temid)){
						$tem_id  = $row->id;
						$isused     = $row->is_default;
						$createtime   = $row->createtime;
						$title      = $row->expresses_name;
				?>
				<tr >
					<td><input type="checkbox" class="temid" value="<?php echo $tem_id;?>" isused=<?php echo $isused?>></td>
					<td><span ondblclick="//ShowElement(this)" class="custom_name"><?php echo $title;?></span></td>
					<td><?php echo $createtime;?></td>
					
					<td style="border-right: none;">
						<a style="margin-right: 8px;" href="express_company_add.php?customer_id=<?php echo passport_encrypt((string)$customer_id);?>&tem_id=<?php echo $tem_id;?>&title=<?php echo $title;?>&op=update">
						<button title="编辑" style="background: none;border: none;" class="btn-white"><img width="20" height="20" src="../../../common/images_V6.0/operating_icon/icon05.png"/></button></a>
						
						<button title="删除" style="background: none;border: none;margin-right: 8px;" class="btn-white" onclick="temp_delete(<?php echo $tem_id;?>)"><img width="20" height="20" src="../../../common/images_V6.0/operating_icon/icon04.png"/></button>
					
						<?php if($isused){?>
						<button title="物流公司已启用" style="background: none;border: none;margin-right: 8px;" class="btn-green diy_btn" onclick="temp_cancel(<?php echo $tem_id;?>)"><img width="20" height="20" src="../../../common/images_V6.0/operating_icon/icon74.png"/></button>					
						<?php }else{?>
						<button title="启用此物流公司" style="background: none;border: none;margin-right: 8px;" class="btn-white " onclick="temp_check(<?php echo $tem_id;?>)"><img width="20" height="20" src="../../../common/images_V6.0/operating_icon/icon75.png"/></button>
						<?php }?>
					</td>
					
				</tr>
				<?php 
					}  //循环结束
				?>
			</tbody>
				
		</table>
	</div>
	<div style="display: none;" class="btn-box">
		<input type="button" id="checkAll" class="select selectshort diy_btn" value="全选" />
		<input type="button" id="reverse" class="select selectshort diy_btn" value="反选" />
				<input type="button" id="removeAll" class="select selectlong diy_btn" value="取消全部" /> 
		<input type="button" value="批量删除" id="delAll" class="select selectlong diy_btn">		
	</div>
	<!--翻页开始-->
		<div class="WSY_page">
			<ul class="WSY_pageleft" style="width:100%;margin-top:5px;">
				<?php 	if($wcount>0){ 
					for($i=1;$i<=$page;$i++){
				?>
					<li <?php if($i==$pagenum){ ?> class="one" <?php } ?> onClick="gopage(this)" value="<?php echo $i; ?>"><?php echo $i; ?></li>
				<?php }} ?>	
			<?php if($wcount>0){ ?>
			<form class="WSY_searchbox">
				<input class="WSY_page_search" name="WSY_jump_page" id="WSY_jump_page" value="">
				<input class="WSY_jump" type="button" value="跳转" onClick="jumppage()" style="border:none">
			</form>
			<?php } ?>
			</ul>
			
		</div>
	<!--翻页结束-->
</div>

<script>
$(function(){
	/* $('.custom_name').change(function(){//修改名称
		var name=$(this).children().val();
		var id=$(this).parent().prev().find('.temid').val();
		changename(id,name);
	}); */
	$("#checkAllChange").click(function() { // 全选/取消全部 
		if (this.checked == true) { 
			$(".temid").each(function() { 
			this.checked = true; 
			}); 
		} else { 
			$(".temid").each(function() { 
			this.checked = false; 
			}); 
		} 
	}); 

	$("#checkAll").click(function() { // 全选 
	var sum_isused = 0;
		$(".temid").each(function() { 
			
			var isused = $(this).attr('isused');	
			
			if(isused ==1 ){
				sum_isused++;
			}
			this.checked = true; 
			
		}); 
			if(sum_isused>0){
				alert('警告：你已经选择已经启用的物流公司！');
			}
	}); 
	 
	$("#removeAll").click(function() { // 取消全部
		$(".temid").each(function() { 
			this.checked = false; 
		}); 
	}); 
		
	$("#reverse").click(function() { // 反选 
		$(".temid").each(function() { 
			if (this.checked == true) { 
				this.checked = false; 
			} else { 
				this.checked = true; 
			} 
		}) 
	}); 
	//批量删除 
	$("#delAll").click(function() {
		
		layer.confirm('确定要删除吗？', {
			title: false,
			skin:'red-skin',
			shift:6,
  			btn: ['删除','取消'] //按钮
		}, function(index){
	  		var arrtemid = new Array();
			var temidarr="";
			$(".temid").each(function(i) { 
				if (this.checked == true) { 
				//	arrtemid[i] = $(this).val(); 
					temidarr+=$(this).val()+",";
				} 
			});
			console.log(temidarr);
			$.ajax({  
				type : "POST",  
				url : "express_company.class.php?op=deleteall",
				data : {"temidarr" : temidarr},
				dataType: "json",		
				success : function(result) {
					console.log(result);
					if(result.code=="1"){
						window.location.reload(); 
					}
				}
			});
			layer.close(index);
		}, function(){
  			
		});
	}); 
}); 

function changename(tem_id,name){  //修改模板名字
	var option="changename";
	var customer_id=<?php echo $customer_id;?>;
	$.ajax({  
		type : "POST",  
		url : "express_company.class.php",
		data : {"option" : option,"customer_id" : customer_id,"tem_id" : tem_id,"name" : name},
		dataType: "json",		
		success : function(result) {
			console.log(result.msg);
		}
		
	});
	
}

function temp_delete(tem_id){ //删除模板
		layer.confirm('确定要删除吗？', {
			title: false,
			skin:'red-skin',
			shift:6,
  			btn: ['删除','取消'] //按钮
		}, function(){
				
			$.ajax({  
				type : "POST",  
				url : "express_company.class.php?op=del",
				data : {"tem_id" : tem_id},
				dataType: "json",		
				success : function(result) {
					if(result.code=="1"){
						window.location.reload(); 
					}
				}
				
			});
		});
}		
function ShowElement(element) //双击可编辑
{
	var oldhtml = element.innerHTML;
	var newobj = document.createElement('input');
	//创建新的input元素
	newobj.type = 'text';
	newobj.value=oldhtml;
	//为新增元素添加类型
	newobj.onblur = function(){
		element.innerHTML = this.value ? this.value : oldhtml;
		//当触发时判断新增元素值是否为空，为空则不修改，并返回原有值 
	}
	element.innerHTML = '';
	element.appendChild(newobj);
	newobj.focus();
}
function temp_check(tem_id){ //选择模板
	layer.confirm('只能选择一个物流公司，确定选择此物流公司吗？', {
			title: false,
			skin:'red-skin',
			//shift:6,
  			btn: ['确认','取消'] //按钮
		}, function(){
	
			$.ajax({  
				type : "POST",  
				url : "express_company.class.php?op=express_check",	
				data : {"tem_id" : tem_id},
				dataType: "json",		
				success : function(result) {
					if(result.code=="1"){
						window.location.reload(); 
					}
				}
				
			});
		});
}

function temp_cancel(tem_id){ //停用模板
	var option="temp_cancel";
	$.ajax({  
		type : "POST",  
		url : "express_company.class.php",
		data : {"option" : option,"tem_id" : tem_id},
		dataType: "json",		
		success : function(result) {
			if(result.code=="1"){
				window.location.reload(); 
			}
		}
		
	});
	
}
var pagenum = <?php echo $pagenum ?>;
var page = <?php echo $page ?>;
function prePage(){
	pagenum--;
	document.location= "express_company.php?pagenum="+pagenum+"&customer_id=<?php echo passport_encrypt((string)$customer_id); ?>";
}
  
function nextPage(){
	pagenum++;
	document.location= "express_company.php?pagenum="+pagenum+"&customer_id=<?php echo passport_encrypt((string)$customer_id); ?>";
}
function search(){
	pagenum = 1;
	var search_keyword = document.getElementById("search_keyword").value;
	document.location= "express_company.php?pagenum="+pagenum
	+"&search_keyword="+search_keyword+"&customer_id=<?php echo passport_encrypt((string)$customer_id); ?>";

}
function gopage(v){
	var a=$(v);
	if(a.hasClass('one')){
		return false;
	}else{
		document.location= "express_company.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&pagenum="+a.val();
	}
}
function jumppage(){
	var a=parseInt($("#WSY_jump_page").val());
	if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
		return false;
	}else{
		document.location= "express_company.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&pagenum="+a;
	}
}

</script>
<!--选择链接的JS结束-->
</body>
</html>  
<?php 

mysql_close($link);
?>