<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\library\client;


abstract class Client
{
    //客户端对象
    protected $client;
    //客户端连接配置
    protected $connectOptions = [
        //服务Sock类型
        'sockType'      => SWOOLE_SOCK_TCP,
        //同步异步
        'syncType'      => SWOOLE_SOCK_SYNC,
        //长连接Key
        'connectionKey' => '127.0.0.1',
        //服务器地址
        'host'          => '',
        //服务器端口
        'port'          => '',
        //连接超时
        'timeout'       => 1,
        //连接是否阻塞
        'flag'          => 0,
    ];
    //客户端运行配置
    protected $configOptions = [];
    //异步回调事件
    protected $callbackEventList = ['Connect', 'Error', 'Receive', 'Close', 'BufferFull', 'BufferEmpty'];


    /**
     * 连接服务器
     * @param array $connectOptions
     * @param array $configOptions
     * @return bool|\swoole_client
     */
    public function connect(array $connectOptions = [], array $configOptions = [])
    {
        //配置客户端连接相关参数
        $this->connectOptions = array_merge($this->connectOptions, $connectOptions);
        $this->configOptions = array_merge($this->configOptions, $configOptions);

        //配置调整
        if (php_sapi_name() != 'cli') {
            $this->connectOptions['syncType'] = SWOOLE_SOCK_SYNC;
        }

        //实例化客户端
        $this->client = new \swoole_client($this->connectOptions['sockType'], $this->connectOptions['syncType'], $this->connectOptions['connectionKey']);

        //设置客户端配置参数
        if (!empty($this->configOptions)) {
            $this->client->set($this->configOptions);
        }

        //异步连接设置异步回调事件
        if ($this->connectOptions['syncType'] === SWOOLE_SOCK_ASYNC) {
            if (!empty($this->callbackEventList)) {
                foreach ($this->callbackEventList as $event) {
                    if (method_exists($this, 'on' . $event)) {
                        $this->client->on($event, [$this, 'on' . $event]);
                    }
                }
            }
        }

        //客户端连接服务器
        $res = $this->client->connect($this->connectOptions['host'], $this->connectOptions['port'], $this->connectOptions['timeout'], $this->connectOptions['flag']);

        //同步返回结果可用，异步不可用
        return $res ? $this->client : false;
    }

    /**
     * 魔术方法
     * @param $method
     * @param $args
     */
    public function __call($method, $args)
    {
        call_user_func_array([$this->client, $method], $args);
    }
}