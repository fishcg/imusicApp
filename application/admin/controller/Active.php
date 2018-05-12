<?php
namespace app\admin\controller;

use app\common\controller\Base;

class Active extends Base
{
	protected $_model_name = 'Active';
	//protected $_form_name = 'MyModule';

	//是否有软删除/回收站
	protected $_recycle = true;

	protected $_where_items = array('name');

	
}