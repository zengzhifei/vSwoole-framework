<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\application\client;


class Index
{
    public function index()
    {
        echo <<<EOT
             <p style="padding-left:30px;font-size: 30px;">VSwoole FrameWork</p>
             <p style="padding-left:30px;font-size: 20px;">Swoole 微服务框架 - Not Decline To Shoulder a Responsibility</p>
EOT;

    }
}