<?php
namespace app\admin\controller;

use app\common\controller\Base;

class Index extends Base
{

	function index(){	
		return view();
	}

	function home(){
		// 根据code字段查询导航
		/* $navs = Nav::getByNumber('none');
		// 模板变量赋值
		//dump($navs->children);
		$this->assign('navs',$navs->children); */
		//$m = Module::where('id',1)->value('code');
		/* $c = Controller::getById(1);
		//$a = Action::getById(1);
		dump($c); */
		return view();
	}
	function main(){
		// 根据code字段查询导航
		/* $navs = Nav::getByNumber('none');
		// 模板变量赋值
		//dump($navs->children);
		$this->assign('navs',$navs->children); */
		//$m = Module::where('id',1)->value('code');
		/* $c = Controller::getById(1);
		//$a = Action::getById(1);
		dump($c); */
		return view();
	}
		function index233(){
	
		return view();
	}
}