<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


class Response
{
    public static function return($data = '', string $format = 'json')
    {
        switch (strtolower($format)) {
            case 'json':
                exit(json_encode($data));
                break;
        }
    }

}