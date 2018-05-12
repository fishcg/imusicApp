<?php

class Form_UserLogin extends QForm {

	function __construct($action) {
		// 调用父类的构造函数
		parent::__construct ( 'form_userlogin', $action );
		
		// 从配置文件载入表单
		$filename = rtrim ( dirname ( __FILE__ ), '/\\' ) . DS . 'userlogin_form.yaml';
		$this->loadFromConfig ( Helper_YAML::loadCached ( $filename ) );
		$this->addValidations ( User::meta () );
		$this->addValidations ( array (
				'imgcode_login' => array (
						array (
								'is_imgcode',
								$this ['imgcode_login']->value,
								'验证码不正确' 
						) 
				) 
		) );
	}
}
?>
