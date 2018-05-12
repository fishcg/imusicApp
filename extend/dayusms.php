<?php

/**
 * 短信发送模块
 * @author WenKai
 *
 */
class Helper_DayuSMS{

	/**
	 * 发送短信(阿里大于)
	 * @param string $mobile 手机号 15008501308
	 * @param string $type 短信类型  register|login|password
	 * @param int $length 验证码长度 默认6
	 */
	static function sendSMS($mobile, $type = 'register', $length = 6){

		if(!QValidator::validate_is_mobile($mobile)){
			return json_encode(array(
					'status' => false,
					'title' => '提示',
					'message' => "请填写正确的手机号"
			));
		}
		$time = time();
		$title = '温馨提示';
		$code = Helper_Common::random($length,1);
		
		// App 证书
		$appkey = Q::ini('appini/dayusms/appkey');
		$secret = Q::ini('appini/dayusms/appsecret');
		// 应用程序名称
		$product = Q::ini('appini/dayusms/product');
		// 签名名称
		$signName = Q::ini('appini/dayusms/signname');
		// 短信模板
		$Template = Q::ini('appini/dayusms/template');
		// 应用扩展
		$extend = Q::ini('appini/dayusms/extend');
		// 短信类型
		$smstype = Q::ini('appini/dayusms/smstype');
		// 引用外部文件
		include 'dayusms/TopSdk.php';
        //dump(Q::ini('appini/dayusms'));
		$c = new TopClient();
		$c->appkey = $appkey;
		$c->secretKey = $secret;
		$req = new AlibabaAliqinFcSmsNumSendRequest();
		$req->setExtend($extend);
		$req->setSmsType($smstype); //
		$req->setSmsFreeSignName($signName);
		$req->setSmsParam("{product:'" . $product . "',code:'" . $code . "'}");
		$req->setRecNum($mobile); // 接收手机号
		$req->setSmsTemplateCode($Template); // 短信模板
		$resp = $c->execute($req);

		if(isset($resp->code) && $resp->code > 0){
			$message = "";
			$msg = $resp->sub_msg;
			if(is_object($msg) || is_array($msg)){
				foreach ($msg as $val){
					$message .= ',' . $val;
				}
			}
			$message = substr($message,1);
			return array(
				'status' => false,
				'title' => $title,
				'message' => $message
			);
		}
		
		$cond = array(
				'mobile' => $mobile,
				'type' => $type,
				'code' => $code,
				'created' => $time,
				'expired' => $time + 900,
				'status' => 0 
		);
		
		$smsCode = new Smscode($cond);
		$smsCode->save();
		return array(
				'status' => true,
				'title' => $title,
				'message' => '信息发送成功'
		);
	
	}

	function validateSms($mobile,$code,$type){
		
		return Smscode::verify($mobile,$code,$type);
	}
}