<?php

class Form_User extends QForm {

	function __construct($action) {
		// 调用父类的构造函数
		parent::__construct('form_user', $action);
		
		// 从配置文件载入表单
		$filename = rtrim(dirname(__FILE__), '/\\') . DS . 'user_form.yaml';
		$this->loadFromConfig(Helper_YAML::loadCached($filename));
		$this->addValidations(User::meta());
		$this->set('enctype', self::ENCTYPE_MULTIPART);	
		
		$roles = Role::find("status = 1 and code not in ('guest')")->getAll();
		$this['roles']->items = Helper_Array::toHashmap($roles, 'id', 'name');
		
		// 检查用户名是否存在
		$this->addValidations(array(
				'password2' => array(
						array(
								array(
										$this,
										'checkNewPassword' 
								),
								'两次输入的密码必须一致' 
						) 
				),
				'username' => array(
						array(
								array(
										$this,
										'checkUsername' 
								),
								'用户帐号已存在' 
						) 
				) 
		)
		);
	}

	function checkUsername() {

		$username = $this['username']->value;
		if (strlen($username) > 0) {
			$where['username'] = $username;
			$uid = $this['uid']->value > 0 ? $this['uid']->value : 0;
			if ($uid > 0) {
				$where[] = 'uid != ' . $this['uid']->value;
			}
			$user = User::find($where)->query();
			
			if ($user->uid) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 检查两次输入的密码是否一致
	 */
	function checkNewPassword($password) {

		return ($this['password2']->value == $this['password']->value);
	}
}
