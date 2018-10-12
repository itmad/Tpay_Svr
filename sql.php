<?php
if(!defined('PRIVTE'))
	exit('-1'); 

//前端申请二维码
//返回申请成功就返回二维码的url，否则返回空文本
//因为申请后不能马上拿到url和mark_sell，所以这里会阻塞，阻塞时长为config->pay_nor或config->pay_slowt秒
function applyQr($channel,$money){
	if(!isCanGetQr($channel)){
		return "";
	}

	$task_extra=applyDo($channel,$money);
	global $config;
	global $mysqli;
	
	$mark_sell = $config->mark_sell.time().mt_rand(100,999);//以时间做为唯一值
	$apply_ip = substr(getIp(),0,16);//数据库是16位，防止出什么问题。

	$sql = "INSERT INTO ".$config->tab_task." (money,channel,task_extra,mark_sell,apply_ip) VALUES (";
	$sql = $sql.$money.",";
	$sql = $sql."'".$channel."',";
	$sql = $sql."'".$task_extra."',";
	$sql = $sql."'".$mark_sell."',";
	$sql = $sql."'".$apply_ip."'";
	$sql = $sql.")";
	$mysqli->query($sql);
	if(!$mysqli){
		return "";
	}
	addLog($channel."渠道被请求".sprintf("%.2f",$money/100)."元的二维码",5);
	return waitQrCreated($mark_sell);
}

//这个函数是在10秒内循环等待二维码的创建成功并返回二维码的url
//取成功返回url和mark_sell，失败返回空文本
function waitQrCreated($mark_sell){
	global $config;
	global $mysqli;
	
	$sql = "select url,mark_sell from ".$config->tab_task." where mark_sell='".$mark_sell;
	$sql = $sql."' and !isnull(creat_time)";
	$result = new stdClass();
	$count=0;
	date_default_timezone_set('PRC');
	$max_count = date('H')>7?$config->pay_nor:$config->pay_slow;
	$max_count=$max_count+5;

	do{
		if(function_exists('sleep')){
			sleep(1);
		}else{
			time_sleep_until(time()+1);//很多垃圾服务器不支持用sleep(1);
		}
		$result = $mysqli->query($sql);
		$count++;
	}while($result->num_rows < 1 && $count<$max_count);
	
	if($result->num_rows > 0){
		$userdata=$result->fetch_assoc();
		return $userdata;
	}
	return "";
}


//返回指定mark_sell的订单是否已经支付成功
function isPayed($mark_sell,$qr){
	global $config;
	global $mysqli;
	
	$sql = "select result_extra from ".$config->tab_task." where mark_sell='".$mark_sell;
	$sql = $sql."' and url='".$qr;
	$sql = $sql."' and !isnull(end_time)";
	$result = $mysqli->query($sql);
	if($result->num_rows > 0){
		$userdata = $result->fetch_assoc();
		//这里要判断字段是不是为空，如果为空，外面判断以为还没支付呢。。
		if(empty($userdata["result_extra"])){
			return true;
		}
		return $userdata["result_extra"];
	}
	return false;
}

//用于前端，用户发起二维码请求时的判断，是否有资格获取二维码
function isCanGetQr($channel){
		if($channel!="wechat" && $channel!="alipay"){
			return false;//目前只有两个渠道
		}
		
		global $config;
		global $mysqli;
		//获取到用户的IP
		$userIp = getIp();
		
		//如果有vps的，这里可以不用执行，直接用mysql的定时任务每小时清表，功能类似但不一样哈。
		$sql = "select from ".$config->tab_police." where now()-lasttime>600";
		$mysqli->query($sql);
		
		//查询该ip获取二维码的情况
		
		$sql = "select * from ".$config->tab_police." where ip = inet_aton('".$userIp."') and channel='".$channel."' limit 1";
		$result = $mysqli->query($sql);
		//帐号或密码错误
		if($result->num_rows > 0){
			$userdata=$result->fetch_assoc();
			if($userdata["get_count"] > $config->pay_count){
				return false;
			}
			$thistime=new DateTime();
			$diff=ceil(strtotime($thistime->format("Y-m-d H:i:s")) - strtotime($userdata["lasttime"]));
			if($diff <= $config->pay_speed){
				return false;
			}
		}
		
		$sql = "INSERT INTO ".$config->tab_police." (ip,channel) VALUES (inet_aton('".$userIp."'),'".$channel."') ON DUPLICATE KEY UPDATE get_count=get_count+1,lasttime=now()";
		$mysqli->query($sql);
		return true;
}



//------------------------------------华丽分割线，下面是和手机端沟通的逻辑，不太明白的别随便乱改哦-----------------------------------
//------------------------------------华丽分割线，下面是和手机端沟通的逻辑，不太明白的别随便乱改哦-----------------------------------
//------------------------------------华丽分割线，下面是和手机端沟通的逻辑，不太明白的别随便乱改哦-----------------------------------


//服务端问我是否需要生成二维码，只返回60秒内申请的，有的话返回需求详情，没有就返回为
//不需要时就返回空文本，需要的时候返回订单的信息类，比如mark_sell,channel等信息
function isNeedCreatQr(){
	global $config;
	global $mysqli;
		
	$sql = "select * from ".$config->tab_task." where now()-apply_time < ".$config->pay_alive." and isnull(creat_time) limit 1";
	$result = $mysqli->query($sql);
	if($result->num_rows > 0){
		$userdata = $result->fetch_assoc();
		return $userdata;
	}
	return "";
}


//添加url到指定mark_sell的记录
//添加成功返回空文本，否则返回新需求订单，没有订单返回也是空，这样就不用等客户端再等xxx秒
function addQrUrl($url,$mark_sell){
	global $config;
	global $mysqli;
		
	$sql = "update ".$config->tab_task." set url='".$url."',creat_time=now() where mark_sell='".$mark_sell."' and isnull(creat_time)";
	$result = $mysqli->query($sql);
	if(!$result){
		addLog($mark_sell."添加URL:".$url."失败！",3);
	}
	return isNeedCreatQr();
}

//添加用户支付成功的消息，返回支付成功的操作是否成功
//只返回是否支付的操作判定是否成功，但是支付后的逻辑是否成功这里不会管，也不用管（好好理解下为什么）
//而且就算支付判定失败了，会记录写日志，叫用户联系你，自己检查失败原因即可
function succQr($mark_sell,$money,$order_id,$mark_buy){
	global $config;
	global $mysqli;

	$sql = "update ".$config->tab_task." set end_time=now(),status=status+1,order_id='".$order_id;
	$sql = $sql."',mark_buy='".$mark_buy;
	$sql = $sql."' where mark_sell='".$mark_sell;
	$sql = $sql."' and money=".$money;
	$sql = $sql." and status=0";
	
	$result = $mysqli->query($sql);
	if($result){
		addLog($mark_sell."成功充值".sprintf("%.2f",$money/100)."元！",4);
		$sql = "select task_extra from ".$config->tab_task;
		$sql = $sql." where mark_sell='".$mark_sell;
		$sql = $sql."' and money=".$money;

		$result = $mysqli->query($sql);
		if($result->num_rows > 0){
			$userdata = $result->fetch_assoc();
			$action_log=succDo($mark_sell,$money,$userdata["task_extra"]);
			addLog($mark_sell."Extra：".$userdata["task_extra"]."返回：".$action_log,4);
		}else{
			addLog($mark_sell."充值".sprintf("%.2f",$money/100)."元后获取额外信息失败！",2);
		}
		return true;
	}else{
		addLog($mark_sell."充值".sprintf("%.2f",$money/100)."元失败！",1);
	}
	
	return false;
}


//添加日志到数据库，返回是否操作成功，只记录左边255个字符
//下面例举已经有的类型，你们以后可以自己定义types类型
//0临时测试用
//1表示收到充值成功的消息却更新充值订单失败
//2表示收到充值成功的消息后获取额外信息失败
//3表示手机端想要添加二维码的url时，却添加失败
//4表示充值成功的消息
//5表示有用户在请求二维码
function addLog($content,$type){
	global $config;
	global $mysqli;
	$sql = "insert into ".$config->tab_log." (content,ip,types) values (left('".$content."',255),'".getIP()."',".$type.")";
	$result = $mysqli->query($sql);
	return $result;
}
 

?>