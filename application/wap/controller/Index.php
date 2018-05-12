<?php
namespace app\wap\controller;

use app\common\controller\Base;

class Index extends Base
{

	function index(){
		return view();
	}
	function getmusic(){
		$encSecKey = $_POST['encSecKey'];
		$params = $_POST['params'];
		$data = $this->curl_post("http://music.163.com/weapi/song/enhance/player/url",array("encSecKey"=>$encSecKey,"params"=>$params));	
		var_dump($data);  
		exit();
	}
	function curl_post($url, $post) {  
		$refer = "http://music.163.com/";
	    $header=array(
	    	"Cookie: " . "appver=1.5.0.75771",
	    
	    	) ;
	    $options = array(  
	        CURLOPT_RETURNTRANSFER => true,  
	        CURLOPT_HEADER         => false,  
	        CURLOPT_POST           => true,  
	        CURLOPT_POSTFIELDS     => $post,  
	    );  
	  
	    $ch = curl_init($url);  
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    curl_setopt_array($ch, $options);  
	    curl_setopt($ch, CURLOPT_REFERER, $refer);
	    $result = curl_exec($ch);  
	    curl_close($ch); 
	    return $result;  
	}  
}