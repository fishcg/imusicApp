<?php
namespace app\common\controller;

use think\Request;

class Error 
{
    public function index(Request $request)
    {
        return "访问地址" . $request->url(true) . "有误";
    }
}