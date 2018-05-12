<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/27
 * Time: 20:08
 */

namespace components;;

use app\models\PaginationModel;
use think\exception\HttpException;

class Mutils
{
    public static function getFileExtension($file_path)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_path);
        switch ($mime) {
            case 'audio/x-m4a':
                return 'm4a';
            case 'audio/mpeg':
                return 'mp3';
            case 'audio/x-wav':
                return 'wav';
            case 'image/jpeg':
                return 'jpg';
            case 'image/gif':
                return 'gif';
            case 'image/png':
                return 'png';
            default:
                return '';
        }
    }

    //随机数
    public static function randomKeys($length, $type = 1)
    {
        $key = '';
        $pattern = '';
        if ($type & 1)
            $pattern .= '1234567890';
        if ($type & 2)
            $pattern .= 'abcdefghijklmnopqrstuvwxyz';
        if ($type & 4)
            $pattern .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $p_length = strlen($pattern) - 1;
        for ($i = 0; $i < $length; $i++)
            $key .= $pattern{mt_rand(0, $p_length)};
        return $key;
    }

    public static function GroupArray($elems, $group_by, $group_value = null)
    {
        $elem_group = [];
        foreach ($elems as $elem) {
            $group_key = $elem[$group_by];
            if (!isset($elem_group[$group_key])) {
                $elem_group[$group_key] = [];
            }
            if ($group_value === null) {
                $elem_group[$group_key][] = $elem;
            } else {
                $elem_group[$group_key][] = $elem[$group_value];
            }
        }
        return $elem_group;
    }

    public static function plainText($text, $length = 0)
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        if ($length) {
            $text = mb_substr($text, 0, $length);
        }
        return $text;
    }

    /*
     * 获得当前到晚上 24 点的时间
     *
     * @return integer 秒为单位的时间长度
     */
    public static function getRemainTime()
    {
        $now = time();
        $over = strtotime(date('y-m-d 23:59:59', $now));
        return $over - $now;
    }

    /**
     * 处理分页参数是否合法
     * @param integer $page 页数
     * @param integer $page_size 每页个数
     * @return PaginationModel
     */
    public static function processPageParams(int $page, int $page_size)
    {
        if (0 > $page) throw new HttpException(400, '参数不合法');
        if (0 > $page_size || 100 < $page_size) throw new HttpException(400, '参数不合法');
        $return_obj = new PaginationModel;
        $return_obj->page = $page;
        $return_obj->page_size = $page_size;
        return $return_obj;
    }

}
