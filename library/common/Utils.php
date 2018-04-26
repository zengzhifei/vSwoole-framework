<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;

class Utils
{
    /**
     * 获取本机服务器Ip地址
     * @return string
     */
    public static function getServerIp()
    {
        $ips = swoole_get_local_ip();
        $server_ip = '';
        foreach ($ips as $ip) {
            $server_ip = $ip;
            break;
        }
        return $server_ip;
    }

    /**
     * 获取连接客户端的IP
     * @param \swoole_server $server
     * @param $fd
     * @return string
     */
    public static function getClientIp(\swoole_server $server, $fd)
    {
        $client_info = $server->getClientInfo($fd);
        return $client_info && isset($client_info['remote_ip']) ? $client_info['remote_ip'] : '';
    }

    /**
     * 获取连接客户端的端口
     * @param \swoole_server $server
     * @param $fd
     * @return string
     */
    public static function getClientPort(\swoole_server $server, $fd)
    {
        $client_info = $server->getClientInfo($fd);
        return $client_info && isset($client_info['server_port']) ? $client_info['server_port'] : '';
    }

    /**
     * 异步记录服务主进程PID
     * @param int $pid
     * @param string $pidName
     */
    public static function writePid(int $pid = 0, string $pidName = '')
    {
        if ($pidName) {
            if (!is_dir(VSWOOLE_DATA_PID_PATH)) {
                mkdir(VSWOOLE_DATA_PID_PATH, 755, true);
            }
            $pidFile = VSWOOLE_DATA_PID_PATH . $pidName . VSWOOLE_PID_EXT;
            swoole_async_writefile($pidFile, $pid);
        }
    }
}