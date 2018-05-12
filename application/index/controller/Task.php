<?php

namespace app\index\controller;

use components\Mutils;
use think\exception\HttpException;

class Task extends Base
{
    // 签到加的音币
    const SIGN_ADD_POINT = 3;

    // 签到标识,0：未签到；1：已签到
    const IS_SIGN = 1;

    // 抽奖后缓存存储的值
    const LOTTERY = 1;

    protected $_model_name = 'User';

    protected $beforeActionList = [
        'userOnly' =>  ['except' => ['clearcache', 'clearrecommend']]
    ];

    /**
     * @api {get} /index/task/index 用户任务页面
     * @apiExample {curl} Example usage:
     *     curl -i http://test.com/index/task/index
     * @apiSampleRequest index/task/index
     *
     * @apiVersion 0.1.0
     * @apiName index
     * @apiGroup index/task
     *
     * @apiSuccess {Number} status 请求状态
     * @apiSuccess {Object} 音币、是否签到、是否摸鱼、评论条数组成的数组对象
     *
     * @apiSuccessExample Success-Response:
     *     {
     *       "status": 200
     *       "info": {
     *         "cash": 233,
     *         "is_sign": 1,
     *         "lottery_count": 0,
     *         "comment_count": 2
     *       }
     *     }
     */
	function index(){
		$uid = $this->_user->uid;
		$cash = $this->_model
			->where('uid','=',$uid)
			->find()->cash;
		// @TODO 此处应在迁移时使用 Redis，防止数据丢失和冗余
		//获取评论数，从 Memcache 中取出key的值
		$options = ['type' => 'Memcache'];
		$tu = PRE_TASK . $uid;
		$user_cache = unserialize(cache($tu, '', $options));
        $comment_count = !isset($user_cache['comment']) ? 0 : ($user_cache['comment'] > 3 ? 3 : $user_cache['comment']);
        return [
            'cash' => $cash,
            'is_sign'=> $user_cache['sign'] ?: 0,
            'lottery_count' => isset($user_cache['lottery']) ? $user_cache['lottery'] : 0,
            'comment_count' => $comment_count
        ];
	}

    /**
     * @api {get} /index/task/sign 用户签到
     * @apiExample {curl} Example usage:
     *     curl -i http://test.com/index/task/sign
     * @apiSampleRequest index/task/sign
     *
     * @apiVersion 0.1.0
     * @apiName sign
     * @apiGroup index/task
     *
     * @apiSuccess {Number} status 请求状态
     * @apiSuccess {Object} 签到成功提示语与用户当前音币数
     *
     * @apiSuccessExample Success-Response:
     *     {
     *       "status": 200
     *       "info": {
     *         "message": "签到成功~",
     *         "cash": 233
     *       }
     *     }
     */
	function sign()
    {
		$uid = (int)$this->_user->uid;
        // @TODO: 从 memcache 中取出用户任务缓存值，以后需要使用 Redis 存储该值
        $options = ['type' => 'Memcache'];
        $key = PRE_TASK . $uid;
        $task_cache = unserialize(cache($key, '', $options));
        if(isset($task_cache['sign'])) throw new HttpException(403, '今天已经签过到啦~');
        // 签到信息存入 Memcache
        $task_cache['sign'] = self::IS_SIGN;
        $task_cache_string = serialize($task_cache);
        // 计算距离今日 24:00 时的时间作为此缓存生命时长
        $expire = Mutils::getRemainTime();
        cache($key, $task_cache_string ,['type' => 'Memcache', 'expire' => $expire]);
        $user = $this->_model
            ->where('uid', '=', $uid)
            ->find();
        $user->cash += self::SIGN_ADD_POINT;
        if(!$user->save()) {
            unset($task_cache['sign']);
            $task_cache_string = serialize($task_cache);
            cache($key, $task_cache_string, $options);
            throw new HttpException(500, '服务器正忙，请稍后再试~');
        }
        return ['message' => '签到成功~', 'cash' => $user->cash];
	}

    /**
     * @api {get} /index/task/lottery 用户摸鱼
     * @apiExample {curl} Example usage:
     *     curl -i http://test.com/index/task/lottery
     * @apiSampleRequest index/task/lottery
     *
     * @apiVersion 0.1.0
     * @apiName lottery
     * @apiGroup index/task
     *
     * @apiSuccess {Number} status 请求状态
     * @apiSuccess {Object} 摸鱼成功提示语与用户当前音币数
     *
     * @apiSuccessExample Success-Response:
     *     {
     *       "status": 200
     *       "info": {
     *         "message": "摸到 3 音币",
     *         "cash": 233
     *       }
     *     }
     */
	function lottery()
    {
		$uid = (int)$this->_user->uid;
        $options = ['type' => 'Memcache'];
        $key = PRE_TASK . $uid;
        $task_cache = unserialize(cache($key, '', $options));
        if(isset($task_cache['lottery'])) throw new HttpException(403, '今天已经抽过奖啦~');
        // 签到信息存入 Memcache
        $task_cache['lottery'] = self::LOTTERY;
        $task_cache_string = serialize($task_cache);
        // 计算距离今日 24 时的时间作为此缓存生命时长
        $expire = Mutils::getRemainTime();
        cache($key, $task_cache_string ,['type' => 'Memcache', 'expire' => $expire]);
        $user = $this->_model
            ->where('uid','=',$uid)
            ->find();
        $cash = mt_rand(1, 20);
        $user->cash += $cash;
        if(!$user->save()) {
            unset($task_cache['lottery']);
            $task_cache_string = serialize($task_cache);
            cache($key, $task_cache_string, $options);
            throw new HttpException(500, '服务器正忙，请稍后再试~');
        }
        $message = $cash < 3 ? '运气不好 T_T' : ($cash > 7 ? '人品不错' : '人品一般');
        $message .= '，抽到' . $cash . '音币~';
        return ['message' => $message, 'cash'=> $user->cash];

	}

    /**
     * 调试用，删除全部任务键值
     *
     * @todo 线上环境需要删除该接口
     */
	function clearCache(){
		if (isset($_GET['key'])&&$_GET['key']=='ganggege') {
			$uid_arr= $this->_model
				->column('uid');
			$options = array('type'=>'Memcache');
			foreach ($uid_arr as $val) {
				$tu = 'task_'.$val;
                cache($tu,NULL,$options);
			}
			echo '任务缓存已清除';
		}else{
			echo '禁止访问';
		}
	}

    /**
     * 调试用，删除缓存首页数据的键值
     *
     * @todo 线上环境需要删除该接口
     */
    function clearRecommend(){
        if (isset($_GET['key'])&&$_GET['key']=='ganggege') {
            $uid_arr= $this->_model
                ->column('uid');
            $options = array('type'=>'Memcache');
            cache(APP_INDEX_RECOMMEND, NULL, $options);
            echo '任务缓存已清除';
        }else{
            echo '禁止访问';
        }
    }
}
