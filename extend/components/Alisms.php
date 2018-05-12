<?php
/**
 * Created by PhpStorm.
 * User: tomcao
 * Date: 2017/8/9
 * Time: 21:34
 */

namespace app\components\util;


use AliyunMNS\Client;
use AliyunMNS\Model\BatchSmsAttributes;
use AliyunMNS\Model\MessageAttributes;
use AliyunMNS\Requests\PublishMessageRequest;
use yii\web\HttpException;

class Alisms
{
    public static $alisms;
    private static $client;

    // params = [ 'code' => '1234' ]; // code 必须为字符串
    const TEMPLET_REGIST = 0;
    const TEMPLET_CHANGE = 1;
    const TEMPLET_REMOTE = 2;

    const TEMPLETS = [
        ['M站', 'SMS_86605154'],//注册
        ['M站', 'SMS_86510119'],//忘记密码
        ['M站', 'SMS_86705149'],//绑定新手机号码
        ['M站', 'SMS_86705149'],//修改手机号码
        ['M站', 'SMS_86755137'],//绑定新邮箱邮箱
        ['M站', 'SMS_86755137'],//修改邮箱
        ['M站', 'SMS_86265011'],//异地登陆验证
        ['M站', 'SMS_86625161'],//支付
        ['M站', 'SMS_98235028'],//注册绑定手机
    ];

    private function __construct()
    {
        self::$client = new Client(ALISMS_ENDPOINT, ALISMS_ACCESSID, ALISMS_ACCESSKEY);
    }

    public static function getInstance()
    {
        if (!self::$alisms) {
            self::$alisms = new self();
        }
        return self::$alisms;
    }

    public function sendSms($mobile, $templet, $params)
    {
        $topicName = 'sms.topic-cn-hangzhou';
        $topic = self::$client->getTopicRef($topicName);
        list($SMSSignName, $SMSTemplateCode) = self::TEMPLETS[$templet];
        $batchSmsAttributes = new BatchSmsAttributes($SMSSignName, $SMSTemplateCode);
        $batchSmsAttributes->addReceiver($mobile, $params);
        $messageAttributes = new MessageAttributes([$batchSmsAttributes]);
        $messageBody = 'smsmessage';
        $request = new PublishMessageRequest($messageBody, $messageAttributes);
        $res = $topic->publishMessage($request);
        if ($res->isSucceed()) {
            return $res->getMessageId();
        } else {
            throw new HttpException(408, '短信发送失败，请稍后重试', 100010005);
        }
    }
}