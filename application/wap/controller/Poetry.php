<?php
namespace app\wap\controller;
use app\common\controller\Base;
use Think\Db;
error_reporting(E_ALL);
include_once('./simple_html_dom.php');
class Poetry extends Base
{
    protected $_model_name = 'Poetry';
    function index(){
        //广告古诗
        $poetry_banners = $this->_model
        ->where(array('recycle'=>0))
        ->where('content','NOT NULL')
        ->limit(0,3)
        ->order('top desc')
        ->select();
        foreach($poetry_banners as $val){
        $row['banners'][] =array(
                'id' => $val->id,
                'subject' => c($val->subject,9,'...'),
                'author' => $val->author,
                'dynasty' => $val->dynasty,
                'photo' => $val->photo,
            );
        }
       //热门
        $poetry_tops = $this->_model
        ->where(array('recycle'=>0))
        ->where('content','NOT NULL')
        ->limit(0,6)
        ->order('sort asc,views desc')
        ->select();
        foreach($poetry_tops as $val){
        $row['top'][] =array(
                'id' => $val->id,
                'subject' => c($val->subject,9,'...'),
                'author' => $val->author,
                'dynasty' => $val->dynasty,
                'views' => $val->views,
                'photo' => $val->photo,
                'comment_count' => count($val->comments),
            );
        }
        //最新
        $poetry_new = $this->_model
        ->where(array('recycle'=>0))
        ->where('content','NOT NULL')
        ->limit(0,6)
        ->order('new desc,created desc')
        ->select();
        foreach($poetry_new as $val){
            $row['new'][] =array(
                'id' => $val->id,
                'subject' => c($val->subject,9,'...'),
                'author' => $val->author,
                'dynasty' => $val->dynasty,
                'views' => $val->views,
                'photo' => $val->photo,
                'comment_count' => count($val->comments),
            );
        }
        echo json_encode($row);
        exit(); 
    }
	function search(){
        $keyword = $_GET['keyword'];
        $html = file_get_html("http://so.gushiwen.org/search.aspx?value=$keyword");
        $ret = $html->find('.sons .cont');
       // echo $ret;
        $arr = array();
        foreach($ret as $key => $val){
           if($key>9)continue;
           $id = substr($val->id,4);   
           $subject = $val->find('p a b',0);
           $subject = str_replace(' ', '',$subject->plaintext); 

           $dynasty = $val->find('.source a',0)->plaintext;
           $author = $val->find('.source a',1)->plaintext;
           $arr[] =(array('id'=>$id,'subject'=>$subject,'dynasty'=>$dynasty,'author'=>$author));
        }
        echo json_encode($arr);
        exit();     
	}

	//诗词
	function create(){
        $poetry_id =  $_GET['id']; 
        $thepoetry = $this->_model
            ->where('poetry_id','=',$poetry_id)
            ->find();   
        if(count($thepoetry) !=0){
                echo  $thepoetry->id;
                exit();
        }else{
            $p = array();
            $content = file_get_html("http://so.gushiwen.org/view_" . $poetry_id . ".aspx");
            //$p['content'] = $content->find('.contson',0);
            $a = "#contson$poetry_id";
            $p['content'] = $content->find($a,0)->plaintext;
            $num =  count($content->find(".contyishang"));
             $appreciation = '';
            if($num>2){
                $appreciations = $content->find(".contyishang",3)->find("p");
               
                foreach($appreciations as $key => $val){
                    if($key>1){
                        $appreciation .= $val->plaintext;
                    }          
                }
            }
           
            $html = file_get_html("http://so.gushiwen.org/shiwen2017/ajaxshiwencont.aspx?id=" . $poetry_id . "&value=yizhu");
            $ret = $html->find('body>p');
            $translate = '';
            $notes = '';
            $count = count($ret);
            foreach($ret as $key=>$val){
                if($key<($count-1)){
                    $translate .= $val->children(1)->plaintext;
                    if(isset($val->children(2)->plaintext)){
                        $notes .= $val->children(2)->plaintext;
                    }
                }             
                
            }
            $p['translate'] = $translate ? $translate : "暂无翻译"; 
            $p['notes'] = $notes ? $notes : "暂无注释"; 
            $p['subject'] = $_GET['subject'];
            $p['appreciation'] = $appreciation ? $appreciation : "暂无赏析"; 
            $p['dynasty'] =  $_GET['dynasty']; 
            $p['author'] =  $_GET['author'];
            $p['poetry_id'] = $poetry_id;
            $p['created'] = time();
            $p['photo'] = isset($_GET['photo']) ? $_GET['photo'] : "/images/logo.jpg";
            $row = $this->_model->data($p)->save();
            if($row){
                $themusic = $this->_model
                    ->where('poetry_id','=',$poetry_id)
                    ->find();
                echo  $themusic->id;
                exit();
            }else{
                echo  0;
                exit();
            }
        }
		
	}

    function view(){
        $id = $_GET['id'];
        $poetry= $this->_model
            ->where('id','=',$id)
            ->find();
        $poetry->views +=1;
        $poetry->save();
        $row = array(
            'id' =>$poetry->id,
            'subject' =>$poetry->subject,
            'content' =>$poetry->content,
            'author' =>$poetry->author,
            'dynasty' =>$poetry->dynasty,
            'created' =>$poetry->created,
            'views' =>$poetry->views,
            'photo' =>$poetry->photo,
            'translate' =>$poetry->translate,
            'notes' =>$poetry->notes,
            'appreciation' =>$poetry->appreciation,
            );  
        echo json_encode($row);
        exit();
    }

    //诗词
    function test(){
        $html = file_get_html("http://so.gushiwen.org/search.aspx?value=李白");
        $ret = $html->find('.sons .cont');
        $arr = array();
        foreach($ret as $key => $val){
            if($key>10)continue;
            $id = substr($val->id,4);   
            $subject = $val->find('p a b',0);
            $subject = $subject->plaintext;       
            $dynasty = $val->find('.source a',0)->plaintext;
            $author = $val->find('.source a',1)->plaintext;
            $arr[] =(array('id'=>$id,'subject'=>$subject,'dynasty'=>$dynasty,'author'=>$author));
        }
       dump($arr); 
        
        
    }
	function get_view_info($id)
	{
	    $url = "http://so.gushiwen.org/shiwen2017/ajaxshiwencont.aspx?id=" . $id . "&value=yizhushang";
	    return $this->curl_get($url);
	}
	function curl_get($url)
	{
	    $refer = "http://www.gushiwen.org/";
	    $header=array(
	    	"Cookie: " . "ASP.NET_SessionId=um0lidvrpxluigdl3fz20udn",
	    
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
}