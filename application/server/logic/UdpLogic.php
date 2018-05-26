<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\application\server\logic;


class UdpLogic
{
    /**
     * UdpLogic constructor.
     * @param \swoole_server $server
     */
    public function __construct(\swoole_server $server)
    {
        $GLOBALS['server'] = $server;
    }

    /**
     * 请求逻辑处理
     * @param string $data
     */
    public function execute(string $data)
    {

    }

}