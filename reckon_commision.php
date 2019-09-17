<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
$user_id 		= -1;
require('../common/common_from.php'); 
$user_id 			= $configutil->splash_new($_POST["user_id"]);
$promoters 			= $configutil->splash_new($_POST["is_promoters"]);		//是否是推广员
$Plevel 			= $configutil->splash_new($_POST["Plevel"]);			//推广员等级
$now_price 			= $configutil->splash_new($_POST["now_price"]);//现价
$init_reward		= $configutil->splash_new($_POST["init_reward"]);//商城分佣比例
$pro_reward			= $configutil->splash_new($_POST["pro_reward"]);//产品分佣比例
$issell_model		= $configutil->splash_new($_POST["issell_model"]);	//复购开关
$is_consume			= $configutil->splash_new($_POST["is_consume"]);	//是否满足消费无限级奖励/股东分红 0:普通推广员 1:代理 2:渠道 3:总代理 4:股东
$issell				= 0;	//分销开关
$is_team			= 0;	//团队佣金开关
$team_all			= 0;	//团队佣金比例
$is_shareholder 	= 0;	//股东佣金开关
$shareholder_all	= 0;	//股东佣金比例
$isOpenGlobal		= 0;	//全球分红开关
$Global_all			= 0;	//全球分红比例
$is_ncomission    	= 0;	//3*3开关
$aplay_grate		= -1;	//区域团队等级
$team_reward		= 0;	//团队佣金
$user_team_reward	= 0;	//个人团队佣金
$p_percent 			= 0;	//省级比例
$c_percent 			= 0;	//市级比例
$a_percent	 		= 0;	//区级比例
$diy_percent		= 0;	//自定义级比例
$shareholder_reward = 0;	//股东佣金
$Global_reward		= 0;	//全球分红佣金
$Dreward			= 0;	//8级佣金
$return_reward		= 0;	//返回总共能得到的佣金
$reward_ratio		= 0;	//分佣比例
$user_shareholder	= 0; 	//个人股东佣金
$a_percent			= 0;	//一级分红比例
$b_percent			= 0;	//二级分红比例
$c_percent			= 0;	//三级分红比例
$d_percent			= 0;	//四级分红比例
$percent			= 0;	//个人股东比例
if( $pro_reward == 0 ){
	$reward_ratio = 0;
}elseif( $pro_reward == -1 ){
	$reward_ratio = $init_reward;
}else{
	$reward_ratio = $pro_reward;
}
$reward = $reward_ratio * $now_price;

$query = "select is_team,is_ncomission,is_shareholder,issell from weixin_commonshops where isvalid=true and customer_id=".$customer_id." limit 0,1";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
	$issell 		= $row->issell;
	$is_team 		= $row->is_team;
	$is_shareholder	= $row->is_shareholder;
	$is_ncomission	= $row->is_ncomission;
}
if( $issell == 1 and $promoters == 1  and $reward > 0 ){
	if( $is_team == 1 ){
		$query = "select team_all from weixin_commonshop_team where isvalid=true and customer_id=".$customer_id." limit 0,1";
		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		while ($row = mysql_fetch_object($result)) {
			$team_all = $row->team_all;
		}
		$query = 'SELECT aplay_grate FROM weixin_commonshop_team_aplay where isvalid=true and status=1 and aplay_user_id='.$user_id.' and customer_id='.$customer_id." order by createtime desc limit 0,1";
		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		while ($row = mysql_fetch_object($result)) {
			$aplay_grate = $row->aplay_grate;
		}
		
		$query = "SELECT p_percent,c_percent,a_percent,diy_percent from weixin_commonshop_team WHERE isvalid = true AND customer_id = ".$customer_id." limit 0,1";
		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		while ($row = mysql_fetch_object($result)) {
			$p_percent 		= $row->p_percent;
			$c_percent 		= $row->c_percent;
			$a_percent	 	= $row->a_percent;
			$diy_percent	= $row->diy_percent;
		}
		switch($aplay_grate){
			case 0: $team_ratio = $a_percent;break;
			case 1: $team_ratio = $c_percent;break;
			case 2: $team_ratio = $p_percent;break;
			case 3: $team_ratio = $diy_percent;break;
		}
		$team_reward 		= $reward * $team_all;
		$user_team_reward	= $team_reward * $team_ratio;
	}

	if( $is_shareholder == 1 ){
		$query = "select shareholder_all from weixin_commonshop_shareholder where isvalid=true and customer_id=".$customer_id." limit 0,1";
		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		while ($row = mysql_fetch_object($result)) {
			$shareholder_all = $row->shareholder_all;
		}
		$shareholder_reward	= $reward * $shareholder_all;
		if( $is_consume > 0 ){
			$query = "SELECT a_percent,b_percent,c_percent,d_percent from weixin_commonshop_shareholder WHERE isvalid = true and customer_id = ".$customer_id." limit 0,1";
			$result = mysql_query($query) or die('Query failed: ' . mysql_error());
			while ($row = mysql_fetch_object($result)) {
				$a_percent	= $row->a_percent;
				$b_percent	= $row->b_percent;
				$c_percent	= $row->c_percent;
				$d_percent	= $row->d_percent;
			}
			switch($is_consume){
				case 1:
				$percent = $a_percent;
				break;
				case 2:
				$percent = $b_percent;
				break;
				case 3:
				$percent = $c_percent;
				break;;
				case 4:
				$percent = $d_percent;
				break;;
			}
			$user_shareholder = $shareholder_reward * $percent;
		}
	}


	$query = "SELECT isOpenGlobal,Global_all FROM weixin_globalbonus where isvalid=true and customer_id=".$customer_id;
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	while ($row = mysql_fetch_object($result)) {
		$isOpenGlobal	= $row->isOpenGlobal;
		$Global_all		= $row->Global_all;
	}
	if( $isOpenGlobal == 1 ){
		$Global_reward = $reward * $Global_all;
	}
	
	if( $issell_model == 2 ){
		if( $is_ncomission == 0){
			$Plevel = 1;
		}
		$query="select init_reward_1 from weixin_commonshop_commisions where isvalid=true and level=".$Plevel." and customer_id=".$customer_id;
		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		while ($row = mysql_fetch_object($result)) {
			$init_reward_1 = $row->init_reward_1;
		}
		$Dreward = ( $reward - $team_reward - $shareholder_reward - $Global_reward ) * $init_reward_1;
	}
}
$return_reward = $user_team_reward + $user_shareholder + $Dreward;
$return_reward = round($return_reward,2);
echo $return_reward;





?>