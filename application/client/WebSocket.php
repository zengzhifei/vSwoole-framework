<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\application\client;


use vSwoole\library\client\WebSocketClient;
use vSwoole\library\common\Config;
use vSwoole\library\common\cache\Redis;
use vSwoole\library\common\Request;
use vSwoole\library\common\Response;

class WebSocket extends WebSocketClient
{
    /**
     * 连接服务器
     * WebSocket constructor.
     * @param array $connectOptions
     * @param array $configOptions
     * @throws \Exception
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        if (empty($connectOptions)) {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $serverKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Server_Ip');
            $server_ips = $redis->SMEMBERS($serverKey);
            if ($server_ips) {
                $connect_status = false;
                foreach ($server_ips as $ip) {
                    $connect_status = parent::connect(['host' => $ip], $configOptions) == false ? $connect_status || false : $connect_status || true;
                }
            } else {
                $connect_status = parent::connect($connectOptions, $configOptions);
            }
        } else {
            $connect_status = parent::connect($connectOptions, $configOptions);
        }
        if (false == $connect_status) {
            Response::return (['status' => 504, 'msg' => 'Server Connect Gateway Timeout']);
        }
    }

    /**
     * 推送消息
     */
    public function send()
    {
        $range_id = Request::getInstance()->param('range_id', null);
        $user_id = Request::getInstance()->param('user_id', null);
        $message = Request::getInstance()->param('message', null);

        if (null === $range_id) {
            Response::return (['status' => -1, 'msg' => 'Arguments range_id is empty']);
        } else if ('' === $range_id) {
            Response::return (['status' => -1, 'msg' => 'Arguments range_id is invalid']);
        }
        if (null === $message) {
            Response::return (['status' => -1, 'msg' => 'Arguments message is empty']);
        } else if ('' === $message) {
            Response::return (['status' => -1, 'msg' => 'Arguments message is invalid']);
        }

        $res = $this->execute('push', ['user_id' => $user_id, 'range_id' => $range_id, 'message' => $message]);
        if ($res) {
            Response::return (['status' => 1, 'msg' => 'send success']);
        } else {
            Response::return (['status' => 0, 'msg' => 'send failed']);
        }
    }

    /**
     * 重启服务
     */
    public function reload()
    {
        $server_ip = Request::getInstance()->param('server_ip', null);
        $res = $this->execute('reload', [], $server_ip);
        if ($res) {
            Response::return (['status' => 1, 'msg' => 'reload success']);
        } else {
            Response::return (['status' => 0, 'msg' => 'reload failed']);
        }
    }

    /**
     * 关闭服务
     */
    public function shutdown()
    {
        $server_ip = Request::getInstance()->param('server_ip', null);
        $res = $this->execute('shutdown', [], $server_ip);
        if ($res) {
            Response::return (['status' => 1, 'msg' => 'shutdown success']);
        } else {
            Response::return (['status' => 0, 'msg' => 'shutdown failed']);
        }
    }

    /**
     * 关闭指定用户连接
     * @throws \Exception
     */
    public function close()
    {
        $range_id = Request::getInstance()->param('range_id', null);
        $user_id = Request::getInstance()->param('user_id', null);

        if (null === $range_id) {
            Response::return (['status' => -1, 'msg' => 'Arguments range_id is empty']);
        } else if ('' === $range_id) {
            Response::return (['status' => -1, 'msg' => 'Arguments range_id is invalid']);
        }
        if (null === $user_id) {
            Response::return (['status' => -1, 'msg' => 'Arguments user_id is empty']);
        } else if ('' === $user_id) {
            Response::return (['status' => -1, 'msg' => 'Arguments user_id is invalid']);
        }

        $res = $this->execute('close', ['range_id' => $range_id, 'user_id' => $user_id]);
        if ($res) {
            Response::return (['status' => 1, 'msg' => 'close success']);
        } else {
            Response::return (['status' => 0, 'msg' => 'close failed']);
        }
    }

    /**
     * 获取服务器列表
     * @throws \Exception
     */
    public function getServerList()
    {
        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $server_ips = $redis->SMEMBERS(Config::loadConfig('redis')->get('redis_key.WebSocket.Server_Ip'));
        $connect_servers = $this->getConnectIp();
        if ($server_ips) {
            foreach ($server_ips as $server_ip) {
                $server_list[] = [
                    'server_ip'     => $server_ip,
                    'server_status' => in_array($server_ip, $connect_servers) ? 1 : 0
                ];
            }
        }
        if (isset($server_list)) {
            Response::return (['status' => 1, 'msg' => 'get success', 'data' => $server_list]);
        } else {
            Response::return (['status' => 0, 'msg' => 'get failed']);
        }
    }

    /**
     * 清理异常服务器
     * @throws \Exception
     */
    public function clearServers()
    {
        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $server_key = Config::loadConfig('redis')->get('redis_key.WebSocket.Server_Ip');
        $server_ips = $redis->SMEMBERS($server_key);
        $connect_servers = $this->getConnectIp();
        if ($server_ips) {
            foreach ($server_ips as $server_ip) {
                if (!in_array($server_ip, $connect_servers)) {
                    $redis->sRem($server_key, $server_ip);
                }
            }
        }
        Response::return (['status' => 1, 'msg' => 'clear success']);
    }

    /**
     * 获取指定服务器在线人数
     * @throws \Exception
     */
    public function getServerOnline()
    {
        $server_ip = Request::getInstance()->param('server_ip', null);

        if ($server_ip && is_string($server_ip)) {
            $online_list = $this->execute('line', [], $server_ip);
        } else {
            $online_list = $this->execute('line', []);
        }
        if ($online_list) {
            Response::return (['status' => 1, 'msg' => 'get success', 'data' => $online_list]);
        } else {
            Response::return (['status' => 0, 'msg' => 'get fail']);
        }
    }

    /**
     * 获取归档
     * @throws \Exception
     */
    public function getRanges()
    {
        $ranges = $this->execute('ranges', []);
        if ($ranges && is_array($ranges)) {
            foreach ($ranges as $range_list) {
                if ($range_list = json_decode($range_list, true)) {
                    foreach ($range_list as $range => $count) {
                        if ($count > 0 && !in_array($range, $_ranges ?? [])) $_ranges[] = $range;
                    }
                }
            }
        }
        if (isset($_ranges)) {
            Response::return (['status' => 1, 'msg' => 'get success', 'data' => $_ranges]);
        } else {
            Response::return (['status' => 0, 'msg' => 'get fail']);
        }
    }

    /**
     * 获取指定分类的在线人数总数
     * @throws \Exception
     */
    public function getRangeOnline()
    {
        $range_id = Request::getInstance()->param('range_id', null);

        if (null === $range_id) {
            Response::return (['status' => -1, 'msg' => 'Arguments range_id is empty']);
        } else if ('' === $range_id) {
            Response::return (['status' => -1, 'msg' => 'Arguments range_id is invalid']);
        }

        $online = $this->execute('line', ['range_id' => $range_id]);
        if ($online && is_array($online)) {
            Response::return (['status' => 1, 'msg' => 'get success', 'data' => array_sum($online)]);
        } else {
            Response::return (['status' => 0, 'msg' => 'get fail']);
        }
    }

    /**
     * 服务配置
     * @throws \Exception
     */
    public function config()
    {
        $configs = Request::getInstance()->param('config', []);

        if (!is_array($configs) && count($configs) < 1) {
            Response::return (['status' => -1, 'msg' => 'Arguments config is invalid']);
        }

        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $res = $redis->hMSet(Config::loadConfig('redis')->get('redis_key.WebSocket.Config'), $configs);
        if ($res) {
            Response::return (['status' => 1, 'msg' => 'config success']);
        } else {
            Response::return (['status' => 0, 'msg' => 'config fail']);
        }
    }

    /**
     * 获取服务配置
     * @throws \Exception
     */
    public function getConfigs()
    {
        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $res = $redis->hGetAll(Config::loadConfig('redis')->get('redis_key.WebSocket.Config'));
        if ($res) {
            Response::return (['status' => 1, 'msg' => 'config success', 'data' => $res]);
        } else {
            Response::return (['status' => 0, 'msg' => 'config fail']);
        }
    }
}