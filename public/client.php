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
    public function __construct()
    {
        //设置框架根目录
        define('VSWOOLE_ROOT', __DIR__ . '/../');
        //设置debug模式
        define('IS_DEBUG', true);
        //引入框架异常处理文件
        require_once VSWOOLE_ROOT . 'library/common/Exception.php';
        //路由
        $this->start();
    }

    public function start()
    {
        try {
            //载入框架初始化文件
            if (php_sapi_name() !== 'cli') {
                require VSWOOLE_ROOT . 'library/Init.php';
            } else {
                throw new \RuntimeException("Swoole Server must run in the PHP-FPM mode");
            }

            //运行框架
            \vSwoole\library\Init::start()->runClient(isset($_GET['s']) ? $_GET['s'] : '');
        } catch (\Exception $e) {
            \vSwoole\library\common\Exception::reportError($e);
        }
    }
}

$client = new Client();