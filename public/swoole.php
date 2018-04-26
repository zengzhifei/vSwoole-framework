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
     * Swoole constructor.
     * @param $argv
     */
    public function __construct()
    {
        //设置框架根目录
        define('VSWOOLE_ROOT', __DIR__ . '/../');
        //设置debug模式
        define('IS_DEBUG', true);
        //引入框架异常处理文件
        require_once VSWOOLE_ROOT . 'library/common/Exception.php';
        //执行命令
        //$this->start();
        $this->test();
    }

    public function test()
    {
        \vSwoole\library\common\Exception::register();
        //$a = new a();
        echo 2/0;

    }

    /**
     * 执行框架命令
     * @param $argv
     */
    private function start()
    {
        try {
            //载入框架初始化文件
            if (php_sapi_name() === 'cli') {
                require VSWOOLE_ROOT . 'library/Init.php';
            } else {
                throw new \RuntimeException("Swoole Server must run in the CLI mode");
            }
            //运行框架
            \vSwoole\library\Init::cmd();
        } catch (\Exception $e) {
            \vSwoole\library\common\Exception::reportError($e);
        }
    }
}

$server = new Swoole();
