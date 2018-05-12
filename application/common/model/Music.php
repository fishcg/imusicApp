<?php
namespace app\common\model;

use components\Missevan;
use think\Exception;
use think\Model;
use think\config;
use think\db;
use app\common\model\User;

class Music extends Base
{
    // M 音频分类，46：漫画；54：ASMR；41：日抓
    const CATALOG_CARTOON = 46;
    const CATALOG_ASMR = 54;
    const CATALOG_DRAMA_CD = 41;

    // 提示音频不存在的音频 ID
    const NO_MUSIC = 223692;

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /*
     * 评论模型的关联
     */
	public function comments()
	{
		return $this->hasMany('Comment','music_id','id');		
	}

    /*
     * 评论弹幕的关联
     */
	public function flys()
	{
		return $this->hasMany('Fly','music_id','id');		
	}

    /**
     * 获取首页推荐音
     *
     * @return array 推荐的歌曲信息组成的数组
     */
    public function getRecommendMusics(): array
    {
        // 获取 missevan 推荐数据
        $missevan = new Missevan();
        $recommend_json = $missevan->getMissevanRecommend();
        $recommend = json_decode($recommend_json);
        $recommend_musics = $recommend->sounds->day3;
        array_push($recommend_musics,  $recommend->sounds->dayw[0]);
        $recommend_musics = self::formatMusic($recommend_musics);
        // ASMR 推荐
        $catalog = json_decode($missevan->getCatalog());
        $asmrs = $catalog->{self::CATALOG_ASMR}->catalogs->time;
        array_push($asmrs,  $catalog->{self::CATALOG_ASMR}->catalogs->point[0]);
        $asmrs = self::formatMusic($asmrs);
        // 有声漫画推荐
        $cartoons = $catalog->{self::CATALOG_CARTOON}->catalogs->time;
        array_push($cartoons,  $catalog->{self::CATALOG_CARTOON}->catalogs->point[0]);
        $cartoons = self::formatMusic($cartoons);
        // 最新歌曲
        $news_musics = $this
            ->where(['recycle' => 0])
            ->where('url', 'NOT NULL')
            ->limit(0, 6)
            ->order('new DESC,created DESC')
            ->select();
        $news_musics = array_map(function ($news_music) {
            return [
                'id' => $news_music->id,
                'subject' => ct($news_music->subject,14,'..'),
                'author' =>$news_music->author,
                'views' => $news_music->views,
                'photo' => $news_music->photo,
                'comment_count' => count($news_music->comments),
            ];
        }, $news_musics);
        // 最热歌曲
        $hot_musics = $this
            ->where(['recycle' => 0])
            ->where('url', 'NOT NULL')
            ->limit(0, 6)
            ->order('views DESC,created DESC')
            ->select();
        $hot_musics = array_map(function ($hot_music) {
            return [
                'id' => $hot_music->id,
                'subject' => ct($hot_music->subject,14,'..'),
                'author' =>$hot_music->author,
                'views' => $hot_music->views,
                'photo' => $hot_music->photo,
                'comment_count' => count($hot_music->comments),
            ];
        }, $hot_musics);
        return [
            'top' => $recommend_musics,
            'asmr' => $asmrs,
            'cartoon' => $cartoons,
            'hot' => $hot_musics,
            'new' => $news_musics
        ];
    }

    /**
     * 格式化 Missevan 单音数据
     * @param array $musics 单音数组
     * @return array 格式化后的单音数组
     */
    static function formatMusic($musics)
    {
        return array_map(function ($music) {
            return [
                'id' => (int)$music->id,
                'subject' => ct($music->soundstr, 16, '..'),
                'author' => '来自 M 站',
                'views' => $music->view_count,
                'photo' => $music->front_cover,
                'comment_count' => $music->all_comments,
            ];
        }, $musics);
    }


    /**
     * 创建音频
     */
    public function getMusic($id)
    {
        $the_music = $this->where('id','=', $id)->find();
        if($the_music){
            return $the_music;
        }else{
            $the_music = $this->where('music_id','=', $id)->find();
            if ($the_music) {
                return $the_music;
            }
            $missevan = new Missevan();
            $music = $missevan->get_music_info($id);
            $str =  str_replace("\u0000","_",$music);
            $music = json_decode($str);
            if($music->state !== 'success' || !$music->info->sound->soundurl){
                return $this->getMusic(self::NO_MUSIC);
            }
            try{
                $summary = $music->info->sound->intro;
                $summary =  strip_tags($summary);
            }catch(\Exception $e){
                $summary = '该音频木有简介~';
            }
            $m['music_id'] = $id;
            $m['url'] = "http://static.missevan.com/sound/" . $music->info->sound->soundurl;
            $m['url_32'] = "http://static.missevan.com/sound/" . $music->info->sound->soundurl_32;
            $m['url_64'] = "http://static.missevan.com/sound/" . $music->info->sound->soundurl_64;
            $m['subject'] = $music->info->sound->soundstr;
            $m['photo'] = $music->info->sound->front_cover;
            $m['author'] = $music->info->sound->username;
            $m['summary'] = $summary;
            $m['created'] = time();
            $m['uid'] = $music->info->user->id ?: 79742;
            $this->data($m)->save();
            //保存user
            $user = Db::table('lab_user')->where('uid','=',$m['uid'])->find();
            if(!$user){
                //存储up信息
                try {
                    $user = new User();
                    $u['uid'] = $m['uid'];
                    $u['summary'] = $music->info->user->intro;
                    $u['name'] = $music->info->user->username;
                    $u['avatar'] = $music->info->user->icon;
                    $u['username'] = "upup".$m['uid'];
                    $u['password'] = md5("123456");
                    $u['reg_time'] = $m['created'];
                    $user->data($u);
                    $user->save();
                    // $uid = Db::table('lab_user')->insert($u);
                } catch (\Exception $e) {
                    // 暂不做处理
                }

            }
            // 返回歌曲
            return $this->where('music_id', '=', $id)->find();
        }
    }

    function get_music_info($music_id)
    {
        $url = "http://www.missevan.com/sound/getsound?soundid=" . $music_id;
        return $this->curl_get($url);
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
}