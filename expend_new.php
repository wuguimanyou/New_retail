<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require('../proxy_info.php');

mysql_query("SET NAMES UTF8");

$gold_id=-1;
if(!empty($_GET["gold_id"])){
   $uptype = $_GET["uptype"];
   $gold_id = $_GET["gold_id"];
   switch($uptype){
       case 1:
	     $gold_name = $_POST["gold_name"];
	     $sql="update exprend_golders set name='".$gold_name."' where id=".$gold_id;
	     mysql_query($sql);
	     break;
	   case 2:
	     $discount = $_POST["discount"];
	     $require_money = $_POST["require_money"];
		 $isauto = $_POST["isauto"];
	     $sql="update exprend_golders set discount=".$discount.",require_money=".$require_money." where id=".$gold_id;
		 mysql_query($sql);
		 $error=mysql_error();
		 break;
	   case 3:
	  
	     $qudao_names = $_POST["qudao_names"];
		 $qudao_discounts = $_POST["qudao_discounts"];
		 $qudaonamearr = explode("_",$qudao_names);
		 $qudaodiscountarr = explode("_",$qudao_discounts);
		 $icount = count($qudaonamearr);

		 if($icount>0){
		     $sql="update exprend_qudaos set isvalid=false where customer_id=".$customer_id;
			 mysql_query($sql);
		 }
		 for($i=0;$i<$icount;$i++){
		    $qudao_name = $qudaonamearr[$i];
			$qudao_discount = $qudaodiscountarr[$i];
			$sql="insert into exprend_qudaos (name,discount,isvalid,createtime,customer_id) values('".$qudao_name."',".$qudao_discount.",true,now(),".$customer_id.")";
			mysql_query($sql);
		 }
	     break;
   }
}

$require_money = 150;
$gold_id=-1;
$gold_name="金牌用户";
$gold_discount = 0;
$gold_level=1;
$query3="select id,name,discount,require_money,level from exprend_golders where isvalid=true and customer_id=".$customer_id;
$result3 = mysql_query($query3) or die('Query failed: ' . mysql_error());
while ($row3 = mysql_fetch_object($result3)) { 
    $gold_id = $row3->id;
	$require_money = $row3->require_money;
	$gold_name = $row3->name;
	$gold_discount = $row3->discount;
	$gold_level = $row3->level;
	break;
}
if($gold_id<0){
    $sql="insert into exprend_golders(require_money,name,discount,level,isvalid,createtime,customer_id) values(150,'金牌用户',0,1,true,now(),".$customer_id.")";
	 mysql_query($sql);
	$gold_id = mysql_insert_id();
}

?>
<!DOCTYPE HTML>
<!DOCTYPE html PUBLIC "" ""><HTML><HEAD><META content="IE=11.0000" 
http-equiv="X-UA-Compatible">
   <TITLE>E扫盈</TITLE>   
<META http-equiv="Content-Type" content="text/html; charset=utf-8"><LINK href="css/admin-041f1b37f7b1a30902403cd98fc02642.css" 
rel="stylesheet" media="all">   
   
<META name="csrf-param" content="authenticity_token"> 
<META name="csrf-token" content="qZIM51uLRuwHAP3DFIE447WW1NQWaUaTJRI9b00LsDg="> 
<META name="GENERATOR" content="MSHTML 11.00.9600.17344"></HEAD> 
<BODY><!-- Fixed navbar --> 

<DIV class="container" id="main" style="margin-top:0px;">

<DIV id="content"><LINK href="css/index-90ca27ee04362d1772fac7c68366c8f0.css" 
rel="stylesheet" media="screen"> 

 
<DIV id="settings-page">
<DIV class="page-header">
<H1>推广设置</H1></DIV><!-- Nav tabs -->   
<UL class="nav nav-tabs" id="settings" style="margin-bottom: 10px;">
  <LI><A href="javascript:choose(1);" 
  data-toggle="tab">角色设定</A></LI>
  <LI><A href="javascript:choose(2);" data-toggle="tab"><?php echo $gold_name; ?></A></LI>
  <LI><A href="javascript:choose(3);" 
  data-toggle="tab">推广渠道设定</A></LI></UL><!-- Tab panes -->   
<DIV>

<DIV class="tab-pane" id="roles">


       	       
<DIV class="ui-box message-box">
<H1>金牌用户：</H1>
<H2>身份获得</H2>
<DIV>
<UL>
  <LI>商家指定或符合条件自动升级</LI></UL></DIV>
<H2>权利</H2>
<DIV>
<UL>
  <LI>通过微信推广微商城可获得佣金</LI>
  <LI>可分配专属推广二维码</LI></UL></DIV>
<H2>金牌用户名称自定义：</H2>
<DIV class="form-group row required site_agent_role" 
style="margin-top: 10px;">
<LABEL class="string required col-sm-2 control-label" 
for="site_agent_role" html='{:style=>"text-align:left;"}'>
<ABBR title="必填的">*</ABBR> 金牌用户
</LABEL>	           
<FORM class="simple_form form-horizontal" id="edit_site_59" role="form" action="expend_new.php?gold_id=<?php echo $gold_id; ?>&uptype=1&customer_id=<?php echo $customer_id_en; ?>" 
  method="post" accept-charset="UTF-8" novalidate="novalidate">
	<DIV class="col-sm-7">
	  <INPUT name="gold_name" class="string required form-control" 
	    id="site_agent_role" type="text" value="<?php echo $gold_name; ?>">
	</DIV>
	<DIV class="col-sm-3 comment">
	    <INPUT name="commit" class="btn btn-default btn btn-primary" type="submit" value="更新">
	</DIV>
</form>
</DIV>

<H2>佣金计算</H2>
<DIV>
<UL>
  <LI>每笔交易佣金=商品折后价×商品<?php echo $gold_name; ?>佣金比例×<?php echo $gold_name; ?>分配比例；</LI>
  <LI>【商品折后价】和【商品<?php echo $gold_name; ?>佣金比例】在商品编辑中设定；</LI>
  <LI>【<?php echo $gold_name; ?>分配比例】在<A class="tab-change" href="javascript:choose(2);"
  data-tab="#individual"><?php echo $gold_name; ?></A>中确定。</LI></UL></DIV></DIV>
<DIV class="ui-box message-box">
<H1>推广渠道：</H1>
<H2>身份获得</H2>
<DIV>
<UL>
  <LI>1. 用户申请</LI> 
  <LI>2. 商家审核</LI>
  
  </UL></DIV>
<H2>权利</H2>
<DIV>
<UL>
  <LI>通过微信推广微商城可获得佣金</LI>
  <LI>可分配专属推广二维码</LI></UL></DIV>

<H2>佣金计算</H2>
<DIV>
<UL>
  <LI>每笔交易佣金=商品折后价×商品推广渠道佣金比例×各推广渠道类型的分配比例；</LI>
  <LI>【商品折后价】和【商品推广渠道佣金比例】在商品编辑中设定；</LI>
  <LI>商家可以设定多个类型的【推广渠道】，不同类型的推广渠道享受不同的销售返点；</LI>
  <LI>【推广渠道分配比例】在<A class="tab-change" href="javascript:choose(3);"
data-tab="#sales">推广渠道设定</A>中确定。</LI></UL></DIV></DIV>

</DIV>

<DIV class="tab-pane" id="individual" style="display:none">
<DIV class="ui-box upgrade-box">
<H1><?php echo $gold_name; ?>升级条件</H1>
<DIV>
<FORM class="simple_form edit_site" id="edit_site_59" action="expend_new.php?customer_id=<?php echo $customer_id_en; ?>&gold_id=<?php echo $gold_id; ?>&uptype=2" 
method="post" accept-charset="UTF-8" novalidate="novalidate">

<SECTION>
<INPUT name="site[enable_auto_upgrade]" type="hidden" value="0"><LABEL>
<INPUT 
name="isauto" class="boolean optional" id="enable_auto_upgrade" 
type="checkbox" checked="checked" value="1"></LABEL>		         
<SPAN>启用自动升级。【普通用户】累计消费金额（必须是已支付或交易成功的订单）达到</SPAN>
		         
				 
		         <SPAN>元时自动升级为【<?php echo $gold_name; ?>】（即时生效）</SPAN>,<span>享受分销奖励:</span>
				 <INPUT name="discount" class="string optional" id="discount" type="text" value="<?php echo $discount; ?>" style="margin-left:10px;height:80%;margin-top:5px;">(0~1)		       </SECTION>
<SECTION>
  <BUTTON class="btn btn-sm btn-default btn-save">保存</BUTTON>
</SECTION></FORM>
</DIV></DIV>

</DIV>
<DIV class="tab-pane" id="sales" style="display:none">
<DIV class="ui-box sales-fee-settings-box">
<H1>推广渠道分配比例</H1>
<DIV class="btn-group">
<BUTTON 
class="btn btn-default btn-sm btn-add-row" onclick="addTui();">增加类型</BUTTON>           <BUTTON 
class="btn btn-danger btn-sm btn-del-row" onclick="subTui();">删除类型</BUTTON>         
</DIV>
 <style>
 .sales-table-box_title{
    width:98%;
	margin:0 auto;
	height:30px;
	font-weight:bold;
	line-height:30px;
	text-align:left;
	font-size:12px;
 }
 .sales-table-box_title_l{
     float:left;
	 width:50%;
	 height:100%;
	 padding-left:5px;
 }
 
 .sales-table-box_item{
    width:100%;
	margin:0 auto;
	height:30px;
	line-height:30px;
	text-align:left;
	border-top:1px solid #e8e8e8;
	font-size:12px;
	padding-left:5px;
 }
 
 </style>
 <DIV class="sales-table-box" id="sales-table-box">
   <div class="sales-table-box_title">
      <div class="sales-table-box_title_l">类型</div>
	  <div class="sales-table-box_title_l">比例(0~1)</div>
   </div>
   <?php 
     $query3="select name,discount from exprend_qudaos where isvalid=true and customer_id=".$customer_id;

	 $curr=0;
	 $result3 = mysql_query($query3) or die('Query failed: ' . mysql_error());
     while ($row3 = mysql_fetch_object($result3)) { 
	    $curr++;
	    $name_1 = $row3->name;
		$discount_1 = $row3->discount;
   ?>
   <div class="sales-table-box_item" id="div_tui_1">
      <div class="sales-table-box_title_l"><input type=text value="<?php echo $name_1; ?>" id="qudao_name_1" name="qudao_name" style="width:100px;height:95%;margin-top:2px;" /></div>
	  <div class="sales-table-box_title_l"><input type=text value="<?php echo $discount_1; ?>" id="qudao_discount_1" name="qudao_discount" style="width:100px;height:95%;margin-top:2px;" /></div>
   </div>
   <?php 
     
   } ?>

 
 </DIV>
 <div style="margin-top:5px;margin-left:250px;text-align:right;">
 <BUTTON class="btn btn-sm btn-default btn-save" onclick="save_qudao();">保存</BUTTON>
 </div>
</DIV>
</DIV>

</DIV></DIV></DIV></DIV><!-- /container --> 

<form action="expend_new.php?uptype=3&gold_id=<?php echo $gold_id; ?>" method="post" id="frm_qudao">
  
      <input type=hidden name="qudao_names" id="qudao_names" value="" />
	  <input type=hidden name="qudao_discounts" id="qudao_discounts" value="" />
	  
  
</form>

<script>
var curr = <?php echo $curr; ?>;
function addTui(){
   curr++;
   var v = document.getElementById("sales-table-box");
   var str= "<div class=\"sales-table-box_item\" id=\"div_tui_"+curr+"\"><div class=\"sales-table-box_title_l\"><input type=text value=\"渠道\" namde=\"qudao_name\" id=\"qudao_name_"+curr+"\" style=\"width:100px;height:95%;margin-top:2px;\" /></div><div class=\"sales-table-box_title_l\"><input type=text value=\"0\" name=\"qudao_discount\" id=\"qudao_discount_"+curr+"\" style=\"width:100px;height:95%;margin-top:2px;\" /></div></div>";
   var iv = v.innerHTML;
   iv = iv + str;
   v.innerHTML = iv;
}
function subTui(){
   var v = document.getElementById("div_tui_"+curr);
   v.style.display="none";
   curr--;
} 

function choose(t){
   document.getElementById("individual").style.display="none";
   document.getElementById("roles").style.display="none";
   document.getElementById("sales").style.display="none";
   switch(t){
      case 1:
	     document.getElementById("roles").style.display="block";
	     break;
	  case 2:
	    document.getElementById("individual").style.display="block";
	     break;
      case 3:
	     document.getElementById("sales").style.display="block";
	     break;
		 
   }
}
function save_qudao(){
  
   var qudao_names= "";
   var qudao_discount="";
   for(i=1;i<curr+1;i++){
      var v= document.getElementById("qudao_name_"+i).value;
	  var v2= document.getElementById("qudao_discount_"+i).value;
	  if(qudao_names==""){
	     qudao_names = qudao_names + v;
		 qudao_discount = qudao_discount + v2;
	  }else{
	     qudao_names = qudao_names+"_"+v;
		 qudao_discount = qudao_discount+"_"+v2;
	  }
   }
   document.getElementById("qudao_names").value=qudao_names;
   document.getElementById("qudao_discounts").value=qudao_discount;
   document.getElementById("frm_qudao").submit();
   
}
</script>
<?php 

mysql_close($link);
?>
</BODY></HTML>
