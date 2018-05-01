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

class Timer extends Server
{
    /**
     * 启动服务器
     * @param array $connectOptions
     * @param array $configOptions
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        try {
            $timer_server_connect = array_merge(Config::loadConfig('timer')->get('timer_server_connect'), $connectOptions);
            $timer_server_config = array_merge(Config::loadConfig('timer')->get('timer_server_config'), $configOptions);

            if (!parent::__construct($timer_server_connect, $timer_server_config)) {
                throw new \Exception("Swoole Timer Server start failed", $this->swoole->getLastError());
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
            @cli_set_process_title(VSWOOLE_TIMER_SERVER . ' master');
        } else {
            @swoole_set_process_name(VSWOOLE_TIMER_SERVER . ' master');
        }

        //异步记录服务进程PID
        Utils::writePid($server->master_pid, VSWOOLE_TIMER_SERVER . '_Master');
        Utils::writePid($server->manager_pid, VSWOOLE_TIMER_SERVER . '_Manager');
    }

    /**
     * 管理进程启动回调函数
     * @param \swoole_server $server
     */
    public function onManagerStart(\swoole_server $server)
    {

    }

    /**
     * 工作进程启动回调函数
     * @param \swoole_server $server
     * @param int $worker_id
     */
    public function onWorkerStart(\swoole_server $server, int $worker_id)
    {
        $is_cache = Config::loadConfig('timer')->get('timer_other_config.is_cache_config');
        if ($is_cache) {
            Config::cacheConfig();
        }
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
     * 客户端断开回调函数
     * @param \swoole_server $server
     * @param int $fd
     * @param int $reactor_id
     */
    public function onClose(\swoole_server $server, int $fd, int $reactor_id)
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
}