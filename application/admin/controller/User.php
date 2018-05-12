<?php
namespace app\admin\controller;

use app\common\controller\Base;
use app\common\model\User as UserModel;
class User extends Base
{
	protected $_model_name = 'User';
	//protected $_form_name = 'MyModule';

	//是否有软删除/回收站
	protected $_recycle = true;

	protected $_where_items = array('name');

	function index($set = '', $addtitle = '信息'){
		$set=isset($_GET['sit'])?$_GET['sit']:"";
		$this->assign('sit',$set); 
		//$set=isset($_GET['title'])?$_GET['title']:"";
		$this->assign('title',$addtitle); 
		$where="";
		if($this->_recycle==true){
			$where="recycle =0";
		}
		$count = $this->_model->where($where)->count();
		$count =ceil($count /10);
		$this->assign('count',$count); 
		return view();
	}
/*	function recycle($set = '', $addtitle = '信息'){
		
		return view();
	}*/
	function datalist(){
		if(isset($_POST['sit'])){
			$this->assign('sit',$_POST['sit']); 
		}
		$class="";
		$listCount = isset($_POST['istCount'])?$_POST['istCount']:10;
		$page = intval($_POST['page']);
		$first = ($page-1) *$listCount;
		$where="";
		if($this->_recycle==true){
			$where = "recycle =0";
		}
		if(isset($_POST['recycle'])&&$_POST['recycle']==1){
			$where = "recycle =1";
			$class = "recycle-list";	
		}
		$this->assign('class',$class); 
		$list= $this->_model
			->where($where)
			->limit($first,$listCount)
			->order('uid', 'asc')
			->select();
		$this->assign('list',$list); 
		$count = $this->_model->where($where)->count();
		$count =ceil($count /10);
		$this->assign('count',$count); 
		return view();
	}

	// 新增用户数据
	public function add(){

		$user = new UserModel;
		$user->username = '流年';
		$user->password = '123456';
		$user->email = "34654@qq.com";
		if ($user->save()) {
		return '用户[ ' . $user->username . ':' . $user->uid . ' ]新增成功';
		} else {
		return $user->getError();
		}
	}
	/**
	 * 删除功能，利用TP的软删除
	 * user使用uid，重写delete 
	 */
	function delete(){

		$ids = $this->_request->param('id/a');
		$c = 0;
		$msg = "";
		$where="";
		$recycle = $this->_request->param('recycle',0);
		if($recycle){
			//放入回收站
			$where="recycle =0";
			foreach($ids as $id){
				//$user = new UserModel();
			    $this->_model->data(['uid'=>intval($id), 'recycle'=>1],true)->isUpdate(true)->save();
			    $c++;
			}
			//$c = $this->_model->saveAll($list);
			$msg = "此" . $c .  "条数据已放入回收站";
		}else{
			// 真实删除
			$c = $this->_model->destroy($ids);
			$msg = "此" . $c .  "条数据已经被彻底删除";
		}
		if($c){
			$this->success($msg);
		}else{
			$this->error("删除失败！");
		}
	/*	$user = new UserModel();
		
		$count = $user->where($where)->count();
		$count =ceil($count /10);
		$this->assign('count',$count); */

	}
	//从回收站恢复
	function recovery(){
		$ids = $_POST["id"];
		foreach($ids as $id){
		    $this->_model->data(['uid'=>intval($id), 'recycle'=>0],true)->isUpdate(true)->save();
		}
		return $this->success('成功恢复' . count($ids) . "条数据");
	}
}