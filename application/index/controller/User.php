<?php

namespace app\index\controller;

use think\db;
use \Openssl;
use think\exception\CHttpException;

class User extends Base
{
	protected $_model_name = 'User';
	//
    protected $beforeActionList = [
        // 'userOnly' //执行任何方法之前都会执行这个first
        // '' =>  ['except'=>'hello'],   //除了hello方法以外的方法执行之前都会先执行一次second
        'userOnly'  =>  ['only' => ['followmusic', 'followuser', 'collect']], //仅在hello和data方法执行之前执行一次three
    ];
	function index(){
		$type = $_GET['type'];
		if($type=="top"){
			$musics= $this->_model
			->where('recycle','=',0)
			->limit(0,6)
			->order('sort', 'asc')
			->select();
		}
		//dump($music);
		echo json_encode($musics);
		exit();
	}

	function login(){
		$code = $_POST['code'];
		$wxinfo = $this->curl_get($code);
		$wxinfo = json_decode($wxinfo,1);
		$userinfo = $_POST['userinfo'];
		$userinfo = json_decode($userinfo);
		try {
            $user= $this->_model
                ->where('wx_openid','=',$wxinfo['openid'] )
                ->find();
            if(empty($user->uid)){
                $data = array(
                    "name" =>$userinfo->nickName,
                    "wx_openid"=>$wxinfo['openid'] ,
                    "avatar" =>$userinfo->avatarUrl,
                    'username' => $wxinfo['openid'] ,
                    'password' =>"wx_123456"
                );
                $add = $this->_model->data($data)->allowField(true)->save();
                if(!$add) throw new CHttpException(500, '注册失败，请联系管理员');
                $user = $this->_model
                    ->where('wx_openid', '=', $wxinfo['openid'] )
                    ->find();
            }
            // 将 user_id 进行 AES 加密作为 token 的值
            $key = Pre_Token . $user->uid;
            $token = Openssl::encrypt($key, AES_KEY);
            $user_info = [
                'uid' => $user->uid,
                'username' => $user->username,
                'name' => $user->name,
                'token' => $token
            ];
            // 包含 token 的用户信息存入 Memcache
            cache($key, serialize($user_info), ['type' => 'Memcache','expire' => ONE_DAY]);
            return $token;
        } catch (\Exception $e) {
            throw new CHttpException(500, $e->getMessage(), FAIL);
        }
	}

	function curl_get($code){
		$url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . WX_APP_ID . '&secret='
            . WX_SECRET_KEY . '&js_code=' . $code . '&grant_type=authorization_code';
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_HEADER, 0);	
		$res = curl_exec($ch);
		curl_close($ch); 	
	    return $res ;
	}

	function view(){
		$id = $_GET['id'];
			$music= $this->_model
			->where('id','=',$id)
			->find();	
		echo json_encode($music);
		exit();
	}

    /**
     * @api {get} /index/user/isfollow 是否关注或点赞
     * @apiExample {curl} Example usage:
     *     curl -i http://www.test.cn/index/user/isfollow
     * @apiSampleRequest /index/user/isfollow
     * @apiVersion 0.4.0
     * @apiName isfollow
     * @apiGroup /index/user
     *
     * @apiParam {Number} music_id 音频 ID
     * @apiParam {Number} to_uid UP主 ID
     *
     * @apiSuccess {Number} status 请求状态
     * @apiSuccess {Object} info 关注、点赞状态
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "info":
     *       {
     *         "follow": 1,
     *         "zan": 1,
     *         "follow_user": 1
     *       }
     *     }
     *
     */
	function isfollow()
    {
		$uid = $this->_user->uid ?? 0;
		$music_id = (int)$_GET['music_id'];
		$to_uid = (int)$_GET['to_uid'];
		$follow = Db::table('lab_music_user')->where(['uid' => $uid, 'music_id' => $music_id])->count();
		$zan = Db::table('lab_music_zan')->where(['uid' => $uid, 'music_id' => $music_id])->count();
		$follow_user = Db::table('lab_user_follow')->where(['uid' => $to_uid, 'by_uid' => $uid])->count();
		return ['follow' => $follow, 'zan' => $zan, 'follow_user' => $follow_user];
	}

    /**
     * @api {post} /index/user/collect 收藏歌曲
     * @apiExample {curl} Example usage:
     *     curl -i http://www.test.cn/index/user/collect
     * @apiSampleRequest /index/user/collect
     * @apiVersion 0.4.0
     * @apiName collect
     * @apiGroup /index/user
     *
     * @apiParam {Number} music_id 音频 ID
     *
     * @apiSuccess {Number} status 请求状态
     * @apiSuccess {String} info 收藏或取消收藏的提示信息
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": 200,
     *       "info": {
     *         "is_collect": true,
     *         "message": "收藏成功~"
     *       }
     *     }
     *
     */
	function collect()
    {
		$music_id = (int)$_POST['music_id'];
		if (!$music_id) throw new CHttpException(400, '参数错误');
        $uid = $this->_user->uid;
		$follow = Db::table('lab_music_user')->where(['uid' => $uid, 'music_id' => $music_id])->count();
		if ($follow) {
            if (!Db::table('lab_music_user')->where(['uid' => $uid, 'music_id' => $music_id])->delete()) {
                throw new CHttpException(500, '取消收藏失败，请联系管理员');
            };
			return ['is_collect' => false, 'message' => '已取消收藏'];
		}
        $data = ['uid' => $uid, 'music_id' => $music_id];
        if (!Db::table('lab_music_user')->insert($data)) {
            throw new CHttpException(500, '收藏失败，请联系管理员');
        }
        return ['is_collect' => true, 'message' => '收藏成功~'];
	}

	function  zan(){
		$music_id = $_GET['music_id'];
		$uid = $_GET['uid'];
		$num = Db::table('lab_music_zan')->where(array('uid'=>$uid,"music_id"=>$music_id ))->count();
		if($num>0){
			Db::table('lab_music_zan')->where(array('uid'=>$uid,"music_id"=>$music_id ))->delete();
			echo json_encode(array("status"=>"quxiao"));
		}
		else{
			$data = array('uid'=>$uid,"music_id"=>$music_id );
			Db::table('lab_music_zan')->insert($data);
			echo json_encode(array("status"=>"zaned"));
		}
	}
	function follow1(){
		$user= $this->_model
			->where(array('uid'=>1))
			->find();
		$data= array("music_id"=>20);
		$user->fmusic()->save($data);
		dump($musics->fmusic);
		exit();
	}
	function  followuser(){
		$uid = $_GET['uid'];
		$by_uid = $_GET['by_uid'];
		$num = Db::table('lab_user_follow')->where(array('uid'=>$uid,"by_uid"=>$by_uid ))->count();
		if($num>0){
			Db::table('lab_user_follow')->where(array('uid'=>$uid,"by_uid"=>$by_uid ))->delete();
			echo json_encode(array("status"=>"quxiao"));
		}
		else{
			$data = array('uid'=>$uid,"by_uid"=>$by_uid );
			Db::table('lab_user_follow')->insert($data);
			echo json_encode(array("status"=>"followed"));
		}
	}

    /**
     * 获取用户收藏的音频
     */
    function followMusic()
    {
        $uid = $this->_user->uid;
		$user = $this->_model->where(['uid' => $uid])->find();
		$like_musics = array_map(function ($music) {
            return [
                'id' => $music['id'],
                'author' => $music['author'],
                'subject' => ct($music['subject'],14,'..'),
                'photo' => $music['photo'],
                'created' => $music['created']
            ];
        }, $user->fmusic);
		return $like_musics;
	}

}