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
use vSwoole\library\common\exception\Exception;
use vSwoole\library\common\Utils;

class WebSocketServer extends Server
{
    /**
     * 启动服务器
     * @param array $connectOptions
     * @param array $configOptions
     * @throws \ReflectionException
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        try {
            $server_connect = array_merge(Config::loadConfig('websocket')->get('server_connect'), $connectOptions);
            $erver_config = array_merge(Config::loadConfig('websocket')->get('server_config'), $configOptions);

            if (!parent::__construct($server_connect, $erver_config)) {
                throw new \Exception("Swoole WebSocket Server start failed", $this->swoole->getLastError());
            }
        } catch (\Exception $e) {
            Exception::reportError($e);
        }
    }

    /**
     * 主进程启动回调函数
     * @param \swoole_server $server
     */
    public function onStart(\swoole_server $server)
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
     * 主进程结束回调函数
     * @param \swoole_server $server
     */
    public function onShutdown(\swoole_server $server)
    {

    }

    /**
     * 管理进程启动回调函数
     * @param \swoole_server $server
     */
    public function onManagerStart(\swoole_server $server)
    {
        //设置管理进程别名
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title(VSWOOLE_WEB_SOCKET_SERVER . ' manager');
        } else {
            @swoole_set_process_name(VSWOOLE_WEB_SOCKET_SERVER . ' manager');
        }
    }

    /**
     * 管理进程结束回调函数
     * @param \swoole_server $server
     */
    public function onManagerStop(\swoole_server $server)
    {

    }

    /**
     * 工作进程启动回调函数
     * @param \swoole_server $server
     * @param int $worker_id
     */
    public function onWorkerStart(\swoole_server $server, int $worker_id)
    {
        //设置工作进程别名
        $worker_name = $server->taskworker ? ' tasker/' . $worker_id : ' worker/' . $worker_id;
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title(VSWOOLE_WEB_SOCKET_SERVER . $worker_name);
        } else {
            @swoole_set_process_name(VSWOOLE_WEB_SOCKET_SERVER . $worker_name);
        }
        //缓存配置
        $is_cache = Config::loadConfig('websocket')->get('other_config.is_cache_config');
        $is_cache && Config::cacheConfig();
    }

    /**
     * 工作进程结束回调函数
     * @param \swoole_server $server
     * @param int $worker_id
     */
    public function onWorkerStop(\swoole_server $server, int $worker_id)
    {

    }

    /**
     * 工作进程退出回调函数
     * @param \swoole_server $server
     * @param int $worker_id
     */
    public function onWorkerExit(\swoole_server $server, int $worker_id)
    {

    }

    /**
     * 工作进程异常回调函数
     * @param \swoole_server $server
     * @param int $worker_id
     */
    public function onWorkerError(\swoole_server $server, int $worker_id)
    {

    }

    /**
     * 客户端连接回调函数
     * @param \swoole_server $server
     * @param int $fd
     * @param int $reactor_id
     */
    public function onConnect(\swoole_server $server, int $fd, int $reactor_id)
    {

    }

    /**
     * 接收客户端数据回调函数
     * @param \swoole_server $server
     * @param int $fd
     * @param int $reactor_id
     * @param string $data
     */
    public function onReceive(\swoole_server $server, int $fd, int $reactor_id, string $data)
    {

    }

    /**
     * 接收客户端UDP数据回调函数
     * @param \swoole_server $server
     * @param string $data
     * @param array $client_info
     */
    public function onPacket(\swoole_server $server, string $data, array $client_info)
    {

    }

    /**
     * 客户端断开回调函数
     * @param \swoole_server $server
     * @param int $fd
     * @param int $reactor_id
     */
    public function onClose(\swoole_server $server, int $fd, int $reactor_id)
    {

    }

    /**
     * 缓存区达到最高水位时回调函数
     * @param \swoole_server $server
     * @param int $fd
     */
    public function onBufferFull(\swoole_server $server, int $fd)
    {

    }

    /**
     * 缓存区达到最低水位时回调函数
     * @param \swoole_server $server
     * @param int $fd
     */
    public function onBufferEmpty(\swoole_server $server, int $fd)
    {

    }

    /**
     * 异步任务执行回调函数
     * @param \swoole_server $server
     * @param int $task_id
     * @param int $src_worker_id
     * @param $data
     */
    public function onTask(\swoole_server $server, int $task_id, int $src_worker_id, $data)
    {

    }

    /**
     * 异步任务执行完成回调函数
     * @param \swoole_server $server
     * @param int $task_id
     * @param $data
     */
    public function onFinish(\swoole_server $server, int $task_id, $data)
    {

    }

    /**
     * 工作进程接收管道消息回调函数
     * @param \swoole_server $server
     * @param int $src_worker_id
     * @param $data
     */
    public function onPipeMessage(\swoole_server $server, int $src_worker_id, $data)
    {

    }

    /**
     * 客户端与WebSocket建立连接成功后回调函数
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     */
    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {

    }

    /**
     * WebSocket服务端接收客户端消息回调函数
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     */
    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        //向PHP-FPM 或Apache 模式的管理客户端返回数据接收成功状态
        if ($frame->finish) {
            $client_info = $server->getClientInfo($frame->fd);
            $admin_port = Config::loadConfig('websocket')->get('server_connect.adminPort');
            if ($client_info && $admin_port == $client_info['server_port']) {
                $server->push($frame->fd, 'ok');
            }
        }
    }

    /**
     * 接收Http客户端请求回调函数
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

    }

}