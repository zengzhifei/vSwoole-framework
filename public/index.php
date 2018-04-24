<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

class Index
{
    public function __construct()
    {
        //设置框架根目录
        define('VSWOOLE_ROOT', __DIR__ . '/../');
        //设置debug模式
        define('IS_DEBUG', true);
        //引入框架异常处理文件
        require_once VSWOOLE_ROOT . 'library/common/Exception.php';
        //引入框架引导文件
        require_once VSWOOLE_ROOT . 'library/Init.php';
        //载入框架

    }
}

$client = new Index();