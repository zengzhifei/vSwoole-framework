<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\core\client;


use vSwoole\library\client\Client;
use vSwoole\library\common\Config;

class HttpClient extends Client
{
    /**
     * 客户端连接实例
     * @var array
     */
    protected $clients_instance = [];
    /**
     * 连接IP
     * @var array
     */
    protected $connect_instance = [];

    /**
     * 连接服务器
     * @param array $connectOptions
     * @param array $configOptions
     * @return bool|\swoole_client
     */
    public function connect(array $connectOptions = [], array $configOptions = [])
    {
        $connectOptions = array_merge(Config::loadConfig('http')->get('client_connect'), $connectOptions);
        $configOptions = array_merge(Config::loadConfig('http')->get('client_config'), $configOptions);
        if (false !== parent::connect($connectOptions, $configOptions)) {
            $this->clients_instance[md5($connectOptions['host'])] = $this->client;
            $this->connect_instance[md5($connectOptions['host'])] = $connectOptions['host'];
            return $this->client;
        } else {
            $this->client->close();
            return false;
        }
    }

    /**
     * 获取已连接IP实例
     * @return array
     */
    public function getConnectIp()
    {
        return $this->connect_instance;
    }

    /**
     * 请求结束，关闭客户端连接
     */
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if ($this->client->isConnected()) {
            $this->client->close();
        }
    }
}