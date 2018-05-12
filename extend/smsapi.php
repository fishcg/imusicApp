<?php
$db_host = "10.0.0.224"; //数据库服务器名称
$db_username = "xuxd"; // 连接数据库用户名
$db_password = "123456"; // 连接数据库密码
$db_database='wjtr2'; // 数据库的名字

$connection = mysql_connect($db_host,$db_username,$db_password);//连接到数据库
mysql_query("set names 'utf8'");//编码转化
if(!$connection){
	die("could not connect to the database:</br>".mysql_error());//诊断连接错误
}
$db_selecct=mysql_select_db($db_database);//选择数据库
if(!$db_selecct){
	die("could not to the database</br>".mysql_error());
}

//获取需要发送新标通知的产品信息
$strsql_smslog = "SELECT * FROM `lab_sms_log` WHERE status = 0";
$result_smslog = mysql_query($strsql_smslog);
while($smslog = mysql_fetch_assoc($result_smslog)){
	if($smslog['tel'] && $smslog['content']){
		$res = sendSMS($smslog['tel'], $smslog['content']);
		if($res){
			$change_status_sql = "UPDATE `lab_sms_log` SET status = 1 WHERE id = ".$smslog['id'];
			mysql_query($change_status_sql);
		}else{
			$change_status_sql = "UPDATE `lab_sms_log` SET status = -1 WHERE id = ".$smslog['id'];
			mysql_query($change_status_sql);
		}
	}
}
mysql_close($connection);//关闭连接

function sendSMS($strMobile, $content){
	include_once '/data/wjtr2/lib/nusoap_base.class.php';
	$client = new nusoap_client("http://sms.2office.cn:8080/WebService/SmsService.asmx?wsdl", true);
	//设置编码格式
	$client->soap_defencoding = 'UTF-8';
	$client->decode_utf8 = false;
	$client->xml_encoding = 'UTF-8';
	//此处的编码格式必须和网页的编码格式一致，如果网页的编码格式是GBK，则UTF-8必须修改为GBK，否则短信内容是乱码
	$err = $client->getError();
	if ($err) {
		//echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
	}
	$password = md5("tyrbl911aa375297554c7b06076cfa57f34add1c");
	$smsid = microtime(true) * 100;
	$param = array(
			'account' => '2522664',
			'password' => $password,
			'mobile' => $strMobile,
			'content' => $content,
			'channel' => '252266401',
			'smsid' => $smsid,
			'sendType' => '1'
	);
	$result = $client->call('SendSms3', array(
			'parameters' => $param
	), '', '', false, true, 'document', 'encoded');
	$str = explode(",", $result['SendSms3Result']);
	return $str[0] == 0 ? 1 : 0;
}