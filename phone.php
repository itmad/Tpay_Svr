<?php
//这个里api是和手机端交互的api实现
define('PRIVTE', TRUE);

require_once "common.php";
checkToken();

switch($_GET['command']){
	case "ask"://手机服务端寻问是否需要新生成二维码
		require_once "sql.php";
		//addLog("11",0);//测试手机是否在线。
		$rnt = isNeedCreatQr();
		echo packData("ok",$rnt,1);
		break;
	case "addqr"://手机服务端添加二维码url
		require_once "sql.php";
		//只要没有空格，一般不用去考虑注入问题。
		$rnt = addQrUrl(str_trim($_GET['url']),str_trim($_GET['mark_sell']));
		echo packData("ok",$rnt,1);
		break;
	case "do"://手机服务端告诉我，xxxx码已经支付成功了。
		require_once "sql.php";
		//无论怎么样，我服务器正常处理了，就返回正常数据，防止客户端再次发起请求
		//就算充值了成功了，但你们的succDo没有执行成功，但是都是有日志记录的，所以可以放心此类情况
		$result=succQr(str_trim($_GET['mark_sell']),str_trim($_GET['money']),str_trim($_GET['order_id']),str_trim($_GET['mark_buy']));
		if($result){
			echo packData("succ","",1);
		}else{
			echo packData("err","",0);
		}
		break;
	default:
		echo packData("参数错误","",0);
		
};



$mysqli->close();

 
?>