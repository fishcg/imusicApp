<?php
namespace app\index\controller;

use Think\Db;
use app\common\model\Music;
use think\exception\CHttpException;
use think\exception\HttpException;

include_once('./simple_html_dom.php');
class Musicm extends Base
{
	protected $_model_name = 'Music';

    /**
     * @api {post} /index/musicm/index 小程序首页
     * @apiExample {curl} Example usage:
     *     curl -i http://www.test.cn/index/musicm/index
     * @apiSampleRequest /index/musicm/index
     * @apiVersion 0.4.0
     * @apiName index
     * @apiGroup /index/musicm
     *
     * @apiSuccess {String} status 请求状态
     * @apiSuccess {Object} info  请求数据详情
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "info":
     *       {
     *          "banner": [],
     *          "channel": [...4],
     *          "sound": [...4],
     *          "seiyuu": [...4],
     *          "album": [...]
     *       }
     *     }
     *
     */
	function index()
    {
        if (!($recommend = json_decode(cache(APP_INDEX_RECOMMEND, '', ['type' => 'File'])))) {
            $recommend = Music::model()->getRecommendMusics();
            $recommend_json = json_encode($recommend);
            cache(APP_INDEX_RECOMMEND, $recommend_json, ['type' => 'File', 'expire' => HALF_DAY]);
        }
        return $recommend;
	}

	/**
	 * 获取 M 站首页
	 */
	function mindex()
    {
    	$url = 'http://www.missevan.com/';
    	$html = $this->curl_get($url);
    	echo $html;
    }

    /**
     * @api {post} /index/musicm/view h5首页
     * @apiExample {curl} Example usage:
     *     curl -i http://www.test.cn/index/musicm/view
     * @apiSampleRequest /index/musicm/view
     * @apiVersion 0.4.0
     * @apiName musicm
     * @apiGroup /index/musicm
     *
     * @apiParam {Number} id 音频 ID
     *
     * @apiSuccess {Number} status 请求状态
     * @apiSuccess {Object} info 音频相关信息
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "info":
     *       {
     *         "id": 2,
     *         "subject": "起风了",
     *         "shortsubject": "起风了",
     *         "summary": "来自i音声",
     *         "author": "周星驰"，
     *         "url": "http://www.test.com/sound/test.mp3"
     *         ..
     *       }
     *     }
     *
     */
	function view(){
		if (!isset($_GET['id'])) {
		    throw new HttpException(400, '参数错误');
        }
        $id = (int)$_GET['id'];
		$music = $this->_model->getMusic($id);
		try{
			$music->views += 1;
			$music->save();
			$user = Db::table('lab_user')->where('uid','=',$music->uid)->find();
			$user_summary = $user['summary'] ? $user['summary']: '这个人很懒，什么都没留下~';
			$url_32 = $str =  str_replace("/32BIT","",$music->url_32);
			return [
				'id' =>$music->id,
				'subject' =>$music->subject,
				'shortsubject' =>ct($music->subject,"16","..."),
				'summary' =>$music->summary,
				'author' =>$music->author,
				'url' =>$music->url,
				'url_32' =>$url_32,
				'created' =>$music->created,
				'views' =>$music->views,
				'photo' =>$music->photo,
				'playtime' =>$music->playtime,
				'lyric' =>$music->lyric,
				'flys' =>$music->flys,
				'user_id'=>$user['uid'],
				'user_avatar'=>$user['avatar'],
				'user_name'=>$user['name'],
				'user_summary'=>$user_summary,
            ];
		}catch(\Exception $e){
			throw new CHttpException(500, $e->getMessage());
		}
	}

	function rank(){
		$musics= $this->_model
			->where('recycle','=',0)
			->order( 'views desc','sort asc')
			->limit(0, 100)
			->select();
		$row = array();
		foreach($musics as $val){
			$row[] = array(
				'id' => $val['id'],
				'author' => $val['author'],
				'subject' => ct($val['subject'],17,'..'),
				'photo' => $val['photo'],
				'created' => $val['created'],
				'views' => $val['views'],
				'comments' => count($val['comments'])
				);
		}
		echo json_encode($row);
		exit();
	}
	function categorylist(){
		$id = $_GET['id'];
		$musics= $this->_model
			->where(array('recycle'=>0,'music_category_id'=>$id))
			->order( 'views desc','sort asc')
			->limit(0, 100)
			->select();	
		$row = array();
		foreach($musics as $val){
			$row[] = array(
				'id' => $val['id'],
				'author' => $val['author'],
				'subject' => ct($val['subject'],20,'..'),
				'photo' => $val['photo'],
				'created' => $val['created'],
				'views' => $val['views'],
				'comments' => count($val['comments'])
				);
		}
		echo json_encode($row);
		exit();
	}
	
	//生成歌词
	function lyric(){
		if(isset($_GET['music_id'])){
			$geciData =$this->get_music_lyric($_GET['music_id']);
			$geci = json_decode ($geciData);
			if(isset($geci->lrc->lyric)){
				$geci = $geci->lrc->lyric;
				$geci = explode("\n",$geci);
				//$html = "[01:32:12] 作曲 : 周杰伦";
				//preg_match_all('/\[.*\]/',$html,$result);
				$arr =array();
				foreach($geci as $val){
					$key = substr($val, 1, 5);
						
					$val1 = substr($val,10);
					$pattern = array(
							"/[[:punct:]]/i", //英文标点符号
							'/[ ]{2,}/'
					);
					$val1 = preg_replace($pattern,' ',$val1);
						
					$arr[$key] = $val1;
				}
				$arr = json_encode($arr);
				//$a = explode("\br",$geci);
				//echo $geci ;
				echo $arr;
				exit();
			}
		}
	}
	//歌曲
	function create(){
		$themusic = $this->_model
			->where('music_id','=', $_GET['music_id'])
			->find();	
		if(count($themusic) !=0){
				echo  $themusic->id;
				exit();
		}else{
			$music = $this->get_music_info($_GET['music_id']);
			$str =  str_replace("\u0000","_",$music);
			$music = json_decode($str);
			if($music->state!="success"||$music->info->sound->soundurl==""){
				echo  0;
				exit();
			}
			$summary = '';
			try{
				$summary = $music->info->sound->intro;
				$summary =  strip_tags($summary);
			}catch(\Exception $e){
				$summary = '该音频木有简介~';
			}
			
			$m['music_id'] = $_GET['music_id'];
			$m['url'] = "http://static.missevan.com/sound/" . $music->info->sound->soundurl;
			$m['url_32'] = "http://static.missevan.com/sound/" . $music->info->sound->soundurl_32;
			$m['url_64'] = "http://static.missevan.com/sound/" . $music->info->sound->soundurl_64;
			$m['subject'] = $music->info->sound->soundstr;
			$m['photo'] = $music->info->sound->front_cover;
			$m['author'] = $music->info->sound->username;
			$m['summary'] = $summary;
			//获取视频时长
			/*$getID3 = new getID3();
			$ThisFileInfo = $getID3->analyze($m['url']); //分析文件，$path为音频文件的地址
			dump($ThisFileInfo);
			$fileduration=$ThisFileInfo['playtime_seconds']; //这个获得的便是音频文件的时长
			$m['playtime'] = $music->songs[0]->bMusic->playTime/1000;*/
					
			/*$lrc_json =$this->get_music_lyric($m['subject']);
			if($lrc_json!="-1"){
				$m['lyric'] = $lrc_json;
				//echo $m['lyric'] ;
			}*/
			$m['created'] = time();
			$m['uid'] = $music->info->user->id;
			$row = $this->_model->data($m)->save();
			//保存user
			$user = Db::table('lab_user')->where('uid','=',$m['uid'])->find();			
			if(count($user) == 0){
				//存储up信息
				$u['uid'] = $m['uid'];
				$u['summary'] = $music->info->user->intro;
				$u['name'] = $music->info->user->username;
				$u['avatar'] = $music->info->user->icon;
				$u['username'] = "upup".$m['uid'];
				$u['password'] = md5("123456");
				$u['reg_time'] = $m['created'];
				$uid = Db::table('lab_user')->insert($u);
			}
			//返回歌曲id
		    if($row){
		    	$themusic = $this->_model
					->where('music_id','=',$_GET['music_id'])
					->find();
				echo  $themusic->id;
				exit();
		    }else{
		    	echo  0;
		        exit();
		    }
		}
	}

	function curl_get($url)
	{
	    $refer = "http://www.missevan.com/";
	    $header = [
	    	'Cookie: Hm_lvt_d4dd9bd2c2f9a6a278c378eda69cd865=1520569601; PHPSESSID=74fbm5j1l4tdj8kdi5717seh55; token=5aaa362839d29a10d1d8759f%7C1521104424%7C6fc7995e2b318447; SERVERID=50dcbb92ed530d21aa89ff907a887973|1521107038|1521080824; Hm_lvt_91a4e950402ecbaeb38bd149234eb7cc=1521080827,1521104371,1521104413,1521104434; Hm_lpvt_91a4e950402ecbaeb38bd149234eb7cc=1521107039',
	    	'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:53.0) Gecko/20100101 Firefox/53.0',
			'Host: www.missevan.com'

	    ];
	    #	"User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:53.0) Gecko/20100101 Firefox/53.0",
	    #	"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
	    #	"Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3",
	    #	"Accept-Encoding: gzip, deflate",
	    #	"Connection: keep-alive",
	    #	"Upgrade-Insecure-Requests: 1",
	    #	"Cache-Control: max-age=0",
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	   	curl_setopt($ch, CURLOPT_REFERER, $refer);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    $output = curl_exec($ch);
	    curl_close($ch);
	    return $output;

	}

	function search(){
		$name =	 $_GET['name'];
		if(isset($_GET['page'])){
			$page =$_GET['page'];
		}else{
			$page =1;
		}
		//$name =rawurlencode("芊芊") ;
		$url = "http://www.missevan.com/sound/getsearch?p=" . $page ."&pagesize=30&kind=3&s=" . $name;
		$result_json = $this->curl_get($url);
		echo $result_json;
		//$result_obj = json_decode($result_json);
		exit();
	}
	  
	function music_search($word, $type,$limit)
	{
	    $url = "http://s.music.163.com/search/get/?";
	    $post_data = array(
	        's' => $word,
	        'offset' => '0',
	        'limit' => $limit,
	        'type' => $type,
	    );
	    $values = array();
	    $result = '';
	    foreach ($post_data as $key => $value) {
	        $values[] = "$key=" . urlencode($value);
	    }
	    $data_string = implode("&", $values);
	    $url .= $data_string;
	    return $this->curl_get($url);
	}
	  
	function get_music_info($music_id)
	{
	    $url = "http://www.missevan.com/sound/getsound?soundid=" . $music_id;
	    return $this->curl_get($url);
	}
	  
	function get_artist_album($artist_id, $limit)
	{
	    $url = "http://music.163.com/api/artist/albums/" . $artist_id . "?limit=" . $limit;
	    return $this->curl_get($url);
	}
	  
	function get_album_info($album_id)
	{
	    $url = "http://music.163.com/api/album/" . $album_id;
	    return $this->curl_get($url);
	}
	  
	function get_playlist_info($playlist_id)
	{
	    $url = "http://music.163.com/api/playlist/detail?id=" . $playlist_id;
	    return $this->curl_get($url);
	}
	function geci(){
		$lrc_json =$this->get_music_lyric($_GET['subject']);
		echo $lrc_json;
	}
	  
	function get_music_lyric($music_name)
	{
		try {
			$url = "http://www.lrcgc.com/so/?q=" . $music_name;
		    $html = file_get_html($url);
		    $content = $html->find('.so_list',0);
		    if($content==""){
				return "-1";
			}
			$solist = $content->find("ul",0)->find("li",0);
			$name = $solist->find("a",0)->plaintext;
			$user = $solist->find("a",1)->plaintext;
			$href = str_replace(".html",'',$solist->find("a",0)->href);
			$href = str_replace("lyric",'lrc',$href);
			$url = "http://www.lrcgc.com" . $href. "/" . $user. "-" . $name. ".lrc";
		    $fp_input = fopen($url, 'r');
		    /*$dir_name = "./lrc/" . $music_name .".txt";
		    file_put_contents($dir_name, $fp_input);*/
		    $file = file_get_contents($url); 
		    $file_arr = explode('[',$file);
		    //删除广告
		    array_pop($file_arr);
		    array_shift($file_arr);
		    $lrc_arr = array();
		    foreach ($file_arr as $key => $val) {
		    	 $arr_li = explode(']',$val);
		    	 $arr_li[0] = ct($arr_li[0],5,"");
		    	 $lrc_arr[$arr_li[0]] = $arr_li[1];
		    }
		    $lrc_json = json_encode($lrc_arr); 
		    return $lrc_json;
		} catch (Exception $e) {
			return "-1";
		}
	}
	  
	function get_mv_info()
	{
	    $url = "http://music.163.com/api/mv/detail?id=319104&type=mp4";
	    return $this->curl_get($url);
	}
	function gethtml()
	{
	    $url = "http://www.missevan.com/";
		$html = file_get_html($url);
		echo $html;
	}

    /**
     * 获取推荐，用于请求不到最新时，返回此份 json
     */
    function recommend()
    {
        echo file_get_contents('./resources/recommend.json');
    }

}