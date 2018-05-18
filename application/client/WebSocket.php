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
use vSwoole\library\common\Utils;

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
                    $connect_status = parent::__construct(['host' => $ip], $configOptions) == false ? $connect_status || false : $connect_status || true;
                }
            } else {
                $connect_status = parent::__construct($connectOptions, $configOptions);
            }
        } else {
            $connect_status = parent::__construct($connectOptions, $configOptions);
        }
        if (false == $connect_status) {
            Response::return(['status' => 504, 'msg' => 'Server Connect Gateway Timeout']);
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
            Response::return(['status' => -1, 'msg' => 'Arguments range_id is empty']);
        } else if ('' === $range_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments range_id is invalid']);
        }
        if (null === $message) {
            Response::return(['status' => -1, 'msg' => 'Arguments message is empty']);
        } else if ('' === $message) {
            Response::return(['status' => -1, 'msg' => 'Arguments message is invalid']);
        }

        $res = $this->execute('push', ['user_id' => $user_id, 'range_id' => $range_id, 'message' => $message]);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'send success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'send failed']);
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
            Response::return(['status' => 1, 'msg' => 'reload success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'reload failed']);
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
            Response::return(['status' => 1, 'msg' => 'shutdown success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'shutdown failed']);
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
            Response::return(['status' => -1, 'msg' => 'Arguments range_id is empty']);
        } else if ('' === $range_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments range_id is invalid']);
        }
        if (null === $user_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments user_id is empty']);
        } else if ('' === $user_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments user_id is invalid']);
        }

        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $serverKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Server_Ip');
        $user_key = Config::loadConfig('redis')->get('redis_key.WebSocket.User_Info');
        $server_ips = $redis->SMEMBERS($serverKey);
        foreach ($server_ips as $ip) {
            $str_ip = str_replace('.', '', $ip);
            $user = $redis->hGet($user_key . '_' . $range_id . '_' . $str_ip, $user_id);
            if ($user && ($user = json_decode($user, true))) {
                $res = $this->execute('close', ['fd' => $user['fd']], $ip);
                $res && Response::return(['status' => 1, 'msg' => 'close success']);
            }
        }
        Response::return(['status' => 0, 'msg' => 'close failed']);
    }

    /**
     * 获取服务器列表
     * @throws \Exception
     */
    public function getServerList()
    {
        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $serverKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Server_Ip');
        $server_ips = $redis->SMEMBERS($serverKey);
        $server_port = Config::loadConfig('websocket')->get('server_connect.adminPort');
        $ips = [];
        if ($server_ips) {
            foreach ($server_ips as $ip) {
                if (Utils::getServerStatus($ip, $server_port)) {
                    $ips[] = $ip;
                }
            }
        }
        if (count($ips)) {
            Response::return(['status' => 1, 'msg' => 'get success', 'data' => $ips]);
        } else {
            Response::return(['status' => 0, 'msg' => 'get failed']);
        }
    }

    /**
     * 获取指定服务器在线人数
     * @throws \Exception
     */
    public function getServerOnline()
    {
        $server_ip = Request::getInstance()->param('server_ip', null);

        if (null === $server_ip) {
            Response::return(['status' => -1, 'msg' => 'Arguments server_ip is empty']);
        } else if ('' === $server_ip) {
            Response::return(['status' => -1, 'msg' => 'Arguments server_ip is invalid']);
        }

        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $link_key = Config::loadConfig('redis')->get('redis_key.WebSocket.Link_Info');
        $server_ips = is_array($server_ip) ? $server_ip : [$server_ip];
        $online_data = [];
        foreach ($server_ips as $server_ip) {
            $linkKey = $link_key . '_' . str_replace('.', '', $server_ip);
            $online_data[$server_ip] = $redis->hLen($linkKey);
        }
        Response::return(['status' => 1, 'msg' => 'get success', 'data' => $online_data]);
    }

    /**
     * 获取分类
     * @throws \Exception
     */
    public function getRanges()
    {
        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $ranges = $redis->hGetAll(Config::loadConfig('redis')->get('redis_key.WebSocket.Range_Info'));
        foreach ($ranges as $range => $count) {
            if ($count > 0) {
                $_ranges[] = $range;
            }
        }
        if (isset($_ranges)) {
            Response::return(['status' => 1, 'msg' => 'get success', 'data' => $_ranges]);
        } else {
            Response::return(['status' => 0, 'msg' => 'get fail']);
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
            Response::return(['status' => -1, 'msg' => 'Arguments range_id is empty']);
        } else if ('' === $range_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments range_id is invalid']);
        }

        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $online = $redis->hGet(Config::loadConfig('redis')->get('redis_key.WebSocket.Range_Info'), $range_id);
        if ($online) {
            Response::return(['status' => 1, 'msg' => 'get success', 'data' => $online]);
        } else {
            Response::return(['status' => 0, 'msg' => 'get fail']);
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
            Response::return(['status' => -1, 'msg' => 'Arguments config is invalid']);
        }

        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $res = $redis->hMSet(Config::loadConfig('redis')->get('redis_key.WebSocket.Config'), $configs);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'config success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'config fail']);
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
            Response::return(['status' => 1, 'msg' => 'config success', 'data' => $res]);
        } else {
            Response::return(['status' => 0, 'msg' => 'config fail']);
        }
    }
}