<?php
/**
 * Swoole 服务底层抽象模型
 *
 * User: zengzhifei
 * Date: 2018/1/29
 * Time: 11:29
 */

namespace swoole\server;

abstract class Server
{
    //服务对象
    protected $server;
    //服务类型
    protected $serverType = '';
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
    //服务事件列表
    protected $eventList = ['Start', 'Shutdown', 'WorkerStart', 'WorkerStop', 'WorkerExit', 'WorkerError', 'Connect', 'Receive', 'Packet', 'Close', 'BufferFull', 'BufferEmpty', 'Task', 'Finish', 'PipeMessage', 'ManagerStart', 'ManagerStop', 'Timer', 'HandShake', 'Open', 'Message', 'Request'];
    //服务配置
    protected $option = [];
    //服务默认配置
    protected $defaultOption = [
        //守护进程化
        'daemonize'                => true,
        //日志
        'log_file'                 => SWOOLE_LOG_PATH . 'swoole.log',
        //工作进程数
        'worker_num'               => 4,
        //工作线程数
        'reactor_num'              => 2,
        //TASK进程数
        'task_worker_num'          => 4,
        //心跳检测最大时间间隔
        'heartbeat_check_interval' => 60,
        //连接最大闲置时间
        'heartbeat_idle_time'      => 600,
        //启用CPU亲和性设置
        'open_cpu_affinity'        => true
    ];

    /**
     * Server constructor.
     */
    public function __construct()
    {
        // 实例化服务
        switch ($this->serverType) {
            case SWOOLE_WEB_SOCKET_SERVER:
                $this->server = new \swoole_websocket_server($this->host, $this->port, $this->mode, $this->sockType);
                break;
            case SWOOLE_HTTP_SERVER:
                $this->server = new \swoole_http_server($this->host, $this->port);
                break;
            default:
                $this->server = new \swoole_server($this->host, $this->port, $this->mode, $this->sockType);
                break;
        }
        // 设置参数
        if (!empty($this->option)) {
            $cpu_cores = swoole_cpu_num();
            $this->defaultOption['worker_num'] = $cpu_cores ? $this->defaultOption['worker_num'] * $cpu_cores : $this->defaultOption['worker_num'];
            $this->defaultOption['reactor_num'] = $cpu_cores ? $this->defaultOption['reactor_num'] * $cpu_cores : $this->defaultOption['reactor_num'];
            $this->defaultOption['task_worker_num'] = $cpu_cores ? $this->defaultOption['task_worker_num'] * $cpu_cores : $this->defaultOption['task_worker_num'];
            $this->option = array_merge($this->defaultOption, $this->option);
            $this->server->set($this->option);
        }
        // 设置回调
        foreach ($this->eventList as $event) {
            if (method_exists($this, 'on' . $event)) {
                $this->server->on($event, [$this, 'on' . $event]);
            }
        }
        //监听管理端端口
        $this->server->addListener($this->host, $this->adminPort, $this->sockType);
        //开启服务
        $this->server->start();
    }

    /**
     * @param $method
     * @param $args
     */
    public function __call($method, $args)
    {
        call_user_func_array([$this->server, $method], $args);
    }
}