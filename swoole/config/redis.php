<?php
/**
 * 缓存配置文件
 * User: zengz
 * Date: 2018/4/19
 * Time: 16:52
 */
return [
    'redis_master' => [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => true,
        'prefix'     => '',
    ],
    'redis_key'    => [
        'Server_Ip' => 'Server_Ip'
    ]
];