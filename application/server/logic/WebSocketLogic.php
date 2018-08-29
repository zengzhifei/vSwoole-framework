<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\application\server\logic;


use vSwoole\library\client\WebSocketClient;
use vSwoole\library\common\Config;
use vSwoole\library\common\cache\Redis;
use vSwoole\library\common\Process;
use vSwoole\library\common\Timer;

class WebSocketLogic
{
    /**
     * 初始化
     * WebSocketLogic constructor.
     * @param $server
     */
    public function __construct($server)
    {
        //写入服务对象到全局变量
        $GLOBALS['server'] = $server;
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
        $GLOBALS['link_table']->set($request['fd'], [
            'fd'        => $request['fd'],
            'link_ip'   => $request['header']['x-real-ip'] ?? '',
            'link_port' => $request['server']['server_port'],
            'link_time' => time()
        ]);
        //验证客户端
        if ($request['server']['server_port'] == Config::loadConfig('websocket')->get('server_connect.port')) {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $configs = $redis->hGetAll(Config::loadConfig('redis')->get('redis_key.WebSocket.Config'));
            if (!$configs) {
                return false;
            }
            //验证连接域名
            if (isset($request['header']['origin']) && $request['header']['origin'] && isset($configs['ENABLE-CHECK-HOST']) && strtolower($configs['ENABLE-CHECK-HOST']) == 'true' && isset($configs['CHECK-HOST']) && $configs['CHECK-HOST']) {
                if ($configs['CHECK-HOST'] !== $request['header']['origin']) {
                    $GLOBALS['server']->close($request['fd']);
                    return false;
                }
            }
            //验证连接IP
            if (isset($request['header']['x-real-ip']) && $request['header']['x-real-ip'] && isset($configs['ENABLE-CHECK-IP']) && strtolower($configs['ENABLE-CHECK-IP']) == 'true' && isset($configs['CHECK-IP']) && $configs['CHECK-IP']) {
                if (count($ips = explode(',', trim($configs['CHECK-IP'], ',')))) {
                    if (in_array($request['header']['x-real-ip'], $ips)) {
                        $GLOBALS['server']->close($request['fd']);
                        return false;
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
                //管理端指令接口
                if (($link_port = $GLOBALS['link_table']->get($frame->fd, 'link_port')) && (Config::loadConfig('websocket')->get('server_connect.adminPort') == $link_port)) {
                    switch (strtolower($data['cmd'])) {
                        case 'line':
                            $this->line($frame);
                            break;
                        case 'ranges':
                            $this->getRanges($frame);
                            break;
                        case 'users':
                            $this->getUsers($frame);
                            break;
                        case 'push':
                            $this->push($frame);
                            $GLOBALS['server']->push($frame->fd, 'pong');
                            break;
                        case 'close':
                            $this->closeFd($data['data'] ?? []);
                            $GLOBALS['server']->push($frame->fd, 'pong');
                            break;
                        case 'reload':
                            $GLOBALS['server']->reload();
                            $GLOBALS['server']->push($frame->fd, 'pong');
                            break;
                        case 'shutdown':
                            $GLOBALS['server']->shutdown();
                            $GLOBALS['server']->push($frame->fd, 'pong');
                            break;
                    }
                } else {
                    //用户端指令接口
                    switch (strtolower($data['cmd'])) {
                        case 'ping':
                            break;
                        case 'range':
                            $this->range($frame);
                            break;
                        case 'online':
                            $this->online($frame);
                            break;
                        case 'message':
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
        $user_info = $GLOBALS['link_table']->get($fd);
        $GLOBALS['link_table']->del($fd);
        if ($user_info['range_id']) {
            $GLOBALS['range_table']->decr($user_info['range_id'], 'link_count', 1);
            if ($user_info['user_id']) {
                $GLOBALS['user_table']->del($user_info['range_id'] . '_' . $user_info['user_id']);
            }
        }
    }

    /**
     * 获取客户端全局对象
     * @throws \Exception
     */
    protected function getClient()
    {
        if (!isset($GLOBALS['client']) || empty($GLOBALS['client'])) {
            $GLOBALS['client'] = new WebSocketClient();
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $server_ips = $redis->SMEMBERS(Config::loadConfig('redis')->get('redis_key.WebSocket.Server_Ip'));
            if ($server_ips) {
                $connect_status = false;
                foreach ($server_ips as $ip) {
                    $connect_status = $GLOBALS['client']->connect(['host' => $ip], []) == false ? $connect_status || false : $connect_status || true;
                }
            } else {
                $connect_status = $GLOBALS['client']->connect([], []);
            }
            if ($connect_status == false) {
                unset($GLOBALS['client']);
            } else {
                Timer::tick(5000, function ($timer_id) {
                    $GLOBALS['client']->ping();
                });
            }
        } else {
            $connect_status = true;
        }

        return $connect_status;
    }

    /**
     * 用户信息归档
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    protected function range(\swoole_websocket_frame $frame)
    {
        if (($link_port = $GLOBALS['link_table']->get($frame->fd, 'link_port')) && (Config::loadConfig('websocket')->get('server_connect.port') == $link_port)) {
            $data = json_decode($frame->data, true);
            $user_data = $data['data'] ?? [];
            if (isset($user_data['range_id']) && (is_string($user_data['range_id']) || is_int($user_data['range_id'])) && isset($user_data['user_id']) && (is_string($user_data['user_id']) || is_int($user_data['user_id']))) {
                $GLOBALS['link_table']->set($frame->fd, ['user_id' => $user_data['user_id'], 'range_id' => $user_data['range_id']]);
                $GLOBALS['user_table']->set($user_data['range_id'] . '_' . $user_data['user_id'], ['fd' => $frame->fd]);
                $GLOBALS['range_table']->incr($user_data['range_id'], 'link_count', 1);
            }
        }
    }

    /**
     * 服务端推送消息
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    protected function push(\swoole_websocket_frame $frame)
    {
        if (($link_port = $GLOBALS['link_table']->get($frame->fd, 'link_port')) && (Config::loadConfig('websocket')->get('server_connect.adminPort') == $link_port)) {
            $data = json_decode($frame->data, true);
            $user_data = $data['data'] ?? [];
            if (isset($user_data['range_id']) && (is_string($user_data['range_id']) || is_int($user_data['range_id']))) {
                if (isset($user_data['user_id']) && (is_string($user_data['user_id']) || is_int($user_data['user_id']))) {
                    $user_fd = $GLOBALS['user_table']->get($user_data['range_id'] . '_' . $user_data['user_id'], 'fd');
                    if ($user_fd && $GLOBALS['server']->exist($user_fd)) {
                        $GLOBALS['server']->push($user_fd, json_encode(['type' => 'message', 'data' => $user_data['message'] ?? '']));
                    }
                } else if ($user_list = $GLOBALS['link_table']->getAll()) {
                    if (Config::loadConfig('websocket')->get('other_config.enable_process_push')) {
                        $process_user_list = array_chunk($user_list, Config::loadConfig('websocket')->get('other_config.process_push_num') ?? 1, true);
                        for ($process_num = 0; $process_num < count($process_user_list); $process_num++) {
                            Process::getInstance()->add(function ($process) use ($process_user_list, $process_num, $user_data) {
                                foreach ($process_user_list[$process_num] as $link_info) {
                                    if ($link_info['range_id'] && $link_info['range_id'] == $user_data['range_id'] && $GLOBALS['server']->exist($link_info['fd'])) {
                                        $GLOBALS['server']->push($link_info['fd'], json_encode(['type' => 'message', 'data' => $user_data['message'] ?? '']));
                                    }
                                }
                            });
                        }
                        Process::signalProcess(true);
                    } else {
                        foreach ($user_list as $link_info) {
                            if ($link_info['range_id'] && $link_info['range_id'] == $user_data['range_id'] && $GLOBALS['server']->exist($link_info['fd'])) {
                                $GLOBALS['server']->push($link_info['fd'], json_encode(['type' => 'message', 'data' => $user_data['message'] ?? '']));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 发送本机在线人数
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    protected function line(\swoole_websocket_frame $frame)
    {
        $data = json_decode($frame->data, true);
        $user_data = $data['data'] ?? [];
        if (isset($user_data['range_id']) && $user_data['range_id']) {
            $online = $GLOBALS['range_table']->get($user_data['range_id'], 'link_count');
            $GLOBALS['server']->push($frame->fd, $online);
        } else {
            $online = $GLOBALS['link_table']->count();
            $GLOBALS['server']->push($frame->fd, $online);
        }
    }

    /**
     * 发送总体在线人数
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    protected function online(\swoole_websocket_frame $frame)
    {
        if ($this->getClient()) {
            $data = json_decode($frame->data, true);
            $user_data = $data['data'] ?? [];
            if (is_array($data)) {
                $online = $GLOBALS['client']->execute('line', $user_data);
                if ($online && is_array($online)) {
                    $GLOBALS['server']->push($frame->fd, json_encode(['type' => 'online', 'data' => array_sum($online)]));
                }
            }
        }
    }

    /**
     * 客户端发送消息
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    protected function send(\swoole_websocket_frame $frame)
    {
        if ($this->getClient()) {
            $data = json_decode($frame->data, true);
            $user_data = $data['data'] ?? [];
            if (is_array($data)) {
                $GLOBALS['client']->execute('push', $user_data);
            }
        }
    }

    /**
     * 关闭客户端连接
     * @param array $data
     */
    protected function closeFd(array $data)
    {
        if (isset($data['range_id']) && isset($data['user_id'])) {
            if ($fd = $GLOBALS['user_table']->get($data['range_id'] . '_' . $data['user_id'], 'fd')) {
                $GLOBALS['server']->exist($fd) && $GLOBALS['server']->close($fd);
            }
        }
    }

    /**
     * 获取归档
     * @param \swoole_websocket_frame $frame
     */
    protected function getRanges(\swoole_websocket_frame $frame)
    {
        $ranges = $GLOBALS['range_table']->getAll();
        if ($ranges) {
            foreach ($ranges as $range => $range_info) {
                $_ranges[$range] = $range_info['link_count'];
            }
        }
        $GLOBALS['server']->push($frame->fd, json_encode($_ranges ?? []));
    }

    /**
     * 获取在线用户
     * @param \swoole_websocket_frame $frame
     */
    protected function getUsers(\swoole_websocket_frame $frame)
    {
        $data = json_decode($frame->data, true);
        $user_data = $data['data'] ?? [];
        $range_users = [];
        $all_users = [];
        if ($users = $GLOBALS['user_table']->getAll()) {
            foreach ($users as $key => $fd) {
                $index = strpos($key, '_');
                $range_id = substr($key, 0, $index);
                $user_id = substr($key, $index + 1);
                $all_users[] = $user_id;
                if ($range_id == $user_data['range_id']) {
                    $range_users[] = $user_id;
                }
            }
        }
        if (isset($user_data['range_id']) && $user_data['range_id']) {
            $GLOBALS['server']->push($frame->fd, json_encode($range_users));
        } else {
            $GLOBALS['server']->push($frame->fd, json_encode($all_users));
        }
    }
}