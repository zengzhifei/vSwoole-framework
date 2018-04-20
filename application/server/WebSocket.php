<?php
/**
 * WebSocket 服务器
 * User: zengzhifei
 * Date: 2018/1/29
 * Time: 11:29
 */

namespace application\server;

use library\common\Config;
use library\common\Redis;
use library\common\Utils;
use library\server\Swoole;

class WebSocket extends Swoole
{
    /**
     * 启动服务器
     */
    public function __construct()
    {
        $wsConfig = Config::loadConfig('websocket');
        $ws_connect_options = $wsConfig->get('ws_connect_options');
        $ws_config_options = $wsConfig->get('ws_config_options');

        parent::__construct($ws_connect_options, $ws_config_options);
    }

    /**
     * 服务器主进程启动回调事件
     * @param \swoole_websocket_server $server
     */
    public function onStart(\swoole_websocket_server $server)
    {
        //设置主进程别名
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title('WebSocket master');
        } else {
            @swoole_set_process_name('WebSocket master');
        }

        //异步写入服务器IP到缓存
        try {
            $redisConf = Config::loadConfig('redis');
            $redisOptions = $redisConf->get('redis_master');
            $redisKeys = $redisConf->get('redis_key');
            $ips = Utils::getServerIp();
            foreach ($ips as $ip) {
                Redis::getInstance($redisOptions, false, ['sAdd', [$redisKeys['Server_Ip'], $ip]]);
            }
        } catch (\Exception $e) {
            Utils::asyncLog($e->getMessage());
        }
    }

    /**
     * 客户端连接服务器回调函数
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     */
    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        var_dump($request);
    }

    /**
     * 服务器接收客户端消息回调函数
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
