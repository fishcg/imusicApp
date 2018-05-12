<?php
namespace app\admin\controller;

use app\common\controller\Base;

class Music extends Base
{
	protected $_model_name = 'Music';
	//protected $_form_name = 'MyModule';

	//是否有软删除/回收站
	protected $_recycle = false;

	protected $_where_items = array('name');

	function create(){
		$sit=isset($_GET['sit'])?$_GET['sit']:"";
		if(!empty($sit)){
			$this->assign('sit',$sit); 	
			return view();
		}else{

			
			if(!empty($_POST['music_id'])){

				$music = $this->get_music_info($_POST['music_id']);

				$music = json_decode($music); 
				if(!isset($music->songs[0])){
					$this->error("朋友，睁大你的双眼，不要输错歌曲编号");
					exit();
				}
				$_POST['url'] = $music->songs[0]->mp3Url;
				$_POST['subject'] = $music->songs[0]->name;
				$_POST['photo'] = $music->songs[0]->album->picUrl;
				$_POST['author'] = $music->songs[0]->artists[0]->name;
				$_POST['playtime'] = $music->songs[0]->bMusic->playTime/1000;

				$geciData =$this->get_music_lyric($_POST['music_id']);
				$geci = json_decode ($geciData); 
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

				$_POST['lyric'] =$arr;
			}
			$_POST['created'] = time();
			$row = $this->_model->data($_POST)->save();
		
		    if($row){
		        $this->success("新增成功");
		    }else{
		        $this->error("新增失败");
		    }
		}
	}

	function curl_get($url)
	{
	    $refer = "http://music.163.com/";
	    $header[] = "Cookie: " . "appver=1.5.0.75771;";
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	    curl_setopt($ch, CURLOPT_REFERER, $refer);
	    $output = curl_exec($ch);
	    curl_close($ch);
	    return $output;
	}

	function search(){
		$word =	 $_GET['name'];
		//$word = "告白气球";
		$a = $this->music_search($word, 1);
		$musics = json_decode($a); 
		//print_r($musics->result->songs[0]) ;
		return $musics->result->songs;
	}
	  
	function music_search($word, $type)
	{
	    $url = "http://s.music.163.com/search/get/?";
	    $post_data = array(
	        's' => $word,
	        'offset' => '0',
	        'limit' => '20',
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