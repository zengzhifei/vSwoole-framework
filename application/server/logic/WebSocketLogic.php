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
use vSwoole\library\common\Redis;
use vSwoole\library\common\Utils;

class WebSocketLogic
{
    /**
     * 设置服务对象为全局变量
     * @param \swoole_server $server
     */
    public function __construct(\swoole_websocket_server $server)
    {
        $GLOBALS['webSocket'] = $server;
    }

    /**
     * 用户信息归档
     * @param \swoole_websocket_frame $frame
     * @throws \ReflectionException
     */
    public function range(\swoole_websocket_frame $frame)
    {
        try {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $linkKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Link_Info');
            $ip = str_replace('.', '', Utils::getServerIp());
            $link_key = $linkKey . '_' . $ip;
            $userKey = Config::loadConfig('redis')->get('redis_key.WebSocket.User_Info');
            $user_key = $userKey;
            $link_info = $redis->hGet($link_key, $frame->fd);
            if (false !== $link_info) {
                $link_info = json_decode($link_info, true);
                if ($link_info['server_port'] == Config::loadConfig('websocket')->get('ws_server_connect.port')) {
                    $data = json_decode($frame->data, true);
                    $data = $data['data'];
                    if (isset($data['user_id']) && $data['user_id']) {
                        $link_info['user_id'] = $data['user_id'];
                        $link_info['range_id'] = $data['range_id'];
                        $redis->hSet($link_key, $frame->fd, json_encode($link_info));
                        $user_info = [
                            'fd'           => $frame->fd,
                            'user_id'      => $data['user_id'],
                            'range_id'     => $data['range_id'],
                            'client_ip'    => $link_info['client_ip'],
                            'server_ip'    => $link_info['server_ip'],
                            'connect_time' => $link_info['connect_time']
                        ];
                        $user_key = isset($data['range_id']) ? $user_key . '_' . $data['range_id'] : $user_key;
                        $redis->hSet($user_key, $data['user_id'], json_encode($user_info));
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
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $userKey = Config::loadConfig('redis')->get('redis_key.WebSocket.User_Info');
            $data = json_decode($frame->data, true);
            $data = $data['data'];
            if (isset($data['range_id']) && $data['range_id']) {
                $user_key = $userKey . '_' . $data['range_id'];
                $online = $redis->hLen($user_key);
                $GLOBALS['webSocket']->push($frame->fd, json_encode(['status' => 1, 'data' => $online]));
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
            $data = $data['data'];
            if (is_array($data)) {
                $client = new \vSwoole\application\client\WebSocket([], []);
                $client->execute('push', $data);
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
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $linkKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Link_Info');
            $server_ip = Utils::getServerIp();
            $ip = str_replace('.', '', $server_ip);
            $link_key = $linkKey . '_' . $ip;
            $userKey = Config::loadConfig('redis')->get('redis_key.WebSocket.User_Info');
            $user_key = $userKey;
            $link_info = $redis->hGet($link_key, $frame->fd);
            if (false !== $link_info) {
                $link_info = json_decode($link_info, true);
                if ($link_info['server_port'] == Config::loadConfig('websocket')->get('ws_server_connect.adminPort')) {
                    $data = json_decode($frame->data, true);
                    $data = $data['data'];
                    if (isset($data['range_id']) && $data['range_id']) {
                        //推送指定用户
                        if (isset($data['user_id']) && $data['user_id']) {
                            $user_key = $user_key . '_' . $data['range_id'];
                            $user_info = $redis->hGet($user_key, $data['user_id']);
                            if (false !== $user_info) {
                                $user_info = json_decode($user_info, true);
                                $push_data = json_decode($frame->data, true);
                                if ($user_info['server_ip'] == $server_ip && $GLOBALS['webSocket']->exist($user_info['fd'])) {
                                    $res = $GLOBALS['webSocket']->push($user_info['fd'], json_encode(['type' => 'message', 'data' => $push_data['message']]));
                                }
                            }
                            //推送所有用户
                        } else {
                            $user_key = $user_key . '_' . $data['range_id'];
                            $user_list = $redis->hVals($user_key);
                            if (false !== $user_list) {
                                $push_data = json_decode($frame->data, true);
                                foreach ($user_list as $user_info) {
                                    $user_info = json_decode($user_info, true);
                                    if (isset($user_info['fd']) && $GLOBALS['webSocket']->exist($user_info['fd'])) {
                                        $GLOBALS['webSocket']->push($user_info['fd'], json_encode(['type' => 'message', 'data' => $push_data['message']]));
                                    }
                                }
                            }
                        }
                    }
                }
                return [[$this, 'clearClient'], [$frame->fd]];
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 删除管理客户端信息
     * @param int $fd
     * @throws \ReflectionException
     */
    public function clearClient(int $fd)
    {
        //删除管理客户端信息
        try {
            Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), false, function ($redis, $get_redis_key) use ($fd) {
                $linkKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Link_Info');
                $ip = str_replace('.', '', Utils::getServerIp());
                $link_key = $get_redis_key($linkKey . '_' . $ip);
                $redis->hGet($link_key, $fd, function ($redis, $result) use ($link_key, $fd) {
                    if (false !== $result) {
                        $link_info = json_decode($result, true);
                        if ($link_info['server_port'] == Config::loadConfig('websocket')->get('ws_server_connect.adminPort')) {
                            $redis->hDel($link_key, $fd, function ($redis, $result) {
                            });
                        }
                    }
                });
            });
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }
}