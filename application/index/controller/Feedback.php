<?php

namespace app\index\controller;

class Feedback extends Base
{
    protected $_model_name = 'Feedback';
	function index(){
		echo "Feedback";
	}
	function ajaxlist(){
    	$from_uid = $_GET['uid'];
        $feedbacks = $this->_model
            ->where("from_uid=$from_uid OR to_uid=$from_uid")
            ->order('created asc')
            ->select();
        $row = array();
        foreach($feedbacks as $val){
            $row[] =array(
                    'from_uid' => $val->from_uid,
                    'content' => $val->content,
                    'created' => $val->created,
                    'username' => $val->user['name'],
                    'useravatar' => $val->user['avatar'],
                );

        }
       echo json_encode($row);
       exit();
    	
    }
    function create(){
        $from_uid = $_GET['uid'];
        $content = $_GET['content'];
        //保存user
        $feedback = $this->_model->where('from_uid','=',$from_uid)->order('created DESC')->find();
        $time = time()-$feedback['created'];
        if($time>15){
            $device = $_GET['device'] . ";" . $_GET['version'];
            $content .= "[" . $device . "]";
        }
        //存储up信息
        $f['from_uid'] = $from_uid;
        $f['to_uid'] = 0;
        $f['created'] = time();
        $f['to_status'] = 0;
        $f['content'] = $content;
        $row = $this->_model->data($f)->save();
        if($row){
                echo  json_encode(array(
                        'status' =>1,
                        'message' =>'感谢您的反馈',
                    ));
                exit();
            }else{
                 echo  json_encode(array(
                        'status' =>0,
                        'message' =>'网络错误，请稍后再试',
                    ));
                exit();
            }
    }

}