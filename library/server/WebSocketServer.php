<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace library\server;


use library\common\Config;
use library\common\Exception;
use library\common\Log;
use library\common\Redis;
use library\common\Utils;
use library\server\Server;

class WebSocketServer extends Server
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
     * 服务器主进程启动回调函数
     * @param \swoole_websocket_server $server
     */
    public function onStart(\swoole_websocket_server $server)
    {
        //展示服务启动信息
        $this->startShowServerInfo();

        //设置主进程别名
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title(VSWOOLE_WEB_SOCKET_SERVER . ' master');
        } else {
            @swoole_set_process_name(VSWOOLE_WEB_SOCKET_SERVER . ' master');
        }

        //异步记录服务进程PID
        Utils::writePid($server->master_pid, VSWOOLE_WEB_SOCKET_SERVER . '_Master');
        Utils::writePid($server->manager_pid, VSWOOLE_WEB_SOCKET_SERVER . '_Manager');
    }

    /**
     * 客户端连接服务器回调函数
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     */
    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {

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
