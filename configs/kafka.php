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
        'serverType'        => VSWOOLE_KAFKA_SERVER,
        //监听IP
        'host'              => '0.0.0.0',
        //监听客户端端口
        'port'              => 9505,
        //服务进程运行模式
        'mode'              => SWOOLE_PROCESS,
        //服务Sock类型
        'sockType'          => SWOOLE_SOCK_TCP,
        //监听管理端IP
        'adminHost'         => '0.0.0.0',
        //监听管理端端口
        'adminPort'         => 8505,
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
        'daemonize'       => false,
        //日志
        'log_file'        => VSWOOLE_LOG_SERVER_PATH . 'Kafka.log',
        //工作进程数
        'worker_num'      => 1,
        //工作线程数
        'reactor_num'     => 0,
        //TASK进程数
        'task_worker_num' => 3,
        //PID
        'pid_file'        => VSWOOLE_DATA_PID_PATH . VSWOOLE_KAFKA_SERVER . '_Master' . VSWOOLE_PID_EXT,
        //SSL Crt
        'ssl_cert_file'   => '',
        //SSL Key
        'ssl_key_file'    => '',
    ],
    //管理客户端连接配置
    'client_connect' => [

    ],
    //客户端配置
    'client_config'  => [

    ],
    //其他配置
    'other_config'   => [
        'is_cache_config' => true,
    ]
];