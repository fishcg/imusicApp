<?php

namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        $id = $_GET['id'] ?? 0;

        echo 'This severs is running！';
        exit();
        $this->assign('id', $id);
          /*$ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
          curl_setopt($ch, CURLOPT_HEADER, 0);
          $output = curl_exec($ch);
          curl_close($ch);
          echo $output;*/
      return view();
    }

    function getRandElements(int $min, int $max, int $length)
    {
        $this->rand();
    }

    function rand() {
        return mt_rand() / mt_getrandmax();
    }
   public function callback()
   {
      $url = "http://112.124.22.79/";
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $output = curl_exec($ch);
      curl_close($ch);
      echo $output;
      /*  echo 111;
   		$filename = "asycImg/" . date('Ymd',time());
   		$dir = iconv("UTF-8", "GBK", $filename);
        if (!file_exists($dir)){
            mkdir ($dir,777,true);
        }
        $name = $filename . '/' . 'test.txt';
        file_put_contents($filename, '11111111111111111111');*/
   }
}	
