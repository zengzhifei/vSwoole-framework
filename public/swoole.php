<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+


class Swoole
{
    /**
     * 框架服务入口
     * Swoole constructor.
     * @throws Exception
     */
    public function __construct()
    {
        //设置框架根目录
        define('VSWOOLE_ROOT', __DIR__ . '/../');
        //载入框架初始化文件
        if (php_sapi_name() === 'cli') {
            require VSWOOLE_ROOT . 'library/Init.php';
            //运行框架
            \vSwoole\library\Init::cmd();
        } else {
            die("Swoole Server must run in the CLI mode");
        }
    }
}

$server = new Swoole();