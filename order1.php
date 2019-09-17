<?php  
	header("Content-type: text/html; charset=utf-8"); 
	require('../../../config.php');   //配置
	require('../../../customer_id_decrypt.php');   //解密参数
	//require('../../../back_init.php');
	
	$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
	mysql_select_db(DB_NAME) or die('Could not select database');
	mysql_query("SET NAMES UTF8");
	
	require('../../../proxy_info.php');
	
	include_once("../../../log_.php");
	$log_ = new Log_();
	$log_name="notify_url_order".date("Y-m-d").".log";//log文件路径

	
	$isauto = 0;
	if(!empty($_GET["isauto"])){
	   $isauto = $configutil->splash_new($_GET["isauto"]);
	}

    //微信支付版本号
	$wxpay_version=1;
	$query_ver = "select version from weixinpays where isvalid=true and customer_id=".$customer_id." limit 1";
	$result_ver = mysql_query($query_ver) or die('Query_ver failed: ' . mysql_error());
	while ($row = mysql_fetch_object($result_ver)) {
		$wxpay_version = $row->version;
	}
	 //微信支付版本号 End


	/* 订单提醒按钮 */
	$is_remind = 0;
	$query_remind = "select order_remind from weixin_commonshop_orderremind where isvalid=true and customer_id=".$customer_id." limit 1";
	$result_remind = mysql_query($query_remind) or die('Query_is_remind failed: ' . mysql_error()); 
	while ($row_remind = mysql_fetch_object($result_remind)) {
	   $is_remind = $row_remind->order_remind;	   
	}	 
	/* 订单提醒按钮 End	*/
	

	/* 从右下角提醒图片进订单管理，更新订单支付总数 */

	if(!empty($_GET["up_order"])){
		$up_order = $configutil->splash_new($_GET["up_order"]);
		$ordercount=-1;
		$sql_ordercount="select count(1) as ordercount from weixin_commonshop_orders where customer_id=".$customer_id." and isvalid=true and paystatus=1";
		$re=mysql_query($sql_ordercount) or die('Query sql_ordercount: '.mysql_error());
		while ($ro = mysql_fetch_object($re)) {
			$ordercount= $ro->ordercount;
		}
		if($ordercount>0){
			$query="update weixin_commonshop_orderremind set order_count=".$ordercount.",last_record=".$ordercount." where isvalid=true and customer_id=".$customer_id;
			mysql_query($query) or die('Query failed3_weixin_commonshop_orderremind: ' . mysql_error());
		}
	}
	/* 从右下角提醒图片进订单管理，更新订单支付总数  End*/	
	 
	/* 4M分销 -- 是否总店  */
	$is_generalcustomer = 1;
	$is_shopgeneral = 0;
	
	$adminuser_id=-1;  //总部模板才添加
	$query_admin = "select adminuser_id from customers where isvalid=true and id=".$customer_id." limit 1";
	$result_admin = mysql_query($query_admin) or die('Query_admin failed: ' . mysql_error()); 
	while ($row_admin = mysql_fetch_object($result_admin)) {
	   $adminuser_id = $row_admin->adminuser_id;
	}
	while($adminuser_id>0){
		$channel_level_id = -1;		
		$query_level = "select channel_level_id,parent_id from adminusers where isvalid=true and id=".$adminuser_id;
		$result_level = mysql_query($query_level) or die('Query_level failed: ' . mysql_error());   
		while ($row_level = mysql_fetch_object($result_level)) {
			$channel_level_id = $row_level->channel_level_id;
			$parent_id2 = $row_level->parent_id;
		}
	    if($channel_level_id==5){
			//找到贴牌
			$query_oem = "select is_shopgeneral from oem_infos where isvalid=true and adminuser_id=".$adminuser_id." limit 1";
			$result_oem = mysql_query($query_oem) or die('Query_oem failed: ' . mysql_error());   
			while ($row_oem = mysql_fetch_object($result_oem)) {
				$is_shopgeneral = $row_oem->is_shopgeneral;
			}
			break;
	    }else{
			$adminuser_id = $parent_id2;
			$is_generalcustomer = 0;
	    }
	}	 
	/*  4M分销 -- 是否总店 End */
	 
	
	//订单查询

	$o_id             = -1;	//编号
	$o_batchcode      = -1;	//订单号
	$o_name           = "";	//名称
	$o_weixin_name    = "";	//微信名称
	$o_phone          = "";	//手机号
	$o_remark         = "";	//订单备注
	$o_paystyle       = 0;	//支付方式 0：微信支付；1：支付宝；2：通联支付
	$o_totalprice     = 0;  //合计总价(包括运费)
	$o_real_pay       = 0;  //实付金额
	$o_freight        = 0;	//运费
	$o_is_change      = 0;	//改价标志
	$o_paystatus      = 0;	//支付状态 0:未支付 1:已支付 -1:支付失败
	$o_createtime     = "1970-00-00 00:00:00";
	$o_transaction_id = "无支付单号";	 //支付单号
	$o_expressName    = "";     //下单名称
	$o_expressPhone   = "";		//下单手机号	
	$o_Pay_Method = 0;		//系统支付方式  0:默认支付;  1:后台手动支付
	$o_status = 0;	//订单状态。-1：取消订单；0：未发货；  1：发货；2：客户收货确定完成；3：商家确定完成订单；4：退货；5：驳回退货；6：退货完成；7：换货完成；8：退款；9：退款完成；10：驳回退款；11：客户发退货物流
	$o_exp_user_id = -1;	//分销商			  
	$o_expressAddress = "";  //收货地址
	$o_identity = "";  //收货身份证
	$o_paytime = "";  //支付时间 
	$o_pageAmount = 0;
	$o_agent_id = -1;
	$o_supply_id = -1;
    $o_agentcont_type = 0;
	$o_aftersale_type = 0;
	$o_is_QR = 0;
	$o_expressnum = "";  //快递单号
	$weipay_style=0;// 0:不是微信支付/找人代付 1:微信支付/找人代付
	$query_order = "
		SELECT 
		orders.id,orders.user_id,orders.batchcode,orders.createtime,orders.paystyle,sum(orders.totalprice) as  totalprice,orders.paystatus,orders.sendstatus,orders.status,orders.exp_user_id,orders.supply_id,orders.allipay_orderid,orders.is_delay,orders.store_id,
		orders.return_type,orders.confirm_sendtime,orders.confirm_receivetime,orders.printUrl,orders.paytime,orders.agent_id,orders.Pay_Method,
		orders.remark,orders.supply_id,orders.supply_id,orders.express_id,orders.sendway,orders.agentcont_type,orders.auto_receivetime,
		orders.aftersale_type,orders.aftersale_state,orders.return_status,orders.return_account,orders.is_QR,orders.expressnum,
		orders.sendstyle,orders.backgoods_reason,
		users.name,users.weixin_name,users.weixin_fromuser,users.phone
		FROM weixin_commonshop_orders as orders 
		LEFT JOIN weixin_users as users ON users.id = orders.user_id";
	$query_order_address = "
		SELECT 
		orders.id,orders.user_id,orders.batchcode,orders.createtime,orders.paystyle,sum(orders.totalprice) as  totalprice,orders.paystatus,orders.sendstatus,orders.status,orders.exp_user_id,orders.supply_id,orders.allipay_orderid,orders.is_delay,orders.store_id,
		orders.return_type,orders.confirm_sendtime,orders.confirm_receivetime,orders.printUrl,orders.paytime,orders.agent_id,orders.Pay_Method,
		orders.remark,orders.supply_id,orders.supply_id,orders.express_id,orders.sendway,orders.agentcont_type,orders.auto_receivetime,
		orders.aftersale_type,orders.aftersale_state,orders.return_status,orders.return_account,orders.is_QR,orders.expressnum,
		orders.sendstyle,orders.backgoods_reason,
		users.name,users.weixin_name,users.weixin_fromuser,users.phone
		FROM weixin_commonshop_orders as orders 
		LEFT JOIN weixin_users as users ON users.id = orders.user_id
		INNER JOIN weixin_commonshop_order_addresses as addr ON orders.batchcode = addr.batchcode and orders.customer_id = ".$customer_id;;
		
	
	/* 搜索条件 */
	$query_search = " WHERE orders.customer_id = ".$customer_id; 
	
	$begintime = "";
	if(!empty($_GET["begintime"])){  //下单时间 
	   $begintime = $_GET["begintime"];
	   $query_search = $query_search." and UNIX_TIMESTAMP(orders.createtime)>=".strtotime($begintime);
	}
	
	$endtime = "";	
	if(!empty($_GET["endtime"])){   //下单时间 End
	   $endtime = $_GET["endtime"];
	   $query_search = $query_search." and UNIX_TIMESTAMP(orders.createtime)<=".strtotime($endtime);
	}
	
	$pay_begintime = "";
	if(!empty($_GET["pay_begintime"])){  //支付时间 
	   $pay_begintime = $_GET["pay_begintime"];
	   $query_search = $query_search." and UNIX_TIMESTAMP(orders.paytime)>=".strtotime($pay_begintime);
	}
	
	$pay_endtime = "";
	if(!empty($_GET["pay_endtime"])){   //支付时间 End
	   $pay_endtime = $_GET["pay_endtime"];
	   $query_search = $query_search." and UNIX_TIMESTAMP(orders.paytime)<=".strtotime($pay_endtime);
	}	
	
	$search_batchcode = "";
	if(!empty($_GET["search_batchcode"])){    //订单号
	   $search_batchcode = $configutil->splash_new($_GET["search_batchcode"]);
	   $query_search = $query_search." and orders.batchcode like '%".$search_batchcode."%'";
	}	
	
	$search_order_ascription = "";
	if(!empty($_GET["search_order_ascription"])){    //订单所属
	   $search_order_ascription = $configutil->splash_new($_GET["search_order_ascription"]);
	    switch($search_order_ascription){
			case -2:
				//所有订单				   
				break;
			case -1:
				//平台订单
				$query_search = $query_search." and orders.supply_id=-1";		   
				break;
			default:
				//供应商订单
				$query_search = $query_search." and orders.supply_id=".$search_order_ascription;
		}	
	}	
	
	$orgin_from = 0;
	if(!empty($_GET["orgin_from"])){    //订单所属
	   $orgin_from = $configutil->splash_new($_GET["orgin_from"]);	
		switch($orgin_from){
			case 1:
				$query_search = $query_search." and orders.exp_user_id<0";
				break;
			case 2:
				$query_search = $query_search." and orders.exp_user_id>0";
				break;
			default:
				break;
		}	
	}	
	
	$search_name = "";
	$search_name_type = 1;
	if(!empty($_GET["search_name_type"])){     //名称类型	   
		(int)$search_name_type = $configutil->splash_new($_GET["search_name_type"]);
	}
	if(!empty($_GET["search_name"])){    //名称	   
	   $search_name = $configutil->splash_new($_GET["search_name"]);
		switch($search_name_type){
			case 1:
				$query_search .= " AND users.weixin_name like '%".$search_name."%'";
			break;
			case 2:
				$query_order_address .= " AND addr.name like '%".$search_name."%'"; 
			break;
		}   	
	}	
	
	$search_paystyle = "";	//支付方式  
	if(isset($_GET["search_paystyle"])){   
	   $search_paystyle = $configutil->splash_new($_GET["search_paystyle"]);  //支付方式
	   $query_search .= " AND orders.paystyle='".$search_paystyle."'";	
	}		
	
	$search_paystatus = "";	//支付状态  
	if(isset($_GET["search_paystatus"])){   
	   (int)$search_paystatus = $configutil->splash_new($_GET["search_paystatus"]);  //支付状态 0:未支付 1:已支付
	   $query_search .= " AND orders.paystatus=".$search_paystatus;	
	}		

	$search_shop_id = "";	//门店号
	if(isset($_GET["search_shop_id"])){   
	   (int)$search_shop_id = $configutil->splash_new($_GET["search_shop_id"]); 
	   $query_search .= " AND orders.store_id='".$search_shop_id."'";	
	}	
	
	$search_user_id="";			//顾客编号
	if(!empty($_GET["user_id"])){		
		(int)$search_user_id = $configutil->splash_new($_GET["user_id"]);
		$query_search .= " AND orders.user_id='".$search_user_id."'";
	}	
	/* 搜索条件End */	
	
	/* 订单管理状态 */
	$search_class = 0;
	if(isset($_GET["search_class"]) and $_GET["search_class"] !="" ){   
	   (int)$search_class = $configutil->splash_new($_GET["search_class"]); 	   	
	}
	switch($search_class){
		case 0:  
			$query_search .= " AND orders.isvalid=true";  //所有订单
		break;			
		case 1: 
			$query_search .= " AND orders.paystatus=false AND orders.isvalid=true";  //待付款
		break;
		case 2:
			$query_search .= " AND orders.paystatus=true AND orders.sendstatus=0  AND orders.isvalid=true";	  //待发货
			break;			
		case 3:
			$query_search .= " AND orders.paystatus=true AND orders.status=1  AND orders.isvalid=true";  //交易完成
			break;			
		case 4:
			$query_search .= " AND orders.status=-1  AND orders.isvalid=true";  //已关闭
			break;			
		case 5:
			$query_search .= " AND orders.paystatus=false  AND orders.isvalid=false";	  //未付款删除
			break;
		case 6:
			$query_search .= " AND orders.paystatus=true  AND orders.isvalid=false";	 //已付款删除
			break;	
		case 7:
			$query_search .= " AND orders.paystatus=true  AND orders.status=0 AND sendstatus in(2,4,6)";	 //已付款删除
			break;	
		case 8:	
			$query_search .= " AND orders.paystatus=true AND orders.isvalid=true ";	 	//已支付
			break;
		case 9:	
			$query_search .= " AND orders.paystatus=false AND orders.isvalid=true ";	 //未支付
			break;
		case 0.5:	
			$query_search .= " AND orders.sendstatus=true AND orders.isvalid=true ";	 //已发货
			break;			
			
		case 10:  
			$query_search .= " AND orders.sendstatus>2  AND orders.isvalid=true";  //所有售后申请
		break;			
		case 11: 
			$query_search .= " AND orders.sendstatus=3 AND orders.return_type=2 AND orders.isvalid=true";  //换货申请
		break;
		case 12:
			$query_search .= " AND (orders.sendstatus=5 OR (orders.sendstatus=3 AND orders.return_type=0)) AND orders.isvalid=true";	  //退款申请
			break;			
		case 13:
			$query_search .= " AND orders.sendstatus=3 AND orders.return_type=1 AND orders.isvalid=true";  //退货申请
			break;			
		case 14:
			$query_search .= " AND (orders.sendstatus=4 OR orders.sendstatus=6) AND orders.isvalid=true";  //售后处理完毕
			break;	
			
		case -1:
			$query_search .= " AND orders.aftersale_state>0  AND orders.isvalid=true";	
			break;			
		case -2:
			$query_search .= " AND orders.aftersale_state>0 AND orders.aftersale_type=3 AND orders.isvalid=true";	
			break;	
		case -3:
			$query_search .= " AND orders.aftersale_state>0 AND orders.aftersale_type=1 AND orders.isvalid=true";	
			break;		
		case -4:
			$query_search .= " AND orders.aftersale_state>0 AND orders.aftersale_type=2 AND orders.isvalid=true";	
			break;		
		case -5:
			$query_search .= " AND orders.aftersale_state=4  AND orders.isvalid=true";	
			break;		
		default:
		echo "状态异常";
		return;
	}	
	/* 订单管理状态 End */

	// 分页---start
	$pagenum = 1;
	if(!empty($_GET["pagenum"])){
	   $pagenum = $configutil->splash_new($_GET["pagenum"]);
	}
	$pagesize = 20;
	if(!empty($_GET["pagesize"])){
	   $pagesize = $configutil->splash_new($_GET["pagesize"]);
	}	
	$start = ($pagenum-1) * $pagesize;
	$end = $pagesize;
	
	$query_num = '
	SELECT count(distinct orders.batchcode) as wcount FROM weixin_commonshop_orders as orders 
	LEFT JOIN weixin_users as users ON users.id = orders.user_id
	INNER JOIN weixin_commonshop_order_addresses as addr ON orders.batchcode = addr.batchcode';
	$query_num .= $query_search;
	$result_num = mysql_query($query_num) or die('Query_num failed: ' . mysql_error());
	$wcount =0;
	$page=0;
	while ($row_num = mysql_fetch_object($result_num)) {
		$wcount =  $row_num->wcount ;
	}			
	$page=ceil($wcount/$end);
	// 分页---end 	
	
	$query_order .= $query_search;
	$query_order .=  " GROUP BY orders.batchcode ORDER BY id DESC limit ".$start.",".$end; 
	//echo "数据".$query_order;	 

		if($search_name_type == 2){
						$query_order = $query_order_address." GROUP BY orders.batchcode ORDER BY id DESC limit ".$start.",".$end; 
						//echo $query_order;exit;
					}
	
	//订单查询End
	
	//	file_put_contents ( "query_order.txt", "postStr====".$query_order . "\r\n", FILE_APPEND );
	//查询平台是否开启虚拟发货
	$query_virtual = "select open_virtual_cust from weixin_commonshops where customer_id = ".$customer_id;
	$open_virtual_cust = 1;
	$result_virtual = mysql_query($query_virtual) or die("query_virtual Query error : ".mysql_error());
	if($row_virtual = mysql_fetch_object($result_virtual)){
		$open_virtual_cust = $row_virtual -> open_virtual_cust;
	}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>订单管理</title>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link rel="stylesheet" type="text/css" href="../../Common/css/Order/orders/order.css">
<script type="text/javascript" src="../../../common/js/jquery-2.1.0.min.js"></script>
<script charset="utf-8" src="../../../common/js/layer/V2_1/layer.js"></script>
<script type="text/javascript" src="../../../js/WdatePicker.js"></script> 
<script src="../../../common_shop/jiushop/js/region_select.js"></script> 
<script type="text/javascript" src="../../Common/js/Order/order/order.js"></script>
<script>
layer.config({
    extend: '/extend/layer.ext.js'
});  
</script> 

<style>
.con-button{  float: left;padding-left: 20px;padding-top: 17px;}
.con-button2{  float: left;padding-left: 5px;padding-top: 17px;}
.textCenter{text-align: center;}
.WSY_position_text a:first-child{margin-left: 0;}
.WSY_bottonli2 input{margin-right: 6px;}

.WSY_righticon_li04 label{margin-top: 3px;display: inline-block;}
.WSY_righticon_li04 input{margin-top: 6px;margin-right: 5px;vertical-align: sub;}  
.WSY_righticon_li05 label{margin-top: 3px;display: inline-block;} 
.WSY_righticon_li05 input{margin-top: 6px;margin-right: 5px;vertical-align: sub;}
.WSY_position_date input{height:22px;}
</style>
</head>

<body>
<!--内容框架开始-->
<div class="WSY_content" id="WSY_content_height">



       <!--列表内容大框开始-->
	<div class="WSY_columnbox">
    	<!--列表头部切换开始-->
    	<div class="WSY_column_header">
        	<div class="WSY_columnnav">
				<?php if($search_class >= 0 and $search_class<10){ ?>
				<a <?php if($search_class == 0){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>">所有订单</a>
				<a <?php if($search_class == 1){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=1">待付款</a>
				<a <?php if($search_class == 2){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=2">待发货</a>
				<a <?php if($search_class == 0.5){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=0.5">已发货</a>				
				<a <?php if($search_class == 7){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=7">待完成</a>				
				<a <?php if($search_class == 3){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=3">交易完成</a>
				<a <?php if($search_class == 4){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=4">已关闭</a>				
				<a <?php if($search_class == 5){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=5">未付款删除</a>
				<a <?php if($search_class == 6){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=6">已付款删除</a>
				<?php }elseif($search_class>=10){ ?>
				<a <?php if($search_class == 10){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=10">所有售后申请</a>
				<a <?php if($search_class == 11){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=11">换货申请</a>
				<a <?php if($search_class == 12){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=12">退款申请</a>
				<a <?php if($search_class == 13){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=13">退货申请</a>				 
				<a <?php if($search_class == 14){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=14">售后处理完毕</a>				
				<?php }else{ ?>
				<a <?php if($search_class == -1){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=-1">所有维权申请</a>
				<a <?php if($search_class == -2){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=-2">换货申请</a>
				<a <?php if($search_class == -3){echo 'class="white1"';} ?> style="display:none;" href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=-3">退款申请</a>
				<a <?php if($search_class == -4){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=-4">退货申请</a>				 
				<a <?php if($search_class == -5){echo 'class="white1"';} ?> href="order.php?customer_id=<?php echo passport_encrypt($customer_id)?>&search_class=-5">维权处理完毕</a>				
				<?php } ?>
            </div>
        </div>
        <!--列表头部切换结束-->
           

    <!--订单管理代码开始-->
	<form class="search" id="search_form" >
    <div class="WSY_data">
		<div class="WSY_position1" >
			<ul>
				<li class="WSY_position_date tate001">
					<p>支付时间：<input class="date_picker" type="text" name="AccTime_S" id="pay_begintime" value="<?php echo $pay_begintime ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
					<p>&nbsp;&nbsp;-&nbsp;&nbsp;<input class="date_picker" type="text" name="AccTime_B" id="pay_endtime" value="<?php echo $pay_endtime ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
				</li>				
				<li class="WSY_position_date tate001" >
					<p>&nbsp;&nbsp;&nbsp;&nbsp;下单时间：<input class="date_picker" type="text" name="AccTime_E" id="begintime" value="<?php echo $begintime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
					<p>&nbsp;&nbsp;-&nbsp;&nbsp;<input class="date_picker" type="text" name="AccTime_B" id="endtime" value="<?php echo $endtime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
				</li>				
			 </ul>
		</div>
		<div class="WSY_position1" >
			<ul>				
				<li class="WSY_position_text">
					<a>订单号：<input type="text" name="search_batchcode" id="search_batchcode" value="<?php echo $search_batchcode; ?>"></a>
					<a>姓名：<input type="text" name="search_name" id="search_name" value="<?php echo $search_name; ?>"></a>
					<input type="hidden" name="search_class" id="search_class" value="<?php echo $search_class; ?>">
					<select name="search_name_type" id="search_name_type">
						<option value="1" <?php if($search_name_type==1){ ?>selected <?php } ?>>微信昵称</option>
						<option value="2" <?php if($search_name_type==2){ ?>selected <?php } ?>>收货人</option>
					</select>
					<a class="WSY_bottonliss"><input type="button" value="订单搜索" onclick="searchForm();" /></a>
				 </li>
				 <li class="WSY_bottonli WSY_bottonli2">
					<input type="button"  value="导出记录" onClick="exportRecord(1);" >
					<input type="button"  value="导出飞豆" onClick="exportRecord(2);" >
					<input type="button"  value="导出海关头部" onClick="exportRecord(3);" >
					<input type="button"  value="导出海关明细" onClick="exportRecord(4);" >
					<input type="button"  value="一键完成订单" onClick="finishOrder();" >
					<!--<input type="button" value="批量删除">-->
				 </li>
			 </ul>
		</div>		
    	<div class="WSY_orderformbox" style="margin-top: 12px;" >
			<ul>					
            <li class="WSY_righticon_li01">
				<p>支付方式：</p>
                	<select name="search_paystyle" id="search_paystyle" >
                    	<option value="" >-- 请选择 --</option>
							<option value="微信支付" <?php if($search_paystyle=="微信支付"){ ?>selected <?php } ?>>微信支付</option>
							<option value="支付宝支付" <?php if($search_paystyle=="支付宝支付"){ ?>selected <?php } ?>>支付宝支付</option>
							<option value="通联支付" <?php if($search_paystyle=="通联支付"){ ?>selected <?php } ?>>通联支付</option>
							<option value="货到付款" <?php if($search_paystyle=="货到付款"){ ?>selected <?php } ?>>货到付款</option>
							<option value="到店支付" <?php if($search_paystyle=="到店支付"){ ?>selected <?php } ?>>到店支付</option>
							<option value="会员卡余额支付" <?php if($search_paystyle=="会员卡余额支付"){ ?>selected <?php } ?>>会员卡余额支付</option>
                 </select>
            </li>
            <li class="WSY_righticon_li02">
				<p>订单归属：</p>
                <select name="search_order_ascription" id="search_order_ascription" >
                   	<option value="-2" >-- 请选择 --</option>
                   	<option value="-1" >平台</option>
					<?php 
					$query_prom = "
					SELECT pro.user_id,users.name,users.weixin_name
					FROM promoters as pro
					LEFT JOIN weixin_users as users on pro.user_id = users.id
					WHERE pro.isvalid = true AND pro.isAgent = 3 AND pro.customer_id = ".$customer_id;					
					$result_prom = mysql_query($query_prom) or die('Query_prom failed: ' . mysql_error());
					while ($row_prom = mysql_fetch_object($result_prom)) {
						$sup_user_id     = $row_prom->user_id;
						$sup_name        = $row_prom->name;
						$sup_weixin_name = $row_prom->weixin_name;	
						$sup_userName    =	$sup_name;
						if(!empty($sup_weixin_name)){ $sup_userName .= "(". $sup_weixin_name . ")"; }
					?>
					<option value="<?php echo $sup_user_id;  ?>" <?php if($search_order_ascription == $sup_user_id){echo "selected"; } ?>><?php echo $sup_userName; ?></option>					
					<?php } ?>
					
                </select>
            </li>
            <li class="WSY_righticon_li03">
				<p>每页记录数：</p>
					<select name="pagesize" id="pagesize" >
					<option value="20" <?php if($pagesize==20){ ?>selected<?php } ?>>20</option>
					<option value="40" <?php if($pagesize==40){ ?>selected<?php } ?>>40</option>
					<option value="75" <?php if($pagesize==75){ ?>selected<?php } ?>>75</option>
					<option value="100" <?php if($pagesize==100){ ?>selected<?php } ?>>100</option>
					</select>
            </li>            
			<li class="WSY_righticon_li03">
				<p>订单来源：</p>
					<select name="orgin_from" id="orgin_from" style="width:100px;">
						<option value="" >所有订单</option>
						<option value="1" <?php if($orgin_from==1){ ?>selected<?php } ?>>非推广订单</option>
						<option value="2" <?php if($orgin_from==2){ ?>selected<?php } ?>>推广订单</option>
					</select>
            </li>
            <li class="WSY_righticon_li04">
			    <input type="checkbox" id="auto_refer" name="auto_refer" value="on" <?php if($isauto){?> checked<?php } ?>><label for="auto_refer">自动刷新订单</label>  
			  </li>            
			  <li class="WSY_righticon_li05">
				<input type="checkbox"  id="order_remind" name="order_remind" value="on" onclick="chkremind();" <?php if($is_remind){?> checked<?php } ?>><label for="order_remind">订单提醒功能</label>  
			  </li>
			</ul>
        </div>		
	</div>		
	</form>
	<form  id="frm_import" action="save_order_excel.php?customer_id=<?php echo passport_encrypt((string)$customer_id); ?>" enctype="multipart/form-data" method="post">
		<div style="overflow:hidden;margin-left:20px">
			<div class="uploader white">
				<input type="text" class="filename" readonly/>
				<input type="button" name="file" class="button" value="上传..."/>
				<input type="file" id="excelfile" name="excelfile" size="30"/>
			</div>
			<div class="WSY_position_text" style="margin-left: 33px;margin-top:24px">
				<!--<input type=file name="excelfile" style="width:150px;" id="excelfile" />-->
				<a class="WSY_bottonliss"><input type="button" value="导入快递号" onclick="importMember();" /></a>
				<span style="color:red">
					(请使用金山Excel) 
				</span>
			</div>
		</div>
	</form>
	<style>
		.uploader,.WSY_position_text{float:left}
	</style>
	<!--订单管理代码开始 End -->
	
     <!--表格开始-->
	<div style="width:100%;overflow:hidden">
		<table width="97%" class="CP_table " id="order_table">
			 <thead class="CP_table_header">
				<th width="30%">产品</th>
				<th width="10%">收货人</th>
				<th width="12%">实付金额</th>
				<th width="15%">发货状态</th>
				<th width="8%">订单状态</th>
				<th width="10%">关系</th>
				<th width="15%">操作</th>
			 </thead>
			 
			 <?php  
				$result_order = mysql_query($query_order) or die('Query_order failed: ' . mysql_error());

				while ($row_order = mysql_fetch_object($result_order)) {
					$o_batchcode = $row_order->batchcode;  //订单
					$address_user_id =$row_order->user_id;
					$o_createtime = $row_order->createtime;  //下单时间
					$o_paystyle = $row_order->paystyle;  //支付类型
					$o_paystatus = $row_order->paystatus;  //支付状态
					$o_allipay_orderid = $row_order->allipay_orderid;	 //通联支付单号						
					//$o_expressName = $row_order->expressName;		//快递-名称						
					$o_weixin_name = $row_order->weixin_name;			//微信名称					
					//$o_expressPhone = $row_order->expressPhone;	 //快递-电话
					$o_backgoods_reason = $row_order->backgoods_reason;	 //(退货/退款)原因
					$o_totalprice = $row_order->totalprice;		  //总价
					$o_exp_user_id = $row_order->exp_user_id;   //推广员ID
					$o_sendstatus = $row_order->sendstatus;   //发货状态
					$o_status = $row_order->status;   //订单状态
					$o_return_type = $row_order->return_type;    //退货/款状态
					$o_confirm_sendtime = $row_order->confirm_sendtime;   //发货时间
					$o_confirm_receivetime = $row_order->confirm_receivetime;   //收货时间
					$o_sf_printUrl = $row_order->printUrl;			//顺风进口订单		
					$o_paytime = $row_order->paytime;			//支付时间		
					$o_agent_id = $row_order->agent_id;			//代理商编号	
					$o_supply_id = $row_order->supply_id;			//供应商编号	
					$o_is_QR = $row_order->is_QR;			//二维码	
					$o_weixin_fromuser = $row_order->weixin_fromuser;			//微信OpneID	
					$o_name = $row_order->name;			//注册-名称	
					$o_phone = $row_order->phone;			//注册-电话	
					$o_phone = $row_order->phone;			//注册-电话	
					///$o_location_p = $row_order->location_p;			//省
					//$o_location_c = $row_order->location_c;			//市	
					//$o_location_a = $row_order->location_a;			//区
					//$o_expressAddress = $row_order->expressAddress;			//详细地址
					//$o_identity = $row_order->identity;			//身份证
					$o_remark = $row_order->remark;			//订单备注
					$o_expressnum = $row_order->expressnum;			//发货-快递单号
					$o_sendstyle = $row_order->sendstyle;			//收货方式
					$o_express_id = $row_order->express_id;			//快递方式
					$o_Pay_Method = $row_order->Pay_Method;			//后台支付
					$o_sendway = $row_order->sendway;			//代理商发货，0:未指派，1：平台发货，2：代理商发货
					$o_agentcont_type = $row_order->agentcont_type;		//代理结算: 1、代理结算 0、推广员结算
					$o_is_delay = $row_order->is_delay;		//申请延期状态：1：已申请；2：已处理
					$o_aftersale_type = $row_order->aftersale_type;		//售后维权 0：无；1：退款；2：退货；3：换货
					$o_aftersale_state = $row_order->aftersale_state;  //申请售后状态：0:默认；1：已申请；2：同意；3：驳回；4：已处理
					$o_return_status = $row_order->return_status;  //退货状态。0. 未退货；1：退货成功；-1：退货失败；2：同意退货；3：驳回请求；4：确认退货；5： 用户已退货；6：商家确认收货；7：商家已发货；8：同意退款；9：驳回退款
					$o_store_id = $row_order->store_id;
					
					
					
					
					
					
					$add_address_sql = 'select addr.name as expressName,addr.phone as expressPhone,addr.location_p,addr.location_c,addr.location_a,addr.address as expressAddress,addr.identity from weixin_commonshop_order_addresses addr where addr.batchcode = "'.$o_batchcode.'"';
					
					$add_address_result = mysql_query($add_address_sql) or die('query_shop failed: ' . mysql_error());		
					if( mysql_num_rows($add_address_result) == 0){
						$address_comon_sql = 'select name,phone,location_a,location_c,location_p,address,identity from weixin_commonshop_addresses where is_default =1 and user_id ='.$address_user_id;
						//echo $address_comon_sql;exit;
						$address_comon_result = mysql_query($address_comon_sql) or die('query_shop222 failed: ' . mysql_error());
						while ($address_comon_row = mysql_fetch_object($address_comon_result)) {
							$o_expressName = $address_comon_row->name;			//注册-名称	
							$o_expressPhone = $address_comon_row->phone;			//注册-电话	
							
							$o_location_p = $address_comon_row->location_p;			//省
							$o_location_c = $address_comon_row->location_c;			//市	
							$o_location_a = $address_comon_row->location_a;			//区
							$o_expressAddress = $address_comon_row->address;			//详细地址
							$o_identity = $address_comon_row->identity;			//身份证
						}
						
						$sql_order_address="insert into weixin_commonshop_order_addresses(name,phone,address,batchcode,location_p,location_c,location_a,identity) values('".$o_expressName."','".$o_expressPhone."','".$o_expressAddress."','".$o_batchcode."','".$o_location_p."','".$o_location_c."','".$o_location_a."','".$o_identity."')";	 
						mysql_query($sql_order_address);
						//file_put_contents ( "3333.txt", "postStr====".$sql_order_address . "\r\n", FILE_APPEND );
					}else{
						//file_put_contents ( "add_address_sql.txt", "postStr====".$add_address_sql . "\r\n", FILE_APPEND );
						while ($row_address_result = mysql_fetch_object($add_address_result)) {
							$o_expressName = $row_address_result->expressName;			//注册-名称	
							$o_expressPhone = $row_address_result->expressPhone;			//注册-电话	
							
							$o_location_p = $row_address_result->location_p;			//省
							$o_location_c = $row_address_result->location_c;			//市	
							$o_location_a = $row_address_result->location_a;			//区
							$o_expressAddress = $row_address_result->expressAddress;			//详细地址
							$o_identity = $row_address_result->identity;			//身份证
						}
					}
					if(!empty($o_weixin_name)){
						$o_name .= "(". $o_weixin_name . ")";
					}
					
					$o_store_name = "";
					if(!empty($o_store_id) && $o_store_id > 0){
						$query_shop = "select name from weixin_card_shops where isvalid = true and id = ".$o_store_id;
						$result_shop = mysql_query($query_shop) or die('query_shop failed: ' . mysql_error());
						if($row_shop = mysql_fetch_object($result_shop)){
							$o_store_name = $row_shop->name;
						}
					}
					
					
					/*  发货状态 */
					$sendstatusstr = "<img src=\"../../../common/images_V6.0/contenticon/notaffirm-icon.png\" /> <b>未发货</b>";
					switch($o_sendstatus){
					   case 1:
					       $sendstatusstr = "<img src=\"../../../common/images_V6.0/contenticon/affirm-icon.png\" /> <b style=\"color:#31B0D5\">已发货</b>";
						   if($o_is_delay == 1){
							    $sendstatusstr .= "<span style='color:red'> [申请延迟收货]</span>";
						   }
					       break;
					   case 2:
						   $sendstatusstr = "<img src=\"../../../common/images_V6.0/contenticon/confirm_delivery.png\" /> <b style=\"color:#337AB7\">顾客已收货</b>";						   
						   break;
					   case 3:
							$sendstatusstr = "<img src=\"../../../common/images_V6.0/contenticon/return-goods.png\" /> <b style=\"color:#C9302C\">顾客申请退货</b>";	
							if($o_return_type == 0){
								$sendstatusstr = "<img src=\"../../../common/images_V6.0/contenticon/return-money-only.png\" /> <b style=\"color:#C9302C\">申请退货(仅退款)</b>";	
							}else if($o_return_type == 2){
								$sendstatusstr = "<img src=\"../../../common/images_V6.0/contenticon/change-goods.png\" /> <b style=\"color:#C9302C\">申请退货(换货)</b>";	
							}
						   if($o_return_status == 2){
							    $sendstatusstr .= "<b style='color:#C9302C'> [已同意]</b>";
						   }else if($o_return_status == 3){
							   $sendstatusstr .= "<b style='color:#C9302C'> [已驳回]</b>";
						   }else if($o_return_status == 5){
							   $sendstatusstr .= "<b style='color:#C9302C'> [用户已退货]</b>";
						   }else if($o_return_status == 6){
							   $sendstatusstr .= "<b style='color:#C9302C'> [已收到退货]</b>";
						   }
						   break;
						case 4:
							$rt = "退货";
							if($o_return_type == 0){
								$rt = "仅退款";
							}else if($o_return_type == 2){
								$rt = "换货";
							}						
						   $sendstatusstr = "<img src=\"../../../common/images_V6.0/contenticon/confirm-return.png\" /> <b style=\"color:#1eaf4e\">退货已确认(".$rt.")</b>";	
						   break;
						case 5:
						   $sendstatusstr = "<img src=\"../../../common/images_V6.0/contenticon/return-money.png\" /> <b style=\"color:#C9302C\">顾客申请退款</b>";	
							if($o_return_status == 8){
							    $sendstatusstr.= "<b style='color:#C9302C'> [已同意]</b>";
						   }else if($o_return_status == 9){
							   $sendstatusstr .= "<b style='color:#C9302C'> [已驳回]</b>";
						   }						   
						   break;
						case 6:	 
						   $sendstatusstr = "<img src=\"../../../common/images_V6.0/contenticon/refund-success.png\" /> <b style=\"color:#1eaf4e\">退款完成</b>";	
						   break;
					}	
					/*  发货状态 End */
					
					

														
					/*是否改价 改价则改价价格为全价,非改价则需加上运费*/
					$o_totalprice_last = 0;
					$o_CouponPrice = 0;	//代金券金额
					/* 查询运费 */
					$query_express="select price from weixin_commonshop_order_express_prices where isvalid=true and batchcode='".$o_batchcode."' limit 0,1";
					$result_express = mysql_query($query_express) or die('Query_express failed: ' . mysql_error());
					$o_express_price = 0;
					while ($row_express = mysql_fetch_object($result_express)) {
						$o_express_price = $row_express->price;
					}										
					/* 查询运费End */	
					$o_totalprice_last = $o_totalprice + $o_express_price;	//非改价加运费 
				
					$query_change="select price,CouponPrice from weixin_commonshop_order_prices where isvalid=true and batchcode='".$o_batchcode."' limit 1";					
					$result_change = mysql_query($query_change) or die('Query_change failed: ' . mysql_error());
					while ($row_change = mysql_fetch_object($result_change)) {
					    //获取订单的真实价格（可能是折扣总价）
					    $o_totalprice_last = $row_change->price;
					    $o_CouponPrice = $row_change->CouponPrice;
					}
					
					$changeprice_str = "";
					$query_change_price= "select totalprice from weixin_commonshop_changeprices where status=1 and isvalid=1 and batchcode='".$o_batchcode."' limit 1";
					$result_change_price = mysql_query($query_change_price) or die('Query_change_price failed: ' . mysql_error());
					while ($row_change_price = mysql_fetch_object($result_change_price)) {
					    $o_totalprice_last = $row_change_price->totalprice;
						if($o_totalprice_last>0){
							$changeprice_str = '<span style="color:#dd514c;margin-left: 4px;">(改价后)</span>';
						}
					}	
					/* 是否改价END */
	

	
					/* 查询上级 */
					$exp_user_name ="无";
					if($o_exp_user_id>0){
					    $query_exp= "select name,phone,weixin_name,weixin_fromuser from weixin_users where id=" . $o_exp_user_id . " limit 1"; 
					    $result_exp = mysql_query($query_exp) or die('Query_exp failed: ' . mysql_error());
						while ($row_exp = mysql_fetch_object($result_exp)) {
							$exp_user_name=$row_exp->name;
							$exp_weixin_name = $row_exp->weixin_name;
							$exp_fromuser = $row_exp->weixin_fromuser;
							if(!empty($exp_weixin_name)){
								$exp_user_name = $exp_user_name."(".$exp_weixin_name.")";		
							}										
						}
					}
					/* 查询上级End */


					
					/* 订单收发货日期 */
					$confirm_sendtimestr = "";
					$confirm_receivetimestr="";
					if(!empty($o_confirm_sendtime) and $o_confirm_sendtime!="0000-00-00 00:00:00"){
						$confirm_sendtimestr = "<p>发货时间:".$o_confirm_sendtime;
						if($o_sf_printUrl){
							$confirm_sendtimestr .= "<a  href='$sf_printUrl' target='_blank' class='btn'   title='顺丰运单打印'><i  class='icon-print'></i></a>&nbsp;&nbsp;<a  href='./sf/routeQuery.php?mailorderNo=$expressnum&customer_id=$customer_id' target='_blank' class='btn'   title='运单路由'><i  class='icon-globe'></i></a>";					   
					  }
					  $confirm_sendtimestr .= "</p>";
				   }
				    if(!empty($o_confirm_receivetime) and $o_confirm_receivetime!="0000-00-00 00:00:00"){					  

					    if($o_sendstatus==4 or $o_sendstatus==6){
							$confirm_receivetimestr="<p>退货时间:".$o_confirm_receivetime."</p>";
					    }else{
							$confirm_receivetimestr="<p>收货时间:".$o_confirm_receivetime."</p>";
					    }
				    }				   
					
					/* 订单收发货日期 End */
	

	
					/* 代理商 */
					$agent_name ="";
					$agent_username = "";
					$agent_weixin_fromuser ="";
					if($o_agent_id>0){
						$query_agent = "SELECT name,weixin_name,weixin_fromuser FROM weixin_users WHERE id=".$o_agent_id." limit 1"; 
						$result_agent = mysql_query($query_agent) or die('query_agent failed: ' . mysql_error());				
						while ($row_agent = mysql_fetch_object($result_agent)) {
							$agent_username=$row_agent->name;
							$agent_weixin_fromuser= $row_agent->weixin_fromuser;
							$agent_weixin_name=$row_agent->weixin_name;
							if(!empty($agent_weixin_fromuser)){
								$agent_username = $agent_username."(".$agent_weixin_name.")";
							}						
						}							
					}				
					/* 代理商 End  */

					
					
					/* 查看代理商发货方式 */
					$p_sendway=0;
					$o_open_sendway=0;
					if($o_agent_id>0){
						$query_sendway = "select sendway from promoters where isvalid=true and customer_id=".$customer_id." and user_id=".$o_agent_id;
						$result_sendway = mysql_query($query_sendway) or die('Query_sendway failed: ' . mysql_error());
						while ($row_sendway = mysql_fetch_object($result_sendway)) {
							$p_sendway = $row_sendway->sendway; //1:代理商自己发货 0:平台发货
						}
						if($p_sendway==1 and $o_sendway==2 and $o_supply_id<0){ 
							$o_open_sendway=1;
						}
					}
					/* 查看代理商发货方式End */


					
					/* 供应商 */
					$supply_name ="";
					$supply_username = "";
					$supply_weixin_fromuser ="";
					if($o_supply_id>0){
						$query_supply = "SELECT name,weixin_name,weixin_fromuser FROM weixin_users WHERE id=".$o_supply_id." limit 1"; 
						$result_supply = mysql_query($query_supply) or die('query_supply failed: ' . mysql_error());				
						while ($row_supply = mysql_fetch_object($result_supply)) {
							$supply_username=$row_supply->name;
							$supply_weixin_fromuser= $row_supply->weixin_fromuser;
							$supply_weixin_name=$row_supply->weixin_name;
							if(!empty($supply_weixin_fromuser)){
								$supply_username = $supply_username."(".$supply_weixin_name.")";
							}						
						}							
					}				
					/* 供应商 End  */					
	
					/* 订单状态  */
					$o_statusstr="<span class='btn btn-grey'>未完成</span>";	
					if($o_status==1){
					   $o_statusstr="<span class='btn btn-success'>已完成</span>";	
					}else if($o_status==-1){
					   $o_statusstr="<span class='btn btn-danger'>顾客已取消</span>";	
					}
					if($o_aftersale_state > 0){
							$o_statusstr = $o_statusstr . "<span class='btn btn-warning'>";
						if($o_aftersale_state == 1){
							$o_statusstr = $o_statusstr . "申请售后维权";
						 }else if($o_aftersale_state == 2){
							  $o_statusstr = $o_statusstr . "同意售后维权";
						 }else if($o_aftersale_state == 3){
							  $o_statusstr = $o_statusstr . "驳回售后维权";
						 }else if($o_aftersale_state == 4){
							  $o_statusstr = $o_statusstr . "售后已处理完成";
						 }
						 $o_statusstr = $o_statusstr . "</span>";
						 $o_statusstr = $o_statusstr . "<span class='btn btn-success'>".($o_aftersale_type == 2 ? "退货":"换货")."</span>";
					}
					/* 订单状态 End  */
	
			 ?>
			 <tr class="CP_table_bianhao" >
				<td class="CP_table_bianhaoa" colspan="7">
					<!--<input type="checkbox" name="code_Value" value="1">-->
					<span class="CP_table_bianhaob" >订单编号：<b onclick="showDetail('<?php echo $o_batchcode; ?>')"><?php echo $o_batchcode; ?></b>
					
					<?php if($o_agentcont_type==1){?>
					<img style="width:18px;height:18px;margin-left:2px" src="../../../common/images_V6.0/contenticon/dai.png" ondragstart="return false;" />
					<?php }  if($o_supply_id>0){?>
					<img style="width:18px;height:18px;margin-left:2px" src="../../../common/images_V6.0/contenticon/gong.png" ondragstart="return false;" />
					<?php }  if($o_is_QR==1){   ?>
					<img style="width:18px;height:18px;margin-left:2px" src="../../../common/images_V6.0/contenticon/coupon.png" ondragstart="return false;" />
					<?php } ?>					
					
					<span class="CP_table_bianhaod"><?php echo $o_createtime; ?></span>
					<?php if($o_CouponPrice>0){ ?>
					<span>
					<img style="margin-right: -20px;" src="../../../common/images_V6.0/contenticon/pay-discount.png" />
					</span>
					<?php } ?>					
					<span id="order_pay_<?php echo $o_batchcode; ?>" >
					<?php if($o_paystatus==0){ ?>
					<img src="../../../common/images_V6.0/contenticon/del-icon.png" /><span class="CP_table_bianhaoe">未支付</span>
					<a title="催单" style="cursor:pointer;" onclick="callPay('<?php echo $o_batchcode; ?>',<?php echo $o_totalprice_last; ?>)" ><img style="width:16px;height:18px;" src="../../../common/images_V6.0/contenticon/callback.png" /></a>
					<?php }else{ ?>
					<img src="../../../common/images_V6.0/contenticon/pay-icon.png" /><span class="CP_table_bianhaof">已支付<?php if($o_Pay_Method==1){?><span style="color:red;">(后台支付)</span><?php }?></span>
					<?php } ?>					
					</span>
					
				<span class="CP_table_bianhaog">支付方式：<?php echo $o_paystyle;?></span>
				<span class="CP_table_bianhaoh">
				<?php 
				if($o_paystatus==1 and $o_Pay_Method==0){ 
					$transaction_id=-1;
					if($o_paystyle=="通联支付"){ 
						echo "[<a href=\"allipay_detail.php?allipay_orderid=$o_allipay_orderid\">". $o_allipay_orderid ."(点击查看)</a>]";
					}
					/* 微信支付 */				
					if($o_paystyle=="微信支付" or $o_paystyle=="找人代付" ){
						$weipay = "select transaction_id from weixin_weipay_notifys where isvalid=true and out_trade_no='".$o_batchcode."'";
						$result_weipay = mysql_query($weipay) or die('Query_weipay failed: ' . mysql_error());
						while ($row_result_weipay = mysql_fetch_object($result_weipay)) {
							$transaction_id = $row_result_weipay->transaction_id;
						}
						if($wxpay_version==2){
							echo "[<a href=\"weipay_detail.php?allipay_orderid=$transaction_id&batchcode=$o_batchcode\">". $transaction_id ."(点击查看)</a>]";
						}else{
							echo "[<a href=\"weipay_detail2.php?batchcode=$o_batchcode\">". $transaction_id ."(点击查看)</a>]";
						}
					}
					/* 微信支付End */	
				}
				?> 
				</span>
				</td>
			 </tr>
			 
			 <?php 
					/* 产品信息 */
						$o2_prvalues        = "";
						$o2_rcount          = 0;
						$o2_prvalue         = "";
						$o2_merchant_remark = "";
						$o2_num             = 0;
						$o2_imgurl          = 0;
						$query_order2 = "SELECT orders2.rcount,orders2.prvalues,orders2.totalprice,orders2.isvalid,orders2.merchant_remark, 
							product.name,product.foreign_mark,product.default_imgurl,product.id
							FROM weixin_commonshop_orders as orders2
							LEFT JOIN weixin_commonshop_products as product on product.id=orders2.pid
							where orders2.batchcode=".$o_batchcode." and orders2.isvalid=true";
						$result_order2 = mysql_query($query_order2) or die('Query_order2 failed: ' . mysql_error());
						$o2_rows = mysql_num_rows($result_order2);
						while ($row_order2 = mysql_fetch_object($result_order2)) { 
							$o2_pid             = $row_order2->id;
							$o2_rcount          = $row_order2->rcount;
							$o2_isvalid         = $row_order2->isvalid;
							$o2_prvalue         = $row_order2->prvalues;
							$o2_nane            = $row_order2->name;
							$o2_totalprice      = $row_order2->totalprice;
							$o2_foreign_mark    = $row_order2->foreign_mark;
							$o2_imgurl          = $row_order2->default_imgurl;
							$o2_merchant_remark = $row_order2->merchant_remark;
							$o2_totalprice      = $o2_totalprice / $o2_rcount;
							
							/* 产品图片 */
							if(empty($o2_imgurl)){
								$query_imgurl="select imgurl from weixin_commonshop_product_imgs where isvalid=true and product_id=".$o2_pid." limit 0,1";
								$result_imgurl = mysql_query($query_imgurl) or die('Query_imgurl failed: ' . mysql_error());
								while ($row_imgurl = mysql_fetch_object($result_imgurl)) {
									$o2_imgurl = $row_imgurl->imgurl;
								}
								if(empty($o2_imgurl)){
									$o2_imgurl = "../../../common/images_V6.0/contenticon/pic_miss.png";
								}
							}							
							/* 产品图片 End */
							
							/* 产品属性 */
							$o2_prvstr="";
							if(!empty($o2_prvalue)){
								$o2_prvalue=str_replace("|","",$o2_prvalue);
								$o2_prvarr= explode("_",$o2_prvalue);								
								for($i=0;$i<count($o2_prvarr);$i++){
									$o2_prvid = $o2_prvarr[$i];
									if($o2_prvid>0){
										$query_pros = "SELECT name from weixin_commonshop_pros where isvalid=true and id=".$o2_prvid;
										$result_pros = mysql_query($query_pros) or die('Query_pros failed: ' . mysql_error());
										while ($row_pros = mysql_fetch_object($result_pros)) {
										   $prname     = $row_pros->name;
										   $o2_prvstr .= $prname."  ";
										}
									}
								}	

								/* 多属性价格属性 */
								$query_prod2="select foreign_mark from weixin_commonshop_product_prices where product_id=".$o2_pid." and proids='".$o2_prvalue."'";
								$result_prod2 = mysql_query($query_prod2) or die('Query_prod2 failed: ' . mysql_error());
								while ($row_prod2 = mysql_fetch_object($result_prod2)) {
									 $o2_foreign_mark = $row_prod2->foreign_mark;
								}								
								/* 多属性价格属性 End */								
								
							}							
		 			 		/* 产品属性 End */	 
			 ?>
			 <tr class="CP_table_chanpina">			 			 
				<td class="CP_table_chanpina_one">
					<img src="<?php echo "http://".$http_host.$o2_imgurl; ?>" />
					<span class="CP_table_chanpina_onep">
						<p><b><?php echo $o2_nane; ?></b></p>
						<p><b>¥<?php echo number_format($o2_totalprice,2); ?></b><span class="CP_table_chanpina_onepa"> 数量：<b><?php echo $o2_rcount; ?></b></span></p>						
						<p>
							<span class="CP_table_chanpina_onepa">属性:<?php echo $o2_prvstr; ?> </span>
							<span class="CP_table_chanpina_onepa">外部标识:<?php echo $o2_foreign_mark; ?> </span>
						</p>
					</span>
				</td>
				<?php if($o2_num==0){ ?>
				<td class="CP_table_chanpina_two" rowspan="<?php echo $o2_rows; ?>">
					<p><?php echo $o_expressName; ?>
					<a title="微信对话" href="../../../weixin_inter/send_to_msg.php?fromuserid=<?php echo $o_weixin_fromuser; ?>&customer_id=<?php echo $customer_id_en; ?>" ><i class="order-comment"></i></a>
					</p>
					<p><?php echo $o_expressPhone; ?></p>
				</td>
				<td class="CP_table_chanpina_three" id="table_three_<?php echo $o_batchcode; ?>" rowspan="<?php echo $o2_rows; ?>">
					<b>¥<?php echo number_format($o_totalprice_last,2)."元".$changeprice_str; ?></b><br/><span><?php if($o_express_price>0){ echo "(含运费 ¥". $o_express_price ."元)"; }else{ echo "免邮"; } ?></span>
				</td>
				<td class="CP_table_chanpina_four" id="table_four_<?php echo $o_batchcode; ?>" rowspan="<?php echo $o2_rows; ?>">
					<p class="CP_table_chanpina_fourp"><?php echo $sendstatusstr; ?></p>
					<?php if(!empty($confirm_sendtimestr)){  echo $confirm_sendtimestr; } ?>
					<?php if(!empty($confirm_receivetimestr)){  echo $confirm_receivetimestr; } ?>
				</td>
				<td class="CP_table_chanpina_five" id="table_five_<?php echo $o_batchcode; ?>" rowspan="<?php echo $o2_rows; ?>"><?php echo $o_statusstr; ?></td>				
				<td class="CP_table_chanpina_five" rowspan="<?php echo $o2_rows; ?>">
					<?php 
					
					
					if($o_exp_user_id>0){ echo '<p>分销商:<a title="分销商" href="../../Users/promoter/promoter.php?search_user_id=' . $o_exp_user_id . '&customer_id=' . $customer_id_en . '">' . $exp_user_name . '</a> <a title="微信对话" href="../../../weixin_inter/send_to_msg.php?fromuserid=' . $exp_fromuser . '&customer_id=' . $customer_id_en . '"><i class="order-comment"></i></a></p>'; } 
					
					if($o_agent_id>0){ echo '<p>代理商:<a title="代理商" href="../../Mode/agent/agent.php?search_user_id=' . $o_agent_id . '&customer_id=' . $customer_id_en . '">' . $agent_username . '</a> <a title="微信对话" href="../../../weixin_inter/send_to_msg.php?fromuserid=' .$agent_weixin_fromuser. '&customer_id=' . $customer_id_en . '"><i class="order-comment"></i></a></p>';}
					if($o_supply_id>0){ 
					$p_str = '<p>供应商:<a title="供应商" href="../../Mode/supplier/supply.php?search_user_id=' . $o_supply_id . '&customer_id=' . $customer_id_en . '">' . $supply_username.'</a>';
					if(!empty($supply_weixin_fromuser)){
						$p_str .= '<a title="微信对话" href="../../../weixin_inter/send_to_msg.php?fromuserid=' .$supply_weixin_fromuser. '&customer_id=' . $customer_id_en . '"><i class="order-comment"></i></a>';   
					}
					$p_str .= '</p>';
					echo $p_str; }	
					if($o_supply_id<0 && $o_agent_id<0 && $o_exp_user_id<0){ echo "<p>无</p>";} 
					?>
				</td>
				<td class="CP_table_chanpina_six" rowspan="<?php echo $o2_rows; ?>">
					<a title="订单详情" onclick="showDetail('<?php echo $o_batchcode; ?>')" ><img src="../../../common/images_V6.0/operating_icon/icon44.png" /></a>															
					<a title="订单日志" onclick="showLog('<?php echo $o_batchcode; ?>')" ><img src="../../../common/images_V6.0/operating_icon/icon11.png" /></a>
					<?php 
				if($o2_isvalid==1){
					if($o_sendstatus==1){  
					?> 
					<a title="延期收货" onclick="showDate('<?php echo $o_batchcode; ?>',<?php echo $o2_rows; ?>)" ><img src="../../../common/images_V6.0/operating_icon/icon53.png" /></a> 					
					<?php  }   if($o_status==0 and $o_sendstatus==0){ ?>
					<a title="修改收件地址" onclick="showAddress('<?php echo $o_batchcode; ?>',<?php echo $o2_rows; ?>)" ><img src="../../../common/images_V6.0/operating_icon/icon52.png" /></a> 
					<?php } 
					if($o_status==0 and $o_paystatus==0 ){ 
						if($o_agentcont_type==0 and $o_supply_id<0){
					?> 
					
					<a title="修改价格" onclick="showPrice('<?php echo $o_batchcode; ?>',<?php echo $o2_rows; ?>)" ><img src="../../../common/images_V6.0/operating_icon/icon05.png" /></a> 
					
					<?php } ?>
					
					<a title="确认支付" data-batchcode="<?php echo $o_batchcode;?>" data-totalprice="<?php echo $o_totalprice_last;?>" onclick="payOrder(this)" ><img src="../../../common/images_V6.0/operating_icon/icon39.png" /></a>
					
					<?php } 
					
					if($o_sendstatus==0 and ($o_paystatus==1 or $o_paystyle=="货到付款") and $o_supply_id<0 and $o_open_sendway==0){ ?>
					
					<a id="button_delivery_<?php echo $o_batchcode; ?>" title="发货"  onclick="showDelivery('<?php echo $o_batchcode; ?>')" ><img src="../../../common/images_V6.0/operating_icon/icon42.png" /></a>
					
					<?php } 
					if($o_status==0 and $o_paystatus==1 and ($o_sendstatus==2 or $o_sendstatus==4 or $o_sendstatus==6) ){ ?>	
					
					<a title="确认完成" data-batchcode="<?php echo $o_batchcode;?>" data-totalprice="<?php echo $o_totalprice_last;?>" onclick="confirmOrder(this)" ><img src="../../../common/images_V6.0/operating_icon/icon23.png" /></a>
					
					<?php if($o_sendstatus!=4 and $o_sendstatus!=6){?>
					
					<a title="红包确认" href="order_send_redpack.php?customer_id=<?php echo $customer_id_en; ?>&batchcode=<?php echo $o_batchcode; ?>" ><img src="../../../common/images_V6.0/operating_icon/icon55.png" /></a>
					
					<?php }} ?>

					<?php if($o_aftersale_state == 1){ ?>
					
						<a title="维权管理" data-batchcode="<?php echo $o_batchcode;?>"  onclick="returnAftersale(this)" ><img src="../../../common/images_V6.0/operating_icon/icon58.png" /></a>
						
					<?php }else if($o_aftersale_state == 2){ ?>
					
						<a title="确认维权完毕" data-batchcode="<?php echo $o_batchcode;?>" onclick="confirmAftersale(this)" ><img src="../../../common/images_V6.0/operating_icon/icon59.png" /></a>  
						
					<?php } ?>
					
					<?php 
					if($o_sendstatus==3 and $o_supply_id<0 and $o_open_sendway==0){ 
					
						if($o_return_status == 0){  //申请退货后审批
						
						?>
						<a title="退货管理" data-batchcode="<?php echo $o_batchcode;?>" data-reason="<?php echo $o_backgoods_reason;?>"  onclick="returnGood(this,<?php echo $o_return_type;?>)" ><img src="../../../common/images_V6.0/operating_icon/icon56.png" /></a>
						<?php
						
						}else if($o_return_status == 2){ //同意退货						
							if($o_return_type == 0 ){ //退货，仅退款

							?>
							<a title="确定退款" data-refund-batchcode="<?php echo $o_batchcode;?>"  onclick="showGoodRefund('<?php echo $o_batchcode; ?>',1)" ><img src="../../../common/images_V6.0/operating_icon/icon57.png" /></a>
							<?php	
	
							}else if($o_return_type == 2){ // 申请换货并且商家已同意

							?>
							<a title="确定已退货" data-batchcode="<?php echo $o_batchcode;?>" onclick="confirmGoodRefund(this)" ><img src="../../../common/images_V6.0/operating_icon/icon56.png" /></a>
							<a id="button_delivery_<?php echo $o_batchcode; ?>" title="发货" onclick="showDelivery('<?php echo $o_batchcode; ?>')" ><img src="../../../common/images_V6.0/operating_icon/icon42.png" /></a>
							<?php								
							
							}
							
						}else if($o_return_status == 5 ){ //退货并且用户已发货						
							if($o_return_type == 1){  //申请退货或换货都可以显示确定

							?>
							<a title="确定已退货" data-refund-all-batchcode="<?php echo $o_batchcode;?>" onclick="showGoodAll('<?php echo $o_batchcode;?>')" ><img src="../../../common/images_V6.0/operating_icon/icon56.png" /></a>
							<?php								
							
							} 
							if($o_return_type == 2){    //或可以直接发货  
							
							?>
							<a title="确定已收到退货" data-batchcode="<?php echo $o_batchcode;?>" onclick="confirmGoodRefund(this)" ><img src="../../../common/images_V6.0/operating_icon/icon56.png" /></a>
							<a id="button_delivery_<?php echo $o_batchcode; ?>" title="发货" onclick="showDelivery('<?php echo $o_batchcode; ?>')" ><img src="../../../common/images_V6.0/operating_icon/icon42.png" /></a>
							<?php	
							
							}
						}else if($o_return_status == 6){ //退货并且商家已确认收货 , 进行退款操作						
							if($o_return_type == 1){
										
							?>
							<a title="确定退款" data-refund-batchcode="<?php echo $o_batchcode;?>"  onclick="showGoodRefund('<?php echo $o_batchcode; ?>',1)" ><img src="../../../common/images_V6.0/operating_icon/icon57.png" /></a>
							<?php											
										
							}else if($o_return_type == 2){ 

							?>
							<a id="button_delivery_<?php echo $o_batchcode; ?>" title="发货" onclick="showDelivery('<?php echo $o_batchcode; ?>')" ><img src="../../../common/images_V6.0/operating_icon/icon42.png" /></a>
							<?php								
							
							}
						}						
								
					} ?>					
					
					<?php 
					// 由供应商/代理商发货  ，申请退货（仅退款） 。 代理商已同意后/
					if($o_sendstatus == 3 and ($o_supply_id > 0 || $o_open_sendway > 0)){ 
						if($o_return_status == 2){ //同意退货
							if($o_return_type == 0 ){ //退货，仅退款

							?>
							<a title="确定退款" data-refund-batchcode="<?php echo $o_batchcode;?>"  onclick="showGoodRefund('<?php echo $o_batchcode; ?>',1)" ><img src="../../../common/images_V6.0/operating_icon/icon57.png" /></a>
							<?php								
							
							}
						}else if($o_return_status == 6){  //供应商已确认收货后 						

						?>
						<a title="确定退款" data-refund-batchcode="<?php echo $o_batchcode;?>"  onclick="showGoodRefund('<?php echo $o_batchcode; ?>',1)" ><img src="../../../common/images_V6.0/operating_icon/icon57.png" /></a>
						<?php	
						
						}
					}					
 
					if($o_sendstatus==5){
						if($o_return_status == 0){  //申请退款后审批

						?>
						<a title="退款管理" data-batchcode="<?php echo $o_batchcode;?>"  onclick="returnMoney(this)" ><img src="../../../common/images_V6.0/operating_icon/icon56.png" /></a>
						<?php
						
						}else if($o_return_status == 8){ //退款

						?>
						<a title="确定退款" data-refund-batchcode="<?php echo $o_batchcode;?>"  onclick="showGoodRefund('<?php echo $o_batchcode; ?>',0)" ><img src="../../../common/images_V6.0/operating_icon/icon57.png" /></a>
						<?php							
						
						}							
					} 
										
					if(($o_status==0 and $o_paystatus==0) or $o_status==1 or $o_status==-1){
						if($is_shopgeneral==0 or $is_generalcustomer==1){ 
					?>					
					<a title="删除" data-batchcode="<?php echo $o_batchcode;?>"  onclick="delOrder(this)" ><img src="../../../common/images_V6.0/operating_icon/icon04.png" /></a>
					<?php 
						}
					}
				}
				
				if($o_paystatus==1){
				?>					
				
				<a title="返佣记录" href="order_rebate_log.php?batchcode=<?php echo $o_batchcode; ?>&customer_id=<?php echo passport_encrypt($customer_id)?>=" ><img src="../../../common/images_V6.0/operating_icon/icon51.png" /></a>

				<?php 
				}				
			?>					
				</td>
				<?php $o2_num++; } ?>
			 </tr>
			 <?php } ?>
		  
          
          <!--订单详情开始·定位属性-->
          <tr class="WSY_positiontrhide">
          	<td colspan="11" class="order_td">
            	<div class="order order_hide div_show" id="order_<?php echo $o_batchcode; ?>" >
					<i class="guanbi" onclick="hideDetail()" ><img class="WSY_modifypimg" src="../../../common/images_V6.0/contenticon/gbicon.png" alt=""></i><!--点击关闭信息-->
                	<dl class="order_dl01">
                        <dt><a>订单信息</a></dt>
                        <div class="order_div">
                            <dd><b>订单号：</b><span><?php echo $o_batchcode; ?></span></dd>
                            <dd><b>下单时间：</b><span><?php echo $o_createtime; ?></span></dd>
                            <dd><b>支付时间：</b><span><?php echo $o_paytime; ?></span></dd>
                            <dd><b>支付方式：</b><span><?php echo $o_paystyle; ?></span></dd>
								<?php if(!empty($o_sendstyle)){  ?>                            
                            <dd><b>收货方式：</b><span><?php echo $o_sendstyle; ?></span>
								<?php if(!empty($o_store_name)){
								?>
								<span><a href='../stores/shops.php?customer_id=<?php echo $customer_id_en;?>&search_name=<?php echo $o_store_name;?>' class="WSY_red">
								[<?php echo $o_store_name;?>]
								</a></span>
								<?php } ?>
							</dd>							
								<?php } if(!empty($agent_username)){   ?>
									<dd><b>代理商：</b><span><?php echo $agent_username; ?></span></dd>	 
								<?php } if(!empty($supply_username)){  ?>                            
                            <dd><b>供应商：</b><span><?php echo $supply_username; ?></span></dd> 
								<?php } ?>                            
                        </div>
                        <div class="order_div">
								<dd><b>订单金额：</b><span class="WSY_red">￥<?php echo $o_totalprice; ?>元</span></dd>
								<dd><b>买家姓名：</b><span><?php echo $o_name; ?></span></dd>
								<dd><b>微信名称：</b><span><?php echo $o_weixin_name; ?></span></dd>
								<dd><b>买家电话：</b><span><?php echo $o_phone; ?></span></dd>
								<?php if($o_CouponPrice>0){?><dd><b>代金券：</b><span class="WSY_red"><?php  echo  "￥".$o_CouponPrice."元";?></span></dd><?php }?>
								<dd><b>邮费：</b><span class="WSY_red"><?php if($o_express_price>0) echo  "￥".$o_express_price."元";else echo "免邮"; ?></span></dd>
								<dd><b>实付金额：</b><span class="WSY_red" id="order_price_<?php echo $o_batchcode; ?>">￥<?php echo number_format($o_totalprice_last,2); ?>元</span></dd>  
                        </div>
                    </dl>
                    <dl class="order_dl02">
                        <form>
                        <dt><a>收货信息</a></dt>
                        <div class="order_div01">
                            <dd><b>收货人：</b><span data-name="<?php echo $o_batchcode; ?>"><?php echo $o_expressName; ?></span></dd>
                            <dd><b>收货电话：</b><span data-phone="<?php echo $o_batchcode; ?>"><?php echo $o_expressPhone; ?></span></dd>
                            <dd><b>收货地址：</b><span title="<?php echo $o_location_p . $o_location_c . $o_location_a . $o_expressAddress; ?>" class="order_span_break" data-add="<?php echo $o_batchcode; ?>"><?php echo $o_location_p . $o_location_c . $o_location_a . $o_expressAddress; ?></span></dd>  
                            <dd><b>订单备注：</b><span class="order_span_break" ><?php echo $o_remark; ?></span></dd>
							<dd class="WSY_bottonli" style="float:none;">
								<b>商家备注：</b>
								<textarea class="merchant_remark_<?php echo $o_batchcode;?>  merchant_remark" name="merchant_remark"  <?php if(!empty($o2_merchant_remark)){?>disabled="disabled" <?php } ?>><?php echo $o2_merchant_remark; ?></textarea>

								<input type="button" style="<?php if(empty($o2_merchant_remark)){?>display:none<?php } ?>" class="change_merchant_remark<?php echo $o_batchcode?> change_remark" value="修改" onclick="change_merchant_remark('<?php echo $o_batchcode;?>')">
								<input type="button"  style="<?php if(!empty($o2_merchant_remark)){?>display:none<?php } ?>" class="save_merchant_remark_<?php echo $o_batchcode?> change_remark" type="button" value="保存" onclick="save_merchant_remark('<?php echo $o_batchcode;?>')">
								
							</dd>
                            <dd><b>物流公司：</b><span id="express_id2_<?php echo $o_batchcode; ?>">
									<?php 
									if($o_sendstatus==0){
										echo "未发货";
									}else{
										switch($o_express_id){
											case -2: echo "顺丰进口";break;
											case 0: echo "虚拟发货";break;
											default:
												if($o_supply_id>0){ 
													$query_express = 'SELECT name FROM weixin_expresses_supply where isvalid=true and supply_id='.$o_supply_id.' and customer_id='.$customer_id.' and id ='.$o_express_id;
												}else{
													$query_express = 'SELECT name FROM weixin_expresses where isvalid=true and customer_id='.$customer_id.' and id ='.$o_express_id;
												}
												$result_express = mysql_query($query_express) or die('Query_express_send failed: ' . mysql_error()); 
												while ($row_express = mysql_fetch_object($result_express)) {
													$e_name = $row_express->name;
													echo $e_name;
											    }
										}
									}
									?>
                            </span></dd>
                        </div>
                        <div class="order_div01">
								<?php if(!empty($o_identity)){ ?><dd><b>身份证号：</b><span><?php echo $o_identity; ?></span></dd><?php } ?>	
                           <dd><b>物流单号：</b><span><input type="text" disabled="disabled"  id="express_num2_<?php echo $o_batchcode; ?>" value="<?php echo $o_expressnum;?>" ></span>
								<?php if(!empty($o_expressnum)){?>
								<span class="order_kuaidi" onclick="KuaiDi100(<?php echo $o_batchcode; ?>)" >(点击查看物流)</span><?php }	?>							
								</dd>								
								<dd class="order_ddleft"><b>物流备注：</b>
								<span><textarea class="textarea01" disabled="disabled" id="express_remark2_<?php echo $o_batchcode; ?>"></textarea></span>
								</dd>
                        </div>
                        </form>
                    </dl>

						<?php  if($o_supply_id>0){  ?>
                    <dl class="order_dl04">
                        <dt><a>留言信息</a><i></i></dt>
                        <dd class="order_dd_hidden_<?php echo $o_batchcode; ?>" style="max-height: 150px;overflow-y: auto;width: 98%;display: inline-block;">
							<?php 
								   $query_msg = 'SELECT message,createtime,type,sub_supplier_id FROM weixin_commonshop_supply_message where isvalid=true and batchcode='.$o_batchcode;
								   $result_msg = mysql_query($query_msg) or die('Query_msg failed: ' . mysql_error()); 
								   while ($row_msg = mysql_fetch_object($result_msg)) {
									  $msg_message= $row_msg->message;
									  $msg_createtime = $row_msg->createtime;
									  $msg_type = $row_msg->type;
									  $sub_supplier_id = $row_msg->sub_supplier_id;
							?>						
                            <div class="order_dl04_div">
                                <h3>
										<?php
										if($msg_type==1){
											$msg_user = $supply_username;
										}else if ($msg_type==2){
											$query_subname = 'SELECT username FROM weixin_commonshop_supply_users where id='.$sub_supplier_id;
											$result_subname = mysql_query($query_subname) or die("L2380 Query_subname error : ".mysql_error());
											$msg_user = $supply_username.":".mysql_result($result_subname,0,0);	
										}else{
											$msg_user = "<a>我</a>";
										}
										echo "<a>" . $msg_user ."</a>  留言于  ".$msg_createtime;
										?>								
							    </h3> 
                                <p style="text-align: center;"><?php echo $msg_message; ?></p>
                            </div>
								  <?php  }  ?>		
                        </dd>

						<dd><textarea class="textarea02" name="order_talk_<?php echo $o_batchcode; ?>" id="order_talk_<?php echo $o_batchcode; ?>" placeholder="输入您想留言的信息"></textarea></dd>
						
						<input type="hidden" name="supply_id_<?php echo $o_batchcode; ?>" id="supply_id_<?php echo $o_batchcode; ?>" value="<?php echo $o_supply_id; ?>" />
						<dd class="WSY_bottonli" style="border:none">
							<input type="button" value="留言" onclick="message(<?php echo $o_batchcode; ?>);">
							<input type="button" value="取消" onclick="hideDetail()">
						</dd>

                    </dl>
						<?php  }  ?>																
             </div>
				<!--订单详情End-->
				
				<?php if($o_sendstatus == 0 or ($o_sendstatus == 3 && $o_return_type == 2) ){ ?>
				<!--订单发货-->
            	<div class="order order_delivery_dl order_hide div_show" id="delivery_<?php echo $o_batchcode; ?>"  >
					<i class="guanbi" onclick="hideDetail()" ><img class="WSY_modifypimg" src="../../../common/images_V6.0/contenticon/gbicon.png" alt=""></i><!--点击关闭信息-->
                    <dl>
                        <form>
                        <dt><a>收货信息</a></dt>
                        <div class="order_div01">
                            <dd><b>收货人：</b><span data-name="<?php echo $o_batchcode; ?>"><?php echo $o_expressName; ?></span></dd>
                            <dd><b>收货电话：</b><span data-phone="<?php echo $o_batchcode; ?>"><?php echo $o_expressPhone; ?></span></dd>
                            <dd><b>收货地址：</b><span title="<?php echo $o_location_p . $o_location_c . $o_location_a . $o_expressAddress; ?>" class="order_span_break" data-add="<?php echo $o_batchcode; ?>"><?php echo $o_location_p . $o_location_c . $o_location_a . $o_expressAddress; ?></span></dd> 
                            <dd><b>订单备注：</b><span class="order_span_break" ><?php echo $o_remark; ?></span></dd>
							<dd class="WSY_bottonli" style="float:none;">
								<b>商家备注：</b>
								<textarea class="merchant_remark_<?php echo $o_batchcode;?>  merchant_remark" name="merchant_remark"  disabled="disabled" ><?php echo $o2_merchant_remark; ?></textarea>
								
							</dd>
                            <dd><b>物流公司：</b><span>
                                <select id="express_id_<?php echo $o_batchcode; ?>">
									<?php if($open_virtual_cust == 1 ){ ?>
                                    <option value="0">虚拟发货</option>	
									<?php } ?>
										   <?php 
										   $sf_id = -1;
										   $query_sf = 'SELECT id FROM sf_import where ison=1 and customer_id='.$customer_id." limit 1";
										   $result_sf = mysql_query($query_sf) or die('Query sf_import: ' . mysql_error()); 
										   while ($row_sf = mysql_fetch_object($result_sf)) {
											  $sf_id= $row_sf->id;
										   }										   
										   if($sf_id>0 ){ ?> 
										   <option value="-2"  <?php if($o_express_id == -2){ ?>selected<?php } ?> >顺丰进口</option> 
										   <?php } 										   
										   if($o_supply_id>0){ 
												$query_express = 'SELECT id,name,price FROM weixin_expresses_supply where isvalid=true and supply_id='.$o_supply_id.' and customer_id='.$customer_id;
										   }else{
												$query_express = 'SELECT id,name,price FROM weixin_expresses where isvalid=true and customer_id='.$customer_id;   
										   }
										   $result_express = mysql_query($query_express) or die('Query_express failed: ' . mysql_error()); 
										   while ($row_express = mysql_fetch_object($result_express)) {
											  $e_id= $row_express->id;
											  $e_name = $row_express->name;
										   ?>
											<option value="<?php echo $e_id; ?>" <?php if($o_express_id == $e_id){ ?>selected<?php } ?>><?php echo $e_name; ?></option>
										   <?php } ?>									
                                </select>
                            </span></dd>
                        <dd class="WSY_bottonli con-button"><input  type="button" value="确定发货" class="order_delivery" data-totalprice="<?php echo $o_totalprice_last;?>" data-batchcode="<?php echo $o_batchcode;?>" ></dd>
                        <dd class="WSY_bottonli con-button2"><input  type="button" value="取消" onclick="hideDetail()"></dd>
                        </div>
                        <div class="order_div01">
								  <?php if(!empty($o_identity)){ ?><dd><b>身份证号：</b><span><?php echo $o_identity; ?></span></dd><?php } ?>
                            <dd><b>物流单号：</b><span><input type="text" placeholder="虚拟发货可不用填" id="express_num_<?php echo $o_batchcode; ?>" ></span></dd>
                            <dd class="order_ddleft"><b>物流备注：</b>
								 <span><textarea class="textarea01" placeholder="写上要留言的信息" id="express_remark_<?php echo $o_batchcode; ?>"></textarea></span>
								 </dd>
                        </div>
                        </form>
                    </dl>					
              </div>	
				<!--订单发货End-->
				<?php  }  
				if($o_status==0 and $o_sendstatus==0){ ?>
				<!--修改收件地址-->
            	<div class="WSY_modifydiv order_hide div_show" id="address_<?php echo $o_batchcode; ?>" > 
                    <dl class="order_dl_gaijia">
                        <dt><a>修改收件地址</a></dt>
                        <dd class="order_dl_add"><b>收件人姓名：</b><span><input type="text" id="address_name_<?php echo $o_batchcode; ?>" value="<?php echo $o_expressName; ?>"></span></dd>
                        <dd class="order_dl_add"><b>收件人手机：</b><span><input type="text" id="address_phone_<?php echo $o_batchcode; ?>" value="<?php echo $o_expressPhone; ?>"></span></dd>
                        <dd class="order_dl_add"><b>省级：</b><span><select name="address_p_<?php echo $o_batchcode; ?>" id="address_p_<?php echo $o_batchcode; ?>" ></select></span></dd>
                        <dd class="order_dl_add"><b>市级：</b><span><select name="address_c_<?php echo $o_batchcode; ?>" id="address_c_<?php echo $o_batchcode; ?>" ></select></span></dd>
                        <dd class="order_dl_add"><b>区级：</b><span><select name="address_a_<?php echo $o_batchcode; ?>" id="address_a_<?php echo $o_batchcode; ?>" ></select></span></dd>
                        <dd class="order_dl_add"><b>详细地址：</b><span><input type="text" name="address_add_<?php echo $o_batchcode; ?>" id="address_add_<?php echo $o_batchcode; ?>" value="<?php echo $o_expressAddress; ?>"></span></dd>
                        <dd class="WSY_bottonli con-button"><input  type="button" value="确定" class="order_add" data-batchcode="<?php echo $o_batchcode;?>"></dd>
                        <dd class="WSY_bottonli con-button2"><input  type="button" value="取消" onclick="hideDetail()"></dd>
                    </dl>
             </div>	
				<script type="text/javascript">
					new PCAS('address_p_<?php echo $o_batchcode; ?>', 'address_c_<?php echo $o_batchcode; ?>', 'address_a_<?php echo $o_batchcode; ?>', '<?php echo $o_location_p; ?>', '<?php echo $o_location_c; ?>', '<?php echo $o_location_a; ?>');
				</script>			 
				<!--修改收件地址End-->
				<?php  }  	
				if($o_sendstatus==1){  ?> 
				<!-- 延期收货 -->
            	<div class="WSY_modifydiv order_hide" id="date_<?php echo $o_batchcode; ?>" > 
                    <dl class="order_date">
                       <dt><a>延期收货</a></dt>
						   <dd><b style="width:135px;">当前自动收货时间：</b><span id="date_time_<?php echo $o_batchcode; ?>" ><?php echo $row_order->auto_receivetime;?></span></dd>
                       <dd><b style="width:65px;">延期</b><span>
									<input type="text" id="data_delay_<?php echo $o_batchcode; ?>" value="3"></span>
									<b style="width:40px;">天</b>
							</dd>
                        <dd class="WSY_bottonli con-button"><input  type="button" value="确定" class="order_delay" data-batchcode="<?php echo $o_batchcode;?>" data-is_delay="<?php echo $o_is_delay;?>"></dd>
                        <dd class="WSY_bottonli con-button2"><input  type="button" value="取消" onclick="hideDetail()"></dd>
                    </dl>
             </div>					
				<!-- 延期收货End -->
				<?php }  
				if($o_sendstatus==3 ||( $o_sendstatus==5 && ($o_return_status==8 or $o_return_status==0 or $o_return_status==6) )){
				?> 				
				<!-- 退款 -->
            	<div class="WSY_modifydiv order_hide div_show" id="refund_<?php echo $o_batchcode; ?>" > 
                    <dl class="order_date">
                       <dt><a>退款</a></dt>
						   <dd><b style="width:135px;">当前申请金额：</b><span><?php echo $row_order->return_account;?></span></dd>
                       <dd><b style="width:135px;">实际退款金额：</b><span>
									<input type="text" id="good_refund_<?php echo $o_batchcode;?>" value="<?php echo $row_order->return_account;?>"></span>
									<b style="width:40px;">元</b>
							</dd>
                        <dd class="WSY_bottonli con-button"><input  type="button" value="确定" class="good_refund" data-batchcode="<?php echo $o_batchcode;?>" data-money="<?php echo $row_order->return_account;?>" ></dd>
                        <dd class="WSY_bottonli con-button2"><input  type="button" value="取消" onclick="hideDetail()"></dd>
                    </dl>
             </div>					
				<!-- 退款 End -->				
				<?php }  ?> 	

				<?php if($o_status==0 and $o_paystatus==0 and $o_agentcont_type==0 and $o_supply_id<0){ ?> 
				<!--修改价格·定位属性-->			
					<div class="WSY_modifydiv order_hide div_show" id="price_<?php echo $o_batchcode; ?>" > 
						<dl class="order_dl_gaijia">
							<dt><a>修改价格</a></dt>
							<dd><b>订单价格：</b><span class="WSY_red">￥<?php echo number_format($o_totalprice,2); ?>元</span></dd>
							<dd><b>现价：</b><span><input type="text" value="<?php echo $o_totalprice; ?>" id="change_price_<?php echo $o_batchcode; ?>"></span></dd>
							<dd class="WSY_bottonli con-button"><input  type="button" value="确定改价" class="order_price" data-batchcode="<?php echo $o_batchcode;?>" data-price="<?php echo $o_totalprice;?>"  ></dd>
							<dd class="WSY_bottonli con-button2"><input  type="button" onclick="hideDetail()" value="取消"></dd>
						</dl>
				 </div>
			  <!--修改价格·定位属性-->
				<?php } ?>				
				
				<!-- 退货(确认收到退货) -->
            	<div class="WSY_modifydiv order_hide div_show" id="refund_all_<?php echo $o_batchcode; ?>" > 
                    <dl class="order_refund" style="width:25%">
                       <dt><a style="width:120px">退货(已收到退货)</a></dt>
						   <dd><b style="width:135px;">当前申请金额：</b><span><?php echo $row_order->return_account;?></span></dd>
                       <dd><b style="width:135px;">实际可退款金额：</b><span>
									<input type="text" id="good_refund_money_<?php echo $o_batchcode;?>" value="<?php echo $row_order->return_account;?>"></span>
									<b style="width:40px;">元</b>
							</dd>
                       <dd><b style="width:135px;">备注：</b><span>
									<textarea class="textarea01" id="good_refund_remark_<?php echo $o_batchcode;?>" ></textarea></span>
							</dd>							
                        <dd class="WSY_bottonli con-button"><input  type="button" value="确定" class="good_refund_all" data-batchcode="<?php echo $o_batchcode;?>" data-money="<?php echo $row_order->return_account;?>" ></dd>
                        <dd class="WSY_bottonli con-button2"><input  type="button" value="取消" onclick="hideDetail()"></dd>
                    </dl>
             </div>					
				<!-- 退货(确认收到退货) End -->					
				
				<!-- 订单日志 -->
            	<div class="WSY_modifydiv order_hide div_show" id="log_<?php echo $o_batchcode; ?>" > 
                    <dl class="order_log">
                        <dt><a>订单日志</a></dt>
							<?php 
								$query_log = "select operation,descript,operation_user,createtime from weixin_commonshop_order_logs where isvalid = true and batchcode='".$o_batchcode."'";
								$result_log = mysql_query($query_log) or die("Query_log failed : ".mysql_error());				
								while($row_log = mysql_fetch_object($result_log)){
							?>						
							<dd>
								<b>时间：</b><span><?php echo $row_log->createtime;?></span>
								<b>操作：</b>
								<span>
							<?php 
									$op_str = "";
									$op = $row_log->operation;									//0：下单；1：取消；2：支付；3：修改价格；4：发货：5：申请延期；6：确认延期；7：确认收货；8：退货；9：退货审批；10：退款；11：退款审批；12：退款；13：用户退货填单；14：商家确认退货；';
									switch($op){
										case 0 :$op_str = "下单";break;
										case 1 :$op_str = "取消";break;
										case 2 :$op_str = "支付";break;
										case 3 :$op_str = "修改价格";break;
										case 4 :$op_str = "发货";break;
										case 5 :$op_str = "申请延期";break;
										case 6 :$op_str = "确认延期";break;
										case 7 :$op_str = "确认收货";break;
										case 8 :$op_str = "退货";break;
										case 9 :$op_str = "退货审批";break;
										case 10 :$op_str = "退款";break;
										case 11 :$op_str = "退款审批";break;
										case 12 : $op_str = "退款操作";break;
										case 13 :$op_str = "用户退货填单";break;
										case 14 :$op_str = "商家确认退货";break;
										case 15 :$op_str = "退货完成";break;
										case 16 :$op_str = "确认完成";break;
										case 17 :$op_str = "订单评价";break;
										case 18 :$op_str = "申请维权";break;
										case 19 :$op_str = "维权审批";break;
										case 20 :$op_str = "维权处理";break;
										case 21 :$op_str = "微信退款";break;
										case 22 :$op_str = "订单删除";break;
									}
									echo $op_str;
								?>								
								</span>
								<b>描述：</b><span><?php echo $row_log->descript;?></span>
								<b>操作人：</b><span><?php echo $row_log->operation_user;?></span>
							</dd>
							<?php } ?>		
                    </dl>
             </div>	
				<!-- 订单日志End -->
				
            </td>
          </tr> 
			<?php  }  ?> 
		  
		</table>
	</div>
 
	<!--翻页开始-->
	<div class="WSY_page">
	</div>
	<!--翻页结束-->
 
     <!--表格结束-->
    </div>

   </div>
    <!--订单管理代码结束-->    

 
</div>
</div>
<script src="../../../js/fenye/jquery.page1.js"></script>
<script type="text/javascript">
//-------------上次文件效果
	$(function() {

		$("input[type=file]").change(function() {
			$(this).parents(".uploader").find(".filename").val($(this).val());
		});

		$("input[type=file]").each(function() {
			if ($(this).val() == "") {
				$(this).parents(".uploader").find(".filename").val("请选择文件...");
			}
		});

	});
</script>
<!--内容框架结束-->
<script>
<!-- 分页 --start--> 
	var customer_id = "<?php echo passport_encrypt($customer_id);?>";
	var pagenum = <?php echo $pagenum ?>;
	var count =<?php echo $page ?>;//总页数	
	//pageCount：总页数
	//current：当前页
	$(".WSY_page").createPage({
		pageCount:count,
		current:pagenum,
		backFn:function(p){
		var url="order.php?customer_id="+customer_id+"&pagenum="+p;	
		search_condition(url); 
	   }
	});
 
  function jumppage(){
	var a=parseInt($("#WSY_jump_page").val());
	if((a<1) || (a>count) || isNaN(a)){
		layer.alert('没有下一页了');
		return false;
	}else{
		var url="order.php?customer_id="+customer_id+"&pagenum="+a;	
		search_condition(url); 
	}
  }
<!-- 分页 --end-->


<!-- 自动刷新 -->
var refer_time=10;
var refer_left_time=0;
var refer_ing=false;
function auto_refer(){
	if($('#auto_refer').is(':checked')){
		if(refer_left_time<refer_time){
			$('#search_form div .WSY_righticon_li04 label').html('<span><strong>'+(refer_time-refer_left_time)+'</strong></span>秒后自动刷新');
			refer_left_time++;
		}else{
				url=window.location.href;
				if(url.indexOf("&isauto=1")==-1)
			　{
			　　url=window.location.href+"&isauto=1";
			　}
				location.href=url;
		}
	}else{
		$('#search_form div .WSY_righticon_li04 label').html('自动刷新订单');
		refer_left_time=0;
		refer_ing=false;
		url=window.location.href;
		if(url.indexOf("&isauto=1")>0)
		{
			url=url.replace("&isauto=1","");
			location.href=url;
		}		
	}
	setTimeout(auto_refer, 1000);
};
auto_refer();
<!-- 自动刷新 --end-->

<!-- 订单提醒 -->
function chkremind(){
	var urls='';
	if($('#order_remind').is(':checked')){
		urls='order_save_remind.php?op=1&customer_id='+customer_id+"&order_remind=1"; 
	}else{
		urls='order_save_remind.php?op=1&customer_id='+customer_id+"&order_remind=0"; 	
	}

	$.ajax({  
	type:"GET",  
	url:urls,  
	dataType:"jsonp",  
	success: function(results){  
		if(results[0].status==1){
			console.log("open remind");
		}else if(results[0].status==0){
			console.log("close remind");
			//parent.location.reload();
		}   
	} 	
	});	
}
<!-- 订单提醒 --end-->

<!-- 关闭订单详情 -->
function hideDetail(){
	$(".order_hide").fadeOut("slow"); 
}
<!-- 关闭订单详情 --end-->

<!-- 显示订单详情 -->
function showDetail(batchcode){
	var div = $("#order_"+batchcode);
	$(".div_show").not(div).hide();
	if(div.is(":hidden")){
		div.fadeIn("slow");
	}else{
		div.fadeOut("slow");
	}
}
<!-- 显示订单详情 --end-->

<!-- 显示订单日志 -->
function showLog(batchcode){
	var div = $("#log_"+batchcode);
	$(".div_show").not(div).hide();
	if(div.is(":hidden")){
		div.fadeIn("slow");
	}else{
		div.fadeOut("slow");
	}
}
<!-- 显示订单日志 --end-->

<!-- 显示订单发货 -->
function showDelivery(batchcode){
	var div = $("#delivery_"+batchcode);
	$(".div_show").not(div).hide();
	if(div.is(":hidden")){
		div.fadeIn("slow");
	}else{
		div.fadeOut("slow");
	}
}
<!-- 显示订单发货 --end-->

<!-- 显示订单发货 -->
function showPrice(batchcode,num){
	if(num>1){
		layer.alert("单个商品才能修改价格！");
		return;
	}		
	var div = $("#price_"+batchcode);
	$(".div_show").not(div).hide();
	if(div.is(":hidden")){
		div.fadeIn("slow");
	}else{
		div.fadeOut("slow");
	}
}
<!-- 显示订单发货 --end-->

<!-- 显示修改地址发货 -->
function showAddress(batchcode){	
	var div = $("#address_"+batchcode);
	$(".div_show").not(div).hide();
	if(div.is(":hidden")){
		div.fadeIn("slow");
	}else{
		div.fadeOut("slow");
	}
}
<!-- 显示订单发货 --end-->

<!-- 显示延期发货 -->
function showDate(batchcode){	
	var div = $("#date_"+batchcode);
	$(".div_show").not(div).hide();
	if(div.is(":hidden")){
		div.fadeIn("slow");
	}else{
		div.fadeOut("slow");
	}
}
<!-- 显示延期发货 --end-->


<!-- 显示退款 -->
var returntype=-1;
function showGoodRefund(batchcode,retype){
	returntype = retype;
	var div = $("#refund_"+batchcode);
	$(".div_show").not(div).hide();
	if(div.is(":hidden")){
		div.fadeIn("slow");
	}else{
		div.fadeOut("slow");
	}
}
<!-- 显示退款 --end-->

<!-- 显示退货确认 -->
function showGoodAll(batchcode){
	var div = $("#refund_all_"+batchcode);
	$(".div_show").not(div).hide();
	if(div.is(":hidden")){
		div.fadeIn("slow");
	}else{
		div.fadeOut("slow");
	}
}
<!-- 显示退货确认 --end-->

<!-- 修改地址 -->
$(".order_add").click(function(){	
	var batchcode = $(this).data('batchcode');
	layer.confirm('您确认要修改 订单:'+batchcode+' 的收货地址信息吗', {
		btn: ['修改收货信息','取消'] 
	}, function(confirm){

		var addressName = $("#address_name_"+batchcode).val();
		var addressPhone = $("#address_phone_"+batchcode).val();
		var addressP = $("#address_p_"+batchcode).val();
		var addressC = $("#address_c_"+batchcode).val();
		var addressA = $("#address_a_"+batchcode).val();
		var addressAdd = $("#address_add_"+batchcode).val(); 
		
		if(addressName=="" && addressPhone=="" && addressP=="" && addressC=="" && addressA=="" && addressAdd=="" ){ 
			layer.alert("请输入完整的收件人信息", function(index){layer.close(index);}); 
			return;  
		}
		layer.close(confirm);	  
		layer_open();			
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'addressName':addressName,'addressPhone':addressPhone,'addressP':addressP,'addressC':addressC,'addressA':addressA,'addressAdd':addressAdd,'op':"changeAdd"},
			dataType:"json",
			success: function(res){
				 layer.close(index_layer);
				if(res.status==0){
					$("span[data-add='"+batchcode+"']").text(addressP+addressC+addressA+addressAdd);			 		 
					$("span[data-name='"+batchcode+"']").text(addressName);			 		 
					$("span[data-phone='"+batchcode+"']").text(addressPhone);			 		 
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});		 
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],
			icon:1
		});
	});
			
});
<!-- 修改地址 End -->

<!-- 改价 -->
$(".order_price").click(function(){	
	var batchcode = $(this).data('batchcode');	
	layer.confirm('您确认要修改 订单:'+batchcode+' 的价格吗', {
		btn: ['改价','取消'] 
	}, function(confirm){ 

		var changePrice = $("#change_price_"+batchcode).val();
		if(isNaN(changePrice)){ 
			layer.alert("请输入正确的金额！");
			return;
		}		

		layer.close(confirm);	  
		layer_open();			
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'changePrice':changePrice,'op':"changPirce"},
			dataType:"json",
			success: function(res){
				 layer.close(index_layer);
				if(res.status==0){
					changePrice = parseFloat(changePrice).toFixed(2);
					$('#price_'+batchcode).find('dd').find('.WSY_red').text('￥'+changePrice+'元'); 
					$('#table_three_'+batchcode).find('b').html('￥'+changePrice+'元<span style="color:#dd514c;margin-left: 4px;">(改价后)</span>'); 
					$('#order_price_'+batchcode).text('￥'+changePrice+'元'); 
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});		 
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],
			icon:1
		});
	});
			
});
<!-- 改价 --end-->

<!-- 催单 -->
function callPay(batchcode,price){	
	layer.confirm('是否要对 订单:'+batchcode+' 进行催单？', {
		btn: ['催单','取消'] 
	}, function(confirm){ 

		layer.close(confirm);	  
		layer_open();			
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'price':price,'op':"callPay"},
			dataType:"json",
			success: function(res){
				layer.close(index_layer);
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});		 
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],
			icon:1
		});
	});
			
}
<!-- 催单 --end-->

<!-- 退货管理 -->
function returnGood(obj,return_type){	
	var batchcode = $(obj).data('batchcode');
	var reason = $(obj).data('reason');
	layer.confirm('顾客申请退货理由:'+reason+'<br/>请选择 订单:'+batchcode+' 的申请退货操作', {
		btn: ['同意退货','拒绝'] 
	}, function(confirm){
		
		layer.close(confirm);
		layer.prompt({
			formType: 0,
			title: '同意退货备注',			
			value: '同意退货申请'
		},function(reason, prompt, elem){
			layer.close(prompt);			
			layer_open();			
			$.ajax({
				url: "order.class.php",
				type:"POST",
				data:{'batchcode':batchcode,'reason':reason,'status':1,'op':"confirmReturnGood"},
				dataType:"json",
				success: function(res){
					 layer.close(index_layer);
					if(res.status==0){ 
						if(return_type==1){
							$(obj).remove(); 
						}else{
							$(obj).replaceWith('<a onclick="showGoodRefund('+batchcode+',1)" data-refund-batchcode="'+batchcode+'" title="确定退款"><img src="../../../common/images_V6.0/operating_icon/icon57.png"></a>'); 
						}						
						$("#table_four_"+batchcode+" p:first-child").append('<b style="color:#C9302C"> [已同意]</b>');
					}
					layer.alert(res.msg);
				},	
				error:function(){
					layer.close(index_layer);
					layer.alert("网络错误请检查网络");
				}						
			});				
			
		});  
			 
	}, function(confirm2){

		layer.close(confirm2);
		layer.prompt({
			formType: 0,
			title: '拒绝退货备注',			
			value: '拒绝退货申请'
		},function(reason, prompt, elem){
			layer.close(prompt);
			if(!reason || reason  == ""){
				layer.alert("驳回请输入理由！");
				return;
			}			
			layer_open();			
			$.ajax({
				url: "order.class.php",
				type:"POST",
				data:{'batchcode':batchcode,'reason':reason,'status':2,'op':"confirmReturnGood"},
				dataType:"json",
				success: function(res){
					 layer.close(index_layer);
					if(res.status==0){ 
						$(obj).remove(); 
						$("#table_four_"+batchcode+" p:first-child").html('<img src="../../../common/images_V6.0/contenticon/affirm-icon.png"><b style="color:#31B0D5"> 已发货</b>');
					}
					layer.alert(res.msg);
				},	
				error:function(){
					layer.close(index_layer);
					layer.alert("网络错误请检查网络");
				}						
			});				
			
		}); 
	
	});
			
}
<!-- 退货管理 --end-->

<!-- 退款管理 -->
function returnMoney(obj){	
	var batchcode = $(obj).data('batchcode');
	layer.confirm('请选择 订单:'+batchcode+' 的申请退款操作', {
		btn: ['同意退款','拒绝','取消'] 
	}, function(confirm){		
		layer.close(confirm);
		layer.prompt({
			formType: 0,
			title: '同意退款备注',			
			value: '同意退款申请'
		},function(reason, prompt, elem){
			layer.close(prompt);			
			layer_open();			
			$.ajax({
				url: "order.class.php",
				type:"POST",
				data:{'batchcode':batchcode,'reason':reason,'status':1,'op':"confirmReturnMoney"},
				dataType:"json",
				success: function(res){
					 layer.close(index_layer);
					if(res.status==0){ 
						$(obj).replaceWith('<a title="确定退款" data-refund-batchcode="'+batchcode+'" onclick="showGoodRefund('+batchcode+',0)"><img src="../../../common/images_V6.0/operating_icon/icon57.png"></a>'); 
						$("#table_four_"+batchcode+" p:first-child").html('<img src="../../../common/images_V6.0/contenticon/return-money.png"> <b style="color:#C9302C">顾客申请退款</b><b style="color:#C9302C"> [已同意]</b>');
					}
					layer.alert(res.msg);
				},	
				error:function(){
					layer.close(index_layer);
					layer.alert("网络错误请检查网络");
				}						
			});							
		});  			 
	}, function(confirm2){
		layer.close(confirm2);
		layer.prompt({
			formType: 0,
			title: '拒绝退款备注',			
			value: '拒绝退款申请'
		},function(reason, prompt, elem){
			layer.close(prompt);
			if(!reason || reason  == ""){
				layer.alert("驳回请输入理由！");
				return;
			}			
			layer_open();			
			$.ajax({
				url: "order.class.php",
				type:"POST",
				data:{'batchcode':batchcode,'reason':reason,'status':2,'op':"confirmReturnMoney"},
				dataType:"json",
				success: function(res){
					 layer.close(index_layer);
					if(res.status==0){ 
						$(obj).remove(); 
						$("#table_four_"+batchcode+" p:first-child").html('<img src="../../../common/images_V6.0/contenticon/notaffirm-icon.png"> <b>未发货</b>');
					}
					layer.alert(res.msg);
				},	
				error:function(){
					layer.close(index_layer);
					layer.alert("网络错误请检查网络");
				}						
			});							
		}); 	
	}, function(confirm3){
		layer.close(confirm2);
	});			
}
<!-- 退货管理 --end-->

<!-- 维权管理 -->
function returnAftersale(obj){	
	var batchcode = $(obj).data('batchcode');
	layer.confirm('请选择 订单:'+batchcode+' 的申请维权操作', {
		btn: ['同意维权','驳回'] 
	}, function(confirm){		
		layer.close(confirm);
		layer.prompt({
			formType: 0,
			title: '同意维权备注',			
			value: '同意维权申请'
		},function(reason, prompt, elem){
			layer.close(prompt);			
			layer_open();			
			$.ajax({
				url: "order.class.php",
				type:"POST",
				data:{'batchcode':batchcode,'reason':reason,'status':1,'op':"confirmReturnAftersale"},
				dataType:"json",
				success: function(res){
					 layer.close(index_layer);
					if(res.status==0){ 
						$(obj).replaceWith('<a title="确认维权完毕" data-batchcode="'+batchcode+'" onclick="confirmAftersale(this)"><img src="../../../common/images_V6.0/operating_icon/icon59.png"></a>'); 
						$("#table_five_"+batchcode+" .btn-warning").html('同意售后维权');
					}
					layer.alert(res.msg);
				},	
				error:function(){
					layer.close(index_layer);
					layer.alert("网络错误请检查网络");
				}						
			});							
		});  			 
	}, function(confirm2){
		layer.close(confirm2);
		layer.prompt({
			formType: 0,
			title: '驳回维权备注',			
			value: '驳回维权申请'
		},function(reason, prompt, elem){
			layer.close(prompt);
			if(!reason || reason  == ""){
				layer.alert("驳回请输入理由！");
				return;
			}			
			layer_open();			
			$.ajax({
				url: "order.class.php",
				type:"POST",
				data:{'batchcode':batchcode,'reason':reason,'status':2,'op':"confirmReturnAftersale"},
				dataType:"json",
				success: function(res){
					 layer.close(index_layer);
					if(res.status==0){ 
						$(obj).remove(); 
						$("#table_five_"+batchcode+" .btn-warning").html('驳回售后维权');
					}
					layer.alert(res.msg);
				},	
				error:function(){
					layer.close(index_layer);
					layer.alert("网络错误请检查网络");
				}						
			});							
		}); 	
	});			
}
<!-- 维权管理 --end-->

<!-- 退款 -->
$(".good_refund").click(function(){	
	var batchcode = $(this).data('batchcode');
	var refundMoney_old = $(this).data('money');
	var refundMoney = $("#good_refund_"+batchcode).val();	
	layer.confirm('订单:'+batchcode+'</br>将退款 <b style="color:red">'+refundMoney+'</b> 元</br><b style="color:red">微信支付订单请先到微信支付详情界面进行手动退款！</b>', {
		btn: ['退款','取消'] 
	}, function(confirm){
		if(isNaN(refundMoney) || parseFloat(refundMoney) > parseFloat(refundMoney_old)){
			layer.alert("请输入正确的金额！");
			return;
		}
		layer.close(confirm);	  
		layer_open();			
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'totalprice':refundMoney,'retype':returntype,'op':"goodRefund"},
			dataType:"json",
			success: function(res){
				 layer.close(index_layer);
				if(res.status==0){
					if(returntype==0){
						$('a[data-refund-batchcode='+batchcode+']').replaceWith('<a onclick="confirmOrder(this)" data-totalprice="'+refundMoney_old+'" data-batchcode="'+batchcode+'" title="确认完成"><img src="../../../common/images_V6.0/operating_icon/icon23.png"></a>'); 
						//$("#table_four_"+batchcode+" p:first-child").html('<img src="../../../common/images_V6.0/contenticon/affirm-icon.png"> <b style="color:#1eaf4e">退货已确认(仅退款)</b>');
						$("#table_four_"+batchcode+" p:first-child").html('<img src="../../../common/images_V6.0/contenticon/refund-success.png"> <b style="color:#1eaf4e">退款完成</b>');		
					}else{
						$('a[data-refund-batchcode='+batchcode+']').replaceWith('<a onclick="confirmOrder(this)" data-totalprice="'+refundMoney_old+'" data-batchcode="'+batchcode+'" title="确认完成"><img src="../../../common/images_V6.0/operating_icon/icon23.png"></a>'); 
						$("#table_four_"+batchcode+" p:first-child").html('<img src="../../../common/images_V6.0/contenticon/confirm-return.png"> <b style="color:#1eaf4e">退货已确认(仅退款)</b>');								
					}
					var urls="../../../common_shop/jiushop/refund.php?out_trade_no="+batchcode+"&refund_fee="+refundMoney+"&total_fee="+refundMoney;


                $.ajax({
                    type:"GET",
                    url:urls,
                    dataType:"json",
                    success: function(results){

                    }
                });

					$(".order_hide").fadeOut("slow"); 
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});		 
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],
			icon:1
		});
	});
			
});
<!-- 退款 End -->

<!-- 申请退货(且退款) -->
$(".good_refund_all").click(function(){	
	var batchcode = $(this).data('batchcode');
	var refundGoodMoney_old = $(this).data('money');
	var refundGoodMoney = $("#good_refund_money_"+batchcode).val();	
	var refundRemark = $("#good_refund_remark_"+batchcode).val();
	var msg = "订单:"+batchcode+"</br>已确定收到退货!";
	if(parseFloat(refundGoodMoney_old) != parseFloat(refundGoodMoney)){
		msg += "并将退款金额修改为：<b style='color:red'>"+refundGoodMoney+"<b> 元";
	}else{
		msg += "退款金额：<b style='color:red'>"+refundGoodMoney+"<b> 元";
	}
	layer.confirm(msg, {
		btn: ['确定修改','取消'] 
	}, function(confirm){
		if(isNaN(refundGoodMoney) || parseFloat(refundGoodMoney) > parseFloat(refundGoodMoney_old)){
			layer.alert("请输入正确的金额！");
			return;
		}
		layer.close(confirm);	  
		layer_open();			
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'totalprice':refundGoodMoney,'remark':refundRemark,'op':"confirmGoodAllRefund"},
			dataType:"json",
			success: function(res){
				 layer.close(index_layer);
				if(res.status==0){
					var currOp = $('a[data-refund-all-batchcode='+batchcode+']');
					var newOp = '<a title="确定退款" data-refund-batchcode='+batchcode
					+'  onclick="showGoodRefund('+batchcode+',1)" ><img src="../../../common/images_V6.0/operating_icon/icon57.png" /></a>';
					currOp.replaceWith(newOp); 
					$("#table_four_"+batchcode+" p:first-child").html('<img src="../../../common/images_V6.0/contenticon/affirm-icon.png"> <b style="color:#EC971F">顾客申请退货</b><span style="color:red"> [已收到退货]</span>');
					$(".order_hide").fadeOut("slow"); 
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});		 
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],
			icon:1
		});
	});
			
});
<!-- 申请退货(且退款) End -->

<!-- 延期收货 -->
$(".order_delay").click(function(){	
	var batchcode = $(this).data('batchcode');
	var is_delay = $(this).data('is_delay');
	var delayDate = $("#data_delay_"+batchcode).val();		
	layer.confirm('您确认要延迟 订单:'+batchcode+' 的</br>收货时间为 '+delayDate+'天 后吗', {
		btn: ['延迟','取消'] 
	}, function(confirm){
		if(isNaN(delayDate)){ 
			layer.alert("请输入正确的时间", function(index){layer.close(index);}); 
			return;  
		}
		layer.close(confirm);	  
		layer_open();			
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'is_delay':is_delay,'Date':delayDate,'op':"delayDate"},
			dataType:"json",
			success: function(res){
				 layer.close(index_layer);
				if(res.status==0){
					$("#date_time_"+batchcode).html(res.time);					
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});		 
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],
			icon:1
		});
	});
			
});
<!-- 延期发货 --end-->

<!-- 确认已退货 -->
function confirmGoodRefund(obj){
	var batchcode = $(obj).data('batchcode');		
	layer.confirm('确定 订单:'+batchcode+' 已收到退货，确定后不可更改！', {
		btn: ['确认','取消'] 
	}, function(confirm){
		
		layer.close(confirm);	  
		layer_open();			
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'op':"confirmGoodRefund"},
			dataType:"json",
			success: function(res){
				 layer.close(index_layer);
				if(res.status==0){
					$(obj).remove();
					$("#table_four_"+batchcode+" p:first-child").html('<img src="../../../common/images_V6.0/contenticon/affirm-icon.png"> <b style="color:#EC971F">申请退货(换货)</b><span style="color:red"> [已收到退货]</span>');
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});		 
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],
			icon:1
		});
	});
			
}
<!-- 确认已退货 --end-->

<!-- 确认订单 -->
function confirmOrder(obj){
	var batchcode = $(obj).data('batchcode');	
	var totalprice = $(obj).data('totalprice');	
	layer.confirm('您确定要确认 订单:'+batchcode+' 交易完成吗？<br/>确认后，表示订单已经完成，并且无法撤销！', {
		btn: ['确认','取消'] 
	}, function(confirm){
		
		layer.close(confirm);	  
		layer_open();			
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'totalprice':totalprice,'op':"confirm"},
			dataType:"json",
			success: function(res){
				 layer.close(index_layer);
				if(res.status==0){
					$(obj).remove();
					$("#table_five_"+batchcode).html('<span class="btn btn-success">已完成</span>');
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});		 
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],
			icon:1
		});
	});
			
}
<!-- 确认订单 --end-->

<!-- 确认维权完毕 -->
function confirmAftersale(obj){
	var batchcode = $(obj).data('batchcode');	
	var totalprice = $(obj).data('totalprice');	
	layer.confirm('您确定要确认 订单:'+batchcode+' 维权完毕吗？', {
		btn: ['确认','取消'] 
	}, function(confirm){
		
		layer.close(confirm);	  
		layer_open();			
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'totalprice':totalprice,'op':"confirmAftersale"},
			dataType:"json",
			success: function(res){
				 layer.close(index_layer);
				if(res.status==0){
					$(obj).remove();
					$("#table_five_"+batchcode+" .btn-warning").html('售后已处理完成');
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});		 
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],
			icon:1
		});
	});
			
}
<!-- 确认维权完毕 --end-->

<!-- 后台支付 -->
function payOrder(obj){
	var batchcode = $(obj).data('batchcode');	
	var totalprice = $(obj).data('totalprice');	

	layer.confirm('您确定要确认支付订单号:'+batchcode+'吗？', {
		title:'后台支付',		
		btn: ['确认支付','取消'] 
	}, function(confirm){
		layer_open();
		layer.close(confirm);	 
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'totalprice':totalprice,'op':"pay"},
			dataType:"json",
			success: function(res){
				if(res.status==0){ 
					layer.close(index_layer);
					$("#order_pay_"+batchcode).html('<img src="../../../common/images_V6.0/contenticon/pay-icon.png" /><span class="CP_table_bianhaof">已支付<span style="color:red;">(后台支付)</span></span>');					
					$(obj).prev("a").remove();
					$(obj).next("a").replaceWith('<a title="返佣记录" href="order_rebate_log.php?batchcode='+batchcode+'&customer_id='+customer_id+'=="><img src="../../../common/images_V6.0/operating_icon/icon51.png"></a>');
					if(res.supply==-1){
						$(obj).replaceWith('<a id="button_delivery_'+batchcode+'" title="发货" onclick="showDelivery('+batchcode+')"><img src="../../../common/images_V6.0/operating_icon/icon42.png"></a>');	
					}else{
						$(obj).remove();
					}
					
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});			
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],  
			icon:1
		});
	});
	
}
<!-- 后台支付 --end-->

<!-- 发货 -->
$(".order_delivery").click(function(){	
	var batchcode = $(this).data('batchcode');
	var totalprice = $(this).data('totalprice');	
	var $button = $(this);
	layer.confirm('您确认要发货吗', {
		btn: ['发货','取消'] 
	}, function(confirm){

		var expressID = $("#express_id_"+batchcode).val();
		var expressName = $("#express_id_"+batchcode).find("option:selected").text();	
		var expressRemark = $("#express_remark_"+batchcode).val();
		var expressNum = $("#express_num_"+batchcode).val();
		
		if(expressNum=="" && expressID!=0){ 
			layer.alert("请输入快递单号", function(index){layer.close(index);}); 
			return;  
		}
		layer.close(confirm);	  
		layer_open();			
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'expressID':expressID,'expressRemark':expressRemark,'expressNum':expressNum,'op':"send"},
			dataType:"json",
			success: function(res){
				 layer.close(index_layer);
				if(res.status==0){
					$button.parent("dd").next("dd").remove(); 
					$button.parent("dd").remove(); 
					var sendstr = "";
					$("#button_delivery_"+batchcode).prev("a").remove();														
					$("#express_id2_"+batchcode).text(expressName);
					$("#express_remark2_"+batchcode).text(expressRemark);
					$("#express_num2_"+batchcode).val(expressNum);
					$("#express_num2_"+batchcode).parent('span').parent('dd').append('<span onclick="KuaiDi100('+batchcode+')" class="order_kuaidi">(点击查看物流)</span>');
					if(expressID!=0){
						sendstr = '<p class="CP_table_chanpina_fourp"><img src="../../../common/images_V6.0/contenticon/affirm-icon.png"><b style="color:#31B0D5"> 已发货</b></p><p>发货时间:'+res.time+'</p>';
						$("#button_delivery_"+batchcode).remove();
					}else{
						sendstr = '<p class="CP_table_chanpina_fourp"><img src="../../../common/images_V6.0/contenticon/confirm_delivery.png"><b style="color:#337AB7"> 顾客已收货</b></p><p>发货时间:'+res.time+'</p><p>收货时间:'+res.time+'</p>'; 
						$("#button_delivery_"+batchcode).replaceWith('<a title="确认完成" data-batchcode="'+batchcode+'" data-totalprice="'+totalprice+'" onclick="confirmOrder(this)"><img src="../../../common/images_V6.0/operating_icon/icon23.png"></a><a title="红包确认"><img src="../../../common/images_V6.0/operating_icon/icon55.png"></a>');						
					}
					$("#table_four_"+batchcode).html(sendstr);
					$(".order_hide").fadeOut("slow"); 					
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.close(index_layer);
				layer.alert("网络错误请检查网络");
			}						
		});		 
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],
			icon:1
		});
	});
			
});
<!-- 发货 --end-->

<!-- 删除订单 -->
function delOrder(obj){
	var batchcode = $(obj).data('batchcode');
	layer.confirm('您确定要删除订单号:'+batchcode+'吗？', {
		title:'订单删除',		
		btn: ['确定删除','取消'] 
	}, function(){
		$.ajax({
			url: "order.class.php",
			type:"POST",
			data:{'batchcode':batchcode,'op':"del"},
			dataType:"json",
			success: function(res){
				if(res.status==0){ 				
					$(obj).parent("td").html('<a style="color:#C9302C">订单已删除</a>');	 	
				}
				layer.alert(res.msg);
			},	
			error:function(){
				layer.alert("网络错误请检查网络");
			}						
		});			
	}, function(){
		layer.msg('已取消删除', {
			time: 4000,
			btn: ['确认'],  
			icon:1
		});
	});
	
}
<!-- 删除订单 --end-->

function backOrder(obj){
	var batchcode = $(obj).data('batchcode');		
	if(!confirm('您确定要驳回客户退货吗？')){
		return false;
	}
	$.ajax({
		url: "order.class.php",
		type:"POST",
		data:{'batchcode':batchcode,'op':"back"},
		dataType:"json",
		success: function(res){
			if(res.status==0){
				$(obj).prev("a").remove();
				$(obj).next("a").remove();
				$(obj).remove();				
				$("#WSY_order_status_"+batchcode).html("<a class=\"WSY_already_red\">驳回退货</a>");
				//$("#WSY_order_pay_"+batchcode).prev("td").children("p").html("微信支付<br>(<a style=\"color:red\">后台支付</a>)");
			}
			alert(res.msg);
		},	
		error:function(){
			alert("网络错误请检查网络");
		}						
	});	
	
}

function finishOrder(){
layer.confirm('您确定要完成所有确认收货的订单吗？', {
		title:'一键完成',		
		btn: ['确定','取消'] 
	}, function(){
		$.ajax({
			url: "order_finish.php",
			type:"POST",
			data:{'op':"finish_order"},
			dataType:"json",
			success: function(res){
				
				layer.alert(res.msg);
			},	
			error:function(){
				layer.alert("网络错误请检查网络");
			}						
		});			
	}, function(){
		layer.msg('已取消', {
			time: 4000,
			btn: ['确认'],  
			icon:1
		});
	});
}

function KuaiDi100(obj){
	KDNum = $("#express_num2_"+obj).val();
	KDName = $("#express_id2_"+obj).val();
	console.log(KDNum);

	layer.open({
		type: 2,
		title: '快递查询',
		shadeClose: true,
		shade: 0.5,
		area: ['450px', '70%'],
		content: 'http://m.kuaidi100.com/index_all.html?type='+KDNum+'&postid='+KDNum+'#result' 
	});  		
	
}



function exportRecord(num){
	switch(num){
		case 1: //订单导出
		  var name ="commonshop_excel";
		  break;
		case 2: //飞豆
		  var name ="commonshop_feidou_excel";
		  break;
	    case 3: //海关头部
		  var name ="commonshop_customs_head_excel";
		  break;
		case 4: //海关明细
		  var name ="commonshop_customs_excel";
		  break;  
	}

	var begintime = $("#begintime").val();//下单时间开始
	var endtime = $("#endtime").val();//下单时间结束
	var pay_begintime = $("#pay_begintime").val();//订单支付时间开始
	var pay_endtime = $("#pay_endtime").val();//订单支付时间结束
	var orgin_from = $("#orgin_from").val();//订单来源
	var search_paystyle = $("#search_paystyle").val();//支付方式
	var search_batchcode = $("#search_batchcode").val();//订单号
	var search_name = $("#search_name").val();//搜索姓名
	var search_name_type = $("#search_name_type").val();//微信名还是收货名
	var search_order_ascription = $("#search_order_ascription").val();//订单归属
	var status = <?php echo $search_class; ?>;//订单状态
	if(search_batchcode==""){
		search_batchcode = -1;
	}	
	if(orgin_from==""){
		orgin_from = -1;
	}
	if(search_name==""){
		search_name = -1;
	}	
	if(search_paystyle==""){
		search_paystyle = -1;
	}
	if(begintime==""){ 
		begintime = 0;
	}
	if(endtime==""){
		endtime = 0;  
	}
	if(pay_begintime==""){
		pay_begintime = 0;
	}
	if(pay_endtime==""){
		pay_endtime = 0;
	}
	var url='/weixin/plat/app/index.php/Excel/'+name+'/customer_id/<?php echo passport_decrypt($customer_id); ?>/begintime/'+begintime+'/endtime/'+endtime+'/pay_begintime/'+pay_begintime+'/pay_endtime/'+pay_endtime+'/search_batchcode/'+search_batchcode+'/orgin_from/'+orgin_from+'/search_paystyle/'+search_paystyle+'/search_order_ascription/'+search_order_ascription+'/search_name/'+search_name+'/search_name_type/'+search_name_type+'/status/'+status+'/';
	//console.log(url);
	document.location=url; 
}

var index_layer;
function layer_open(){
	index_layer= layer.load(0, {
		shade: [0.1,'#000'], //0.1透明度的白色背景
		content: '<div style="position:relative;top:30px;width:200px;color:red">数据处理中</div>'
	});	
}
</script>
</body>
</html>
