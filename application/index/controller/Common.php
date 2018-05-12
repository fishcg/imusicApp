<?php
namespace app\index\controller;

use app\common\controller\Base;

class Common 
{

	function index(){
		echo "common";
	}
	function timgup(){
    	$base64 = $_POST['img'];
    	imgup($base64);
    }
    function fileup(){
        echo 111;

    	/*$file =$_FILES;
    	print_r($file);*/
    }

}