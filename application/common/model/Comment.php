<?php
namespace app\common\model;

use app\common\model\Base;
use components\GreenCheck;


class Comment extends Base
{
	public function user()
    {
        return $this->hasOne('User','uid','uid');
    }

    public static function greenCheck(string $comment)
    {
        $green_check = new GreenCheck();
        $check_result = $green_check->checkText($comment, 'block');
        return $check_result->Allaccord === 1 ? true : false;
    }
}