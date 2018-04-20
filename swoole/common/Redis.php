<?php
/**
 * Redis工具类
 * User: zengz
 * Date: 2018/4/19
 * Time: 18:09
 */

namespace swoole\common;


use Swoole\Mysql\Exception;
use swoole\Swoole;

class Redis
{
    //同步redis对象
    private static $sync_instance = [];
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
     * @param array|null $callback
     * @return mixed
     * @throws \Exception
     */
    private static function connect(bool $is_sync = true, array $callback = null)
    {
        $key = md5(json_encode(self::$redisOptions));
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
            $redis->connect(self::$redisOptions['host'], self::$redisOptions['port'], function (\swoole_redis $redis, $result) use ($key, $callback) {
                if ($result === false) {
                    throw new \Exception('connect to redis server failed' . PHP_EOL);
                } else if ($callback && count($callback)) {
                    $count = count($callback);
                    if (!is_callable($callback[$count - 1])) {
                        $callback[$count] = function () {
                        };
                    }
                    $redis->__call($callback[0], array_slice($callback, 1));
                }
            });
        }
    }

    /**
     * 获取同步或异步Redis客户端
     * @param array $options
     * @param bool $is_sync
     * @param array|null $callback
     * @return mixed
     * @throws \Exception
     */
    public static function getInstance(array $options = [], bool $is_sync = true, array $callback = null)
    {
        if (is_array($options) && !empty($options)) {
            self::$redisOptions = array_merge(self::$redisOptions, $options);
        }

        $key = md5(json_encode(self::$redisOptions));
        if ($is_sync) {
            if (!empty(self::$sync_instance) && isset(self::$sync_instance[$key])) {
                return self::$sync_instance[$key];
            } else {
                return self::connect($is_sync);
            }
        } else {
            return self::connect($is_sync, $callback);
        }
    }

}