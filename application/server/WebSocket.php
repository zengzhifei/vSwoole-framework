<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\application\server;


use vSwoole\application\server\logic\WebSocketLogic;
use vSwoole\library\common\Command;
use vSwoole\library\common\Config;
use vSwoole\library\common\exception\Exception;
use vSwoole\library\common\Inotify;
use vSwoole\library\common\Process;
use vSwoole\library\common\cache\Redis;
use vSwoole\library\common\Task;
use vSwoole\library\common\Utils;
use vSwoole\library\server\WebSocketServer;

class WebSocket extends WebSocketServer
{
    /**
     * 启动服务器
     * @param array $connectOptions
     * @param array $configOptions
     * @throws \ReflectionException
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        parent::__construct($connectOptions, $configOptions);
    }

    /**
     * 主进程启动回调函数
     * @param \swoole_server $server
     * @throws \ReflectionException
     */
    public function onStart(\swoole_server $server)
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
     * 管理进程启动回调函数
     * @param \swoole_server $server
     */
    public function onManagerStart(\swoole_server $server)
    {
        parent::onManagerStart($server); // TODO: Change the autogenerated stub

        //DEBUG模式下，监听文件变化自动重启
        if (Config::loadConfig('config', true)->get('is_debug')) {
            $process = new Process();
            $process->add(function () use ($server) {
                Inotify::getInstance()->watch([VSWOOLE_CONFIG_PATH, VSWOOLE_APP_SERVER_PATH . 'logic/WebSocketLogic.php'], function () use ($server) {
                    Command::getInstance($server)->reload();
                });
            });
        }
    }

    /**
     * 工作进程启动回调函数
     * @param \swoole_server $server
     * @param int $worker_id
     */
    public function onWorkerStart(\swoole_server $server, int $worker_id)
    {
        parent::onWorkerStart($server, $worker_id); // TODO: Change the autogenerated stub

        //引入服务逻辑对象
        $this->logic = new WebSocketLogic($server);
    }

    /**
     * 客户端与WebSocket建立连接成功后回调函数
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     * @throws \ReflectionException
     */
    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        parent::onOpen($server, $request); // TODO: Change the autogenerated stub

        //异步写入客户端信息到缓存
        try {
            Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), false, function ($redis, $get_redis_key) use ($server, $request) {
                $linkKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Link_Info');
                $server_ip = Utils::getServerIp();
                $ip = str_replace('.', '', $server_ip);
                $link_key = $get_redis_key($linkKey . '_' . $ip);
                $clientInfo = [
                    'client_ip'    => isset($request->header['x-real-ip']) ? $request->header['x-real-ip'] : Utils::getClientIp($server, $request->fd),
                    'server_ip'    => $server_ip,
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
     * WebSocket服务端接收客户端消息回调函数
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     * @throws \ReflectionException
     */
    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        parent::onMessage($server, $frame); // TODO: Change the autogenerated stub

        //根据指令接口投递异步任务
        if ($frame->finish) {
            $data = json_decode($frame->data, true);
            if (is_array($data) && isset($data['cmd'])) {
                $client_info = $server->getClientInfo($frame->fd);
                $admin_port = Config::loadConfig('websocket')->get('server_connect.adminPort');
                //管理客户端指令接口
                if ($client_info && $admin_port == $client_info['server_port']) {
                    switch (strtolower($data['cmd'])) {
                        case 'ping':
                            break;
                        case 'online':
                            Task::task($server, [$this->logic, 'line'], [$frame]);
                            break;
                        case 'push':
                            Task::task($server, [$this->logic, 'push'], [$frame]);
                            break;
                        case 'reload':
                            Command::getInstance($server)->reload();
                            break;
                        case 'shutdown':
                            Command::getInstance($server)->shutdown();
                            break;
                        case 'test':
                            break;
                    }
                } else {
                    //用户客户端指令接口
                    switch (strtolower($data['cmd'])) {
                        case 'range':
                            Task::task($server, [$this->logic, 'range'], [$frame]);
                            break;
                        case 'ping':
                            break;
                        case 'online':
                            Task::task($server, [$this->logic, 'line'], [$frame]);
                            break;
                        case 'send':
                            Task::task($server, [$this->logic, 'send'], [$frame]);
                            break;
                    }
                }
            }
        }
    }

    /**
     * 异步任务执行回调函数
     * @param \swoole_server $server
     * @param int $task_id
     * @param int $src_worker_id
     * @param $data
     */
    public function onTask(\swoole_server $server, int $task_id, int $src_worker_id, $data)
    {
        parent::onTask($server, $task_id, $src_worker_id, $data); // TODO: Change the autogenerated stub

        //执行异步任务
        Task::execute($server, $data);
    }

    /**
     * 异步任务执行完成回调函数
     * @param \swoole_server $server
     * @param int $task_id
     * @param $data
     */
    public function onFinish(\swoole_server $server, int $task_id, $data)
    {
        parent::onFinish($server, $task_id, $data); // TODO: Change the autogenerated stub

        // 执行异步任务完成回调函数
        Task::finish($data);
    }

    /**
     * 客户端断开回调函数
     * @param \swoole_server $server
     * @param int $fd
     * @param int $reactor_id
     * @throws \ReflectionException
     */
    public function onClose(\swoole_server $server, int $fd, int $reactor_id)
    {
        parent::onClose($server, $fd, $reactor_id); // TODO: Change the autogenerated stub

        //删除客户端信息
        try {
            Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), false, function ($redis, $get_redis_key) use ($fd) {
                $linkKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Link_Info');
                $ip = str_replace('.', '', Utils::getServerIp());
                $link_key = $get_redis_key($linkKey . '_' . $ip);
                $userKey = Config::loadConfig('redis')->get('redis_key.WebSocket.User_Info');
                $user_key = $get_redis_key($userKey);
                $redis->hGet($link_key, $fd, function ($redis, $result) use ($link_key, $user_key, $fd) {
                    if (false !== $result) {
                        $link_info = json_decode($result, true);
                        if ($link_info['server_port'] != Config::loadConfig('websocket')->get('server_connect.adminPort')) {
                            if (isset($link_info['range_id']) && $link_info['range_id']) {
                                $redis->hDel($user_key . '_' . $link_info['range_id'], $link_info['user_id'], function ($redis, $result) {
                                });
                            }
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