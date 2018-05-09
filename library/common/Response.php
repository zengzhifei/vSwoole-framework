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
    /**
     * 输出指定格式内容
     * @param string $data
     * @param string $format
     */
    public static function return($data = '', string $format = 'json')
    {
        switch (strtolower($format)) {
            case 'json':
                exit(json_encode($data));
                break;
        }
    }

}