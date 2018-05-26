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
    'server_connect' => [
        //服务类型
        'serverType'        => VSWOOLE_UDP_SERVER,
        //监听IP
        'host'              => '0.0.0.0',
        //监听客户端端口
        'port'              => 9504,
        //服务进程运行模式
        'mode'              => SWOOLE_PROCESS,
        //服务Sock类型
        'sockType'          => SWOOLE_SOCK_UDP,
        //监听管理端IP
        'adminHost'         => '0.0.0.0',
        //监听管理端端口
        'adminPort'         => 8504,
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
    'server_config'  => [
        //守护进程化
        'daemonize'       => true,
        //日志
        'log_file'        => VSWOOLE_LOG_SERVER_PATH . 'udp.log',
        //工作进程数
        'worker_num'      => 0,
        //工作线程数
        'reactor_num'     => 0,
        //TASK进程数
        'task_worker_num' => 0,
        //PID
        'pid_file'        => VSWOOLE_DATA_PID_PATH . VSWOOLE_UDP_SERVER . '_Master' . VSWOOLE_PID_EXT,
        //SSL Crt
        'ssl_cert_file'   => 'server.crt',
        //SSL Key
        'ssl_key_file'    => 'server.key',
    ],
    //管理客户端连接配置
    'client_connect' => [
        //服务Sock类型
        'sockType'      => SWOOLE_SOCK_UDP,
        //同步异步(PHP-FPM/APACHE模式下只允许同步)
        'syncType'      => SWOOLE_SOCK_SYNC,
        //长连接Key
        'connectionKey' => '',
        //服务器地址
        'host'          => '127.0.0.1',
        //服务器端口
        'port'          => 8504,
        //连接超时
        'timeout'       => 3,
        //连接是否阻塞
        'flag'          => 0,
    ],
    //客户端配置
    'client_config'  => [

    ],
    //其他配置
    'other_config'   => [
        'is_cache_config' => true,
    ]
];