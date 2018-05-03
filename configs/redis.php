<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

return [
    //缓存连接配置（正式）
    'redis_master' => [
        'host'       => '10.4.0.100',
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
            'Server_Ip' => 'WebSocket-Server_Ip',
            'Link_Info' => 'WebSocket-Link_Info',
            'User_Info' => 'WebSocket-User_Info',
        ],
        'Timer'     => [
            'Task_List' => 'Timer-Task_List',
        ]
    ],
];