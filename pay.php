<?php
//此文件是用户在网页端支付所需要的api接口，采用前后端分离开发，前端请求此api执行展示
define('PRIVTE', TRUE);

require_once "common.php";
require_once "sql.php";

if(!isset($_POST['command'])){
	die(packData("参数错误","",0));
}


//有且仅有两个API，够前端使用了
//前端可以传更多值，你自己在config.php里获取生成extra就能有无数个参数匹配了。
//理解下上面的话就可以了，哈哈哈
switch($_POST['command']){
	case "applyqr"://用户客户端申请二维码
		if(!is_numeric($_POST["money"])){
			echo packData("参数错误","",0);
			break;
		}
		if($_POST["money"]<0.01){
			echo packData("参数错误","",0);
			break;
		}
		if(!isset($_POST["channel"])){
			echo packData("参数错误","",0);
			break;
		}
		$qr = applyQr(str_trim(strtolower($_POST["channel"])),$_POST["money"]);
		if(is_null($qr) || sizeof($qr) < 2){
			echo packData("试试等几秒再获取二维码吧~","",0);
			break;
		}
		echo packData("ok",$qr,1);
		break;
	case "ispayed"://用户客户端询问是否支付成功
		if(!isset($_POST["mark_sell"])){
			echo packData("参数错误","",0);
			break;
		}
		$result = isPayed(str_trim(strtolower($_POST["mark_sell"])),str_trim($_POST["qr"]));
		if($result){
			echo packData("ok",$result,1);
		}else{
			echo packData("err","",0);
		}
		break;
	
	default:
		echo packData("参数错误","",0);
}











$mysqli->close();
?>