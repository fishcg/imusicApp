<?php
/**
 * Created by PhpStorm.
 * User: wwwhq
 * Date: 2017/1/16
 * Time: 15:44
 */
namespace app\common\controller;
use think\response\View;

class ControlElement{
    // 元素id
    protected $_id;
    // 元素属性
    protected $_attr;
    // 视图对象
    protected $_view;
    // 视图路径
    protected $_view_path = '__DIR__/../control';
    // 元素的类型
    protected $_type;
    function __construct($id,$attr)
    {
        dump(dirname(__FILE__).'/../control');
        if (empty($id)) {
            return '请传入一个元素';
        }
        if (!is_array($attr) || count($attr) === 0) {
            return '请传入有效的元素属性的数组';
        }

        $this->_type = isset($attr['_ui']) ? $attr['_ui'] : '';
        if (!$this->_type) {
            return '请传入有效的元素类型';
        }
        $this->_id = $id;
        unset($attr['_ui']);
        $this->_attr = $attr;
        $this->_view = new View(['view_path'=>$this->_view_path]);

        //return $this;
        dump($this);
    }

    public static function control() {
        //$this->_view->fetch();
    }

}