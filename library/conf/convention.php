<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

return [
    'define' => [
        //框架应用根目录
        'VSWOOLE_APP_PATH'             => VSWOOLE_ROOT . 'application/',
        //框架应用服务端目录
        'VSWOOLE_APP_SERVER_PATH'      => VSWOOLE_ROOT . 'application/server/',
        //框架应用客户端目录
        'VSWOOLE_APP_CLIENT_PATH'      => VSWOOLE_ROOT . 'application/client/',
        //框架配置目录
        'VSWOOLE_CONFIG_PATH'          => VSWOOLE_ROOT . 'configs/',
        //框架数据根目录
        'VSWOOLE_DATA_PATH'            => VSWOOLE_ROOT . 'data/',
        //框架数据服务进程目录
        'VSWOOLE_DATA_PID_PATH'        => VSWOOLE_ROOT . 'data/pid/',
        //框架核心根目录
        'VSWOOLE_LIB_PATH'             => VSWOOLE_ROOT . 'library/',
        //框架核心工具目录
        'VSWOOLE_LIB_COMMON_PATH'      => VSWOOLE_ROOT . 'library/common/',
        //框架核心服务目录
        'VSWOOLE_LIB_SERVER_PATH'      => VSWOOLE_ROOT . 'library/server/',
        //框架日志根目录
        'VSWOOLE_LOG_PATH'             => VSWOOLE_ROOT . 'log/',
        //框架日志服务端目录
        'VSWOOLE_LOG_SERVER_PATH'      => VSWOOLE_ROOT . 'log/server/',
        //框架日志客户端目录
        'VSWOOLE_LOG_CLIENT_PATH'      => VSWOOLE_ROOT . 'log/client/',


        //服务器
        'VSWOOLE_SERVER'               => 1,
        //客户端
        'VSWOOLE_CLIENT'               => 2,
        //Http服务
        'VSWOOLE_HTTP_SERVER'          => 'Swoole_Http_Server',
        //WebSocket服务
        'VSWOOLE_WEB_SOCKET_SERVER'    => 'Swoole_WebSocket_Server',

        //根命名空间
        'VSWOOLE_NAMESPACE'            => 'vSwoole',
        //服务端命名空间
        'VSWOOLE_APP_SERVER_NAMESPACE' => 'vSwoole\application\server',
        //客户端命名空间
        'VSWOOLE_APP_CLIENT_NAMESPACE' => 'vSwoole\application\client',

        //类文件扩展名
        'VSWOOLE_CLASS_EXT'            => '.php',
        //配置文件扩展名
        'VSWOOLE_CONFIG_EXT'           => '.php',
        //进程号文件扩展名
        'VSWOOLE_PID_EXT'              => '.pid',
        //日志文件扩展名
        'VSWOOLE_LOG_EXT'              => '.log',
    ],
];