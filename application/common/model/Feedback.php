<?php
namespace app\common\model;

use think\Model;

class Feedback extends Model
{
	// 设置数据表（不含前缀）
	protected $name = 'Feedback';
	public function user()
    {
        return $this->hasOne('User','uid','from_uid');
    }

}