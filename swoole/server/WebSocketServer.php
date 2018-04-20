<?php
/**
 * WebSocket 服务
 *
 * User: zengzhifei
 * Date: 2018/1/29
 * Time: 11:29
 */

namespace swoole\server;

use swoole\common\Config;
use swoole\common\Redis;
use swoole\common\Utils;

class WebSocketServer extends SwooleServer
{
    //服务类型
    protected $serverType = SWOOLE_WEB_SOCKET_SERVER;
    //监听IP
    protected $host = '0.0.0.0';
    //监听客户端端口
    protected $port = 9501;
    //监听管理端端口
    protected $adminPort = 9500;
    //服务进程运行模式
    protected $mode = SWOOLE_PROCESS;
    //服务Sock类型
    protected $sockType = SWOOLE_SOCK_TCP;
    //参数配置
    protected $option = [
        //守护进程化
        'daemonize' => false,
    ];

    /**
     * 主进程启动回调事件
     * @param \swoole_websocket_server $server
     */
    public function onStart(\swoole_websocket_server $server)
    {
        //设置主进程别名
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title('WebSocketServer master');
        } else {
            @swoole_set_process_name('WebSocketServer master');
        }

        //异步写入服务器IP到缓存
        try {
            $redisConfig = Config::loadConfig('redis');
            $redisOptions = $redisConfig->get('redis_master');
            $redisKeys = $redisConfig->get('redis_key');
            $ips = Utils::getServerIp();
            foreach ($ips as $ip) {
                Redis::getInstance($redisOptions, false, ['sAdd', $redisKeys['Server_Ip'], $ip]);
            }
        } catch (\Exception $e) {
            Utils::asyncLog($e->getMessage());
        }
    }

    /**
     * 工作进程启动回调函数
     * @param \swoole_server $server
     * @param $work_id
     */
    public function onWorkerStart(\swoole_server $server, $work_id)
    {
        //self::workerStart();
    }

    /**
     * 服务器连接回调函数
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     */
    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        $redis = self::getRedis();
        $ip = $redis->SMEMBERS(config('swoole_key.server_ip'));
        var_dump($ip);

        //$this->userTable->set($request->fd,['user_id'=>]);
    }

    /**
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     */
    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {

    }

    /**
     * @param \swoole_server $server
     * @param $task_id
     * @param $src_worker_id
     * @param $data
     */
    public function onTask(\swoole_server $server, $task_id, $src_worker_id, $data)
    {

    }

    /**
     * @param \swoole_server $server
     * @param $task_id
     * @param $data
     */
    public function onFinish(\swoole_server $server, $task_id, $data)
    {

    }
}
