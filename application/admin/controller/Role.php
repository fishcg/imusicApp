<?php
namespace app\admin\controller;

use app\common\controller\Base;

class Role extends Base
{
	protected $_model_name = 'Role';
	//protected $_form_name = 'MyModule';

	//是否有软删除/回收站
	protected $_recycle = true;

	protected $_where_items = array('name');

	function index(){
		$this->finder();
		return view();
	}
	
}