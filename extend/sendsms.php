<?php

class Helper_Sendsms {

	/**
	 * 吉信通和第二办公室 可以切换
	 * 注意：接口是采用GB2312编码
	 * $sendType 1使用吉信通2使用第二办公室
	 * 
	 * @param
	 *
	 */
	static function SendSMS($strMobile, $content, $sendType = '') {

		$sendType = $sendType ? $sendType : intval(Q::ini('appini/sendsms/type'));
		if ($sendType == 1) {
			$url = "http://service.winic.org:8009/sys_port/gateway/?id=%s&pwd=%s&to=%s&content=%s&time=";
			$id = urlencode("tyrbl_timely");
			$pwd = urlencode("tyrbl911");
			$to = urlencode($strMobile);
			$content = iconv("UTF-8", "GB2312", $content); //将utf-8转为gb2312再发
			$content = urlencode($content);
			$rurl = sprintf($url, $id, $pwd, $to, $content);
			$result = file_get_contents($rurl);
			return $result;
		}
		elseif ($sendType == 2) {
			$set = Setting::cache();
			if(isset($set['is_send_phone']['value']) && $set['is_send_phone']['value']){
				include_once Q::ini('app_config/ROOT_DIR') . '/lib/nusoap_base.class.php';
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
				return 0;
			}
	elseif ($sendType == 3) {
		include_once Q::ini('app_config/ROOT_DIR') . '/lib/nusoap_base.class.php';
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
	}
}