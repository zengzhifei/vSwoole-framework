<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\application\server;


use vSwoole\application\server\logic\UdpLogic;
use vSwoole\library\common\Command;
use vSwoole\library\common\Config;
use vSwoole\library\common\Inotify;
use vSwoole\library\common\Process;
use vSwoole\library\server\UdpServer;

class Udp extends UdpServer
{
    /**
     * 启动服务器
     * @param array $connectOptions
     * @param array $configOptions
     * @throws \ReflectionException
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        parent::__construct($connectOptions, $configOptions);
    }

    /**
     * 管理进程启动回调函数
     * @param \swoole_server $server
     */
    public function onManagerStart(\swoole_server $server)
    {
        parent::onManagerStart($server); // TODO: Change the autogenerated stub

        //DEBUG模式下，监听文件变化自动重启
        if (Config::loadConfig('config')->get('is_debug')) {
            $process = new Process();
            $process->add(function () use ($server) {
                Inotify::getInstance()->watch([VSWOOLE_CONFIG_PATH, VSWOOLE_APP_SERVER_PATH . 'logic/UdpLogic.php'], function () use ($server) {
                    Command::getInstance($server)->reload();
                });
            });
        }
    }

    /**
     * 工作进程启动回调函数
     * @param \swoole_server $server
     * @param int $worker_id
     */
    public function onWorkerStart(\swoole_server $server, int $worker_id)
    {
        parent::onWorkerStart($server, $worker_id); // TODO: Change the autogenerated stub

        //加载Udp服务器逻辑类
        $this->logic = new UdpLogic($server);
    }

    /**
     * 接收客户端UDP数据回调函数
     * @param \swoole_server $server
     * @param string $data
     * @param array $client_info
     */
    public function onPacket(\swoole_server $server, string $data, array $client_info)
    {
        parent::onPacket($server, $data, $client_info); // TODO: Change the autogenerated stub

        //Udp请求逻辑处理
        $this->logic->execute($data);
    }

}