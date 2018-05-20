<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library;


use vSwoole\library\common\Build;
use vSwoole\library\common\Command;
use vSwoole\library\common\Config;
use vSwoole\library\common\exception\ClassNotFoundException;
use vSwoole\library\common\exception\Exception;

class Init
{
    /**
     * 执行命令
     * @throws \Exception
     */
    public static function cmd()
    {
        $commands = [
            'default'  => 'You can input the following commands:' . PHP_EOL,
            'start'    => '  start servername' . '       you can start a server[WebSocket,Crontab,Http,Udp]' . PHP_EOL,
            'build'    => '  build servername' . '       you can build a new server' . PHP_EOL,
            'reload'   => '  reload servername' . '      you can reload has runing server' . PHP_EOL,
            'shutdown' => '  shutdown servername' . '    you can shutdown has runing server' . PHP_EOL,
            'log'      => '  log servername' . '         you can reload log file' . PHP_EOL,
            'clear'    => '  clear' . '                  you can clear the logs of the vswoole framework' . PHP_EOL,
            'install'  => '  install' . '                you can install the necessary directory in the vswoole framework' . PHP_EOL,
            'help'     => '  help' . '                   you can get help about vswoole framework' . PHP_EOL,
        ];

        $cmd = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
        switch (strtolower($cmd)) {
            case 'start':
                if (count($_SERVER['argv']) !== 3) {
                    echo "command: '{$cmd}' require argument server name and do not require more arguments" . PHP_EOL;
                    echo 'help:' . PHP_EOL;
                    echo $commands[$cmd];
                } else {
                    self::start()->runServer($_SERVER['argv'][2]);
                }
                break;
            case 'build':
                if (count($_SERVER['argv']) > 3) {
                    echo "command: '{$cmd}' require arguments server name and do not require more arguments" . PHP_EOL;
                    echo 'help:' . PHP_EOL;
                    echo $commands[$cmd];
                } else if (count($_SERVER['argv']) == 2) {
                    echo "will build a Demo server for you..." . PHP_EOL;
                    self::build();
                } else {
                    self::build($_SERVER['argv'][2]);
                }
                break;
            case 'reload':
                if (count($_SERVER['argv']) !== 3) {
                    echo "command: '{$cmd}' require argument server name and do not require more arguments" . PHP_EOL;
                    echo 'help:' . PHP_EOL;
                    echo $commands[$cmd];
                } else {
                    self::reload($_SERVER['argv'][2]);
                }
                break;
            case 'shutdown':
                if (count($_SERVER['argv']) !== 3) {
                    echo "command: '{$cmd}' require argument server name and do not require more arguments" . PHP_EOL;
                    echo 'help:' . PHP_EOL;
                    echo $commands[$cmd];
                } else {
                    self::shutdown($_SERVER['argv'][2]);
                }
                break;
            case 'log':
                if (count($_SERVER['argv']) !== 3) {
                    echo "command: '{$cmd}' require argument server name and do not require more arguments" . PHP_EOL;
                    echo 'help:' . PHP_EOL;
                    echo $commands[$cmd];
                } else {
                    self::log($_SERVER['argv'][2]);
                }
                break;
            case 'clear':
                if (count($_SERVER['argv']) > 2) {
                    echo "command: '{$cmd}' do not require arguments" . PHP_EOL;
                    echo 'help:' . PHP_EOL;
                    echo $commands[$cmd];
                } else {
                    self::clear();
                }
                break;
            case 'install':
                if (count($_SERVER['argv']) > 2) {
                    echo "command: '{$cmd}' do not require arguments" . PHP_EOL;
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
            case 'test':
                self::test();
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
    }

    /**
     * 初始化框架环境常量
     */
    private static function initEnv()
    {
        //设置时区
        ini_set('date.timezone', Config::loadConfig()->get('timezone'));
    }

    /**
     * 初始化框架偏好配置
     */
    private static function initConvention()
    {
        //自定义常量
        file_exists(VSWOOLE_ROOT . 'configs/const.php') && require VSWOOLE_ROOT . 'configs/const.php';
        file_exists(VSWOOLE_ROOT . 'library/conf/const.php') && require VSWOOLE_ROOT . 'library/conf/const.php';
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
        //框架数据服务进程目录
        if (!is_dir(VSWOOLE_DATA_CACHE_PATH)) {
            mkdir(VSWOOLE_DATA_CACHE_PATH, 755, true);
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
     * @throws ClassNotFoundException
     */
    private static function loadClass(string $className)
    {
        if (false !== strpos($className, VSWOOLE_NAMESPACE)) {
            $class = str_replace(VSWOOLE_NAMESPACE . '\\', '', $className);
            $class = str_replace('\\', '/', $class);
            if (file_exists(VSWOOLE_ROOT . $class . VSWOOLE_CLASS_EXT)) {
                require_once VSWOOLE_ROOT . $class . VSWOOLE_CLASS_EXT;
            } else {
                throw new ClassNotFoundException("class {$className} not exist,file path: " . VSWOOLE_ROOT . $class . VSWOOLE_CLASS_EXT);
            }
        }
    }

    /**
     * 框架异常处理注册
     */
    private static function exceptionRegister()
    {
        require_once VSWOOLE_ROOT . 'library/common/exception/Exception.php';
        Exception::register();
    }

    /**
     * 注册框架自动加载
     */
    private static function autoloadRegister()
    {
        spl_autoload_register([self::class, 'loadClass']);
    }

    /**
     * 安装框架目录结构
     */
    private static function install()
    {
        self::initConvention();
        self::initInstall();
    }

    /**
     * 构建服务器基础文件
     * @param string $serverName
     */
    private static function build(string $serverName = 'Demo')
    {
        self::initConvention();
        require_once VSWOOLE_ROOT . 'library/common/Build.php';
        if (!Build::build($serverName)) {
            die('Build the server failure,and the server file has already existed.' . PHP_EOL);
        }
    }

    /**
     * 清除框架日志文件
     * @param null $dir
     */
    private static function clear($dir = null)
    {
        if (!defined('VSWOOLE_LOG_PATH') || !defined('VSWOOLE_LOG_SERVER_PATH') || !defined('VSWOOLE_LOG_CLIENT_PATH')) {
            self::initConvention();
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
     * 重载指定服务工作进程
     * @param string $serverName
     */
    private static function reload(string $serverName = '')
    {
        self::initConvention();
        self::exceptionRegister();
        self::autoloadRegister();
        $server_list = Config::loadConfig()->get('server_list');
        if (array_key_exists($serverName, $server_list)) {
            Command::getInstance()->reload($server_list[$serverName]);
        } else {
            die('Reload the server failure,and the server is not exist.' . PHP_EOL);
        }
    }

    /**
     * 关闭指定服务
     * @param string $serverName
     */
    private static function shutdown(string $serverName = '')
    {
        self::initConvention();
        self::exceptionRegister();
        self::autoloadRegister();
        $server_list = Config::loadConfig()->get('server_list');
        if (array_key_exists($serverName, $server_list)) {
            Command::getInstance()->shutdown($server_list[$serverName]);
        } else {
            die('Shutdown the server failure,and the server is not exist.' . PHP_EOL);
        }
    }

    /**
     * 重载服务日志文件
     * @param string $serverName
     * @throws \ReflectionException
     */
    private static function log(string $serverName = '')
    {
        self::initConvention();
        self::exceptionRegister();
        self::autoloadRegister();
        $server_list = Config::loadConfig()->get('server_list');
        if (array_key_exists($serverName, $server_list)) {
            Command::getInstance()->reloadLog($server_list[$serverName]);
        } else {
            die('Reload log of the server failure,and the server is not exist.' . PHP_EOL);
        }
    }

    /**
     * 测试使用
     */
    private static function test()
    {
        self::initConvention();
        self::exceptionRegister();
        self::autoloadRegister();

    }

    /**
     * 载入框架
     * @return Init
     * @throws \Exception
     */
    public static function start()
    {
        //务必按顺序初始化
        self::initConvention();
        self::exceptionRegister();
        self::autoloadRegister();
        self::initCheck();
        self::initEnv();
        self::initInstall();

        return new self();
    }

    /**
     * 启动框架服务
     * @param string $class
     */
    private function runServer(string $class)
    {
        $server_list = Config::loadConfig()->get('server_list');
        if (array_key_exists($class, $server_list)) {
            $class = VSWOOLE_APP_SERVER_NAMESPACE . '\\' . $class;
            $server = new $class;
        } else {
            die('Start the server failure,and the server is not exist.' . PHP_EOL);
        }
    }

    /**
     * 启动框架客户端
     * @throws \Exception
     */
    public function runClient()
    {
        $uri = isset($_GET[VSWOOLE_VAR_URL]) ? $_GET[VSWOOLE_VAR_URL] : '';
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
    }
}