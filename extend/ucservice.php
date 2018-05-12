<?php

class Helper_Ucservice{
	public function __construct(){
		include_once Q::ini('app_config/ROOT_DIR').'/public/ucenter/config.inc.php';
		include_once Q::ini('app_config/ROOT_DIR').'/public/ucenter/uc_client/client.php';
	}
	/**
	 * 会员注册
	 */
	public function uc_register($username, $password, $email){
		$uid = uc_user_register($username, $password, $email);//UCenter的注册验证函数
		if($uid <= 0) {
			if($uid == -1) {
				$error = '用户名不合法';
			} elseif($uid == -2) {
				$error = '包含不允许注册的词语';
			} elseif($uid == -3) {
				$error = '用户名已经存在';
			} elseif($uid == -4) {
				$error = 'Email 格式有误';
			} elseif($uid == -5) {
				$error = 'Email 不允许注册';
			} elseif($uid == -6) {
				$error = '该 Email 已经被注册';
			} else {
				$error = '未定义';
			}
			return json_encode(array("status"=>0,"uid"=>$uid,"message"=>$error));
		} else {
			return json_encode(array("status"=>1,"uid"=>$uid));
		}
	}
	/**
	 * uc登陆
	 * @param unknown_type $username
	 * @param unknown_type $password
	 * @return multitype:unknown Ambigous <mixed, string, multitype:, unknown> |string
	 */
	public function uc_login($username, $password){
		list($uid, $username, $password, $email) = uc_user_login($username,$password);
		if($uid > 0) {
			return array(
				'uid' => $uid,
				'username' => $username,
				'password' => $password,
				'email' => $email
			);
		}elseif($uid == -1) {
			return '用户不存在,或者被删除';
		}elseif($uid == -2) {
			return '密码错误';
		}elseif($uid == -3) {
			return '安全提问错误';
		} else {
			return '未定义';
		}
	}
	/**
	 * 同步登陆
	 * @param unknown_type $uid
	 * @return string
	 */
	public function uc_synlogin($uid){
		return uc_user_synlogin($uid);
	}

}