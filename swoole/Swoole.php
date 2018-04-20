<?php
/**
 * swoole 服务入口文件
 * User: zengz
 * Date: 2018/4/19
 * Time: 13:18
 */

namespace swoole;

class Swoole
{
    /**
     * Swoole constructor.
     * @param $argv
     */
    public function __construct($argv)
    {
        try {
            if (php_sapi_name() === 'cli') {
                self::init();
            } else {
                throw new \Exception("swoole server mode must in cli");
            }
            if (function_exists('spl_autoload_register')) {
                spl_autoload_register("self::loadClass");
            } else {
                throw new \Exception('not suport function: spl_autoload_register');
            }
            if (isset($argv[1])) {
                $server = "swoole\\server\\{$argv[1]}";
                $swooleServer = new $server;
            } else {
                throw new \Exception("server class param can't empty");
            }
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * 初始化常量
     */
    private static function init()
    {
        define('SWOOLE_ROOT', __DIR__ . '/../');
        define('SWOOLE_CONFIG_PATH', SWOOLE_ROOT . 'swoole/config/');
        define('SWOOLE_LOG_PATH', SWOOLE_ROOT . 'swoole/log/');
        define('SWOOLE_SERVER_PATH', SWOOLE_ROOT . 'swoole/server/');
        define('SWOOLE_HTTP_SERVER', 'Http_Server');
        define('SWOOLE_WEB_SOCKET_SERVER', 'WebSocket_Server');
    }

    /**
     * 引入类文件
     * @param $className
     * @throws \Exception
     */
    private static function loadClass($className)
    {
        $class = str_replace("\\", '/', $className);
        if (file_exists(SWOOLE_ROOT . $class . '.php')) {
            require_once SWOOLE_ROOT . $class . '.php';
        } else {
            throw new \Exception("class {$className} not exist,file path: " . SWOOLE_ROOT . $class . ".php");
        }
    }
}

$swoole = new Swoole($argv);
