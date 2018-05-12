<?php

class Form_UserRegister extends QForm {

	function __construct($action) {
		// 调用父类的构造函数
		parent::__construct('form_userregister', $action);
		
		// 从配置文件载入表单
		$filename = rtrim(dirname(__FILE__), '/\\') . DS . 'userregister_form.yaml';
		$this->loadFromConfig(Helper_YAML::loadCached($filename));
		$this->addValidations(User::meta());
		$this->set('enctype', self::ENCTYPE_MULTIPART);
		
		// $this['cids']->addValidations(array($this, 'checkNewPassword'),
		// '两次输入的密码必须一致');
		$this->addValidations(array(
				'password2' => array(
						array(
								'not_empty',
								'核对密码不能为空' 
						),
						array(
								array(
										$this,
										'checkNewPassword' 
								),
								'两次输入的密码必须一致' 
						) 
				),
				'imgcode_register' => array(
						array(
								'is_imgcode',
								$this['imgcode_register']->value,
								'验证码不正确' 
						) 
				) 
		));
	}

	/**
	 * 检查两次输入的密码是否一致
	 */
	function checkNewPassword($password) {

		return ($this['password2']->value == $this['password']->value);
	}
}
