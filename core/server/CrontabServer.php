<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\core\server;


use vSwoole\library\common\Config;
use vSwoole\library\common\exception\Exception;
use vSwoole\library\common\Utils;
use vSwoole\library\server\Server;

class CrontabServer extends Server
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
            $server_connect = array_merge(Config::loadConfig('crontab')->get('server_connect'), $connectOptions);
            $server_config = array_merge(Config::loadConfig('crontab')->get('server_config'), $configOptions);

            if (!parent::__construct($server_connect, $server_config)) {
                throw new \Exception("Swoole Crontab Server start failed", $this->swoole->getLastError());
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
        Utils::setProcessName(VSWOOLE_CRONTAB_SERVER . ' master');
        //异步记录服务进程PID
        Utils::writePid($server->manager_pid, VSWOOLE_CRONTAB_SERVER . '_Manager');
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
        Utils::setProcessName(VSWOOLE_CRONTAB_SERVER . ' manager');
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
        Utils::setProcessName(VSWOOLE_CRONTAB_SERVER . $worker_name);
        //缓存配置
        $is_cache = Config::loadConfig('crontab')->get('other_config.is_cache_config');
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
     * 客户端与WebSocket建立连接后握手回调函数
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    public function onHandShake(\swoole_http_request $request, \swoole_http_response $response)
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