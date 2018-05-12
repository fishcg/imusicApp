<?php

class Form_Userinfo extends QForm{

	function __construct($action){
		// 调用父类的构造函数
		parent::__construct('form_userinfo', $action);
		
		// 从配置文件载入表单
		$filename = rtrim(dirname(__FILE__), '/\\') . DS . 'userinfo_form.yaml';
		$this->loadFromConfig(Helper_YAML::loadCached($filename));
		// $this->addValidations(Userinfo::meta());
		$this->set('enctype', self::ENCTYPE_MULTIPART);
		$this->addValidations(array(
				'name' => array(
						array(
								'not_empty',
								'姓名不能为空' 
						) 
				),
				'idcard' => array(
						array(
								'not_empty',
								'身份证号不能为空' 
						),
						array(
								'regex',
								'/^[1-9]\\d{5}[1-9]\\d{3}((0\\d)|(1[0-2]))(([0|1|2]\\d)|3[0-1])\\d{3}([0-9]|X)$/',
								'身份证号无效' 
						) 
				),
				'gender' => array(
						array(
								'is_int',
								'性别不能为空' 
						) 
				),
				'birthday' => array(
						array(
								'not_empty',
								'出生年月不能为空' 
						),
						array(
								'is_date',
								'出生年月无效' 
						) 
				),
				'native' => array(
						array(
								'not_empty',
								'籍贯不能为空' 
						) 
				),
				'nation' => array(
						array(
								'not_empty',
								'民族不能为空' 
						) 
				),
				'birthplace' => array(
						array(
								'not_empty',
								'出生地不能为空' 
						) 
				),
				'political' => array(
						array(
								'not_empty',
								'政治面貌不能为空' 
						) 
				),
				
				'mobile' => array(
						array(
								'not_empty',
								'联系电话不能为空' 
						),
						array(
								'is_mobile',
								'邮箱格式不正确' 
						) 
				),
				'email' => array(
						array(
								'skip_empty' 
						),
						array(
								'is_email',
								'邮箱格式不正确' 
						) 
				),
				'address' => array(
						array(
								'not_empty',
								'通讯地址不能为空' 
						) 
				),
				'postcode' => array(
						array(
								'not_empty',
								'通讯邮编不能为空' 
						) 
				),
				'introduce' => array(
						array(
								'not_empty',
								'简介不能为空' 
						) 
				) 
		));
	
	}
}
