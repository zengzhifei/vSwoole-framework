<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\application\client;


use vSwoole\library\common\Utils;

class Test
{
    public function ip()
    {
        echo Utils::getServerIp();
    }

    public function test()
    {
        throw new \vSwoole\library\common\exception\Exception(11);
    }
}