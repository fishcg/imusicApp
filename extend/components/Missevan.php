<?php
/**
 * 获取猫耳FM 工具类
 *
 * Author: fish
 * Date: 2018/03/16
 * Time: 09:45
 */

namespace components;

use think\Exception;
use think\Config;
use think\exception\HttpException;

include_once('./simple_html_dom.php');

class Missevan
{

    // 登陆状态标识，0：未登录；1：登陆
    const STATUS_LOGOUT = 0;
    const STATUS_LOGIN = 1;

    // Missevan 版本标识，1：正常版本；2：纯净版
    const VERSION_NORMAL = 1;
    const VERSION_GREEN = 2;

    // 登陆状态
    private $login_status = self::STATUS_LOGIN;

    // 登陆状态
    private $version = self::VERSION_NORMAL;

    public function __construct($version = null)
    {
        $this->version = $version ?: Config::get('missevan.version');

    }

    /**
     * 设置登陆状态
     *
     * @param string $status 登陆状态
     * @throws Exception 设置的登陆状态不在允许范围内
     */
    public function setLogin($status)
    {
        if (!in_array($status, [self::STATUS_LOGOUT, self::STATUS_LOGIN])) {
            throw new Exception('登陆状态有误');
        }
        $this->login_status = $status;
    }

    /**
     * 设置 Missevan Web 版本
     *
     * @param int $version 登陆状态
     * @throws Exception 设置的版本不在允许范围内
     */
    public function setVersion($version)
    {
        if (!in_array($version, [self::VERSION_NORMAL, self::VERSION_GREEN])) {
            throw new Exception('M 接口版本有误');
        }
        $this->version = $version;
    }

    /**
     * 获取登陆后相关网页内容
     *
     * @param string $url 获取的地址
     * @return string 网页内容
     * @throws Exception 请求阿里云内容安全接口出现问题
     */
    function curl_get($url)
    {
        // @TODO 暂时先写死 token，日后需做自动登录功能
        $header = [
            'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:53.0) Gecko/20100101 Firefox/53.0',
            'Host: www.missevan.com'
        ];
        if ($this->login_status === 1) {
            $cookie = 'Cookie: Hm_lvt_d4dd9bd2c2f9a6a278c378eda69cd865=1520569601;
                PHPSESSID=74fbm5j1l4tdj8kdi5717seh55;
                token=5aaa362839d29a10d1d8759f%7C1521104424%7C6fc7995e2b318447;
                SERVERID=50dcbb92ed530d21aa89ff907a887973|1521107038|1521080824;
                Hm_lvt_91a4e950402ecbaeb38bd149234eb7cc=1521080827,1521104371,1521104413,1521104434;
                Hm_lpvt_91a4e950402ecbaeb38bd149234eb7cc=1521107039';
            array_push($header, $cookie);
        }
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
        curl_setopt($ch, CURLOPT_REFERER, M_DOMAIN);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;

    }

    /**
     * 获取搜索结果
     *
     * @param string $name 搜索关键词
     * @param int $page 页数
     * @param int $page_size 分页条数
     * @param int $king 搜索类型
     *
     * @return string 搜索结果数组 json
     * @throws Exception 请求 Missevan 接口出现问题
     */
    public function search(string $name, int $page = DEFAULT_PAGE, int $page_size = PAGE_SIZE, int $king = 3)
    {
        $Pagination = Mutils::processPageParams($page, $page_size);
        $search_url = Config::get('missevan.search')
            . '?p=' . $Pagination->page
            . '&pagesize=' . $Pagination->page_size
            . '&kind=' . $king
            . '&s=' . $name;
        $result_json = $this->curl_get($search_url);
        return $result_json;
    }

    /**
     * 获取歌曲信息
     *
     * @param int $music_id Missevan 单音 ID
     * @return string 歌曲信息 json 数组
     * @throws Exception 请求 Missevan 接口出现问题
     */
    function get_music_info(int $music_id)
    {
        $url = Config::get('missevan.get_sound_info') . "?soundid=$music_id";
        return $this->curl_get($url);
    }

    /**
     * 获取歌词
     *
     * @param string $music_name 歌曲名称
     * @return string 歌曲信息 json 数组
     */
    function getMusicLyric(string $music_name)
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
            $file_arr = explode('[', $file);
            //删除广告
            array_pop($file_arr);
            array_shift($file_arr);
            $lrc_arr = [];
            foreach ($file_arr as $key => $val) {
                $arr_li = explode(']',$val);
                $arr_li[0] = ct($arr_li[0], 5, '');
                $lrc_arr[$arr_li[0]] = $arr_li[1];
            }
            $lrc_json = json_encode($lrc_arr);
            return $lrc_json;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取 M 站推荐数据，并更新备份
     */
    public function getMissevanRecommend()
    {
        try {
            // @TODO: 日后需要整理下此处代码
            if ($this->version === self::VERSION_GREEN) {
                $url = url('index/musicm/mindex', '', '', true);
            } else {
                $url = M_DOMAIN;
            }
            $html = file_get_html($url);
            $script_str = $html->find('body', 0)->find('#new_content', 0)->last_child()->innertext;
            $preg = '/"web_recommend.*?json"/';
            preg_match($preg, $script_str, $result);
            $json = '{' . $result[0] . '}';
            $result = json_decode($json);
            $recommend = Config('missevan.static_domain') . '/' . $result->web_recommend;
            $recommend_json = file_get_html($recommend);
            // 更新备份
            file_put_contents('./resources/recommend.json', $recommend_json);
        } catch (Exception $e) {
            // 出现异常时，取备份数据返回
            $url = url('index/musicm/recommend', '', '' ,true);
            $recommend_json =  file_get_html($url);
        }
        return $recommend_json;
    }

    /**
     * 获取 M 站分类数据，并更新备份
     */
    public function getCatalog()
    {
        try {
            $url = M_DOMAIN . '/organization/getcatalogdata';
            $catalog = $this->curl_get($url);
            if (!$catalog) {
                throw new Exception('获取 Missevan 分类数据异常');
            }
            // 更新备份
            file_put_contents('./resources/catalog.json', $catalog);
        } catch (Exception $e) {
            // 出现异常时，取备份数据返回
            $catalog = file_get_contents('./resources/catalog.json');
        }
        return $catalog;
    }
    /*
    public function music_search($word, $type,$limit)
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

    function get_mv_info()
    {
        $url = "http://music.163.com/api/mv/detail?id=319104&type=mp4";
        return $this->curl_get($url);
    }*/

    function getHtml($url)
    {
        $html = file_get_html($url);
        return $html;
    }
}
