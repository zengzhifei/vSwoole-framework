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
     * 处理连接
     * @param array $request
     * @return bool
     * @throws \Exception
     */
    public function open(array $request)
    {
        //存储连接信息到内存表
        $GLOBALS['link_table']->set($request['fd'], ['server_port' => $request['server']['server_port'], 'client_ip' => $request['header']['x-real-ip'] ?? '']);
        //验证客户端
        if ($request['server']['server_port'] == Config::loadConfig('websocket')->get('server_connect.port')) {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $configs = $redis->hGetAll(Config::loadConfig('redis')->get('redis_key.WebSocket.Config'));
            if ($configs) {
                //验证连接域名
                if (isset($request['header']['origin']) && $request['header']['origin'] && isset($configs['ENABLE-CHECK-HOST']) && strtolower($configs['ENABLE-CHECK-HOST']) == 'true' && isset($configs['CHECK-HOST']) && $configs['CHECK-HOST']) {
                    if ($configs['CHECK-HOST'] !== $request['header']['origin']) {
                        $GLOBALS['WebSocket']->close($request['fd']);
                        return false;
                    }
                }
                //验证连接IP
                if (isset($request['header']['x-real-ip']) && $request['header']['x-real-ip'] && isset($configs['ENABLE-CHECK-IP']) && strtolower($configs['ENABLE-CHECK-IP']) == 'true' && isset($configs['CHECK-IP']) && $configs['CHECK-IP']) {
                    if (count($ips = explode(',', trim($configs['CHECK-IP'], ',')))) {
                        if (in_array($request['header']['x-real-ip'], $ips)) {
                            $GLOBALS['WebSocket']->close($request['fd']);
                            return false;
                        }
                    }
                }
            }
        }
    }

    /**
     * 处理消息
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    public function message(\swoole_websocket_frame $frame)
    {
        //根据指令接口投递异步任务
        if ($frame->finish) {
            if (($data = json_decode($frame->data, true)) && is_array($data) && isset($data['cmd'])) {
                //管理客户端指令接口
                if (($server_port = $GLOBALS['link_table']->get($frame->fd, 'server_port')) && (Config::loadConfig('websocket')->get('server_connect.adminPort') == $server_port)) {
                    switch (strtolower($data['cmd'])) {
                        case 'ping':
                            break;
                        case 'push':
                            $this->push($frame);
                            break;
                        case 'close':
                            $GLOBALS['WebSocket']->exist($data['data']['fd']) && $GLOBALS['WebSocket']->close($data['data']['fd']);
                            break;
                        case 'reload':
                            $GLOBALS['WebSocket']->reload();
                            break;
                        case 'shutdown':
                            $GLOBALS['WebSocket']->shutdown();
                            break;
                        case 'test':
                            break;
                    }
                } else {
                    //用户客户端指令接口
                    switch (strtolower($data['cmd'])) {
                        case 'ping':
                            break;
                        case 'range':
                            $this->range($frame);
                            break;
                        case 'online':
                            $this->line($frame);
                            break;
                        case 'send':
                            $this->send($frame);
                            break;
                    }
                }
            }
        }
    }

    /**
     * 清理客户端信息
     * @param $fd
     * @throws \Exception
     */
    public function close($fd)
    {
        $link_port = $GLOBALS['link_table']->get($fd, 'server_port');
        $GLOBALS['link_table']->del($fd);
        $server_port = Config::loadConfig('websocket')->get('server_connect.port');
        if ($link_port && $server_port == $link_port) {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $linkKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Link_Info');
            $str_ip = str_replace('.', '', Utils::getServerIp());
            $link_key = $linkKey . '_' . $str_ip;
            $link_info = $redis->hGet($link_key, $fd);
            if ($link_info && ($link_info = json_decode($link_info, true))) {
                $userKey = Config::loadConfig('redis')->get('redis_key.WebSocket.User_Info');
                $redis->hDel($link_key, $fd);
                if (isset($link_info['range_id']) && $link_info['range_id'] && isset($link_info['user_id']) && $link_info['user_id']) {
                    $redis->hDel($userKey . '_' . $link_info['range_id'] . '_' . $str_ip, $link_info['user_id']);
                    $redis->hIncrby(Config::loadConfig('redis')->get('redis_key.WebSocket.Range_Info'), $link_info['range_id'], -1);
                }
            }
        }
    }

    /**
     * 用户信息归档
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    protected function range(\swoole_websocket_frame $frame)
    {
        if (($server_port = $GLOBALS['link_table']->get($frame->fd, 'server_port')) && (Config::loadConfig('websocket')->get('server_connect.port') == $server_port)) {
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
                    'client_ip'    => $GLOBALS['link_table']->get($frame->fd, 'client_ip'),
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
                    //记录分类信息
                    $redis->hIncrby(Config::loadConfig('redis')->get('redis_key.WebSocket.Range_Info'), $user_data['range_id'], 1);
                }
            }
        }
    }

    /**
     * 发送在线人数
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    protected function line(\swoole_websocket_frame $frame)
    {
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
    }

    /**
     * 客户端发送消息
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    protected function send(\swoole_websocket_frame $frame)
    {
        $data = json_decode($frame->data, true);
        $user_data = $data['data'];
        if (is_array($data)) {
            $client = new \vSwoole\application\client\WebSocket([], []);
            $client->execute('push', $user_data);
        }
    }

    /**
     * 服务端推送消息
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    protected function push(\swoole_websocket_frame $frame)
    {
        if (($server_port = $GLOBALS['link_table']->get($frame->fd, 'server_port')) && (Config::loadConfig('websocket')->get('server_connect.adminPort') == $server_port)) {
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
    }

}