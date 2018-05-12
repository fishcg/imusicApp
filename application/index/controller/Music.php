<?php

namespace app\index\controller;

class Music extends Base
{
	protected $_model_name = 'Music';
	private static $PUBLIC_KEY = '0CoJUm6Qyw8W8jud';

	function shafa(){

	}
	/**     
     * 公钥加密     
     * @param string $data     
     * @return null|string     
     */ 
	public static function publicEncrypt($data = ''){ 
		if (!is_string($data)) {
		 return null; 
		} 
		return openssl_public_encrypt($data,$encrypted,self::getPublicKey()) ? base64_encode($encrypted) : null; 
	}
	 function curl_post1($url, $post) {  
	    $options = array(  
	        CURLOPT_RETURNTRANSFER => true,  
	        CURLOPT_HEADER         => false,  
	        CURLOPT_POST           => true,  
	        CURLOPT_POSTFIELDS     => $post,  
	    );  
	  
	    $ch = curl_init($url);  
	    curl_setopt_array($ch, $options);  
	    $result = curl_exec($ch);  
	    curl_close($ch); 
	    return $result;  
	}  
	function index(){
/*		$publicEncrypt = $this->publicEncrypt(json_encode($data));
		echo '公钥加密后:'.$publicEncrypt.'<br>';

		exit();

	 //$data = $this->curl_post1("http://www.missevan.com/site/addcomment/",array("comment"=>"test2","eId"=>"417671","type" =>"1")); 

	$data = $this->curl_post1("http://music.163.com/weapi/song/enhance/player/url",array("LoginForm[password]"=>"cg147258","LoginForm[username]"=>"15685598480"));	
  
  	

	var_dump($data);  
	exit();*/
		//热门歌曲
		$music_tops = $this->_model
		->where(array('recycle'=>0))
		->where('url','NOT NULL')
		->limit(0,6)
		->order('sort asc,top desc,views desc')
		->select();
		foreach($music_tops as $val){
		$row['top'][] =array(
				'id' => $val->id,
				'subject' => c($val->subject,9,'...'),
				'author' => $val->author,
				'views' => $val->views,
				'photo' => $val->photo,
				'comment_count' => count($val->comments),
			);
		}
		//推荐歌曲
		$music_new = $this->_model
		->where(array('recycle'=>0))
		->where('url','NOT NULL')
		->limit(0,6)
		->order('new desc,created desc')
		->select();
		foreach($music_new as $val){
			$row['new'][] =array(
					'id' => $val->id,
					'subject' => c($val->subject,9,'...'),
					'author' => $val->author,
					'views' => $val->views,
					'photo' => $val->photo,
					'comment_count' => count($val->comments),
			);
		}
		echo json_encode($row);
		exit();
	}
	function view(){
		$id = $_GET['id'];
		$music= $this->_model
			->where('id','=',$id)
			->find();
		$music->views +=1;
		$music->save();
	/*	$commentNum = Db::table('lab_comment')
		->where(array(
			'music_id' => $id,
			'status' => 1
			))
		->count();*/
		$row = array(
			'id' =>$music->id,
			'subject' =>$music->subject,
			'summary' =>$music->summary,
			'author' =>$music->author,
			'url' =>$music->url,
			'created' =>$music->created,
			'views' =>$music->views,
			'photo' =>$music->photo,
			'playtime' =>$music->playtime,
			'lyric' =>$music->lyric,
			'flys' =>$music->flys,
			);	
		echo json_encode($row);
		exit();
	}
	function rank(){
		$musics= $this->_model
			->where('recycle','=',0)
			->order( 'views desc','sort asc')
			->limit(0,30)
			->select();
		echo json_encode($musics);
		exit();
	}
	function categorylist(){
		$id = $_GET['id'];
		$musics= $this->_model
			->where(array('recycle'=>0,'music_category_id'=>$id))
			->order( 'views desc','sort asc')
			->limit(0,30)
			->select();	
		echo json_encode($musics);
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
			->where('music_id','=',$_GET['music_id'])
			->find();	
		if(count($themusic) !=0){
				echo  $themusic->id;
				exit();
		}else{
			$music = $this->get_music_info($_GET['music_id']);
			$music = json_decode($music); 
			if(!isset($music->songs[0])){
				echo  0;
				exit();
			}
			$m['music_id'] = $_GET['music_id'];
			$m['url'] = $music->songs[0]->mp3Url;
			$m['subject'] = $music->songs[0]->name;
			$m['photo'] = $music->songs[0]->album->picUrl;
			$m['author'] = $music->songs[0]->artists[0]->name;
			$m['playtime'] = $music->songs[0]->bMusic->playTime/1000;
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
				$m['lyric'] =$arr;
			}
		
			$m['created'] = time();
			$row = $this->_model->data($m)->save();
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
	    $refer = "http://music.163.com/";
	    $header=array(
	    	"Cookie: " . "appver=1.5.0.75771",
	    
	    	) ;
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
		$word =	 $_GET['name'];
		if(isset($_GET['limit'])){
			$limit =$_GET['limit'];
		}else{
			$limit =20;
		}
		//$word = "告白气球";
		$a = $this->music_search($word, 1,$limit);
		//$musics = json_decode($a); 
		//print_r($musics->result->songs[0]) ;
		//print_r($musics->result->songs);  
		echo $a;
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
	    $url = "http://music.163.com/api/song/detail/?id=" . $music_id . "&ids=%5B" . $music_id . "%5D";
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
	  
	function get_music_lyric($music_id)
	{
	    $url = "http://music.163.com/api/song/lyric?os=pc&id=" . $music_id . "&lv=-1&kv=-1&tv=-1";
	    return $this->curl_get($url);
	}
	  
	function get_mv_info()
	{
	    $url = "http://music.163.com/api/mv/detail?id=319104&type=mp4";
	    return $this->curl_get($url);
	}

}