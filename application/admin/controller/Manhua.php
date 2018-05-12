<?php
namespace app\admin\controller;

use app\common\controller\Base;

class Manhua extends Base
{

	public $url='http://m.62fan.com';
    function index(){
    	$contents = file_get_contents($this->url);
    	$str1 = strpos($contents, "<ul class=\"pic pic1\">");
    	$str2 = strlen('<ul class="pic pic1">');
    	$contents = substr($contents,$str1+$str2);
    	$str1 = strpos($contents, "</ul>");
    	$contents = substr($contents,0,$str1);
    	$contents = trim($contents);
    	$ex = '/<a class="pic" href="(.*)"><img alt="(.*)" src="(.*)"><\/a>/';
    	preg_match_all($ex,$contents,$matches);
    	foreach ($matches[1] as $key=>$vo){
    		$a[$key]['url'] = $vo;
    		$a[$key]['title'] = iconv('GB2312','UTF-8',$matches[2][$key]);
    		$a[$key]['tupian'] = 'http://'.$_SERVER['HTTP_HOST'].U('tupian',array('url'=>$matches[3][$key]));
    	}
    	echo json_encode($a);
    }
    function tupian($url){
    	echo $this->gethost($url,false);
    }
    function xq(){
    	$urls = $_GET['urls'];
    	$contents = file_get_contents($this->url.$urls);
    	$str1 = strpos($contents,'<div id="jiazai" style="margin-bottom:-30px; text-align:center; margin-top:20px; height:25px; width:100%"></div>');
    	$str2 = strlen('<div id="jiazai" style="margin-bottom:-30px; text-align:center; margin-top:20px; height:25px; width:100%"></div>');
    	$contents = substr($contents,$str1+$str2);
    	$str1 = strpos($contents,'</li></div>');
    	$contents = substr($contents,0,$str1);
    	$contents = trim($contents);
    	$ex = '/<img alt=".*" src="(.*)" \/>/';
    	preg_match_all($ex,$contents,$matches);
    	$a['tupian'] = 'http://127.0.0.1/'.U('tupian',array('url'=>$matches[1][0]));
    	$ex = '/<a href=\'(.*)\'>.*<\/a>/';
    	preg_match_all($ex,$contents,$matches);
    	$str1 = strripos($urls,'/');
    	$urls = substr($urls,0,$str1+1);
    	if($matches[1][0] == "#"){
    		$a['qianyi'] = $matches[1][0];
    	}else{
    		$a['qianyi'] = $urls.$matches[1][0];
    	}
    	if($matches[1][1] == "#"){
    		$a['houyi'] = $matches[1][1];
    	}else{
    		$a['houyi'] = $urls.$matches[1][1];
    	}
    	echo json_encode($a);
    }
    function gethost($urls,$status=true){
    	$ch = curl_init();
    	//跳过SSL证书
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_URL, $urls);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	$output = curl_exec($ch);
    	curl_close($ch);
    	if($status){
    		$output = json_decode($output,true);
    	}
    	return $output;
    }
}