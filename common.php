<?php
if(!defined('PRIVTE'))
	exit('-1'); 

//这里正式环境时，一定要关闭日志，不然有些Notic等会很麻烦
//需要看日志时，再打开
error_reporting(E_ALL);//这一句是关掉所有错误显示
//error_reporting(E_ALL ^ E_WARNING);//E_NOTICE
ini_set('display_errors',1);//这一句是就算有错误，也返回200，说明服务器处理过了。



require "config.php";
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set("Asia/Shanghai");

$mysqli = new mysqli($config->sql_addr,$config->sql_user,$config->sql_pass,$config->sql_name);
$mysqli->set_charset("utf8");
if ($mysqli->connect_error){
  die('连接失败！');
}


//检测这个请求是否合法，token值是否正确
function checkToken(){
	global $config;
	$thisurl='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$key = decrypt($_SERVER['HTTP_TOKEN']."",$config->token);
	$sign = explode("|",$key);
	if(count($sign)<2 || $sign[1]!=$thisurl){
		die("err");
	}
}

//打包返回信息的通用函数
//一般status为1表示正常，0表示不正常，其它代码表示指定错误类型
function packData($msg,$data,$status){
	 $obj=new stdClass();
	 $obj->message=$msg;
	 $obj->data=$data;
	 $obj->status=$status;
	 if(version_compare(PHP_VERSION,'5.4.0',">")){
		return json_encode($obj,JSON_UNESCAPED_UNICODE); 
	 }
	 return json_encode($obj);
}


//解密通用函数
function decrypt($str, $key){
	if(version_compare(PHP_VERSION,'5.4.0',">")){
		$str = hex2bin(strtolower($str));
	}else{
		$str = pack("H" . strlen($str), $str); 
	}
	$decrypted = mcrypt_decrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);
    $dec_s = strlen($decrypted);
    $padding = ord($decrypted[$dec_s-1]);
    $decrypted = substr($decrypted, 0, -$padding);
    return $decrypted;
}

//为了兼容php5.4以上
function hex2binLow($hexdata) { 
	$bindata = '';   
	if (strlen($hexdata) % 2 == 1) {  
		$hexdata = '0' . $hexdata;  
	}  
	for ($i = 0; $i < strlen($hexdata); $i+=2) { 
		$bindata .= chr(hexdec(substr($hexdata, $i, 2))); 
	}   
	return $bindata;
}


//简单替换所有空格防止注入问题
function str_trim($str){
	return str_replace(" ","",$str);
}

//获取用户真实IP 
function getIp() { 
     if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) 
         $ip = getenv("HTTP_CLIENT_IP"); 
     else 
         if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) 
             $ip = getenv("HTTP_X_FORWARDED_FOR"); 
         else 
             if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) 
                 $ip = getenv("REMOTE_ADDR"); 
             else 
                 if (isset ($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) 
                     $ip = $_SERVER['REMOTE_ADDR']; 
                 else 
                     $ip = "unknown"; 
	
	$ips = explode(",", $ip);
	if(sizeof($ips)>1){
		$ip=$ips[0];
	}
    return str_trim($ip); 
}

?>