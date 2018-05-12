<?php
namespace app\index\controller;

use think\exception\CHttpException;
use think\Request;
use think\Controller;
use \Openssl;

class Base extends Controller
{
	// 控制器要使用的模型的名称
	protected $_model_name;

	// 控制器要使用的模型
	protected $_model;

	// 当前用户
	protected $_user = null;

    // request 实例
    protected $_request;

    // 请求的模块
    protected $_module_name;

    // 请求的控制器
    protected $_controller_name;

    // 请求的动作
    protected $_action_name;
	//前置操作，无值的话为当前控制器下所有方法的前置方法。
	/* protected $beforeActionList = [
		'first',
		'second' =>  ['except'=>'hello'],//表示这些方法不使用前置方法，
		'three'  =>  ['only'=>'hello,data'],//表示只有这些方法使用前置方法。
	]; */

	/**
	 * 控制器初始化
	 */
	public function _initialize()
	{
		parent::_initialize();
		$this->_request = Request::instance();
		$this->assign('_request', $this->_request);
		$this->_model = $this->_get_model();
		$this->_view_value();
		// 根据 token 获取用户登录信息
        $header = $this->request->header();
        if (!isset($header['token'])) throw new CHttpException(403, '非法请求', NOT_LOGIN);
        $cache_options = ['type' => 'Memcache'];
        $token = $header['token'];
        $key = Openssl::decrypt($token, AES_KEY);
        // cache($key, NULL, $cache_options);
        $user_info = unserialize(cache($key, '', $cache_options));
        if ($user_info) {
            $this->_user = (object)$user_info;
        }
	}

	protected function _get_model()
    {

		if(empty($this->_model_name)){
			return false;
		}
		$model = model($this->_model_name);
		if(!is_object($model)){
			return false;
		}else{
			return $model;
		}
	}

	protected function _view_value()
    {
	    $this->assign([
	        '_model' => $this->_model,
	    ]);
	}

    /**
     * 对用户进行登陆验证
     */
	protected function userOnly()
    {
        if (!$this->_user) throw new CHttpException(403, '未登录或登陆已过期，请重新登陆', NOT_LOGIN);
        return true;
    }
}
