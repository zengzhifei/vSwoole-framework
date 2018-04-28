<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

return [
    //服务端连接配置
    'timer_server_connect' => [
        //服务类型
        'serverType'        => VSWOOLE_TIMER_SERVER,
        //监听IP
        'host'              => '0.0.0.0',
        //监听客户端端口
        'port'              => 9500,
        //服务进程运行模式
        'mode'              => SWOOLE_PROCESS,
        //服务Sock类型
        'sockType'          => SWOOLE_SOCK_TCP,
        //监听管理端IP
        'adminHost'         => '0.0.0.0',
        //监听管理端端口
        'adminPort'         => 9400,
        //监听管理Sock类型
        'adminSockType'     => SWOOLE_SOCK_TCP,
        //监听其他客户端IP+端口
        'others'            => [],
        //监听其他客户端Sock类型
        'othersSockType'    => '',
        //服务回调事件列表
        'callbackEventList' => [],
    ],
    //服务端配置
    'timer_server_config'  => [
        //守护进程化
        'daemonize'                => false,
        //日志
        'log_file'                 => VSWOOLE_LOG_SERVER_PATH . 'Timer.log',
        //工作进程数
        'worker_num'               => 2,
        //工作线程数
        'reactor_num'              => 1,
        //TASK进程数
        'task_worker_num'          => 2,
        //心跳检测最大时间间隔
        'heartbeat_check_interval' => 60,
        //连接最大闲置时间
        'heartbeat_idle_time'      => 600,
        //启用CPU亲和性设置
        'open_cpu_affinity'        => true,
        //debug模式
        'debug_mode'               => false,
        //SSL Crt
        'ssl_cert_file'            => 'server.crt',
        //SSL Key
        'ssl_key_file'             => 'server.key'
    ],
    //管理客户端连接配置
    'timer_client_connect' => [
        //服务Sock类型
        'sockType'      => SWOOLE_SOCK_TCP,
        //同步异步(PHP-FPM/APACHE模式下只允许同步)
        'syncType'      => SWOOLE_SOCK_SYNC,
        //长连接Key
        'connectionKey' => '',
        //服务器地址
        'host'          => '10.4.0.100',
        //服务器端口
        'port'          => 9400,
        //连接超时
        'timeout'       => 3,
        //连接是否阻塞
        'flag'          => 0,
    ],
    //客户端配置
    'timer_client_config'  => [

    ],
    //其他配置
    'timer_other_config'   => [
        'is_cache_config' => true,
    ]
];