<?php
/**
 * Swoole 服务底层抽象模型
 * User: zengzhifei
 * Date: 2018/1/29
 * Time: 11:29
 */

namespace library\server;

abstract class Swoole
{
    //服务对象
    protected $swoole;
    //服务连接配置
    protected $connectOptions = [
        //服务类型
        'serverType'     => '',
        //监听IP
        'host'           => '0.0.0.0',
        //监听客户端端口
        'port'           => 9501,
        //服务进程运行模式
        'mode'           => SWOOLE_PROCESS,
        //服务Sock类型
        'sockType'       => SWOOLE_SOCK_TCP,
        //监听管理端IP
        'adminHost'      => '',
        //监听管理端端口
        'adminPort'      => '',
        //监听管理Sock类型
        'adminSockType'  => '',
        //监听其他客户端IP+端口
        'others'         => [],
        //监听其他客户端Sock类型
        'othersSockType' => '',
    ];
    //服务运行配置
    protected $configOptions = [
        //守护进程化
        'daemonize'                => true,
        //日志
        'log_file'                 => VSWOOLE_SERVER_LOG_PATH . 'vswoole.log',
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
    //服务回调事件列表
    protected $callbackEventList = [
        'Start',
        'Shutdown',
        'WorkerStart',
        'WorkerStop',
        'WorkerExit',
        'WorkerError',
        'Connect',
        'Receive',
        'Packet',
        'Close',
        'BufferFull',
        'BufferEmpty',
        'Task',
        'Finish',
        'PipeMessage',
        'ManagerStart',
        'ManagerStop',
        'Timer',
        'HandShake',
        'Open',
        'Message',
        'Request'
    ];

    /**
     * Swoole constructor.
     * @param array $connect_options
     * @param array $config_options
     */
    public function __construct(array $connect_options = [], array $config_options = [])
    {
        //配置相关参数
        $this->connectOptions = array_merge($this->connectOptions, $connect_options);
        $this->configOptions = array_merge($this->configOptions, $config_options);

        // 实例化服务
        if (!empty($this->connectOptions)) {
            switch ($this->connectOptions['serverType']) {
                case VSWOOLE_WEB_SOCKET_SERVER:
                    $this->swoole = new \swoole_websocket_server($this->connectOptions['host'], $this->connectOptions['port'], $this->connectOptions['mode'], $this->connectOptions['sockType']);
                    break;
                case VSWOOLE_HTTP_SERVER:
                    $this->swoole = new \swoole_http_server($this->connectOptions['host'], $this->connectOptions['port']);
                    break;
                default:
                    $this->swoole = new \swoole_server($this->connectOptions['host'], $this->connectOptions['port'], $this->connectOptions['mode'], $this->connectOptions['sockType']);
                    break;
            }
        }

        // 设置服务参数
        if (!empty($this->configOptions)) {
            $cpu_cores = swoole_cpu_num();
            $this->configOptions['worker_num'] = $cpu_cores ? $this->configOptions['worker_num'] * $cpu_cores : $this->configOptions['worker_num'];
            $this->configOptions['reactor_num'] = $cpu_cores ? $this->configOptions['reactor_num'] * $cpu_cores : $this->configOptions['reactor_num'];
            $this->configOptions['task_worker_num'] = $cpu_cores ? $this->configOptions['task_worker_num'] * $cpu_cores : $this->configOptions['task_worker_num'];
            $this->configOptions = array_merge($this->configOptions, $config_options);
            $this->swoole->set($this->configOptions);
        }

        // 设置服务回调事件
        if (!empty($this->callbackEventList)) {
            foreach ($this->callbackEventList as $event) {
                if (method_exists($this, 'on' . $event)) {
                    $this->swoole->on($event, [$this, 'on' . $event]);
                }
            }
        }

        //监听管理端IP+端口
        if ($this->connectOptions['adminHost'] && $this->connectOptions['adminPort']) {
            $this->swoole->addListener($this->connectOptions['adminHost'], $this->connectOptions['adminPort'], $this->connectOptions['adminSockType'] ? $this->connectOptions['adminSockType'] : $this->connectOptions['sockType']);
        }

        //监听其他客户端IP+端口
        if (is_array($this->connectOptions['others'])) {
            foreach ($this->connectOptions['others'] as $ip => $port) {
                $this->swoole->addListener($ip, $port, $this->connectOptions['othersSockType'] ? $this->connectOptions['othersSockType'] : $this->connectOptions['sockType']);
            }
        }

        //开启服务
        $this->swoole->start();
    }

    /**
     * @param $method
     * @param $args
     */
    public function __call($method, $args)
    {
        call_user_func_array([$this->swoole, $method], $args);
    }
}