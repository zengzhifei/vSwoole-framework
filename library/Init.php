<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library;


use vSwoole\library\common\Exception;

class Init
{
    /**
     * 执行命令
     */
    public static function cmd()
    {
        $commands = [
            'default' => 'You can input the following commands:' . PHP_EOL,
            'start'   => '  start servername' . '       you can start a server[WebSocket,Http,Udp]' . PHP_EOL,
            'clear'   => '  clear' . '                  you can clear the logs of the vswoole framework' . PHP_EOL,
            'install' => '  install' . '                you can install the necessary directory in the vswoole framework' . PHP_EOL,
            'help'    => '  help' . '                   you can get help about vswoole framework' . PHP_EOL,
        ];

        $cmd = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
        switch (strtolower($cmd)) {
            case 'start':
                if (count($_SERVER['argv']) !== 3) {
                    echo "command: '{$cmd}' require parameter server name and do not require more parameter" . PHP_EOL;
                    echo 'help:' . PHP_EOL;
                    echo $commands[$cmd];
                } else {
                    self::start()->runServer($_SERVER['argv'][2]);
                }
                break;
            case 'clear':
                if (count($_SERVER['argv']) > 2) {
                    echo "command: '{$cmd}' do not require parameters" . PHP_EOL;
                    echo 'help:' . PHP_EOL;
                    echo $commands[$cmd];
                } else {
                    self::clear();
                }
                break;
            case 'install':
                if (count($_SERVER['argv']) > 2) {
                    echo "command: '{$cmd}' do not require parameters" . PHP_EOL;
                    echo 'help:' . PHP_EOL;
                    echo $commands[$cmd];
                } else {
                    self::install();
                }
                break;
            case 'help':
                $help_cmd = isset($_SERVER['argv'][2]) ? strtolower($_SERVER['argv'][2]) : '';
                if (array_key_exists($help_cmd, $commands)) {
                    echo 'help:' . PHP_EOL;
                    echo $commands[$help_cmd];
                } else {
                    echo join('', $commands);
                }
                break;
            case '':
                echo join('', $commands);
                break;
            default:
                echo "command: '{$cmd}' is invalid" . PHP_EOL;
                echo join('', $commands);
                break;
        }
    }

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
            //Swoole 扩展检查
            if (!extension_loaded('swoole')) {
                throw new \Exception('swoole extension not loaded');
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
            Exception::reportError($e);
        }
    }

    /**
     * 初始化框架环境常量
     */
    private static function initEnv()
    {
        //设置时区
        ini_set('date.timezone', 'PRC');
    }

    /**
     * 初始化框架偏好常量
     */
    private static function initDefine()
    {
        //偏好配置
        $defines = require VSWOOLE_ROOT . 'library/conf/convention.php';
        foreach ($defines['define'] as $defineKey => $defineValue) {
            define(strtoupper($defineKey), $defineValue);
        }
    }

    /**
     * 初始化框架目录
     */
    private static function initInstall()
    {
        //应用根目录
        if (!is_dir(VSWOOLE_APP_PATH)) {
            mkdir(VSWOOLE_APP_PATH, 755, true);
        }
        //应用服务端目录
        if (!is_dir(VSWOOLE_APP_SERVER_PATH)) {
            mkdir(VSWOOLE_APP_SERVER_PATH, 755, true);
        }
        //应用客户端目录
        if (!is_dir(VSWOOLE_APP_CLIENT_PATH)) {
            mkdir(VSWOOLE_APP_CLIENT_PATH, 755, true);
        }
        //框架配置目录
        if (!is_dir(VSWOOLE_CONFIG_PATH)) {
            mkdir(VSWOOLE_CONFIG_PATH, 755, true);
        }
        //框架数据根目录
        if (!is_dir(VSWOOLE_DATA_PATH)) {
            mkdir(VSWOOLE_DATA_PATH, 755, true);
        }
        //框架数据服务进程目录
        if (!is_dir(VSWOOLE_DATA_PID_PATH)) {
            mkdir(VSWOOLE_DATA_PID_PATH, 755, true);
        }
        //日志根目录
        if (!is_dir(VSWOOLE_LOG_PATH)) {
            mkdir(VSWOOLE_LOG_PATH, 755, true);
        }
        //日志服务端目录
        if (!is_dir(VSWOOLE_LOG_SERVER_PATH)) {
            mkdir(VSWOOLE_LOG_SERVER_PATH, 755, true);
        }
        //日志客户端目录
        if (!is_dir(VSWOOLE_LOG_CLIENT_PATH)) {
            mkdir(VSWOOLE_LOG_CLIENT_PATH, 755, true);
        }
    }

    /**
     * 类自动加载
     * @param string $className
     */
    private static function loadClass(string $className)
    {
        try {
            $class = str_replace(VSWOOLE_NAMESPACE . '\\', '', $className);
            $class = str_replace('\\', '/', $class);
            if (file_exists(VSWOOLE_ROOT . $class . VSWOOLE_CLASS_EXT)) {
                require_once VSWOOLE_ROOT . $class . VSWOOLE_CLASS_EXT;
            } else {
                throw new \RuntimeException("class {$className} not exist,file path: " . VSWOOLE_ROOT . $class . VSWOOLE_CLASS_EXT);
            }
        } catch (\Exception $e) {
            Exception::reportError($e);
        }
    }

    /**
     * 注册框架核心功能
     */
    private static function initRegister()
    {
        //注册类自动加载
        spl_autoload_register("self::loadClass");
        //注册异常错误处理
        //todo
    }

    /**
     * 安装框架目录结构
     */
    public static function install()
    {
        self::initDefine();
        self::initInstall();
    }

    /**
     * 清除框架日志文件
     * @param null $dir
     */
    public static function clear($dir = null)
    {
        if (!defined('VSWOOLE_LOG_PATH') || !defined('VSWOOLE_LOG_SERVER_PATH') || !defined('VSWOOLE_LOG_CLIENT_PATH')) {
            self::initDefine();
        }

        $dir = $dir ? $dir : VSWOOLE_LOG_PATH;

        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $this_dir = $dir . $file;
                    if (is_dir($this_dir)) {
                        self::clear($this_dir . '/');
                    } else {
                        @unlink($this_dir);
                    }
                }
            }
            $dir !== VSWOOLE_LOG_SERVER_PATH && $dir !== VSWOOLE_LOG_CLIENT_PATH && @rmdir($dir);
        }
    }

    /**
     * 载入框架
     * @return Init
     */
    public static function start()
    {
        self::initCheck();
        self::initEnv();
        self::initDefine();
        self::initInstall();
        self::initRegister();

        return new self();
    }

    /**
     * 启动框架服务
     * @param string $class
     */
    public function runServer(string $class)
    {
        try {
            $class = VSWOOLE_APP_SERVER_NAMESPACE . '\\' . $class;
            $server = new $class;
        } catch (\Exception $e) {
            Exception::reportError($e);
        }
    }

    /**
     * 启动框架客户端
     * @param string $uri
     */
    public function runClient(string $uri = null)
    {
        try {
            $router = explode("\\", str_replace('/', '\\', $uri));
            $controller = isset($router[0]) && $router[0] != '' ? $router[0] : 'Index';
            $action = isset($router[1]) && $router[1] != '' ? $router[1] : 'index';
            $class = VSWOOLE_APP_CLIENT_NAMESPACE . '\\' . $controller;
            $client = new $class;
            if (method_exists($client, $action)) {
                $client->$action();
            } else {
                throw new \Exception("Argument 2 {$action} method not exist");
            }
        } catch (\Exception $e) {
            Exception::reportError($e);
        }
    }
}