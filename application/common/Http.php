<?php
namespace app\common;

use Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;

class Http extends Handle
{
    public function render(Exception $e)
    {
        // 参数验证错误
        if ($e instanceof ValidateException) {
            return json($e->getError(), 422);
        }

        // 请求异常
        if ($e instanceof HttpException) {
            $error = json_encode([
                'status' => $e->getStatusCode(),
                'info' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return response($error, $e->getStatusCode());
        }

        // 其他错误交给系统处理
        return parent::render($e);
    }

}