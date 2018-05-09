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
    /**
     * 第三方框架引入入口
     * @throws Exception
     */
    public function __construct()
    {
        //设置框架根目录
        define('VSWOOLE_ROOT', __DIR__ . '/../');
        //载入框架初始化文件
        require VSWOOLE_ROOT . 'library/Init.php';
        //运行框架
        \vSwoole\library\Init::start();
    }
}

$client = new Index();