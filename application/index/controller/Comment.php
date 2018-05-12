<?php
namespace app\index\controller;

use components\Mutils;
use think\db;
use app\common\model;
use think\exception\HttpException;

class Comment extends Base
{
	protected $_model_name = 'Comment';

    protected $beforeActionList = [
        'userOnly'  =>  ['only' => 'comment']
    ];

	function index(){

	}
    //评论列表
	function commentlist(){
		$id = $_GET['id'];
		$comments= $this->_model
			->where(['status' => 1, 'music_id' => $id])
			->order( 'created desc')
			->limit(0,30)
			->select();	
		$row = [];
		foreach($comments as $val){
			$row[] = [
                'content' => $val->content,
                'created' => $val->created,
                'username' => $val->user['name'],
                'useravatar' => $val->user['avatar'],
            ];
		}
		echo json_encode($row);
	}
	 //评论
	function comment()
    {
		$id = $_POST['id'];
		$content = $_POST['content'];
		$created = time();
		try {
            if (!$content) throw new \Exception('评论不能为空哦');
            if (model\Comment::greenCheck($content)) throw new HttpException(403, '评论违规哟~');
            // 缓存中取出用户信息
            $uid = $this->_user->uid;
            $data = ['uid' => $uid, 'music_id' => $id, 'content' => $content, 'created' => $created];
            // 启动事务
            Db::startTrans();
            $row = $this->_model->data($data)->allowField(true)->save();
            if($row){
                $options = ['type' => 'Memcache'];
                $key = Pre_TASK . $uid;
                $user_cache = unserialize(cache($key, '', $options));
                if (!isset($user_cache['comment'])) {
                    $user_cache['comment'] = 1;
                } elseif ($user_cache['comment'] < 4) {
                    $user_cache['comment']++;
                }
                $user_cache_serialize_ = serialize($user_cache);
                $expire = Mutils::getRemainTime();
                cache($key, $user_cache_serialize_, array_merge($options, ['expire' => $expire]));
                if($user_cache['comment'] === 3){
                    $update = Db::table('lab_user')->where('uid', '=', $uid)->setInc('cash', 3);
                    if (!$update) throw new HttpException('评论失败，请稍后再试~');
                }
                Db::commit();
                echo json_encode([
                    'status' => 200,
                    'info' => "评论成功(●'◡'●)",
                ]);
            }else{
                throw new HttpException('评论失败，请稍后再试');
            }
        } catch (HttpException $e) {
            Db::rollback();
            echo json_encode([
                'status' => $e->getStatusCode(),
                'info' => $e->getMessage(),
                'code' => FAIL
            ]);
        }
	}

}