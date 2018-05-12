<?php
namespace app\common\model;

use think\Model;

class Base extends Model
{
    public static function model($className=__CLASS__)
    {
        return new $className();
    }
}