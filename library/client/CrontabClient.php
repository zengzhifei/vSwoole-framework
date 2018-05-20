<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\client;


use vSwoole\library\common\Config;

class CrontabClient extends Client
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
        $connectOptions = array_merge(Config::loadConfig('crontab')->get('client_connect'), $connectOptions);
        $configOptions = array_merge(Config::loadConfig('crontab')->get('client_config'), $configOptions);
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
     * 向服务器发送指令+数据
     * @param string $cmd
     * @param array $data
     * @return bool
     */
    public function execute(string $cmd = '', array $data = [])
    {
        if ($cmd && is_string($cmd) && !empty($this->clients_instance)) {
            $send_data = ['cmd' => $cmd, 'data' => $data];
            foreach ($this->clients_instance as $ip => $client) {
                if ($client->isConnected()) {
                    return $client->send(json_encode($send_data) . "\r\n");
                }
            }
        }
        return false;
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