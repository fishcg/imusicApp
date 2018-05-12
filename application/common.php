<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function Imgup($base64){

	/*$img = base64_decode($base64);
	print_r($img);
	exit();*/
	header("Content-Type: text/html; charset=base64");
	$url = explode(',',$base64);
	$img = base64_decode($url[1]); 
	$name = time().".jpg";
	$dir = "images";
	//查找当日文件夹名
	$dir_name = date("Ymd",time());   
	$thedir = $dir."/".$dir_name."/"; 
	if(!file_exists($thedir)){			
		mkdir($thedir,0777); 
	}
	$photo =  $thedir.$name;
	$a = file_put_contents($photo, $img);//返回的是字节数
	$arr = array(
		"status"=>'1',
		"url" => '/'.$photo
	);
	echo json_encode($arr);
}