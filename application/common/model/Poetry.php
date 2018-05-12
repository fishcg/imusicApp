<?php
namespace app\common\model;

use app\common\model\Base;

class Poetry extends Base
{
	//评论关联
	public function comments()
	{
		return $this->hasMany('Comment','music_id','id');		
	}
}