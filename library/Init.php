<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace library;

use library\common\Exception;

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
        //框架根目录
        define('VSWOOLE_ROOT', __DIR__ . '/../');
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
            $class = str_replace("\\", '/', $className);
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
     * 注册类自动加载
     */
    private static function registerAutoload()
    {
        spl_autoload_register("self::loadClass");
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
            Exception::reportError($e);
        }
    }
}