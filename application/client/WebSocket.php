<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace application\client;


use library\client\WebSocketClient;

class WebSocket extends WebSocketClient
{
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        $res = parent::__construct($connectOptions, $configOptions);
        var_dump($res);
    }


}