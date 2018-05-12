<?php
// +----------------------------------------------------------------------
// | 请求异常抛出
// +----------------------------------------------------------------------
// | Author: fish <353740902@qq.com>
// +----------------------------------------------------------------------

namespace think\exception;

class CHttpException extends HttpException
{
    public function __construct($statusCode, $message = null, $code = 0)
    {
        parent::__construct($statusCode, $message, null, [], $code);
    }
}
