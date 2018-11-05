<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\application\client;


use vSwoole\core\client\UdpClient;
use vSwoole\library\common\cache\Redis;
use vSwoole\library\common\Config;
use vSwoole\library\common\Response;

class Udp extends UdpClient
{
    /**
     * 连接服务器
     * Udp constructor.
     * @param array $connectOptions
     * @param array $configOptions
     * @throws \Exception
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        if (empty($connectOptions)) {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $serverKey = Config::loadConfig('redis')->get('redis_key.Udp.Server_Ip');
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
            Response::return(['status' => 504, 'msg' => 'Server Connect Gateway Timeout']);
        }
    }

    /**
     * 获取服务器列表
     * @throws \Exception
     */
    public function getServerList()
    {
        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $server_ips = $redis->SMEMBERS(Config::loadConfig('redis')->get('redis_key.Udp.Server_Ip'));
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
            Response::return(['status' => 1, 'msg' => 'get success', 'data' => $server_list]);
        } else {
            Response::return(['status' => 0, 'msg' => 'get failed']);
        }
    }

    /**
     * 清理异常服务器
     * @throws \Exception
     */
    public function clearServers()
    {
        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $server_key = Config::loadConfig('redis')->get('redis_key.Udp.Server_Ip');
        $server_ips = $redis->SMEMBERS($server_key);
        $connect_servers = $this->getConnectIp();
        if ($server_ips) {
            foreach ($server_ips as $server_ip) {
                if (!in_array($server_ip, $connect_servers)) {
                    $redis->sRem($server_key, $server_ip);
                }
            }
        }
        Response::return(['status' => 1, 'msg' => 'clear success']);
    }

}