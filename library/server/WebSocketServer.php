<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\server;


use vSwoole\library\common\Config;
use vSwoole\library\common\Exception;
use vSwoole\library\common\Utils;

class WebSocketServer extends Server
{
    /**
     * 启动服务器
     * WebSocketServer constructor.
     * @param array $connectOptions
     * @param array $configOptions
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        try {
            $ws_server_connect = array_merge(Config::loadConfig('websocket')->get('ws_server_connect'), $connectOptions);
            $ws_server_config = array_merge(Config::loadConfig('websocket')->get('ws_server_config'), $configOptions);

            if (!parent::__construct($ws_server_connect, $ws_server_config)) {
                throw new \Exception("Swoole WebSocket Server start failed", $this->swoole->getLastError());
            }
        } catch (\Exception $e) {
            Exception::reportError($e);
        }
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
        //向PHP-FPM 或Apache 模式的管理客户端返回数据接收成功状态
        if ($frame->finish) {
            $client_info = $server->getClientInfo($frame->fd);
            $admin_port = Config::loadConfig('websocket')->get('ws_server_connect.adminPort');
            if ($client_info && $admin_port == $client_info['server_port']) {
                $server->push($frame->fd, 'ok');
            }
        }
    }

    /**
     * 服务器执行异步任务回调函数
     * @param \swoole_server $server
     * @param $task_id
     * @param $src_worker_id
     * @param $data
     */
    public function onTask(\swoole_server $server, $task_id, $src_worker_id, $data)
    {

    }

    /**
     * 服务器异步任务执行结束回调函数
     * @param \swoole_server $server
     * @param $task_id
     * @param $data
     */
    public function onFinish(\swoole_server $server, $task_id, $data)
    {

    }

    /**
     * 客户端断开连接回调函数
     * @param \swoole_websocket_server $server
     * @param $fd
     */
    public function onClose(\swoole_websocket_server $server, $fd)
    {

    }
}
