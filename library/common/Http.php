<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


class Http
{
    /**
     * http异步客户端
     * @var null
     */
    protected $http_instance = null;

    /**
     * 默认配置参数
     * @var array
     */
    protected $http_options = [
        'enable_dns_lookup' => false,
        'request_domain'    => '',
        'request_port'      => 80,
        'request_callback'  => null,
        'ssl'               => [
            'ssl_cert_file' => '',
            'ssl_key_file'  => ''
        ]
    ];

    /**
     * 配置HTTP参数
     * @param array $http_options
     */
    public function __construct(array $http_options = [])
    {
        $this->http_options = array_merge($this->http_options, $http_options);
    }

    /**
     * 实例化Http客户端
     */
    public function connect()
    {
        if ($this->http_options['enable_dns_lookup']) {
            \Swoole\Async::dnsLookup($this->http_options['request_domain'], function ($host, $ip) {
                $this->http_instance = new \swoole_http_client($ip, $this->http_options['request_port']);
                $this->http_instance->setHeaders([
                    'host'       => $host,
                    "User-Agent" => 'Swoole_http_client'
                ]);
                if ($this->http_options['request_port'] == 443) {
                    $this->http_instance->set($this->http_options['ssl']);
                }
                if (is_callable($this->http_options['request_callback'])) {
                    call_user_func_array($this->http_options['request_callback'], [$this->http_instance]);
                }
            });
        } else {
            $this->http_instance = new \swoole_http_client($this->http_options['request_domain'], $this->http_options['request_port']);
            $this->http_instance->setHeaders([
                'host'       => $this->http_options['request_domain'],
                "User-Agent" => 'Swoole_http_client'
            ]);
            if ($this->http_options['request_port'] == 443) {
                $this->http_instance->set($this->http_options['ssl']);
            }
            if (is_callable($this->http_options['request_callback'])) {
                call_user_func_array($this->http_options['request_callback'], [$this->http_instance]);
            }
        }
    }

    /**
     * 魔术方法
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        call_user_func_array([$this->http_instance, $name], $arguments);
    }
}