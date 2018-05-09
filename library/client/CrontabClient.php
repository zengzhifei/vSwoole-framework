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
use vSwoole\library\common\exception\Exception;

class CrontabClient extends Client
{
    /**
     * 连接服务器
     * @param array $connectOptions
     * @param array $configOptions
     * @throws \ReflectionException
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        try {
            $connectOptions = array_merge(Config::loadConfig('crontab')->get('client_connect'), $connectOptions);
            $configOptions = array_merge(Config::loadConfig('crontab')->get('client_config'), $configOptions);
            if (false !== parent::__construct($connectOptions, $configOptions)) {
                return $this->client;
            } else {
                $this->client->close();
                throw new \Exception('Swoole Client connect failed');
            }
        } catch (\Exception $e) {
            Exception::reportError($e);
        }
    }

    /**
     * 向服务器发送指令+数据
     * @param string $cmd
     * @param array $data
     * @return bool
     */
    public function execute(string $cmd = '', array $data = [])
    {
        if ($cmd && is_string($cmd)) {
            $send_data = ['cmd' => $cmd, 'data' => $data];
            if ($this->client->isConnected()) {
                return $this->client->send(json_encode($send_data));
            }
        } else {
            return false;
        }
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