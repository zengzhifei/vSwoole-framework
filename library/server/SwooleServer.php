<?php
/**
 * Swoole 服务基类
 *
 * User: zengzhifei
 * Date: 2018/1/29
 * Time: 11:29
 */

namespace swoole\server;

use think\cache\driver\Redis;

class SwooleServer extends Server
{
    //redis
    protected static $_redis = null;

    /**
     * 单例连接redis
     * @return null|object
     */
    protected static function getRedis()
    {
        if (empty(self::$_redis)) {
            self::$_redis = (new Redis(config('redis_master')))->handler();
        }
        return self::$_redis;
    }





    /**
     * 服务进程启动执行事件
     * 在WorkerStart回调事件中先调用
     */
    protected function workerStart()
    {
        //加载框架
        try {
            require __DIR__ . '/../public/index.php';
        } catch (\Exception $e) {
            if (!$this->server->taskworker) {
                self::asyncLog($e->getMessage());
            }
        }
        //将服务器IP写入公共存储
        $redis = self::getRedis();
        $ips = self::getServerIp();
        foreach ($ips as $ip) {
            $redis->sAdd(config('swoole_key.server_ip'), $ip);
        }
        $con = config('redis.swoole_key');
        var_dump($con);
        return;
    }

}