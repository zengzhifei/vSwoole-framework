<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace application\server;


use library\common\Config;
use library\common\Exception;
use library\common\Redis;
use library\common\Utils;
use library\server\WebSocketServer;

class WebSocket extends WebSocketServer
{
    /**
     * 服务器主进程启动回调函数
     * @param \swoole_websocket_server $server
     */
    public function onStart(\swoole_websocket_server $server)
    {
        parent::onStart($server); // TODO: Change the autogenerated stub

        //异步写入服务器IP到缓存
        try {
            Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), false, function ($redis, $get_redis_key) {
                $ipKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Server_Ip');
                $redis->sAdd($get_redis_key($ipKey), Utils::getServerIp(), function ($redis, $result) {
                    try {
                        if (false === $result) {
                            throw new \Exception($redis->errMsg, $redis->errCode);
                        }
                    } catch (\Exception $e) {
                        Exception::reportException($e);
                    }
                });
            });
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 客户端连接服务器回调函数
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     */
    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        parent::onOpen($server, $request); // TODO: Change the autogenerated stub

        //异步写入客户端信息到缓存
        try {
            Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), false, function ($redis, $get_redis_key) use ($request) {
                $linkKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Link_Info');
                $ip = Utils::getServerIp();
                $format_ip = str_replace('.', '', $ip);
                $link_key = $get_redis_key($linkKey . '_' . $format_ip);
                $clientInfo = [
                    'client_ip'    => isset($request->header['x-real-ip']) ? $request->header['x-real-ip'] : '',
                    'server_ip'    => $ip,
                    'server_port'  => $request->server['server_port'],
                    'connect_time' => time()
                ];
                $redis->hSet($link_key, $request->fd, json_encode($clientInfo), function ($redis, $result) {
                    try {
                        if (false === $result) {
                            throw new \Exception($redis->errMsg, $redis->errCode);
                        }
                    } catch (\Exception $e) {
                        Exception::reportException($e);
                    }
                });
            });
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 重写服务器接收客户端消息回调函数
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     */
    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        parent::onMessage($server, $frame); // TODO: Change the autogenerated stub

        //异步写入用户信息到缓存
        if ($frame->finish) {
            $data = json_decode($frame->data, true);
            if (is_array($data) && isset($data['cmd'])) {
                switch (strtolower($data['cmd'])) {
                    case 'range':
                        $server->task(['method' => 'range', 'arguments' => [$frame]]);
                        break;
                    case 'ping':
                        break;
                    case 'online':
                        $server->task(['method' => 'line', 'arguments' => [$frame]]);
                        break;
                    case 'send':
                        $server->task(['method' => 'send', 'arguments' => [$frame]]);
                        break;
                    case 'push':
                        $server->task(['method' => 'push', 'arguments' => [$frame]]);
                        break;
                }
            }
        }
    }

    /**
     * 异步任务回调函数
     * @param \swoole_server $server
     * @param $task_id
     * @param $src_worker_id
     * @param $data
     */
    public function onTask(\swoole_server $server, $task_id, $src_worker_id, $data)
    {
        parent::onTask($server, $task_id, $src_worker_id, $data); // TODO: Change the autogenerated stub

        //执行异步任务
        try {
            if (isset($data['method']) && method_exists($this, $data['method'])) {
                $method = $data['method'];
                $arguments = isset($data['arguments']) ? $data['arguments'] : [];
                $res = $this->$method(...$arguments);
                if (null !== $res) {
                    $server->finish($res);
                }
            } else {
                throw new \Exception('Argument method is not set or not exists');
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 异步任务执行完成回调函数
     * @param \swoole_server $server
     * @param $task_id
     * @param $data
     */
    public function onFinish(\swoole_server $server, $task_id, $data)
    {
        parent::onFinish($server, $task_id, $data); // TODO: Change the autogenerated stub

        //todo 执行异步任务完成回调函数
    }

    /**
     * 用户信息归档
     * @param \swoole_websocket_frame $frame
     */
    private function range(\swoole_websocket_frame $frame)
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
                    try {
                        if (isset($data['user_id']) && $data['user_id']) {
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
                    } catch (\Exception $e) {
                        Exception::reportException($e);
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
     */
    private function line(\swoole_websocket_frame $frame)
    {
        try {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $userKey = Config::loadConfig('redis')->get('redis_key.WebSocket.User_Info');
            $data = json_decode($frame->data, true);
            if (isset($data['range_id']) && $data['range_id']) {
                $user_key = $userKey . '_' . $data['range_id'];
                $online = $redis->hLen($user_key);
                $this->swoole->push($frame->fd, json_encode(['status' => 1, 'data' => $online]));
            } else {
                throw new \Exception('Argument range_id is invalid');
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 客户端发送消息
     * @param \swoole_websocket_frame $frame
     */
    private function send(\swoole_websocket_frame $frame)
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
                    if (isset($data['range_id']) && $data['range_id']) {
                        //推送指定用户
                        if (isset($data['user_id']) && $data['user_id']) {
                            $user_key = $user_key . '_' . $data['range_id'];
                            $user_info = $redis->hGet($user_key, $data['user_id']);
                            if (false !== $user_info) {
                                $user_info = json_decode($user_info, true);
                                $push_data = json_decode($frame->data, true);
                                if ($this->swoole->exist($user_info['fd'])) {
                                    $res = $this->swoole->push($user_info['fd'], json_encode(['type' => 'message', 'data' => $push_data['message']]));
                                    $this->swoole->push($frame->fd, json_encode(['status' => $res ? 1 : 0]));
                                }
                            }
                            //推送所有用户
                        } else {
                            $user_info = $redis->hKeys($link_key);
                            if (false !== $user_info) {
                                $push_data = json_decode($frame->data, true);
                                $fail_push = 0;
                                foreach ($user_info as $fd) {
                                    if ($this->swoole->exist($fd)) {
                                        $res = $this->swoole->push($fd, json_encode(['type' => 'message', 'data' => $push_data['message']]));
                                        !$res && $fail_push++;
                                    }
                                }
                                $this->swoole->push($frame->fd, json_encode(['status' => 1, 'data' => ['total_push' => count($push_data), 'fail_push' => $fail_push]]));
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 服务端推送消息
     * @param \swoole_websocket_frame $frame
     */
    private function push(\swoole_websocket_frame $frame)
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
                if ($link_info['server_port'] == Config::loadConfig('websocket')->get('ws_server_connect.adminPort')) {
                    $data = json_decode($frame->data, true);
                    if (isset($data['range_id']) && $data['range_id']) {
                        //推送指定用户
                        if (isset($data['user_id']) && $data['user_id']) {
                            $user_key = $user_key . '_' . $data['range_id'];
                            $user_info = $redis->hGet($user_key, $data['user_id']);
                            if (false !== $user_info) {
                                $user_info = json_decode($user_info, true);
                                $push_data = json_decode($frame->data, true);
                                if ($this->swoole->exist($user_info['fd'])) {
                                    $res = $this->swoole->push($user_info['fd'], json_encode(['type' => 'message', 'data' => $push_data['message']]));
                                    $this->swoole->push($frame->fd, $res ? 1 : 0);
                                }
                            }
                            //推送所有用户
                        } else {
                            $user_info = $redis->hKeys($link_key);
                            if (false !== $user_info) {
                                $push_data = json_decode($frame->data, true);
                                $fail_push = 0;
                                foreach ($user_info as $fd) {
                                    if ($this->swoole->exist($fd)) {
                                        $res = $this->swoole->push($fd, json_encode(['type' => 'message', 'data' => $push_data['message']]));
                                        !$res && $fail_push++;
                                    }
                                }
                                $this->swoole->push($frame->fd, json_encode(['total_push' => count($push_data), 'fail_push' => $fail_push]));
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