<?php

class Form_UserRole extends QForm {

	function __construct($action) {
		// 调用父类的构造函数
		parent::__construct('form_userrole', $action);
		
		// 从配置文件载入表单
		$filename = rtrim(dirname(__FILE__), '/\\') . DS . 'userrole_form.yaml';
		$this->loadFromConfig(Helper_YAML::loadCached($filename));
		
		$roles = Role::find("status = 1")->getAll();
		$this['roles']->items = Helper_Array::toHashmap($roles, 'id', 'name');
	}
}
