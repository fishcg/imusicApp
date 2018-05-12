<?php
namespace app\common\model;

use think\Model;

class User extends Model
{
	// 设置数据表（不含前缀）
	protected $name = 'user';
	public function fmusic()
    {
        return $this->belongsToMany('Music','lab_music_user','music_id','uid');
    }
}