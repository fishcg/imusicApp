<?php
namespace app\index\controller;

use app\common\controller\Base;

class Active extends Base
{
	protected $_model_name = 'Active';
	
	function index(){
			$actives= $this->_model
			->where('recycle =0 and status=1')
			->limit(0,6)
			->order('sort', 'asc')
			->select();
	
		//dump($actives);
		echo json_encode($actives);
		exit();
	}
	function view(){
		$id = $_GET['id'];
			$active= $this->_model
			->where('id','=',$id)
			->find();	
		echo json_encode($active);
		exit();
	}

}