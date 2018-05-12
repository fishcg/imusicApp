<?php
// $Id$

/**
 * Controller_Helper_SySms 控制器
 */
class Helper_SySms{
	/**
	 * @param unknown $tel
	 * @param string $type 登录：login 注册：register 忘记密码：forget_password 绑定手机：binding
	 * @return multitype:array
	 */
	static function sendByType($mobile, $type='login'){
		#示远科技验证码短信平台
		#shiyuan technology
		#示梦于心   远航于行
		//header("Content-Type: text/html; charset=utf-8");
		$msg = '';
		$data = array();
		//检测短信发送是否过于频繁
		$time = time();
		$condition = 'mobile="' .$mobile . '" and created>=' . ($time - 3600);
		$count = Smscode::find($condition)->getCount();
		if($count > 6){
			$smsCode = Smscode::find($condition)->order('created asc')->getOne();
			$data['status'] = false;
			$data['message'] = '您发送短信过于频繁，请等待' . intval((61-(($time-$smsCode->created)/60))) . '分钟后重试';
			return $data;
		}
		$condition = array('mobile'=>$mobile, 'type'=>$type);
		$smsCode = Smscode::find($condition)->order('created desc')->getOne();
		$code = '';
		if($smsCode->id > 0 && $time-$smsCode->created < 61){
			$data['status'] = false;
			$data['message'] = '请勿频繁发送短信，请等待' . (61-($time-$smsCode->created)) . '秒';
			return $data;
		} else {
			//生成验证码
			for($i=0; $i<6; $i++){
				$code .= mt_rand(0, 9);
			}
		}
		switch($type){
			case 'login':
				$msg = '动态登录密码：' . $code . '，请勿将您的动态密码告诉他人，如果不是您的操作请忽略此短信';
				break;
			case 'register':
				$msg = '注册验证码：' . $code . '，请勿将您的动态密码告诉他人，如果不是您的操作请忽略此短信';
				break;
			case 'forget_password':
				$msg = '验证码：' . $code . '，请勿将您的动态密码告诉他人，如果不是您的操作请忽略此短信';
				break;
			case 'binding':
				$msg = '验证码：' . $code . '，请勿将您的动态密码告诉他人，如果不是您的操作请忽略此短信';
				break;
		}
		$post_data = array();
		$post_data['account'] = '003553';   //帐号
		$post_data['pswd'] = 'Pnac20150515';  //密码
		$post_data['msg'] =urlencode($msg); //短信内容需要用urlencode编码下
		$post_data['mobile'] = $mobile; //手机号码， 多个用英文状态下的 , 隔开
		$post_data['product'] = ''; //产品ID  不需要填写
		$post_data['needstatus']='true'; //是否需要状态报告，需要true，不需要false
		$post_data['extno']='';  //扩展码   不用填写
		$url='http://send.18sms.com/msg/HttpBatchSendSM';
		$o='';
		foreach ($post_data as $k=>$v)
		{
			$o.="$k=".urlencode($v).'&';
		}
		$post_data=substr($o,0,-1);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 如果需要将结果直接返回到变量里，那加上这句
		$result = curl_exec($ch);
		$result = explode(',', $result);
		if(!isset($result[1])){
			$data['status'] = false;
			$data['message'] = '信息发送失败，请稍后再试';
			return $data;
		}
		switch($result[1]){
			case 0:
				break;
			case 104:
				$data['status'] = false;
				$data['message'] = '系统忙，请您稍后再点击发送';
				return $data;
			default:
				$data['status'] = false;
				$data['message'] = '信息发送失败，请稍后再试';
				return $data;
		}
		
		$smsData = array('mobile'=>$mobile, 'type'=>$type, 'code'=>$code, 'created'=>$time, 'expired'=>$time+900,
				'status'=>0
		);
		$smsCode = new Smscode($smsData);
		if(!$smsCode->save()){
			$data['status'] = false;
			$data['message'] = '信息发送失败，请等待1分钟后重试';
			return $data;
		}
		
		$data['status'] = true;
		$data['message'] = '信息发送成功';
		return $data;
	}
	
	//暂不可用
	static function sendInvite($mobile, $name, $company, $address, $time, $tel=''){return false;
		$msg = $name . '您好，' . $company . '诚邀您与' . date('Y年m月d日 H:i') . '参加面试，地址：' . $address . (strlen($tel) > 3 ? ('，联系电话:' . $tel) : '' . '。') .
		'';
		$post_data = array();
		$post_data['account'] = '003553';   //帐号
		$post_data['password'] = 'Pnac20150515';  //密码
		$post_data['content'] =urlencode($msg); //短信内容需要用urlencode编码下
		$post_data['mobile'] = $mobile; //手机号码， 多个用英文状态下的 , 隔开
		$post_data['action']='send';
		$post_data['sendTime']='';
		$post_data['extno']='';  //扩展码   不用填写
		$url='http://121.43.107.8:8888/Index.aspx';
		$o='';
		foreach ($post_data as $k=>$v)
		{
			$o.="$k=".urlencode($v).'&';
		}
		$post_data=substr($o,0,-1);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 如果需要将结果直接返回到变量里，那加上这句
		$result = curl_exec($ch);
		print_r($result);exit;
		$result = explode(',', $result);
		if(!isset($result[1])){
			$data['status'] = false;
			$data['message'] = '信息发送失败，请稍后再试';
			return $data;
		}
		switch($result[1]){
			case 0:
				break;
			case 104:
				$data['status'] = false;
				$data['message'] = '系统忙，请您稍后再点击发送';
				return $data;
			default:
				$data['status'] = false;
				$data['message'] = '信息发送失败，请稍后再试';
				return $data;
		}
		
		$data['status'] = true;
		$data['message'] = '信息发送成功';
		return $data;
	}
}


