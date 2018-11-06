<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


class Build
{
    /**
     * 服务类型
     * @var string
     */
    protected static $serverType;

    /**
     * 核心服务名称
     * @var string
     */
    protected static $coreServerName;

    /**
     * 核心服务客户端名称
     * @var string
     */
    protected static $coreClientServerName;

    /**
     * 应用服务名称
     * @var string
     */
    protected static $serverName;

    /**
     * 服务端口
     */
    protected static $serverPort;

    /**
     * 管理服务端口
     */
    protected static $adminServerPort;

    /**
     * 获取核心服务回调事件函数列表
     * @return array
     */
    protected static function getCoreCallbackEventList()
    {
        $serverName = self::$serverName;
        $serverNameUpper = strtoupper(self::$serverName);
        $serverNameLower = strtolower(self::$serverName);

        return [
            '__construct'    => [
                'annotation' => '启动服务器',
                'parameters' => ['@param array $connectOptions', '@param array $configOptions', '@throws \ReflectionException'],
                'arguments'  => ['array $connectOptions = []', 'array $configOptions = []'],
                'content'    => <<<EOF
        try {
            \$server_connect = array_merge(Config::loadConfig('{$serverNameLower}')->get('server_connect'), \$connectOptions);
            \$server_config = array_merge(Config::loadConfig('{$serverNameLower}')->get('server_config'), \$configOptions);
            
            if (!parent::__construct(\$server_connect, \$server_config)) {
                throw new \Exception("Swoole {$serverName} Server start failed", \$this->swoole->getLastError());
            }
        } catch (\Exception \$e) {
            Exception::reportError(\$e);
        }                                      
EOF
            ],
            'onStart'        => [
                'annotation' => '主进程启动回调函数',
                'parameters' => ['@param \swoole_server $server'],
                'arguments'  => ['\swoole_server $server'],
                'content'    => <<<EOF
        //展示服务启动信息
        \$this->startShowServerInfo();
        //设置主进程别名
        Utils::setProcessName(VSWOOLE_{$serverNameUpper}_SERVER . ' master');
        //异步记录服务进程PID
        Utils::writePid(\$server->manager_pid, VSWOOLE_{$serverNameUpper}_SERVER . '_Manager');                                     
EOF
            ],
            'onShutdown'     => [
                'annotation' => '主进程结束回调函数',
                'parameters' => ['@param \swoole_server $server'],
                'arguments'  => ['\swoole_server $server']
            ],
            'onManagerStart' => [
                'annotation' => '管理进程启动回调函数',
                'parameters' => ['@param \swoole_server $server'],
                'arguments'  => ['\swoole_server $server'],
                'content'    => <<<EOF
        //设置管理进程别名
        Utils::setProcessName(VSWOOLE_{$serverNameUpper}_SERVER . ' manager');                                     
EOF
            ],
            'onManagerStop'  => [
                'annotation' => '管理进程结束回调函数',
                'parameters' => ['@param \swoole_server $server'],
                'arguments'  => ['\swoole_server $server']
            ],
            'onWorkerStart'  => [
                'annotation' => '工作进程启动回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $worker_id'],
                'arguments'  => ['\swoole_server $server', 'int $worker_id'],
                'content'    => <<<EOF
        //设置工作进程别名
        \$worker_name = \$server->taskworker ? ' tasker/' . \$worker_id : ' worker/' . \$worker_id;
        Utils::setProcessName(VSWOOLE_{$serverNameUpper}_SERVER . \$worker_name);
        //缓存配置
        \$is_cache = Config::loadConfig('{$serverNameLower}')->get('other_config.is_cache_config');
        \$is_cache && Config::cacheConfig();                                     
EOF
            ],
            'onWorkerStop'   => [
                'annotation' => '工作进程结束回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $worker_id'],
                'arguments'  => ['\swoole_server $server', 'int $worker_id']
            ],
            'onWorkerExit'   => [
                'annotation' => '工作进程退出回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $worker_id'],
                'arguments'  => ['\swoole_server $server', 'int $worker_id']
            ],
            'onWorkerError'  => [
                'annotation' => '工作进程异常回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $worker_id'],
                'arguments'  => ['\swoole_server $server', 'int $worker_id']
            ],
            'onConnect'      => [
                'annotation' => '客户端连接回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $fd', '@param int $reactor_id'],
                'arguments'  => ['\swoole_server $server', 'int $fd', 'int $reactor_id']
            ],
            'onReceive'      => [
                'annotation' => '接收客户端数据回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $fd', '@param int $reactor_id', '@param string $data'],
                'arguments'  => ['\swoole_server $server', 'int $fd', 'int $reactor_id', 'string $data']
            ],
            'onPacket'       => [
                'annotation' => '接收客户端UDP数据回调函数',
                'parameters' => ['@param \swoole_server $server', '@param string $data', '@param array $client_info'],
                'arguments'  => ['\swoole_server $server', 'string $data', 'array $client_info']
            ],
            'onClose'        => [
                'annotation' => '客户端断开回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $fd', '@param int $reactor_id'],
                'arguments'  => ['\swoole_server $server', 'int $fd', 'int $reactor_id']
            ],
            'onBufferFull'   => [
                'annotation' => '缓存区达到最高水位时回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $fd'],
                'arguments'  => ['\swoole_server $server', 'int $fd']
            ],
            'onBufferEmpty'  => [
                'annotation' => '缓存区达到最低水位时回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $fd'],
                'arguments'  => ['\swoole_server $server', 'int $fd']
            ],
            'onTask'         => [
                'annotation' => '异步任务执行回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $task_id', '@param int $src_worker_id', '@param $data'],
                'arguments'  => ['\swoole_server $server', 'int $task_id', 'int $src_worker_id', '$data']
            ],
            'onFinish'       => [
                'annotation' => '异步任务执行完成回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $task_id', '@param $data'],
                'arguments'  => ['\swoole_server $server', 'int $task_id', '$data']
            ],
            'onPipeMessage'  => [
                'annotation' => '工作进程接收管道消息回调函数',
                'parameters' => ['@param \swoole_server $server', '@param int $src_worker_id', '@param $data'],
                'arguments'  => ['\swoole_server $server', 'int $src_worker_id', '$data']
            ],
            'onOpen'         => [
                'annotation' => '客户端与WebSocket建立连接成功后回调函数',
                'parameters' => ['@param \swoole_websocket_server $server', '@param \swoole_http_request $request'],
                'arguments'  => ['\swoole_websocket_server $server', '\swoole_http_request $request']
            ],
            'onMessage'      => [
                'annotation' => 'WebSocket服务端接收客户端消息回调函数',
                'parameters' => ['@param \swoole_websocket_server $server', '@param \swoole_websocket_frame $frame'],
                'arguments'  => ['\swoole_websocket_server $server', '\swoole_websocket_frame $frame'],
                'content'    => <<<EOF
            //向管理客户端返回数据接收成功状态
EOF
            ],
            'onRequest'      => [
                'annotation' => '接收Http客户端请求回调函数',
                'parameters' => ['@param \swoole_http_request $request', '@param \swoole_http_response $response'],
                'arguments'  => ['\swoole_http_request $request', '\swoole_http_response $response']
            ]
        ];
    }

    /**
     * 获取应用服务回调事件函数列表
     * @return array
     */
    protected static function getCallbackEventList()
    {
        return [
            '__construct' => [
                'annotation' => '启动服务器',
                'parameters' => ['@param array $connectOptions', '@param array $configOptions'],
                'arguments'  => ['array $connectOptions = []', 'array $configOptions = []'],
                'content'    => <<<EOF
        parent::__construct(\$connectOptions, \$configOptions);// TODO: Change the autogenerated stub
EOF
            ],
        ];
    }

    /**
     * 构建核心服务头部内容
     * @return string
     */
    protected static function buildCoreServerHeader()
    {
        $serverName = self::$coreServerName;
        return <<<EOF
<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\core\server;


use vSwoole\library\common\Config;
use vSwoole\library\common\\exception\Exception;
use vSwoole\library\common\Utils;
use vSwoole\library\server\Server;

class {$serverName} extends Server 
{
EOF;
    }

    /**
     * 构建核心服务回调函数内容
     * @return string
     */
    protected static function buildCoreServerCallback()
    {
        $callbackContent = PHP_EOL;

        foreach (self::getCoreCallbackEventList() as $callbackName => $callback) {
            $parameters = join(PHP_EOL . '     * ', $callback['parameters']);
            $arguments = join(', ', $callback['arguments']);
            $content = isset($callback['content']) ? $callback['content'] : '';

            $callbackContent .= <<<EOF
    /**
     * {$callback['annotation']}
     * {$parameters}
     */
    public function {$callbackName}({$arguments}) 
    {
{$content}
    }


EOF;
        }

        return $callbackContent;
    }

    /**
     * 构建核心服务尾部内容
     * @return string
     */
    protected static function buildCoreServerFooter()
    {
        return <<<EOF
}
EOF;
    }

    /**
     * 构建应用服务头部内容
     * @return string
     */
    protected static function buildServerHeader()
    {
        $serverName = self::$serverName;
        $coreServerName = self::$coreServerName;

        return <<<EOF
<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\application\server;


use vSwoole\core\server\\$coreServerName;

class {$serverName} extends {$coreServerName} 
{
EOF;
    }

    /**
     * 构建应用服务回调函数内容
     * @return string
     */
    protected static function buildServerCallback()
    {
        $callbackContent = PHP_EOL;

        foreach (self::getCallbackEventList() as $callbackName => $callback) {
            $parameters = join(PHP_EOL . '     * ', $callback['parameters']);
            $arguments = join(', ', $callback['arguments']);
            $content = isset($callback['content']) ? $callback['content'] : '';

            $callbackContent .= <<<EOF
    /**
     * {$callback['annotation']}
     * {$parameters}
     */
    public function {$callbackName}({$arguments}) 
    {
{$content}
    }


EOF;
        }

        return $callbackContent;
    }

    /**
     * 构建应用服务尾部内容
     * @return string
     */
    protected static function buildServerFooter()
    {
        return <<<EOF
}
EOF;
    }

    /**
     * 构建配置服务内容
     * @return string
     */
    protected static function buildConfigServer()
    {
        $serverType = self::$serverType;
        $serverName = self::$serverName;
        $serverNameUpper = strtoupper(self::$serverName);
        $serverPort = self::$serverPort;
        $adminServerPort = self::$adminServerPort;

        return <<<EOF
<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

return [
    //服务端连接配置
    'server_connect' => [
        //服务类型
        'serverType'        => {$serverType},
        //监听IP
        'host'              => '0.0.0.0',
        //监听客户端端口
        'port'              => {$serverPort},
        //服务进程运行模式
        'mode'              => SWOOLE_PROCESS,
        //服务Sock类型
        'sockType'          => SWOOLE_SOCK_TCP,
        //监听管理端IP
        'adminHost'         => '0.0.0.0',
        //监听管理端端口
        'adminPort'         => {$adminServerPort},
        //监听管理Sock类型
        'adminSockType'     => SWOOLE_SOCK_TCP,
        //监听其他客户端IP+端口
        'others'            => [],
        //监听其他客户端Sock类型
        'othersSockType'    => '',
        //服务回调事件列表
        'callbackEventList' => [],
    ],
    //服务端配置
    'server_config'  => [
        //守护进程化
        'daemonize'       => false,
        //日志
        'log_file'        => VSWOOLE_LOG_SERVER_PATH . '{$serverName}.log',
        //工作进程数
        'worker_num'      => 0,
        //工作线程数
        'reactor_num'     => 0,
        //TASK进程数
        'task_worker_num' => 0,
        //PID
        'pid_file'        => VSWOOLE_DATA_PID_PATH . VSWOOLE_{$serverNameUpper}_SERVER . '_Master' . VSWOOLE_PID_EXT,
        //SSL Crt
        'ssl_cert_file'   => '',
        //SSL Key
        'ssl_key_file'    => '',
    ],
    //管理客户端连接配置
    'client_connect' => [
        //服务Sock类型(使用SWOOLE_KEEP会出现丢消息现象，不建议开启)
        'sockType'      => SWOOLE_SOCK_TCP,
        //同步异步(PHP-FPM/APACHE模式下只允许同步)
        'syncType'      => SWOOLE_SOCK_SYNC,
        //长连接Key
        'connectionKey' => '',
        //服务器地址
        'host'          => '127.0.0.1',
        //服务器端口
        'port'          => {$adminServerPort},
        //连接超时
        'timeout'       => 3,
        //连接是否阻塞
        'flag'          => 0,
    ],
    //客户端配置
    'client_config'  => [
    
    ],
    //其他配置
    'other_config'   => [
        //是否缓存配置文件
        'is_cache_config' => true,
    ]
];
EOF;
    }

    /**
     * 构建核心客户端
     * @return string
     */
    protected static function buildCoreClient()
    {
        $coreClientServerName = self::$coreClientServerName;
        $serverNameLower = strtolower(self::$serverName);

        return <<<EOF
<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\core\client;


use vSwoole\library\client\Client;
use vSwoole\library\common\Config;

class {$coreClientServerName} extends Client
{
    /**
     * 客户端连接实例
     * @var array
     */
    protected \$clients_instance = [];
    /**
     * 连接IP
     * @var array
     */
    protected \$connect_instance = [];

    /**
     * 连接服务器
     * @param array \$connectOptions
     * @param array \$configOptions
     * @return bool|\swoole_client
     */
    public function connect(array \$connectOptions = [], array \$configOptions = [])
    {
        \$connectOptions = array_merge(Config::loadConfig('{$serverNameLower}')->get('client_connect'), \$connectOptions);
        \$configOptions = array_merge(Config::loadConfig('{$serverNameLower}')->get('client_config'), \$configOptions);
        if (false !== parent::connect(\$connectOptions, \$configOptions)) {
            \$this->clients_instance[md5(\$connectOptions['host'])] = \$this->client;
            \$this->connect_instance[md5(\$connectOptions['host'])] = \$connectOptions['host'];
            return \$this->client;
        } else {
            \$this->client->close();
            return false;
        }
    }

    /**
     * 获取已连接IP实例
     * @return array
     */
    public function getConnectIp()
    {
        return \$this->connect_instance;
    }

    /**
     * 向服务器发送指令+数据
     * @param string \$cmd
     * @param array \$data
     * @param string|null \$server_ip
     * @return bool
     */
    public function execute(string \$cmd = '', array \$data = [], string \$server_ip = null)
    {
        if (\$cmd && is_string(\$cmd) && !empty(\$this->clients_instance)) {
            \$send_data = ['cmd' => \$cmd, 'data' => \$data];
            if (empty(\$server_ip)) {
                foreach (\$this->clients_instance as \$ip => \$client) {
                    if (\$client->isConnected()) {
                        \$result[\$this->connect_instance[\$ip]] = \$client->send(json_encode(\$send_data) . "\\r\\n");
                    }
                }
            } else if (array_key_exists(md5(\$server_ip), \$this->clients_instance)) {
                \$client = \$this->clients_instance[md5(\$server_ip)];
                if (\$client->isConnected()) {
                    \$result[\$server_ip] = \$client->send(json_encode(\$send_data) . "\\r\\n");
                }
            }
        }
        return \$result ?? false;
    }

    /**
     * 请求结束，关闭客户端连接
     */
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if (\$this->client->isConnected()) {
            \$this->client->close();
        }
    }
}
EOF;
    }

    /**
     * 初始化构建
     * @param string $serverName
     * @param int $serverPort
     * @param string $serverType
     */
    protected static function initBuild(string $serverName, $serverPort = 9501, string $serverType = 'COMMON')
    {
        $serverType = in_array($serverType, ['COMMON', 'WEBSOCKET', 'HTTP', 'UDP']) ? $serverType : 'COMMON';
        self::$serverType = 'VSWOOLE_SERVER_' . $serverType;
        self::$serverName = $serverName;
        self::$coreServerName = $serverName . 'Server';
        self::$coreClientServerName = $serverName . 'Client';
        self::$serverPort = $serverPort;
        self::$adminServerPort = abs($serverPort - 1000);

        if (!defined($serverName)) {
            $serverNameUpper = strtoupper($serverName);
            $define = <<<EOF
//{$serverName}服务
defined('VSWOOLE_{$serverNameUpper}_SERVER') or define('VSWOOLE_{$serverNameUpper}_SERVER', 'Swoole_{$serverName}_Server') and define('{$serverName}', VSWOOLE_{$serverNameUpper}_SERVER);

EOF;
            @file_put_contents(VSWOOLE_CONFIG_PATH . 'const' . VSWOOLE_CONFIG_EXT, $define, FILE_APPEND);
        }
    }

    /**
     * 校验核心服务
     * @return bool
     */
    protected static function checkCoreServer()
    {
        return self::$serverName && !file_exists(VSWOOLE_CORE_SERVER_PATH . self::$coreServerName . VSWOOLE_CLASS_EXT);
    }

    /**
     * 校验应用服务
     * @return bool
     */
    protected static function checkAppServer()
    {
        return self::$serverName && !file_exists(VSWOOLE_APP_SERVER_PATH . self::$serverName . VSWOOLE_CLASS_EXT);
    }

    /**
     * 校验服务配置
     * @return bool
     */
    protected static function checkConfigServer()
    {
        return self::$serverName && !file_exists(VSWOOLE_CONFIG_PATH . strtolower(self::$serverName) . VSWOOLE_CONFIG_EXT);
    }

    /**
     * 校验核心客户端
     * @return bool
     */
    protected static function checkCoreClient()
    {
        return self::$serverName && !file_exists(VSWOOLE_CORE_CLIENT_PATH . self::$coreClientServerName . VSWOOLE_CLASS_EXT);
    }

    /**
     * 获取核心服务文件名称
     * @return string
     */
    protected static function getCoreServerFileName()
    {
        return VSWOOLE_CORE_SERVER_PATH . self::$coreServerName . VSWOOLE_CLASS_EXT;
    }

    /**
     * 获取应用服务文件名称
     * @return string
     */
    protected static function getAppServerFileName()
    {
        return VSWOOLE_APP_SERVER_PATH . self::$serverName . VSWOOLE_CLASS_EXT;
    }

    /**
     * 获取配置服务文件名称
     * @return string
     */
    protected static function getConfigServerFileName()
    {
        return VSWOOLE_CONFIG_PATH . strtolower(self::$serverName) . VSWOOLE_CONFIG_EXT;
    }

    /**
     * 获取核心客户端文件名称
     * @return string
     */
    protected static function getCoreClientFileName()
    {
        return VSWOOLE_CORE_CLIENT_PATH . self::$coreClientServerName . VSWOOLE_CLASS_EXT;
    }

    /**
     * 构建服务文件
     * @param string $serverName
     * @param int $serverPort
     * @param string $serverType
     * @return bool
     */
    public static function build(string $serverName, int $serverPort = 9501, string $serverType = 'common')
    {
        self::initBuild(ucwords($serverName), $serverPort, strtoupper($serverType));

        if (self::checkCoreServer()) {
            $coreServerContent = self::buildCoreServerHeader() . self::buildCoreServerCallback() . self::buildCoreServerFooter();
            @file_put_contents(self::getCoreServerFileName(), $coreServerContent);
        }

        if (self::checkAppServer()) {
            $serverContent = self::buildServerHeader() . self::buildServerCallback() . self::buildServerFooter();
            @file_put_contents(self::getAppServerFileName(), $serverContent);
        }

        if (self::checkConfigServer()) {
            $configServerContent = self::buildConfigServer();
            @file_put_contents(self::getConfigServerFileName(), $configServerContent);
        }

        if (self::checkCoreClient()) {
            $coreClientContent = self::buildCoreClient();
            @file_put_contents(self::getCoreClientFileName(), $coreClientContent);
        }

        return !self::checkCoreServer() && !self::checkAppServer() && !self::checkConfigServer();
    }

}