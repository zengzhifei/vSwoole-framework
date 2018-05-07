<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+


class Client
{
    /**
     * 框架客户端入口
     * @throws Exception
     */
    public function __construct()
    {
        //设置框架根目录
        define('VSWOOLE_ROOT', __DIR__ . '/../');
        //载入框架初始化文件
        if (php_sapi_name() !== 'cli') {
            require VSWOOLE_ROOT . 'library/Init.php';
            //运行框架
            \vSwoole\library\Init::start()->runClient();
        } else {
            die("Swoole Client must run in the PHP-FPM or Apache mode");
        }
    }
}

$client = new Client();