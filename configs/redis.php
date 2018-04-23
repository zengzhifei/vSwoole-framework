<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

return [
    //缓存连接配置
    'redis_master' => [
        'host'       => '192.168.31.100',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => true,
        'prefix'     => 'VSwoole-',
    ],
    //缓存键值
    'redis_key'    => [
        'WebSocket' => [
            'Server_Ip' => 'WebSocket_Server_Ip',
            'Link_Info' => 'WebSocket_Link_Info',
            'User_Info' => 'WebSocket_User_Info',
        ],
    ],
];