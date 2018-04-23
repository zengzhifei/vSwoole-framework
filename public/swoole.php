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
    public function __construct($argv)
    {
        //设置框架根目录
        define('VSWOOLE_ROOT', __DIR__ . '/../');
        //设置debug模式
        define('IS_DEBUG', true);
        //引入框架异常处理文件
        require_once VSWOOLE_ROOT . 'library/common/Exception.php';
        //执行命令
        $this->start($argv);
    }

    /**
     * 执行框架命令
     * @param $argv
     */
    private function start($argv)
    {
        try {
            //载入框架初始化文件
            if (php_sapi_name() === 'cli') {
                require VSWOOLE_ROOT . 'library/Init.php';
            } else {
                throw new \RuntimeException("Swoole Server must run in the CLI mode");
            }
            //运行框架
            if (isset($argv[1])) {
                switch (strtolower($argv[1])) {
                    case 'install':
                        \library\Init::install();
                        break;
                    case 'clear':
                        \library\Init::clear();
                        break;
                    case 'start':
                        if (isset($argv[2])) {
                            \library\Init::start()->runServer($argv[2]);
                        } else {
                            throw new \InvalidArgumentException("Argument 2 is invalid");
                        }
                        break;
                    default:
                        throw new \InvalidArgumentException("Argument 1 is invalid, please use [install,clear,start]");
                        break;
                }
            } else {
                throw new \InvalidArgumentException("Argument can't be empty");
            }
        } catch (\Exception $e) {
            \library\common\Exception::reportError($e);
        }
    }
}

$server = new Swoole(@$argv);
