<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


class Request
{
    protected static $_instance;

    /**
     * 单例模式获取请求实例
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    /**
     * 获取参数
     * @param string $name
     * @param null $default
     * @param string $filter
     * @return array|null
     */
    public function param(string $name = '', $default = null, $filter = '')
    {
        if ($name) {
            $value = isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : $default);
            if (is_string($filter) && function_exists($filter)) {
                $value = $filter($value);
            } else if (is_callable($filter)) {
                $value = call_user_func_array($filter, [$value]);
            }
            return $value;
        } else {
            return ['GET' => $_GET, 'POST' => $_POST];
        }
    }

}