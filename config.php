<?php
if(!defined('PRIVTE'))
	exit('-1'); 

global $config;
global $mysqli;
$config = new stdClass();
//--------------这里开始配置用户资料，不太懂加的Tpay群：524901982-------------------
//--------------此文件会被包含到common.php里面-------------------


//客户端的通讯密码：只能为一个8位字符，可以符号数字字母等。
$config->token="D@a4.;1A";
//收款备注前缀：长度为8位或以下的数字或字母的组合，自己随便写
$config->mark_sell="编号：";
//mysql的连接地址
$config->sql_addr="localhost";
//mysql的连接用户名
$config->sql_user="root";
//mysql的连接密码
$config->sql_pass="root";
//mysql的数据库名
$config->sql_name="t_pay";
//mysql的日志表表名
$config->tab_log = 'pay_log';
//mysql的任务表表名
$config->tab_task = 'pay_task';
//mysql的防御表表名
$config->tab_police = 'pay_police';
//mysql的IP黑名单列表
$config->pay_black = 'pay_black';
//同一IP多少秒内只能请求一次二维码，防止有人一直恶意请求二维码
$config->pay_speed=5;
//同一IP在1小时内，最多获取多少次二维码
$config->pay_count=20;

//7-24点用户在申请二维码时，超过多少秒没返回二维码就算失败，这里必须大于手机端设置的间隔时间
$config->pay_nor=15;
//0-7点，取二维码的超时秒数，必须大于手机端设置的间隔时间
$config->pay_slow=20;
//用户请求二维码后，在多少秒内可以创建二维码，默认60秒，不太懂就不要改这个
//有时用户申请了二维码支付，但手机端可能信号不好，当超过申请时间60s都没有创建出二维码，那就放弃这个二维码吧。
$config->pay_alive=60;



//此函数，用于用户在申请二维码支付时，你自己服务器要处理的逻辑
//$channel目前只会传wechat或alipay,$money为用户申请支付的整数价格，单位为分
//必须返回一个文本类型的订单附加值
function applyDo($channel,$money){
	$task_extra = "";//必须文本类型，空文本也可以
	//下面比如你可以写：$task_extra=$_GET["user_name"]或$task_extra=$_POST["user_name"].$_POST["pay_code"]等等。
	//这个函数一般情况下不用数据库操作，就根据get或post的值返回指定的extra即可，如果非要比如根据金额从数据库取激活码这类的需求，自己引入$mysqli，自己操作返回即可
	if(isset($_POST['email']) && isset($_POST['descp'])){
		$task_extra=str_trim($_POST["email"])."|@test@|".str_trim($_POST["descp"]);
		return $task_extra;
	}
	
	
	if(isset($_POST['6fcode'])){//这些都是乱写的举例，一个接口其实可以多个功能
		$task_extra=str_trim($_POST["6fcode"]);
		return $task_extra;
	}
	
	
	$task_extra=str_trim($_POST["user_name"]);//这些你们全按需求自己改
	return $task_extra;
}


//此函数，用于用户在支付成功后，你自己服务器要处理的逻辑
//$task_extra为:你在为用户创建二维码订单时，你自己传的值
//这个函数返回一个文本即可，逻辑成功或者失败都是有日志的
//不成功的情况你也可以自己在扩展不成功的逻辑等等
//最简单的逻辑就是用addLog($content,$type)记录，自己以后检查失败原因即可
function succDo($mark_sell,$money,$task_extra){
	global $config;
	global $mysqli;

	//这个|test@|等等全部都可以自己定义分割，我只是随便弄来做个演示
	//实际商用过程中，这些接口通过分割可以实现很多自动发卡自动xx等功能
	//一个支付接口理论上可以实现太多支持功能，好好理解下为什么，
	//还是不懂的可以加群询问，Tpay群：524901982--->感谢大家支持。
	if(strpos($task_extra,"|@test@|")){
		$extra = explode("|@test@|",$task_extra);
		if(count($extra)<1 || empty($extra)){
			return "未留邮箱".$task_extra;
		}
		
		if($money<10){
			return "金额不足".$task_extra;
		}
		
		//如果你们要发信，去QQMailer修改成自己的邮箱吧。
		require_once './mail/QQMailer.php';
		$mailer = new QQMailer(false);
		$title = 'Tpay相关源码，请查收。';
		$content = "感谢您的支持<br /> 您的留言为：".(count($extra)>1?$extra[1]:"空")."<Br />";
		if($money>=180){
			$content=$content."Tpay微信端和服务端完整源码：<br />";
			$content=$content."https://xxxxx";
		}else if($money>=280){
			$content=$content."Tpay全端完整源码（包括例程）：<br />";
			$content=$content."https://xxxxx";
		}else{
			$content=$content."Tpay服务端完整源码：<br />";
			$content=$content."https://xxxxx";
		}

		$flag = $mailer->send($extra[0], $title, $content);
		if($flag){
			echo "邮件发送成功！".$task_extra;
		}else{
			echo "邮件发送失败！".$task_extra;
		}
		return;
	}
	
	
	//下面是我用测试数据库，如果用户充值了xx分钱，数据库中user的money就增加xx分看我怎么弄，照葫芦画瓢即可
	//以后你们自己要把下面删完，写自己的逻辑即可
	if(is_numeric($money)){
		$sql="INSERT INTO test_tab_user (user,money) VALUES ('".$task_extra."',".$money.") ON DUPLICATE KEY UPDATE money=money+".$money;
		$result = $mysqli->query($sql);
		if($result){
			//XXXXXXXXXXXXX
		}
	}
	
	return $task_result;
}









//--------------这里配置用户资料结束，请不要在下面留空行，不然返回值会出问题-------------------
?>