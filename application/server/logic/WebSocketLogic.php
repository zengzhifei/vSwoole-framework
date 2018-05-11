<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\application\server\logic;


use vSwoole\library\common\Config;
use vSwoole\library\common\exception\Exception;
use vSwoole\library\common\cache\Redis;
use vSwoole\library\common\Process;
use vSwoole\library\common\Utils;

class WebSocketLogic
{
    /**
     * 设置服务对象为全局变量
     * @param \swoole_server $server
     */
    public function __construct(\swoole_websocket_server $server)
    {
        $GLOBALS['WebSocket'] = $server;
    }

    /**
     * 用户信息归档
     * @param \swoole_websocket_frame $frame
     * @throws \ReflectionException
     */
    public function range(\swoole_websocket_frame $frame)
    {
        try {
            if (Config::loadConfig('websocket')->get('server_connect.port') == ($server_port = Utils::getServerPort($GLOBALS['WebSocket'], $frame->fd))) {
                $data = json_decode($frame->data, true);
                $user_data = $data['data'] ?? [];
                if (isset($user_data['user_id']) && (is_string($user_data['user_id']) || is_int($user_data['user_id']))) {
                    $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                    //写入连接信息
                    $linkKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Link_Info');
                    $server_ip = Utils::getServerIp();
                    $str_ip = str_replace('.', '', $server_ip);
                    $link_key = $linkKey . '_' . $str_ip;
                    $link_info = [
                        'client_ip'    => Utils::getClientIp($GLOBALS['WebSocket'], $frame->fd),
                        'server_ip'    => $server_ip,
                        'server_port'  => $server_port,
                        'user_id'      => $user_data['user_id'],
                        'range_id'     => $user_data['range_id'] ?? '',
                        'connect_time' => Utils::getClientConnectTime($GLOBALS['WebSocket'], $frame->fd)
                    ];
                    $redis->hSet($link_key, $frame->fd, json_encode($link_info));
                    //写入用户信息
                    if (isset($user_data['range_id']) && (is_string($user_data['range_id']) || is_int($user_data['range_id']))) {
                        $userKey = Config::loadConfig('redis')->get('redis_key.WebSocket.User_Info');
                        $user_key = $userKey . '_' . $user_data['range_id'] . '_' . $str_ip;
                        $user_info = [
                            'fd'           => $frame->fd,
                            'user_id'      => $user_data['user_id'],
                            'range_id'     => $user_data['range_id'],
                            'client_ip'    => $link_info['client_ip'],
                            'server_ip'    => $link_info['server_ip'],
                            'connect_time' => $link_info['connect_time']
                        ];
                        $redis->hSet($user_key, $user_data['user_id'], json_encode($user_info));
                    }
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 发送在线人数
     * @param \swoole_websocket_frame $frame
     * @throws \ReflectionException
     */
    public function line(\swoole_websocket_frame $frame)
    {
        try {
            $data = json_decode($frame->data, true);
            $user_data = $data['data'] ?? [];
            if (isset($user_data['range_id']) && $user_data['range_id']) {
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $userKey = Config::loadConfig('redis')->get('redis_key.WebSocket.User_Info');
                $serverKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Server_Ip');
                $server_ips = $redis->SMEMBERS($serverKey);
                $online = 0;
                foreach ($server_ips as $server_ip) {
                    $str_ip = str_replace('.', '', $server_ip);
                    $user_key = $userKey . '_' . $user_data['range_id'] . '_' . $str_ip;
                    $online = $online + $redis->hLen($user_key);
                }
                $GLOBALS['WebSocket']->push($frame->fd, json_encode(['status' => 1, 'data' => $online]));
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 客户端发送消息
     * @param \swoole_websocket_frame $frame
     * @throws \ReflectionException
     */
    public function send(\swoole_websocket_frame $frame)
    {
        try {
            $data = json_decode($frame->data, true);
            $user_data = $data['data'];
            if (is_array($data)) {
                $client = new \vSwoole\application\client\WebSocket([], []);
                $client->execute('push', $user_data);
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 服务端推送消息
     * @param \swoole_websocket_frame $frame
     * @return array
     * @throws \ReflectionException
     */
    public function push(\swoole_websocket_frame $frame)
    {
        try {
            if (Config::loadConfig('websocket')->get('server_connect.adminPort') == ($server_port = Utils::getServerPort($GLOBALS['WebSocket'], $frame->fd))) {
                $data = json_decode($frame->data, true);
                $user_data = $data['data'] ?? [];
                if (isset($user_data['range_id']) && (is_string($user_data['range_id']) || is_int($user_data['range_id']))) {
                    $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                    $userKey = Config::loadConfig('redis')->get('redis_key.WebSocket.User_Info');
                    $server_ip = Utils::getServerIp();
                    $str_ip = str_replace('.', '', $server_ip);
                    $user_key = $userKey . '_' . $user_data['range_id'] . '_' . $str_ip;
                    //推送指定用户
                    if (isset($user_data['user_id']) && (is_string($user_data['user_id']) || is_int($user_data['user_id']))) {
                        $user_info = $redis->hGet($user_key, $user_data['user_id']);
                        if (false !== $user_info) {
                            $user_info = json_decode($user_info, true);
                            if ($GLOBALS['WebSocket']->exist($user_info['fd'])) {
                                $GLOBALS['WebSocket']->push($user_info['fd'], json_encode(['type' => 'message', 'data' => $user_data['message'] ?? '']));
                            }
                        }
                        //推送所有用户
                    } else {
                        $user_list = $redis->hVals($user_key);
                        if (false !== $user_list) {
                            if (Config::loadConfig('websocket')->get('other_config.enable_process_push')) {
                                $process_user_list = array_chunk($user_list, Config::loadConfig('websocket')->get('other_config.process_push_num'), true);
                                for ($process_num = 0; $process_num < count($process_user_list); $process_num++) {
                                    Process::getInstance()->add(function ($process) use ($process_user_list, $process_num, $server_ip, $user_data) {
                                        foreach ($process_user_list[$process_num] as $user_info) {
                                            $user_info = json_decode($user_info, true);
                                            if ($GLOBALS['WebSocket']->exist($user_info['fd'])) {
                                                $GLOBALS['WebSocket']->push($user_info['fd'], json_encode(['type' => 'message', 'data' => $user_data['message'] ?? '']));
                                            }
                                        }
                                    });
                                }
                            } else {
                                foreach ($user_list as $user_info) {
                                    $user_info = json_decode($user_info, true);
                                    if ($GLOBALS['WebSocket']->exist($user_info['fd'])) {
                                        $GLOBALS['WebSocket']->push($user_info['fd'], json_encode(['type' => 'message', 'data' => $user_data['message'] ?? '']));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

}