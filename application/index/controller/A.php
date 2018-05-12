<?php
namespace app\index\controller;

class A
{
    public function index()
    {
      $url = "http://www.acfun.cn";
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $output = curl_exec($ch);
      curl_close($ch);
      echo $output;  

   }
   public function read($id)
   {
   	echo "<title>little-gay</title>";
      $url = "http://www.acfun.cn/a/$id";
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $output = curl_exec($ch);
      curl_close($ch);
      echo $output;  
    }  
}
