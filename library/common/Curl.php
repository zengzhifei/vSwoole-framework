<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


class Curl
{
    /**
     * CURL资源句柄
     * @var
     */
    protected $curl_instance;

    /**
     * CURL默认配置
     * @var array
     */
    protected $curl_options = [
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HEADER         => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => array("Content-type: text/html; charset=utf-8"),
    ];

    /**
     * 配置CURL参数
     * @param array $curl_options
     */
    public function __construct(array $curl_options = [])
    {
        $this->curl_options = $curl_options + $this->curl_options;

        $this->init();
    }

    /**
     * 初始化CURL
     */
    private function init()
    {
        $this->curl_instance = curl_init();
    }

    /**
     * 执行CURL
     * @return mixed
     */
    private function execute()
    {
        return curl_exec($this->curl_instance);
    }

    /**
     * GET方式访问
     * @param string $url
     * @param array $param
     * @return mixed
     */
    public function get(string $url, array $param = [])
    {
        if ($url) {
            $symbol = strstr($url, '?') ? '&' : '?';
            $this->curl_options[CURLOPT_URL] = !empty($param) ? $url . $symbol . http_build_query($param) : $url;
            curl_setopt_array($this->curl_instance, $this->curl_options);
            return $this->execute();
        }
    }

    /**
     * POST方式访问
     * @param string $url
     * @param array $param
     * @return mixed
     */
    public function post(string $url, array $param = [])
    {
        if ($url) {
            $this->curl_options[CURLOPT_URL] = $url;
            $this->curl_options[CURLOPT_POST] = true;
            $this->curl_options[CURLOPT_POSTFIELDS] = $param;
            curl_setopt_array($this->curl_instance, $this->curl_options);
            return $this->execute();
        }
    }

    /**
     * 关闭CURL
     */
    public function close()
    {
        curl_close($this->curl_instance);
    }
}