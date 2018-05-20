<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+


return [

    //是否开启调试
    'is_debug'           => true,
    //是否显示默认错误提示
    'show_default_error' => false,
    //是否记录日志
    'is_log'             => true,
    //日志记录级别
    'log_grade'          => E_ALL,
    //时区
    'timezone'           => 'PRC',
    //服务
    'server_list'        => [
        'WebSocket' => VSWOOLE_WEB_SOCKET_SERVER,
        'Crontab'   => VSWOOLE_CRONTAB_SERVER,
        'Http'      => VSWOOLE_HTTP_SERVER,
        'Udp'       => VSWOOLE_UDP_SERVER,
    ],
];