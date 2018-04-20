<?php
/**
 * 框架服务模式入口文件
 * User: zengzhifei
 * Date: 2018/4/19
 * Time: 13:18
 */

class Server
{
    /**
     * Swoole constructor.
     * @param $argv
     */
    public function __construct($argv)
    {
        try {
            if (php_sapi_name() === 'cli') {
                require_once '../library/Init.php';
            } else {
                throw new \RuntimeException("vSwoole server mode must in cli");
            }

            if (isset($argv[1])) {
                Init::start()->run(VSWOOLE_APP_SERVER_NAMESPACE . '\\' . $argv[1]);
            } else {
                throw new \InvalidArgumentException("server class param can't empty");
            }
        } catch (\Exception $e) {
            exit($e->getMessage() . PHP_EOL);
        }
    }
}

$server = new Server($argv);
