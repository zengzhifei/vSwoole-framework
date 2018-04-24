<?php
/**
 * Created by PhpStorm.
 * User: zengzhifei
 * Date: 2018/4/24
 * Time: 16:08
 */

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