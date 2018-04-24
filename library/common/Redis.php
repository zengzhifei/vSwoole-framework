<?php
/**
 * Redis工具类
 * User: zengzhifei
 * Date: 2018/4/19
 * Time: 18:09
 */

namespace vSwoole\library\common;

class Redis
{
    //同步redis对象key
    private $_instance_key = '';
    //同步redis对象
    private static $sync_instance = [];
    //客户端缓存配置
    private static $sync_config_instance = [];
    //redis配置
    private static $redisOptions = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => true,
        'prefix'     => '',
    ];


    /**
     * 同步或异步连接redis
     * @param bool $is_sync
     * @param callable|null $callback
     * @return mixed
     * @throws \Exception
     */
    private static function connect(bool $is_sync = true, callable $callback = null)
    {
        $key = md5(json_encode(self::$redisOptions));
        self::$sync_config_instance[$key] = self::$redisOptions;
        if ($is_sync) {
            if (!extension_loaded('redis')) {
                throw new \Exception('not support: redis');
            }
            $func = self::$redisOptions['persistent'] ? 'pconnect' : 'connect';
            $redis = new \Redis();
            $redis->$func(self::$redisOptions['host'], self::$redisOptions['port'], self::$redisOptions['timeout']);
            if ('' != self::$redisOptions['password']) {
                $redis->auth(self::$redisOptions['password']);
            }
            if (0 != self::$redisOptions['select']) {
                $redis->select(self::$redisOptions['select']);
            }
            self::$sync_instance[$key] = $redis;
            return self::$sync_instance[$key];
        } else {
            $redis = new \swoole_redis([
                'timeout'  => self::$redisOptions['timeout'],
                'password' => self::$redisOptions['password'],
                'database' => self::$redisOptions['select']
            ]);
            $redis->connect(self::$redisOptions['host'], self::$redisOptions['port'], function (\swoole_redis $redis, $result) use ($callback) {
                if ($result === false) {
                    throw new \Exception('connect to redis server failed');
                } else if (is_callable($callback)) {
                    $callback($redis, function ($redisKey) {
                        return self::$redisOptions['prefix'] . $redisKey;
                    });
                }
            });
        }
    }

    /**
     * 获取同步或异步Redis客户端
     * @param array $options
     * @param bool $is_sync
     * @param callable|null $callback
     * @return Redis
     * @throws \Exception
     */
    public static function getInstance(array $options = [], bool $is_sync = true, callable $callback = null)
    {
        if (is_array($options) && !empty($options)) {
            self::$redisOptions = array_merge(self::$redisOptions, $options);
        }

        $key = md5(json_encode(self::$redisOptions));
        if ($is_sync) {
            if (empty(self::$sync_instance) || !isset(self::$sync_instance[$key])) {
                self::connect($is_sync);
            }
            $me = new self();
            $me->setInstanceKey($key);
            return $me;
        } else {
            self::connect($is_sync, $callback);
        }
    }

    /**
     * 设置Redis同步客户端的唯一连接标识
     * @param string $key
     */
    private function setInstanceKey(string $key)
    {
        $this->_instance_key = $key;
    }

    /**
     * Redis同步客户端调用原生Redis方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $arguments[0] = self::$sync_config_instance[$this->_instance_key]['prefix'] . $arguments[0];
        return self::$sync_instance[$this->_instance_key]->$name(...$arguments);
    }
}