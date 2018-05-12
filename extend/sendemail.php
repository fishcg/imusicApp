<?php

class Helper_Sendemail {

	/**
	 *
	 * @param 称呼 name
	 * @param 主题 title
	 * @param 收件人 email
	 * @param 邮箱内容 content
	 * @param unknown_type $name
	 */
	static function sendEmail($name, $title, $email, $content) {

		$set = Setting::cache();
		if(isset($set['is_send_mail']['value']) && $set['is_send_mail']['value']){
			$config = array(
					'host' => 'mail.tyrbl.com',
					'port' => 25,
					'user' => 'noreply@tyrbl.com',
					'pass' => 'gzlit123'
			);
			$res = @Helper_Email::send_mails($name, $email, $title, $content, $config, '无界投融', '', '', 1);
			return $res;
		}
		return 0;
	}
}