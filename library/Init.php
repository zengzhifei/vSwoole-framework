<?php
/**
 * 框架引导文件
 * User: zengzhifei
 * Date: 2018/4/20
 * Time: 10:18
 */

class Init
{
    /**
     * 框架初始化检测
     */
    private static function initCheck()
    {
        try {
            //PHP 版本检测
            if (version_compare(PHP_VERSION, '7.0.0', '<')) {
                throw new \Exception('php version must than 7.0');
            }
            //Swoole 版本检测
            if (version_compare(swoole_version(), '2.0', '<')) {
                throw new \Exception('swoole version must than 2.0');
            }
            //自动加载函数检测
            if (!function_exists('spl_autoload_register')) {
                throw new \BadFunctionCallException('function not support: spl_autoload_register');
            }
        } catch (\Exception $e) {
            exit($e->getMessage() . PHP_EOL);
        }
    }

    /**
     * 初始化框架环境常量
     */
    private static function initEnv()
    {
        //框架根目录
        define('VSWOOLE_ROOT', __DIR__ . '/../');
        //框架应用目录
        define('VSOOLE_APP_PATH', VSWOOLE_ROOT . 'application/');
        //框架日志目录
        define('VSWOOLE_LOG_PATH', VSWOOLE_ROOT . 'log/');
        //框架服务端日志目录
        define('VSWOOLE_SERVER_LOG_PATH', VSWOOLE_LOG_PATH . 'server/');
        //框架客户端日志目录
        define('VSWOOLE_CLIENT_LOG_PATH', VSWOOLE_LOG_PATH . 'client/');
        //框架配置目录
        define('VSWOOLE_CONFIG_PATH', VSWOOLE_ROOT . 'configs/');
        //框架核心目录
        define('VSWOOLE_LIBRARY_PATH', VSWOOLE_ROOT . 'library/');
    }

    /**
     * 初始化框架服务常量
     */
    private static function initServer()
    {
        //服务器
        define('VSWOOLE_SERVER', 1);
        //客户端
        define('VSWOOLE_CLIENT', 2);
        //Http服务
        define('VSWOOLE_HTTP_SERVER', 'Http_Server');
        //WebSocket服务
        define('VSWOOLE_WEB_SOCKET_SERVER', 'WebSocket_Server');
    }

    /**
     * 初始化框架命名空间常量
     */
    private static function initNamespace()
    {
        //服务端命名空间
        define('VSWOOLE_APP_SERVER_NAMESPACE', 'application\server');
        //客户端命名空间
        define('VSWOOLE_APP_CLIENT_NAMESPACE', 'application\client');
    }

    /**
     * 初始化框架偏好常量
     */
    private static function initConvention()
    {
        //类文件扩展名
        define('VSWOOLE_CLASS_EXT', '.php');
        //配置文件扩展名
        define('VSWOOLE_CONFIG_EXT', '.php');
    }

    /**
     * 初始化日志目录
     */
    private static function initLogPath()
    {
        if (!is_dir(VSWOOLE_SERVER_LOG_PATH)) {
            mkdir(VSWOOLE_SERVER_LOG_PATH, 755, true);
        }
        if (!is_dir(VSWOOLE_CLIENT_LOG_PATH)) {
            mkdir(VSWOOLE_CLIENT_LOG_PATH, 755, true);
        }
    }

    /**
     * 类自动加载
     * @param string $className
     * @throws ErrorException
     */
    private static function loadClass(string $className)
    {
        $class = str_replace("\\", '/', $className);
        if (file_exists(VSWOOLE_ROOT . $class . VSWOOLE_CLASS_EXT)) {
            require_once VSWOOLE_ROOT . $class . VSWOOLE_CLASS_EXT;
        } else {
            throw new \ErrorException("class {$className} not exist,file path: " . VSWOOLE_ROOT . $class . VSWOOLE_CLASS_EXT);
        }
    }

    /**
     * 注册类自动加载
     */
    private static function registerAutoload()
    {
        spl_autoload_register("self::loadClass");
    }

    /**
     * 载入框架
     * @return Init
     */
    public static function start()
    {
        self::initCheck();
        self::initEnv();
        self::initServer();
        self::initNamespace();
        self::initConvention();
        self::initLogPath();
        self::registerAutoload();

        return new self();
    }

    /**
     * 启动框架
     * @param string $class
     */
    public function run(string $class)
    {
        try {
            $server = new $class;
        } catch (\Exception $e) {
            exit($e->getMessage() . PHP_EOL);
        }
    }
}