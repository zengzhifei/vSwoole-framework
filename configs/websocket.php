<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

return [
    //连接配置
    'ws_connect_options' => [
        //服务类型
        'serverType'        => VSWOOLE_WEB_SOCKET_SERVER,
        //监听IP
        'host'              => '0.0.0.0',
        //监听客户端端口
        'port'              => 9501,
        //服务进程运行模式
        'mode'              => SWOOLE_PROCESS,
        //服务Sock类型
        'sockType'          => SWOOLE_SOCK_TCP,
        //监听管理端IP
        'adminHost'         => '0.0.0.0',
        //监听管理端端口
        'adminPort'         => '9500',
        //监听管理Sock类型
        'adminSockType'     => SWOOLE_SOCK_TCP,
        //监听其他客户端IP+端口
        'others'            => [],
        //监听其他客户端Sock类型
        'othersSockType'    => '',
        //服务回调事件列表
        'callbackEventList' => [],
    ],
    //服务配置
    'ws_config_options'  => [
        //守护进程化
        'daemonize'                => false,
        //日志
        'log_file'                 => VSWOOLE_LOG_SERVER_PATH . 'WebSocket.log',
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
        'open_cpu_affinity'        => true,
        //debug模式
        'debug_mode'               => false,
    ]
];