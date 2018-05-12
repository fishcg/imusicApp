<?php
namespace app\admin\controller;

use app\common\controller\Base;

class News extends Base
{
	protected $_model_name = 'News';
	//protected $_form_name = 'MyModule';

	//是否有软删除/回收站
	protected $_recycle = true;

	protected $_where_items = array('name');

}