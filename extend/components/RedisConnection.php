<?php
/**
 * Created by PhpStorm.
 * User: tomcao
 * Date: 2017/5/3
 * Time: 17:09
 */

namespace app\components\db;

use Yii;
use Redis;
use RedisException;
use yii\base\Configurable;
use yii\base\InvalidParamException;

class RedisConnection extends Redis implements Configurable
{
    public $hostname = 'localhost';
    public $port = 6379;
    public $unixSocket;
    public $password;
    public $database = 0;
    public $connectionTimeout = 0.0;

    private $_db = 0;

    public static function className()
    {
        return get_called_class();
    }

    public function __construct(array $config = [])
    {
        if ($config) {
            Yii::configure($this, $config);
        }
        $this->init();
    }

    protected function init()
    {
        $this->_connect();
    }

    private function _connect()
    {
        if ($this->unixSocket !== null) {
            $isConnected = $this->connect($this->unixSocket);
        } else {
            $isConnected = $this->pconnect($this->hostname, $this->port,
                $this->connectionTimeout);
        }

        if ($isConnected === false) {
            throw new RedisException('Connection refused');
        }

        if ($this->password !== null) {
            $this->auth($this->password);
        }

        if ($this->database !== null) {
            $this->select($this->database);
        }

        if ($this->ping() !== '+PONG') {
            throw new RedisException('NOAUTH Authentication required.');
        }
    }

    /**
     * 生成某类型的Key
     *
     * @param $pattern key的模式
     * @param array ...$args 模式中替代成数据
     * @return string
     *
     * generateKey('test:*', 'arg') 会返回 test:arg
     *
     */
    public function generateKey($pattern, ...$args)
    {
        $str_count = substr_count($pattern, '*');
        $arg_count = count($args);
        if ($str_count !== $arg_count)
            throw new InvalidParamException("pattern expects $str_count arguments $arg_count given");
        $pattern = str_replace('*', '%s', $pattern);
        $key = sprintf($pattern, ...$args);
        return $key;
    }

    /**
     * 返回某个 key 模式下所有的 key
     *
     * @param string $pattern key 的模式
     * @param int $limit limit 为正整数。返回的最大数量（需要注意的是，最大返回数量可能略大于1000）
     * @param null $cursor 游标，此变量为引用变量。
     * @return array 返回的key
     */
    public function getPatternKeys(string $pattern, int $limit = 200, &$cursor = Null): array
    {
        $keys = [];

        if ($limit < 0) throw new InvalidParamException('limit experts bigger than zero');
        $limit = ($limit && $limit > 1000) ? 1000 : $limit;

        do {
            $sig_keys = $this->scan($cursor, $pattern, 200);
            array_push($keys, ...$sig_keys);
            if ($limit <= count($keys)) break;
        } while ($cursor);
        return array_unique($keys);
    }

    /**
     * 删除某个 key 模式下所有的 key
     *
     * @param string $pattern key 的模式
     * @return int 删除key的数量
     */
    public function delPatternKeys(string $pattern): int
    {
        $cursor = Null;
        $count = 0;
        do {
            $keys = $this->scan($cursor, $pattern, 1000);
            $count += $this->del($keys);
        } while($cursor);
        return $count;
    }

    /**
     * 设置分布式锁，有至多1秒的延时
     *
     * @param string $lock_key 锁名
     * @param int $timeout 超时时间
     * @return bool 上锁是否成功
     */
    public function lock($lock_key, int $timeout)
    {
    $now = time();
    $timestamp = $now + $timeout + 1;
    return $this->lockAt($lock_key, $timestamp);
    }

    /**
     * 设置分布式锁
     *
     * @param string $lock_key 锁名
     * @param int $timestamp 锁过期时间
     * @return bool 上锁是否成功
     */
    public function lockAt($lock_key, int $timestamp)
    {
        $now = time();
        if ($this->setnx($lock_key, $timestamp) ||
            ($now > $this->get($lock_key)) && ($now > $this->getSet($lock_key, $timestamp))) {
            $this->expireAt($lock_key, $timestamp);
            return true;
        }
        return false;
    }

    /**
     * 分布式锁解锁
     *
     * @param string $lock_key 锁名
     * @return bool 是否解锁成功
     */
    public function unlock($lock_key)
    {
        $now = time();
        if ($now < $this->get($lock_key)) {
            $this->del($lock_key);
            return true;
        }
        return false;
    }

    /**
     * 计数器
     *
     * @param $key 计数器的键
     * @param int $timeout 过期时间，0为不设置过期时间
     * @param int $max_count 最大次数，0为不设置最大次数
     * @return bool|int|mixed 超过最大次数返回 false, 其他则返回计数次数
     */
    public function counter($key, $timeout = 0, $max_count = 0)
    {
        if (0 !== $timeout) {
            $script = <<<'EOT'
local current
current = redis.call("incr", KEYS[1])
if tonumber(current) == 1 then
    redis.call("expire", KEYS[1], KEYS[2])
end
return current
EOT;
            $count = $this->eval($script, [$key, $timeout], 2);
        } else {
            $count = $this->incr($key);
        }
        if ($max_count && $count > $max_count) {
            return false;
        } else {
            return $count;
        }
    }

    /**
     * 获取某个模式下永久key
     *
     * @param string $pattern key 的模式，当为 null 时匹配所有key
     * @param int $limit 默认值 200
     * @param &int $cursor 游标，引用类型
     * @return array
     */
    public function getPermanentKeys($pattern = Null, int $limit = 200, &$cursor = Null)
    {
        $array = [];

        if ($limit < 0) throw new InvalidParamException('limit experts bigger than zero');
        $limit = ($limit && $limit > 1000) ? 1000 : $limit;

        do {
            $keys = $this->scan($cursor, $pattern, 200);
            foreach ($keys as $key) {
                if (-1 === $this->ttl($key)) {
                    array_push($array, $key);
                }
            }
            if ($limit <= count($keys)) break;
        } while($cursor);
        return array_unique($array);
    }

    /**
     * 获取某个模式下的闲置key
     *
     * @param null $pattern key 的模式，当为 null 时匹配所有key
     * @param int $limit 默认值 200
     * @param int $time 默认值 1天
     * @param $cursor 游标，引用类型
     * @return array
     */
    public function getIdleKeys($pattern = Null, int $limit = 200, &$cursor, $time = ONE_DAY)
    {
        $idle_keys = [];

        if ($time < 0) throw new InvalidParamException('time experts bigger than zero');
        if ($limit < 0) throw new InvalidParamException('limit experts bigger than zero');
        $limit = ($limit && $limit > 1000) ? 1000 : $limit;

        do {
            $keys = $this->scan($cursor, $pattern, 200);
            $part_keys = array_filter($keys, function($key) use($time) {
                return $this->object('idletime', $key) > $time;
            });
            $idle_keys = array_merge($idle_keys, $part_keys);
            if ($limit <= count($idle_keys)) break;
        } while($cursor);
        return $idle_keys;
    }

    /**
     * 刪除並获取 hash 对象
     *
     * @param $key  hash 对象的 key
     * @param int $timeout 获取超时时间, 0为不超时，默认值为5秒
     * @return array 返回 hash 对象
     * @throws RedisException 超时抛出异常
     */
    public function hGetDel($key, $timeout = 5)
    {
        $type = $this->type($key);
        if (self::REDIS_HASH !== $type)
            throw new InvalidParamException('key must be a HASH KEY');

        $count = 0;
        $time = 1;
        $stime = time();
        $this->watch($key);
        while(true) {
            $ret = $this->multi()
                ->hGetAll($key)
                ->del($key)
                ->exec();
            if (false !== $ret) break;
            if ($timeout && (time() - $stime) > $timeout)
                throw new RedisException('Get Hash Key TIMEOUT');
            // 尝试多次后失败后，会延时尝试。
            if (!(++$count % 3)) {
                sleep($time);
                $time *= 2;
            }
        }
        return $ret[0];
    }

    /**
     * 选择 database
     *
     * @param int $dbindex database的序列号
     */
    public function select($dbindex)
    {
        parent::select($dbindex);
        $this->_db = $dbindex;
    }

    /**
     * @return int 返回database的序列号
     */
    public function getDb()
    {
        return $this->_db;
    }
}