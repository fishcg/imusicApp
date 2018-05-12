<?php
namespace app\common\controller;

use think\exception\HttpException;
use think\Request;
use think\Controller;
use think\db;
use \Openssl;

class Base extends Controller
{
	// 控制器要使用的模型的名称
	protected $_model_name;

	// 控制器要使用的模型
	protected $_model;

	// 表单要使用的模型的名称
	protected $_form_name;

	// 表单要使用的模型
	protected $_form;

	// 是否使用回收站
	protected $_recycle = false;

	// 当前表单是否生成post_key
	protected $_post_key = false;

	// 控制器要使用哪些字段进行过滤
	protected $_where_items = array();

	// 过滤条件
	protected $_where = array();

	// 增删查改按钮控制
	protected $_cud = array('create','update','delete');

	// 列表页的参数
	protected $_finder = 'datagrid';

	//树形列表的时候显示的字段
	protected $_treeField = 'name';
	// 当前用户
	protected $_user = null;

    // request 实例
    protected $_request;

    // 请求的模块
    protected $_module_name;

    // 请求的控制器
    protected $_controller_name;

    // 请求的动作
    protected $_action_name;
	//前置操作，无值的话为当前控制器下所有方法的前置方法。
	/* protected $beforeActionList = [
		'first',
		'second' =>  ['except'=>'hello'],//表示这些方法不使用前置方法，
		'three'  =>  ['only'=>'hello,data'],//表示只有这些方法使用前置方法。
	]; */

	/***
	 * 控制器初始化
	 */
	public function lll()
	{
		echo 111;
		exit();
	}
	public function _initialize()
	{
		parent::_initialize();
		$this->_request = Request::instance();
		$this->assign('_request',$this->_request);

		$this->_model = $this->_get_model();
		//$this->_form = $this->_get_form();
		$this->_view_value();
	}

	protected function _get_model(){

		if(empty($this->_model_name)){
			return false;
		}
		$model = model($this->_model_name);
		if(!is_object($model)){
			return false;
		}else{
			return $model;
		}
	}

	/* protected function _get_form(){

		if(empty($this->_form_name)){
			return false;
		}

		$form = form($this->_form_name);

		if(!is_object($form)){
			return false;
		}else{
			return $form;
		}
	}*/

	protected function _view_value(){

	    $this->assign([
	        '_cud'  => $this->_cud,
	        '_finder' => $this->_finder,
	        '_recycle' => $this->_recycle,
	        '_model' => $this->_model,
	        '_where' => $this->_where,
	        '_where_items' => $this->_where_items,
	    	'_treeField' => $this->_treeField,
	        '_form' => $this->_form
	    ]);
	}

	function index(){
		$this->finder();
		return view();

	}
	function datalist(){
		
		$this->finder_ajax();
		return view();
	}
	function finder($set = '', $addtitle = '信息'){
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
	}
	function finder_ajax(){
		if(isset($_POST['sit'])){
			$this->assign('sit',$_POST['sit']); 
		}
		$class="";
		$listCount = isset($_POST['istCount'])?$_POST['istCount']:10;
		$page = intval($_POST['page']);
		$first = ($page-1) *$listCount;
		$where="";
		if($this->_recycle==true){
			$where="recycle =0";
		}
		if(isset($_POST['recycle'])&&$_POST['recycle']==1){
			$where = "recycle =1";
			$class = "recycle-list";	
		}
		$this->assign('class',$class); 
		$list= $this->_model
			->where($where)
			->limit($first,$listCount)
			->order('id', 'asc')
			->select();
		$this->assign('list',$list); 
		$count = $this->_model->where($where)->count();
		$count =ceil($count /10);
		$this->assign('count',$count); 
		
	}


	function create(){
		$sit=isset($_GET['sit'])?$_GET['sit']:"";
		if(!empty($sit)){
			$this->assign('sit',$sit); 	
			return view();
		}else{
			if(isset($_POST['content'])){
				$_POST['content']= htmlspecialchars($_POST['content']);				
			}

			$row = $this->_model->data($_POST)->allowField(true)->save();
		    if($row){
		        $this->success("新增成功");
		    }else{
		        $this->error("新增失败");
		    }
		}
        // 指定表单动作
        /*if ($this->_form->_attrs['action'] == '') {
            $this->_form->_attrs['action'] = url( request()->module().'/' . request()->controller(). "/create");
        }
		if($this->_request->param('_ajax')){
		    $row = $this->_model->data($_POST)->allowField(true)->save();
		    if($row){
		        $this->success("新增成功");
		    }else{
		        $this->error("新增失败");
		    }
		}else{

            $this->assign('form',$this->_form);
            //$this->assign('form_attrs',$this->_form->_attrs);
            //$control = new ControlElement('news',array('_ui'=>'radiogroup','name'=>'andy','url'=>'/www/home'));
            //dump($control);
            //dump($this->_form);
            //exit;
		    return view();
		}*/
	}

	function update(){

		if($this->_request->param('_ajax')){
			$fields =$this->_model->_fields;
			foreach ($_POST as $key=>$val){
				if(!array_key_exists($key, $fields)){
					unset($_POST[$key]);
				}
			}
		    $row = $this->_model->update($_POST);
		    if($row){
		        $this->success("更新成功");
		    }else{
		        $this->error("更新失败");
		    }
		}else{
			$id = $this->_request->param('id');
			$row = $this->_model->find($id);
			$this->assign('row',$row);
		    return view();
		}
	}

	/**
	 * 删除功能，利用TP的软删除
	 */
	function delete(){

		$ids = $this->_request->param('id/a');
		$c = 0;
		$msg = "";
		$where="";
		$recycle = $_POST['recycle'];
		if($recycle){
			//放入回收站
			$where="recycle =0";
			foreach($ids as $id){
				//$user = new UserModel();
			    $this->_model->data(['id'=>intval($id), 'recycle'=>1],true)->isUpdate(true)->save();
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

	}

	function recycle($set = '', $title = '信息'){
		$set=isset($_GET['sit'])?$_GET['sit']:"";
		$this->assign('sit',$set);
		//$set=isset($_GET['title'])?$_GET['title']:"";
		$this->assign('title',$title); 
	    $where="";
		if($this->_recycle==true){
			$where="recycle =1";
		}
		$count = $this->_model->where($where)->count();
		$count =ceil($count /10);
		$this->assign('count',$count); 
		return view();	
	}
	function recycle_ajax(){
		if(isset($_POST['sit'])){
			$this->assign('sit',$_POST['sit']); 
		}
		$listCount = isset($_POST['istCount'])?$_POST['istCount']:10;
		$page = intval($_POST['page']);
		$first = ($page-1) *$listCount;
		$where="";
		if($this->_recycle==true){
			$where="recycle =1";
		}
		$list= $this->_model
			->where($where)
			->limit($first,$listCount)
			->order('id', 'asc')
			->select();
		$this->assign('list',$list); 
		$count = $this->_model->where($where)->count();
		$count =ceil($count /10);
		$this->assign('count',$count); 
		
	}

	//从回收站恢复
	function recovery(){
		$ids = $this->_request->param($this->_model->getPk() . '/a');
		foreach($ids as $id){
		    $this->_model->data(['id'=>intval($id), 'recycle'=>0],true)->isUpdate(true)->save();
		}
		return $this->success('成功恢复' . count($ids) . "条数据");
	}

	//软恢复
	function softRestore(){
	    $ids = $this->_request->param($this->_model->getPk() . '/a');
	    $this->_model->restore("id in(" . implode(',', $ids) . ")");
	    return $this->success('成功恢复' . count($ids) . "条数据");
	}

	/**
	 * 审核
	 */
	function verify() {

	    if ($this->_request->isPost()) {

	    	$id = $this->_request->param($this->_model->getPk() . '/a');
	        if (!is_array($id)) {
	        	$id = intval($id);
	        }

	        if (empty($id)) {
	            return $this->error('操作错误，主键不能为空');
	        }

	        if (!isset($_POST['status'])) {
	            return $this->error('操作错误，没有传递修改的值');
	        }

	        // 获得模型的属性
	        $props = $this->_model->_fields;

	        // 修改 update_uid
	        /* if ($this->_user->uid) {
	            if (isset($props['update_uid'])) {
	                $_POST['update_uid'] = $this->_user->uid;
	            }
	            if (isset($props['verify_uid'])) {
	                $_POST['verify_uid'] = $this->_user->uid;
	            }
	            if (isset($props['verified'])) {
	                $_POST['verified'] = time();
	            }
	        } */
			if(is_array($id)){
				$list = [];
				foreach ($id as $val){
					$list[] = [$this->_model->getPk()=>$val,'status'=>$_POST['status']];
				}
				//dump($list);exit;
				$this->_model->allowField(true)->saveAll($list);
			}else{
				$this->_where['{$this->_model->getPk()}'] = $id;
				$this->_model->allowField(true)->save($_POST,$this->_where);
			}

	        return $this->success('操作成功');
	    }
	}
	/***
	 * 空操作
	 */
	public function _empty()
	{
		//把所有城市的操作解析到city方法
		return "访问地址有误，方法不存在";
	}

	/**
	 * 向数据集里面追加获取器字段
	 * @param unknown_type $data 查询数据集
	 * @param unknown_type $attr 要追加字段列表
	 * @return unknown
	 */
	protected function appendAll($data,$attr = []){
		if(empty($attr)){
			return $data;
		}
		foreach ($data as $key=>$val){
			foreach ($attr as $row){
				if(isset($val->$row)){
					$data[$key][$row] = $val->$row;
				}
			}
		}
		return $data;
	}
	protected  function get($parameter = null, $default = null)
    {
        if (is_null($parameter))
            return $_GET;
        return isset($_GET[$parameter]) ? $_GET[$parameter] : $default;
    }

    public function  allToArray($items){

    	$item = [];
    	foreach ($items as $key=>$val){
    		$item[$key] = $val->toArray();
    	}
    	return $item;
    }
    //编辑器传图
	public function imgup(){

	   	$tem_name = time();
		//文件名
		$name = $_FILES['file']['name'] ;
		//文件尺寸
		$size =  $_FILES['file']['size'] ;
		//文件后缀名
		$type = substr($name, strrpos( $name, '.')+1);
		//echo $_FILES['file']['tmp_name'] ;
		//文件上传路径
		$dir = "images";
		//查找当日文件夹名
		$dir_name = date("Ymd",time());   
		$thedir = $dir."/".$dir_name."/"; 
		if(!file_exists($thedir)){			
			mkdir($thedir,0777); 
		}
		$dir =  $thedir;
		$max_size = 6;
		$max_size =  intval($max_size) * 1048576;
		if($size >$max_size){
			echo json_encode(
		    	array(
		    		"code" => 1,
		    		"meg" => "照片不能超过".$max_size ,
		    	)
		    );
		    exit();
		}
		//你可以加上，文件类型，大小等判断
		if(move_uploaded_file($_FILES['file']['tmp_name'],$dir.$tem_name.".". $type)){
			$img_info = getimagesize($dir.$tem_name.".". $type);
			$img = new \Imgup();
			if($img_info[0]>1100){
				$img->load($dir.$tem_name.".". $type)
				->width(1000)	//设置生成图片的宽度，高度将按照宽度等比例缩放
				//->height(200)	//设置生成图片的高度，宽度将按照高度等比例缩放
				//->size(300,300)	//设置生成图片的宽度和高度
				//->fixed_given_size(true)	//生成的图片是否以给定的宽度和高度为准
				->keep_ratio(true)		//是否保持原图片的原比例
				->quality(60)	//设置生成图片的质量 0-100，如果生成的图片格式为png格式，数字越大，压缩越大，如果是其他格式，如jpg，gif，数组越小，压缩越大
				->save($dir.$tem_name.".". $type);	//保存生成图片的路径
			}
			
		    echo json_encode(
		    	array(
		    		"code" => 0,
		    		"meg" => "上传成功",
		    		"data" => array(
		    				"src" => '/'.$dir.$tem_name.'.'.$type,
		    				"title" => ""
		    		)
		    	)
		    );
		    exit();
		  }
		  else
		  {
		    echo json_encode(
		    	array(
		    		"code" => 1,
		    		"meg" => "上传失败",
		    	)
		    );
		    exit();
		  }

	}

	protected function userOnly()
    {
	    $header = $this->request->header();
        if (!isset($header['token'])) throw new HttpException(403, '非法请求', NOT_LOGIN);
        $cache_options = ['type' => 'Memcache'];
        $token = $header['token'];
        $key = Openssl::decrypt($token, AES_KEY);
        // cache($key, NULL, $cache_options);
        $user_info = unserialize(cache($key, '', $cache_options));
        if (!$user_info) throw new HttpException(403, '未登录或登陆已过期，请重新登陆', NOT_LOGIN);
        $this->_user = (object)$user_info;
    }
}
