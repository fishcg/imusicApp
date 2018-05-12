<?php

class Form_Zone extends QForm {

	function __construct($action) {
		// 调用父类的构造函数
		parent::__construct('form_zone', $action);
		
		// 从配置文件载入表单
		$filename = rtrim(dirname(__FILE__), '/\\') . DS . 'zone_form.yaml';
		$this->loadFromConfig(Helper_YAML::loadCached($filename));
		$this->set('enctype', self::ENCTYPE_MULTIPART);
		$this->addValidations(Zone::meta());
	}
}
