<?php

namespace app\index\controller;

class Fly extends Base
{
	protected $_model_name = 'Fly';
	function index(){

	}
	
	function create(){
		$id = $_GET['id'];
		$uid = $_GET['uid'];
		$content = $_GET['content'];
		$time = $_GET['time'];
		$data = array('uid'=>$uid,"music_id"=>$id,"content"=>$content,"time"=>$time);
		if($content==''){
			 echo json_encode(array(
	        	"status"=>0,
	        	'message'=>"弹幕不能为空哟"
	        	));
			 exit();
		}
		$row = $this->_model->data($data)->allowField(true)->save();
	    if($row){
			$arr['status'] = 1;
	        echo json_encode($arr);
	        exit();
	    }else{
	         echo json_encode(array(
	        	"status"=>0,
	        	'message'=>"评论失败，请稍后再试"
	        	));
			 exit();
	    }
	}
	

}