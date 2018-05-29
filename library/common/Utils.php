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
     * 获取客户端的连接端口
     * @param \swoole_server $server
     * @param $fd
     * @return string
     */
    public static function getServerPort(\swoole_server $server, $fd)
    {
        $client_info = $server->getClientInfo($fd);
        return $client_info && isset($client_info['server_port']) ? $client_info['server_port'] : '';
    }

    /**
     * 获取客户端的连接IP
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
     * 获取客户端连接时间
     * @param \swoole_server $server
     * @param $fd
     * @return int
     */
    public static function getClientConnectTime(\swoole_server $server, $fd)
    {
        $client_info = $server->getClientInfo($fd);
        return $client_info && isset($client_info['connect_time']) ? $client_info['connect_time'] : time();
    }

    /**
     * 异步记录服务主进程PID
     * @param int $pid
     * @param string $pid_name
     */
    public static function writePid(int $pid = 0, string $pid_name = '')
    {
        $pid_name && File::write(VSWOOLE_DATA_PID_PATH . $pid_name . VSWOOLE_PID_EXT, $pid);
    }

    /**
     * 获取指定服务器运行状态
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @param int $flag
     * @return bool
     */
    public static function getServerStatus(string $host, int $port, int $timeout = 3, int $flag = 0)
    {
        $client = new \swoole_client(SWOOLE_TCP);
        $client->connect($host, $port, $timeout, $flag);
        return $client->isConnected();
    }

    /**
     * 设置进程别名
     * @param string $process_name
     */
    public static function setProcessName(string $process_name = '')
    {
        if ($process_name) {
            if (function_exists('cli_set_process_title')) {
                @cli_set_process_title($process_name);
            } else {
                @swoole_set_process_name($process_name);
            }
        }
    }

    /**
     * 字节转换
     * @param int $size
     * @return string
     */
    public static function byteConvert(int $size = 0)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}