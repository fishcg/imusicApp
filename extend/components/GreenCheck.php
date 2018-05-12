<?php
/**
 * Created by PhpStorm.
 * User: jiepe
 * Date: 2017/10/17
 * Time: 10:34
 */

namespace components;

use Yii;
use stdClass;
use Exception;
use DefaultProfile;
use DefaultAcsClient;
use Green\Request\V20170112\TextScanRequest;
use Green\Request\V20170112\ImageSyncScanRequest;
use Green\Request\V20170112\ImageAsyncScanRequest;
use Green\Request\V20170112\ImageAsyncScanResultsRequest;

class GreenCheck
{
    private static $client;
    private static $text_client;
    private static $img_client;

    private $text_scene = 'antispam';
    private $image_scene = ['porn', 'terrorism'];

    public function __construct()
    {
        if (!self::$client) {
            $iClientProfile = DefaultProfile::getProfile(
                'cn-shanghai',
                ALIYUN_SCAN_ACCESSID,
                ALIYUN_SCAN_ACCESSKEY
            );
            DefaultProfile::addEndpoint(
                'cn-shanghai',
                'cn-shanghai',
                "Green",
                'green.cn-shanghai.aliyuncs.com'
            );
            self::$client = new DefaultAcsClient($iClientProfile);
        }
    }

    /**
     * 设置文字鉴别场景
     * Array||String $text_senne可为antispam(垃圾检测) keyword(关键词)
     * 可为单一字符串，也可为数组形式设置多个场景，如['antispam', 'keyword']
     */
    public function setTextScene($text_scene)
    {
        $this->text_scene = $text_scene;
    }

    /**
     * 设置图片鉴别场景
     * Array||String $text_senne可为 porn(涉黄)、terrorism(涉政涉暴)
     * 可为单一字符串，也可为数组形式设置多个场景，如['antispam', 'keyword']
     */
    public function setImageScene($image_scene)
    {
        $this->image_scene = $image_scene;
    }

    /*
     * 检查文本
     * Array||String $data  为相关字符串或字符串组成的数组，如"你好北京" 或 ['你好','北京']
     * String $suggestion  为鉴定级别，分为pass(通过),review(擦边),block(违规)
     * Array $rate 为鉴定相似度具体百分比，元素值从0.00到100.00
     *             传入[60]代表过滤掉相似度小于60%的，
     *             传入[30，90]代表过滤掉相似度小于30%的和大于90%的
     */
    public function checkText($data, $suggestion = 'block', $rate = [])
    {
        $response = $this->getRequest($data, 'text');
        if ($response) {
            return $this->getResult($response, $data, $suggestion, $rate, 'text');
        } else {
            $Result_obj =  new stdClass();
            $Result_obj->status = -1;
            $Result_obj->results = [];
            return $Result_obj;
        }
    }

    /**
     * 检查图片
     * array||string $data  为相关url完整路径字符串或该字符串组成的数组
     * string $suggestion  为鉴定级别，分为pass(通过),review(擦边),block(违规)
     * array $rate 为鉴定相似度具体百分比，元素值从0.00到100.00
     *             传入[60]代表过滤掉相似度小于60%的，
     *             传入[30，90]代表过滤掉相似度小于30%的和大于90%的
     */
    public function checkImage($data, $suggestion = 'block', $rate = [])
    {
        $response = $this->getRequest($data, 'image');
        if ($response) {
            return $this->getResult($response, $data, $suggestion, $rate, 'image');
        } else {
            $Result_obj = new stdClass();
            $Result_obj->status = -1;
            $Result_obj->results = [];
            return $Result_obj;
        }
    }

    /**
     * 请求阿里接口数据
     * Array||string $data  为相关字符串或字符串组成的数组
     * String  $type 检查类型，值为text或image
     */
    private function getRequest($data, string $type)
    {
        if ($type === 'text') {
            if (!self::$text_client) {
                self::$text_client = new TextScanRequest();
                self::$text_client->setAcceptFormat("JSON");
            }
            $tasks = $this->setTasks($data, 'text');
            self::$text_client->setContent(json_encode([
                'tasks' => $tasks,
                'scenes' => $this->text_scene
            ]));
            try {
                $response = self::$client->getAcsResponse(self::$text_client);
                return $response;
            } catch (Exception $e) {
                throw new Exception('检查文本出错');
            }
        } elseif ($type === 'image') {
            if (!self::$img_client) {
                self::$img_client = new ImageSyncScanRequest();
                self::$img_client->setAcceptFormat("JSON");
            }
            $tasks = $this->setTasks($data, 'image');
            self::$img_client->setContent(json_encode([
                'tasks' => $tasks,
                'scenes' => $this->image_scene
            ]));
            try {
                $response = self::$client->getAcsResponse(self::$img_client);
                return $response;
            } catch (Exception $e) {
                throw new Exception('检查图片出错');
            }
        } else {
            throw new Exception('检查类型出错');
        }
    }

    /**
     * 设置检查的任务数组
     * Array||string $data  为相关字符串或字符串组成的数组
     * String  $type 检查类型，值为text或image
     */
    private function setTasks($data, string $type)
    {
        $contentType = $type === 'text' ? 'content' : 'url';
        if (is_string($data)) {
            $data = [$data];
        }
        $data = array_merge($data);
        $tasks = array_map(function ($task) use ($contentType) {
            return ['dataId' => uniqid(), $contentType => $task];
        }, $data);
        return $tasks;
    }

    /**
     * 返回结果
     * Object $response  为阿里接口返回对象
     * Array||string $data  为相关字符串或字符串组成的数组
     * String $suggestion  为鉴定级别，分为pass(通过),review(擦边),block(违规)
     * Array $rate 为鉴定相似度具体百分比，元素值从0.00到100.00
     * String  $type 检查类型，值为text或image
     * Array  $queryAsync 查询异步接口传递的包含url和taskId的数组
     */
    private function getResult($response, $data, string $suggestion, $rate, string $type, $queryAsync = [])
    {
        $contentType = $type == 'text' ? 'content' : 'url';
        $Result_obj = new stdClass();
        if (200 === $response->code) {
            $Result_obj->status = 200;
            $Result_obj->Allaccord = 0;
            $Result_obj->results = [];
            $taskResults = $response->data;
            foreach ($taskResults as $key => $taskResult) {
                if (200 === $taskResult->code) {
                    $sceneResults = $taskResult->results;
                    $labes = [];
                    foreach ($sceneResults as $k => $sceneResult) {
                        if ($sceneResult->suggestion == $suggestion ) {
                            // 过滤掉不符合相似度的数据
                            if (count($rate) === 1
                                && $rate[0] >= $sceneResult->rate)
                                continue;
                            if (count($rate) === 2
                                && ($rate[0] > $sceneResult->rate || $rate[1] < $sceneResult->rate))
                                continue;
                            // 过滤掉多场景情况下某一场景通过检查而其他场景没有通过检查的数据
                            if ($suggestion === 'pass' && $k>0
                                && $sceneResults[$k-1]->suggestion !== 'pass')
                                break;
                            $Result_obj->Allaccord = 1;
                            $Result_obj->results[$key][$contentType] = $taskResult->{$contentType};
                            $Result_obj->results[$key]['code'] = $taskResult->code;
                            $Result_obj->results[$key]['suggestion'] = $suggestion;
                            $labes[$k] = $sceneResult->label;
                            $Result_obj->results[$key]['labels'] = array_unique($labes);
                            $Result_obj->results[$key]['scene'] = $type === 'text' ?
                                $this->text_scene :
                                $this->image_scene;
                        }
                    }
                } elseif (200 !== $taskResult->code && (!empty($queryAsync) )) {
                    $Result_obj->Allaccord = -1;
                    $Result_obj->results[$key]['code'] = $taskResult->code;
                    $Result_obj->results[$key]['msg'] = $taskResult->msg;
                    $Result_obj->results[$key]['suggestion'] = 'review';
                    $Result_obj->results[$key]['taskId'] = $taskResult->taskId;
                    // 取出taskid相对应的url
                    $Result_obj->results[$key]['url'] = $queryAsync[$taskResult->taskId];
                } elseif (200 !== $taskResult->code && $type === 'image'){
                    try {
                        $this->asyncCheckImage($taskResult->url);
                    } catch (Exception $e) {
                        // 异步查询图片出异常时，不进行操作
                    }
                }
            }
            return $Result_obj;
        } else {
            if (!empty($queryAsync)) {
                throw new Exception('发送检查请求异常');
            }
            if ($type === 'image') {
                // 将所有图片进行异步鉴别处理，得到的结果保存于redis-list链表中
                $this->asyncCheckImage($data);
            }
            $Result_obj = new stdClass();
            $Result_obj->status = $response->code;
            $Result_obj->Allaccord = -1;
            $Result_obj->results = [];
            return $Result_obj;
        }
    }

    /**
     * 异步发送图片鉴别请求
     * Array||String $data  为相关字符串或字符串组成的数组
     */
    public function asyncCheckImage($data)
    {
        if (!self::$img_client) {
            self::$img_client = new ImageAsyncScanRequest();
            self::$img_client->setMethod('POST');
            self::$img_client->setAcceptFormat('JSON');
        }
        $tasks = $this->setTasks($data, 'image');
        self::$img_client->setContent(json_encode([
            'tasks' => $tasks,
            'scenes' => $this->image_scene,
            /* 'seed' => '签名',
             'callback' => '回调地址',*/
        ]));
        try {
            $response = self::$client->getAcsResponse(self::$img_client);
            if (200 === $response->code) {
                $taskResults = $response->data;
                foreach ($taskResults as $taskResult) {
                    if(200 === $taskResult->code) {
                        // 将taskId 保存下来，用于查询其检查的结果
                        $taskId = $taskResult->taskId;
                        $url = $taskResult->url;
                        $value = json_encode([$taskId, $url]);
                        $redis = Yii::$app->redis;
                        $redis->lpush('list:imageCheck:tasks', $value);
                    }
                }
            }
        } catch (Exception $e) {
            // 异步查询图片出异常时，不进行操作
        }
    }

    /**
     * 已异步鉴别的图片-结果查询
     * Array||String $tasks  为相关图片鉴别后的taskid和相对应url组成的数组
     * Atring $suggestion  为鉴定级别，分为pass(通过),review(擦边),block(违规)
     * Array $rate 为鉴定相似度具体百分比，元素值从0.00到100.00
     *       传入[60]代表过滤掉相似度小于60%的，
     *       传入[30，90]代表过滤掉相似度小于30%的和大于90%的
     */
    public function queryImage($tasks, $suggestion = 'block', $rate = [])
    {
        if (empty($tasks)) {
            return false;
        }
        if (!self::$img_client) {
            self::$img_client = new ImageAsyncScanResultsRequest();
            self::$img_client->setMethod('POST');
            self::$img_client->setAcceptFormat('JSON');
        }
        $taskIds_url = array_map(function ($task) {
            $task_arr = json_decode($task);
            return ['taskId' => $task_arr[0], 'url' => $task_arr[1]];
        }, $tasks);
        $queryAsync = array_column($taskIds_url, 'url', 'taskId');
        $taskIds = array_keys($queryAsync);
        self::$img_client->setContent(json_encode($taskIds));
        try {
            $response = self::$client->getAcsResponse(self::$img_client);
            return $this->getResult($response, [], $suggestion, $rate, 'image', $queryAsync);
        } catch (Exception $e) {
            throw new Exception('查询图片出错');
        }
    }

}