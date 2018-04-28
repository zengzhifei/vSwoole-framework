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
use vSwoole\library\common\Exception;
use vSwoole\library\common\Redis;
use vSwoole\library\common\Request;
use vSwoole\library\common\Response;

class WebSocket extends WebSocketClient
{
    /**
     * 连接服务器
     * @param array $connectOptions
     * @param array $configOptions
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        try {
            if (empty($connectOptions)) {
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $serverKey = Config::loadConfig('redis')->get('redis_key.WebSocket.Server_Ip');
                $server_ips = $redis->SMEMBERS($serverKey);
                if ($server_ips) {
                    foreach ($server_ips as $ip) {
                        parent::__construct(['host' => $ip], $configOptions);
                    }
                } else {
                    parent::__construct($connectOptions, $configOptions);
                }
            } else {
                parent::__construct($connectOptions, $configOptions);
            }
        } catch (\Exception $e) {
            Exception::reportError($e);
        }
    }

    /**
     * 推送消息
     */
    public function sendMessage()
    {
        $range_id = Request::getInstance()->param('range_id', null);
        $user_id = Request::getInstance()->param('user_id', null);
        $message = Request::getInstance()->param('message', null);

        if (null === $range_id) {
            Response::return(['status' => -1, 'msg' => 'param range_id is empty']);
        } else if ('' === $range_id) {
            Response::return(['status' => -1, 'msg' => 'param range_id is invalid']);
        }
        if (null === $message) {
            Response::return(['status' => -1, 'msg' => 'param message is empty']);
        } else if ('' === $message) {
            Response::return(['status' => -1, 'msg' => 'param message is invalid']);
        }

        $res = $this->execute('push', ['user_id' => $user_id, 'range_id' => $range_id, 'message' => $message]);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'send success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'send failed']);
        }
    }

}